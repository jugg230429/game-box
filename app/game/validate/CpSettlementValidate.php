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

class CpSettlementValidate extends Validate
{

    protected $rule = [
        'cp_bank_mobile' =>'require|regex:/^1[3-9]\d{9}$/',
        'cp_bank_num' =>'require|number|max:19',
        'bank_name' =>'require|min:2',
        'cp_bank_username' =>'require|chs|length:2,25',
        'cp_bank_open_area' =>'require|chs|length:2,25',
    ];
    protected $message = [
        'cp_bank_mobile'=>'请输入正确手机号',
        'cp_bank_num.require'=>'请输入银行卡号',
        'cp_bank_num.number'=>'银行卡号错误',
        'cp_bank_num.chs'=>'银行卡号错误',
        'bank_name.require'=>'开户银行不能为空',
        'cp_bank_username.require'=>'开户银行不能为空',
        'cp_bank_username.chs'=>'姓名格式错误',
        'cp_bank_username.length'=>'姓名长度需要在2-25个字符之间',
        'cp_bank_open_area.require'=>'开户网点不能为空',
        'cp_bank_open_area.chs'=>'开户网点错误',
        'cp_bank_open_area.length'=>'开户网点错误',
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

