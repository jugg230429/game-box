<?php

namespace app\user\model;

use app\member\model\UserLoginRecordModel;
use app\member\model\UserLoginRecordMongodbModel;
use think\Db;
use think\Model;

class UserUnsubscribeModel extends Model
{


    protected $table = 'tab_user_unsubscribe';
    protected $autoWriteTimestamp = true;


    /**
     * @获取用户注销状态
     *
     * @author: zsl
     * @since: 2021/5/13 15:26
     */
    public function getUnsubscribeStatus($user_id)
    {
        $where = [];
        $where['user_id'] = $user_id;
        $status = $this -> where($where) -> value('status');
        return $status ? $status : 0;
    }

    /**
     * @插入注销申请记录
     *
     * @author: zsl
     * @since: 2021/5/13 15:31
     */
    public function insertUnsubscribeData($user_id)
    {
        // 验证用户是否存在
        $userInfo = get_user_entity($user_id, false, 'id,account');
        if (empty($userInfo)) {
            return false;
        }
        //实际注销时间
        $unsubscribe_time = time() + (86400 * 7);
        //写入数据
        $this -> user_id = $userInfo['id'];
        $this -> user_account = $userInfo['account'];
        $this -> user_account_alias = $userInfo['account'] . '（已注销 ' . date("YmdHis", $unsubscribe_time).'）';
        $this -> apply_time = time();
        $this -> unsubscribe_time = $unsubscribe_time;
        $this -> err_num = 0;
        $result = $this -> isUpdate(false) -> allowField(true) -> save();
        return !!$result;
    }


    /**
     * @删除注销记录
     *
     * @author: zsl
     * @since: 2021/5/13 17:50
     */
    public function delUnsubscribeData($user_id)
    {
        // 验证用户是否存在
        $userInfo = get_user_entity($user_id, false, 'id,account');
        if (empty($userInfo)) {
            return false;
        }
        $where = [];
        $where['status'] = 1;// 注销中
        $where['user_id'] = $user_id;
        $result = $this -> where($where) -> delete();
        return $result;
    }


    /**
     * @获取满足注销时间用户
     *
     * @author: zsl
     * @since: 2021/5/14 10:37
     */
    public function getExpireUser()
    {

        $field = "*";
        $where = [];
        $where['err_num'] = ['lt',4];
        $where['status'] = 1;
        $where['unsubscribe_time'] = ['elt', time()];
        $result = $this -> field($field) -> where($where) -> order('id asc') -> find();
        return $result;
    }


    /**
     * @注销账号
     *
     * @author: zsl
     * @since: 2021/5/14 11:10
     */
    public function doUnsubscribe($unsubscribeInfo)
    {

        $userInfo = get_user_entity($unsubscribeInfo['user_id'], false, 'id,email,phone');
        // 1. 修改 tab_user 表数据
        $mUser = new \app\member\model\UserModel();
        $where = [];
        $where['id'] = $unsubscribeInfo['user_id'];
        $where['puid'] = 0;
        $data = [];
        $data['is_unsubscribe'] = 1; // 已注销
        $data['account'] = $unsubscribeInfo['user_account_alias'];
        $data['password'] = '';
        $data['phone'] = '';
        $data['real_name'] = '';
        $data['idcard'] = '';
        $data['wx_nickname'] = '';
        $data['openid'] = '';
        $mUser -> save($data, $where);
        // 2. 更新关联表用户名称
        Db ::table('tab_coupon_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_game_comment') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_game_gift_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_promote_bind') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_promote_settlement') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_spend') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_spend_bind') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_spend_distinction') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_spend_provide') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_spend_rebate_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_award_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_balance_edit') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_deduct_bind') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_feedback') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_invitation') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_invitation_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);

        $mUserLoginRecord = new UserLoginRecordModel();
        $mUserLoginRecord -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        try{
            $mUserLoginRecordMongodb = new UserLoginRecordMongodbModel();
            $mUserLoginRecordMongodb -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        }catch (\Exception $e){

        }

        Db ::table('tab_user_member') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_mend') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_play') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_play_info') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_point_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_point_shop_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_point_use') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_tplay_record') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_tplay_withdraw') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_transaction') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_transaction_order') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_user_transaction_profit') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        Db ::table('tab_warning') -> where(['user_id' => $unsubscribeInfo['user_id']]) -> setField('user_account', $unsubscribeInfo['user_account_alias']);
        return $userInfo;
    }

}
