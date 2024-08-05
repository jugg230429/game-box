<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use think\Validate;
use app\game\model\ServerModel;

class ServerValidate extends Validate
{

    protected $rule = [
        'game_id' => 'require',
        'server_name' => 'require|checkName',
        'start_time' => 'require',
        'server_num' => 'require',
    ];
    protected $message = [
        'game_id.require' => '游戏名称不能为空',
        'server_name.require' => '区服名称不能为空',
        'server_name.checkName' => '同游戏下区服名称已存在',
        'start_time.require' => '开始时间不能为空',
        'server_num.require' => '对接区服ID不能为空',
    ];
    protected $scene = [
        'edit' => ['server_name', 'start_time', 'server_num'],
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        $model = new ServerModel();
        $map['server_name'] = $value;
        $map['game_id'] = $data['game_id'];
        $result = $model->field('id')->where($map)->find();
        if ($result) {
            return false;
        } else {
            return true;
        }

    }

}

