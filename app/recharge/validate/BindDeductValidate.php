<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\recharge\validate;

use think\Validate;
use think\Db;

class BindDeductValidate extends Validate
{
    protected $rule = [
            'account' => 'require|checkaccount',
            'game_id' => 'require',
            'amount' => 'require|number',
            'password' => 'require|checkpassword',
    ];
    protected $message = [
            'account.require' => '玩家账号不能为空',
            'account.checkaccount' => '用户不存在',
            'game_id.require'=>'游戏不能为空',
            'amount' => '请输入大于0的整数',
            'password.require' => '密码不能为空',
            'password.checkpassword' => '密码错误，请重新输入',
    ];

    /**
     * @函数或方法说明
     * @检查账号
     * @param $value
     * @param $rule
     * @param $data
     *
     * @author: 郭家屯
     * @since: 2019/10/14 10:27
     */
    protected function checkaccount($value,$rule,$data){
        $user = get_user_entity($value,true,'id');
        if($user){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @函数或方法说明
     * @检查二级密码
     * @param $value
     * @param $rule
     * @param $data
     *
     * @author: 郭家屯
     * @since: 2019/10/14 10:27
     */
    protected function checkpassword($value,$rule,$data){
        $result = Db::name('user')->field('second_pass')->where(['id' => cmf_get_current_admin_id()])->find();
        if (!xigu_compare_password($value, $result['second_pass'])) {
           return false;
        }else{
            return true;
        }
    }

}

