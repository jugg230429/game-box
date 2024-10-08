<?php

namespace app\sdk\controller;

use app\member\model\UserParamModel;
use app\member\model\UserTplayModel;
use app\promote\model\PromoteunionModel;
use app\site\model\AdvModel;
use cmf\controller\HomeBaseController;
use think\Db;

// class ServerController extends BaseController
class ServerController
{
    /**
     * 服务端版本号
     */
    public function get_server_version()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $upgradeversion = WEB_ROOT.'../data/'.'upgrade_version.txt';
        if(!file_exists($upgradeversion)){
            file_put_contents($upgradeversion,840);
        }
        $version = file_get_contents($upgradeversion);
        $version = intval($version);
        if($version<830){
            $version=840;
        }
        //查询是否超级签
        $gameInfo = get_game_entity($data['game_id'],'id,down_port,age_type');

        $sdk_set = cmf_get_option('sdk_set');
        // 否显示账号VIP等级 1 默认显示 0 不显示
        $vip_level_show_switch = $sdk_set['vip_level_show_switch'] ?? 1;  // 0 关闭 1 开启
        // 控制SDK悬浮球上是否显示网速，关闭时不显示
        $network_speed_show_switch = $sdk_set['network_speed_show_switch'] ?? 1; // 0 关闭 1 开启
        // 控制SDK一级页面是否显示“分享”入口
        $share_entrance_switch = $sdk_set['share_entrance_switch'] ?? 1; // 0 关闭 1 开启
        // 禁止模拟器安装(安卓)
//        $forbid_simulator_install_switch = $sdk_set['forbid_simulator_install_switch'] ?? 1; // 0 关闭 1 开启
        $return_data = array(
            "code" => 200,
            "msg" => '获取版本号成功',
            "data" => $version,
            "sdk_verify" => get_game_sdk_verify($data['game_id']),
            "is_super_sign" => $gameInfo['down_port'] == '3' ? 1 : 0,
            "age_type" => $gameInfo['age_type'] == 2 ? 1 : 0,//实名认证系统开关  1国家  0平台
        );
        //写入 ios scheme 信息
        if (!empty($data['ios_wxpay_scheme'])) {
            $game_scheme = Db ::table('tab_game') -> where(['id' => $data['game_id']]) -> value('sdk_scheme');
            if ($game_scheme != $data['ios_wxpay_scheme']) {
                Db ::table('tab_game') -> where(['id' => $data['game_id']]) -> setField('sdk_scheme', $data['ios_wxpay_scheme']);
            }
        }

        echo base64_encode(json_encode($return_data,JSON_PRESERVE_ZERO_FRACTION));exit;
    }
    /*
        获取各种开端 独立接口
        created by wjd
        2021-5-17 10:12:11
     */
    public function get_switch()
    {
        $req_data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $req_data = get_real_promote_id($req_data);
        $tmp_game_id = $req_data['game_id'] ?? 0;
        $sdk_version = $req_data['sdk_version'] ?? -1; // 1:安卓 2:ios
        // $type 1: 拆红包 2: 获取悬浮球
        $type = $req_data['type'] ?? -1;  // 用于区分是哪个动作请求开关状态
        $promote_id = $req_data['promote_id'] ?? 0;
        // 特殊需要用的参数, 用到一个添加一个
        // ...
        $sdk_set = cmf_get_option('sdk_set');
        // 否显示账号VIP等级 1 默认显示 0 不显示
        $vip_level_show_switch = $sdk_set['vip_level_show_switch'] ?? 1;  // 0 关闭 1 开启
        // 控制SDK悬浮球上是否显示网速，关闭时不显示
        $network_speed_show_switch = $sdk_set['network_speed_show_switch'] ?? 1; // 0 关闭 1 开启
        // 控制SDK一级页面是否显示“分享”入口
        $share_entrance_switch = $sdk_set['share_entrance_switch'] ?? 1; // 0 关闭 1 开启
        // 禁止模拟器安装(安卓)
//        $forbid_simulator_install_switch = $sdk_set['forbid_simulator_install_switch'] ?? 1; // 0 关闭 1 开启
        $suspend_show_status = $sdk_set['suspend_show_status'] ?? 1;
        $suspend_icon = cmf_get_image_url($sdk_set['suspend_icon']); // 悬浮球图标
        // 渠道悬浮球设置读取
        if ($req_data['promote_id'] > 0) {
            $model = new PromoteunionModel;
            $map['union_id'] = $req_data['promote_id'];
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $promote_config  = json_decode($resule['union_set'], true);
                $suspend_icon = cmf_get_image_url($promote_config['xfq']) ?? $suspend_icon;
                $suspend_show_status = $promote_config['xfq_show_switch'] ?? $suspend_show_status;
            }
        }
        // 控制SDK上是否显示“游客登录”入口
        $yk_login_switch = $sdk_set['yk_login_status'] ?? 1; // 0 关闭 1 开启
        $account_register_switch = $sdk_set['account_register_switch'] ?? 1; // 0 关闭 1 开启
        $phonenum_register_switch = $sdk_set['phonenum_register_switch'] ?? 1; // 0 关闭 1 开启
        // 原来的登录开关保留
        // $userParmModel = new UserParamModel();
        // $login_type = $userParmModel->getLists($tmp_game_id);  // 获取第三方登录方式
        // $data_ = [];
        // if (empty($login_type)) {
        //     $data_['config'] = '';
        // }else {
        //     foreach ($login_type as $key => $val) {
        //         if ($val['type'] == 1) {
        //             $data_['config'] .= 'qq|';
        //         }
        //         if ($val['type'] == 2) {
        //             $data_['config'] .= 'wx|';
        //         }
        //     }
        // }
        // if($yk_login_switch == 1){
        //     $data_['config'] .= 'yk|';
        // }

        $data_['suspend_show_status'] = $suspend_show_status;
        $data_['vip_level_show_switch'] = $vip_level_show_switch;
        $data_['network_speed_show_switch'] = $network_speed_show_switch;
        $data_['share_entrance_switch'] = $share_entrance_switch;
        if($sdk_version != 2){
//            $data_['forbid_simulator_install_switch'] = $forbid_simulator_install_switch;
        }
        $data_['account_register_switch'] = $account_register_switch;
        $data_['phonenum_register_switch'] = $phonenum_register_switch;
        $data_['suspend_icon'] = $suspend_icon;

        // 获取是否显示 退出广告页
        $data_['logout_adv_show_switch'] = 0;
        $model = new AdvModel();
        $adv = $model->getAdv('loginout_sdk');
        $has_adv_data = $adv['data'] ?? '';
        if(!empty($has_adv_data)){
            $data_['logout_adv_show_switch'] = 1;
        }
        // $data_['task_id'] = 0;
        $type = 2;  // 写死 查询渠道的悬浮球
        // 根据type去判断需要读取哪些表
        if($type == 1){ // 拆红包开关
            $userTplayModel = new UserTplayModel();
            $map['status'] = 1;
            $map['start_time'] = ['lt',time()];
            $map['game_id'] = $tmp_game_id;
            $task = $userTplayModel->field('id')->where($map)->find();
            $data_['task_id'] = $task['id']?1:0;
        }
        if($type == 2){ // 2: 获取渠道悬浮球
            if ($promote_id > 0) {
                $promoteUnionModel = new PromoteunionModel();
                $map['union_id'] = $promote_id;
                $resule = $promoteUnionModel->field('union_set')->where($map)->find();
                $resule = empty($resule) ? [] : $resule->toarray();
                if($resule && $resule['union_set']){
                    $promote_config  = json_decode($resule['union_set'], true);
                    $data_['suspend_icon'] = cmf_get_image_url($promote_config['xfq']);
                    $data_['suspend_show_status'] = $promote_config['xfq_show_switch'] ?? $data_['suspend_show_status'];  // 新增悬浮球显示开关0-关；1-开 by wjd
                }
            }
        }
        if($type == 3){ // 3: 查询是否超级签 和 实名认证系统开关  1国家  0平台
            if ($tmp_game_id > 0) {
                $gameInfo = get_game_entity($tmp_game_id,'id,down_port,age_type');
                $data_["is_super_sign"] = $gameInfo['down_port'] == '3' ? 1 : 0;
                $data_["age_type"] = $gameInfo['age_type'] == 2 ? 1 : 0;//实名认证系统开关  1国家  0平台
                $data["sdk_verify"] = get_game_sdk_verify($tmp_game_id);  // 是否开启验证(0:关闭 1:开启)
            }
        }

        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-start
        $data_['yidun_login_switch'] = $sdk_set['yidun_login_switch']??0;
        //如果是iOS(简化版暂时只有安卓)版本,返回易盾的business_id
        if($sdk_version==2){
            $data_['yidun_business_id'] = get_game_entity($tmp_game_id,'yidun_business_id')['yidun_business_id']??'';
        }
        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-end
        //增加悬浮球内平台币充值开关-20210701-byh
        $data_['ptb_recharge_switch'] = get_ptb_bind_recharge_status('ptb_pay');

        $data_["sdk_verify"] = get_game_sdk_verify($tmp_game_id);

        // 是否显示切换账号按钮
        $data_['loginout_status'] = $sdk_set['loginout_status'] ? : 0;
        $return_data = array(
            "code" => 200,
            "msg" => '获取开关成功!',
            "data" => $data_
        );
        // return json($return_data);
        echo base64_encode(json_encode($return_data,JSON_PRESERVE_ZERO_FRACTION));exit;

    }


    /**
     * @获取用户注册协议,隐私协议,用户注销协议
     *
     * @author: zsl
     * @since: 2021/7/30 19:48
     */
    public function get_protocol()
    {
        $req_data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $req_data = get_real_promote_id($req_data);
        $tmp_game_id = $req_data['game_id'] ?? 0;
        $ProtocolModel = new \app\site\model\PortalPostModel();
        $data['protocol_title'] = $ProtocolModel -> where('id', '=', 27) -> value('post_title');
        $data['protocol_url'] = url('mobile/user/protocol', ['issdk' => 1], true, true);
        $data['privacy_title'] = $ProtocolModel -> where('id', '=', 16) -> value('post_title');
        $data['privacy_url'] = url('mobile/user/privacy', ['issdk' => 1], true, true);
        $data['unsubscribe_protocol_title'] = $ProtocolModel -> where('id', '=', 2) -> value('post_title');
        $data['unsubscribe_protocol_url'] = url('mobile/user/unsubscribe_protocol', ['issdk' => 1], true, true);
        $return_data = array(
                "code" => 200,
                "msg" => '请求成功!',
                "data" => $data
        );
        echo base64_encode(json_encode($return_data, JSON_PRESERVE_ZERO_FRACTION));
        exit;
    }


     /**
     * @获取用户注册协议,隐私协议,用户注销协议
     *
     * @author: zsl
     * @since: 2021/7/30 19:48
     */
    public function get_hot_update()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        if (empty($request['id'])){
            exit(base64_encode(json_encode(['code'=>1003,'msg'=>'参数错误'], JSON_PRESERVE_ZERO_FRACTION)));
        }
        $data = Db::table('tab_hot_update_config')->where('channel',$request['id'])->find();
        if($data){
            $config = cmf_get_option('admin_set');
            $data['hot_update_url'] = $config['web_site'] . $data['hot_update_url'] ;
        }
        $return_data = array(
                "code" => 200,
                "msg" => '请求成功!',
                "data" => $data
        );
        exit(base64_encode(json_encode($return_data, JSON_PRESERVE_ZERO_FRACTION)));
    }

}
