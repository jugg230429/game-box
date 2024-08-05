<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\member\validate;

use think\Validate;

class UserTransactionValidate extends Validate
{

    protected $rule = [
        'small_id' => 'require|number',
        'title' => 'require|length:5,30',
        'server_name' => 'require',
        'money'      =>'require|float',
        //'dec'        =>'require',
        'screenshot' =>'require|checkcount:thinkphp',
        'send_type'  =>'require|in:1,2',
        'code'       =>'require|number'
    ];
    protected $message = [
        'small_id' => '请选择出售小号',
        'title' => '请填写正确的标题',
        'server_name' => '请填写正确的所在区服',
        'money'=>'请填写正确的售价',
        //'dec' => '请输入描述',
        'screenshot.require' =>'请上传截图',
        'screenshot.checkcount' =>'请上传截图',
        'send_type' =>'验证错误',
        'code' =>'验证码不能为空'
    ];
    protected $scene = [
        'edit' => ['small_id', 'title','server_name','money','screenshot'],
    ];

    /**
     * @函数或方法说明
     * @验证上传截图不超过九张
     * @param $value
     * @param $rule
     * @param $data
     *
     * @author: 郭家屯
     * @since: 2020/3/10 10:46
     */
    protected function checkcount($value, $rule, $data)
    {
       $count = count(explode(',',$value));
       if($count > 9){
           return false;
       }else{
           return true;
       }
    }
}

