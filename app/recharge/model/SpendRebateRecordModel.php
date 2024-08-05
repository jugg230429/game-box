<?php

namespace app\recharge\model;

use think\Model;

/**
 * yyh
 */
class SpendRebateRecordModel extends Model
{

    protected $table = 'tab_spend_rebate_record';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @获取返利数据
     * @param array $map
     * @param int $p
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/2/7 17:55
     */
    public function get_my_rebate($map=[],$p=1,$limit=10)
    {
        $data = $this
                ->field('game_name,pay_amount,create_time,ratio_amount')
                ->where($map)
                ->order('id desc')
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($data as $key=>$v){
            $data[$key]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }
        return $data;
    }

}