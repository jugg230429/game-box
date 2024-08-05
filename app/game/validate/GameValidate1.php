<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use app\game\model\GameModel;
use think\Validate;

class GameValidate1 extends Validate
{

    protected $rule = [
        'game_name' => 'require|checkGameName|length:1,30',
        'game_type_id' => 'require|egt:1',
        'game_appid' => 'require',
        'ratio' => 'between:0,100',
        'money' => 'require',
        'sort' => '/^[0-9]*$/',
        'game_score' => ['regex' => '/^(\d(\.\d)?|10)$/'],
        'recommend_level' => ['regex' => '/^(\d(\.\d)?|10)$/'],
        'discount' => 'require|between:0.01,10',
        'bind_recharge_discount' => 'require|between:0.01,10',
        'game_key' => 'length:1,32',
    ];
    protected $message = [
        'game_name.require' => '游戏名称不能为空',
        'game_name.checkGameName' => '游戏名称已存在',
        'game_name.length' => '游戏名称不超过30个字符',
        'game_type_id' => '游戏类型不能为空',
        'game_type_id.require' => '游戏类型不能为空',
        'game_type_id.egt' => '请选择游戏类型',
        'game_appid' => '游戏APPID不能为空',
        'ratio' => '分成比例不正确',
        'money' => '推广注册CPA单价不能为空',
        'sort' => '排序必须是数字',
        'game_score' => '游戏评分输入格式不正确',
        'recommend_level' => '推荐指数输入格式不正确',
        'discount' => '代充折扣错误',
        'bind_recharge_discount'=> '绑币充值折扣错误',
        'game_key' => '游戏KEY不能超过32个字符',
    ];
    protected $scene = [
        'edit' => ['game_type_id', 'ratio', 'money', 'sort', 'game_score', 'recommend_level', 'discount', 'game_key', 'bind_recharge_discount'],
    ];

    // 自定义验证规则
    protected function checkGameName($value, $rule, $data = [])
    {
        $model = new GameModel();
        $map['game_name'] = $data['game_name'];
        $map['sdk_version'] = 3;
        $result = $model->where($map)->find();
        if ($result) {
            return false;
        } else {
            return true;
        }

    }
}

