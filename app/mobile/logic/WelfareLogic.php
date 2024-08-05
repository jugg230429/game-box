<?php

namespace app\mobile\logic;

use app\game\model\GameModel;
use app\member\model\UserModel;
use app\common\controller\BaseHomeController;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendWelfareModel;

class WelfareLogic
{
    /**
     * 用户返利列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_rebate_list($user_id=0,$p=1,$limit=10)
    {
        if($user_id >0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            if($user['promote_id'] ==0){
                $data = $this->get_unpromote_rebate($p,$limit);
            }else{
                $data = $this->get_promote_rebate($user['promote_id'],$p,$limit);
            }
        }else{
            $data = $this->get_unpromote_rebate($p,$limit);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_promote_rebate($promote_id=0,$p=1,$limit=10)
    {
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('r.ratio,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                ->join(['tab_game'=>'g'],'r.game_id=g.id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->order('g.sort desc,g.id desc')
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($rebate as $key=>$v){
            $rebate[$key]['icon'] = cmf_get_image_url($v['icon']);
            $rebate[$key]['ratio'] = end(explode('/',$v['ratio']));
            $rebate[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $rebate;
    }

    /**
     * @函数或方法说明
     * @获取官方下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_rebate($p=1,$limit=10)
    {
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('r.ratio,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_game'=>'g'],'r.game_id=g.id','left')
                ->where('type','in',[1,2])
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->order('g.sort desc,g.id desc')
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($rebate as $key=>$v){
            $rebate[$key]['icon'] = cmf_get_image_url($v['icon']);
            $rebate[$key]['ratio'] = end(explode('/',$v['ratio']));
            $rebate[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $rebate;
    }

    /**
     * 用户首冲列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_first_list($user_id=0,$p=1,$limit=10)
    {
        if($user_id >0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            if($user['promote_id'] == 0){
                $data = $this->get_unpromote_first($p,$limit);
            }else{
                $data = $this->get_promote_first($user['promote_id'],$p,$limit);
            }
        }else{
            $data = $this->get_unpromote_first($p,$limit);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_promote_first($promote_id=0,$p=1,$limit=10)
    {
        $model = new SpendWelfareModel();
        $first = $model->alias('w')
                ->field('w.first_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->where('first_status',1)
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($first as $key=>$v){
            $first[$key]['icon'] = cmf_get_image_url($v['icon']);
            $first[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $first;
    }

    /**
     * @函数或方法说明
     * @获取官方下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_first($p=1,$limit=10)
    {
        $model = new SpendWelfareModel();
        $first = $model->alias('w')
                ->field('w.first_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where('type','in',[1,2])
                ->where('first_status',1)
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($first as $key=>$v){
            $first[$key]['icon'] = cmf_get_image_url($v['icon']);
            $first[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $first;
    }


    /**
     * 用户续充列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_recharge_list($user_id=0,$p=1,$limit=10)
    {
        if($user_id >0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            if($user['promote_id'] == 0){
                $data = $this->get_unpromote_recharge($p,$limit);
            }else{
                $data = $this->get_promote_recharge($user['promote_id'],$p,$limit);
            }
        }else{
            $data = $this->get_unpromote_recharge($p,$limit);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_promote_recharge($promote_id=0,$p=1,$limit=10)
    {
        $model = new SpendWelfareModel();
        $recharge = $model->alias('w')
                ->field('w.continue_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where('continue_status',1)
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($recharge as $key=>$v){
            $recharge[$key]['icon'] = cmf_get_image_url($v['icon']);
            $recharge[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $recharge;
    }

    /**
     * @函数或方法说明
     * @获取官方下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_recharge($p=1,$limit=10)
    {
        $model = new SpendWelfareModel();
        $recharge = $model->alias('w')
                ->field('w.continue_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where('type','in',[1,2])
                ->where('g.game_status',1)
                ->where('g.sdk_version',get_devices_type())
                ->where('continue_status',1)
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($recharge as $key=>$v){
            $recharge[$key]['icon'] = cmf_get_image_url($v['icon']);
            $recharge[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $recharge;
    }

    /**
     * 绑币充值列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_bind_list($p=1,$limit=10)
    {
        $model = new GameModel();
        $data = $model
                ->field('bind_recharge_discount,relation_game_name,relation_game_id,icon,game_type_name,dow_num,game_size')
                ->where('bind_recharge_discount','lt',10)
                ->where('game_status',1)
                ->where('sdk_version',get_devices_type())
                ->page($p,$limit)
                ->select()->toArray();
        foreach ($data as $key=>$v){
            $data[$key]['icon'] = cmf_get_image_url($v['icon']);
            $data[$key]['url'] = url("Game/detail",['game_id'=>$v['relation_game_id']]);
        }
        return $data;
    }

}