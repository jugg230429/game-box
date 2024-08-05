<?php

namespace app\channelsite\logic;

use app\common\controller\BaseHomeController;
use app\game\model\GameModel;
use app\promote\model\PromoteagentModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendWelfareModel;

class WelfareLogic
{


    /**
     * @函数或方法说明
     * @获取推广用户下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    public function get_promote_rebate($promote_id=0,$game_id=0)
    {
        if($game_id){
            $map['r.game_id'] = $game_id;
        }
        $map['g.test_game_status'] = 0;
        // 渠道独占------------START
        $onlyForPromote = game_only_for_promote($promote_id);
        $forbidGameids = $onlyForPromote['forbid_game_ids'];
        $map_forbid = [];
        if(!empty($forbidGameids)){
            $map_forbid['g.id'] = ['notin', $forbidGameids];
        }
        // 渠道独占------------END

        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('r.game_id,r.ratio,g.game_name,g.icon,r.status,r.money')
                ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                ->join(['tab_game'=>'g'],'r.game_id=g.id','right')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where($map)
                ->where($map_forbid) // 渠道独占
                ->where('g.game_status',1)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->select()->toArray();
        return $rebate;
    }
    /**
     * @函数或方法说明
     * @获取推广用户下的折扣数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    public function get_promote_welfare($promote_id=0,$game_id=0)
    {
        if($game_id){
            $map['w.game_id'] = $game_id;
        }
        $map['g.test_game_status'] = 0;
        // 渠道独占------------START
        $onlyForPromote = game_only_for_promote($promote_id);
        $forbidGameids = $onlyForPromote['forbid_game_ids'];
        $map_forbid = [];
        if(!empty($forbidGameids)){
            $map_forbid['g.id'] = ['notin', $forbidGameids];
        }
        // 渠道独占------------END

        $model = new SpendWelfareModel();
        $welfare = $model->alias('w')
                ->field('w.game_id,if(first_status>0,w.first_discount,0) as first_discount,g.game_name,g.icon,if(w.continue_status>0,w.continue_discount,0) as continue_discount')
                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','right')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where($map)
                ->where($map_forbid) // 渠道独占
                ->where('g.game_status',1)
                ->where(function($query){
                    $query->where('first_status',1)->whereor('continue_status',1);
                })
                ->select()->toArray();
        return $welfare;
    }


    /**
     * @函数或方法说明
     * @获取代充数据
     * @param int $promote_id
     *
     * @author: 郭家屯
     * @since: 2020/2/10 12:02
     */
    public function get_promote_agent($promote_id=0,$game_id=0)
    {
        // if($game_id){
        //     $map['g.id'] = $game_id;
        // }
        $map['g.test_game_status'] = 0;
        $map['g.game_status'] = 1;
        // 渠道独占------------START
        $onlyForPromote = game_only_for_promote($promote_id);
        $forbidGameids = $onlyForPromote['forbid_game_ids'];
        $map_forbid = [];
        if(!empty($forbidGameids)){
            $map['g.id'] = ['notin', $forbidGameids];
        }

        if($game_id){
            $map['g.id'] = $game_id;
            if(in_array($game_id, $forbidGameids)){
                $map['g.id'] = 0;
            }
        }
        // 渠道独占------------END
        $model = new GameModel();
        $data = $model->alias('g')
                ->field('g.id as game_id,a.promote_discount,ga.discount,ga.continue_discount,g.icon,g.game_name,a.promote_discount_first,a.promote_discount_continued')
                ->join(['tab_game_attr'=>'ga'],'g.id=ga.game_id ','left')
                ->join(['tab_promote_agent'=>'a'],'g.id=a.game_id and a.promote_id='.$promote_id,'left')
                ->where('g.game_status',1)
                ->where($map)
                // ->where($map_forbid) // 渠道独占
                ->select()->toArray();
        foreach ($data as $key=>$v){
            if(empty($v['promote_discount_first']) && empty($v['promote_discount_continued']) &&
                ($v['discount'] == 10 || empty($v['discount'])) && ($v['continue_discount'] == 10 || empty($v['continue_discount']))){
               unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @代金券领取列表
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/10 17:37
     */
    public function getCouponRecord($request=[],$map=[])
    {
        $model = new CouponRecordModel();
        $base = new BaseHomeController();
        $user_account = $request['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $coupon_name = $request['coupon_name'];
        if ($coupon_name != '') {
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $limit_money = $request['limit_money'];
        if($limit_money == 1){
            $map['limit_money'] = 0;
        }elseif($limit_money == 2){
            $map['limit_money'] = ['gt',0];
        }
        $game_id = $request['game_id'];
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if($status != ''){
            if($status == 3){
                $map['is_delete'] = 1;
            }elseif($status == 2){
                $map['status'] = 0;
                $map['end_time'] = [['lt',time()],['neq',0]];
            }elseif($status == 1){
                $map['status'] = $status;
            }else{
                $map['is_delete'] = 0;
                $map['status'] = 0;
                $map['end_time'] = [['gt',time()],['eq',0],'or'];
            }
        }
        $get_way = $request['get_way'];
        if($get_way != ''){
            $map['get_way'] = $get_way;
        }
        $exend['field'] = 'id,user_account,coupon_name,game_name,mold,money,limit_money,status,create_time,update_time,cost,pay_amount,get_way,end_time,is_delete,deduct_amount';
        $data = $base->data_list($model, $map, $exend);
        return $data;
    }

    /**
     * @函数或方法说明
     * @累计汇总
     * @author: 郭家屯
     * @since: 2020/2/4 9:16
     */
    public function get_coupon_total($request=[],$map=[])
    {
        //累计统计
        $model = new CouponRecordModel();
        $base = new BaseHomeController;
        $user_account = $request['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $coupon_name = $request['coupon_name'];
        if ($coupon_name != '') {
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $game_id = $request['game_id'];
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if($status != ''){
            if($status == 2){
                $map['status'] = 0;
                $map['end_time'] = ['lt',time()];
            }else{
                $map['status'] = $status;
            }
        }
        $get_way = $request['get_way'];
        if($get_way != ''){
            $map['get_way'] = $get_way;
        }
        $exend['field'] = 'sum(pay_amount) as total,sum(cost) as totalcost,sum(deduct_amount) as totaldeduct';
        $total = $base->data_list_select($model, $map, $exend);
        return $total;
    }

    /**
     * 获取当前渠道下绑币充值的折扣数据
     * by:byh 2021-8-26
     */
    public function get_promote_bind_discount($promote_id=0,$game_id=0)
    {
        if($game_id){
            $map['b.game_id'] = $game_id;
        }
        $map['b.status'] = 1;
        $map['g.test_game_status'] = 0;
        // 渠道独占------------START
        $onlyForPromote = game_only_for_promote($promote_id);
        $forbidGameids = $onlyForPromote['forbid_game_ids'];
        $map_forbid = [];
        if(!empty($forbidGameids)){
            $map_forbid['g.id'] = ['notin', $forbidGameids];
        }
        // 渠道独占------------END

        $model = new SpendBindDiscountModel();
        $welfare = $model->alias('b')
            ->field('b.game_id,g.game_name,g.icon,b.first_discount as bind_first_discount,b.continue_discount as bind_continue_discount')
            ->join(['tab_spend_bind_discount_promote'=>'p'],'b.id=p.bind_discount_id','left')
            ->join(['tab_game'=>'g'],'b.game_id=g.id','right')
            ->where(function($query) use ($promote_id){
                $query->where(function($query) use ($promote_id){
                    $query->where('p.promote_id',$promote_id)->where('type',4);
                });
                $query->whereor('type','in',[1,3]);
            })
            ->where($map)
            ->where($map_forbid) // 渠道独占
            ->where('g.game_status',1)
            ->select()->toArray();
        return $welfare;
    }

}
