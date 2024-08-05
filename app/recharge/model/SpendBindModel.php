<?php

namespace app\recharge\model;

use think\Model;

/**
 * yyh
 */
class SpendBindModel extends Model
{

    protected $table = 'tab_spend_bind';

    protected $autoWriteTimestamp = true;

    /**
     * [获取绑币充值列表]
     * @param array $map
     * @param int $p
     * @param int $row
     * @author 郭家屯[gjt]
     */
    public function getLists($map = [], $p = 1, $row = 10)
    {
        $map['pay_status'] = 1;
        $list = $this
            ->field('user_id,promote_id,pay_amount,pay_way,pay_time')
            ->where($map)
            ->order('id desc')
            ->page($p, $row)
            ->select()
            ->each(function ($item, $key) {
                $item['pay_way'] = get_info_status($item['pay_way'], 1);
                return $item;
            });
        $count = $this
                ->field('id')
                ->where($map)
                ->count();
        $data['lists'] = $list ? $list->toArray() : [];
        $data['count'] = $count ? $count : 0;
        return $data;
    }

    /**
     * [获取绑币充值列表]
     * @param array $map
     * @param int $p
     * @param int $row
     * @author 郭家屯[gjt]
     */
    public function getMobileLists($map = [], $p = 1, $row = 10)
    {
        $map['pay_status'] = 1;
        $list = $this
                ->field('user_account,pay_amount,pay_way,pay_time,game_name')
                ->where($map)
                ->order('id desc')
                ->page($p, $row)
                ->select()
                ->each(function ($item, $key) {
                    $item['pay_way'] = get_info_status($item['pay_way'], 1);
                    $item['pay_time'] = date('Y-m-d H:i:s',$item['pay_time']);
                    return $item;
                });
        $sum  = $this->where($map)->sum('pay_amount');
        $data['data'] = $list ? $list->toArray() : [];
        $data['sum'] = $sum ? round($sum,2) : 0;
        return $data;
    }

}