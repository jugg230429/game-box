<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\member\model\UserInvitationModel;
use app\member\model\UserInvitationRecordModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\site\model\TipModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class InvitationLogic{

    /**
     * @函数或方法说明
     * @获取奖励
     * @param array $map
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/2/21 15:56
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_award($map=[])
    {
        $model = new CouponModel();
        $data = $model->field('money,limit_money,coupon_type')
                ->where($map)
                ->where('status',1)
                ->select()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取奖励信息
     * @param array $map
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/2/21 15:56
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_one_award($map=[])
    {
        $model = new CouponModel();
        $data = $model->field('money,limit_money,spend_limit,coupon_type')
                ->where($map)
                ->where('status',1)
                ->find();
        return $data?$data->toArray():[];
    }

    /**
     * @函数或方法说明
     * @获取邀请人列表
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/21 15:56
     */
    public function get_invitation_data($user_id=0,$p=1,$limit=10)
    {
        $model = new UserInvitationModel();
        $data = $model->field('user_account,invitation_account')
                ->where('user_id',$user_id)
                ->order('id desc')
                ->page($p,$limit)
                ->select()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @添加邀请记录
     * @param $user_id
     * @param $invitation_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 11:57
     */
    public function add_record($user_id=0,$invitation_id=0)
    {
        $model = new UserInvitationModel();
        $save['user_id'] = $invitation_id;
        $save['user_account'] = get_user_entity($invitation_id,false,'account')['account'];
        $save['invitation_id'] = $user_id;
        $save['invitation_account'] = get_user_entity($user_id,false,'account')['account'];
        $save['create_time'] = time();
        $model->insert($save);
        $record = $model->where('user_id',$invitation_id)->where('create_time','between',total(1,2))->count();
        if($record > 10){
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @发放代金券
     * @param array $user
     * @param int $invitation_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 10:08
     */
    public function send_coupon($user_id=[],$invitation_id=0)
    {
        $model = new CouponModel();
        $data = $model
                ->where('status',1)
                ->where('money','gt',0)
                ->select()->toArray();
        $y_tip = false;
        $b_tip = false;
        foreach ($data as $key=>$v){
            if($v['coupon_type'] == 1){
                $y_tip = true;
                $this->sendCoupon($invitation_id,$v,$user_id);
            }elseif($v['coupon_type'] == 2 && $v['spend_limit'] == 0){
                $y_tip = true;
                $this->sendCoupon($invitation_id,$v,$user_id);
            }
            if($v['coupon_type'] == 3){
                $b_tip = true;
                $this->sendCoupon($user_id,$v);
            }
        }
        $tipmodel = new TipModel();
        //邀请人奖励通知
        if($y_tip){
            $account = substr_replace(get_user_entity($user_id,false,'account')['account'],'****',3,4);
            $save['user_id'] = $invitation_id;
            $save['title'] = "邀请奖励发放通知";
            $save['content'] = "您邀请的好友".$account."注册成功，代金券奖励已发放，请注意查收";
            $save['create_time'] = time();
            $save['type'] = 2; // 添加消息类型
            $tipmodel->insert($save);
        }
        //被邀请人奖励通知
        if($b_tip){
            $save['user_id'] = $user_id;
            $save['title'] = "注册奖励发放通知";
            $save['content'] = "您通过被邀请注册成功，代金券奖励已发放，请注意查收哦";
            $save['create_time'] = time();
            $save['type'] = 2;  // 添加消息类型
            $tipmodel->insert($save);
        }
    }


    /**
     * @函数或方法说明
     * @发放代金券
     * @param int $user_id
     * @param int $coupon_id
     *
     * @author: 郭家屯
     * @since: 2020/2/5 11:30
     */
    public function sendCoupon($user_id=0,$coupon=[],$be_invite=0)
    {
        $model = new CouponModel();
        $recordmodel = new CouponRecordModel();
        $invitation_model = new UserInvitationRecordModel();
        $add['user_id'] = $user_id;
        $add['user_account'] = get_user_entity($user_id,false,'account')['account'];
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
        $add['get_way'] = 1;
        $save['be_invite'] = $be_invite?:0;
        $save['user_id'] = $user_id;
        $save['user_account'] = $add['user_account'];
        $save['type'] = $coupon['coupon_type'];
        $save['coupon_id'] = $coupon['id'];
        $save['create_time'] = time();
        Db::startTrans();
        try{
            $recordmodel->insert($add);
            $model->where('id',$coupon['id'])->setInc('stock',1);
            $invitation_model->insert($save);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @发放消费代金券
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/25 11:45
     */
    public function send_spend_coupon($invitation_id=0,$user_id,$money=0)
    {
        $model = new CouponModel();
        $coupon = $model
                ->where('status',1)
                ->where('spend_limit',['gt',0],['elt',$money])
                ->where('coupon_type',2)
                ->where('end_time',['eq',0],['gt',time()],'or')
                ->find();
        if(!$coupon){
            return 0;
        }
        $coupon = $coupon->toArray();
        $recordmodel = new UserInvitationRecordModel();
        $record = $recordmodel
                ->field('id')
                ->where('user_id',$invitation_id)
                ->where('be_invite',$user_id)
                ->where('type',2)
                ->find();
        if($record){
            return 0;
        }
        $count = $recordmodel
                ->field('id')
                ->where('user_id',$invitation_id)
                ->where('type',2)
                ->count();
        if($count >= 10){
            return 0;
        }
        $result = $this->sendCoupon($invitation_id,$coupon,$user_id);
        if($result){
            $tipmodel = new TipModel();
            //邀请人奖励通知
            $account = substr_replace(get_user_entity($user_id,false,'account')['account'],'****',3,4);
            $save['user_id'] = $invitation_id;
            $save['title'] = "邀请奖励发放通知";
            $save['content'] = "您邀请的好友".$account."消费达标，代金券奖励已发放，请注意查收";
            $save['create_time'] = time();
            $save['type'] = 2;
            $tipmodel->insert($save);
        }
    }











}