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

class CouponValidate extends Validate
{
    protected $rule = [
            'mold'=>'in:0,1',
            'type' => 'in:1,2,3,4',
            'game_id' => 'requireIf:mold,1',
            'game_name'=>'requireIf:mold,1',
            'status'=>'in:0,1',
            'money'=>'require',
            'coupon_name' =>'require',
            'stock'=>'require|number',
            'limit'=>'require|number',
            'promote_ids'=>'requireIf:type,4|array'
    ];
    protected $message = [
            'mold' => '适用类型参数错误',
            'type' => '返利对象参数错误',
            'coupon_name'=>'代金券名称不能为空',
            'game_id'=>'游戏不能为空',
            'game_name'=>'游戏不能为空',
            'status' => '开启状态参数错误',
            'money'=>'优惠金额不能为空',
            'stock' =>'请输入代金券数量',
            'limit' =>'请输入可领取数量',
            'promote_ids'=>'推广员不能为空'
    ];
    protected $scene = [
            'promote' => ['coupon_name','game_id', 'game_name','money'],
    ];
}

