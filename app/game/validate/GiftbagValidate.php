<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use think\Validate;
use app\game\model\GiftbagModel;

class GiftbagValidate extends Validate
{

    protected $rule = [
        'game_id' => 'require|checkGame',
        'giftbag_name' => 'require|checkName',
        'remain_num'=>'requireIf:type,2|number',
        'novice' => 'require',
        'sort' => '/^[0-9]*$/',
        'giftbag_version'=>'require'
    ];
    protected $message = [
        'game_id.require' => '游戏名称不能为空',
        'game_id.checkGame' => '游戏不存在',
        'giftbag_name.require' => '礼包名称不能为空',
        'giftbag_name.checkName' => '礼包名称已存在',
        'novice.require' => '激活码不能为空',
        'remain_num.require'=>'剩余数量不能为空',
        'remain_num.number'=>'请输入整数',
        'sort' => '优先级必须是整数',
        'giftbag_version.require'=>'运行平台不能为空'
    ];
    protected $scene = [
        'edit' => ['giftbag_name', 'novice','sort','giftbag_version'],
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        $model = new GiftbagModel();
        $map['giftbag_name'] = $value;
        $map['game_id'] = $data['game_id'];
        $map['server_id'] = $data['server_id'];
        $result = $model->where($map)->find();
        if ($result) {
            return false;
        } else {
            return true;
        }

    }

    protected function checkGame($value, $rule, $data = [])
    {
        $game = get_game_entity($value);
        if ($game) {
            return true;
        } else {
            return false;
        }

    }

}

