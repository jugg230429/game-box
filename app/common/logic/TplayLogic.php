<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\member\model\UserTplayModel;
use app\member\model\UserTplayRecordModel;
use app\user\model\UserModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class TplayLogic{


    /**
     * @函数或方法说明
     * @修改试玩状态
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/3/20 13:54
     */
    public function changeStatus($data=[])
    {
        $status = $data['status']==0 ? 1:0;
        $recordstatus = $data['status']==0 ? 0:2;
        $model = new UserTplayModel();
        $recordmodel = new UserTplayRecordModel();
        Db::startTrans();
        try{
            $model->where('id',$data['id'])->setField('status',$status);
            $recordmodel->where('tplay_id',$data['id'])->where('status','neq',1)->setField('status',$recordstatus);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @新增任务
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/3/20 15:07
     */
    public function addTplay($data=[])
    {
        $model = new UserTplayModel();
        $data['create_time'] = time();
        $result = $model->insert($data);
        return $result;
    }

    /**
     * @函数或方法说明
     * @编辑试玩任务
     * @param  array $data
     *
     * @return bool
     *
     * @author: 郭家屯
     * @since: 2020/3/20 15:55
     */
    public function editTplay($data=[])
    {
        $model = new UserTplayModel();
        $result = $model->field('quota,start_time,end_time,award,level,cash')->update($data);
        if($result !== false){
            return true;
        }
        return false;
    }

    /**
     * @函数或方法说明
     * @获取详情信息
     * @param int $id
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/3/20 15:33

     */
    public function getDetail($id=0)
    {
        $model = new UserTplayModel();
        $data = $model->where('id',$id)->find();
        return $data?$data->toArray():[];
    }

    /**
     * @函数或方法说明
     * @获取报名详情
     * @param array $map
     *
     * @return array|false|null|\PDOStatement|string|\think\Model
     *
     * @author: 郭家屯
     * @since: 2020/3/23 20:24
     */
    public function getRecordDetail($map=[])
    {
        $data = Db::table('tab_user_tplay_record')->field('status,end_time,award,level,cash')->where($map)->find();
        return $data;
    }
    /**
     * @函数或方法说明
     * @更新超时任务状态
     * @author: 郭家屯
     * @since: 2020/3/20 16:21
     */
    public function updateTplayRecord()
    {
        $model = new UserTplayRecordModel();
        //超时
        $model->where('end_time','lt',time())->where('status',0)->where('level',0)->setField('status',2);
        //完成部分
        $model->where('end_time','lt',time())->where('status',0)->where('level','gt',0)->setField('status',1);
    }

    /**
     * @函数或方法说明
     * @发放奖励
     * @param array $ids
     *
     * @author: 郭家屯
     * @since: 2020/3/23 9:27
     */
    public function grant($ids=[])
    {
        $model = new UserTplayRecordModel();
        $tplaymodel = new UserTplayModel();
        $data = $model
                ->field('id,user_id,tplay_id,level')
                ->where('status',1)
                ->where('award',0)
                ->where('cash',0)
                ->where('id','in',$ids)
                ->select()->toArray();
        if(empty($data)){
            return false;
        }
        foreach ($data as $key=>$v){
            $tplay = $tplaymodel->field('award,level,cash')->where('id',$v['tplay_id'])->where('status',1)->find();
            if(empty($tplay))continue;
            $level = explode('/',$tplay['level']);
            $award = explode('/',$tplay['award']);
            $cash = explode('/',$tplay['cash']);
            //奖励金额
            $money = 0;
            $cash_money = 0;
            foreach ($level as $kk=>$vv){
                if($v['level'] >= $vv){
                    $money += $award[$kk];
                    $cash_money += $cash[$kk];
                }
            }
            $tip['user_id'] = $v['user_id'];
            $tip['title'] = '任务成功';
            $tip['create_time'] =time();
            $tip['content'] = '您的任务奖励已发放，获得'.$money.'平台币，'.$cash_money.'元,请注意查收';
            $tip['type'] = 3; // 添加类型 3:试玩任务奖励通知
            Db::startTrans();
            try{
                $save['award'] = $money;
                $save['cash'] = $cash_money;
                $model->where('id',$v['id'])->update($save);
                if($money > 0){
                    Db::table('tab_user')->where('id',$v['user_id'])->setInc('balance',$money);
                }
                if($cash_money > 0){
                    Db::table('tab_user')->where('id',$v['user_id'])->setInc('tplay_cash',$cash_money);
                }
                Db::table('tab_tip')->insert($tip);
                //发放试玩任务奖励
                $modelUserModel = new \app\member\model\UserModel();
                $modelUserModel->auto_task_complete($v['user_id'],'try_game',1);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                continue;
            }

        }
        return true;
    }

    /**
     * @函数或方法说明
     * @报名
     * @param int $id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/3/23 14:01
     */
    public function sign($id=0,$user_id=0)
    {
        $tplay = $this->getDetail($id);
        if($tplay['status'] == 0){
            return 0;
        }
        $model = new UserTplayRecordModel();
        $map['tplay_id'] = $id;
        $map['user_id'] = $user_id;
        $map['start_time'] = ['lt',time()];
        $map['end_time'] = [['eq',0],['gt',time()],'or'];
        $record = $model->field('id')->where($map)->find();
        if($record){
            return -2;
        }
        $count = $model->field('id')->where('tplay_id',$id)->count();
        if($count >= $tplay['quota'] && $tplay['quota'] > 0){
            return -1;//名额已满
        }
        $save['user_id'] = $user_id;
        $save['user_account'] = get_user_entity($user_id,false,'account')['account'];
        $save['game_id'] = $tplay['game_id'];
        $save['game_name'] = $tplay['game_name'];
        $save['server_id'] = $tplay['server_id'];
        $save['server_name'] = $tplay['server_name'];
        $save['start_time'] = time();
        $save['end_time'] = strtotime("+{$tplay['time_out']} hour");
        $save['create_time'] = time();
        $save['tplay_id'] = $id;
        $result = $model->insert($save);
        return $result;
    }

    /**
     * @函数或方法说明
     * @提交任务
     * @param int $id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/3/23 14:01
     */
    public function complete($id=0,$user_id=0)
    {
        $user = get_user_entity($user_id,false,'age_status');
        if($user['age_status'] == 0){
            return -4;//实名认证
        }
        $tplay = $this->getDetail($id);
        if($tplay['status'] == 0){
            return 0;
        }
        $model = new UserTplayRecordModel();
        $map['tplay_id'] = $id;
        $map['user_id'] = $user_id;
        $record = $model->field('id,end_time,status')->where($map)->find();
        if(!$record){
            return 0;
        }
        $record = $record->toArray();
        if($record['status'] == 1){
            return 0;
        }
        if(time() > $record['end_time']){
            return -1;
        }
        $role = Db::table('tab_user_play_info')->field('max(role_level) as role_level')->where('game_id',$tplay['game_id'])->where('server_id',$tplay['server_id'])->where('user_id|puid',$user_id)->find();
        if(is_null($role['role_level'])){
            return -2;//没有小号
        }
        $role_level = $role['role_level'];
        $level = explode('/',$tplay['level']);
        if(reset($level) > $role_level){
            return -3;//角色等级不足
        }
        //多阶段处理状态
        if(end($level) <= $role_level){
            $save['status'] = 1;
        }
        $save['level'] = $role_level;
        $result = $model->where('id',$record['id'])->update($save);
        if($result !== false){
            return true;
        }else{
            return false;
        }

    }

}