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
            default:
                return '官方支付';    
        }
    }
}