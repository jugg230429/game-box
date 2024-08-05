<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use app\common\logic\TplayLogic;
use app\member\model\UserTplayModel;
use app\member\model\UserTplayRecordModel;
use app\member\model\UserTplayWithdrawModel;
use app\member\validate\UserTplayValidate;
use app\game\model\ServerModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\weixinsdk\Weixin;
use think\Db;
use think\Request;

class TplayController extends AdminBaseController
{
    //判断权限
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    /**
     * @函数或方法说明
     * @任务列表
     * @author: 郭家屯
     * @since: 2020/3/20 13:08
     */
    public function task()
    {
        //更新记录
        $logic = new TplayLogic();
        $logic->updateTplayRecord();
        //查询数据
        $base = new BaseController();
        $model = new UserTplayModel();
        $game_id = $this->request->param('game_id/d');
        if ($game_id) {
            $map['tab_user_tplay.game_id'] = $game_id;
            $map1['game_id'] = $game_id;
        }
        $status = $this->request->param('status');
        if($status != ''){
            $map['tab_user_tplay.status'] = $status;
        }
        $extend['field'] = 'tab_user_tplay.id,tab_user_tplay.server_name,tab_user_tplay.game_name,tab_user_tplay.status,tab_user_tplay.start_time,tab_user_tplay.end_time,quota,tab_user_tplay.award,tab_user_tplay.cash,count(r.id) as count_sign,count(DISTINCT r.id,IF(r.status=1,true,NULL)) as count_finish,sum(r.award) as total_award,sum(r.cash) as total_cash';
        $extend['order'] = 'tab_user_tplay.id desc';
        $extend['join1'] = [['tab_user_tplay_record'=>'r'],'tab_user_tplay.id=r.tplay_id','left'];
        $extend['group'] = 'tab_user_tplay.id';
        $data = $base->data_list_join($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        //汇总
        $recordmodel = new UserTplayRecordModel();
        $total = $recordmodel->field('count(DISTINCT id,IF(status=1,true,null)) as total_sign,sum(award) as total_award,sum(award) as total_cash')->where($map1)->find();
        $this->assign('total', $total);
        return $this->fetch();
    }

    /**
     * [修改状态]
     * @author 郭家屯[gjt]
     */
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        $logic = new TplayLogic();
        $result = $logic->changeStatus($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @新增任务
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/20 14:15
     */
    public function add()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new UserTplayValidate();
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']):0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']):0;
            if(($data['start_time']<time() || $data['start_time'] = 0) && ($data['end_time'] > time() || $data['end_time'] == 0)){
                $model = new UserTplayModel();
                $tplay = $model->field('id')
                        ->where('game_id',$data['game_id'])
                        ->where('start_time',['<',time()],['eq',0],'or')
                        ->where('end_time',['>',time()],['eq',0],'or')
                        ->where('status',1)
                        ->find();
                if($tplay){
                    $this->error('同一时间只能有一个任务生效');
                }
            }
            foreach ($data['award'] as $key=>$v){
                if(empty($v)){
                    $data['award'][$key] = 0;
                }
            }
            foreach ($data['cash'] as $key=>$v){
                if(empty($v)){
                    $data['cash'][$key] = 0;
                }
            }
            $data['award'] = implode('/',$data['award']);
            $data['level'] = implode('/',$data['level'] );
            $data['cash'] = implode('/',$data['cash'] );

            //查询真实区服id
            $mServer = new ServerModel();
            $data['server_id'] = $mServer->where(['id'=>$data['server_id']])->value('server_num') ?? $data['server_id'];

            $logic = new TplayLogic();
            $result  = $logic->addTplay($data);
            if($result){
                $this->success('新增成功',url('task'));
            }else{
                $this->error('新增失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑奖励
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/20 15:29
     */
    public function edit()
    {
        $id = $this->request->param('id');
        $logic = new TplayLogic();
        $tplay = $logic->getDetail($id);
        if(empty($tplay))$this->error('试玩信息不存在');
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']):0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']):0;
            if(($data['start_time']<time() || $data['start_time'] = 0) && ($data['end_time'] > time() || $data['end_time'] == 0)){
                $model = new UserTplayModel();
                $tplay = $model->field('id')
                        ->where('id','neq',$data['id'])
                        ->where('game_id',$tplay['game_id'])
                        ->where('start_time',['<',time()],['eq',0],'or')
                        ->where('end_time',['>',time()],['eq',0],'or')
                        ->where('status',1)
                        ->find();
                if($tplay){
                    $this->error('同一时间只能有一个任务生效');
                }
            }
            foreach ($data['award'] as $key=>$v){
                if(empty($v)){
                    $data['award'][$key] = 0;
                }
            }
            foreach ($data['cash'] as $key=>$v){
                if(empty($v)){
                    $data['cash'][$key] = 0;
                }
            }
            $data['award'] = implode('/',$data['award']);
            $data['level'] = implode('/',$data['level'] );
            $data['cash'] = implode('/',$data['cash'] );
            $logic = new TplayLogic();
            $result  = $logic->editTplay($data);
            if($result){
                $this->success('编辑成功',url('task'));
            }else{
                $this->error('编辑失败');
            }
        }
        $tplay['level'] = explode('/',$tplay['level']);
        $tplay['award'] = explode('/',$tplay['award']);
        $tplay['cash'] = explode('/',$tplay['cash'] );
        $this->assign('data',$tplay);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @试玩记录
     * @author: 郭家屯
     * @since: 2020/3/20 16:11
     */
    public function record()
    {
        //查询数据
        $base = new BaseController();
        $model = new UserTplayRecordModel();
        $user_account = $this->request->param('user_account/s');
        if ($user_account != '') {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $status = $this->request->param('status');
        if($status != ''){
           $map['status'] = $status;
        }
        $extend['field'] = 'id,user_account,game_name,server_name,status,award,cash';
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @发放奖励
     * @author: 郭家屯
     * @since: 2020/3/23 9:22
     */
    public function grant()
    {
        $ids = $this->request->param('ids/a');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        $logic = new TplayLogic();
        $result = $logic->grant($ids);
        if($result){
            $this->success('发放成功');
        }else{
            $this->error('发放失败');
        }
    }

    /**
     * [ajax获取游戏区服]
     * @return \think\response\Json
     * @author 郭家屯[gjt]
     */
    public function get_ajax_area_list()
    {
        $area = new ServerModel();
        $game_id = $this->request->param('game_id', 0, 'intval');
        if (empty($game_id)) {
            return json([]);
        }
        $list = $area->where('game_id', $game_id)->field('server_num as id,server_name')->select();
        if (empty($list)) {
            return json([]);
        }
        return json($list->toArray());
    }

    /**
     * @函数或方法说明
     * @提现记录
     * @author: 郭家屯
     * @since: 2020/9/27 10:23
     */
    public function withdraw()
    {
        //查询数据
        $base = new BaseController();
        $model = new UserTplayWithdrawModel();
        $user_account = $this->request->param('user_account');
        if ($user_account) {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $type = $this->request->param('type');
        if($type != ''){
            $map['type'] = $type;
        }
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        //汇总
        $recordmodel = new UserTplayWithdrawModel();
        $total = $recordmodel->field('sum(money) as total_money,sum(fee) as total_fee')->where($map)->find();
        $this->assign('total', $total);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @提现配置
     * @author: 郭家屯
     * @since: 2020/9/27 11:02
     */
    public function withdraw_set()
    {
        $data = cmf_get_option('withdraw_set');
        $this->assign('data', $data);
        $this->assign("name", 'withdraw_set');
        return $this->fetch();
    }
    /**
     * [站点设置保存]
     * @author 郭家屯[gjt]
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $set_type = $data['set_type'];
            cmf_set_option($set_type, $data);
            write_action_log("试玩提现设置");
            $this->success("保存成功！");
        }
    }

    /**
     * @函数或方法说明
     * @补发提现
     * @author: 郭家屯
     * @since: 2020/9/28 10:44
     */
    public function reissue()
    {
        $id = $this->request->param('id');
        $model = new UserTplayWithdrawModel();
        $detail = $model->where('id',$id)->where('status','in',[0,2])->find();
        if(empty($detail)){
            $this->error('订单不存在');
        }
        if($detail['pay_way'] == 1){
            $result = $this->alipay_withdraw($detail['pay_order_number']);
        }elseif($detail['pay_way'] == 2){
            $result = $this->weixin_withdraw($detail);
        }else{
            $this->error('订单错误');
        }
        return json($result);
    }

    /**
     * @函数或方法说明
     * @打款
     * @author: 郭家屯
     * @since: 2020/9/30 13:57
     */
    public function setstatus()
    {
        $id = $this->request->param('id');
        $model = new UserTplayWithdrawModel();
        $detail = $model->where('id',$id)->where('status',0)->find();
        if(empty($detail)){
            $this->error('订单不存在');
        }
        $result = $model->where('id',$id)->setField('status',1);
        if($result){
            $this->success('打款成功');
        }else{
            $this->error('打款失败');
        }
    }

    /**
     * @函数或方法说明
     * @微信自动打款
     * @author: 郭家屯
     * @since: 2020/9/27 16:53
     */
    protected function weixin_withdraw($data=[])
    {
        $openid = get_user_entity($data['user_id'],false,'openid')['openid'];
        $weixin = new Weixin();
        $result = $weixin->weixin_transfers('试玩提现',$data['pay_order_number'],($data['money']-$data['fee']),$openid);
        //修改状态
        if($result['code'] == 1){
            $save['status'] = 1;
        }
        $save['remark'] = $result['msg'];
        Db::table('tab_user_tplay_withdraw')->where('pay_order_number',$data['pay_order_number'])->update($save);
        return $result;
    }

    /**
     * @函数或方法说明
     * @支付宝提现
     * @author: 郭家屯
     * @since: 2020/9/27 15:21
     */
    protected function alipay_withdraw($widthdrawNo)
    {
        //支付宝
        $config = get_pay_type_set('zfb');
        $pay = new \think\Pay('alipay', $config['config']);
        $vo   = new \Think\Pay\PayVo();
        $vo->setOrderNo($widthdrawNo)
                ->setTable('withdraw')
                ->setPayMethod("transfer")
                ->setDetailData('试玩提现');
        $res =  $pay->buildRequestForm($vo);
        if($res==10000){
            return ['code'=>1,'msg'=>'打款成功'];
        }else{
            return ['code'=>0,'msg'=>$res];
        }
    }

}
