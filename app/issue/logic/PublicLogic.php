<?php

namespace app\issue\logic;

use app\issue\model\OpenUserLoginRecordModel;
use app\issue\model\OpenUserModel;
use app\issue\validate\PublicValidate;
use think\Db;

class PublicLogic
{

    /**
     * @分发平台用户登录
     *
     * @author: zsl
     * @since: 2020/7/11 11:30
     */
    public function login($param)
    {
        $result = ['code' => 200, 'msg' => '登录成功', 'data' => []];
        $vPublic = new PublicValidate();
        if (!$vPublic -> scene('login') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vPublic -> getError();
            return $result;
        }
        //验证,查询用户信息
        $vRes = $vPublic -> checkUserInfo($param);
        if ($vRes['code'] == 0) {
            $result['code'] = 0;
            $result['msg'] = $vRes['msg'];
            return $result;
        }
        Db ::startTrans();
        try {

            $userInfo = $vRes['data'];
            //验证通过,保存登录状态
            session('issue_open_user_info', $userInfo -> toArray());
            //更新用户信息
            $mOpenUser = new OpenUserModel();
            $mOpenUser -> save(['last_login_time' => time(), 'last_login_ip' => get_client_ip()], ['id' => $userInfo['id']]);
            //新增登录记录
            $mOpenUserLoginRecord = new OpenUserLoginRecordModel();
            $record = [];
            $record['open_user_id'] = $userInfo -> id;
            $record['login_ip'] = get_client_ip();
            $record['login_type'] = (request()->module()=='issuesy')?'pack':'pc';
            $mOpenUserLoginRecord -> allowField(false) -> save($record);
            Db ::commit();
            $result['data'] = $userInfo;

        } catch (\Exception $e) {
            Db ::rollback();
            $result['code'] = 0;
            $result['msg'] = '发生错误,登录失败';
            return $result;
        }
        return $result;
    }


}