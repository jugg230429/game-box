<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\member\validate;

use think\Validate;

class PointShopValidate extends Validate
{

    protected $rule = [
        'type' => 'require|in:1,2',
        'good_name' => 'require',
        'price' => 'require|number',
        'number' => 'require|number',
//        'vip_grade' => 'number',
        'limit' => 'require|number',
        'good_info' => 'require',
    ];
    protected $message = [
        'type' => '属性错误',
        'good_name' => '商品名称不能为空',
        'price' => '所需积分输入错误',
        'number' => '商品数量输入错误',
//        'vip_grade' => 'VIP格式错误',
        'limit' => '可兑换数量输入错误',
        'good_info' => '商品详情不能为空',
    ];
}

