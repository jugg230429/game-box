<?php

namespace app\recharge\model;

use think\Model;

/**
 * jugg
 */
class SpendPromoteParamModel extends Model
{

    protected $table = 'tab_spend_promote_param';

    protected $autoWriteTimestamp = true;

    /**
     * 选择的支付商家配置
     * gameId 游戏id
     * payType 支付类型 1.支付宝 2.微信
     * amount 支付金额
     */
    public function choosePromoteConfig($gameId,$payType,$amount){
        //查找对应游戏和类型以及支付金额的商家配置,进行权重筛选,如果没有则走默认
        $map = [
            'game_id' => $gameId,
            'type' => $payType,
            'status' => 1,
            'min_amount' => ['<=',$amount],
            'max_amount' => ['>=',$amount]   
        ];
        $weights = $this->where($map)->order('id desc')->column('weight','id');
        if($weights == null){
            $map['game_id'] = 0;
            $weights = $this->where($map)->order('id desc')->column('weight','id');
        }
        if(!$weights){
            //判断是否有开启，金额过小还是过大
            unset($map['max_amount']);
            unset($map['min_amount']);
            $weights = $this->where($map)->order('id desc')->column('weight','id');
            if(!$weights){
                exit(base64_encode(json_encode(['code'=>500,'msg'=>'支付通道异常,请您选其它方式支付'], JSON_FORCE_OBJECT)));
            }
            $map['min_amount'] =  ['<=',$amount];
            $weights = $this->where($map)->order('id desc')->column('weight','id');
            if(!$weights){
                exit(base64_encode(json_encode(['code'=>500,'msg'=>'订单小于支付通道最小金额，请您选其它方式支付'], JSON_FORCE_OBJECT)));
            }
            unset($map['min_amount']);
            $map['max_amount'] =  ['>=',$amount];
            $weights = $this->where($map)->order('id desc')->column('weight','id');
            if(!$weights){
                exit(base64_encode(json_encode(['code'=>500,'msg'=>'订单大于支付通道最大金额，请您选其它方式支付'], JSON_FORCE_OBJECT)));
            }
        }
        $configId = $this->getRandomItems($weights);
        return $this->where('id',$configId)->find();
    }

    public function getRandomItems($weights) {
        $total_weight = array_sum($weights);
        $random_number = mt_rand(1, $total_weight);
        $result = null;
        $sum = 0;
        foreach ($weights as $key => $weight) {
            $sum += $weight;
            if ($random_number <= $sum) {
                $result = $key;
                break;
            }
        }
        return $result;
    }


    public function getPromoteBussinessNameById($id){
        $promote_id = $this->where('id',$id)->value('promote_id');
        switch($promote_id){
            case 1:
                return '鼎盛支付';
            case 2:
                return '蚂蚁支付';
            case 3:
                return '彩虹易支付';
            case 4:
                return 'hiPay支付';
            case 5:
                return '大头支付';
            default:
                return '官方支付';    
        }
    }
}