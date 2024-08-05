<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use app\site\model\KefutypeModel;
use think\Validate;

class KefutypeValidate extends Validate
{

    protected $rule = [
        'platform_type' => 'require',
        'name' => 'require|checkName',
    ];
    protected $message = [
        'name.require' => '问题类型不能为空',
        'name.checkName' => '问题类型名称不能相同',
        'platform_type.require' => '请选择所属站点',
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
    public function checkName($value, $rule, $data = [])
    {
        $model = new KefutypeModel();
        $map['platform_type'] = $data['platform_type'];
        $map['name'] = $data['name'];
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

