<?php

namespace app\business\model;

use think\Model;
use think\Pinyin;

class PromoteBusinessModel extends Model
{

    protected $table = 'tab_promote_business';

    protected $autoWriteTimestamp = true;

    /**
     * [登录接口]
     * @param $data
     * @param int $type
     * @author yyh
     */
    public function login($data)
    {
        $user = $this->field('id,account,password,status')->where('account', $data['account'])->find();
        if (empty($user) || $user['status'] != 1) {
            return -1;//账户不存在 或 账户被禁用
        }
        $user = $user->toArray();
        if (!xigu_compare_password($data['pwd'], $user['password'])) {
            return -2;//密码错误
        }
        $this->updateLogin($user); //更新用户登录信息
        return $user['id'];
    }
    private function updateLogin($user)
    {
        $save['last_login_time'] = time();
        $this->where('id', $user['id'])->update($save);
        session('BID', $user['id']);
        session('BNAME', $user['account']);
    }
}