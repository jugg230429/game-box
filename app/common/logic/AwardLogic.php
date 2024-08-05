<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\member\model\UserAwardModel;
use app\member\model\UserAwardRecordModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class AwardLogic{

    /**
     * @函数或方法说明
     * @获取奖品列表
     * @author: 郭家屯
     * @since: 2020/8/12 17:30
     */
    public function getaward()
    {
        $model = new UserAwardModel();
        $data = $model->getaward();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取抽奖次数
     * @author: 郭家屯
     * @since: 2020/8/12 17:34
     */
    public function get_award_count($user_id=0)
    {
        if($user_id){
            $model = new UserAwardRecordModel();
            $map['user_id'] = $user_id;
            $map['create_time'] = total(1,1);
            $count = $model->where($map)->count();
            $count = $count ? : 0;
            $vip_level = get_user_entity($user_id,false,'vip_level')['vip_level'];
        }
        $set = cmf_get_option('award_set');
        $point_draw = $set['draw_limit']?:0;
        $free_draw = $set['free_draw']?:0;
        //获取VIP数量
        if($set['vip_level'] && $vip_level > 0){
            $vip_count = $this->get_vip_count($set['vip_level'],$set['count'],$vip_level);
        }
        return $point_draw + $free_draw + $vip_count - $count;
    }

    /**
     * @函数或方法说明
     * @抽奖
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/8/13 9:43
     */
    public function draw($user_id=0)
    {
        $recordmodel = new UserAwardRecordModel();
        $map['user_id'] = $user_id;
        $map['create_time'] = total(1,1);
        $count = $recordmodel->where($map)->count();
        $count = $count ? : 0;
        $user = get_user_entity($user_id,false,'id,account,point,vip_level');
        $vip_level = $user['vip_level'];
        $set = cmf_get_option('award_set');
        $point_draw = $set['draw_limit']?:0;//积分次数
        $free_draw = $set['free_draw']?:0;//免费次数
        //VIP增加数量
        if($set['vip_level'] && $vip_level > 0){
            $vip_count = $this->get_vip_count($set['vip_level'],$set['count'],$vip_level);
        }
        if($count >= ($point_draw + $free_draw + $vip_count)){
            return ['code'=>0,'msg'=>'抽奖次数已用完'];
        }
        $award_id = $this->get_draw_award();
        $model = new UserAwardModel();
        $award = $model->get_award_info($award_id);
        if(!$award){
            return ['code'=>0,'msg'=>'奖品不存在'];
        }
        // 启动事务
        Db::startTrans();
        try{
            //使用积分
            if($count >= ($free_draw+$vip_count)){
                if($set['user_point'] > $user['point']){
                    return ['code'=>0,'msg'=>'积分不足'];
                }
                //写入抽奖记录
                $record = $this->record_data($user,$award,$set['user_point']);
                $record_id = $recordmodel->insertGetId($record);
                Db::table('tab_user')->where('id',$user['id'])->setDec('point',$set['user_point']);//扣除积分
                //写入积分使用记录
                $point_record = $this->point_record_data($user,$record_id,$award,$set['user_point']);
                Db::table('tab_user_point_use')->insert($point_record);
            }else{
                //写入抽奖记录
                $record = $this->record_data($user,$award);
                $recordmodel->insert($record);
            }
            //获取奖励
            switch ($award['type']){
                case 1://发放积分
                    Db::table('tab_user')->where('id',$user['id'])->setInc('point',$award['award']);//发放积分
                    //积分记录
                    $ponit_data = $this->get_point_data($user,$award);
                    Db::table('tab_user_point_record')->insert($ponit_data);
                    //扣除库存
                    $model->where('id',$award_id)->setDec('stock');
                    break;
                case 2://发放平台币
                    Db::table('tab_user')->where('id',$user['id'])->setInc('balance',$award['award']);//发放积分
                    //扣除库存
                    $model->where('id',$award_id)->setDec('stock');
                    break;
                case 3://发放代金券
                    //发放代金券
                    $coupon = get_coupon_info($award['award']);
                    $coupon_data = $this->get_coupon_data($coupon,$user);
                    Db::table('tab_coupon_record')->insert($coupon_data);
                    //扣除库存
                    $model->where('id',$award_id)->setDec('stock');
                    break;
                case 4://商品
                    //扣除库存
                    $model->where('id',$award_id)->setDec('stock');
                    break;
                case 5://谢谢惠顾
                    break;
                default:
                    return ['code'=>0,'msg'=>'奖品不存在'];
            }
            //增加发放记录
            $model->where('id',$award_id)->setInc('number');
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code'=>0,'msg'=>'抽奖失败'];
        }
        return ['code'=>1,'msg'=>'抽奖成功','award_id'=>$award_id];
    }

    /**
     * @函数或方法说明
     * @获取VIP免费抽取数量
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/13 11:36
     */
    protected function get_vip_count($vip,$count,$vip_level)
    {
        $vip = explode(',',$vip);
        rsort($vip);
        $vip_levels = count($vip);
        foreach ($vip as $key=>$v){
            if($vip_level >= $v){
                $index = $vip_levels-1 - $key;
                break;
            }
        }
        if($vip_level >= end($vip)){
            $vip_count = explode(',',$count)[$index];//vip额外次数
        }
        return $vip_count;
    }

    /**
     * @函数或方法说明
     * @获取奖品id
     * @author: 郭家屯
     * @since: 2020/8/13 10:43
     */
    protected function get_draw_award()
    {
        $model = new UserAwardModel();
        $data = $model->getaward();
        $list = [];
        foreach ($data as $key=>$v){
            if($v['type'] == 5 || $v['stock'] > 0) {
                $list = array_merge($list, array_fill(0, $v['probability'], $v['id']));//生成奖品数组
            }
        }
        shuffle($list);//打乱数组顺序
        $key = array_rand($list,1);//获取数组随机下标
        return $list[$key];
    }


    /**
     * @函数或方法说明
     * @奖品记录数据
     * @author: 郭家屯
     * @since: 2020/8/13 11:27
     */
    protected function record_data($user=[],$award=[],$point = 0)
    {
        $data['user_id'] = $user['id'];
        $data['user_account'] = $user['account'];
        $data['name'] = $award['name'];
        $data['award'] = $award['award'];
        $data['type'] = $award['type'];
        $data['point'] = $point;
        $data['create_time'] = time();
        return $data;
    }

    /**
     * @函数或方法说明
     * @积分使用记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/13 11:38
     */
    protected function point_record_data($user=[],$record_id=0,$award=[],$point=0)
    {
        $data['user_id'] = $user['id'];
        $data['user_account'] = $user['account'];
        $data['type_id'] = 2;
        $data['type_name'] = '积分抽奖';
        $data['item_id'] = $record_id;
        $data['create_time'] = time();
        $data['point'] = $point;
        $data['good_name'] = $award['name'];
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取积分获取几率
     *
     * @author: 郭家屯
     * @since: 2020/8/13 13:42
     */
    protected function get_point_data($user=[],$award=[])
    {
        $data['type_id'] = 13;
        $data['type_name'] = '积分抽奖';
        $data['user_id'] = $user['id'];
        $data['user_account'] = $user['account'];
        $data['point'] = $award['award'];
        $data['create_time'] = time();
        return $data;
    }

    /**
     * @函数或方法说明
     * @代金券数据
     * @author: 郭家屯
     * @since: 2020/8/13 13:50
     */
    protected function get_coupon_data($coupon=[],$user=[])
    {
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = $coupon['coupon_name'];
        $add['game_id'] = $coupon['game_id'];
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['start_time'] = $coupon['start_time'];
        $add['end_time'] = $coupon['end_time'];
        $add['limit'] = $coupon['limit'];
        $add['get_way'] = 2;//抽奖
        return $add;
    }



}