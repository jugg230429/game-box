<?php

namespace app\sdksimplify\controller;

//use cmf\controller\HomeBaseController;
use think\Db;

class ServerController extends BaseController
{
    /**
     * 服务端版本号
     */
    public function get_server_version()
    {

        $data = json_decode(base64_decode(file_get_contents("php://input")), true);

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
        $msg = array(
            "code" => 200,
            "msg" => '获取版本号成功',
            "data" => $version,
            "sdk_verify" => get_game_sdk_verify($data['game_id']),
            "is_super_sign" => $gameInfo['down_port'] == '3' ? 1 : 0,
            "age_type" => $gameInfo['age_type'] == 2 ? 1 : 0,//实名认证系统开关  1国家  0平台
        );
        //写入ios scheme信息
        if (!empty($data['ios_wxpay_scheme'])) {
            $game_scheme = Db ::table('tab_game') -> where(['id' => $data['game_id']]) -> value('sdk_scheme');
            if ($game_scheme != $data['ios_wxpay_scheme']) {
                Db ::table('tab_game') -> where(['id' => $data['game_id']]) -> setField('sdk_scheme', $data['ios_wxpay_scheme']);
            }
        }

        echo base64_encode(json_encode($msg,JSON_PRESERVE_ZERO_FRACTION));exit;
    }


    public function get_switch()
    {
        $req_data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $tmp_game_id = $req_data['game_id'] ?? 0;
        $sdk_version = $req_data['sdk_version'] ?? -1; // 1:安卓 2:ios

        $sdk_set = cmf_get_option('sdksimplify_set');


        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-start
        $data_['yidun_login_switch'] = $sdk_set['yidun_login_switch']??0;
        //如果是iOS(简化版暂时只有安卓)版本,返回易盾的business_id
        if($sdk_version==2){
            $data_['yidun_business_id'] = get_game_entity($tmp_game_id,'yidun_business_id')['yidun_business_id']??'';
        }
        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-end

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

}
