<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\recharge\validate;

use think\Db;
use think\Validate;

class RebateValidate extends Validate
{
    protected $rule = [
            'bind_status'=>'in:0,1',
            'type' => 'in:1,2,3,4,5|checktype:thinkphp',
            'game_id' => 'require',
            'game_name'=>'require',
            'status'=>'in:0,1',
            'money'=>'requireIf:status,1',
            'ratio'=>'require',
            'promote_ids'=>'requireIf:type,4|array',
            'game_user_ids'=>'requireIf:type,5|array'
    ];
    protected $message = [
            'bind_status' => '绑币消费参数错误',
            'type' => '返利对象参数错误',
            'game_id.require'=>'游戏不能为空',
            'game_name.require'=>'游戏不能为空',
            'status' => '金额限制状态参数错误',
            'money'=>'金额不能为空',
            'ratio'=>'返利比例不能为空',
            'promote_ids'=>'推广员不能为空',
            'game_user_ids'=>'游戏玩家不能为空'
    ];
    protected $scene = [
            'edit' => ['bind_status', 'status', 'money', 'ratio', 'promote_ids', 'game_user_ids'],
    ];

    /**
     * @函数或方法说明
     * @检查
     * @author: 郭家屯
     * @since: 2020/1/9 16:53
     */
    protected function checktype($value,$rule,$data){
        if($value == 1 || $value == 5){
            $map['type'] = ['in',[1,2,3,4,5]];
        }elseif($value==2){
            $map['type'] = ['in',[1,2,5]];
        }elseif($value==3){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,3,5]];
        }
        $map['game_id'] = $data['game_id'];
        $rebate = Db::table('tab_spend_rebate')->field('id')->where($map)->find();
        return $rebate?"同一游戏的返利对象不可重叠，请重新设置":true;
    }
}

