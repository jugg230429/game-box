<?php

namespace app\member\model;

use app\common\logic\InvitationLogic;
use app\common\task\Task;
use app\game\logic\InterflowLogic;
use app\member\model\UserItemModel as modelUserItem;
use app\recharge\model\SpendModel;
use app\webplatform\logic\UserLogic;
use cmf\org\RedisSDK\RedisController as Redis;
use think\Db;
use app\member\model\PointTypeModel as modelPointType;
use app\member\model\PointRecordModel as modelPointRecord;
use think\Exception;
use think\helper\Time;
use think\Model;
use app\common\logic\AntiaddictionLogic;
use app\common\task\HandleUserStageTask;

class UserModel extends Model
{

    protected $table = 'tab_user';

    protected $autoWriteTimestamp = false;

    protected $userPoint = 0;
    /**
     * [登录接口]
     * @param $data
     * @param int $type
     * @author 郭家屯[gjt]
     */
    public function login($data,$type=0,$model='')
    {
        // 第三方平台用户登录
        if($data['game_id'] >0 && is_third_platform($data['promote_id'])){
            $UserLogic = new UserLogic($data['promote_id']);
            $res = $UserLogic->user_login($data);
            if(!$res){
                return 1005;
            }
            $user = $this->where('id',$res)->find();
            $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])), 1);//记录用户token
            $this->user_login_record($user, $data,$model);
            $this->updateLogin($user); //更新用户登录信息
        }else{
            $user = $this->where('account', $data['account'])->where('puid',0)->find();
            if (empty($user)) {
                return 1005;
            }
            $user = $user->toArray();
            if (!xigu_compare_password($data['password'],$user['password']) && $type == 0) {
                return 1006;
            }
            if ($user['lock_status'] == 0) {
                return 1007;
            }
            if ($data['type'] == '4' && !empty($data['device_name'])) {
                $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'], 'device_name' => get_real_devices_name($data['device_name']))), 1);//记录用户token

                $redis = new Redis(['host' => '127.0.0.1', 'port' => 6379], []);
                $redis->set('app_token_'.$user["id"],$user['token']);

            } else {
                $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])), 1);//记录用户token
            }
            $this->user_login_record($user, $data,$model);

            $user['login_equipment_num'] = $data['equipment_num'] ?? '';

            $this->updateLogin($user); //更新用户登录信息
            //上传国家实名认证

            $systemSet = cmf_get_option('admin_set');
            if (empty($systemSet['task_mode'])) {
                if ($data['game_id'] > 0 && get_game_age_type($data['game_id']) == 2) {
                    if (!empty($user['idcard']) && !empty($user['real_name'])) {
                        $map['user_id'] = $user['id'];
                        $map['game_id'] = $data['game_id'];
                        $user_age = Db ::table('tab_user_age_record') -> field('id') -> where($map) -> find();
                        if (empty($user_age)) {
                            $logic = new AntiaddictionLogic($data['game_id']);
                            $res = $logic -> checkUser($user['real_name'], $user['idcard'], $user["id"], $data['game_id']);
                        }
                    }
                }
            } else {
                $task = new Task();
                $taskParam = [
                    'title' => '上传国家实名认证',
                    'class_name' => '\app\common\task\UserTask',
                    'function_name' => 'taskUpRealName',
                    'param' => ['data' => $data, 'user' => $user],
                    'remark' => '',
                ];
                $taskRes = $task -> create($taskParam);
            }
        }
        return $user;
    }

    /**
     * [第三方登录接口]
     * @param $data
     * @param int $type
     * @author 郭家屯[gjt]
     */
    public function auth_login($data)
    {
        $user = $this->where('id', $data['id'])->find()->toArray();
        $this->user_login_record($user, $data);
        $user['openid'] = $data['openid'];
        $this->updateLogin($user); //更新用户登录信息
    }

    /**
     * [第三方登录接口]
     * @param $data
     * @param int $type
     * @author 郭家屯[gjt]
     */
    public function oauth_login($data,$model='')
    {
        if($data['game_id'] >0 && is_third_platform($data['promote_id'])){
            $UserLogic = new UserLogic($data['promote_id']);
            $res = $UserLogic->user_login($data);
            if(!$res){
                return 1005;
            }
            $user = $this->where('id',$res)->find();
        }else{
            $user = $this->where('account', $data['account'])->find();
            if (empty($user)) {
                return 1005;
            }
            $user = $user->toArray();
            if ($user['lock_status'] == 0) {
                return 1007;
            }
        }
        $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])),1);//记录用户token
        $redis = new Redis(['host' => '127.0.0.1', 'port' => 6379], []);
        $redis->set('app_token_'.$user["id"],$user['token']);
        $this->user_login_record($user, $data , $model);
        $this->updateLogin($user); //更新用户登录信息
        return $user;
    }

    /**
     * [用户注册]
     * @param $data
     * @return array|int|string
     * @author 郭家屯[gjt]
     */
    public function register($data,$model='')
    {
        if($data['game_id'] >0 && is_third_platform($data['promote_id'])){
            $UserLogic = new UserLogic($data['promote_id']);
            $res = $UserLogic->user_register($data);
            if(!$res){
                return -1;
            }
            $user = $this->where('id',$res)->find();
            $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])),1);
            $data['is_new'] = 1;
            $this->user_login_record($user, $data,$model);
            $this->updateLogin($user);
            return $user;
        }else{
            if ($data['promote_id']) {
                $promote = get_promote_entity($data['promote_id'],'id,account,parent_id,parent_name,pattern,promote_level,game_ids');
                if ($promote['pattern'] == 1) {
                    $is_check = 1;
                }
            }
            $save = array(
                'account' => $data['account'],
                'password' => cmf_password($data['password']),
                'nickname' => $data['nickname']?:$data['account'],
                'wx_nickname' =>$data['wx_nickname']?:'',
                'phone' => empty($data['phone']) ? '' : $data['phone'],
                'email' => empty($data['email']) ? '' : $data['email'],
                'unionid' => empty($data['unionid']) ? '' : $data['unionid'],
                'openid' => empty($data['openid']) ? '' : $data['openid'],
                'promote_id' => empty($data['promote_id']) ? 0 : $data['promote_id'],
                'head_img' => empty($data['head_img']) ? (cmf_get_option('admin_set')['web_set_avatar']?:'sdk/logoo.png') : $data['head_img'],
                'promote_account' => $promote['account'] ? : "官方渠道",
                'register_way' => $data['register_way'],
                'register_type' => $data['register_type'],
                'register_ip' => get_client_ip(),
                'parent_id' => empty($promote['parent_id']) ? 0 : $promote['parent_id'],
                'parent_name' => $promote['parent_name']?:'',
                'fgame_id' => $data['game_id']?:0,
                'fgame_name' => $data['game_name']?:'',
                'register_time' => time(),
                'real_name' => empty($data['real_name']) ? "" : $data['real_name'],
                'idcard' => empty($data['idcard']) ? '' : $data['idcard'],
                'age_status' => empty($data['age_status']) ? '' : $data['age_status'],
                'sex' => empty($data['sex']) ? '' : $data['sex'],
                'login_time' => time(),
                'login_ip' => get_client_ip(),
                'is_check' => empty($is_check) ? 0 : 1,
                'equipment_num' => empty($data['equipment_num']) ? '' : $data['equipment_num'],
                'login_equipment_num' => empty($data['equipment_num']) ? '' : $data['equipment_num'], // 最后一次登录的设备码
                'device_name'=> get_real_devices_name($data['device_name']) ? : '',
                'invitation_id' => $data['invitation_id'] ? : 0,
                //新增sdk
                'is_hot_sdk' => isset($data['is_hot_sdk']) ? $data['is_hot_sdk'] : 0,
                'version_code' => isset($data['version_code']) ? $data['version_code'] : 0,
                'last_version_code' => isset($data['last_version_code']) ? $data['last_version_code'] : 0
            );
            if($data['id']){
                $save['id'] = $data['id'];
            }
            //验证推广员推广是否有效
            if($save['promote_id'] > 0 && $data['game_id'] > 0){
                if($promote['game_ids'] && in_array($data['game_id'],explode(',',$promote['game_ids']))){
                    $save['promote_id'] = 0;
                    $save['promote_account'] = '';
                    $save['parent_id'] = 0;
                    $save['parent_name'] = '';
                }else{
                    $apply = Db::table('tab_promote_apply')->field('id')->where('promote_id',$save['promote_id'])->where('game_id',$save['fgame_id'])->where('status',1)->find();
                    if(!$apply){
                        $save['promote_id'] = 0;
                        $save['promote_account'] = '';
                        $save['parent_id'] = 0;
                        $save['parent_name'] = '';
                    }
                }
            }
            Db::startTrans();
            if($data['id']){
                $this->field(true)->insert($save);
                $uid = $data['id'];
            }else{
                $uid = $this->field(true)->insertGetId($save);
            }
            if ($uid) {
                $user = $save;
                $user['id'] = $uid;
                $save['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])),1);//记录用户token

                $redis = new Redis(['host' => '127.0.0.1', 'port' => 6379], []);
                $redis->set('app_token_'.$user["id"],$save['token']);

                $save['id'] = $user['id'];
                $data['is_new'] = 1;
                $this->user_login_record($user, $data,$model);
                $this->updateLogin($save);
                if($data['invitation_id']){//邀请
                    $this->send_coupon($user['id'],$data['invitation_id']);
                }
                if ($data['promote_id']) {//cpa
                    if ($promote['pattern'] == 1 && $data['game_id']) {

                        $systemSet = cmf_get_option('admin_set');
                        if(empty($systemSet['task_mode'])){
                            $this -> set_promote_radio($user, $data, $promote);
                        }else{
                            $task = new Task();
                            $taskParam = [
                                'title' => '设置渠道结算比例',
                                'class_name' => '\app\common\task\UserTask',
                                'function_name' => 'taskSetPromoteRadio',
                                'param' => ['user' => $user, 'data' => $data, 'promote' => $promote],
                                'remark' => '',
                            ];
                            $taskRes = $task -> create($taskParam);
                        }
                    }
                }
                $user['token'] = $save['token'];
                Db::commit();
                return $user;
            } else {
                Db::rollback();
                return -1;
            }
        }
    }

    public function task_complete($user_id,$key,$value){
        if(!$value){
            return false;
        }
        $modelUserItem = new modelUserItem();
        $modelUserItemData = $modelUserItem->field($key)->where('user_id','=',$user_id)->find();
        $modelUserData = $this->field('id,account,point')->where('id','=',$user_id)->where('puid','=','0')->find();
        if(empty($modelUserItemData)||$modelUserItemData[$key] > 1){
            return false;
        }
        $task = (new modelPointType())->where('key','=',$key)->where('status','=',1)->column('id,name as type_name,key,point','key');
        if(empty($task)){
            $status = 0;
        }else{
            $this->userPoint = $modelUserData->point;
            $status = $this->send_point_award($task,$key,$value);
            $modelUserData->point = $this->userPoint;
            $modelUserData->save();
        }
        $modelUserItemData->$key = $status;
        $modelUserItemData->save();
    }

    public function auto_task_complete($user_id,$key,$value){
        if(!$value){
            return false;
        }
        $modelUserData = $this->field('id,account,point,idcard')->where('id','=',$user_id)->where('puid','=','0')->find();
        $task = (new modelPointType())->where('key','=',$key)->where('status','=',1)->column('id,name as type_name,key,point,birthday_point','key');
        if(empty($task)||empty($modelUserData)){
            $status = false;
        }else{
            $this->userPoint = $modelUserData->point;
            $this->auto_send_point_award($task,$key,$value,$modelUserData);
            $modelUserData->point = $this->userPoint;
            $modelUserData->save();
        }
    }

    private function send_point_award($task,$key,$value)
    {
        if(!$value){//无值
            return 1;//未完成
        }
        if(empty($task[$key])&&$value){
            return 0;//关闭时完成
        }
        if(!empty($task[$key])&&$value){
            return 2;//已完成
        }
    }

    private function auto_send_point_award($task,$key,$value,$userData)
    {
        $point = $task[$key]['point'];
        if($key=='pay_award'){
            $nowDay = date('md');
            $birth = substr($userData->idcard, 10, 4);
            if($nowDay==$birth && $task[$key]['birthday_point']){
                $point = $task[$key]['birthday_point'];
            }
            $addpoint = ($point>0?$point:0)*$value;
            $this->userPoint = $this->userPoint+$addpoint;
        }else{
            $addpoint = ($point>0?$point:0);
            $this->userPoint = $this->userPoint+$addpoint;
        }
        $modelPointRecord = new modelPointRecord();
        $modelPointRecord->type_id = $task[$key]['id'];
        $modelPointRecord->type_name = $task[$key]['type_name'];
        $modelPointRecord->user_id = $userData->id;
        $modelPointRecord->user_account = $userData->account;
        $modelPointRecord->point = $addpoint;
        $modelPointRecord->save();
    }
    /**
     * @函数或方法说明
     * @发放邀请奖励
     * @author: 郭家屯
     * @since: 2020/2/24 9:18
     */
    public function send_coupon($user_id=0,$invitation_id=0)
    {
        $logic = new InvitationLogic();
        $result = $logic->add_record($user_id,$invitation_id);
        if($result){
            $logic->send_coupon($user_id,$invitation_id);
        }
    }

    /**
     * [更新用户最后登录信息]
     * @param $id
     * @author 郭家屯[gjt]
     */
    public function updateLogin($user)
    {
        $save['login_time'] = time();
        $save['login_ip'] = get_client_ip();
        $save['token'] = $user['token'];
        $save['login_equipment_num'] = $user['login_equipment_num'];
        if($user['openid']){
            $save['openid'] = $user['openid'];
        }
        $id = $user['id'];
        $this->where('id', $id)->update($save);
        // 用户阶段更改
        try{
            $systemSet = cmf_get_option('admin_set');

            if(empty($systemSet['task_mode'])){
                $a = (new HandleUserStageTask())->changeUserStage1(['user_id'=>$user['id']]);
            }else{
                (new HandleUserStageTask())->saveOperation('',$user['id'], 0);
            }

        }catch(\Exception $e){

        }
    }
    /**
     * [修改密码]
     * @param $id
     * @param $password
     * @author 郭家屯[gjt]
     */
    public function updatePassword($id, $password)
    {
        $result = $this->where('id', $id)->setField('password', cmf_password($password));
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 方法 updateUserPassword
     *
     * @descript 修改密码
     *
     * @param $id
     * @param string $password
     * @param string $old
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 15:32
     */
    public function updateUserPassword($id, $password, $old='') {
        //try {
            if (empty($password) || empty($id)) {
                return 1003;
            }
            $user = $this -> where('id', $id) -> find();
            if (empty($user)) {
                return 1003;
            }
            if (!empty($old) && !xigu_compare_password($old,$user['password'])) {
                return 1039;
            }
            $save['token'] = think_encrypt(json_encode(array('uid' => $id, 'password' => cmf_password($password))),1);
            $save['password'] = cmf_password($password);
            $res = $this->where('id', $id)->update($save);
            if ($res) {
                return 204;
            } else {
                return 1037;
            }
        //} catch (\Exception $e) {
        //    return 1037;
       // }
    }

    /**
     * [修改用户信息]
     * @param $id
     * @param $data
     * @return bool
     * @author 郭家屯[gjt]
     */
    public function update_user_info($id, $data)
    {
        $result = $this->where('id', $id)->update($data);
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 方法 updateUserAccountAndPassword
     *
     * @descript 更新用户账号和密码
     *
     * @param $id
     * @param $param
     * @param bool $flag
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 10:46
     */
    public function updateUserAccountAndPassword($id, $param, $flag=true)
    {
        if (empty($param['account'])) {
            return 1009;
        }
        if (empty($param['password'])) {
            return 1010;
        }
        try {
            $data = $this
                ->field('id,account,password')
                ->where(['id', $id])
                ->find();
        } catch (Exception $e) {
            return 1003;
        }
        if (empty($data)) {
            return 1003;
        }
        if ($flag && $data['account'] !== $param['old_account']) {
            return 1005;
        }
        if ($flag && !xigu_compare_password($data['password'],$param['old_password'])) {
            return 1006;
        }
        $result = $this->where('id', $param['user_id'])->update([
            'account'=>$param['account'],
            'password' => cmf_password($param['password'])
        ]);
        if ($result !== false) {
            return 200;
        } else {
            return 1037;
        }
    }

    /**
     * [登录记录]
     * @param $user
     * @author 郭家屯[gjt]
     */
    public function user_login_record($user, $data,$model='')
    {
        $mUserLoginRecord = new UserLoginRecordModel();
        $info = [];
        if($model != 'sdk'){
            $where['user_id'] = $user['id'];
            $where['login_time'] = ['egt',time()-300];
            if($data['game_id']){
                $where['game_id'] = $data['game_id'];
            }
            $record = $mUserLoginRecord->field('id')->where($where)->find();
            if($record){
                //5分钟内只记录一次登录记录
                return false;
            }
        }
        //保存登录记录表
        $save = array(
            'user_id' => $user['id'],
            'user_account' => $user['account'],
            'type' => $data['type'],
            'promote_id' => $user['promote_id'],
            'login_time' => time(),
            'login_ip' => get_client_ip(),
            'game_id' => $data['game_id'] ? $data['game_id'] : 0,
            'game_name' => $data['game_name']?:($data['game_id'] ? get_game_name($data['game_id']) : ''),
            'puid' =>$user['puid']?:0,
        );
        $login_id = $mUserLoginRecord->insertGetId($save);
        try {
            $mUserLoginRecordMongodb = new UserLoginRecordMongodbModel();
            $mUserLoginRecordMongodb -> insertGetId($save);
        }catch (\Exception $e){

        }
        //插入每天登录记录表
        if ($login_id&&$user['puid']==0) {//小号不记录
            $map['user_id'] = $user['id'];
            $map['login_time'] = total(1, 1);
            if ($data['game_id']) {
                $map['game_id'] = $data['game_id'];
            }
            $login_record = Db::table('tab_user_day_login')->field('id')->where($map)->find();
            if (empty($login_record)) {
                $day_login['login_record_id'] = $login_id;
                $day_login['user_id'] = $user['id'];
                $day_login['promote_id'] = $user['promote_id'];
                $day_login['game_id'] = $data['game_id'] ? $data['game_id'] : 0;
                $day_login['is_new'] = empty($data['is_new']) ? 0 : 1;
                $day_login['login_time'] = time();
                Db::table('tab_user_day_login')->insert($day_login);
            }
        }
        if ($user['fgame_id'] == 0 && $data['game_id']&&$user['puid']==0) {//第一进入游戏   小号不参与结算
            $res = true;
            if ($user['promote_id'] ) {
                $promote = get_promote_entity($data['promote_id'],'id,account,parent_id,parent_name,pattern,promote_level,game_ids');
                if($promote['pattern'] == 1){
                    //设置渠道结算比例
                    $systemSet = cmf_get_option('admin_set');
                    if(empty($systemSet['task_mode'])){
                        $res = $this->set_promote_radio($user, $data,$promote);
                    }else{
                        $task = new Task();
                        $taskParam = [
                                'title' => '设置渠道结算比例',
                                'class_name' => '\app\common\task\UserTask',
                                'function_name' => 'taskSetPromoteRadio',
                                'param' => ['user' => $user, 'data' => $data,'promote'=>$promote],
                                'remark' => '',
                        ];
                        $taskRes = $task -> create($taskParam);
                    }
                }
            }
            if(!$res){
                return false;
            }
            $info['fgame_id'] = $data['game_id'];
            $info['fgame_name'] = $data['game_name'];
            $this->where('id', $user['id'])->update($info);
        }
        return $info;
    }

    /**
     * [绑币余额列表]
     * @param string $user_id
     * @param int $p
     * @param int $row
     * @author 郭家屯[gjt]
     */
    public function getUserBindCoin($user_id = '', $p = 1, $row = 10)
    {
        $lists = Db::table('tab_user_play')->alias('up')
            ->field('g.game_name,g.icon,up.bind_balance')
            ->join(['tab_game' => 'g'], 'up.game_id=g.id', 'left')
            ->where('up.user_id',$user_id)
            ->where('up.bind_balance','gt',0)
            ->page($p, $row)
            ->select()
            ->each(function ($item, $key) {
                $item['icon'] = cmf_get_image_url($item['icon']);
                return $item;
            })->toArray();
        return $lists;
    }

    /**
     * 检测用户是否存在或被禁用
     *
     * @param $account
     *
     * @return array|int
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\27 0027 15:36
     */
    public function checkAccount($account)
    {
        $user = $this->where('account', $account)->find();
        if (empty($user)) {
            return 1005;
        }
        $user = $user->toArray();
        if ($user['lock_status'] == 0) {
            return 1007;
        }

        return $user;

    }

    /**
     * @函数或方法说明
     * @生成注册结算单
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2019/6/26 14:50
     */
    public function set_promote_radio($user = [], $data = [],$promote_data)
    {
        $top_promote_id = get_top_promote_id($user['promote_id']);//记录顶级渠道 一级即自己
        $promote_data['top_promote_id'] = $top_promote_id;//兼容老客户
        $settment = [
            'promote_id' => $user['promote_id'],
            'promote_account' => $user['promote_account'],
            'parent_id' => $user['parent_id'] ? $user['parent_id'] : $user['promote_id'],
            'parent_name' => $user['parent_name'] ? $user['parent_name'] : $user['promote_account'],
            'top_promote_id' => $top_promote_id,//管理后台结算一级使用
            'game_id' => $data['game_id'],
            'game_name' => $data['game_name'],
            'pattern' => 1,
            'sum_reg' => 1,
            'user_id' => $user['id'],
            'user_account' => $user['account'],
            'register_type' => $user['register_type'],
            'create_time' => time()
        ];
        for ($i=1;$i<=$promote_data['promote_level'];$i++){
            $game = [];
            $j = $i==1?'':$i;
            if($i == $promote_data['promote_level']){
                $promote_id = $user['promote_id'];
            }else{
                if($i==1){//当前渠道不是超过一级会进入判断
                    $promote_id = $top_promote_id;
                }else{//超过二级会进入 支持三级以上渠道
                    $infotmp = Db::table('tab_promote')->field('id')->where(['top_promote_id'=>$top_promote_id,'promote_level'=>$i])->select()->toArray();
                    foreach ($infotmp as $k=>$v){
                        $infotmp =  Db::table('tab_promote')->field('id')->where(['parent_id'=>$v['id'],'id'=>$user['promote_id']])->find();
                        if(!empty($infotmp)){
                            $promote_id = $v['id'];
                            unset($infotmp);
                            break;
                        }
                    }
                }
            }
            $new_ratio = 'ratio'.$j;
            $new_money = 'money'.$j;
            $new_sum_money = 'sum_money'.$j;
            $apply = Db::table('tab_promote_apply')
                    ->field('id,promote_money,promote_ratio')
                    ->where('game_id',$data['game_id'])
                    ->where('promote_id',$promote_id)
                    ->where('status',1)
                    ->find();
//            $game = Db::table('tab_game')->alias('g')
//            ->field('g.game_name,IF(promote_money>0,promote_money,money) as money,IF(promote_ratio>0,promote_ratio,ratio) as ratio')
//            ->join(['tab_promote_apply' => 'a'], 'g.id=a.game_id and promote_id=' . $promote_id, 'left')
//            ->where('g.id', $data['game_id'])
//            ->find();
            $settment[$new_ratio] = $apply['promote_ratio']?:0;
            $settment[$new_money] = $apply['promote_money']?:0;
            $settment[$new_sum_money] = $apply['promote_money']?:0;
        }
        Db::startTrans();
        try {
            Db::table('tab_promote_settlement')->insert($settment);
            $this->where('id', $user['id'])->setField('is_check', 0);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /*
     * 小号注册
     */
    public function register_small($puid, $account,$nickname,$register_way = 1, $register_type = 1, $promote_id = 0, $promote_account = "", $game_id = "", $game_name = "", $sdk_version = "")
    {
        $data = array(
            'account' => $account,
            'nickname' => $nickname,
            'head_img' => empty($data['head_img']) ? "http://" . cmf_get_option('admin_set')['web_site'] . '/upload/sdk/logoo.png' : $data['head_img'],
            'promote_id' => $promote_id,
            'promote_account' => $promote_account,
            'register_way' => $register_way,
            'register_type' => $register_type,
            'register_ip' => get_client_ip(),
            'parent_id' => get_fu_id($promote_id),
            'parent_name' => get_parent_name($promote_id),
            'fgame_id' => $game_id,
            'register_time' => time(),
            'fgame_name' => $game_name,
            'puid' => $puid,
            'is_check' => empty($is_check) ? 0 : 1,
        );
        /* 添加用户 */
        $uid = $this->insertGetId($data);
        if ($uid) {
            return $uid;
        }else{
            return -1;
        }
    }

    /**
     * 小号进入游戏
     * @param array $map
     * @param $account
     * @param $game_id
     * @param $game_name
     * @param $sdk_version
     * @param int $type
     * @param bool $islog
     * @return array|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author yyh
     */
    public function get_enter_game_info($map = array(), $account, $game_id, $game_name, $sdk_version, $type = 4, $islog = true)
    {
        switch ($type) {
            case 1:
                {
                    $map['account'] = $account;
                };
                break;
            case 2:
                {
                    $map['email'] = $account;
                };
                break;
            case 3:
                {
                    $map['phone'] = $account;
                };
                break;
            case 4:
                {
                    $map['id'] = $account;
                };
                break;
        }
        /* 获取用户数据 */
        $user = $this ->field('id,account,nickname,password,fgame_id,lock_status,promote_id,puid,token')-> where($map) -> find();
        $user = empty($user)?'':$user->toArray();
        if (is_array($user) && $user['lock_status']) {
            if($user['puid'] != 0){
                $user['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])),1);//记录用户token
            }
            if ($islog) {
                $data['id'] = $user['id'];
                $data['game_id'] = $game_id;
                $data['type'] = 1;
                $this->user_login_record($user, $data);
            }
            $this->updateLogin($user); //更新用户登录信息
            return array('small_id' => $user['id'], 'nickname' => $user['nickname'], 'token' => $user['token']); //登录成功，返回用户ID
        } else {
            return - 1; //用户不存在或被禁用
        }
    }

    /**
     * 判断id是否是某一用户的小号
     * @param $puid
     * @param $small_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function is_user_small($puid,$small_id)
    {
        if($small_id==''){
            return  false;
        }
        if ($puid == $small_id) {
            return true;
        }
        $data = $this->field('id')->where(['puid'=>$puid,'id'=>$small_id])->find();
        if(empty($data)){
            return false;
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @获取小号列表
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/7 20:14
     */
    public function get_trumpet_list($user_id=0)
    {
        $data = $this->alias('u')
                ->field('u.id,account,nickname,cumulative,fgame_id,fgame_name,g.icon')
                ->join(['tab_game'=>'g'],'g.id=u.fgame_id','left')
                ->where('puid',$user_id)
                ->select()->toArray();
        if(!$data)return[];
        $trumpet = [];
        foreach ($data as $key=>$v){
            $trumpet[$v['fgame_id']][] = $v;
        }
        return $trumpet;
    }

    /**
     * @函数或方法说明
     * @获取新小号列表
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/7 20:14
     */
    public function get_new_trumpet_list($user_id=0)
    {
        //正常状态小号
        $data = $this->alias('u')
                ->field('u.id,u.account,u.nickname,cumulative,fgame_id,fgame_name,g.icon,0 as sell_status')
                ->join(['tab_game'=>'g'],'g.id=u.fgame_id','left')
                ->where('u.puid',$user_id)
                ->select()->toArray();
        //出售中的小号
        $sell = Db::table('tab_user_transaction')
                ->alias('t')
                ->field('small_id as id,u.account,u.nickname,u.cumulative,u.fgame_id,u.fgame_name,g.icon,1 as sell_status')
                ->join(['tab_user'=>'u'],'u.id=t.small_id','left')
                ->join(['tab_game'=>'g'],'g.id=u.fgame_id','left')
                ->where('user_id',$user_id)
                ->where('t.status',1)
                ->select()->toArray();
        if(empty($data) && empty($sell)){
            return [];
        }elseif(empty($data)){
            $data = $sell;
        }elseif($sell){
            $data = array_merge($data,$sell);
        }
        $trumpet = [];
        foreach ($data as $key=>$v){

            //判断是否为互通游戏
            $lInterflow = new InterflowLogic();
            $game_ids = $lInterflow -> getInterflowGameIds(['game_id' => $v['fgame_id']]);
            if(empty($game_ids)){
                $game_ids = [$v['fgame_id']];
            }
            $role = Db::table('tab_user_play_info')
                    ->field('server_name,role_name,update_time')
                    ->where('user_id',$v['id'])
                    ->where('game_id','in',$game_ids)
                    ->select()->toArray();
            $v['role'] = $role;
            $trumpet[$v['fgame_id']][] = $v;
        }
        return $trumpet;
    }

    /**
     * @函数或方法说明
     * @获取出售小号列表
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/7 20:14
     */
    public function get_sell_small_list($user_id=0)
    {
        $data = $this->alias('u')
                ->field('u.id,u.account,u.nickname,u.cumulative,fgame_id,fgame_name,g.icon')
                ->join(['tab_game'=>'g'],'g.id=u.fgame_id','left')
                ->join(['tab_user_play_info'=>'i'],'u.id=i.user_id','left')
                ->where('i.puid',$user_id)
                ->where('u.puid',$user_id)
                ->order('u.id desc')
                ->group('u.id')
                ->select()->toArray();
        if(!$data)return[];
        $trumpet = [];
        foreach ($data as $key=>$v){
            $trumpet[$v['fgame_id']][] = $v;
        }
        return $trumpet;
    }

    /**
     * @函数或方法说明
     * @获取公共账号信息
     * @author: 郭家屯
     * @since: 2020/3/2 16:02
     */
    public function get_public_account()
    {
        $data = $this->field('id,account,real_name,idcard,register_time,register_ip,login_time,login_ip')->where('is_platform',1)->find();
        return $data ? $data->toArray() : [];
    }


    /**
     * @每日首充发放奖励
     *
     * @author: zsl
     * @since: 2021/5/17 9:51
     */
    public function first_pay_every_day($user_id, $pay_amount)
    {
        $spend = new SpendModel();
        $today = Time ::today();
        $todayMap = [];
        $todayMap['user_id'] = $user_id;
        $todayMap['pay_status'] = 1;
        $todayMap['pay_time'] = ['between', [$today[0], $today[1]]];
        $alreadyPay = $spend -> where($todayMap) -> find();
        if (empty($alreadyPay)) {
            $this -> auto_task_complete($user_id, 'first_pay_every_day', $pay_amount);
        }
    }


}
