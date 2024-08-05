<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\issue\validate;

use think\Validate;
use app\issue\model\OpenUserModel;

class OpenUserValidate extends Validate
{

    protected $rule = [
            'account' => 'require|alphaNum|length:6,30|unique:open_user',
            'password' => 'require|alphaNum|length:6,30',
            //'nickname' => 'require',
            'company_name' => 'require',
            'linkman' => 'require',
            'job' => 'require',
            'mobile' => 'require',
            'wechat' => 'require',
            'qq' => 'require',
            'address' => 'require',
            'business_license_img' => 'require',
            'wenwangwen_img' => 'require',
            'icp_img' => 'require',
            'new_password' => 'require|alphaNum|length:6,15',
            're_new_password' => 'require|confirm:new_password',
            'bank_phone' => 'require',
            'bank_card' => 'require',
            'bank_name' => 'require',
            'bank_account' => 'require',
            'account_openin' => 'require',
            'check_people' => 'require',
            'check_people_qq' => 'require',
            'check_people_phone' => 'require',
            'alipay_account' => 'require',
            'alipay_realname' => 'require',
    ];
    protected $message = [
            'account.require' => '请输入用户名',
            'account.alphaNum' => '账号为6-30位字母或数字组合',
            'account.length' => '账号为6-30位字母或数字组合',
            'account.unique' => '账号已存在',
            'password.require' => '请输入密码',
            'password.alphaNum' => '密码为6-30位字母或数字组合',
            'password.length' => '密码为6-30位字母或数字组合',
            //'nickname' => '请填写平台名称',
            'company_name.require' => '公司名称不能为空',
            'linkman.require' => '联系人姓名不能为空',
            'job.require' => '职务不能为空',
            'mobile.require' => '联系电话不能为空',
            'wechat.require' => '联系微信不能为空',
            'qq.require' => '联系QQ不能为空',
            'address.require' => '联系地址不能为空',
            'business_license_img.require' => '营业执照不能为空',
            'wenwangwen_img.require' => '文网文不能为空',
            'icp_img.require' => 'ICP许可证不能为空',
            'new_password.require' => '请输入新密码',
            'new_password.alphaNum' => '新密码为6-15位字母或数字组合',
            'new_password.length' => '新密码为6-15位字母或数字组合',
            're_new_password.require' => '请输入确认密码',
            're_new_password.confirm' => '两次输入的密码不一致',
            'bank_phone.require' => '请输入手机号',
            'bank_card.require' => '请输入银行卡号',
            'bank_name.require' => '请选择收款银行',
            'bank_account.require' => '请输入持卡人',
            'account_openin.require' => '请输入开户网点',
            'check_people.require' => '请输入对账人',
            'check_people_qq.require' => '请输入对账人QQ',
            'check_people_phone.require' => '请输入对账人手机号',
            'alipay_account.require' => '请输入支付宝账号',
            'alipay_realname.require' => '请输入支付宝真实姓名',
    ];
    protected $scene = [
            'create' => ['account', 'password', 'nickname'],
            'auth' => ['company_name', 'linkman', 'job', 'mobile', 'wechat', 'address', 'business_license_img', 'wenwangwen_img', 'icp_img','qq'],
            'change_pass' => ['password', 'new_password', 're_new_password'],
            'bank' => ['bank_phone', 'bank_card', 'bank_name', 'bank_account', 'account_openin', 'check_people', 'check_people_qq', 'check_people_phone'],
            'alipay' => ['alipay_account', 'alipay_realname'],
    ];

}

