<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelwap\controller;

use app\channelsite\logic\WelfareLogic;
use app\common\logic\PromoteLogic;
use app\game\model\GameModel;
use app\member\model\UserModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\validate\CouponValidate;
use think\Db;
use Think\Exception;
use think\Request;
use app\promote\model\PromoteModel;

class CouponController extends BaseController
{

    public function lists()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @代金券列表
     * @author: 郭家屯
     * @since: 2020/11/4 15:07
     */
    public function get_lists()
    {
        $request = $this->request->param();
        $logic = new PromoteLogic();
        if($request['limit_money'] == 1){
            $request['limit_money'] = '0';
        }
        $data = $logic->get_coupon_lists(PID,$request)->each(function($item,$key){
            $item['start_time'] = $item['start_time'] ? date('Y-m-d',$item['start_time']) : '永久';
            $item['end_time'] = $item['end_time'] ? date('Y-m-d',$item['end_time']) : '永久';
            $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            $item['url'] = url('coupon/del',['id'=>$item['id']]);
            return $item;
        });
        return json($data);
    }

    /**
     * [删除代金券]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id/d');
        if (!$id) $this->error('请选择要操作的数据');
        $model = new CouponModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
    /**
     * @函数或方法说明
     * @新增
     * @author: 郭家屯
     * @since: 2020/9/19 11:56
     */
    public function add_coupon()
    {
        if($this->request->isPost()){
            $model = new CouponModel();
            $validate = new CouponValidate();
            $data=  $this->request->param();
            if(empty($data['pgame_id'])){
                $this->error('请选择游戏');
            }
            $data['game_name'] = get_game_entity($data['pgame_id'],'game_name')['game_name'];
            $data['game_id'] = -1;
            $data['type'] = 1;
            $data['mold'] = 1;
            $data['coupon_type'] = 6;
            $data['pid'] = PID;
            $data['status'] = 1;
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            $data['create_time'] = time();
            if(!$validate->scene('promote')->check($data)){
                $this->error($validate->getError());
            }
            $result = $model->insert($data);
            if($result){
                $this->success('添加成功',url('lists'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * #发放记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/21 10:54
     */
    public function grant_list()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取发放记录
     * @author: 郭家屯
     * @since: 2020/11/4 16:27
     */
    public function get_grant_list()
    {
        $logic = new WelfareLogic();
        $request = $this->request->param();
        $map['pid'] = PID;

        // 排除测试中的游戏
        $mGame = new GameModel();
        $testGameIds = $mGame -> where(['test_game_status' => '1']) -> column('id');
        if (!empty($testGameIds)) {
            $map['pgame_id'] = ['not in', $testGameIds];
        }

        $data = $logic->getCouponRecord($request,$map)->each(function($item,$key){
            $item['url'] = url('coupon/recovery',['ids'=>$item['id']]);
            $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            $item['update_time'] = $item['update_time'] ? date('Y-m-d H:i:s',$item['update_time']) : '-';
            $item['condition'] = $item['limit_money'] ? '满减：满'.$item['limit_money'] : '无门槛';
            if($item['is_delete'] == 1){
                $item['status'] = '已回收';
            }else{
                if($item['status'] == 0){
                    if($item['end_time'] == 0 || $item['end_time'] > time()){
                        $item['status'] = '未使用';
                    }else{
                        $item['status'] = '已过期';
                    }
                }else{
                    $item['status'] = '已使用';
                }
                if($item['status'] != '已使用'){
                    $item['pay_amount'] = '-';
                    $item['cost'] = '-';
                }
            }
            return $item;
        });
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
        }

        //累计统计
        $total = $logic->get_coupon_total($request,$map);
        return json(['data'=>$data,'total'=>$total[0]]);
    }

    /**
     * @函数或方法说明
     * @发放方法
     * @author: 郭家屯
     * @since: 2020/9/21 10:54
     */
    public function grant()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @提交发放请求
     * @author: 郭家屯
     * @since: 2020/10/10 9:42
     */
    public function grant_post()
    {
        if($this->request->isPost()){
            $coupon_ids = $this->request->param('coupon_ids/a');
            $ids = $this->request->param('ids/a');
            if(empty($ids)){
                $this->error('请选择用户');
            }
            if(empty($coupon_ids)){
                $this->error('请选择代金券');
            }
            $couponmodel = new CouponModel();
            $coupon = $couponmodel->where('pid',PID)->where('id','in',$coupon_ids)->select()->toArray();
            if(empty($coupon)){
                $this->error('代金券不存在');
            }
            $usermodel = new UserModel();
            $user = $usermodel->field('id,account')->where('id','in',$ids)->where('promote_id',PID)->select()->toArray();
            if(empty($user)){
                $this->error('用户不存在');
            }
            $add = [];
            $total = 0;
            $lPay = new \app\common\logic\PayLogic();
            foreach ($coupon as $key=>$vo){
                $game_id = get_game_entity($vo['pgame_id'],'relation_game_id')['relation_game_id'];
                foreach ($user as $k=>$v){
                    $discount = $lPay -> get_agent_discount($vo['pgame_id'], PID,$v['id']);
                    $total += $vo['money']*$discount['discount']/10;
                    $add[] = $this->get_coupon_data($vo,$v,$vo['money']*$discount['discount']/10,$game_id);
                }
            }
            if($total > $this->promote['balance_coin']){
                $this->error('平台币余额不足无法发放，请先充值');
            }
            Db::startTrans();
            try{
                $model = new PromoteModel();
                //扣除平台币
                $model->where('id',PID)->setDec('balance_coin',$total);
                //写入代金券记录
                $recordmodel = new CouponRecordModel();
                $recordmodel->insertAll($add);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error('发放失败');
            }
            $this->success('发放成功',url('grant_list'));
        }
        $this->error('请求错误');
    }

    /**
     * @函数或方法说明
     * @回收方法
     * @author: 郭家屯
     * @since: 2020/9/21 10:55
     */
    public function recovery()
    {
        $ids = $this->request->param('ids');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new CouponRecordModel();
        $sum = $model->where('id', 'in', $ids)
                ->where('pid',PID)
                ->where('status',0)
                ->where('end_time',['>', time()], ['=', 0], 'or')
                ->where('is_delete',0)
                ->sum('deduct_amount');
        Db::startTrans();
        try{
            //删除操作
            $save['is_delete'] = 1;
            $save['deduct_amount'] = 0;
            $model->where('id', 'in', $ids)
                    ->where('pid',PID)
                    ->where('status',0)
                    ->where('end_time',['>', time()], ['=', 0],'or')
                    ->where('is_delete',0)
                    ->update($save);
            //回收返平台币
            $promotemodel = new PromoteModel();
            $promotemodel->where('id',PID)->setInc('balance_coin',$sum);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('回收失败');
        }
        if (count($ids) > 1) {
            write_action_log("批量回收代金券记录");
        } else {
            write_action_log("回收代金券记录");
        }
        $this->success('回收成功');
    }

    /**
     * @函数或方法说明
     * @代金券数据
     * @author: 郭家屯
     * @since: 2020/10/10 10:42
     */
    protected function get_coupon_data($coupon=[],$user=[],$deduct_amount = '0',$game_id=0)
    {
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = $coupon['coupon_name'];
        $add['game_id'] = $game_id;
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['start_time'] = $coupon['start_time'];
        $add['end_time'] = $coupon['end_time'];
        $add['limit'] = $coupon['limit'];
        $add['pid'] = PID;
        $add['deduct_amount'] = $deduct_amount;
        $add['get_way'] = 2;//推广员发放
        return $add;
    }



    /**
     * 方法 ajax_get_user_lists_info
     *
     * @descript 描述
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/30 0030 13:16
     */
    public function ajax_get_user_lists_info()
    {
        $page = $this->request->param('page');
        $name = $this->request->param('name');
        $this -> success('','', $this -> get_user_lists_info_private($page, $name));
    }

    /**
     * 方法 get_user_lists_info_private
     *
     * @descript 描述
     *
     * @param int $page
     * @param string $name
     * @return array
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/30 0030 13:16
     */
    private function get_user_lists_info_private($page=1, $name='')
    {
        $result = ['total' => 0, 'list' => [], 'page' => 1];
        try {
            $map = ['promote_id' => PID];
            if (!empty($name)) {
                $map['account'] = ['like', '%'.htmlencode($name).'%'];
            }

            $count = DB::table('tab_user')
                ->where($map)
                ->count();
            if ($count < 1) {
                return $result;
            }
//            dump();die;
            $limit = 40;
            $total = intval(($count - 1)/ $limit) + 1;

            $page = $page < 1 ? 1 : $page;
            $page = $page > $total ? $total : $page;

            $data = DB::table('tab_user')->field('id,account')
                ->where($map)
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 判断当前渠道是否有权限显示完成整手机号或完整账号
            $ys_show_promote = get_promote_privicy_two_value();
            foreach ($data as $k5 => $v5) {
                if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                    $data[$k5]['account'] = get_ys_string($v5['account'],$ys_show_promote['account_show_promote']);
                }
            }

            return [
                'total' => $total,
                'page' => $page,
                'list' => $data
            ];
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * 获取渠道的优惠券数据
     * by:byh 2021-9-22
     */
    public function ajax_get_coupon_lists_info()
    {
        $name = $this->request->param('name');
        $map = [];
        if(!empty($name)){
            $map['coupon_name'] = ['like', '%'.htmlencode($name).'%'];
        }
        $filed = 'id,coupon_name,game_name,money,start_time,end_time';
        $data =  get_coupon_list($filed,$map,1,PID);
        if(!empty($data)){
            foreach ($data as $k => $v){
                $str = '';
                if(empty($v['stat_time'])){
                    $str = '永久';
                }else{
                    $str = date('Y-m-d',$v['stat_time']);
                }
                $str .= '至';
                if(empty($v['end_time'])){
                    $str .= '永久';
                }else{
                    $str .= date('Y-m-d',$v['end_time']);
                }
                $data[$k]['time_str'] = $str;
            }
        }
        return $data;
    }

}
