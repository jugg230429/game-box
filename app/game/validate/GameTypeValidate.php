<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use app\game\model\GameTypeModel;
use think\Validate;

class GameTypeValidate extends Validate
{

    protected $rule = [
        'type_name' => 'require|checkTypeName|length:1,30',
    ];
    protected $message = [
        'type_name.require' => '类型名称不能为空',
        'type_name.checkTypeName' => '类型名称已存在',
        'type_name.length' => '类型名称不超过30个字符',
    ];

    // 自定义验证规则
    protected function checkTypeName($value, $rule, $data = [])
    {
        $model = new GameTypeModel();
        $map['type_name'] = $value;
        if($data['id']){
            $map['id'] = ['neq',$data['id']];
        }
        $result = $model->where($map)->find();
        $result=$result?$result->toArray():[];
        if ($result) {
            return false;
        } else {
            return true;
        }
    }
}

