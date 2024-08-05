<?php

namespace app\game\model;

use think\Model;

/**
 * gjt
 */
class GiftRecordModel extends Model
{

    protected $table = 'tab_game_gift_record';

    protected $autoWriteTimestamp = true;

    /**
     * 获取我的礼包列表
     *
     * @param int $uid 用户编号
     * @param int $type 类型（0：所有，1：已领取，2：已过期）
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\30 0030 10:47
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function record($uid = 0, $type = 0)
    {
        $map['delete_status'] = 0;
        $uid > 0 && $map['user_id'] = $uid;
        switch ($type) {
            case 1:
                $map['end_time'] = array(['egt', time()], ['eq', 0], 'or');
                $order = 'r.create_time desc';
                break;
            case 2:
                $map['end_time'] = array(['elt', time()], ['neq', 0]);
                $order = 'r.end_time desc';
                break;
            default:
                $order = 'r.create_time desc';
        }
        $list = $this->alias('r')
            ->field('r.id,r.game_id,r.game_name,g.relation_game_name,r.gift_id,r.gift_name,r.novice,r.start_time,r.end_time,r.gift_version,g.icon')
            ->join(['tab_game' => 'g'], 'g.id=r.game_id', 'left')
            ->where($map)
            ->order($order)
            ->select()->toArray();
        return $list;

    }

    /**
     * 删除礼包领取记录
     *
     * @param array $map 条件
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\30 0030 13:44
     */
    public function deleteMyGift($map = [])
    {
        $result = $this->where($map)->update(['delete_status' => 1]);
        if ($result) {
            return true;
        } else {
            return false;
        }

    }

}
