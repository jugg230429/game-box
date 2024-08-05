<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\game\model\GameModel;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use app\promote\model\PromoteUserBindDiscountModel;
use app\promote\model\PromoteUserWelfareModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendWelfareModel;
use app\webplatform\logic\UserLogic;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PayLogic{

    /**
     * @函数或方法说明
     * @返利发放
     * @param string $spend 订单信息
     * @param string $user 用户信息
     * @param int $pay_way  0为现金和平台币支付方式   1 绑币支付
     *
     * @author: 郭家屯
     * @since: 2020/1/10 13:51
     */
    public function set_ratio($spend=[],$user=[],$pay_way=0)
    {
        //type  返利类型  1全站  2 官方  3渠道 4部分渠道 5部分游戏玩家
        if($user['promote_id'] >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        if($pay_way == 1){
            $map['bind_status'] = 1;
        }
        $model = new SpendRebateModel();
        $rebate = $model
            ->field('id,game_id,type,money,ratio,status,bind_status')
            ->where($map)
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->where('game_id',$spend['game_id'])
            ->find();
        if(!$rebate){
            return true;
        }
        //部分渠道处理
        if($rebate['type'] == 4){
            $where['p.promote_id'] = $user['promote_id'];
            if($pay_way == 1){
                $where['r.bind_status'] = 1;
            }
            //查找部分渠道数据
            $rebate = $model->alias('r')
                    ->field('r.id,game_id,type,money,ratio,status,bind_status')
                    ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                    ->where($where)
                    ->where('start_time', ['<', time()], ['=', 0], 'or')
                    ->where('end_time', ['>', time()], ['=', 0], 'or')
                    ->where('game_id',$spend['game_id'])
                    ->find();
            if(!$rebate){
                return true;
            }
        }
        //部分游戏玩家处理-20210629-byh
        if($rebate['type'] == 5){
            $where['gu.game_user_id'] = $user['id'];
            if($pay_way == 1){
                $where['r.bind_status'] = 1;
            }
            //查找部分玩家数据
            $rebate = $model->alias('r')
                ->field('r.id,game_id,type,money,ratio,status,bind_status')
                ->join(['tab_spend_rebate_game_user'=>'gu'],'r.id=gu.rebate_id','left')
                ->where($where)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->where('game_id',$spend['game_id'])
                ->find();
            if(!$rebate){
                return true;
            }
        }
        //无金额限制
        if($rebate['status'] == 0){
            $data['ratio'] = $rebate['ratio'];
            $data['ratio_amount'] = round($spend['pay_amount']*$rebate['ratio']/100,2);
        }else{
            $money = explode('/',$rebate['money']);
            $ratio = explode('/',$rebate['ratio']);
            if($spend['pay_amount']<$money[0]){//无返利
                return true;
            }elseif($spend['pay_amount'] >= end($money)){//最大返利
                $data['ratio'] = end($ratio);
                $data['ratio_amount'] = round($spend['pay_amount']*$data['ratio']/100,2);
            }else{//中间返利
                foreach ($money as $key=>$v){
                    if($spend['pay_amount'] <$v){
                        $data['ratio'] = $ratio[$key-1];
                        $data['ratio_amount'] = round($spend['pay_amount']*$data['ratio']/100,2);
                        break;
                    }
                }
            }

        }
        //写入记录
        $this->add_rebate_record($spend,$data);
        //增加绑币
        Db::table('tab_user_play')->where('user_id',$spend['user_id'])->where('game_id',$spend['game_id'])->setInc('bind_balance',$data['ratio_amount']);
        return true;
    }

    /**
     * @函数或方法说明
     * @写入返利记录
     * @param array $spend
     * @param array $data
     *
     * @return int|string
     *
     * @author: 郭家屯
     * @since: 2020/1/10 15:51
     */
    protected function add_rebate_record($spend=[],$data=[])
    {
        $data['pay_order_number'] = $spend['pay_order_number'];
        $data['game_id'] = $spend['game_id'];
        $data['game_name'] = $spend['game_name'];
        $data['user_id'] = $spend['user_id'];
        $data['user_account'] = $spend['user_account'];
        $data['pay_amount'] = $spend['pay_amount'];
        $data['promote_id'] = $spend['promote_id'];
        $data['promote_account'] = $spend['promote_account'];
        $data['create_time'] = time();
        $result = Db::table('tab_spend_rebate_record')->insert($data);

        // 将返利记录写入消息通知 (tab_tip 表)
        // by wjd 
        $tip = [];
        $tip['user_id'] = $spend['user_id'];
        $tip['title'] = '返利到账';
        $tip['create_time'] =time();
        $game_name = $spend['game_name'];
        $ratio = $data['ratio'];
        $ratio_amount = $data['ratio_amount'];
        $ratio_amount22 = sprintf("%01.2f", $ratio_amount);
        $tip['content'] = '您在'.$game_name.'内的返利优惠<绑定平台币 '.$ratio_amount22.'>已到账, 请注意查收';
        $tip['type'] = 6; // 添加类型 6:充值返利 到账消息
        Db::table('tab_tip')->insert($tip);

        return $result;
    }

    /**
     * @函数或方法说明
     * @获取折扣
     * @param int $game_id
     * @param int $promote_id
     *
     * @author: 郭家屯
     * @since: 2020/1/11 14:17
     */
    public function get_discount($game_id=0,$promote_id=0,$user_id=0)
    {
        // 简化版玩家折扣
        if($game_id > 0 && is_third_platform($promote_id)){
            $UserLogic = new UserLogic($promote_id);
            $res = $UserLogic->get_user_discount($user_id,$game_id);
            if($res){
                return $res;
            }else{
                $res = [];
                $res['discount'] = 1;
                $res['discount_type'] = 0;//无折扣
                return $res;
            }
        }

        $spend = Db::table('tab_spend')->field('id')->where('user_id',$user_id)->where('game_id',$game_id)->where('pay_status',1)->find();

        //查询渠道是否配置了游戏折扣
        if($promote_id>0){
            //查询当前渠道的顶级渠道
            $top_promote_id = get_top_promote_id($promote_id);
            $qd_model = new PromoteUserWelfareModel();
            $qd_where = [
                'game_id'=>$game_id,
                'promote_id'=>$top_promote_id
            ];
            $welfare = $qd_model->where($qd_where)->find();
            if(!empty($welfare)){//有设置,判断开关
                $welfare = $welfare->toArray();
                //判断渠道设置的折扣玩家是否符合,不符合,则无折扣
                if($welfare['type'] == 2 && $user_id>0){
                    $qd_user = Db::table('tab_promote_user_welfare_game_user')
                        ->field('id')
                        ->where(['user_welfare_id'=>$welfare['id'],'game_user_id'=>$user_id])->find();
                    if(empty($qd_user)){
                        $res['discount'] = 1;
                        $res['discount_type'] = 0;//无折扣
                        return $res;
                    }
                }
                if($spend && $welfare['continue_status'] == 1){
                    $res['discount'] = $welfare['continue_discount']/10;
                    $res['discount_type'] = 2;//续充折扣
                    return $res;
                }elseif(!$spend && $welfare['first_status'] == 1){
                    $res['discount'] = $welfare['first_discount']/10;
                    $res['discount_type'] = 1;//首充折扣
                    return $res;
                }else{
                    $res['discount'] = 1;
                    $res['discount_type'] = 0;//无折扣
                    return $res;
                }
            }
        }

        if($promote_id >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        $model = new SpendWelfareModel();
        $welfare = $model
                ->field('id,game_id,type,first_discount,continue_discount,first_status,continue_status')
                ->where($map)
                ->where('game_id',$game_id)
                ->find();
        if(!$welfare){
            $res['discount'] = 1;
            $res['discount_type'] = 0;//无折扣
            return $res;
        }
        //部分渠道处理
        if($welfare['type'] == 4){
            $where['p.promote_id'] = $promote_id;
            //查找部分渠道数据
            $welfare = $model->alias('w')
                    ->field('w.id,w.type,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                    ->where($where)
                    ->where('w.game_id',$game_id)
                    ->find();
            if(!$welfare){
                $res['discount'] = 1;
                $res['discount_type'] = 0;//无折扣
                return $res;
            }
        }
        //部分玩家处理-byh-20210629
        if($welfare['type'] == 5){
            $where['gu.game_user_id'] = session('member_auth.user_id');
            //查找部分渠道数据
            $welfare = $model->alias('w')
                ->field('w.id,w.type,game_id,first_discount,continue_discount,first_status,continue_status')
                ->join(['tab_spend_welfare_game_user'=>'gu'],'w.id=gu.welfare_id','left')
                ->where($where)
                ->where('w.game_id',$game_id)
                ->find();
            if(!$welfare){
                $res['discount'] = 1;
                $res['discount_type'] = 0;//无折扣
                return $res;
            }
        }
        if($spend && $welfare['continue_status'] == 1){
            $res['discount'] = $welfare['continue_discount']/10;
            $res['discount_type'] = 2;//续充折扣
            return $res;
        }elseif(!$spend && $welfare['first_status'] == 1){
            $res['discount'] = $welfare['first_discount']/10;
            $res['discount_type'] = 1;//首充折扣
            return $res;
        }else{
            $res['discount'] = 1;
            $res['discount_type'] = 0;//无折扣
            return $res;
        }
    }


    /**
     * @函数或方法说明
     * @获取详情返利
     * @param int $game_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/1/11 15:53
     */
    public function get_detail_ratio($game_id=0,$promote_id=0)
    {
        if($promote_id >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        $model = new SpendRebateModel();
        $rebate = $model
                ->field('money,ratio,status,type')
                ->where($map)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->where('game_id',$game_id)
                ->find();
        if(!$rebate){
            return [];
        }
        //部分渠道处理
        if($rebate['type'] == 4){
            $where['p.promote_id'] = $promote_id;
            //查找部分渠道数据
            $rebate = $model->alias('r')
                    ->field('money,ratio,status,type')
                    ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                    ->where($where)
                    ->where('start_time', ['<', time()], ['=', 0], 'or')
                    ->where('end_time', ['>', time()], ['=', 0], 'or')
                    ->where('game_id',$game_id)
                    ->find();
            if(!$rebate){
                return [];
            }
        }
        //部分游戏玩家处理-20210629-byh
        if($rebate['type'] == 5){
            $where2['gu.game_user_id'] = session('member_auth.user_id');
            //查找部分渠道数据
            $rebate = $model->alias('r')
                ->field('money,ratio,status,type')
                ->join(['tab_spend_rebate_game_user'=>'gu'],'r.id=gu.rebate_id','left')
                ->where($where2)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->where('game_id',$game_id)
                ->find();
            if(!$rebate){
                return [];
            }
        }
        if($rebate['status'] == 1){
            $rebate['money'] = explode('/',$rebate['money']);
            $rebate['ratio'] = explode('/',$rebate['ratio']);
        }
        return $rebate;
    }

    /**
     * @函数或方法说明
     * @获取详情页折扣
     * @param int $game_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/1/11 15:54
     */
    public function get_detail_discount($game_id=0,$promote_id=0,$user_id=0)
    {
        $res = [];
        //优先查询渠道自定义情况
        if($promote_id>0){
            $top_promote_id = get_top_promote_id($promote_id);
            $qd_model = new PromoteUserWelfareModel();
            $qd_where = [
                'game_id'=>$game_id,
                'promote_id'=>$top_promote_id,
            ];
            $qd_welfare = $qd_model->where($qd_where)->find();
            //判断渠道数据
            if(!empty($qd_welfare)){
                $qd_welfare = $qd_welfare->toArray();
                //判断渠道折扣类型和玩家是否享受折扣
                if($qd_welfare['type'] == 2 && $user_id>0){
                    $qd_user = Db::table('tab_promote_user_welfare_game_user')
                        ->field('id')
                        ->where(['user_welfare_id'=>$qd_welfare['id'],'game_user_id'=>$user_id])
                        ->find();
                    if(empty($qd_user)){//玩家不在折扣内,则不享受
                        return [];
                    }
                }
                if($qd_welfare['first_status'] != 1 && $qd_welfare['continue_status'] != 1){
                    return [];
                }
                if($qd_welfare['first_status'] == 1){
                    $res['first_discount'] = $qd_welfare['first_discount'];
                }
                if($qd_welfare['continue_status'] == 1){
                    $res['continue_discount'] = $qd_welfare['continue_discount'];
                }
                return $res;
            }
        }


        if($promote_id >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        $model = new SpendWelfareModel();
        $welfare = $model
                ->field('type,first_discount,continue_discount,first_status,continue_status')
                ->where($map)
                ->where('game_id',$game_id)
                ->find();
        if(!$welfare){
            return [];
        }
        $welfare = $welfare->toArray();
        //部分渠道处理
        if($welfare['type'] == 4){
            $where['p.promote_id'] = $promote_id;
            //查找部分渠道数据
            $welfare = $model->alias('w')
                    ->field('w.id,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                    ->where($where)
                    ->where('w.game_id',$game_id)
                    ->find();
            if(!$welfare){
                return [];
            }
            $welfare = $welfare->toArray();
        }
        //部分玩家处理-byh-20210629
        if($welfare['type'] == 5   && (session('member_auth.user_id') || $user_id>0)){
            if(empty($user_id)){
                $where2['gu.game_user_id'] = session('member_auth.user_id');
            }else{
                $where2['gu.game_user_id'] = $user_id;
            }
            //查找部分玩家数据
            $welfare = $model->alias('w')
                ->field('w.id,game_id,first_discount,continue_discount,first_status,continue_status')
                ->join(['tab_spend_welfare_game_user'=>'gu'],'w.id=gu.welfare_id','left')
                ->where($where2)
                ->where('w.game_id',$game_id)
                ->find();
            if(!$welfare){
                return [];
            }
            $welfare = $welfare->toArray();
        }
        if($welfare['first_status'] == 1){
            $res['first_discount'] = $welfare['first_discount'];
        }
        if($welfare['continue_status'] == 1){
            $res['continue_discount'] = $welfare['continue_discount'];
        }
        return $res;
    }

    /**
     * @函数或方法说明
     * @获取详情页优惠券
     * @param int $game_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/1/11 15:54
     */
    public function get_detail_coupon($user_id=0,$game_id=0,$promote_id=0)
    {
        if($promote_id >0){
            $map['type'] = ['in',[1,3,4]];
        }else{
            $map['type'] = ['in',[1,2]];
        }
        $model = new CouponModel();
        $coupon = $model
                ->field('id,mold,game_id,game_name,coupon_name,money,limit_money,limit,start_time,end_time,type')
                ->where($map)
                ->where('game_id',[['=',0],['=',$game_id]],'or')
                ->where('stock','gt',0)
                ->where('status',1)
                ->where('receive_start_time', ['<', time()], ['=', 0], 'or')
                ->where('receive_end_time', ['>', time()], ['=', 0], 'or')
                ->where('is_delete',0)
                ->where('coupon_type',0)
                ->order('id desc')
                ->select()->toArray();
        $recordmodel = new CouponRecordModel();
        foreach ($coupon as $key=>$v){
            if(!$user_id){
                $coupon[$key]['receive_status'] = 0;
                $coupon[$key]['login_status'] = 1;
            }else{
                if($promote_id >0 && $v['type'] == 4){
                    $valid = Db::table('tab_coupon_promote')->where('coupon_id',$v['id'])->where('promote_id',$promote_id)->field('id')->find();
                    if(!$valid){
                        unset($coupon[$key]);
                        continue;
                    }
                }
                $countrecord = $recordmodel->field('id')
                        ->where('user_id',$user_id)
                        ->where('coupon_id',$v['id'])
                        ->count();
                if($countrecord >= $v['limit']){
                    $coupon[$key]['receive_status'] = 1;
                }else{
                    $coupon[$key]['receive_status'] = 0;
                }
                $coupon[$key]['limit_num'] = $v['limit'] - $countrecord;
            }
            if($v['mold'] == 1){
               $game_names = $v['game_name'];
            }else{
                $game_names = "全部游戏";
            }
            $coupon[$key]['coupon_game'] = $game_names;
        }
        return array_values($coupon);
    }

    /**
     * @函数或方法说明
     * @获取可领取优惠券
     * @param int $game_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/1/11 15:54
     */
    public function get_coupon_lists($user_id=0,$promote_id=0,$game_id=0,$amount=0)
    {
        if($promote_id >0){
            $map['type'] = ['in',[1,3,4]];
        }else{
            $map['type'] = ['in',[1,2]];
        }
        if($game_id){
            $map['game_id'] = [['eq',$game_id],['eq',0],'or'];
        }
        if($amount>0){
            $map['limit_money'] = [['elt',$amount],['eq',0],'or'];
            //$map['money'] = ['lt',$amount];
        }
        $model = new CouponModel();
        $coupon = $model
                ->field('id,mold,game_id,game_name,coupon_name,money,limit_money,limit,start_time,end_time,type')
                ->where($map)
                ->where('stock','gt',0)
                ->where('status',1)
                ->where('receive_start_time', ['<', time()], ['=', 0], 'or')
                ->where('receive_end_time', ['>', time()], ['=', 0], 'or')
                ->where('is_delete',0)
                ->where('coupon_type',0)
                ->order('id desc')
                ->select()->toArray();
        $recordmodel = new CouponRecordModel();
        foreach ($coupon as $key=>$v){
            if($promote_id >0 && $v['type'] == 4){
                $valid = Db::table('tab_coupon_promote')->where('coupon_id',$v['id'])->where('promote_id',$promote_id)->field('id')->find();
                if(!$valid){
                    unset($coupon[$key]);
                    continue;
                }
            }
            $recordcount = $recordmodel->field('status')
                    ->where('user_id',$user_id)
                    ->where('coupon_id',$v['id'])
                    ->count();
            if($recordcount >= $v['limit']){
                unset($coupon[$key]);
                continue;
            }
            $coupon[$key]['limit_num'] = $v['limit'] - $recordcount;
            if($v['mold'] == 1){
                $game_names = $v['game_name'];
            }else{
                $game_names = "全部游戏";
            }
            $coupon[$key]['coupon_game'] = $game_names;
        }
        return $coupon;
    }


    /**
     * @函数或方法说明
     * @获取代充折扣
     * @param int $game_id
     * @param int $promote_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/1/19 14:50
     */
    public function get_agent_discount($game_id=0,$promote_id=0,$user_id=0)
    {
        $data = get_promote_dc_discount($promote_id, $game_id, $user_id);
        return $data?$data:[];
    }

    /**
     * @函数或方法说明
     * @获取使用的优惠券信息
     * @param $user_id
     * @param $price
     * @param $coupon_id
     *
     * @author: 郭家屯
     * @since: 2020/2/6 14:25
     */
    public function get_use_coupon($user_id=0,$price=0,$coupon_id=0)
    {
        $recordmodel = new CouponRecordModel();
        $record = $recordmodel
                ->field('limit,money,coupon_id')
                ->where('user_id',$user_id)
                ->where('id',$coupon_id)
                ->where('status',0)
                ->where('limit_money',[['elt',$price],['eq',0]],'or')
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->find();
        if(!$record){
            return 0;
        }
        $record = $record->toArray();
        //后台发放数量+抽奖获取数量+尊享卡发放
        $houtai_count = $recordmodel
                ->where('user_id',$user_id)
                ->where('coupon_id',$record['coupon_id'])
                ->where('get_way','in',[1,2])
                ->count();
        //使用数量
        $use_count = $recordmodel
                ->where('user_id',$user_id)
                ->where('coupon_id',$record['coupon_id'])
                ->where('status',1)
                ->count();
        if($record['limit'] > 0 && ($use_count >= ($houtai_count + $record['limit']))){
            return 0;
        }
        return $record['money'];
    }


    /**
     * @函数或方法说明
     * @写入交易订单
     * @param array $user
     * @param array $transaction
     *
     * @author: 郭家屯
     * @since: 2020/3/6 17:40
     */
    public function add_transaction($user=[],$transaction=[],$request=[])
    {
        $data['user_id'] =  $user['id'];
        $data['user_account'] = $user['account'];
        $data['game_id'] = $transaction['game_id'];
        $data['game_name'] = $transaction['game_name'];
        $data['server_name'] = $transaction['server_name'];
        $data['title'] = $transaction['title'];
        $data['screenshot'] = $transaction['screenshot'];
        $data['dec'] = $transaction['dec'];
        $data['cumulative'] = $transaction['cumulative'];
        $data['pay_order_number'] = $request['pay_order_number'];
        $data['transaction_number'] = $transaction['order_number'];
        $data['balance_money'] = $request['balance_money']?:0;
        $data['pay_amount'] = $transaction['money'];
        $data['fee'] = $request['fee']?:0;
        $data['pay_time'] = $request['time']?:time();
        $data['pay_status'] = $request['status'] ? : 0;
        $data['pay_way'] = $request['pay_way'];
        $data['pay_ip'] = get_client_ip();
        $data['sell_id'] = $transaction['user_id'];
        $data['sell_account'] = $transaction['user_account'];
        $data['small_id'] = $transaction['small_id'];
        $data['phone'] = $transaction['phone'];
        $data['password'] = $transaction['password'];
        $data['transaction_id'] = $transaction['id'];
        $model = new UserTransactionOrderModel();
        $result = $model->insert($data);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询处理绑币充值折扣配置情况
     * by:byh 2021-8-25 10:24:07
     * @param int $game_id 游戏id
     * @param int $promote_id 渠道id
     * @param int $user_id (输入的账号/被充值的)玩家id
     */
    public function get_bind_discount($game_id=0,$promote_id=0,$user_id=0)
    {
        //游戏表的折扣配置
        //查询玩家是否已存在支付的游戏订单-判断首充续充状态
        $spend = Db::table('tab_spend_bind')->field('id')->where('user_id',$user_id)->where('game_id',$game_id)->where('pay_status',1)->find();

        //查询渠道是否配置了游戏折扣
        if($promote_id>0){
            //查询当前渠道的顶级渠道
            $top_promote_id = get_top_promote_id($promote_id);
            $qd_model = new PromoteUserBindDiscountModel();
            $qd_where = [
                'game_id'=>$game_id,
                'promote_id'=>$top_promote_id
            ];
            $bind = $qd_model->where($qd_where)->find();
            if(!empty($bind)){//有设置,判断开关
                $bind = $bind->toArray();
                //判断渠道设置的折扣玩家是否符合,不符合,则无折扣
                if($bind['type'] == 2 && $user_id>0){
                    $qd_user = Db::table('tab_promote_user_bind_discount_game_user')
                        ->field('id')
                        ->where(['user_bind_discount_id'=>$bind['id'],'game_user_id'=>$user_id])->find();
                    if(empty($qd_user)){
                        $res['discount'] = 1;
                        $res['discount_type'] = 0;//无折扣
                        return $res;
                    }
                }
                if($spend && $bind['continue_status'] == 1){
                    $res['discount'] = $bind['continue_discount']/10;
                    $res['discount_type'] = 2;//续充折扣
                    return $res;
                }elseif(!$spend && $bind['first_status'] == 1){
                    $res['discount'] = $bind['first_discount']/10;
                    $res['discount_type'] = 1;//首充折扣
                    return $res;
                }else{
                    $res['discount'] = 1;
                    $res['discount_type'] = 0;//无折扣
                    return $res;
                }
            }
        }
        //以上未return,则继续查询后台的配置
        if($promote_id >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        $model = new SpendBindDiscountModel();
        //查询当前游戏的绑币充值折扣配置
        $bind = $model
            ->field('id,game_id,type,first_discount,continue_discount,first_status,continue_status')
            ->where($map)
            ->where('game_id',$game_id)
            ->find();
        if(!empty($bind)){//有设置,判断类型和开关
            $bind = $bind->toArray();
            //部分渠道处理
            if($bind['type'] == 4){
                $where['p.promote_id'] = $promote_id;
                //查找部分渠道数据
                $bind = $model->alias('b')
                    ->field('b.id,b.type,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_bind_discount_promote'=>'p'],'b.id=p.bind_discount_id','left')
                    ->where($where)
                    ->where('b.game_id',$game_id)
                    ->find();
                if(empty($bind)){
                    $res['discount'] = 1;
                    $res['discount_type'] = 0;//无折扣
                    return $res;
                }
                $bind = $bind->toArray();
            }
            //部分玩家处理
            if($bind['type'] == 5){
//            $where['gu.game_user_id'] = session('member_auth.user_id');
                $where['gu.game_user_id'] = $user_id;
                //查找部分渠道数据
                $bind = $model->alias('b')
                    ->field('b.id,b.type,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_bind_discount_game_user'=>'gu'],'b.id=gu.bind_discount_id','left')
                    ->where($where)
                    ->where('b.game_id',$game_id)
                    ->find();
                if(empty($bind)){
                    $res['discount'] = 1;
                    $res['discount_type'] = 0;//无折扣
                    return $res;
                }
                $bind = $bind->toArray();
            }
            //判断处理返回
            if($spend && $bind['continue_status'] == 1){
                $res['discount'] = $bind['continue_discount']/10;
                $res['discount_type'] = 2;//续充折扣
                return $res;
            }elseif(!$spend && $bind['first_status'] == 1){
                $res['discount'] = $bind['first_discount']/10;
                $res['discount_type'] = 1;//首充折扣
                return $res;
            }else{
                $res['discount'] = 1;
                $res['discount_type'] = 0;//无折扣
                return $res;

            }
        }
        //以上未有返回,继续查询游戏中的绑币充值首充续充折扣配置
        $game_ds_arr = get_game_attr_entity($game_id,'bind_recharge_discount,bind_continue_recharge_discount');
        $yx_first_discount = $game_ds_arr['bind_recharge_discount']??10;
        $yx_continue_discount = $game_ds_arr['bind_continue_recharge_discount']??10;
        if($spend && $yx_continue_discount != 10){
            $res['discount'] = $yx_continue_discount/10;
            $res['discount_type'] = 2;//续充折扣
            return $res;
        }elseif(!$spend && $yx_first_discount != 10){
            $res['discount'] = $yx_first_discount/10;
            $res['discount_type'] = 1;//首充折扣
            return $res;
        }else{
            $res['discount'] = 1;
            $res['discount_type'] = 0;//无折扣
            return $res;
        }


    }

    /**
     * 获取详情页绑币充值折扣
     * by:byh  2021-8-26 09:51:24
     * 备注:渠道自定义>后台配置>游戏配置,折扣一旦配置,按照对应的配置处理,不享受上一个等级的配置
     */
    public function get_detail_bind_discount($game_id=0,$promote_id=0,$user_id=0)
    {
        $res = [];
        //根据游戏和渠道查询是否有自定义设置
        if($promote_id>0){
            $top_promote_id = get_top_promote_id($promote_id);
            $qd_model = new PromoteUserBindDiscountModel();
            $qd_where = [
                'game_id'=>$game_id,
                'promote_id'=>$top_promote_id,
            ];
            $bind = $qd_model->where($qd_where)->find();
            //判断渠道数据
            if(!empty($bind)){
                $bind = $bind->toArray();
                //判断渠道折扣类型和玩家是否享受折扣
                if($bind['type'] == 2 && $user_id>0){
                    $qd_user = Db::table('tab_promote_user_bind_discount_game_user')
                        ->field('id')
                        ->where(['user_bind_discount_id'=>$bind['id'],'game_user_id'=>$user_id])
                        ->find();
                    if(empty($qd_user)){//玩家不在折扣内,则不享受
                        return [];
                    }
                }
                if($bind['first_status'] != 1 && $bind['continue_status'] != 1){
                    return [];
                }
                $res['first_status'] = $bind['first_status'];
                $res['first_discount'] = $bind['first_discount'];
                $res['continue_status'] = $bind['continue_status'];
                $res['continue_discount'] = $bind['continue_discount'];
                return $res;
            }
        }

        //查询后台配置的折扣
        if($promote_id >0){
            $map['type'] = ['in',[1,3,4,5]];
        }else{
            $map['type'] = ['in',[1,2,5]];
        }
        $model = new SpendBindDiscountModel();
        $bind = $model
            ->field('type,first_discount,continue_discount,first_status,continue_status')
            ->where($map)
            ->where('game_id',$game_id)
            ->find();

        if(!empty($bind)){
            $bind = $bind->toArray();
            //部分渠道处理
            if($bind['type'] == 4){
                $where['p.promote_id'] = $promote_id;
                //查找部分渠道数据
                $bind = $model->alias('bd')
                    ->field('bd.id,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_bind_discount_promote'=>'p'],'bd.id=p.bind_discount_id','left')
                    ->where($where)
                    ->where('bd.game_id',$game_id)
                    ->find();
                if(empty($bind)){//不存在,则不享受
                    return [];
                }
            }
            //部分玩家处理-byh-20210629
            if($bind['type'] == 5  && (session('member_auth.user_id') || $user_id>0)){
                if(empty($user_id)){
                    $where2['gu.game_user_id'] = session('member_auth.user_id');
                }else{
                    $where2['gu.game_user_id'] = $user_id;
                }
                //查找部分玩家数据
                $bind = $model->alias('bd')
                    ->field('bd.id,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_bind_discount_game_user'=>'gu'],'bd.id=gu.bind_discount_id','left')
                    ->where($where2)
                    ->where('bd.game_id',$game_id)
                    ->find();
                if(empty($bind)){//不存在,则不享受
                    return [];
                }
            }
            if($bind['first_status'] != 1 && $bind['continue_status'] != 1){
                return [];
            }
            $res['first_status'] = $bind['first_status'];
            $res['first_discount'] = $bind['first_discount'];
            $res['continue_status'] = $bind['continue_status'];
            $res['continue_discount'] = $bind['continue_discount'];
            return $res;

        }

        //查询出游戏中的绑币充值折扣配置
        $game_ds_arr = get_game_attr_entity($game_id,'bind_recharge_discount,bind_continue_recharge_discount');
        $first_discount = $game_ds_arr['bind_recharge_discount']??'10.00';
        $continue_discount = $game_ds_arr['bind_continue_recharge_discount']??'10.00';

        $res['first_status'] = 1;
        $res['first_discount'] = $first_discount;
        $res['continue_status'] = 1;
        $res['continue_discount'] = $continue_discount;
        if($first_discount == 10){
            $res['first_status'] = 0;
        }
        if($continue_discount == 10){
            $res['continue_status'] = 0;
        }
        return $res;
    }







}
