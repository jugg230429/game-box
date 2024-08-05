<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\common\controller\BaseHomeController;
use app\member\model\UserModel;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class TradeLogic{

    /**
     * @函数或方法说明
     * @获取出售小号列表
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/3/3 10:56
     */
    public function get_samll_list($user_id=0)
    {
        if(empty($user_id)){
            return [];
        }
        $model = new UserModel();
        $data = $model->get_sell_small_list($user_id);
        return $data;
    }

    /**
 * @函数或方法说明
 * @出售小号
 * @param array $data
 *
 * @author: 郭家屯
 * @since: 2020/3/5 11:36
 */
    public function add_sell($user=[],$data=[])
    {
        $small = get_user_entity($data['small_id'],false,'fgame_id,fgame_name,cumulative');
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['password'] = $data['password']?:'';
        $add['phone'] = $user['phone']?:'';
        $add['game_id'] = $small['fgame_id']?:'';
        $add['game_name'] = $small['fgame_name']?:'';
        $add['server_name'] = $data['server_name']?:'';
        $add['title'] = $data['title']?:'';
        $add['screenshot'] = $data['screenshot']?:'';
        $add['dec'] = $data['dec']?:'';
        $add['order_number'] = 'UT_' . date('Ymd') . date('His') . sp_random_string(4);
        $add['cumulative'] = $small['cumulative']?:0;
        $add['money'] = $data['money'];
        $add['create_time'] = time();
        $add['small_id'] = $data['small_id'];
        $model = new UserTransactionModel();
        Db::startTrans();
        try{
            $model->insert($add);
            Db::table('tab_user')->where('id',$data['small_id'])->setField('puid',$data['platform_id']);
            Db::table('tab_user_play_info')->where('user_id',$data['small_id'])->setField('puid',$data['platform_id']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @编辑小号
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/3/5 11:36
     */
    public function edit_sell($transaction=[],$data=[])
    {
        $add['password'] = $data['password']?:'';
        $add['server_name'] = $data['server_name']?:'';
        $add['title'] = $data['title']?:'';
        $add['screenshot'] = $data['screenshot']?:'';
        $add['dec'] = $data['dec']?:'';
        $add['money'] = $data['money'];
        $add['status'] = 0;
        $model = new UserTransactionModel();
        Db::startTrans();
        try{
            $model->where('id',$data['id'])->update($add);
            if($transaction['status'] == 4){
                Db::table('tab_user')->where('id',$data['small_id'])->setField('puid',$data['platform_id']);
                Db::table('tab_user_play_info')->where('user_id',$data['small_id'])->setField('puid',$data['platform_id']);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @获取购买记录
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/3/5 14:27
     */
    public function get_order($user_id=0)
    {
        if(empty($user_id))return [];
        $map['user_id'] = $user_id;
        $map['is_delete'] = 0;
        $model = new UserTransactionOrderModel();
        $base = new BaseHomeController();
        $extend['field'] = 'tab_user_transaction_order.id,title,g.game_name,g.icon,server_name,pay_time,pay_amount,tab_user_transaction_order.pay_status';
        $extend['join1'] = [['tab_game'=>'g'],'g.id=tab_user_transaction_order.game_id','left'];
        $extend['order'] = 'tab_user_transaction_order.id desc';
        $list = $base->data_list_join_select($model,$map,$extend);
        return $list;
    }

    /**
     * @函数或方法说明
     * @删除订单
     * @param int $user_id
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/3/5 15:07
     */
    public function del_order($user_id=0,$id=0)
    {
        $model = new UserTransactionOrderModel();
        $result = $model->where('user_id',$user_id)->where('id',$id)->setField('is_delete',1);
        return $result;
    }

    /**
     * @函数或方法说明
     * @取消订单
     * @param int $user_id
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/3/5 15:09
     */
    public function set_order_status($user_id=0,$id=0)
    {
        $model = new UserTransactionOrderModel();
        $info = $model -> field('id,transaction_id') -> where('user_id', $user_id) -> where('id', $id) -> find()->toArray();
        $tmodel = new UserTransactionModel();
        $transaction = $tmodel->field('id,small_id,user_id')->where('id',$info['transaction_id'])->find()->toArray();
        Db::startTrans();
        try {
            $model -> where('user_id', $user_id) -> where('id', $id) -> setField('pay_status', 2);
            $save['status'] = 1;
            $price = Db ::table('tab_user_transaction_tip') -> where('transaction_id', $transaction['id']) -> where('type', 1) -> where('status', 0) -> find();
            $lower = Db ::table('tab_user_transaction_tip') -> where('transaction_id', $transaction['id']) -> where('type', 2) -> where('status', 0) -> find();
            if ($price) {
                $save['money'] = $price['price'];
                Db ::table('tab_user_transaction_tip') -> where('id', $price['id']) -> setField('status', 1);
            }
            if ($lower) {
                $save['status'] = 4;
                $save['lower_shelf'] = '';
                Db ::table('tab_user') -> where('id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                Db ::table('tab_user_play_info') -> where('user_id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                Db ::table('tab_user_transaction_tip') -> where('id', $lower['id']) -> setField('status', 1);
            }
            $tmodel -> where('id', $info['transaction_id']) -> update($save);
            // 提交事务
            Db::commit();
        }
        catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @修改出售状态
     * @param int $user_id
     * @param int $id
     * @param int $status
     *
     * @author: 郭家屯
     * @since: 2020/3/5 15:41
     */
    public function cancel_sell($user_id=0,$id=0,$status=0)
    {
        $model = new UserTransactionModel();
        $usermodel = new UserModel();
        $data = $model->field('user_id,small_id')->where('user_id',$user_id)->where('id',$id)->find()->toArray();
        $save['status'] = $status;
        $save['lower_shelf'] = '';
        Db::startTrans();
        try{
            $usermodel->where('id',$data['small_id'])->setField('puid',$data['user_id']);
            Db::table('tab_user_play_info')->where('user_id',$data['small_id'])->setField('puid',$data['user_id']);
            $model->where('user_id',$user_id)->where('id',$id)->update($save);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }

        return true;
    }

    /**
     * @函数或方法说明
     * @删除处守纪律
     * @param int $user_id
     * @param int $id
     * @param int $status
     *
     * @author: 郭家屯
     * @since: 2020/3/5 15:41
     */
    public function del_sell($user_id=0,$id=0)
    {
        $model = new UserTransactionModel();
        $result = $model->where('user_id',$user_id)->where('id',$id)->where(['status'=>['in',['2','3','4']]])->setField('is_delete',1);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @函数或方法说明
     * @设置价格
     * @param $user_id
     * @param $id
     * @param $price
     *
     * @author: 郭家屯
     * @since: 2020/3/6 14:56
     */
    public function set_sell_price($user_id,$id,$price)
    {
        $model = new UserTransactionModel();
        $result = $model->where('user_id',$user_id)->where('id',$id)->setField('money',$price);
        if($result !== false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * @函数或方法说明
     * @获取购买记录
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/3/5 14:27
     */
    public function get_sell_list($user_id=0)
    {
        if(empty($user_id))return [];
        $map['tab_user_transaction.user_id'] = $user_id;
        $map['is_delete'] = 0;
        $model = new UserTransactionModel();
        $base = new BaseHomeController();
        $extend['order'] = "tab_user_transaction.id desc";
        $extend['field'] = 'tab_user_transaction.id,title,g.game_name,g.icon,server_name,tab_user_transaction.create_time,tab_user_transaction.money,status,reject,lower_shelf';
        $extend['join1'] = [['tab_game'=>'g'],'g.id=tab_user_transaction.game_id','left'];
        $list = $base->data_list_join_select($model,$map,$extend);
        return $list;
    }

    /**
     * @函数或方法说明
     * @获取订单详情
     * @param int $id
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/3/5 14:55
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_order_info($id=0)
    {
        $model = new UserTransactionOrderModel();
        $data = $model->alias('o')
                ->field('o.*,g.icon,relation_game_id')
                ->join(['tab_game'=>'g'],'o.game_id=g.id','left')
                ->where('o.id',$id)
                ->find();
        return $data?$data->toArray():[];
    }

    /**
     * @函数或方法说明
     * @出售详情
     * @param $id
     *
     * @author: 郭家屯
     * @since: 2020/3/5 15:48
     */
    public function get_sell_info($id)
    {
        $model = new UserTransactionModel();
        $data = $model->alias('t')
                ->field('t.*,g.icon,relation_game_id')
                ->join(['tab_game'=>'g'],'t.game_id=g.id','left')
                ->where('t.id',$id)
                ->find();
        return $data?$data->toArray():[];
    }

    /**
     * @函数或方法说明
     * @获取订单详情
     * @param int $id
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @author: 郭家屯
     * @since: 2020/3/9 11:46
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_transaction_detail($id=0)
    {
        $model = new UserTransactionModel();
        $detail = $model->alias('t')
                ->field('t.id,t.user_id,g.game_name,relation_game_id,t.screenshot,game_id,title,small_id,t.money,server_name,cumulative,t.create_time,password,dec,g.icon')
                ->join(['tab_game'=>'g'],'g.id=t.game_id','left')
                ->where('t.id',$id)
                ->where('game_status',1)
                ->where('status',1)
                ->find();
        if($detail){
            $detail->toArray();
        }else{
            return [];
        }
        $detail['screenshot'] = $detail['screenshot'] ? explode(',',$detail['screenshot']):[];
        $detail['days'] = get_days(date('Y-m-d'),date('Y-m-d',$detail['create_time']));
        $detail['small_account'] = get_user_entity($detail['small_id'],false,'nickname')['nickname'];
        return $detail;
    }

    /**
     * @函数或方法说明
     * @获取其他游戏
     * @param int $game_id
     * @param int $transaction_id
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/3/9 11:56
     */
    public function get_other_game($game_id=0,$transaction_id=0,$limit=10)
    {
        $model = new UserTransactionModel();
        $data = $model->alias('t')
                ->field('t.id,t.title,g.game_name,small_id,t.money,server_name,cumulative,t.create_time,password,dec,g.icon')
                ->join(['tab_game'=>'g'],'g.id=t.game_id','left')
                ->where('t.game_id',$game_id)
                ->where('game_status',1)
                ->where('status',1)
                ->where('t.id','neq',$transaction_id)
                ->order('t.id desc')
                ->limit($limit)
                ->select()->toArray();
        foreach ($data as $key=>$v){
            $data[$key]['days'] = get_days(date('Y-m-d'),date('Y-m-d',$v['create_time']));
            $data[$key]['small_account'] = get_user_entity($v['small_id'],false,'nickname')['nickname'];
        }
        $data['data'] = $data;
        $data['count'] = $model->alias('t')
                ->field('t.id')
                ->join(['tab_game'=>'g'],'g.id=t.game_id','left')
                ->where('t.game_id',$game_id)
                ->where('game_status',1)
                ->where('status',1)
                ->where('t.id','neq',$transaction_id)
                ->count();
        return $data;
    }

    /**
     * @函数或方法说明
     * @添加计划任务执行改价或计划任务
     * @param int $id
     * @param int $price
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/3/16 9:20
     */
    public function add_transaction_tip($id=0,$price=0,$type=1)
    {
        $save['transaction_id'] = $id;
        $save['type'] = $type;
        $save['status'] = 0;
        if($type == 1){
            $save['price'] = $price;
        }
        $record = Db::table('tab_user_transaction_tip')->where('transaction_id',$id)->where('type',$type)->find();
        if($record){
            $result = Db::table('tab_user_transaction_tip')->where('transaction_id',$id)->where('type',$type)->update($save);
        }else{
            $result = Db::table('tab_user_transaction_tip')->insert($save);
        }
        return $result;
    }
}
