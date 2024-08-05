<?php

namespace app\recharge\model;

use think\Model;
use think\Db;
/**
 * yyh
 */
class CouponRecordModel extends Model
{

    protected $table = 'tab_coupon_record';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @获取我的优惠券
     * @param int $user_id
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/5 16:57
     */
    public function get_my_coupon($user_id=0,$type=1,$game_id=0,$pay_amount=0)
    {
        if($type == 3){
            $data = $this->get_all_coupon($user_id,$game_id);//sdk获取游戏所有优惠券
        }elseif($type == 1){
            $data = $this->get_valid_coupon($user_id,$game_id,$pay_amount);
        }else{
            $data = $this->get_unvalid_coupon($user_id);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取所有领取优惠券
     * @param int $user_id
     * @param int $game_id
     *
     * @author: 郭家屯
     * @since: 2020/2/6 10:20
     */
    protected function get_all_coupon($user_id=0,$game_id=0)
    {
        $coupon = $this
                ->field('coupon_name,mold,game_id,game_name,money,limit_money,start_time,end_time,status')
                ->where('user_id',$user_id)
                ->where('game_id',[['eq',$game_id],['eq',0]],'or')
                ->where('is_delete',0)
                ->order('id desc')
                ->select()->toArray();
        foreach ($coupon as $key=>$v){
            if($v['mold'] == 1){
                $game_names = $v['game_name'];
            }else{
                $game_names = "通用";
            }
            $coupon[$key]['coupon_game'] = $game_names;
        }
        return $coupon;
    }

    /**
     * @函数或方法说明
     * @获取未使用优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 17:20
     */
    protected function get_valid_coupon($user_id=0,$game_id=0,$pay_amount=0)
    {
        if($game_id > 0){
            $map['game_id'] = [['eq',$game_id],['eq',0],'or'];
        }
        if($pay_amount>0){
            $map['limit_money'] = [['elt',$pay_amount],['eq',0],'or'];
            //$map['money'] = ['lt',$pay_amount];
        }
        $coupon = $this
                ->field('id,coupon_id,coupon_name,mold,game_id,game_name,money,limit_money,start_time,end_time,status')
                ->where('user_id',$user_id)
                ->where('status',0)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->where($map)
                ->where('is_delete',0)
                ->order('id desc')
                ->select()->toArray();
        foreach ($coupon as $key=>$v){
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
     * @获取未使用优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 17:20
     */
    protected function get_unvalid_coupon($user_id=0)
    {
        $coupon = $this
                ->field('coupon_name,mold,game_id,game_name,money,limit_money,start_time,end_time,status')
                ->where('user_id',$user_id)
                ->where(function($query){
                    $query->where(function($query){
                        $query->where('status',0)->where('end_time',[['lt', time()],['neq',0]],'and');
                    });
                    $query->whereor('status',1);
                })
                ->order('id desc')
                ->where('is_delete',0)
                ->select()->toArray();
        foreach ($coupon as $key=>$v){
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
     * @获取需要通知的过期代金券
     * @author: 郭家屯
     * @since: 2020/2/24 19:51
     */
    public function get_notice_coupon()
    {
        $coupon = $this
                ->field('user_id')
                ->where('end_time','between',total(12,3))
                ->where('is_delete',0)
                ->where('status',0)
                ->group('user_id')
                ->select()->toArray();
        return $coupon;
    }
}