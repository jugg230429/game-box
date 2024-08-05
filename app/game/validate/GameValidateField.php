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

class GameValidateField extends Validate
{

    protected $rule = [
        'game_name' => 'require|checkGameName|length:1,30',
        'game_type_id' => 'require',
        'game_appid' => 'require',
        'game_address_size' => 'requireIf:down_port,2',
        'ratio' => 'between:0,100',
        'money' => '/^[0-9]*$/',
        'cp_ratio' => 'between:0,100',
        'cp_pay_ratio' => 'between:0,100',
        'sort' => '/^[0-9]*$/',
        'down_num'=>'/^[0-9]*$/',
        'game_score' => ['regex' => '/^(\d(\.\d)?|10)$/'],
        'recommend_level' => ['regex' => '/^(\d(\.\d)?|10)$/'],
        'game_status'=>'in:0,1',
    ];
    protected $message = [
        'game_name.require' => '游戏名称不能为空',
        'game_name.checkGameName' => '游戏名称已存在',
        'game_name.length' => '游戏名称不超过30个字符',
        'game_type_id' => '游戏类型不能为空',
        'game_type_id.require' => '游戏类型不能为空',
        'game_type_id.egt' => '请选择游戏类型',
        'game_appid' => '游戏APPID不能为空',
        'game_address_size' => '游戏大小不能为空',
        'ratio' => 'CPS分成比例不正确',
        'money' => 'CPA单价不能为空',
        'cp_ratio'=>'CP分成比例不正确',
        'cp_pay_ratio'=>'CP通道费率不正确',
        'sort' => '排序必须是数字',
        'game_score' => '游戏评分输入格式不正确',
        'recommend_level' => '推荐指数输入格式不正确',
        'game_status'=>'上架状态错误'
    ];
    protected $scene = [
        'edit' => ['game_type_id', 'ratio', 'money', 'sort', 'game_score', 'recommend_level', 'cp_ratio', 'cp_pay_ratio'],
        'setField' => ['down_num','sort','game_score','cp_ratio','cp_pay_ratio','ratio','money','game_status'],
    ];

    // 自定义验证规则
    protected function checkGameName($value, $rule, $data = [])
    {
        $model = new GameModel();
        $map['sdk_version'] = ['in',[1,2]];
        if ($data['is_guanlian'] == 1) {
            $map['game_name'] = $value;
            $map['sdk_version'] = $data['sdk_version'];
        } else {
            $map['relation_game_name'] = $data['game_name'];
        }
        $result = $model->where($map)->find();
        if ($result) {
            return false;
        } else {
            return true;
        }

    }
}

