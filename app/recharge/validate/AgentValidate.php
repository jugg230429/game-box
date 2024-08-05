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

class AgentValidate extends Validate
{
    protected $rule = [
//            'promote_id' => 'require|checkpromote:thinkphp',
            'promote_id' => 'require|array',
            'game_id' => 'require',
            'game_name'=>'require',
//            'promote_discount'=>'require|between:1,10'
            'promote_discount_first'=>'require|between:1,10',
            'promote_discount_continued'=>'require|between:1,10'
    ];
    protected $message = [
            'promote_id' => '渠道账号不能为空',
            'game_id.require'=>'游戏名称不能为空',
            'game_name.require'=>'游戏名称不能为空',
            'promote_discount_first.require' => '渠道首充折扣不能为空',
            'promote_discount_first.between'=>'渠道首充折扣为1-10的两位小数',
            'promote_discount_continued.require' => '渠道续充折扣不能为空',
            'promote_discount_continued.between'=>'渠道续充折扣为1-10的两位小数',
    ];
    protected $scene = [
            'edit' => ['promote_discount_first','promote_discount_continued'],
    ];

    /**
     * @函数或方法说明
     * @检查
     * @author: 郭家屯
     * @since: 2020/1/9 16:53
     */
    protected function checkpromote($value,$rule,$data){
        $map['promote_id'] = $value;
        $map['game_id'] = $data['game_id'];
        $rebate = Db::table('tab_promote_agent')->field('id')->where($map)->find();
        return $rebate?"该游戏已设置代充折扣":true;
    }
}

