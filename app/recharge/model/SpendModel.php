<?php

namespace app\recharge\model;

use think\Model;

/**
 * yyh
 */
class SpendModel extends Model
{

    protected $table = 'tab_spend';

    protected $autoWriteTimestamp = true;

    /**
     * 我的游戏账单（排除绑币）
     *
     * @param int $uid
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\30 0030 15:30
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function getMyGameBill($uid = 0)
    {
        if ($uid > 0) {

            $map['pay_status'] = 1;
            $map['pay_way'] = ['gt', 0];
            $map['user_id'] = $uid;

            $list = $this->field('game_name,pay_time,pay_amount,cost,pay_way')
                ->where($map)
                ->order('pay_time desc')
                ->select()
                ->toArray();

            return $list;
        } else {
            return [];
        }
    }

    public function getPayDetail($map = [])
    {
        $data = $this
            ->field('sum(pay_amount) as total,user_id,user_account')
            ->where($map)
            ->group('user_id')
            ->select()->toArray();
        return $data;
    }

    /**
     * [获取游戏充值列表]
     * @param array $map
     * @param int $p
     * @param int $row
     * @author 郭家屯[gjt]
     */
    public function getLists($map = [], $p = 1, $row = 10)
    {
        $map['pay_status'] = 1;
        $list = $this
            ->field('id,pay_order_number,user_id,promote_id,pay_amount,pay_way,pay_time,game_name')
            ->where($map)
            ->order('id desc')
            ->page($p, $row)
            ->select()
            ->each(function ($item, $key) {
                $item['pay_way'] = get_info_status($item['pay_way'], 1);
                return $item;
            });
        $count = $this->where($map) ->sum('pay_amount');
        $data['lists'] = $list ? $list->toArray() : [];
        $data['count'] = $count ? $count : 0;
        return $data;
    }

    /**
     * [返回总数量]
     * @param array $map
     * @return int|string
     * @author 郭家屯[gjt]
     */
    public function countLists($map = [])
    {
        $map['pay_status'] = 1;

        return $count;
    }
}
