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

class WelfareValidate extends Validate
{
    protected $rule = [
            'type' => 'in:1,2,3,4,5|checktype:thinkphp',
            'game_id' => 'require',
            'game_name'=>'require',
            'first_discount'=>'require|between:0.01,10',
            'continue_discount'=>'require|between:0.01,10',
            'promote_ids'=>'requireIf:type,4|array',
            'game_user_ids'=>'requireIf:type,5|array'
    ];
    protected $message = [
            'type' => '折扣对象参数错误',
            'game_id.require'=>'游戏不能为空',
            'game_name.require'=>'游戏不能为空',
            'first_discount.require' => '首充折扣不能为空',
            'first_discount.between'=>'折扣为0-10的两位小数',
            'continue_discount.between'=>'折扣为0-10的两位小数',
            'continue_discount.require'=>'续充折扣不能为空',
            'promote_ids'=>'推广员不能为空',
            'game_user_ids'=>'游戏玩家不能为空'
    ];
    protected $scene = [
            'edit' => ['first_discount', 'continue_discount', 'promote_ids',game_user_ids],
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
        $rebate = Db::table('tab_spend_welfare')->field('id')->where($map)->find();
        return $rebate?"同一游戏的折扣对象不可重叠，请重新设置":true;
    }
}

