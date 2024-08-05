<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\promote\validate;

use app\promote\model\PromoteModel;
use think\Validate;

class PromoteValidate extends Validate
{

    protected $rule = [
        'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
        'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$',
        'real_name' => 'require|min:2|max:25|checkChineseName',
        'mobile_phone' => 'length:11|isMobileNO',
        'email' => 'email',
        'second_pwd' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
    ];
    protected $message = [
        'account.require' => '渠道账号不能为空',
        'account.unique' => '用户名已存在',
        'account.regex' => '账号为6-30位字母或数字组合',
        'account.min' => '账号为6-30位字母或数字组合',
        'account.max' => '账号为6-30位字母或数字组合',
        'password.require' => '登录密码不能为空',
        'password.min' => '密码为6-30位字母或数字组合',
        'password.max' => '密码为6-30位字母或数字组合',
        'password.regex' => '密码为6-30位字母或数字组合',
        'real_name.require' => '姓名不能为空',
        'real_name.min' => '姓名长度需要在2-25个字符之间',
        'real_name.max' => '姓名长度需要在2-25个字符之间',
        'real_name.checkChineseName' => '姓名格式错误',
        'mobile_phone.length'=>'请输入11位手机号码',
        'mobile_phone.isMobileNO' => '手机号码格式错误',
        'email' => '邮箱格式错误',
        'second_pwd.min' => '二级密码为6-30位字母或数字组合',
        'second_pwd.max' => '二级密码为6-30位字母或数字组合',
        'second_pwd.regex' => '二级密码为6-30位字母或数字组合',
    ];

    /**
     * 构造函数
     * @access public
     * @param array $rules 验证规则
     * @param array $message 验证提示信息
     * @param array $field 验证字段描述信息
     */
    public function __construct(array $rules = [], $message = [])
    {
        $this->rule = array_merge($this->rule, $rules);
        $this->message = array_merge($this->message, $message);
    }

    protected $scene = [
        'edit' => [
            'password' => 'min:6|max:30',
            'email' => 'email',
        ],
        'proinfo1' => [
            'real_name' => 'require|min:2|max:25|checkChineseName',
            'mobile_phone' => 'require|isMobileNO',
            'email' => 'require|email',
        ],
        'proinfo2' => [
            'bank_phone' => 'require|isMobileNO',
            'bank_card' => 'require|number|length:10,19',
            'bank_name' => 'require',
            'bank_account' => 'require|checkChineseName',
            'account_openin' => 'require|min:2|checkChineseName',
        ],
        'proinfo3' => [
            'old_password' => 'require',
            'password' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
        ],
        'proinfo4' => [
            'second_pwd' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
        ],
        'proinfo5' => [
                'alipay_account' => 'require',
                'alipay_name' => 'require|min:2|max:25|checkChineseName',
        ],
        'addzipromote' => [
            'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
            'password' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
            'real_name' => 'min:2|max:25|checkChineseName',
            'mobile_phone' => 'length:11|isMobileNO',
            'email' => 'email',
            'second_pwd' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
        ],
        'addzipromote1' => [
                'account' => 'require|min:6|max:30|checkaccount:thinkphp|regex:^[A-Za-z0-9]+$',
                'password' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
                'real_name' => 'min:2|max:25|checkChineseName',
                'email' => 'email',
                'mobile_phone' => 'isMobileNO',
                'second_pwd' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
        ],
        'editzipromote1' => [
            'password' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
            'real_name' => 'min:2|checkChineseName',
            'mobile_phone' => 'isMobileNO',
            'email' => 'email',
        ],
        'register' => [
            'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
            'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$|confirm',
            'real_name' => 'checkChineseName',
            'mobile_phone' => 'isMobileNO',
        ],
        'add' => [
            'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
            'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$',
            'real_name' => 'min:2|max:25|checkChineseName',
            'mobile_phone' => 'isMobileNO',
            'email' => 'email',
        ],
        'api_add' => [
                'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
                'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$',
        ]
    ];

    //验证中文姓名
    protected function checkChineseName($value)
    {
        if (preg_match("/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/", $value)) {
            return true;
        } else {
            return false;
        }
    }


    protected function checkaccount($value){
        $model = new PromoteModel();
        $promote = $model->field('id')->where('account',$value)->find();
        if($promote){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 判断字符串是否符合手机号码格式
     * 移动号段: 134,135,136,137,138,139,147,150,151,152,157,158,159,170,178,182,183,184,187,188
     * 联通号段: 130,131,132,145,155,156,170,171,175,176,185,186
     * 电信号段: 133,149,153,170,173,177,180,181,189
     * @param str
     * @return 待检测的字符串
     */
    protected function isMobileNO($value)
    {
        return isMobileNO($value);
    }
}

