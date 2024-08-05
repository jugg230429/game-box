<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use app\game\model\CpModel;
use think\Validate;
use app\game\model\GiftbagModel;

class CpValidate extends Validate
{

    protected $rule = [
        'cp_name' => 'require|checkName',
        'cp_attribute' =>'require|number',
        'cp_mobile' =>'/^1[3-9]\d{9}$/',
        'cp_contact_name' =>'chs|length:2,25',
    ];
    protected $message = [
        'cp_name' => 'cp商名称不能为空',
        'cp_name.checkName' => 'cp商名称已存在',
        'cp_attribute.require' => '请选择CP属性',
        'cp_attribute.number' => 'CP属性只能为数字',
        'cp_mobile'=>'请输入正确手机号',
        'cp_contact_name.chs'=>'姓名格式错误',
        'cp_contact_name.length'=>'姓名格式错误',
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        $model = new CpModel();
        $map['cp_name'] = $value;
        $result = $model->where($map)->find();
        if(isset($data['id'])&&$data['id']){
            if ($result['id']!=$data['id']) {
                // CP商名字已经存在
                return false;
            } else {
                return true;
            }
        }else{
            if ($result) {
                // CP商名字已经存在
                return false;
            } else {
                return true;
            }
        }
    }


}

