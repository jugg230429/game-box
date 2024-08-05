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

class WxparamValidate extends Validate
{
    protected $rule = [
            'game_id' => 'require|checkGame:thinkphp',
            'partner'=>'require',
            'appid'=>'require',
            'key'=>'require',
    ];
    protected $message = [
            'game_id.require' => '请选择游戏',
            'partner'=>'商户号不能为空',
            'appid' => 'APPID不能为空',
            'key'=>'API秘钥不能为空',
    ];
    protected $scene = [
            'edit' => ['partner', 'appid', 'key'],
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

