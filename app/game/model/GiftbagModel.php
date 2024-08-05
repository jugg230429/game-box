<?php

namespace app\game\model;

use think\Db;
use think\Model;
use app\game\model\GiftRecordModel;

/**
 * gjt
 */
class GiftbagModel extends Model
{

    protected $table = 'tab_game_giftbag';

    protected $autoWriteTimestamp = true;

    /**
     * [获取礼包列表]
     * @param string $game_id
     * @param string $user_id
     * @param string $sdk_version
     * @param array $map
     * @param string $order
     * @param int $row
     * @param int $p
     * @return array|false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGiftLists($game_id = '', $user_id = '', $sdk_version = '', $map = array(), $order = "gb.sort desc,gb.id desc", $row = 10, $p = 1)
    {
        if ($game_id) {
            $map['and_id|ios_id'] = ['in',[$game_id,get_game_entity($game_id,'relation_game_id')['relation_game_id']]];
        }
        if ($sdk_version) {
            $map['gb.giftbag_version'] = $map['g.sdk_version']  = ['like','%'.$sdk_version.'%'];
        }
        $map['gb.status'] = 1;
        $map['gb.platform_id'] = 0;
        $list = $this->alias('gb')
            ->field('gb.game_id,g.game_name,gb.id as gift_id,gb.start_time,gb.end_time,gb.giftbag_version,gb.giftbag_name,gb.novice,g.icon,g.sdk_version,gb.novice_num,gb.remain_num,gb.digest,g.relation_game_name,gb.desribe,vip')
            ->join(['tab_game' => 'g'], 'g.relation_game_name = gb.game_name', 'left')
            ->where($map)
            ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
            ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
            ->order($order)
            ->page($p, $row)
            ->select()->toArray();
        if ($list) {
            $giftRecordModel = new GiftRecordModel();
            foreach ($list as $key => $val) {
                if ($user_id) {
                    $gift_record = $giftRecordModel->field('novice,delete_status')->where('gift_id', $val['gift_id'])->where('user_id', $user_id)->find();
                    //是否已领取
                    if ($gift_record) {
                        $gift_record = $gift_record->toArray();
                        if($gift_record['delete_status'] == 1){
                            unset($list[$key]);
                            continue;
                        }
                        $list[$key]['received'] = 1;
                        $list[$key]['novice'] = $gift_record['novice'];
                    } else {
                        $list[$key]['received'] = 0;
                    }
                } else {
                    $list[$key]['received'] = 0;
                }
                if($val['remain_num'] == 0 && $list[$key]['received'] == 0){
                    unset($list[$key]);
                    continue;
                }
                //是否已领完
                $list[$key]['surplus'] = $val['remain_num'];//是否有剩余
                $list[$key]['icon'] = empty($val['icon']) ? '' : cmf_get_image_url($val['icon']);
            }
        }
        return array_values($list);
    }

    /**
     * @函数或方法说明
     * @查询礼包接口
     * @param array $map
     *
     * @author: 郭家屯
     * @since: 2020/2/19 10:27
     */
    public function getkeygift($map=[])
    {
        $map['gb.status'] = 1;
        $list = $this->alias('gb')
                ->field('g.relation_game_name as game_name,gb.id as gift_id,gb.giftbag_name,vip,gb.giftbag_version')
                ->join(['tab_game' => 'g'], '(g.id = gb.game_id or g.relation_game_id = gb.game_id)', 'left')
                ->where($map)
                ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
                ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
                ->order("gb.sort desc,gb.id desc")
                ->select()->toArray();
        return $list?:[];
    }

    /**
     * [获取礼包列表]
     * @param string $game_id
     * @param string $user_id
     * @param string $sdk_version
     * @param array $map
     * @param string $order
     * @param int $row
     * @param int $p
     * @return array|false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGameGiftPage($game_id = '', $user_id = '',$p=1,$row=10, $order = "gb.sort desc,gb.id desc",$sdk_version=1)
    {
        if ($game_id) {
            $map['and_id|ios_id|h5_id'] = $game_id;
        }
        //$map['gb.giftbag_version'] = ['like','%'.$sdk_version.'%'];
        $map['gb.status'] = 1;
        $map['gb.platform_id'] = 0;
        $list = $this->alias('gb')
                ->field('gb.game_id,g.relation_game_name as game_name,gb.id as gift_id,gb.start_time,gb.end_time,gb.giftbag_name,gb.novice,g.icon,gb.novice_num,gb.remain_num,gb.digest,g.relation_game_name,gb.desribe,vip,gb.giftbag_version')
                ->join(['tab_game' => 'g'], 'g.id = gb.game_id', 'left')
                ->where($map)
                ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
                ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
                ->order($order)
                ->page($p,$row)
                ->distinct('giftbag_name')
                ->select()->toArray();
        if ($list) {
            $giftRecordModel = new GiftRecordModel();
            foreach ($list as $key => $val) {
                if ($user_id) {
                    $gift_record = $giftRecordModel->field('novice,delete_status')->where('gift_id', $val['gift_id'])->where('user_id', $user_id)->find();
                    //是否已领取
                    if ($gift_record) {
                        $gift_record = $gift_record->toArray();
                        if($gift_record['delete_status'] == 1){
                            unset($list[$key]);
                            continue;
                        }
                        $list[$key]['received'] = 1;
                        $list[$key]['novice'] = $gift_record['novice'];
                    } else {
                        $list[$key]['received'] = 0;
                    }
                } else {
                    $list[$key]['received'] = 0;
                }
                if($val['remain_num'] == 0 && $list[$key]['received'] == 0){
                    unset($list[$key]);
                    continue;
                }
                //是否已领完
                $list[$key]['surplus'] = $val['remain_num'];//是否有剩余
                $list[$key]['icon'] = empty($val['icon']) ? '' : cmf_get_image_url($val['icon']);
            }
        }
        return array_values($list);
    }

    /**
     * [获取礼包列表数量]
     * @param string $game_id
     * @param string $user_id
     * @param string $sdk_version
     * @param array $map
     * @param string $order
     * @param int $row
     * @param int $p
     * @return array|false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGameGiftcount($game_id = '', $user_id = '',$sdk_version=1)
    {
        if ($game_id) {
            $map['and_id|ios_id|h5_id'] = $game_id;
        }
        //$map['gb.giftbag_version'] = ['like','%'.$sdk_version.'%'];
        $map['gb.status'] = 1;
        $map['gb.platform_id'] = 0;
        $list = $this->alias('gb')
                ->field('gb.game_id,g.relation_game_name as game_name,gb.id as gift_id,gb.start_time,gb.end_time,gb.giftbag_name,gb.novice,g.icon,gb.novice_num,gb.remain_num,gb.digest,g.relation_game_name,gb.desribe,vip,gb.giftbag_version')
                ->join(['tab_game' => 'g'], 'g.id = gb.game_id', 'left')
                ->where($map)
                ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
                ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
                ->distinct('giftbag_name')
                ->select()->toArray();
        if ($list) {
            $giftRecordModel = new GiftRecordModel();
            foreach ($list as $key => $val) {
                if ($user_id) {
                    $gift_record = $giftRecordModel->field('novice,delete_status')->where('gift_id', $val['gift_id'])->where('user_id', $user_id)->find();
                    //是否已领取
                    if ($gift_record) {
                        $gift_record = $gift_record->toArray();
                        if($gift_record['delete_status'] == 1){
                            unset($list[$key]);
                            continue;
                        }
                        $list[$key]['received'] = 1;
                        $list[$key]['novice'] = $gift_record['novice'];
                    } else {
                        $list[$key]['received'] = 0;
                    }
                } else {
                    $list[$key]['received'] = 0;
                }
                if($val['remain_num'] == 0 && $list[$key]['received'] == 0){
                    unset($list[$key]);
                    continue;
                }
                //是否已领完
                $list[$key]['surplus'] = $val['remain_num'];//是否有剩余
                $list[$key]['icon'] = empty($val['icon']) ? '' : cmf_get_image_url($val['icon']);
            }
        }
        return count($list);
    }
    /**
     * [获取首页礼包列表]
     * @param int $user_id
     * @param array $map
     * @param int $limit
     * @return array|false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGiftIndexLists($user_id = 0, $map = array(), $limit = 10,$order='gb.sort desc,gb.id desc')
    {
        $map['gb.status'] = 1;
        $map['g.game_status'] = 1;
        $map['gb.platform_id'] = 0;
        //$map['gb.remain_num'] = ['gt', 0];
        $list = $this->alias('gb')
            ->field('gb.game_id,g.relation_game_name as game_name,gb.id as gift_id,gb.start_time,gb.end_time,gb.giftbag_version,gb.giftbag_name,g.icon,gb.novice_num,gb.remain_num,gb.digest,g.relation_game_name,gb.desribe,vip')
            ->join(['tab_game' => 'g'], 'g.id = gb.game_id', 'left')
            ->where($map)
            ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
            ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
            ->order($order)
            ->limit($limit)
            ->select()->toArray();
        $giftRecordModel = new GiftRecordModel();
        foreach ($list as $key => $val) {
            if ($user_id) {
                $gift_record = $giftRecordModel->field('novice,delete_status')->where('gift_id', $val['gift_id'])->where('user_id', $user_id)->find();
                //是否已领取
                if ($gift_record) {
                    if($gift_record['delete_status'] == 1){
                        unset($list[$key]);
                        continue;
                    }
                    $list[$key]['received'] = 1;
                    $list[$key]['novice'] = $gift_record['novice'];
                } else {
                    $list[$key]['received'] = 0;
                }
            } else {
                $list[$key]['received'] = 0;
            }
            //是否已领完
            $list[$key]['giftbag_name'] = $val['giftbag_name'];//是否有剩余
            $list[$key]['surplus'] = $val['remain_num'];//是否有剩余
            $list[$key]['icon'] = empty($val['icon']) ? '' : cmf_get_image_url($val['icon']);
        }
        return $list;
    }

    /**
     *[wap礼包首页]
     * @param array $map
     * @param int $user_id
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chen
     */
    public function getGameGiftLists($map = array(), $user_id = 0)
    {
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $map['g.test_game_status'] = 0; // 不显示测试状态游戏
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map['g.game_status'] = 1;
        $map['gb.platform_id'] = 0;

        $data = $this->alias('gb')
            ->field('gb.game_id,g.icon,g.relation_game_name,g.relation_game_id,gb.giftbag_name,gb.id as gift_id,gb.desribe,gb.novice,gb.giftbag_version,gb.start_time,gb.end_time,remain_num,digest,vip,novice_num')
            ->join(['tab_game' => 'g'], 'g.id = gb.game_id','right') // 由left -> right 关联 以游戏是否存在为准 才显示对应礼包
            ->where($map)
            ->where('gb.status',1)
            ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
            ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
            ->order('gb.sort desc,gb.id desc')
            ->select()->toArray();//礼包列表
        $new_data = [];
        foreach ($data as $key => $vo) {
            $vo['novice'] = '';
            $vo['geted'] = 0;
            if ($user_id) {
                $grmap['gift_id'] = $vo['gift_id'];
                $grmap['game_id'] = $vo['game_id'];
                $grmap['user_id'] = $user_id;
                $gift_record = \think\Db::table('tab_game_gift_record')->field('novice,delete_status')->where($grmap)->find();
                if ($gift_record) {
                    if($gift_record['delete_status'] == 1){
                        continue;
                    }
                    $vo['geted'] = 1;
                    $vo['novice'] = $gift_record['novice'];
                }
            }
            if($vo['remain_num'] == 0 && empty($vo['geted'])){
                continue;
            }
            $new_data[$vo['relation_game_name']]['game_name'] = $vo['relation_game_name'];
            $new_data[$vo['relation_game_name']]['game_id'] = $vo['relation_game_id'];
            $new_data[$vo['relation_game_name']]['relation_game_name'] = $vo['relation_game_name'];
            $new_data[$vo['relation_game_name']]['icon'] = cmf_get_image_url($vo['icon']);
            $new_data[$vo['relation_game_name']]['gblist'][] = $vo;
        }
        if (empty($new_data)) {
            return [];
        }
        return array_values($new_data);
    }

    /**
     * [礼包详情]
     * @param string $gift_id
     * @param string $user_id
     * @author 郭家屯[gjt]
     */
    public function getDetail($gift_id = '', $user_id = '')
    {
        if (empty($gift_id)) {
            return [];
        }
        $map['gb.id'] = $gift_id;
        $map['gb.status'] = 1;
        $gift = $this->alias('gb')
            ->field('gb.game_id,g.game_name,g.relation_game_id,g.relation_game_name,gb.id as gift_id,gb.start_time,gb.end_time,gb.giftbag_name,gb.novice,g.icon,g.sdk_version,gb.novice_num,gb.remain_num,gb.digest,gb.desribe,competence,notice,gb.giftbag_version,vip,and_id,ios_id,h5_id,pc_id')
            ->join(['tab_game' => 'g'], 'g.id=gb.game_id', 'left')
            ->where($map)
            ->find();
        if (empty($gift)) {
            return [];
        }
        $gift = $gift->toArray();
        if ($user_id) {
            $giftRecordModel = new GiftRecordModel();
            $gift_record = $giftRecordModel
                ->field('id,novice')
                ->where('gift_id', $gift['gift_id'])
                ->where('user_id', $user_id)
                ->find();
            //是否已领取
            if ($gift_record) {
                $gift_record = $gift_record->toArray();
                $gift['received'] = 1;
                $gift['novice'] = $gift_record['novice'];
            } else {
                $gift['received'] = 0;
            }
        } else {
            $gift['received'] = 0;
        }
        //是否已领完
        $gift['surplus'] = $gift['remain_num'];//是否有剩余
        $gift['icon'] = empty($gift['icon']) ? '' : cmf_get_image_url($gift['icon']);
        return $gift;
    }

    public function receiveGift($gift_id = '', $user_id = '')
    {
        if (empty($gift_id) || empty($user_id)) {
            return -1;
        }
        $map['id'] = $gift_id;
        $map['status'] = 1;
        $gift = $this->where($map)
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->find();
        if (empty($gift)) {
            return -1;
        }
        // $user_vip = get_user_entity($user_id,false,'vip_level')['vip_level'];
        $user_info = get_user_entity($user_id,false,'vip_level,cumulative');
        $user_vip = $user_info['vip_level'];
        if($user_vip<$gift['vip']){
            return -4;
        }
        // 用户累充达不到 也不能领取礼包
        $user_cumulative = $user_info['cumulative'];
        if($user_cumulative < $gift['accumulate_recharge_limit']){
            return -4;
        }

        $gift = $gift->toArray();
        if ($gift['remain_num'] == 0) {
            return -2;
        }
        $giftRecordModel = new GiftRecordModel();
        $gift_record = $giftRecordModel->field('id')
            ->where('gift_id', $gift['id'])
            ->where('user_id', $user_id)
            ->find();
        if ($gift_record) {
            return -3;
        }
        if($gift['type'] == 1){
            #将激活码分成数据
            $novice_arr = explode(",", $gift['novice']);
            $data_record['novice'] = $novice_arr[0];
        }else{
            $data_record['novice'] = $gift['novice'];
        }
        #礼包记录数据
        $data_record['game_id'] = $gift['game_id'];
        $data_record['game_name'] = $gift['game_name'];
        $data_record['server_id'] = $gift['server_id'];
        $data_record['server_name'] = $gift['server_name'];
        $data_record['gift_id'] = $gift_id;
        $data_record['gift_name'] = $gift['giftbag_name'];
        $data_record['user_id'] = $user_id;
        $data_record['user_account'] = get_user_name($user_id);
        $data_record['create_time'] = time();
        $data_record['start_time'] = $gift['start_time'];
        $data_record['end_time'] = $gift['end_time'];
        $data_record['gift_version'] = $gift['giftbag_version'];
        $result = $giftRecordModel->insert($data_record);
        if ($result) {
            if($gift['type'] == 1){
                #领取成功后移除这个激活码
                unset($novice_arr[0]);
                #将新的激活码转换成字符串 保存
                $act['novice'] = implode(",", $novice_arr);
            }
            $act['remain_num'] = $gift['remain_num'] - 1;
            $this->where('id', $gift_id)->update($act);
            return $data_record['novice'];
        } else {
            return -5;
        }
    }
}
