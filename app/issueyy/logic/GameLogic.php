<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-08
 */

namespace app\issueyy\logic;

use app\common\controller\BaseController;
use app\game\model\GameModel;
use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issueyy\model\GameApplyModel;
use cmf\controller\HomeBaseController;
use think\Db;

class GameLogic extends  HomeBaseController
{
    public function play($request)
    {
        if(empty($request['channel_code'])){
            return json(['code'=>1001,'msg'=>'缺少分发平台参数']);
        }else{
            //平台状态
            $mPlatform = new  PlatformModel();
            $platformData = $mPlatform->field('id,open_user_id,platform_config_yy,service_config,controller_name_yy,status')->where('id','=',$request['channel_code'])->find();
            if($platformData['status']!=1){
                return json(['code'=>1014,'msg'=>'分发平台状态已关闭']);
            }
            //用户状态
            $mOpenUser = new OpenUserModel();
            $openUserData = $mOpenUser -> field('id,account,auth_status,status,settle_type') -> where('id', '=', $platformData['open_user_id']) -> find();
            if ($openUserData['status'] != 1 || $openUserData['auth_status'] != 1) {
                return json(['code' => 1020, 'msg' => '平台用户已锁定']);
            }

        }
        if(empty($request['game_id'])){
            return json(['code'=>1002,'msg'=>'缺少游戏数据']);
        }else{
            //游戏状态
            $mGame = new IssueGameModel();
            $mGameData = $mGame->field('id,status,pay_notify_url,game_key')->where('id','=',$request['game_id'])->find();
            if($mGameData['status']!=1){
                return json(['code'=>1010,'msg'=>'游戏已下架']);
            }
        }
        $appmodel = new GameApplyModel();
        $applyData = $appmodel->where('platform_id','=',$request['channel_code'])->where('game_id','=',$request['game_id'])->find();
        if($applyData['status']!=1||$applyData['enable_status']!=1){//不存在该分发或分发状态不符合
            return json(['code'=>1022,'msg'=>'游戏已关闭']);
            //$this->redirect(url('mobile/game/open_game',[],false)."?game_id=".$request['game_id']);exit;
        }else{//分发状态符合
            //打开游戏页面
            $controller_name = $this->controller_name($request['channel_code']);
            if(!$controller_name){//渠道接分发
                //验签
                if(0){
                    return json(['code'=>1003,'msg'=>'验签失败']);
                }
                return json(['code'=>1000,'msg'=>'系统错误']);
                $login_url = $this->get_login_url($request);
                if(!$login_url){
                    return json(['code'=>1000,'msg'=>'系统错误']);
                }
                $this->assign('login_url',$login_url);
                return $this->fetch($controller_name.'/open_game');
            }else{
                if(!file_exists(APP_PATH.'issueyy/logic/pt/'.$controller_name.'Logic.php')){
                    return json(['code'=>1004,'msg'=>'平台接口文件错误']);
                }
                $class = '\app\\'.request()->module().'\\logic\\pt\\'.$controller_name.'Logic';
                $logic = new $class();
                //验签
                $checksign = false;
                $checksign = $logic->check_sign($request,$applyData);
                if(is_bool($checksign)){
                    if(!$checksign){
                        return json(['code'=>1003,'msg'=>'验签失败']);
                    }
                }else{
                    return json($checksign);
                }
                //用户登录
                $user = $logic->user_login($request,$applyData);
                if($user['lock_status']!='1'){
                    return json(['code'=>1021,'msg'=>'用户已锁定']);
                }
                $user_id = 'sue_'.$user->id;
                //游戏信息
                $modelGame = new IssueGameModel();
                $modelGame -> field('id,interface_id,cp_game_id,status');
                $modelGame -> where('id', '=', $request['game_id']);
                $modelGameData = $modelGame -> find();
                if (empty($modelGameData) || $modelGameData['status'] != 1) {
                    return json(['code'=>1021,'msg'=>'用户已锁定']);
                }
                $interface = Db::table('tab_game_interface')->field('tag')->where('id',$modelGameData['interface_id'])->find();
                if(empty($interface)){
                    return json(['code'=>1021,'msg'=>'CP接口错误']);
                }
                $server = Db::table('tab_game_server')->field('server_num')->where('id',$request['server_id'])->find();
                if(empty($server)){
                    return json(['code'=>1021,'msg'=>'区服不存在']);
                }
                if($openUserData['settle_type'] == 0){
                    $url = $request['pay_url_img'];
                }else{
                    $url = url('@media/Issuepay/pay', ['channel_code'=>$request['channel_code'],'user_id' => str_replace('sue_','',$user_id), 'game_id' => $modelGameData['id'], 'server_id' => $request['server_id']], true, true);
                }
                $pay_url = url('@media/Index/qrcode', ['url' => base64_encode(base64_encode($url))], true, true);
                $controller_game_name = "\app\sdkyy\api\\".$interface['tag'];
                $gamecontroller = new $controller_game_name;
                $login_url = $gamecontroller->play($modelGameData['cp_game_id'],$server['server_num'],$user_id,$pay_url);
                $this->assign('login_url',$login_url);
                return $this->fetch($controller_name.'/open_game');
            }

        }
    }

    public function controller_name($channel_code)
    {
        if(empty($channel_code)){
            return 'false';
        }
        $modelPlat = new PlatformModel();
        $viewData = $modelPlat
            ->field('controller_name_yy')
            ->where(array('id' => $channel_code))
            ->find();
        return $viewData->controller_name_yy?:false;
    }

    public function get_login_url(array $params)
    {
        if (empty($params['platform_user']) || empty($params['game_id'])) {
            return '';
        }
        //透传参数,需要cp方回传信息
        $channelExt['host'] = $this -> request -> host();
        $channelExt['ip'] = $this -> request -> ip();
        $channelExt['ff_platform'] = $params['channel_code'];
        $channelExt['time'] = time();
        $ext = simple_encode(json_encode($channelExt));//登录透传信息，cp跳转页面后需要解密处理
        //游戏信息
        $modelGame = new IssueGameModel();
        $modelGame -> alias('g');
        $modelGame -> field('g.id,g.game_appid,g.status,g.login_notify_url,g.agent_id,game_key,access_key');
        $modelGame -> where('g.id', '=', $params['game_id']);
        $modelGameData = $modelGame -> find();
        if (empty($modelGameData) || $modelGameData['status'] != 1 || empty($modelGameData['login_notify_url'])) {
            return '';
        }
        $data['channelExt'] = $ext;
        $data['agent_id'] = $modelGameData['agent_id'];
        $data['game_appid'] = $modelGameData['game_appid'];
        $data['timestamp'] = time();
        $data['loginplatform2cp'] = get_client_ip();
        $data['user_id'] = $params['platform_user'];
        $data['birthday'] = $params['birthday'];
        ksort($data);//字典排序
        if(!file_exists(APP_PATH.'sdkyy/logic/GameLogic.php')){
            return '';
        }
        $data['sign'] = (new \app\sdkyy\logic\GameLogic()) -> h5SignData($data, $modelGameData['game_key']);
        $_loginUrl = trim($modelGameData['login_notify_url']) . "?" . http_build_query($data);
        return $_loginUrl;
    }

    public function page($request)
    {
        $base = new BaseController();
        $model = new IssueGameModel();
        $map = [];
        $map['sdk_version'] = 4;
        if (!empty($request['id'])) {
            $map['id'] = $request['id'];
        }
        if (!empty($request['game_name'])) {
            $map['game_name'] = $request['game_name'];
        }

        $where_str = '';//游戏类型查询更改
        if (!empty($request['game_type_id'])) {
            $where_str = "FIND_IN_SET('".$request['game_type_id']."',game_type_id)";
        }
        if ($request['status'] === '0' || $request['status'] === '1') {
            $map['status'] = $request['status'];
        }
        $start_time = $request['start_time'];
        $end_time = $request['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        //查询字段
        $exend['field'] = '*';
        //排序优先，ID在后
        $exend['order'] = 'sort desc,create_time desc,id desc';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend,$where_str);
        return $data;
    }

    /**
     * @查询未导入游戏列表
     *
     * @author: zsl
     * @since: 2020/7/10 19:46
     */
    public function xt_page($request)
    {
        $base = new BaseController();
        $model = new GameModel();
        $map = [];
        if (!empty($request['game_ids'])) {
            $map['tab_game.id'] = ['in', $request['game_ids']];
        } else {
            $ids = Db ::table('tab_issue_game') -> column('game_id');
            $map['tab_game.id'] = ['not in', $ids];
        }
        $map['tab_game.sdk_version'] = 4;
        $map['a.issue'] = 1;
        $exend['row'] = 50;
        //查询字段
        $exend['field'] = 'tab_game.id as game_id,sdk_version,game_name,game_type_id,game_type_name,game_appid,short,icon,cover,
        screenshot,features,introduction,screen_type,dev_name,interface_id,currency_name,currency_ratio,a.issue';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,tab_game.id desc';
        $exend['join1'] = [['tab_game_attr' => "a"], 'tab_game.id = a. game_id', 'left'];
        $data = $base -> data_list_join($model, $map, $exend);
        return $data;
    }



    public function add($request)
    {
        if (empty($request['game_ids'])) {
            return json(['code' => 0, 'msg' => '请选择游戏数据']);
        }
        $gameData = $this -> xt_page(['game_ids' => $request['game_ids']]) -> toarray()['data'];
        if (empty($gameData)) {
            return json(['code' => 0, 'msg' => '游戏数据错误']);
        }
        Db ::startTrans();
        foreach ($gameData as $key => $value) {
            $gamemodel = new GameModel();
            $issuegamemodel = new IssueGameModel();
            $issuegamemodel -> game_id = $value['game_id'];
            $issuegamemodel -> cp_game_id = $value['cp_game_id']?:0;
            $issuegamemodel -> game_name = $value['game_name'];
            $issuegamemodel -> currency_name = $value['currency_name'];
            $issuegamemodel -> currency_ratio = $value['currency_ratio'];
            $issuegamemodel -> interface_id = $value['interface_id'];
            $issuegamemodel -> sdk_version = $value['sdk_version'];
            $issuegamemodel -> game_type_id = $value['game_type_id'];
            $issuegamemodel -> game_type_name = $value['game_type_name'];
            $issuegamemodel -> game_appid = $value['game_appid'];
            $issuegamemodel -> game_key = $value['game_key']?:'';
            $issuegamemodel -> access_key = $value['access_key']?:'';
            $issuegamemodel -> agent_id = $value['agent_id']?:'';
            $issuegamemodel -> short = $value['short'];
            $issuegamemodel -> login_notify_url = $value['login_notify_url']?:'';
            $issuegamemodel -> pay_notify_url = $value['pay_notify_url']?:'';
            $issuegamemodel -> material_url = $value['material_url']?:'';
            $issuegamemodel -> icon = $value['icon'];
            $issuegamemodel -> cover = $value['cover'];
            $issuegamemodel -> screenshot = $value['screenshot'];
            $issuegamemodel -> features = $value['features'];
            $issuegamemodel -> introduction = $value['introduction'];
            $issuegamemodel -> screen_type = $value['screen_type'];
            $issuegamemodel -> dev_name = $value['dev_name'];
            $issuegamemodel -> status = 1;
            $issuegamemodel -> save();
        }
        Db ::commit();
        return json(['code' => 200, 'msg' => '导入成功']);

    }


    public function edit($param)
    {
        $mGame = new IssueGameModel();
        $param['game_type_name'] = get_game_type_name_str($param['game_type_id']);//游戏类型名称修改
        $param['screenshot'] = $param['screenshot'] ? implode(',', $param['screenshot']) : '';
        $res = $mGame -> allowField(true) -> save($param, ['id' => $param['id']]);
        return $res;
    }


    public function info($id)
    {
        $mGame = new IssueGameModel();
        $field = '';
        $where = [];
        $where['id'] = $id;
        $info = $mGame -> field($field) -> where($where) -> find();
        return $info;
    }

}
