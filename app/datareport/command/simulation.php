<?php

namespace app\datareport\command;

use app\member\model\UserGameLoginModel;
use app\member\model\UserLoginRecordModel;
use app\member\model\UserLoginRecordMongodbModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\promote\model\PromoteModel;
use app\site\model\EquipmentGameModel;
use app\site\model\EquipmentLoginModel;
use function GuzzleHttp\Psr7\str;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use app\recharge\model\SpendModel;

class simulation extends Command
{

    protected function configure()
    {
        $this
            ->setName('simulation')
            ->addArgument('type', Argument::OPTIONAL)
            ->addArgument('start_date', Argument::OPTIONAL, '开始日期')
            ->addArgument('end_date', Argument::OPTIONAL, '结束日期')
            ->setDescription('数据模拟');
    }

    protected function execute(Input $input, Output $output)
    {
        //时间范围
        $start_date = $input->getArgument('start_date');
        $end_date = $input->getArgument('end_date');
        $end_date = empty($end_date) ? $start_date : $end_date;
        $todaytime = strtotime(date("Y-m-d"));
        if (empty($start_date)) {
            $start_date = $end_date = date('Y-m-d', $todaytime - 86400);
        }
        $date_arr = Db::name('date_list')->where(['time' => ['between', [$start_date, $end_date]]])->where(['time'=>['lt',date('Y-m-d')]])->order('time asc')->column('time');
        if (empty($date_arr)) {
            $output->newLine();
            $output->writeln('日期参数错误');
            return false;
        }
        //数据类型
        $type = $input->getArgument('type');
        switch ($type){
            case 1:
                $this->add_promote($date_arr);        //添加推广员
                break;
            case 2;
                $this->add_user($date_arr);
            break;
//            case 3;
//                $this->add_user_login($date_arr);
//                break;
            case 4;
                $this->add_user_pay($date_arr);
                break;
        }
        $output->newLine();
        $output->writeln('数据记录成功');
    }
    //添加推广员
    private function add_promote($date_arr)
    {
        for ($i=0;$i<10;$i++){
            $add['account'] = sp_random_string(8);
            $add['password'] = cmf_password($add['account']);
            $add['pattern'] = array_rand([0,1]);
            $add['create_time'] = strtotime(reset($date_arr));
            $model = new PromoteModel();
            $result[] = $model->field(true)->insertGetId($add);
        }
        foreach ($result as $k=>$v){
            $this->add_promote_child($v,$add['account'],$add['create_time']);
            $this->add_promote_child($v,$add['account'],$add['create_time']);
        }
    }
    private function add_promote_child($pid,$pname,$create_time,$plevel=1){
        $model = new PromoteModel();
        $data['parent_id'] = $pid;
        $data['parent_name'] = $pname;
        $data['promote_level'] = $plevel+1;
        $top_id = get_top_promote_id($pid);
        $data['top_promote_id'] = $top_id;
        $top_pattern = get_promote_entity($top_id,'pattern')['pattern'];
        $data['pattern'] = $top_pattern;
        $data['account'] = sp_random_string(8);
        $data['password'] = cmf_password($data['account']);
        $data['create_time'] = $create_time;
        $res = $this->add_child($data);
        if($data['promote_level']<3){
            $this->add_promote_child($res,$data['account'],$create_time,2);
            $this->add_promote_child($res,$data['account'],$create_time,2);
        }
    }
    public function add_child($data=[]){
        $model = new PromoteModel();
        $parentdata = $model->field('busier_id')->find($data['parent_id'])->toArray();
        $add['account'] = $data['account'];
        $add['nickname'] = $data['account'];
        $add['password'] = cmf_password($data['password']);
        $add['parent_id'] = $data['parent_id'];
        $add['parent_name'] = $data['parent_name'];
        $add['pattern'] = $data['pattern'];
        $add['promote_level'] = $data['promote_level']?:1;
        $add['top_promote_id'] = $data['top_promote_id']?:0;
        $add['status'] = 1;
        $add['busier_id'] = $parentdata['busier_id'];
        $add['create_time'] = $data['create_time'];
        $res = $model->insertGetId($add);
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }
    //用户注册
    private function add_user($date_arr)
    {
        $promote_ids = Db::table('tab_promote')->column('id');
        $promote_ids[] = 0;
        foreach ($date_arr as $k=>$v){
            $date = $v;
            for ($i=0;$i<20;$i++){
                $promote_id = $promote_ids[array_rand($promote_ids,1)];
                if($promote_id==0){
                    $this->pc_register($v);
                }else{
                    $this->sdk_register($v,$promote_id);
                }
            }
            $this->add_user_login([$date]);
        }
    }
    //用户登录
    private function add_user_login($date_arr)
    {
        $user_ids = Db::table('tab_user')->order('id asc')->column('id');
        $game_ids = Db::table('tab_game')->column('id');
        foreach ($date_arr as $k=>$v){
            $date = $v;
            for ($i=0;$i<50;$i++){
                $user_id = $user_ids[array_rand($user_ids,1)];
                $data['account'] = $data['password'] = get_user_entity($user_id,false,'account')['account'];
                $data['game_id'] = $game_ids[array_rand($game_ids,1)];
                $model = new UserModel();
                $data['type'] = 1;//游戏登录
                $data['login_time'] = strtotime($date);
                $result = $this->login($data);
                $this->playlogin(['user_id'=>$user_id,'game_id'=>$data['game_id']],get_user_entity($user_id),get_game_entity($data['game_id']),$date);
                $map = [];
                //修改游戏记录
                $request['user_id'] = $user_id;
                $request['game_id'] = $data['game_id'];
                $map['time'] = $date;
                $map['user_id'] = $request['user_id'];
                $map['game_id'] = $request['game_id'];
                for ($ii=0;$ii<10;$ii++) {
                    if($result['puid']==0){//小号不记录
                        $res = $this->usergamelogin($map, $data, $result, $request);
                    }
                }
                $map['equipment_num'] = get_user_entity($user_id,false,'equipment_num')['equipment_num'];
                if(empty($map['equipment_num'])){
                    continue;
                }
                $map['promote_id'] = get_user_entity($user_id,false,'promote_id')['promote_id'];
                $this->equipmentlogin($map,$map);
            }
        }
    }
    private function login($data){
        $model = new UserModel();
        $user = $model->where('account', $data['account'])->find();
        $this->updateLogin($user); //更新用户登录信息
        $this->user_login_record($user, $data);
        return $user;
    }
    /**
     * @函数或方法说明
     * @游戏登录
     * @param array $map
     * @param array $user
     * @param array $game
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:56
     */
    private function playlogin($map=[],$user=[],$game=[],$date){
        $playmodel = new UserPlayModel();
        $res = $playmodel->field('id')->where($map)->find();
        if (empty($res)) {
            //添加账号
            $data["user_id"] = $user["id"];
            $data["user_account"] = $user["account"];
            $data["game_id"] = $game["id"];
            $data["game_appid"] = $game["game_appid"];
            $data["game_name"] = $game["game_name"];
            $data["bind_balance"] = 0;
            $data["promote_id"] = $user["promote_id"];
            $data["promote_account"] = $user["promote_account"];
            $data["is_small"] = $user["puid"]?1:0;
            $data['play_time'] = strtotime($date);
            $data['create_time'] = strtotime($date);
            $data['play_ip'] = get_client_ip();
            $data["sdk_version"] = $game["sdk_version"];
            $playmodel->insert($data);
        } else {
            $res = $res->toArray();
            $playmodel->where('id', $res['id'])->setField('play_time', strtotime($date));
        }
    }
    //用户游戏充值支付
    private function add_user_pay($date_arr)
    {
        $userids = Db::table('tab_user')->order('id asc')->column('id');
        $game_ids = Db::table('tab_game')->column('id');
        foreach ($date_arr as $k=>$v){
            $date = $v;
            for ($i = 0; $i < 100; $i++) {
                $user_id = $userids[array_rand($userids)];//大号或小号
                $data['user_id'] = get_user_entity($user_id,false,'puid')['puid']?get_user_entity($user_id,false,'puid')['puid']:$user_id;//如果是小号 user_id 是大号的  如果是大号 是其自己
                $userdata = Db::table('tab_user_play')->where(['user_id'=>$data['user_id'],'game_id' => ['gt', 0]])->order('id asc')->column(' game_id');
                if(empty($userdata)){
                    continue;
                }
                $promote_id = get_user_entity($data['user_id'],false,'promote_id')['promote_id'];
                if ($promote_id) {
                    $promote = get_promote_entity($promote_id,'pattern');
                    if ($promote['pattern'] == 0) {
                        $data['is_check'] = 1;
                    }
                }
                $data['game_id'] = $userdata[0];
                $gamedata = get_game_entity($data['game_id']);
                $data['sdk_version'] = $gamedata['sdk_version'];
                $data['pay_way'] = mt_rand(1, 4);
                $data['server_id'] = mt_rand(1, 40);
                $data['props_name'] = mt_rand(10000, 10000000);
                $data['extend'] = $data['spend_ip'] = mt_rand(10000, 10000000000);
                $data['price'] = mt_rand(1, 100);
                $data['pay_status'] = 1;
                $data['pay_game_status'] = 1;
                $data['small_id'] = get_user_entity($user_id,false,'puid')['puid']?$user_id:0;//小号是他自己 大号为0
                $data['small_nickname'] = get_user_entity($user_id,false,'puid')['puid']?get_user_entity($user_id,false,'nickname')['nickname']:'';//小号是他自己 大号为0
                $data['pay_order_number'] = 'SP_' . $v . $data['extend'];
                $data['pay_time'] = mt_rand(strtotime($v), strtotime($v) + 23 * 3600);
                $spend_data = $this->spend_param($data);
                $spend = new SpendModel();
                $result = $spend->field(true)->insertGetId($spend_data);
                //更新VIP等级和充值总计
                set_vip_level($data['user_id'], $data['price'],get_user_entity($data['user_id'],false,'cumulative')['cumulative']);
                if (isset($promote) && $promote['pattern'] == 0) {
                    set_promote_radio($spend_data,get_user_entity($data['user_id'],false));
                }
            }
        }
    }
    private function spend_param($param = array())
    {
        $user_entity = get_user_entity($param['user_id']);
        $game = get_game_entity($param["game_id"]);
        $data_spend['user_id'] = $param["user_id"];
        $data_spend['user_account'] = $user_entity["account"];
        $data_spend['user_nickname'] = $user_entity["nickname"];
        $data_spend['game_id'] = $param["game_id"];
        $data_spend['game_appid'] = $param["game_appid"];
        $data_spend['game_name'] = $game['game_name'];
        $data_spend['server_id'] = $param["server_id"] ? $param["server_id"] : 0;
        $data_spend['server_name'] = $param["server_name"] ? $param["server_name"] : '';
        $data_spend['game_player_name'] = $param["game_player_name"] ? $param["game_player_name"] : '';
        $data_spend['promote_id'] = $user_entity["promote_id"];
        $data_spend['promote_account'] = $user_entity["promote_account"];
        $data_spend['order_number'] = $param["order_number"] ? $param["order_number"] : '';
        $data_spend['pay_order_number'] = $param["pay_order_number"];
        $data_spend['props_name'] = $param["title"] ? $param["title"] : '';
        $data_spend['cost'] = $param["price"];//原价
        $data_spend['pay_time'] = $param["pay_time"];
        $data_spend['pay_status'] = $param["pay_status"];
        $data_spend['pay_game_status'] = 0;
        $data_spend['extend'] = $param['extend'];
        $data_spend['pay_way'] = $param["pay_way"];
        $data_spend['pay_amount'] = $param["price"];
        $data_spend['discount_type'] = 0;
        $data_spend['small_id'] = $param['small_id'];
        $data_spend['small_nickname'] = get_user_entity($param['small_id'],false,'nickname')['nickname'];
        $data_spend['spend_ip'] = $param["spend_ip"];
        $data_spend['is_check'] = $param["is_check"]?:0;
        $data_spend['sdk_version'] = $param["sdk_version"];
        return $data_spend;
    }
    /**
     * @函数或方法说明
     * @设备登录
     * @param string $type
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:31
     */
    private function equipmentlogin($map1=[],$request=[])
    {
        $Gamemodel = new EquipmentGameModel();
        $map['equipment_num'] = $map1['equipment_num'];
        $map['promote_id'] = $map1['promote_id'];
        $map['game_id'] = $map1['game_id'];
        $equipment = $Gamemodel->field('id,create_time')->where($map)->order('id desc')->find();
        $equipment = empty($equipment)?[]:$equipment->toArray();
        if (!$equipment || (date('Y-m-d', $equipment['create_time']) != $map['time'])) {
            $data['equipment_num'] = $request['equipment_num'];
            $data['promote_id'] = $request['promote_id'];
            $data['game_id'] = $request['game_id'];
            $data['sdk_version'] = get_game_entity($data['game_id'],'sdk_version')['sdk_version'];
            $data['ip'] = get_client_ip();
            $data['create_time'] = strtotime($map1['time']);
            if ($equipment) {
                $data['first_device'] = 2;
            } else {
                $data['first_device'] = 1;
            }
            $result = $Gamemodel->insert($data);
        }
        //设备在线时间更新
        $map['time'] = $map1['time'];
        $loginModel = new EquipmentLoginModel();
        $equipmentLogin = $loginModel->field('id,login_count,last_down_time')->where($map)->find();
        if ($equipmentLogin) {
            $equipmentLogin = $equipmentLogin->toArray();
            $save['login_count'] = $equipmentLogin['login_count'] + 1;
            $save['last_login_time'] = strtotime($map1['time']);
            $result = $loginModel->where('id', $equipmentLogin['id'])->update($save);
        } else {
            $data['play_time'] = 0;
            $save['time'] = $map1['time'];
            $save['equipment_num'] = $request['equipment_num'];
            $save['game_id'] = $request['game_id'];
            $save['promote_id'] = $request['promote_id'];
            $save['login_count'] = 1;
            $save['first_login_time'] = strtotime($map1['time']);
            $save['last_login_time'] = strtotime($map1['time']);
            $result = $loginModel->insert($save);
        }

        $equipmentLogin = $loginModel->field('id,play_time,last_login_time')->where($map)->find()->toArray();
        $save['play_time'] = $equipmentLogin['play_time'] + 1500;//当天在线总时长
        $save['last_down_time'] = strtotime($map['time'])+12*3600;
        $result = $loginModel->where('id', $equipmentLogin['id'])->update($save);
        return true;
    }
    //在线时长记录 模拟上线
    /**
     * @函数或方法说明
     * @游戏登录
     * @author: 郭家屯
     * @since: 2019/8/12 17:24
     */
    private function usergamelogin($map=[],$data=[],$user=[],$request=[]){
        $model = new UserGameLoginModel();
        //上线
        $gameLogin = $model->field('id,login_count,effective_time,last_down_time')->where($map)->find();
        if ($gameLogin) {
            $gameLogin = $gameLogin->toArray();
            if ($data['hours_cover'] && $gameLogin['last_down_time']) {
                $time = (time() - $gameLogin['last_down_time']) / 3600;
                if ($time >= $data['hours_cover']) {
                    $gameLogin['effective_time'] = 0;
                }
            }
            $play_time = $gameLogin['effective_time'];
            $save['login_count'] = $gameLogin['login_count'] + 1;
            $save['last_login_time'] = strtotime($map['time'])+12*3600;
            $result = $model->where('id', $gameLogin['id'])->update($save);
        } else {
            $play_time = 0;
            $save['time'] = $map['time'];
            $save['user_id'] = $request['user_id'];
            $save['game_id'] = $request['game_id'];
            $save['promote_id'] = $user['promote_id'];
            $save['login_count'] = 1;
            $save['last_login_time'] = strtotime($map['time'])+12*3600;
            $result = $model->insert($save);
        }

        $result = $this->get_down($map,$request);
        return true;
    }
//    模拟下线
    private function get_down($map=[],$request=[]){
        $model = new UserGameLoginModel();
        $gameLogin = $model->field('id,play_time,last_login_time,effective_time')->where($map)->find();
        if ($gameLogin) {//一日内
            $gameLogin = $gameLogin->toArray();
            $save['play_time'] = $gameLogin['play_time'] + 1500;//当天在线总时长
            $save['last_down_time'] = strtotime($map['time'])+12*3600;
            $model->where('id', $gameLogin['id'])->update($save);
        }
        return true;
    }
    //模拟网站登录
    private function pc_register($date,$promote_id = 0)
    {
        $find_web_stie = cmf_get_option('admin_set')['web_site'];
        $data['account'] = $data['nickname'] = sp_random_string(8);
        $data['password'] = cmf_password($data['account']);
        $data['register_way'] = 3;
        $data['register_type'] = 1;
        $data['register_time'] = $data['login_time'] = strtotime($date);
        $data['type'] = 1;
        $data['promote_id'] = 0;
        $data['promote_account'] = get_promote_name($data['promote_id']);
        $data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        $data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        $data['head_img'] = 'http://'.$find_web_stie. '/upload/sdk/logoo.png';
        $data['fgame_id'] = 0;
        $data['fgame_name'] = '';
        $data['equipment_num'] = sp_random_string(30);
        $this->register($data);
    }
    //模拟sdk登录
    private  function sdk_register($date,$promote_id)
    {
        $find_web_stie = cmf_get_option('admin_set')['web_site'];
        $game_ids = Db::table('tab_game')->column('id');
        $data['account'] = $data['nickname'] = sp_random_string(8);
        $data['password'] = cmf_password($data['account']);
        $data['register_type'] = 1;
        $data['register_way'] = 1;
        $data['register_time'] = $data['login_time'] = strtotime($date);
        $data['promote_id'] = $promote_id;
        $data['fgame_id'] = $game_ids[array_rand($game_ids,1)];
        $data['promote_account'] = get_promote_name($data['promote_id']);
        $data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        $data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        $data['fgame_name'] = get_game_name($data['fgame_id']);
        $data['type'] = 1;
        $data['equipment_num'] = sp_random_string(30);
        $user_id = $this->register($data);
        $this->playlogin(['user_id'=>$user_id,'game_id'=>$data['fgame_id']],get_user_entity($user_id),get_game_entity($data['fgame_id']),$date);
        $this->add_small($user_id,$data['fgame_id'],$date);//sdk自动加小号
        $data['login_time'] = strtotime($date);
        //修改游戏记录
        $request['user_id'] = $user_id;
        $request['game_id'] = $data['fgame_id'];
        $gmap['time'] = $date;
        $gmap['user_id'] = $user_id;
        $gmap['game_id'] = $data['fgame_id'];
        $res = $this->usergamelogin($gmap, $data, $data, $request);
    }

    //新增小号
    private function add_small($user_id,$game_id,$date)
    {
        $user = new UserModel();
        $list = $user -> field('account,promote_id,lock_status')
            -> where(['id' => $user_id]) -> find();
        $list = empty($list)?'':$list->toArray();
        if (!is_array($list)) {
            exit('用户不存在');
        }elseif($list['lock_status']!=1){
            exit('账号被锁定');
        }
        $request['user_id'] = $user_id;
        $request['login_time'] = strtotime($date);
        $request['game_id'] = $game_id;
        $request['account'] = $list['account'].sp_random_string(4);
        $request['nickname'] = $request['account'];
        $res = $this->register_small($request['user_id'], $request['account'],$request['nickname'], 1, 1, 0, get_promote_name(0), $request["game_id"], get_game_name($request["game_id"]), $date);
        if ($res) {
            $smallinfo = $user->field('id,account,promote_id,promote_account,password,puid')->find($res)->toArray();
            $request['token'] = think_encrypt(json_encode(array('uid' => $smallinfo["id"], 'password' => $smallinfo['password'])));//记录用户token
            $request['id'] = $smallinfo['id'];
            $request['type'] = 1;
            $this->updateLogin($request);
            $this->user_login_record($smallinfo, $request);
        }
    }
    /*
     * 小号注册
     */
    private function register_small($puid, $account,$nickname,$register_way = 1, $register_type = 1, $promote_id = 0, $promote_account = "", $game_id = "", $game_name = "", $date = "")
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
            'register_time' => strtotime($date),
            'fgame_name' => $game_name,
            'puid' => $puid,
            'is_check' => empty($is_check) ? 0 : 1,
        );
        /* 添加用户 */
        $user = new UserModel();
        $uid = $user->insertGetId($data);
        if ($uid) {
            return $uid;
        }else{
            return -1;
        }
    }
    private  function register($data)
    {
        if ($data['promote_id']) {
            $promote = get_promote_entity($data['promote_id'],'pattern');
            if ($promote['pattern'] == 1) {
                $is_check = 1;
            }
        }
        $model = new UserModel();
        $data['is_check'] = empty($is_check) ? 0 : 1;
        $uid = $model->field(true)->insertGetId($data);
        if ($uid) {
            $user = $model->where('id', $uid)->find()->toArray();
            $data['token'] = think_encrypt(json_encode(array('uid' => $user["id"], 'password' => $user['password'])));//记录用户token
            $data['id'] = $user['id'];
            $this->updateLogin($data);
            $data['is_new'] = 1;
            $this->user_login_record($user, $data);
            if ($data['promote_id']) {
                if ($promote['pattern'] == 1 && $data['game_id']) {
                    $this->set_promote_radio($user, $data);
                }
            }
        }
        return $uid;
    }
    /**
     * [更新用户最后登录信息]
     * @param $id
     * @author 郭家屯[gjt]
     */
    private function updateLogin($user)
    {
        $model = new UserModel();
        $save['login_time'] = $user['login_time'];
        $save['login_ip'] = get_client_ip();
        $save['token'] = $user['token'];
        $id = $user['id'];
        $model->where('id', $id)->update($save);
    }
    /**
     * [登录记录]
     * @param $user
     * @author 郭家屯[gjt]
     */
    private function user_login_record($user, $data)
    {
        $model = new UserModel();
        //保存登录记录表
        $save = array(
            'user_id' => $user['id'],
            'user_account' => $user['account'],
            'type' => $data['type'],
            'promote_id' => $user['promote_id'],
            'login_time' => $data['login_time'],
            'login_ip' => get_client_ip(),
            'game_id' => $data['game_id'] ? $data['game_id'] : 0,
            'game_name' => $data['game_id'] ? get_game_name($data['game_id']) : '',
            'puid' =>$user['puid']?:0,
        );
        $mUserLoginRecord = new UserLoginRecordModel();
        $login_id = $mUserLoginRecord->insertGetId($save);
        try{
            $mUserLoginRecordMongodb = new UserLoginRecordMongodbModel();
            $mUserLoginRecordMongodb->insertGetId($save);
        }catch (\Exception $e){

        }

        //插入每天登录记录表
        if ($login_id&&$user['puid']==0) {//小号不记录
            $map['login_time'] = total(1, 1);
            $map['user_id'] = $user['id'];
            if ($data['game_id']) {
                $map['game_id'] = $data['game_id'];
            }
            $login_record = Db::table('tab_user_day_login')->where($map)->find();
            if (empty($login_record)) {
                $day_login['login_record_id'] = $login_id;
                $day_login['user_id'] = $user['id'];
                $day_login['promote_id'] = $user['promote_id'];
                $day_login['game_id'] = $data['game_id'] ? $data['game_id'] : 0;
                $day_login['is_new'] = empty($data['is_new']) ? 0 : 1;
                $day_login['login_time'] = $data['login_time'];
                Db::table('tab_user_day_login')->insert($day_login);
            }
        }
        if ($user['fgame_id'] == 0 && $data['game_id']&&$user['puid']==0) {//第一进入游戏   小号不参与结算
            $res = true;
            if ($user['promote_id']) {
                $res = $this->set_promote_radio($user, $data);
            }
            if(!$res){
                return false;
            }
            $info['fgame_id'] = $data['game_id'];
            $info['fgame_name'] = $data['game_name'];
            $model->where('id', $user['id'])->update($info);
        }
    }

    /**
     * @函数或方法说明
     * @生成注册结算单
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2019/6/26 14:50
     */
    private function set_promote_radio($user = [], $data = [])
    {
        $promote_data = get_promote_entity($user['promote_id'],'id,pattern,parent_id,promote_level');//查看当前渠道的信息
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
            'create_time' => $data['login_time']
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
            $game = Db::table('tab_game')->alias('g')
                ->field('g.game_name,IF(promote_money>0,promote_money,money) as money,IF(promote_ratio>0,promote_ratio,ratio) as ratio')
                ->join(['tab_promote_apply' => 'a'], 'g.id=a.game_id and promote_id=' . $promote_id, 'left')
                ->where('g.id', $data['game_id'])
                ->find();
            $settment[$new_ratio] = $game['ratio'];
            $settment[$new_money] = $game['money'];
            $settment[$new_sum_money] = $game['money'];
        }
        Db::startTrans();
        try {
            Db::table('tab_promote_settlement')->insert($settment);
            $model = new UserModel();
            $model->where('id', $user['id'])->setField('is_check', 0);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
}
