<?php

namespace app\member\logic;

use app\member\model\UserBatchCreateLogModel;
use app\member\model\UserModel;
use app\member\validate\UserBatchCreateValidate;

class UserLogic
{
    public function add_small($user,$game_id,$account,$nickname,$sdk_version)
    {
        $usermodel = new UserModel();
        $find = $usermodel->field('id')->where(['account'=>$account])->find();
        if(!empty($find)){
            $account = $account.sp_random_string(4);//防止账号被使用
        }
        $res = $usermodel->register_small($user['id'], $account,$nickname, 1, 1, 0, get_promote_name(0), $game_id, get_game_name($game_id), $sdk_version);
        return $res;
    }

    /**
     * @函数或方法说明
     * @修改公共账号信息
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/3/2 19:30
     */
    public function set_public_account($request=[])
    {
        $model = new UserModel();
        if($request['id'] > 0){
            $result = $model->where('id',$request['id'])->setField('password',cmf_password($request['password']));
        }else{
            $request['register_way'] = 1;
            $request['register_time'] = time();
            $request['register_ip'] = get_client_ip();
            $request['is_platform'] = 1;
            $request['password'] = cmf_password($request['password']);
            $result = $model->field('account,register_way,is_platform,password,real_name,idcard,age_status,register_ip,register_time')->insert($request);
        }
        if($result !== false){
            return true;
        }else{
            return false;
        }
    }


    /**
     * @批量创建用户
     *
     * @author: zsl
     * @since: 2021/7/10 9:13
     */
    public function batchCreate($param)
    {
        $result = ['code' => 1, 'msg' => '生成成功', 'data' => []];
        //验证参数
        $vUserBatchCreate = new UserBatchCreateValidate();
        if (!$vUserBatchCreate -> scene('create') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vUserBatchCreate -> getError();
            return $result;
        }
        //添加记录
        $mBatchCreateLog = new UserBatchCreateLogModel();
        $logRes = $mBatchCreateLog -> createLog($param);
        if (false === $logRes) {
            $result['code'] = 0;
            $result['msg'] = '生成失败';
            return $result;
        }
        //生成账号
        $createRes = $mBatchCreateLog -> batchCreate($param);
        if (false === $createRes) {
            $result['code'] = 0;
            $result['msg'] = '生成失败';
            return $result;
        }
        return $result;
    }


}
