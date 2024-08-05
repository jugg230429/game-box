<?php

namespace app\game\model;

use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use think\Db;
use think\Model;
use think\Pinyin;

/**
 * gjt
 */
class GameModel extends Model
{

    protected $table = 'tab_game';

    protected $autoWriteTimestamp = true;


    public function gameAttr()
    {
        return $this->hasOne('\app\game\model\GameAttrModel','game_id');
    }


    public function checkGameName($game_name = '',$sdk_version=1)
    {
        if (empty($game_name)) {
            return ['status' => 1, 'msg' => '游戏名称不能为空'];
        }
        if($sdk_version == 3){
            $map['sdk_version'] = 3;
        }elseif($sdk_version == 4){
            $map['sdk_version'] = 4;
        }else{
            $map['sdk_version'] = ['in',[1,2]];
        }
        $map['relation_game_name'] = $game_name;
        $data = $this->field('id,game_name')->where($map)->select()->toArray();
        if(($sdk_version == 3 || $sdk_version == 4) && $data){
            $msg = '游戏名称已存在，请重新输入！';
            return ['status' => 1, 'msg' => $msg];
        }
        if (empty($data)) {
            $pinyin = new Pinyin();
            $num = mb_strlen($game_name, 'UTF8');
            $short = '';
            for ($i = 0; $i < $num; $i++) {
                $str = mb_substr($_POST['game_name'], $i, $i + 1, 'UTF8');
                $short .= $pinyin->getFirstChar($str);
            }
            return ['status' => 0, 'short' => $short];
        } elseif (count($data) == 1) {
            $msg = '你已添加' . $data[0]['game_name'] . '游戏，请前往该游戏关联！';
            return ['status' => 1, 'msg' => $msg];
        } else {
            $msg = '游戏名称已存在，请重新输入！';
            return ['status' => 1, 'msg' => $msg];
        }
    }

    /**
     * [获取游戏列表]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGameLists($map = [], $limit = 10)
    {
        $map['game_status'] = 1;
        $map['sdk_area'] = 0; //不显示海外游戏
        $data = $this->field('count(id) as sdk_count,relation_game_id as id,relation_game_id,relation_game_name as game_name,cover,hot_cover,features,icon,game_type_name,game_score,sdk_version,game_size,game_address_size,down_port,dow_num')
            ->where($map)
            ->order('sort desc,id desc')
            ->group('relation_game_id')
            ->limit($limit)
            ->select();
        return $data;
    }

    /**
     * [获取推荐游戏列表区分版本号]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getRecommendGameLists($map = [], $limit = 10,$where_str='')
    {
        $map['sdk_area'] = 0; // 不显示海外游戏
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入

        $data = $this->field('id,game_name,relation_game_id,sdk_version,relation_game_name,icon,dow_num,game_type_name,if(down_port=1,game_size,game_address_size) as game_size,tag_name,features,down_port,and_dow_address,ios_dow_plist,add_game_address,ios_game_address,dow_num')
            ->where($map)
            ->where('game_status',1)
            ->where($where_str)
            ->order('sort desc,id desc')
            ->limit($limit)
            ->select();
        foreach ($data as $key=>$v){
            $data[$key]['game_size'] = $v['down_port'] == 2 ? $v['game_size'].'MB':$v['game_size'];
        }
        return $data;
    }

    /**
     * [获取推荐游戏列表区分版本号(分页获取)]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getRecommendGameLists1($map = [], $p = 1, $row = 10)
    {
        $map['game_status'] = 1;
        $data = $this->field('id,game_name,relation_game_id,sdk_version,relation_game_name,icon,dow_num,game_type_name,if(down_port=1,game_size,game_address_size) as game_size,tag_name,features,down_port,and_dow_address,ios_dow_plist,add_game_address,ios_game_address')
            ->where($map)
            ->order('sort desc,id desc')
            ->page($p, $row)
            ->select();
        foreach ($data as $key=>$v){
            $data[$key]['game_size'] = $v['down_port'] == 2 ? $v['game_size'].'MB':$v['game_size'];
        }
        return $data;
    }

    /**
     * [获取更多游戏(分页获取)]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getMoreGame($map = [],$order='sort desc,id desc', $p = 1, $row = 10,$group=null,$where_str='')
    {
        $map['game_status'] = 1;
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['sdk_area'] = 0; // 不显示海外游戏
        $data = $this->field('id,game_name,relation_game_id,sdk_version,relation_game_name,icon,dow_num,game_type_name,if(down_port=1,game_size,game_address_size) as game_size,tag_name,features,down_port,video_cover,video,video_url')
                ->where($map)
                ->where($where_str)
                ->order($order)
                ->page($p, $row)
                ->group($group)
                ->select();
        foreach ($data as $key=>$v){
            $data[$key]['game_size'] = $v['down_port'] == 2 ? $v['game_size'].'MB':$v['game_size'];
        }
        return $data;
    }

    /**
     * [获取游戏数量区分版本号]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getGameCount($map = [])
    {
        $map['game_status'] = 1;
        $count = $this
            ->where($map)
            ->count();
        return $count;
    }


    /**
     * [PC官网获取游戏详情]
     * @param $relation_game_id
     * @author 郭家屯[gjt]
     */
    public function getRelationGameDetail($relation_game_id)
    {
        $map['game_status'] = 1;
        // $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['relation_game_id'] = $relation_game_id;
        $data = $this->field('id,count(id) as sdk_type,relation_game_name as coupon_game,relation_game_id,relation_game_name as game_name,cover,features,icon,game_type_name,game_score,groom,game_type_name,down_port,if(down_port=1,game_size,game_address_size) as game_size,screenshot,introduction,sdk_version,dow_num,game_address_size,vip_table_pic')
            ->where($map)
            ->order('sort desc,id desc')
            ->group('relation_game_id')
            ->find();
        return $data ? $data->toArray() : [];
    }

    /**
     * [PC官网获取游戏ID]
     * @param $relation_game_id
     * @author 郭家屯[gjt]
     */
    public function getRelationGameId($relation_game_id)
    {
        $map['game_status'] = 1;
        $map['relation_game_id'] = $relation_game_id;
        $data = $this->field('id,game_name,sdk_version')
            ->where($map)
            ->select()->toArray();
        return $data;
    }

    /**
     * [WAP官网获取游戏详情]
     * @param $relation_game_id
     * @author chen
     */
    public function getGameDetail($map=[])
    {
        $map['game_status'] = 1;
        $data = $this->field('id,relation_game_id,relation_game_name as game_name,cover,features,icon,game_type_name,game_score,groom,game_type_name,game_size,screenshot,introduction,sdk_version,tag_name,dow_num,game_type_id,down_port,and_dow_address,ios_dow_plist,add_game_address,ios_game_address,down_port,game_address_size,screen_type,fullscreen,vip_table_pic,sdk_scheme')
            ->where($map)
            ->find();
        //根据数据情况查询游戏从表字段信息-20210830-byh
        if(!empty($data)){
            $data = $data->toArray();
            $attr_data = get_game_attr_entity($data['id'],'bind_recharge_discount,bind_continue_recharge_discount');
            $data = array_merge($data,(array)$attr_data);
        }else{
            $data = [];
        }
        return $data ;
    }

    /**
     * 获取我的游戏下载列表
     *
     * @param int $uid
     * @param array $map
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\29 0029 16:51
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function getMyGameList($uid = 0, $map = [])
    {
        $list = Db::table('tab_game_down_record')
            ->alias('d')
            ->field(['d.id' =>'record_id','game_id', 'g.id', 'game_name', 'relation_game_id','relation_game_name','features', 'icon', 'max(d.create_time)' => 'create_time'])
            ->join(['tab_game' => 'g'], 'g.id = d.game_id', 'left')
            ->where('user_id', $uid)
            ->where($map)
            ->order('create_time desc')
            ->group('d.game_id')
            ->select()->toArray();
        return $list;

    }

    /**
     * @函数或方法说明
     * @获取查询游戏
     * @param array $map
     *
     * @author: 郭家屯
     * @since: 2020/2/19 10:08
     */
    public function getSearchGame($map=[])
    {
        $map['game_status'] = 1;
        $data = $this->field('id as game_id,relation_game_name as game_name,sdk_version')
                ->where($map)
                ->group('relation_game_id')
                ->select()->toArray();
        return $data?:[];
    }

    /**
     * @函数或方法说明
     * @获取收藏游戏
     * @param int $uid
     * @param array $map
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/7/9 13:42
     */
    public function getMyCollectList($uid = 0, $map = [])
    {
        $list = Db::table('tab_game_collect')
            ->alias('c')
            ->field("game_id,c.id,c.id as record_id,g.game_name,g.relation_game_name,g.relation_game_id,g.sdk_version,g.icon,FROM_UNIXTIME(c.create_time,'%Y-%m-%d') as create_time")
            ->join(['tab_game' => 'g'], 'g.id = c.game_id', 'left')
            ->where('user_id', $uid)
            ->where($map)
            ->where('status',1)
            ->order('create_time desc')
            ->select()->toArray();
        return $list;

    }

    /**
     * 删除我的游戏下载记录
     *
     * @param array $map
     *
     * @return bool
     *
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\29 0029 16:59
     */
    public function deleteMyGame($map = [])
    {

        if (Db::table('tab_game_down_record')->where($map)->delete()) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * [获取推荐游戏列表区分版本号]
     * @param array $map
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getAllGameLists($map = [])
    {
        $map['game_status'] = 1;
        $data = $this->field('id,game_name,relation_game_id,sdk_version,relation_game_name,icon,dow_num,game_type_name,game_size,tag_name,features,short')
            ->where($map)
            ->order('sort desc,id desc')
            ->select()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取最近在玩游戏
     * @param int $user_id
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/6/16 20:02
     */
    public function get_play_game($map=0,$limit=10)
    {
        $map['sdk_area'] = 0; // 不显示海外游戏
        $data = $this->alias('g')
                ->field('p.game_id,g.game_name,g.icon,g.features,p.id,g.relation_game_id,g.sdk_version,screen_type,fullscreen')
                ->join(['tab_user_play'=>'p'],'g.id=p.game_id','left')
                ->where('g.game_status',1)
                ->where($map)
                ->order('p.play_time desc')
                ->limit($limit)
                ->select()->toArray();
        return $data;
    }

    /**
     * 删除我的H5在玩记录
     *
     * @param array $map
     *
     * @return bool
     *
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\29 0029 16:59
     */
    public function deleteMyPlayGame($map = [])
    {

        if (Db::table('tab_user_play')->where($map)->setField('is_del',1)) {
            return true;
        } else {
            return false;
        }

    }


    /**
     * @获取含有代金券游戏
     *
     * @author: zsl
     * @since: 2021/4/22 10:57
     */
    public function couponGame($where = [],$promote_id=0)
    {
        if($promote_id >0){
            $where['type'] = ['in',[1,3,4]];
        }else{
            $where['type'] = ['in',[1,2]];
        }
        // 获取游戏下代金券数据
        $field = "g.id as game_id,g.game_name,g.game_type_name,g.game_size,g.tag_name,g.icon,g.relation_game_id,
        count(c.id) as coupon_num,sum(c.money * c.limit) as coupon_money,sum(c.limit) as total_limit,group_concat(c.id) as coupon_ids";
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_game_id(MOBILE_PID);
            $where['g.id'] = ['in', $game_ids];
        }
        $where['g.sdk_version'] = ['in', [get_devices_type(), 3]];
        $where['c.is_delete'] = 0;
        $where['c.coupon_type'] = 0;
        $where['c.receive_start_time'] = [['lt', time()], ['eq', 0], 'or'];
        $where['c.receive_end_time'] = [['gt', time()], ['eq', 0], 'or'];
        $where['c.stock'] = ['gt', 0];
        $where['c.status'] = 1;
        $gameLists = $this -> alias('g')
                -> field($field)
                -> join(['tab_coupon' => 'c'], 'g.relation_game_id = c.game_id', 'left')
                -> where($where)
                -> group('game_id')
                -> order('sort desc')
                -> select();
        //查询通用代金券数据
        $mCoupon = new CouponModel();
        $cWhere = [];
        if($promote_id >0){
            $cWhere['type'] = ['in',[1,3,4]];
        }else{
            $cWhere['type'] = ['in',[1,2]];
        }
        $cWhere['is_delete'] = 0;
        $cWhere['mold'] = 0;
        $cWhere['coupon_type'] = 0;
        $cWhere['receive_start_time'] = [['lt', time()], ['eq', 0], 'or'];
        $cWhere['receive_end_time'] = [['gt', time()], ['eq', 0], 'or'];
        $cWhere['stock'] = ['gt', 0];
        $cWhere['status'] = 1;
        $couponInfo = $mCoupon -> alias('c') -> field("count(c.id) as coupon_num, sum(c.money * c.limit) as coupon_money, sum(c.limit) as total_limit") -> where($cWhere) -> find();
        $couponIds = $mCoupon -> where($cWhere) -> column('id');
        // 游戏代金券数据加上通用代金券数据
        if (!empty($gameLists)) {
            foreach ($gameLists as &$v) {
                $v['coupon_num'] += $couponInfo['coupon_num'];
                $v['coupon_money'] += $couponInfo['coupon_money'];
                $v['total_limit'] += $couponInfo['total_limit'];
                $v['coupon_ids'] .= ',' . implode(',', $couponIds);
            }
            unset($v);
        }
        // 获取用户领取数量
        if (!empty($gameLists)) {
            $mCouponRecord = new CouponRecordModel();
            foreach ($gameLists as &$v) {
                $crmap = [];
                $crmap['user_id'] = UID;
                $crmap['coupon_id'] = ['in', explode(',', $v['coupon_ids'])];
                $total_receive_num = $mCouponRecord -> where($crmap) -> count();
                if ($total_receive_num >= $v['total_limit']) {
                    $v['is_can_receive'] = 0;
                } else {
                    $v['is_can_receive'] = 1;
                }
            }
            unset($v);
        }
        return $gameLists;
    }


    /**
     * @修改游戏名称
     *
     * @author: zsl
     * @since: 2021/7/30 21:26
     */
    public function changeGameName($param)
    {
        $this -> startTrans();
        try {
            $oldName = $this -> where(['id' => $param['id']]) -> value('game_name');
            $relation_game_name = str_replace('(安卓版)','',$param['game_name']);
            $relation_game_name = str_replace('(苹果版)','',$relation_game_name);
            // 修改游戏名称
            Db ::table('tab_game') -> where(['id' => $param['id']]) -> update(['game_name' => $param['game_name'],'relation_game_name'=>$relation_game_name]);
            Db ::table('tab_user') -> where(['fgame_id' => $param['id']]) -> update(['fgame_name' => $param['game_name']]);
            $where = [];
            $where['game_id'] = $param['id'];
            $data = [];
            $data['game_name'] = $param['game_name'];
            Db ::table('tab_alipay_auth') -> where($where) -> update($data);
            Db ::table('tab_bt_welfare_monthcard') -> where($where) -> update($data);
            Db ::table('tab_bt_welfare_recharge') -> where($where) -> update($data);
            Db ::table('tab_bt_welfare_register') -> where($where) -> update($data);
            Db ::table('tab_bt_welfare_total_recharge') -> where($where) -> update($data);
            Db ::table('tab_bt_welfare_weekcard') -> where($where) -> update($data);
            Db ::table('tab_coupon') -> where($where) -> update($data);
            Db ::table('tab_coupon_record') -> where($where) -> update($data);
            Db ::table('tab_game_comment') -> where($where) -> update($data);
            Db ::table('tab_game_gift_record') -> where($where) -> update($data);
            Db ::table('tab_game_giftbag') -> where($where) -> update(['game_name'=>$relation_game_name]);
            Db ::table('tab_game_source') -> where($where) -> update($data);
            Db ::table('tab_notice') -> where($where) -> update($data);
            Db ::table('tab_promote_agent') -> where($where) -> update($data);
            Db ::table('tab_promote_bind') -> where($where) -> update($data);
            Db ::table('tab_promote_settlement') -> where($where) -> update($data);
            Db ::table('tab_spend') -> where($where) -> update($data);
            Db ::table('tab_spend_bind') -> where($where) -> update($data);
            Db ::table('tab_spend_distinction') -> where($where) -> update($data);
            Db ::table('tab_spend_provide') -> where($where) -> update($data);
            Db ::table('tab_spend_rebate') -> where($where) -> update($data);
            Db ::table('tab_spend_rebate_record') -> where($where) -> update($data);
            Db ::table('tab_spend_welfare') -> where($where) -> update($data);
            Db ::table('tab_spend_wxparam') -> where($where) -> update($data);
            Db ::table('tab_support') -> where($where) -> update($data);
            Db ::table('tab_user_balance_edit') -> where($where) -> update($data);
            Db ::table('tab_user_batch_create_log') -> where($where) -> update($data);
            Db ::table('tab_user_deduct_bind') -> where($where) -> update($data);
            Db ::table('tab_user_feedback') -> where($where) -> update($data);
            Db ::table('tab_user_login_record') -> where($where) -> update($data);
            Db ::table('tab_user_play') -> where($where) -> update($data);
            Db ::table('tab_user_play_info') -> where($where) -> update($data);
            Db ::table('tab_user_tplay') -> where($where) -> update($data);
            Db ::table('tab_user_tplay_record') -> where($where) -> update($data);
            Db ::table('tab_user_transaction') -> where($where) -> update($data);
            Db ::table('tab_user_transaction_order') -> where($where) -> update($data);
            Db ::table('tab_user_transaction_profit') -> where($where) -> update($data);
            Db ::table('tab_spend_bind_discount') -> where($where) -> update($data);
            Db ::table('tab_promote_user_welfare') -> where($where) -> update($data);
            Db ::table('tab_promote_user_bind_discount') -> where($where) -> update($data);
            //写入修改记录表
            $mChangeLog = new GameChangeNameLogModel();
            $logData = [];
            $logData['game_id'] = $param['id'];
            $logData['old_game_name'] = $oldName;
            $logData['new_game_name'] = $param['game_name'];
            $logData['admin_id'] = cmf_get_current_admin_id();
            $mChangeLog -> isUpdate(false) -> save($logData);
            $this -> commit();
            return true;
        } catch (\Exception $e) {
            $this -> rollback();
            return false;
        }

    }

    /**
     * 修改游戏字段
     *
     * @param array $map
     * @param string $name
     * @param string $value
     * @author: Juncl
     * @time: 2021/08/14 13:40
     * modified by wjd 2021-8-19 17:32:42
     */
    public function setField($map=[], $name='', $value='')
    {
        if(check_game_attr($name)){
            $data['game_id'] = $map['game_id'];
            $data[$name] = $value;
            $setRes = setTableGameAttr($data);
            if($setRes == -1){
                $result = false;
            }else{
                $result = $setRes;
            }
            // $GameAttrModel = new GameAttrModel();
            // $result = $GameAttrModel->where($map)->setField($name,$value);
        }else{
            $result = $this->where($map)->setField($name,$value);
        }
        return $result;
    }



}
