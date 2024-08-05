<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\promote\validate;

use think\Validate;

class PromotedepositValidate extends Validate
{

    protected $rule = [
        'pay_order_number' => 'require',
    ];
    protected $message = [
        'pay_order_number.require' => '订单不能为空',
        'pay_order_number.unique' => '订单已存在，请重新下单',
    ];
    protected $scene = [
        'checkorder' => [
            'pay_order_number' => 'require',
        ],
    ];

}

