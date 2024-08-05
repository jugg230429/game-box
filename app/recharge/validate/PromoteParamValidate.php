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

class PromoteParamValidate extends Validate
{
    protected $rule = [
            'partner'=>'require',
            'promote_id'=>'require',
            'type'=>'require',
            'key'=>'require',
    ];
    protected $message = [
            'partner'=>'商户号不能为空',
            'promote_id' => '支付渠道不能为空',
            'type'=>'支付类型不能为空',
            'key'=>'秘钥不能为空',
    ];
    protected $scene = [
            'edit' => ['partner', 'promote_id','type', 'key'],
    ];

    /**
     * @函数或方法说明
     * @检查
     * @author: 郭家屯
     * @since: 2020/1/9 16:53
     */
    protected function checkGame($value,$rule,$data){
        $map['game_id'] = $data['game_id'];
        $wxparam = Db::table('tab_spend_wxparam')->field('id')->where($map)->find();
        return $wxparam?"该游戏已经设置过参数":true;
    }
}

