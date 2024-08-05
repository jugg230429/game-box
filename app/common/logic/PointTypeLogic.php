<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\member\model\PointRecordModel;
use app\member\model\PointTypeModel;
use app\member\model\UserItemModel;
use app\member\model\UserModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PointTypeLogic{

    public function lists($user_id)
    {
        if(empty($user_id)) return [];
        $model = new PointTypeModel();
        $model->field('id,name,key,point,type,send_type,remark,description,time_of_day,birthday_point');
        $model->where('status','=',1);
        $modelData = $model->order('sort desc,id asc')->select()->toArray();
        $vip_upgrade = '';
        $viplevel = [];
        foreach ($modelData as $key=>$value){
            if($value['key']=='vip_upgrade'){
                $vipset = cmf_get_option('vip_set');
                $viplevel = explode(',',$vipset['vip']);
                $vip_upgrade = $value;
                unset($modelData[$key]);
            }
        }
        $tem_id = 1;
        foreach ($viplevel as $k=>$v){
            $vip_upgrade_tem = $vip_upgrade;
            $vip_upgrade_tem['level'] = $tem_id;
            $vip_upgrade_tem['name'] = 'VIP'.$tem_id.'升级奖励';
            $vip_upgrade_tem['description'] = '等级到VIP'.$tem_id.'即可，充值更多惊喜！';
            $modelData[] = $vip_upgrade_tem;
            $tem_id++;
        }

        foreach ($modelData as $key=>$value){
            $modelData[$key]['task_status'] = $this->task_status($value,$user_id);
            switch ($value['key']){
                case 'vip_upgrade';
                    $modelData[$key]['user_get_point'] = ($value['point']+(($value['level']-1)*$value['time_of_day']));
                    break;
                case 'sign_in';
                    $modelData[$key]['user_get_point'] = (new PointLogic())->sign_detail($user_id)['totay_point'];
                    // $modelData[$key]['task_status'] = (new PointLogic())->sign_detail($user_id)['today_signed22'];
                    break;
                case 'pay_award';
                    $nowDay = date('md');
                    $birth = substr(get_user_entity($user_id,false,'idcard')['idcard'], 10, 4);
                    if($nowDay==$birth){
                        $modelData[$key]['user_get_point'] = ($value['birthday_point']>0?$value['birthday_point']:1)*$value['point'];
                    }else{
                        $modelData[$key]['user_get_point'] = $value['point'];
                    }
                    break;
                default:
                    $modelData[$key]['user_get_point'] = $value['point'];
                    break;
            }
//            $modelData[$key]['user_get_point'] = $value['key']=='vip_upgrade'?($value['point']+(($value['level']-1)*$value['time_of_day'])):($value['key']=='sign_in'?(new PointLogic())->sign_detail($user_id)['totay_point']:($value['key']=='pay_award'?0:1));

        }
        // var_dump($modelData);
        // var_dump($modelData[$key]['task_status']);
        // exit;
        return $modelData;
    }
    // 获取任务完成状态
    public function task_status($task,$user_id)
    {
        if(empty($user_id)||empty($task)) return false;
        $model = new UserItemModel();
        $userData = $model
            ->field('tab_user_item.*,u.vip_level')
            ->join(['tab_user'=>'u'],'u.id = tab_user_item.user_id')
            ->where('user_id','=',$user_id)
            ->find();
        if(empty($userData)){
            return false;
        }
        $userData = $userData->toArray();
        if($task['key']=='vip_upgrade'){
            $vip_upgrade = json_decode($userData['vip_upgrade'],true);
            if(empty($vip_upgrade[$task['level']])){
                $userVip = $userData['vip_level'];
                if($userVip>=$task['level']){
                    return 2;
                }else{
                    return 1;
                }
            }else{
                return $vip_upgrade[$task['level']];
            }
        }else{
            return $userData[$task['key']];
        }
    }
    public function receive_award($task_key,$user_id,$extend)
    {
        if(empty($user_id)) return [];
        $model = new PointTypeModel();
        $model->field('id,name,key,point,type,send_type,remark,description,time_of_day,birthday_point');
        $model->where('status','=',1);
        $model->where('key','=',$task_key);
        $model->where('send_type','=',0);//一次性领取的  useritem表对应字段
        $modelData = $model->find();
        if(empty($modelData)){
            return -1;//关闭奖励
        }
        if($task_key=='vip_upgrade'){
            $modelData->level = $extend;
        }
        $res = $this->task_status($modelData->toArray(),$user_id);
        if($res===false){
            return -2;//数据错误
        }
        if($res == 1){
            return -3;//任务未完成
        }
        if($res == 3){
            return -4;//奖励已领取
        }
        Db::startTrans();
        $modelItem = new UserItemModel();
        $modelItem->where('user_id','=',$user_id);
        $modelItemData = $modelItem->find();
        if($task_key=='vip_upgrade'){
            $vip_upgrade = json_decode($modelItemData->vip_upgrade,true);
            if(empty($vip_upgrade)){
                $vip_upgrade = [$extend=>3];
            }else{
                $vip_upgrade[$extend] = 3;
            }
            $modelItemData->vip_upgrade = json_encode($vip_upgrade);
            $modelItemData->save();
        }else{
            $modelItemData->$task_key = 3;
            $modelItemData->save();
        }

        $modelUser = new UserModel();
        if($task_key=='vip_upgrade'){
            $point = $modelData->point+(($extend-1)*$modelData->time_of_day);
        }else{
            $point = $modelData->point;
        }
        $modelUser->where('id','=',$user_id)->setInc('point',$point);

        $modelPointRecord = new PointRecordModel();
        $modelPointRecord->type_id = $modelData->id;
        $modelPointRecord->type_name = $modelData->name;
        $modelPointRecord->user_id = $user_id;
        $modelPointRecord->user_account = get_user_name($user_id);
        $modelPointRecord->point = $point;
        $modelPointRecord->vip = $task_key=='vip_upgrade'?$extend:0;
        $modelPointRecord->save();
        Db::commit();
        return ['point'=>$point];
    }

    public function user_vip($user_id)
    {
        $userConfig = cmf_get_option('vip_set');
        $vipLevel = explode(',',$userConfig['vip']);
        if(empty($vipLevel)){
            $this->error('vip功能暂时关闭');
        }
        $data[0] = $vipLevel[0]-1<1?$vipLevel[0]-1:'0-'.($vipLevel[0]-1);
        $userData = get_user_entity($user_id,false,'vip_level,cumulative');
        $model = new PointTypeModel();
        $model->field('id,name,key,point,type,send_type,remark,description,time_of_day,birthday_point');
        $model->where('status','=',1);
        $model->where('key','=','vip_upgrade');
        $modelData = $model->find();
        $vip_upgrade = [];
        if(empty($modelData)){
            $vip_upgrade = [];//关闭
        }else{
            $res = (new PointTypeLogic())->lists($user_id);
            foreach ($res as $k=>$v){
                if($v['key']!='vip_upgrade'){
                    unset($res[$k]);
                    continue;
                }
                $vip_upgrade[$v['level']] = $v;
            }
        }
        foreach ($vipLevel as $key=>$value){
            $data[] = $key==(count($vipLevel)-1)?$value.'以上':$value.'-'.($vipLevel[$key+1]-1);
        }
        $next_pay = false;
        foreach ($vipLevel as $key=>$value){
            if($userData['cumulative']<$value&&$userData['vip_level']<($key+1)){
                $next_pay = $value-$userData['cumulative'];
                break;
            }
        }
        return ['vip_upgrade'=>$vip_upgrade,'next_pay'=>null_to_0($next_pay),'data'=>$data];
    }

    /**
     * @函数或方法说明
     * @更新任务状态
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/9/16 11:30
     */
    public function update_task($user_id=0)
    {
        if(empty($user_id)){
            return false;
        }
        $user = get_user_entity($user_id,false,'id,account,nickname,email,phone,receive_address,idcard,cumulative,fgame_id,qq,register_time,head_img,wechat_cdkey');
        $itemmodel = new UserItemModel();
        $item = $itemmodel->where('user_id',$user_id)->find();
        //获取关注公众号任务正确cdkey
        $mPointType = new PointTypeModel();
        $trueCdkey = $mPointType -> where(['key' => 'subscribe_wechat']) -> value('cdkey');
        if(empty($trueCdkey)){
            $trueCdkey = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx**********xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // 防止未配置cdkey用户直接完成任务
        }
        if($item){
            $save['bind_phone'] = $this->get_task_status($item['bind_phone'],$user['phone']);
            $save['improve_address'] = $this->get_task_status($item['improve_address'],$user['receive_address']);
            $save['bind_email'] = $this->get_task_status($item['bind_email'],$user['email']);
            $save['auth_idcard'] = $this->get_task_status($item['auth_idcard'],$user['idcard']);
            $save['play_game'] = $this->get_task_status($item['play_game'],$user['fgame_id']);
            $save['first_pay'] = $this->get_task_status($item['first_pay'],$user['cumulative']);
            $save['change_nickname'] = $this->get_task_status($item['change_nickname'],$user['nickname'] !== $user['account']);
            $save['bind_qq'] = $this->get_task_status($item['bind_qq'],$user['qq']);
            $save['subscribe_wechat'] = $this->get_task_status($item['subscribe_wechat'],$user['wechat_cdkey']==$trueCdkey);
            if(strpos($user['head_img'],'site/') !== false || $user['head_img'] == 'sdk/logoo.png'){
                $change_head_status = 0;
            }else{
                $change_head_status = 1;
            }
            $save['change_headimg'] = $this->get_task_status($item['change_headimg'],$change_head_status);
            $save['update_time'] = time();
            $itemmodel->where('user_id',$user_id)->update($save);
        }else{
            $save['user_id'] = $user_id;
            $save['register_award'] = 2;
            $save['bind_phone'] = $user['phone']?2:1;
            $save['improve_address'] = $user['receive_address']?2:1;
            $save['bind_email'] = $user['email']?2:1;
            $save['auth_idcard'] = $user['idcard']?2:1;
            $save['play_game'] = $user['fgame_id']?2:1;
            $save['first_pay'] = $user['cumulative']>0?2:1;
            $save['change_nickname'] = $user['nickname'] == $user['account'] ? 1:2;
            $save['bind_qq'] = $user['qq']?2:1;
            $save['subscribe_wechat'] = $user['wechat_cdkey'] == $trueCdkey ? 2 : 1;
            if(strpos($user['head_img'],'site/') !== false || $user['head_img'] == 'sdk/logoo.png'){
                $change_head_status = 0;
            }else{
                $change_head_status = 1;
            }
            $save['change_headimg'] = $change_head_status == 1?2:1;
            $save['create_time'] = time();
            $save['update_time'] = time();
            $itemmodel->insert($save);
        }
    }

    /**
     * @函数或方法说明
     * @获取任务状态
     * @author: 郭家屯
     * @since: 2020/9/16 13:43
     */
    protected function get_task_status($task_status,$value)
    {
        // if($task_status <2 && $value){
        //     return 2;
        // }
        // 改--------------wjd
        if($task_status <2 && $value>0){
            return 2;
        }
        return $task_status;
    }
}
