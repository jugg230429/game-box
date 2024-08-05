<?php

namespace app\api\model\migrate;

use think\Model;

class UserModel extends Model
{

    protected $table = 'tab_user';

    protected $autoWriteTimestamp = false;


    /**
     * @用户数据迁移
     *
     * @author: zsl
     * @since: 2021/2/4 9:45
     */
    public function migrateData($param)
    {


        $saveData = [];
        foreach ($param as $k => $v) {
            $saveData[$k]['id'] = $v['id'];
            $saveData[$k]['account'] = $v['account'];
            $saveData[$k]['password'] = '##$##' . $v['password'];
            $saveData[$k]['promote_id'] = $v['promote_id'];
            $saveData[$k]['promote_account'] = $v['promote_account'];
            $saveData[$k]['parent_id'] = $v['parent_id'];
            $saveData[$k]['parent_name'] = $v['parent_name'];
            $saveData[$k]['fgame_id'] = $v['fgame_id'];
            $saveData[$k]['fgame_name'] = $v['fgame_name'];
            $saveData[$k]['nickname'] = $v['nickname'];
            $saveData[$k]['sex'] = $v['sex'];
            $saveData[$k]['email'] = $v['email'];
            $saveData[$k]['qq'] = $v['qq'];
            $saveData[$k]['phone'] = $v['phone'];
            $saveData[$k]['receive_address'] = $v['receive_address'];
            $saveData[$k]['real_name'] = $v['real_name'];
            $saveData[$k]['idcard'] = $v['idcard'];
            $saveData[$k]['vip_level'] = $v['vip_level'];
            $saveData[$k]['cumulative'] = $v['cumulative'];
            $saveData[$k]['balance'] = $v['balance'];
            $saveData[$k]['anti_addiction'] = $v['anti_addiction'];
            $saveData[$k]['lock_status'] = $v['lock_status'];
            $saveData[$k]['age_status'] = $v['age_status'];
            $saveData[$k]['register_way'] = $v['register_way'];
            $saveData[$k]['register_type'] = $v['register_type'];
            $saveData[$k]['register_time'] = $v['register_time'];
            $saveData[$k]['login_time'] = $v['login_time'];
            $saveData[$k]['register_ip'] = $v['register_ip'];
            $saveData[$k]['login_ip'] = $v['login_ip'];
            $saveData[$k]['is_check'] = $v['is_check'];
            $saveData[$k]['settle_check'] = $v['settle_check'];
            $saveData[$k]['sub_status'] = $v['sub_status'];
            $saveData[$k]['token'] = $v['token'];
            $saveData[$k]['unionid'] = $v['openid'];
            $saveData[$k]['head_img'] = $v['head_img'];
            $saveData[$k]['point'] = $v['point'];
            $saveData[$k]['gold_coin'] = 0;
            $saveData[$k]['puid'] = $v['puid'];
            $saveData[$k]['openid'] = $v['openid'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }

}
