<?php

namespace app\btwelfare\validate;

use app\btwelfare\model\BtWelfareModel;
use think\Validate;

class BtWelfareValidate extends Validate
{


    protected $rule = [
            'game_id' => 'require|integer|gt:0',
            'promote_ids' => 'require',
            'start_time' => 'require|date',
            'end_time' => 'date|checkEndTime',
    ];
    protected $message = [
            'game_id' => [
                    'require' => '请选择游戏',
                    'integer' => '请选择游戏',
                    'gt' => '请选择游戏',
            ],
            'promote_ids' => [
                    'require' => '请选择渠道',
            ],
            'start_time' => [
                    'require' => '请选择开始时间',
            ],
            'end_time' => [
                    'checkEndTime' => '结束时间要大于开始时间',
            ],
    ];


    protected $scene = [
            'add' => ['game_id', 'promote_ids', 'start_time', 'end_time'],
            'edit' => ['game_id', 'promote_ids', 'start_time', 'end_time'],
    ];


    // 自定义验证规则
    protected function checkEndTime($value, $rule, $data)
    {
        return !($value < $data['start_time']);
    }


    // 验证推广员是否重复配置
    public function checkPromote($param = [])
    {
        $mWelfare = new BtWelfareModel();
        $where = [];
        $where['w.game_id'] = $param['game_id'];
        $where['wp.promote_id'] = ['in', $param['promote_ids']];
        if (!empty($param['id'])) {
            $where['w.id'] = ['neq', $param['id']];
        }
        $selected = $mWelfare -> alias('w')
                -> field('w.id,w.game_id,wp.promote_id')
                -> join(['tab_bt_welfare_promote' => 'wp'], 'w.id = wp.bt_welfare_id')
                -> where($where)
                -> select();
        return $selected;
    }


}