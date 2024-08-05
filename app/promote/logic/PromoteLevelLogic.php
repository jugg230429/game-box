<?php

namespace app\promote\logic;

use app\game\model\GameAttrModel;
use app\promote\model\PromoteLevelModel;
use app\promote\model\PromoteModel;
use app\recharge\model\SpendModel;

class PromoteLevelLogic
{

    /**
     * 设置渠道等级配置
     *
     * @param array $param
     * @return bool
     * @author: Juncl
     * @time: 2021/09/08 21:43
     */
    public function savePromoteLevel($param=[])
    {
        $GameAttrModel = new GameAttrModel();
        $promoteLevel = $param['promote_level_set'];
        // 如果为空 则给个默认值
        if(empty($promoteLevel)){
            // 所有设置渠道等级限制的游戏改为0
            $GameAttrModel->where('promote_level_limit','gt',0)->update(['promote_level_limit'=>0]);
            return true;
        }
        $data = [];
        foreach ($promoteLevel as $key => $val)
        {
            $data[$key]['promote_level'] = $val['promote_level']?:'';
            $data[$key]['level'] = $key+1;
            $data[$key]['level_name'] = $val['level_name']?:'';
            $data[$key]['sum_money'] = $val['sum_money']?:0;
            $data[$key]['cash_money'] = $val['cash_money']?:0;
        }
        cmf_set_option('promote_level_set',$data,true);
        // 已有渠道等级修改
        $PromoteLevelModel = new PromoteLevelModel();
        $promoteInfo = $PromoteLevelModel->select()->toArray();
        if(empty($promoteInfo)){
            return true;
        }
        foreach ($promoteInfo as $k =>$v)
        {
            $level = $this->checkLevel($v['sum_money'],$v['cash_money']);
            $PromoteLevelModel->where('promote_id',$v['promote_id'])->update(['promote_level'=>$level]);
        }
        // 所有设置大于该渠道等级限制的游戏改为0
        $GameAttrModel->where('promote_level_limit','gt',count($data))->update(['promote_level_limit'=>0]);
        return true;
    }

    /**
     * 设置渠道等级（包含上级渠道）
     *
     * @param int $promote_id
     * @param int $amount
     * @return bool
     * @author: Juncl
     * @time: 2021/09/10 9:21
     */
    public function setPromoteLevel($promote_id=0,$amount=0)
    {
         if($promote_id == 0 || $amount == 0){
             return true;
         }
         $promoteInfo = get_promote_entity($promote_id,'promote_level,parent_id,top_promote_id');
         // 渠道不存在 直接返回
         if(empty($promoteInfo)){
             return true;
         }
         // 更新自身等级
         $this->updatePromoteLevel($promote_id,$amount);
         //更新上级渠道等级
         if($promoteInfo['promote_level'] == 2){
             // 二级渠道存入一级渠道等级
             $this->updatePromoteLevel($promoteInfo['parent_id'],$amount);
         }elseif($promoteInfo['promote_level'] == 3){
             // 三级渠道存入一级和二级渠道等级
             $this->updatePromoteLevel($promoteInfo['parent_id'],$amount);
             $this->updatePromoteLevel($promoteInfo['top_promote_id'],$amount);
         }
    }


    /**
     * 获取渠道等级详情
     *
     * @param int $promote_id
     * @return array
     * @author: Juncl
     * @time: 2021/09/09 14:49
     */
    public function getPromoteLevel($promote_id=0)
    {
        if($promote_id == 0){
            return [];
        }
        $PromoteLevelModel = new PromoteLevelModel();
        // 查询渠道当前等级
        $promoteLevel = $PromoteLevelModel->where('promote_id',$promote_id)->find();
        if(empty($promoteLevel)){
            return [];
        }
        // 查询后台渠道等级配置
        $levels = cmf_get_option('promote_level_set');
        if(empty($levels)){
            return [];
        }
        $data = [];
        $max = count($levels);
        foreach ($levels as $key => $val){
            if($promoteLevel['promote_level'] == $val['level']){
                // 渠道当前等级
                $data['level'] = $val['level'];
                // 渠道等级名称
                $data['level_name'] = $val['level_name'];
                // 渠道当前充值
                $data['sum_money'] = $promoteLevel['sum_money'];
                // 渠道当前押金
                $data['cash_money'] = $promoteLevel['cash_money'];
                //下级所需充值
                if($max == $promoteLevel['promote_level']){
                    $data['next_money'] = '0';
                    $data['next_cash'] = '0';
                }else{
                    $next_money =  $levels[$key+1]['sum_money'] - $promoteLevel['sum_money'];
                    $next_cash =  $levels[$key+1]['cash_money'] - $promoteLevel['cash_money'];
                    $data['next_money'] =$next_money>0 ? $next_money : '0.00';
                    $data['next_cash'] =$next_cash>0 ? $next_cash : '0.00';
                }
            }
        }
        return $data;
    }

    /**
     * 存入渠道等级
     *
     * @param int $promote_id
     * @param int $amount
     * @author: Juncl
     * @time: 2021/09/09 21:47
     */
    public function updatePromoteLevel($promote_id=0,$amount=0)
    {
        // 获取渠道信息
        $promoteInfo = get_promote_entity($promote_id,'cash_money');
        if(empty($promoteInfo)){
            return true;
        }
        // 渠道已交押金 默认为 0
        if($promoteInfo['cash_money'] > 0){
            $cash_money = $promoteInfo['cash_money'];
        }else{
            $cash_money = 0;
        }
        $PromoteLevelModel = new PromoteLevelModel();
        $promoteLevel = $PromoteLevelModel->where('promote_id',$promote_id)->find();
        // 没有记录渠道等级 则新增一条数据
        if(empty($promoteLevel)){
            // 查询渠道所有充值 如果有子渠道，则包含子渠道充值
            $sum_amount = $this->getSumMoney($promote_id);
            $save['promote_id'] = $promote_id;
            $save['sum_money'] = $sum_amount;
            // 判断渠道等级
            if($sum_amount == 0){
                $save['promote_level'] = 0;
            }else{
                $save['promote_level'] = $this->checkLevel($sum_amount,$cash_money);
            }
            $PromoteLevelModel->insert($save);
        }else{
            $sum_amount = $promoteLevel['sum_money'] + $amount;
            $save['id'] = $promoteLevel['id'];
            $save['sum_money'] = $sum_amount;
            $save['promote_level'] = $this->checkLevel($sum_amount,$cash_money);
            $PromoteLevelModel->update($save);
        }
    }

    /**
     * 获取渠道所有充值（包含子渠道）
     *
     * @param int $id
     * @return int
     * @author: Juncl
     * @time: 2021/09/09 16:14
     */
    private function getSumMoney($id=0){
        $PromoteModel = new PromoteModel();
        $SpendModel = new SpendModel();
        // 获取所有子渠道ID
        $map['id|parent_id|top_promote_id'] = $id;
        $ids = $PromoteModel->where($map)->column('id');
        $data = $SpendModel
            ->field('sum(pay_amount) as sum_money')
            ->where('promote_id','in',$ids)
            ->where('pay_status',1)
            ->find()
            ->toArray();
        return $data['sum_money']?:0;
    }

    /**
     * 根据后台配置 调整渠道等级
     *
     * @param int $sum_money
     * @return int
     * @author: Juncl
     * @time: 2021/09/09 21:12
     */
    private function checkLevel($sum_money=0,$cash_money=0)
    {
        $levels = cmf_get_option('promote_level_set');
        // 没设置渠道等级则返回0级
        if(empty($levels)){
            return 0;
        }
        $level = 0;
        // 最大等级
        $max = count($levels);
        foreach ($levels as $key => $val)
        {
            if($sum_money >= $val['sum_money'] && $cash_money >= $val['cash_money']){
                if($level == $max){
                    break;
                }else{
                    if($sum_money < $levels[$key+1]['sum_money'] || $cash_money < $levels[$key+1]['cash_money']){
                        $level++;
                        break;
                    }else{
                        $level++;
                        continue;
                    }
                }
            }else{
                break;
            }
        }
        return $level;
    }

    /**
     * 获取渠道等级
     *
     * @param int $id
     * @return string
     * @author: Juncl
     * @time: 2021/09/16 20:35
     */
    public function getPromoteLevelName($id=0)
    {
        if($id == 0)
        {
            return '--';
        }
        $PromoteLevelModel = new PromoteLevelModel();
        $promoteLevel = $PromoteLevelModel->where('promote_id',$id)->value('promote_level');
        if(!$promoteLevel)
        {
            return '--';
        }
        $levels = cmf_get_option('promote_level_set');
        if(empty($levels)){
            return '--';
        }
        $data = '--';
        foreach ($levels as $key => $val)
        {
            if($promoteLevel == $val['level'])
            {
                $data = $val['level_name'];
                break;
            }
        }
        return $data;
    }
}