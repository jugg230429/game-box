<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\member\model\PointRecordModel;
use app\member\model\PointTypeModel;
use app\member\model\UserModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PointLogic{

    public function sign_detail($user_id,$check_status=0)
    {
        $point_arr = [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0];
        $signed_day = 0;
        $pointTypeData = (new PointTypeModel())->field('id,name,point,time_of_day,status')->where('key','=','sign_in')->find();
        if(empty($pointTypeData)){
            return -1;
        }
        if($check_status==1){
            return 1;//允许签到
        }
        $modelPointRecord = new PointRecordModel();
        $lastSignData = $modelPointRecord
            ->field('day,create_time,FROM_UNIXTIME(create_time,\'%Y-%m-%d\') as sign_date')
            ->where('user_id','=',$user_id)
            ->where('type_id','=',$pointTypeData->id)
            ->order('id desc')
            ->find();
        $signed_day = $lastSignData->day;
        if(empty($lastSignData)){
            $signed_day = 0;//未签到过
        }
        if(strtotime($lastSignData->sign_date)+86400<strtotime(date('Y-m-d'))){
            $signed_day = 0;//中断
        }
        $today_signed = 0;
        if(strtotime($lastSignData->sign_date)+86400>strtotime(date('Y-m-d'))){
            $signed_day = $lastSignData->day;//今日已签过
            $today_signed = 1;
            $today_signed22 = 3;  // 3 已完成(和页面mall_task对应)
        }
        foreach ($point_arr as $key=>&$value){
            $value = $pointTypeData->point+($key-1)*$pointTypeData->time_of_day;
        }
        unset($value);
        $total_sign = $modelPointRecord->where('user_id','=',$user_id)->where('type_id','=',$pointTypeData->id)->count();
        $totay_point = $today_signed==1?$point_arr[$signed_day]:$point_arr[$signed_day+1];
        return ['status'=>$pointTypeData->status,'point_arr'=>$point_arr,'today_signed'=>$today_signed,'signed_day'=>$signed_day,'total_sign'=>$total_sign,'totay_point'=>$totay_point,'today_signed22'=>$today_signed22];
    }

    public function sign_in(int $user_id)
    {
        if(!$user_id){
            return;
        }
        $pointTypeData = (new PointTypeModel())->field('id,name,point,time_of_day')->where('key','=','sign_in')->where('status','=',1)->find();
        if(empty($pointTypeData)){
            return -1;//未开启签到奖励
        }
        $userData = get_user_entity($user_id,false,'real_name,idcard');
        if(!$userData['real_name']||!$userData['idcard']){
            return -3;//未实名认证
        }
        $modelPointRecord = new PointRecordModel();
        $lastSignData = $modelPointRecord
            ->field('day,create_time,FROM_UNIXTIME(create_time,\'%Y-%m-%d\') as sign_date')
            ->where('user_id','=',$user_id)
            ->where('type_id','=',$pointTypeData->id)
            ->order('id desc')
            ->find();
        $day = $lastSignData->day;
        if(empty($lastSignData)){
            $day = 0;//未签到过
        }
        if(strtotime($lastSignData->sign_date)+86400<strtotime(date('Y-m-d'))){
            $day = 0;//中断
        }
        if(strtotime($lastSignData->sign_date)+86400>strtotime(date('Y-m-d'))){
           return -2;//今日已签过
        }
        if($day>=7){//7天一循环
            $day = 0;
        }
        $addpoint = $pointTypeData->point+$day*$pointTypeData->time_of_day;
        $modelPointRecord->type_id = $pointTypeData->id;
        $modelPointRecord->type_name = $pointTypeData->name;
        $modelPointRecord->user_id = $user_id;
        $modelPointRecord->user_account = get_user_name($user_id);
        $modelPointRecord->point = $addpoint;
        $modelPointRecord->day = $day+1;
        $modelPointRecord->save();
        if($addpoint<0){
            return 1;
        }
        (new UserModel())->where('id','=',$user_id)->setInc('point',$addpoint);
        return $addpoint;
    }

    /**
     * @函数或方法说明
     * @补发首冲积分
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/8/24 10:02
     */
    public function first_pay($user_id=0){
        if(!$user_id){
            return -1;
        }
        //充值记录
        if(!user_is_paied($user_id)){
            return -2;
        }
        //领取记录
        $record = Db::table('tab_user_point_record')
                ->field('id')
                ->where('user_id',$user_id)
                ->where('type_id',7)
                ->find();
        if($record){
            return -3;
        }
        $modelUserModel = new UserModel();
        $modelUserModel->task_complete($user_id,'first_pay',1);//首冲
        return 1;
    }

    /**
     * 分享到微信朋友圈 或者 分享到QQ空间 完成分享任务(积分自动发放)
     * created by wjd 2021-6-17 11:43:52
    */
    public function grantShareOutTask($user_id, $key)
    {   
        $todayStartTime = strtotime(date('Y-m-d')); 
        $todayEndTime = strtotime(date('Y-m-d')) + 86399;
        $pointTypeData = (new PointTypeModel())->field('id,name,point,time_of_day')->where('key','=', $key)->where('status','=',1)->find();
        if(empty($pointTypeData)){
            return ['code'=>-1, 'msg'=>'未开启分享奖励'];
        }
        // 判断今天是否已经分享过
        $map = [];
        $map['user_id'] = $user_id;
        $map['create_time'] = ['between',[$todayStartTime, $todayEndTime]];
        $map['type_id'] = $pointTypeData->id;
        $userPointRecordModel = new PointRecordModel();
        $shareInfo = $userPointRecordModel->where($map)->find();
        if(!empty($shareInfo)){
            return ['code'=>-1, 'msg'=>'今日已经签到过'];
        }
        $saveData = [
            'type_id'=>$pointTypeData->id,
            'type_name'=>$pointTypeData->name,
            'user_id'=>$user_id,
            'user_account' => get_user_name($user_id),
            'point'=>$pointTypeData->point,
            'create_time'=>time()
        ];
        // 启动事务
        $flag = 0;  // 0 未执行成功, 1 执行成功
        Db::startTrans();
        try{
            $saveRes = $userPointRecordModel->save($saveData);
            // 今天任务 发放积分
            $addpoint = $pointTypeData->point;
            (new UserModel())->where('id','=',$user_id)->setInc('point',$addpoint);
            // 提交事务
            $flag = 1;
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $flag = 0;
            Db::rollback();
        }
        if($flag == 1){
            return ['code'=>1, 'msg'=>'任务完成,已发放奖励'];
        }else{
            return ['code'=>-1, 'msg'=>'请稍后重试 ~~'];
        }
       
    }
}
