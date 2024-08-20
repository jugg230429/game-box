<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/22
 * Time: 11:06
 */

namespace app\sdk\controller;

use app\common\lib\Util\HolidayUtil;
use app\common\logic\AntiaddictionLogic;
use app\common\logic\PayLogic;
use app\common\logic\SimulatorLogic;
use app\common\logic\YiDunLoginLogic;
use app\extend\model\MsgModel;
use app\game\logic\InterflowLogic;
use app\game\model\GamesourceModel;
use app\member\logic\UserLogic;
use app\member\model\AlipayModel;
use app\member\model\UserConfigModel;
use app\member\model\UserGameLoginModel;
use app\member\model\UserModel;
use app\member\model\UserParamModel;
use app\member\model\UserPlayInfoModel;
use app\member\model\UserPlayModel;
use app\member\model\UserTplayModel;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteunionModel;
use app\site\model\EquipmentGameModel;
use app\site\model\EquipmentLoginModel;
use app\site\model\NoticeModel;
use app\user\logic\UnsubscribeLogic;
use geetest31\GeetestLib;
use think\Cache;
use think\Checkidcard;
use think\Db;
use think\WechatAuth;
use think\xigusdk\Xigu;
use app\webplatform\logic\UserLogic as WebUserLogic;

class UserController extends BaseController
{
    /**
     * 强更接口
     * @return [type] [description]
     */
    public function force_update()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $data = array();
        $game_info = get_game_entity($request['game_id'],'is_force_update,sdk_version,down_port,ios_game_address');
        $gamesourceModel = new GamesourceModel();
        $game_source = $gamesourceModel->field('source_version,source_name,remark,file_size,bao_name,file_url,plist_url')->where('game_id', $request['game_id'])->find();
        $game_source = empty($game_source)?[]:$game_source->toArray();
        if (empty($game_source)) {
            $game_source['source_version'] = 0;
        }
        $data['is_force_update'] = $game_info['is_force_update'];
        $data['source_version'] = $game_source['source_version'];
        $data['update_tips'] = '游戏已经更新，请下载最新游戏包~';
        $remark = [$game_source['remark']];
        if ($game_info['sdk_version'] == 1) {
            $data['and_remark'] = !empty($remark) ? $remark : [];
            $data['and_remark'] = explode(  "\r\n", $data['and_remark'][0]);
            $data['and_file_size'] = $game_source['file_size'];
            $data['and_version_name'] = $game_source['source_name'];
            $host = cmf_get_domain();
        } else {
            $data['ios_remark'] = !empty($remark) ? $remark : [];
            $data['ios_file_size'] = $game_source['file_size'];
            $data['ios_version_name'] = $game_source['source_name'];
            $host = 'https://' . $_SERVER['HTTP_HOST'];
        }
        if ($request['promote_id'] > 0) {
            // 判断是否走的第三方上传
            $upload_config = cmf_get_option("storage");
            //渠道包
            $applymodel = new \app\promote\model\PromoteapplyModel();
            $apply_info = $applymodel->field('enable_status,pack_url,plist_url,pack_type')->where('game_id', $request['game_id'])->where('promote_id', $request['promote_id'])->find();
            $apply_info = $apply_info ? $apply_info->toArray() : [];
            if ($apply_info['enable_status'] == 1) {
                if ($upload_config['type'] != 'Local') {
                    $file_url = cmf_get_file_download_url($apply_info['pack_url']);
                } else {
                    $file_url = $host . '/upload/'.ltrim($apply_info['pack_url'], '.');
                }
                if($apply_info['pack_type'] == 4){
                    $pfile_url = $apply_info['pack_url'];
                }else{
                    $pfile_url = $host  . '/upload/'.ltrim($apply_info['plist_url'], '.');
                }
            } else {
                $data['update_tips'] = '游戏正在更新，请稍后再试~';
                $file_url = '';
                $pfile_url = '';
            }
        } else {
            //原包
            $file_url = $host  . '/upload/'. ltrim($game_source['file_url'], '.');
            if($game_info['down_port'] == 3){
                $pfile_url = $game_info['ios_game_address'];
            }else{
                $pfile_url = $host  . '/upload/'. ltrim($game_source['plist_url'], '.');
            }

        }
        if ($game_info['sdk_version'] == 1) {
            $data['and_file_url'] = $file_url;
        } else {
            $data['ios_file_url'] = $pfile_url;
        }
        $this->set_message(200,"请求成功",$data);
    }

    /**
     * @超级签更新
     *
     * 版本号 : super_version
     * 版本名称 : super_version_name
     * 更新说明 : super_remark
     * 更新地址 : down_url
     * 强更开关 : is_force_update
     * 原包大小 : game_address_size
     *
     * @author: zsl
     * @since: 2021/1/6 11:11
     */
    public function super_sign_update()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $gameInfo = get_game_entity($data['game_id'], 'id,ios_game_address,down_port,super_version,super_remark,is_force_update,game_address_size,super_version_name');
        if ($gameInfo['down_port'] != '3') {
            $this -> set_message(1001, "该游戏不是超级签");
        }
        $return_msg = [];
        $return_msg['is_force_update'] = $gameInfo['is_force_update'];
        $return_msg['super_version'] = $gameInfo['super_version'];
        $return_msg['super_version_name'] = $gameInfo['super_version_name'];
        $return_msg['game_address_size'] = $gameInfo['game_address_size'];
        $return_msg['super_remark'] = empty($gameInfo['super_remark'])?[]:[$gameInfo['super_remark']];
        if (empty($data['promote_id'])) {
            //官方包
            $info = [];
            $info['MCHPromoteID'] = (string)'0';
            $info['XiguSuperSignVersion'] = (string)super_sign_version($data['game_id']);
            $down_url = $gameInfo['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
        } else {
            //渠道包
            $mApply = new PromoteapplyModel();
            $where = [];
            $where['game_id'] = $data['game_id'];
            $where['promote_id'] = $data['promote_id'];
            $where['status'] = 1;
            $where['enable_status'] = 1;
            $down_url = $mApply -> where($where) -> value('dow_url');
        }
        $return_msg['down_url'] = $down_url;
        $this -> set_message(200, "请求成功", $return_msg);
    }


    /**
     * [sdk登录接口]
     * @author 郭家屯[gjt]
     */
    public function user_login()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);

        //验证验证码
        $sdk_verify = get_game_sdk_verify($data['game_id']);
        if ($sdk_verify) {
            $vResult = $this -> secondValidate($data[GeetestLib::GEETEST_CHALLENGE], $data[GeetestLib::GEETEST_VALIDATE], $data[GeetestLib::GEETEST_SECCODE]);
            if (false === $vResult) {
                $this -> set_message(1001, "验证码验证失败");
            }
        }

        $model = new UserModel();
        $data['type'] = 1;//游戏登录
        $result = $model->login($data,0,'sdk'); // user_info
        switch ($result) {
            case 1005:
                $this->set_message(1005, '用户名不存在');
                break;
            case 1006:
                $this->set_message(1006, '密码错误');
                break;
            case 1007:
                $this->set_message(1007, '账号被锁定');
                break;
            default:
                //封禁判断-20210712-byh
                if(!judge_user_ban_status($data['promote_id'],$data['game_id'],$result['id'],$data['equipment_num'],get_client_ip(),$type=1)){
                    $this->set_message(1005, '您当前被禁止登录，请联系客服');
                }
                if (!empty($data['is_simulator'])) {
                    //检查是否有模拟器登录权限
                    $lSimulator = new SimulatorLogic();
                    $checkArr = [];
                    $checkArr['user_id'] = $result['id'];
                    $checkArr['game_id'] = $data['game_id'];
                    if(!$lSimulator -> checkUser($checkArr)){
                        $this->set_message(1040, '不允许模拟器内登录');
                    }
                }

                $this->add_user_play($result, $data);//大号也创建玩家 统计时不计算小号的
                $small_list = $this -> small_list($result['id'], $data['game_id']);
                if (empty($small_list)) {
                    // 创建小号
                    $userlogic = new UserLogic();
                    $new_small_id = $userlogic->add_small($result,$data['game_id'],$result['account'].'_@_1',$result['account'].'@小号1',$data['sdk_version']);
                    // 创建小号后返回信息
                    $small_list = [];
                    $small_list[0] = array('small_id' => $new_small_id,'account' => $result['account'].'_@_1','nickname' => $result['account'].'@小号1');
                }
                // 是否显示选择小号入口
                $sdk_set = cmf_get_option('sdk_set');
                $need_choose_smallaccount_switch = $sdk_set['need_choose_smallaccount_switch'] ?? 1; // 0 关闭 1 开启

                $res_msg = array(
                    "user_id" => $result["id"],
                    "account" => $result["account"],
                    "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                    'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                    'sex' => $result['sex'],
                    "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                    "extra_param" => request()->domain(),//下单时需要原样返回
                    "age_status" =>$result['age_status'],
                    "birthday" => get_birthday_by_idcard($result['idcard']),
                    "small_list"=>$small_list,
                    //实名认证会话标识
                    "talking_code"=>cmf_random_string(22),
                    'is_open_small_account'=>$need_choose_smallaccount_switch
                );
                break;
        }
        $this->set_message(200,"登录成功",$res_msg);
    }




    /**
     * 一键登录接口
     * by:byh 2021/06/24
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function one_click_login()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $yidun_data = [
            'yidun_token'=>$data['yidun_token'],
            'accessToken'=>$data['accessToken'],
        ];

        //一键登录号码处理--
        $yd_logic = new YiDunLoginLogic($data['game_id']);
        $res = $yd_logic->one_click($yidun_data);
        if($res['code'] != 200 || $res['data']['phone'] == ''){
            $this->set_message(1015,'手机号获取失败');//msg:获取失败
        }

        $mobile = $res['data']['phone'];//手机号

        $model = new UserModel();
        $data['type'] = 1;//游戏登录
        $data['account'] = $mobile;
        //查询手机号是否已存在,存在直接登录,如不存在,注册登录
        if(is_third_platform($data['promote_id'])){
            $UserLogic = new WebUserLogic($data['promote_id']);
            $userRes = $UserLogic->get_user_info($mobile,4);
            if($userRes > 0){
                $user = $model->field('id')->where('id',$userRes)->find();
            }
        }else{
            $user = $model->field('id')->where('account',$mobile)->find();
        }
        if(empty($user)){//不存在-则注册后返回用户信息

            //封禁判断-20210712-byh
            if(!judge_user_ban_status($data['promote_id'],$data['game_id'],'',$data['equipment_num'],get_client_ip(),$type=1)){
                $this->set_message(1005, '您当前被禁止登录，请联系客服');
            }
            //验证注册ip
            if(!checkregiserip()){
                $this->set_message(1039,'您的IP被限制，无法进行登录注册');
            }
            $is_register = 1;//注册

            $data['phone'] = $mobile;
            //此处增加判断手机号有没有被其他账号绑定,如被绑定,此处注册留空
            $is_bind_phone = $model->where('phone',$mobile)->count();
            if($is_bind_phone>0){
                $data['phone'] = '';
            }
            $data['register_type'] = 2;//注册方式 0游客1账号 2 手机 3微信 4QQ 5百度 6微博 7邮箱
            $data['register_way'] = 1;//注册来源 1sdk 2app 3PC 4wap

            $game = Cache::get('sdk_game_data'.$data['game_id']);
            $data['game_name'] = $game['game_name'];
            $result = $model->register($data,'sdk');
            if ($result == -1) {
                $this->set_message(1028, '登录失败');
            } else {
                $this->add_user_play($result, $data,1);
                //创建小号
                $userlogic = new UserLogic();
                $userlogic->add_small($result,$data['game_id'],$result['account'].'_@_1',$result['account'].'@小号1',$data['sdk_version']);
                $small_list = $this->small_list($result['id'], $data['game_id']);
                $res_msg = array(
                    "user_id" => $result["id"],
                    "account" => $result["account"],
                    "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                    'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                    'sex' => $result['sex'],
                    "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                    "extra_param" => request()->domain(),//下单时需要原样返回
                    "age_status" =>0,
                    "birthday" => "",
                    "small_list"=>$small_list,
                    "talking_code"=>cmf_random_string(22),
                    'is_open_small_account'=>1
                );
            }
            $this->set_message(200,"登录成功",$res_msg);

        }else{
            //封禁判断-20210712-byh
            if(!judge_user_ban_status($data['promote_id'],$data['game_id'],$user['id'],$data['equipment_num'],get_client_ip(),$type=1)){
                $this->set_message(1005, '您当前被禁止登录，请联系客服');
            }
            $is_register = 0;//登录
            //根据获取的$user(id)再查一遍account
            $account = $model->where('id',$user['id'])->value('account');
            $data['account'] = $account;
            $result = $model->login($data,1,'sdk'); // user_info
            switch ($result) {
                case 1005:
                    $this->set_message(1005, '用户名不存在');
                    break;
                case 1007:
                    $this->set_message(1007, '账号被锁定');
                    break;
                default:
                    $this->add_user_play($result, $data);//大号也创建玩家 统计时不计算小号的
                    $small_list = $this -> small_list($result['id'], $data['game_id']);
                    $res_msg = array(
                        "user_id" => $result["id"],
                        "account" => $result["account"],
                        "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                        'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                        'sex' => $result['sex'],
                        "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                        "extra_param" => request()->domain(),//下单时需要原样返回
                        "age_status" =>$result['age_status'],
                        "birthday" => get_birthday_by_idcard($result['idcard']),
                        "small_list"=>$small_list,
                        //实名认证会话标识
                        "talking_code"=>cmf_random_string(22),
                        'is_open_small_account'=>1
                    );
                    break;
            }
            $this->set_message(200,"登录成功",$res_msg);

        }

    }

    public function get_small_lists()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if (empty($request)) {
            $this->set_message(1071, '操作数据不能为空');
        }
        $res = $this->small_list($request['user_id'],$request['game_id']);
        $this->set_message(200, '请求成功',$res);
    }
    /**
     * 小号列表
     * @param string $puid 大号id
     * @param string $game_id 游戏id
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function small_list($puid = '', $game_id = '')
    {
        if (!is_numeric($puid) || $puid < 1) {
            return [];
        }
        if (!is_numeric($game_id) || $game_id < 1) {
            return [];
        }
         //判断是否为互通游戏
        $lInterflow = new InterflowLogic();
        $game_ids = $lInterflow -> getInterflowGameIds(['game_id' => $game_id]);
        if (!empty($game_ids)) {
            $map = ['puid' => $puid, 'lock_status' => 1, 'fgame_id' => ['in', $game_ids]];
        } else {
            $map = ['puid' => $puid, 'lock_status' => 1, 'fgame_id' => $game_id];
        }
        $field = 'id as small_id,account,nickname';

        $order = 'register_time desc';
        $small_list = get_user_lists_info($field, $map, $order);
        $map = ['id' => $puid, 'lock_status' => 1];
        $user_list = get_user_lists_info($field, $map, $order);
        foreach ($user_list as &$v) {
            $v['nickname'] = $v['nickname'].' [不可交易]';
        }
        $list = array_merge($user_list, $small_list); // 查询大号
        return $list;
    }
    public function add_small()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        if (empty($request)) {
            $this->set_message(1071, '操作数据不能为空');
        }
        $user = new UserModel();
        $list = $user -> field('account,promote_id,lock_status')
            -> where(['id' => $request['user_id']]) -> find();
        $list = empty($list)?'':$list->toArray();
        if (!is_array($list)) {
            $this->set_message(1005, '用户不存在');
        }elseif($list['lock_status']!=1){
            $this->set_message(1007, '账号被锁定');
        }
        $count_map['fgame_id'] = $request['game_id'];
        $count_map['puid'] = $request['user_id'];
        $result2 = $user->field('id')->where($count_map)->count();
        if ($result2 >= 20) {
            $this->set_message(1078, '小号个数已满20，不可继续添加');
        }
        $request['account'] = $list['account'].sp_random_string(4);
        $request['nickname'] = trim($request['nickname']);
        if(mb_strlen($request['nickname'])>20){
            $this->set_message(1081, '小号昵称长度不能超过20');
        }
        $res = $user->register_small($request['user_id'], $request['account'],$request['nickname'], 1, 1, 0, get_promote_name(0), $request["game_id"], $request['game_name'], $request['sdk_version']);
        if ($res) {
            $small_list = $this -> small_list($request['user_id'], $request['game_id']);
            $this -> set_message(200, "添加成功",$small_list);
        } else {
            $this -> set_message(1027, "添加失败");
        }
    }
    public function change_small_nickname()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $usermodel = new UserModel();
        if(empty($request['user_id'])||empty($request['small_id'])){
            $this -> set_message(1079,"用户数据不能为空");
        }
        $isusersmall = $usermodel->is_user_small($request['user_id'],$request['small_id']);
        if(!$isusersmall){
            $this -> set_message(1080, "小号不属于该账户");
        }
        $nickanme = trim($request['nickname']);
        //判断长度
        if(mb_strlen($nickanme)>20){
            $this->set_message(1081, '小号昵称长度不能超过20');
        }
        $save['nickname'] = $nickanme;
        $res = $usermodel->where(['id'=>$request['small_id']])->update($save);
        if($res!==false){
            $this -> set_message(200, "修改成功");
        }
        $this -> set_message(1037, "修改失败");
    }

    /**
     * 小号进入游戏
     */
    public function get_enter_game_info()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $usermodel = new UserModel();
        if(empty($request['user_id'])||empty($request['small_id'])){
            $this -> set_message(1079, "用户数据不能为空");
        }
        if ($request['user_id'] == $request['small_id']) {
            $res = $usermodel->get_enter_game_info([], $request['small_id'], $request['game_id'], get_game_name($request['game_id']), $request['sdk_version']);
        } else {
            $res = $usermodel->get_enter_game_info(['puid' => $request['user_id']], $request['small_id'], $request['game_id'], get_game_name($request['game_id']), $request['sdk_version']);
        }
        if (is_array($res)) {
            $this->set_message(200,'登录成功',['small_id'=>$res['small_id'],'nickname'=>$res['nickname'],"extra_param" => request()->domain(),'token'=>password_hash($res['token'],PASSWORD_DEFAULT)]);//extra_param下单时需要原样返回
        } else {
            $this->set_message(1004, "用户不存在或被禁用");
        }

    }
    /**
     * [第三方登录方式]
     * @author 郭家屯[gjt]
     */
    public function thirdparty()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $model = new UserParamModel();
        $login_type = $model->getLists($request['game_id']);  // 获取第三方登录方式
        $data = [];
        if (empty($login_type)) {
            $data['config'] = '';
        } else {
            foreach ($login_type as $key => $val) {
                if ($val['type'] == 1) {
                    $data['config'] .= 'qq|';
                }
                if ($val['type'] == 2) {
                    $data['config'] .= 'wx|';
                }
            }
        }
        // 控制SDK上是否显示“游客登录”入口
        $sdk_set = cmf_get_option('sdk_set');


        if($request['sdk_version']=='2'){
            // IOS 判断版本
            $deviceInfo = explode(',',$request['ios_deviceInfo']);
            $versionStr = findNum($deviceInfo[0]);
            if($versionStr>=955){
                if(isset($sdk_set['yk_login_status']) && $sdk_set['yk_login_status'] == 1){
                    $data['config'] .= 'yk|';
                }
            }
        }else{
            if(isset($sdk_set['yk_login_status']) && $sdk_set['yk_login_status'] == 1){
                $data['config'] .= 'yk|';
            }
        }

        //新增返回sdk登陆配置字段
        $data['sdk_set'] = $sdk_set;

        $post_title = Cache::get('sdk_post_title');
        if(empty($post_title)){
            $portalPostModel = new \app\site\model\PortalPostModel;
            $portal = $portalPostModel->where('id', 27)->find();
            $post_title = $portal['post_title'];
            Cache::set('sdk_post_title',$post_title,86400);
        }
        $data['portal_title'] = $post_title;
//        $data['sdk_verify'] = get_game_sdk_verify($request['game_id']);

        //增加对登录logo字段返回-20210624-byh-s
        $data['sdk_login_logo'] = cmf_get_image_url($sdk_set['sdk_login_logo']);
        //判断是否渠道方,如是,且配置了渠道logo,则返回渠道logo
        if($request['promote_id']>0){
            $model = new PromoteunionModel;
            $map['union_id'] = $request['promote_id'];
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $promote_config  = json_decode($resule['union_set'], true);
                $data['sdk_login_logo'] = empty($promote_config['sdk_login_logo'])?cmf_get_image_url($sdk_set['sdk_login_logo']):cmf_get_image_url($promote_config['sdk_login_logo']);
            }
        }
        //增加对登录logo字段返回-20210624-byh-e

        $this->set_message(200,"获取成功",$data);
    }

    /**
     * [第三方登录参数请求(安卓使用，苹果本地参数)]
     * @author 郭家屯[gjt]
     */
    public function oauth_param()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $type = $request['login_type'];
        $param['qqappid'] = '';
        $param['weixinappid'] = '';
        switch ($type) {
            case 'qq':
                $param_set = get_game_login_param($request['game_id'], 1);
                $param['qqappid'] = $param_set['openid'];
                break;
            case 'wx':
                $param_set = get_game_login_param($request['game_id'], 2);
                $param['weixinappid'] = $param_set['wx_appid'];
                break;
        }
        if (empty($param)) {
            $this->set_message(1053, '服务器未配置此参数');
        }
        $this->set_message(200,"获取成功",$param);
    }

    /**
     * [第三方登录]
     * @author 郭家屯[gjt]
     */
    public function oauth_login()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        //验证验证码
        $sdk_verify = get_game_sdk_verify($request['game_id']);
        if ($sdk_verify) {
            $vResult = $this -> secondValidate($request[GeetestLib::GEETEST_CHALLENGE], $request[GeetestLib::GEETEST_VALIDATE], $request[GeetestLib::GEETEST_SECCODE]);
            if (false === $vResult) {
                $this -> set_message(1001, "验证码验证失败");
            }
        }
        //封禁判断-20210712-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],'',$request['equipment_num'],get_client_ip(),$type=1)){
            $this->set_message(1005, '您当前被禁止登录，请联系客服');
        }

        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $openid = $request['openid'];
        if ($request['login_type'] == "wx") {
            $param_set = get_game_login_param($request['game_id'], 2);
            if (empty($param_set['wx_appid'])) {
                $this->set_message(1051, '微信登录appid/appsecret为空');
            }
            Vendor("wxPayPubHelper.WxPayPubHelper");
            // 使用jsapi接口
            $jsApi = new \JsApi_pub($param_set['wx_appid'], $param_set['appsecret'], $request['code']);
            $wx = $jsApi->create_openid($param_set['wx_appid'], $param_set['appsecret'], $request['code']);
            //unionid如果开发者有在多个公众号，或在公众号、移动应用之间统一用户帐号的需求，需要前往微信开放平台（open.weixin.qq.com）绑定公众号后，才可利用UnionID机制来满足上述需
            if (empty($wx['unionid'])) {
                $this->set_message(1052, '请到微信开放平台（open.weixin.qq.com）绑定公众号');
            }
            $openid = $wx['unionid'];
            $auth = new WechatAuth($param_set['wx_appid'], $param_set['appsecret'], $wx['access_token']);
            $userInfo = $auth->getUserInfo($wx['openid']);
            $register_type = 3;
            $head_img = $userInfo['headimgurl'];
        } elseif ($request['login_type'] == "qq") {
            $register_type = 4;
            $userconfig = new UserConfigModel();
            $config = $userconfig->getSet('qq_login');
            $qq_parm['access_token'] = $request['accessToken'];
            $qq_parm['oauth_consumer_key'] = $config['config']['appid'];
            $qq_parm['openid'] = $config['config']['key'];
            $qq_parm['format'] = "json";
            $openid = get_union_id($request['accessToken']);
            if (empty($openid)) {
                $this->set_message(1054, '腾讯公司应用未打通 未将所有appid设置统一unionID');
            }
            $url = "https://graph.qq.com/user/get_user_info?" . http_build_query($qq_parm);
            $qq_url = json_decode(file_get_contents($url), true);
            $head_img = $qq_url['figureurl_qq_1 '];
        } elseif ($request['login_type'] == "yk" && isset($request['account'])) {
            $map['account'] = $request["account"];
            // $map['password'] = cmf_password($request['password']);
            $map['register_type'] = 0;
            $register_type = 0;
        } elseif ($request['login_type'] == "yk") {
            //验证注册ip
            if(!checkregiserip()){
                $this->set_message(1074, '暂时无法注册，请联系客服');
            }
            $register_type = 0;
            //$head_img = cmf_get_domain() . '/upload/sdk/logoo.png';
        }
        //查询用户名是否存在
        $map['unionid'] = $openid;
        if ($request['login_type'] == "yk" && isset($request['account'])) {
            unset($map['unionid']);
            $map['account'] = $request["account"];
            $map['register_type'] = 0;
        } elseif ($request['login_type'] == "yk") {
            unset($map['unionid']);
            $map['id'] = -1;
        }
        $usermodel = new UserModel();
        if($map['id'] == -1){
            $data = [];
        }else{
            $data = $usermodel->field('id,account')->where($map)->find();
        }
        $data = empty($data)?[]:$data->toArray();
        if (empty($data)) {//注册
            do {
                $data['account'] = $request['login_type'] . '_' . sp_random_string();
                $account = $usermodel->field('id')->where(['account' => $data['account']])->find();
            } while (!empty($account));
            $data['password'] = sp_random_string(8);
            $data['nickname'] = $data['account'];
            $data['unionid'] = $openid;
            $data['game_id'] = $request['game_id'];
            $data['head_img'] = !empty($head_img) ? $head_img : '';//头像
            $data['game_name'] = $game_data['game_name'];
            $data['promote_id'] = !empty($request['promote_id']) ? $request['promote_id'] : 0;
            $data['register_way'] = 1;
            $data['register_type'] = $register_type;
            $data['type'] = 1;
            $data['equipment_num'] = $request['equipment_num'];
            $data['device_name'] = get_real_devices_name($request['device_name'])?:'';
            dump($data);
            $result = $usermodel->register($data,'sdk');
            if ($result == -1) {
                $this->set_message(1028, '注册失败');
            } else {
                //封禁判断-20210712-byh
                if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$result['id'],$request['equipment_num'],get_client_ip(),$type=1)){
                    $this->set_message(1005, '您当前被禁止登录，请联系客服');
                }

                if (!empty($request['is_simulator'])) {
                    //检查是否有模拟器登录权限
                    $lSimulator = new SimulatorLogic();
                    $checkArr = [];
                    $checkArr['user_id'] = $result['id'];
                    $checkArr['game_id'] = $request['game_id'];
                    if(!$lSimulator -> checkUser($checkArr)){
                        $this->set_message(1040, '不允许模拟器内登录');
                    }
                }

                $this->add_user_play($result, $request,1);
                //创建小号
                $userlogic = new UserLogic();
                $userlogic->add_small($result,$data['game_id'],$result['account'].'_@_1',$result['account'].'@小号1',$data['sdk_version']);
                $small_list = $this->small_list($result['id'], $data['game_id']);
                // 是否显示选择小号入口
                $sdk_set = cmf_get_option('sdk_set');
                $need_choose_smallaccount_switch = $sdk_set['need_choose_smallaccount_switch'] ?? 1; // 0 关闭 1 开启

                $res_msg = array(
                    "user_id" => $result["id"],
                    "account" => $result["account"],
                    "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                    "is_platform" => $result['is_platform'],
                    'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                    'sex' => $result['sex'],
                    'password' => $register_type == 0? $data['password']:'',
                    "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                    "extra_param" => request()->domain(),//下单时需要原样返回
                    "age_status" =>0,
                    "birthday" => "",
                    "small_list"=>$small_list,
                    "talking_code"=>cmf_random_string(22),
                    'is_open_small_account'=>$need_choose_smallaccount_switch
                );
                $this->set_message(200,"登录成功",$res_msg);
            }
        } else {
            //第三方登录
            $request['type'] = 1;
            if (empty($request['account'])) {
                $request['account'] = $data['account'];
            }
            $result = $usermodel->oauth_login($request,'sdk');
            switch ($result) {
                case 1005:
                    $this->set_message(1005, '用户名不存在');
                    break;
                case 1007:
                    $this->set_message(1007, '账号被锁定');
                    break;
                default:
                    //封禁判断-20210712-byh
                    if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$result['id'],$request['equipment_num'],get_client_ip(),$type=1)){
                        $this->set_message(1005, '您当前被禁止登录，请联系客服');
                    }

                    if (!empty($request['is_simulator'])) {
                        //检查是否有模拟器登录权限
                        $lSimulator = new SimulatorLogic();
                        $checkArr = [];
                        $checkArr['user_id'] = $result['id'];
                        $checkArr['game_id'] = $request['game_id'];
                        if(!$lSimulator -> checkUser($checkArr)){
                            $this->set_message(1040, '不允许模拟器内登录');
                        }
                    }

                    $this->add_user_play($result, $request);
                    $small_list = $this->small_list($result['id'], $request['game_id']);
                    // 是否显示选择小号入口
                    $sdk_set = cmf_get_option('sdk_set');
                    $need_choose_smallaccount_switch = $sdk_set['need_choose_smallaccount_switch'] ?? 1; // 0 关闭 1 开启

                    $res_msg = array(
                        "user_id" => $result["id"],
                        "account" => $result["account"],
                        "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                        "is_platform" => $result['is_platform'],
                        'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                        'sex' => $result['sex'],
                        "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                        "extra_param" => request()->domain(),//下单时需要原样返回
                        "age_status" =>$result['age_status'],
                        "birthday" => get_birthday_by_idcard($result['idcard']),
                        "small_list"=>$small_list,
                        "talking_code"=>cmf_random_string(22),
                        'is_open_small_account'=>$need_choose_smallaccount_switch
                    );
                    break;
            }
            $this->set_message(200,"登录成功",$res_msg);
        }
    }

    /**
     * [添加玩家账号]
     * @param array $user
     * @author 郭家屯[gjt]
     */
    private function add_user_play($user = array(), $request = array(),$register = 0)
    {
        //查询是否存在账号
        $game = Cache::get('sdk_game_data'.$request['game_id']);
        $map["game_id"] = $request["game_id"];
        $map["user_id"] = $user["id"];
        $model = new UserPlayModel();
        $model->login($map,$user,$game,$register);
    }

    /**
     * [账号注册]
     * @author 郭家屯[gjt]
     */
    public function register()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        //验证验证码
        $sdk_verify = get_game_sdk_verify($data['game_id']);
        if ($sdk_verify) {
            $vResult = $this -> secondValidate($data[GeetestLib::GEETEST_CHALLENGE], $data[GeetestLib::GEETEST_VALIDATE], $data[GeetestLib::GEETEST_SECCODE]);
            if (false === $vResult) {
                $this -> set_message(1001, "验证码验证失败");
            }
        }
        //封禁判断-20210712-byh
        if(!judge_user_ban_status($data['promote_id'],$data['game_id'],'',$data['equipment_num'],get_client_ip(),$type=2)){
            $this->set_message(1005, '您当前被禁止注册，请联系客服');
        }

        //验证注册ip
        if(!checkregiserip()){
            $this->set_message(1074, '暂时无法注册，请联系客服');
        }
        if (empty($data['account'])) {
            $this->set_message(1009, '账号名称不能为空');
        }
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
        if (!cmf_check_mobile($data['account']) && !preg_match($pattern, $data['account']) && check_account($data['account']) == 1011) {
            $this->set_message(1011, '账号名称格式错误');
        }
        if (checkAccount($data['account'])) {
            $this->set_message(1013, '账号名称已存在');
        }
        if (empty($data['password'])) {
            $this->set_message(1010, '账号密码不能为空');
        }
        if (check_password($data['password']) == 1012) {
            $this->set_message(1012, '账号密码格式错误');
        }
        switch ($data['type']) {
            case 1:
                //普通账号注册
                if ($data['repassword'] != $data['password']) {
                    $this->set_message(1013, '确认密码和密码不匹配');
                }
                $data['register_type'] = 1;
                break;
            case 2:
                //手机号注册
                $this->sms_verify($data['account'], $data['code']);
                $data['phone'] = $data['account'];
                $data['register_type'] = 2;
                break;
            case 3:
                //邮箱注册
                $this->verify_email($data['account'], $data['code']);
                $data['email'] = $data['account'];
                $data['register_type'] = 7;
                break;
            default:
                $this->set_message(1008, '未知注册类型');
        }
        $data['register_way'] = 1;
        //$data['promote_account'] = get_promote_name($data['promote_id']);
        //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        $game = Cache::get('sdk_game_data'.$data['game_id']);
        $data['game_name'] = $game['game_name'];
        $data['type'] = 1;
        $model = new UserModel();
        $result = $model->register($data,'sdk');
        if ($result == -1) {
            $this->set_message(1028, '注册失败');
        } else {

            if (!empty($data['is_simulator'])) {
                //检查是否有模拟器登录权限
                $lSimulator = new SimulatorLogic();
                $checkArr = [];
                $checkArr['user_id'] = $result['id'];
                $checkArr['game_id'] = $data['game_id'];
                if (!$lSimulator -> checkUser($checkArr)) {
                    $this -> set_message(1040, '不允许模拟器内登录');
                }
            }

            $this->add_user_play($result, $data,1);
            //创建小号
            $userlogic = new UserLogic();
            $userlogic->add_small($result,$data['game_id'],$result['account'].'_@_1',$result['account'].'@小号1',$data['sdk_version']);
            $small_list = $this->small_list($result['id'], $data['game_id']);
            // 是否显示选择小号入口
            $sdk_set = cmf_get_option('sdk_set');
            $need_choose_smallaccount_switch = $sdk_set['need_choose_smallaccount_switch'] ?? 1; // 0 关闭 1 开启

            $res_msg = array(
                    "user_id" => $result["id"],
                    "account" => $result["account"],
                    "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                    'head_img' => empty($result['head_img']) ? '' : $result['head_img'],
                    'sex' => $result['sex'],
                    "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                    "extra_param" => request()->domain(),//下单时需要原样返回
                    "age_status" =>0,
                    "birthday" => "",
                    "small_list"=>$small_list,
                    "talking_code"=>cmf_random_string(22),
                    'is_open_small_account'=>$need_choose_smallaccount_switch
            );
        }
        $this->set_message(200,"登录成功",$res_msg);
    }

    /**
     * [找回密码]
     * [优先返回短信验证]
     * @author 郭家屯[gjt]
     */
    public function forget_account()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        if (empty($data['account'])) {
            $this->set_message(1009, '账号名称不能为空');
        }
        $pattern = '/^[A-Za-z0-9-_]+$/';
        if (!preg_match($pattern, $data['account'])&&!filter_var($data['account'], FILTER_VALIDATE_EMAIL)) {
            $this->set_message(1011, '账号名称格式错误');
        }
        $model = new UserModel();
        // 简化版平台账号判断
        if(is_third_platform($data['promote_id'])){
            $UserLogic = new WebUserLogic($data['promote_id']);
            $userId = $UserLogic->get_user_info( $data['account'],4);
            if($userId > 0){
                $user = $model->where('account', $data['account'])->field('id,account,phone,email,lock_status')->find();
            }else{
                $user = [];
            }
        }else{
            $user = $model->where('account', $data['account'])->field('id,account,phone,email,lock_status')->find();
        }
        if (empty($user)) {
            $this->set_message(1005, '用户名不存在');
        }
        if($user['lock_status'] == 0){
            $this->set_message(1007, '账号被锁定');
        }
        $user = $user->toArray();
        if (empty($user['phone']) && empty($user['email'])) {
            $this->set_message(1029, '账号未绑定手机号或邮箱');
        }
        $res_msg = array(
            "user_id" => $user["id"],
            "account" => $user["account"],
            "phone" => $user['phone'],
            "email" => $user['email'],
        );
        $this->set_message(200,"获取成功",$res_msg);
    }

    /**
     * [验证码验证]
     * @author 郭家屯[gjt]
     */
    public function checkCode()
    {
        $user = json_decode(base64_decode(file_get_contents("php://input")), true);
        $user = get_real_promote_id($user);
        if ($user['code_type'] == 1) {
            //短信验证
            $this->sms_verify($user['phone'], $user['code'], 3);
        } elseif ($user['code_type'] == 2) {
            //邮箱验证
            $this->verify_email($user['email'], $user['code']);
        } else {
            $this->set_message(1030, "未知找回密码方式");
        }
        $res_msg = array(
            "account" => $user["phone"]?:$user['email'],
        );
        $this->set_message(200,"验证成功",$res_msg);
    }

    /**
     * [修改忘记密码接口]
     * @author 郭家屯[gjt]
     */
    public function forget_password()
    {
        $user = json_decode(base64_decode(file_get_contents("php://input")), true);
        $user = get_real_promote_id($user);
        #验证短信验证码
        if ($user['repassword'] != $user['password']) {
            $this->set_message(1013, '确认密码和密码不匹配');
        }
        if (empty($user['account'])) {
            $this->set_message(1031, "找回密码失败");
        }
        $model = new UserModel();
        // 简化版玩家修改密码
        if(is_third_platform($user['promote_id'])){
            $UserLogic = new WebUserLogic($user['promote_id']);
            $userId = $UserLogic->get_user_info($user['account'],4);
            if($userId > 0){
                $save['password'] = $user['password'];
                $result = $UserLogic->update_user_info($userId,$save);
            }else{
                $result = false;
            }
        }else{
            $user['user_id'] = get_user_entity($user['account'],true,'id')['id'];
            $result = $model->updatePassword($user['user_id'], $user['password']);
        }
        if ($result == true) {
            $this->set_message(200, "找回密码成功");
        } else {
            $this->set_message(1031, "找回密码失败");
        }

    }

    /**
     * 添加游戏角色数据
     * @param $request
     */
    public function save_user_play_info()
    {
        //设置请求频率
        if (!check_ip_access_frequency(30, 1)) {
            $this -> set_message(200, '请求过于频繁,请稍后再试');
        }
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        if($request['server_id'] =='' ){
            $this->set_message(1064,'请上传区服id');
        }
        if($request['server_name']==''){
            $this->set_message(1065,'请上传区服名称');
        }
        if($request['game_player_name']==''){
            $this->set_message(1066,'请上传角色名称');
        }
        if($request['role_level']==''){
            $this->set_message(1068,'请上传角色等级');
        }
        $model = new UserPlayInfoModel();
        $result = $model->add_user_play_info($request);
        if($result){
            if(is_third_platform($request['promote_id'])){
                try {
                    $UserLogic = new \app\webplatform\logic\UserLogic($request['promote_id']);
                    $UserLogic->saveUserPlayInfo($request);
                }catch (\Exception $e){}
            }
            $this->set_message(200, "上传角色成功");
        }else{
            $this->set_message(1069, "上传角色失败");
        }
    }

    /**
     * [修改用户信息]
     * @author 郭家屯[gjt]
     */
    public function user_update_data()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $userModel = new UserModel();
        switch ($data['code_type']) {
            case 'account':
                $checkaccount = $userModel->field('id')->where('account',$data['account'])->find();
                if($checkaccount){
                    $this->set_message(1077, "账号名称已存在");
                }
                $save['account'] = $data['account'];
                $save['nickname'] = $data['account'];
                $save['password'] = cmf_password($data['password']);
                $save['token'] = think_encrypt(json_encode(array('uid' => $data['user_id'], 'password' => cmf_password($data['password']))),1);
                $save['register_type'] = 1;
                break;
            case 'rephone':
                $this->sms_verify($data['phone'], $data['code']);
                $save['phone'] = '';
                break;
            case 'phone':
                // 简化版玩家查询手机号是否绑定
                if(is_third_platform($data['promote_id'])){
                    $UserLogic = new WebUserLogic($data['promote_id']);
                    $userId = $UserLogic->get_user_info($data['phone'],2);
                    if($userId > 0){
                        $this->set_message(1014, "该手机号已被注册或绑定过");
                    }
                }else{
                    $checkphone = $userModel->field('id')->where('phone|account', $data['phone'])->find();
                    if(!empty($checkphone)){
                        $this->set_message(1014, "该手机号已被注册或绑定过");
                    }
                }
                $this->sms_verify($data['phone'], $data['code']);
                $save['phone'] = $data['phone'];
                $userModel->task_complete($data['user_id'],'bind_phone',$save['phone']);//绑定任务
                break;
            case 'email':
                if(is_third_platform($data['promote_id'])){
                    $UserLogic = new WebUserLogic($data['promote_id']);
                    $userId = $UserLogic->get_user_info($data['email'],3);
                    if($userId > 0){
                        $this->set_message(1014, "该手机号已被注册或绑定过");
                    }
                }else{
                    $checkemail = $userModel->where('email|account', $data['email'])->find();
                    if(!empty($checkemail)){
                        $this->set_message(1022, "该邮箱已被注册或绑定过");
                    }
                }
                $this->verify_email($data['email'], $data['code']);
                $save['email'] = $data['email'];
                $userModel->task_complete($data['user_id'],'bind_email',$save['email']);//绑定任务
                break;
            case 'reemail':
                $this->verify_email($data['email'], $data['code']);
                $save['email'] = '';
                break;
            case 'nickname':
                $nk = $userModel->where('nickname', $data['nickname'])->field('id')->find();
                if ($nk) {
                    $this->set_message(1036, '昵称已被使用');
                }
                $save['nickname'] = $data['nickname'];
                break;
            case 'pwd' :
                if ($data['old_password'] == $data['password']) {
                    $this->set_message(1038, '新密码与原始密码不能相同');
                }
                $user = get_user_entity($data['user_id'],false,'password');
                if (!xigu_compare_password($data['old_password'], $user['password'])) {
                    $this->set_message(1039, '密码错误');
                }
                $save['password'] = cmf_password($data['password']);
                $save['token'] = think_encrypt(json_encode(array('uid' => $data['user_id'], 'password' => cmf_password($data['password']))),1);
                break;
            default:
                $this->set_message(1035, "修改信息不明确");
                break;
        }
        // 简化版玩家修改信息
        if(is_third_platform($data['promote_id']) && (isset($save['phone']) || isset($save['email']))){
            $UserLogic = new WebUserLogic($data['promote_id']);
            $res = $UserLogic->update_user_info($data['user_id'],$save);
            if(!$res){
                $this->set_message(1037, '修改失败');
            }
        }
        $result = $userModel->update_user_info($data['user_id'], $save);
        if ($result) {
            $json = [];
            if ($data['code_type'] == 'pwd' || $data['code_type'] == 'account') {
                $json['token'] = password_hash($save['token'],PASSWORD_DEFAULT);
            }
            $this->set_message(200,"修改成功",$json);
        } else {
            $this->set_message(1037, '修改失败');
        }
    }

    /**
     * [获取绑币列表]
     * @author 郭家屯[gjt]
     */
    public function get_user_bind_coin()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $model = new UserModel();
        $lists = $model->getUserBindCoin($request['user_id'], $request['p'], $request['row']);
        $this->set_message(200,"获取成功",$lists);
    }

    /**
     * [实名认证]
     * @author 郭家屯[gjt]
     */
    public function idcard_change()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if (empty($request['user_id']) || empty($request['idcard']) || empty($request['real_name'])) {
            $this->set_message(1041, "用户数据异常");
        }
        $data['idcard'] = $request['idcard'];
        $data['real_name'] = $request['real_name'];
        if (substr($data['idcard'], -1) === 'X') {
            $data['idcard'] = str_replace('X', 'x', $data['idcard']);
        }
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $this->set_message(1042, "身份证号码填写不正确！");
        }

        $userconfig = new UserConfigModel();
        //实名认证设置
        $config = $userconfig->getSet('age');

        $userModel = new UserModel();

        //判断是否走国家实名认证系统
        if(get_game_age_type($request['game_id']) == 2){
            $logic = new AntiaddictionLogic($request['game_id']);
            $re = $logic->checkUser($request['real_name'],$request['idcard'],$request['user_id'],$request['game_id']);
            if($re['status'] == 1){//认证成功
                if (is_adult($data['idcard'])) {
                    $data['age_status'] = 2;
                    $data['anti_addiction'] = 1;
                } else {
                    $data['age_status'] = 3;
                }
            }elseif($re['status'] == 2){//认证中
                $data['age_status'] = 4;
            }else{//认证失败
                $this->set_message(2002,"认证失败，请重新提交认证");
            }
            $result = $userModel->where('id', $request['user_id'])->update($data);
            $userModel->task_complete($request['user_id'],'auth_idcard',$data['idcard']);//绑定任务
            if (!$result) {
                $this->set_message(2002, "认证失败，请重新提交认证");
            }
            if($re['status'] == 1){
                $this->set_message(200,"实名认证成功",$data);
            }else{
                $this->set_message(2001,"提交成功，请等待认证结果",$data);
            }
        }else{
            if ($config['config']['can_repeat'] != '1') {
                $cardd = $userModel -> where('idcard', $data['idcard']) -> field('id') -> find();
                if ($cardd) {
                    $this -> set_message(1043, "身份证号码已被使用！");
                }
            }
            if (($config['status'] == 0) || ($config['status'] == 1 && $config['config']['ali_status'] == 0)) {
                //判断年龄是否大于16岁
                if (is_adult($data['idcard'])) {
                    $data['age_status'] = 2;
                } else {
                    $data['age_status'] = 3;
                }
            } else {
                //真实判断身份证是否有效
                $re = age_verify($data['idcard'], $data['real_name'], $config['config']['appcode']);
                switch ($re) {
                    case -1:
                        $this->set_message(1044, "短信数量已经使用完！");
                        break;
                    case -2:
                        $this->set_message(1045, "连接认证接口失败");
                        break;
                    case 0:
                        $this->set_message(1046, "认证信息错误");
                        break;
                    case 1://成年
                        $data['age_status'] = 2;
                        $data['anti_addiction'] = 1;
                        break;
                    case 2://未成年
                        $data['age_status'] = 3;
                        break;
                    default:
                }
            }
        }
        // 简化版玩家修改信息
        if(is_third_platform($request['promote_id']) && isset($data['idcard']) && isset($data['real_name'])){
            $UserLogic = new WebUserLogic($request['promote_id']);
            $res = $UserLogic->update_user_info($request['user_id'],$data);
            if(!$res){
                $this->set_message(1047, "认证失败，请重新提交认证");
            }
        }
        $result = $userModel->where('id', $request['user_id'])->update($data);
        $userModel->task_complete($request['user_id'],'auth_idcard',$data['idcard']);//绑定任务
        if (!$result) {
            $this->set_message(1047, "认证失败，请重新提交认证");
        }

        $this->set_message(200,"实名认证成功",$data);
    }

    /**
     * [支付宝芝麻信用]
     * @author 郭家屯[gjt]
     */
    public function alipay_zmxy()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $user_id = $request['user_id'];
        $game_id = $request['game_id'];
        $alimodel = new AlipayModel();
        $alipayauth = $alimodel->field('appid,status')->where('game_id', $game_id)->find();
        if (empty($alipayauth)) {
            $this->set_message(1048, '此游戏不支持支付宝快捷认证');
        }
        $alipayauth = $alipayauth->toArray();
        if ($alipayauth['status'] != 1) {
            $this->set_message(1049, '此游戏未开启支付宝快捷认证');
        }
        $appid = $alipayauth['appid'];
        Vendor('alipay.AopSdk');
        $c = new \AopClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $appid;
        $provate_url = ROOT_PATH . "/app/sdk/secretKey/alipay/rsa2_private_key.txt";
        if(!file_exists($provate_url)){
            $provate_url = ROOT_PATH . "/app/callback/secretKey/alipay/rsa2_private_key.txt";
        }
        $c->rsaPrivateKey = file_get_contents($provate_url);
        $c->format = "json";
        $c->charset = "utf-8";
        $c->signType = "RSA2";
        $public_url = ROOT_PATH . "/app/sdk/secretKey/alipay/alipay2_public_key.txt";
        if(!file_exists($public_url)){
            $public_url = ROOT_PATH . "/app/callback/secretKey/alipay/alipay2_public_key.txt";
        }
        $c->alipayrsaPublicKey = file_get_contents($public_url);
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.user.userinfo.share
        $request = new \ZhimaCustomerCertificationInitializeRequest();
        $transaction_id = 'ZGYD' . date('Ymdhis', time()) . sp_random_string(6);
        $request->setBizContent("{"
            . "\"transaction_id\":\"{$transaction_id}\","
            . "\"product_code\":\"w1010100000000002978\","
            . "\"biz_code\":\"SMART_FACE\","
            . "\"identity_param\":\"{}\","
            . "\"ext_biz_param\":\"{}\"" . "  }");
        $response = $c->execute($request);
        if ($response->zhima_customer_certification_initialize_response->code != 10000) {
            $this->set_message(1050, '授权失败' . $response->zhima_customer_certification_initialize_response->code);
        }
        $biz_no = $response->zhima_customer_certification_initialize_response->biz_no;
        $request_get_url = new \ZhimaCustomerCertificationCertifyRequest();
        $request_get_url->setBizContent("{\"biz_no\":\"{$biz_no}\"}");
        //返回地址
        $return_url = $_SERVER['HTTP_HOST'] . '/sdk/User/get_alipay_zmxy_return/user_id/' . $user_id . '/game_id/' . $game_id;
        // $request_get_url->setNotifyUrl("alipay://{$notify_url}");
        $request_get_url->setReturnUrl("alipay://{$return_url}");
        $responseurl = $c->pageExecute($request_get_url, 'GET');
        $result = ['url' => $responseurl, 'appid' => $appid];
        $this->set_message(200,"实名认证成功",$result);
    }

    /**
     * [芝麻信用回调]
     * @author 郭家屯[gjt]
     */
    public function get_alipay_zmxy_return()
    {
        $biz_content = json_decode($_GET['biz_content'], true);
        $biz_no = $biz_content['biz_no'];
        $user_id = $this->request->param('user_id');
        $game_id = $this->request->param('game_id');
        if ($user_id < 1) {
            $this->assign('result', 'fail');
            return $this->fetch();
        }
        $user_data = get_user_entity($user_id,false,'id');
        if (empty($user_data)) {
            $this->assign('result', 'fail');
            return $this->fetch();
        }
        $alimodel = new AlipayModel();
        $alipayauth = $alimodel->field('appid,status')->where(['game_id' => $game_id])->find();
        if (empty($alipayauth)) {
            $this->assign('result', 'fail');
            return $this->fetch();
        }
        $alipayauth = $alipayauth->toArray();
        if ($alipayauth['status'] != 1) {
            $this->assign('result', 'fail');
            return $this->fetch();
        }
        $appid = $alipayauth['appid'];
        Vendor('alipay.AopSdk');
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appid;
        $provate_url = ROOT_PATH . "/app/sdk/secretKey/alipay/rsa2_private_key.txt";
        if(!file_exists($provate_url)){
            $provate_url = ROOT_PATH . "/app/callback/secretKey/alipay/rsa2_private_key.txt";
        }
        $aop->rsaPrivateKey = file_get_contents($provate_url);
        $public_url = ROOT_PATH . "/app/sdk/secretKey/alipay/alipay2_public_key.txt";
        if(!file_exists($public_url)){
            $public_url = ROOT_PATH . "/app/callback/secretKey/alipay/alipay2_public_key.txt";
        }
        $aop->alipayrsaPublicKey = file_get_contents($public_url);
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $request = new \ZhimaCustomerCertificationQueryRequest ();
        $request->setBizContent("{" .
            "\"biz_no\":\"{$biz_no}\"" .
            "  }");
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $zfbuserjson = $result->$responseNode->identity_info;
        $zfbuserarr = json_decode($zfbuserjson, true);
        $zfbuser = $zfbuserarr['user_id'];
        if (!empty($resultCode) && $resultCode == 10000) {
            $save['age_status'] = 2;
            $save['third_authentication'] = 1;
            $save['real_name'] = $biz_no;
            $save['idcard'] = $zfbuser;
            $save['anti_addiction'] = 1;
            $usermodel = new UserModel();
            $result = $usermodel->where('id', $user_id)->update($save);
            if ($result) {
                $this->assign('result', 'success');
            } else {
                $this->assign('result', 'fail');
            }
        } else {
            $this->assign('result', 'fail');
        }
        return $this->fetch();
    }

    /**
     * 获取支付宝实名认证结果
     * @return   third_authentication  0失败 1成功
     * author: gjt
     */
    public function get_auth_result()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $user = get_user_entity($request['user_id'],false,'third_authentication');
        $this->set_message(200,'获取成功', ['third_authentication' => $user['third_authentication']]);
    }

    /**
     * [游戏下线接口]
     * @author 郭家屯[gjt]  前台回到后台、kill、退出
     */
    public function get_down_time()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        //防沉迷开关
        $map['time'] = date('Y-m-d');
        $map['user_id'] = $request['user_id'];
        $map['game_id'] = $request['game_id'];
        $model = new UserGameLoginModel();
        $result = $model->get_down($map,$request);
        $this->set_message(200,'下线成功');
    }

    /**
     * 实名认证信息(防沉迷)   获得传递过来的UID，返回该玩家是否已经通过审核
     * @return mixed 登录、后台回到前台
     */

    public function return_age()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $user = get_user_entity($request['user_id'],false,'age_status,promote_id');
        $configmodel = new UserConfigModel();

        //是否强制实名认证
        $game = get_game_entity($request['game_id'],'is_force_real');
        //实名认证
        $age = Cache::get('sdk_return_age');
        if(empty($age)){
            $age = $configmodel->where('name', 'age')->field('config,status')->find()->toArray();
            Cache::set('sdk_return_age',$age,86400);
        }
        if($age['status']==0){
            $data['auth'] = 0;//关闭实名认证
        }else{
            if($game['is_force_real']==1){
                $data['auth'] = 2;//强制实名认证
            }else{
                $data['auth'] = 1;//开启实名认证
            }
        }

        //$data['auth'] = $age['status'];
        if($user['age_status']=='4'){
            $data['age_status'] = 3; //审核中走未成年逻辑
        }else{
            $data['age_status'] = $user['age_status'];
        }
        $data['contents_off'] = json_decode($age['config'], true)['contents_off']?:'根据国家关于《防止未成年人沉迷网络游戏的通知》要求，平台所有玩家必须完成实名认证，否则将会被禁止进入游戏。';
        //防沉迷
        $age_prevent = Cache::get('sdk_age_prevent');
        if(empty($age_prevent)){
            $age_prevent = $configmodel->where('name', 'age_prevent')->field('config,status')->find()->toArray();
            Cache::set('sdk_age_prevent',$age_prevent,86400);
        }
        $age_prevent_config = json_decode($age_prevent['config'], true);
        $data['on_off'] = $age_prevent['status'];
        $data['hours_off_one'] = $age_prevent_config['hours_off_one'];
        $data['contents_one'] = $age_prevent_config['contents_one'];
        $data['hours_off_two'] = $age_prevent_config['hours_off_two'];
        $data['contents_two'] = $age_prevent_config['contents_two'];
        $data['hours_cover'] = $age_prevent_config['hours_cover']; //hours_cover 防沉迷清除时间
        $data['bat'] = empty($age_prevent_config['down_time']) ? 0 : $age_prevent_config['down_time'];
        $data['prevent_way'] = empty($age_prevent_config['way']) ? '2':$age_prevent_config['way']; //是否走官方防沉迷规则
        //查询未成年玩家剩余在线时间
        $surplusSecond = HolidayUtil ::checkPlayGameStatus();
        if (false === $surplusSecond) {
            $data['surplus_second'] = 0;
        } else {
            $data['surplus_second'] = $surplusSecond;
        }
        //修改游戏记录
        $map['time'] = date('Y-m-d');
        $map['user_id'] = $request['user_id'];
        $map['game_id'] = $request['game_id'];
        if($data['on_off'] == 0){
            $data['play_time'] = '0';
        }else{
            $model = new UserGameLoginModel();
            $data['play_time'] = $model->login($map,$data,$user,$request);
        }
        if($request['get_talking_code'] != '0'){
            $data['talking_code'] = cmf_random_string(22);
        }
        //获取未读消息状态
        $model = new NoticeModel();
        $noticemark = $model->getNoticeMark(['user_id'=>$request['user_id'],'game_id'=>$request['game_id']]);
        $data['read_status'] = $noticemark['read_status'];
        $this->set_message(200,'上线成功',$data);
    }


    /**
     * @函数或方法说明
     * @设备在线 游戏设备表 每日打开记录一次
     * @author: 郭家屯
     * @since: 2019/7/16 9:24
     */
    public function equipment_login()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['equipment_num'])){
            $this->set_message(200,'上线成功');
        }
        //游戏设备表数据更新
        $map['equipment_num'] = $request['equipment_num'];
        $map['promote_id'] = $request['promote_id'];
        $map['game_id'] = $request['game_id'];
        $Gamemodel = new EquipmentGameModel();
        $result = $Gamemodel->login($map,$request);
        //设备在线时间更新
        $map['time'] = date('Y-m-d');
        $loginModel = new EquipmentLoginModel();
        $result1 = $loginModel->login($map,$request);
        $this->set_message(200,'上线成功');
    }

    /**
     * @函数或方法说明
     * @设备下线 前台回到后台、kill、退出
     * @author: 郭家屯
     * @since: 2019/7/16 9:25
     */
    public function equipment_down()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['equipment_num'])){
            $this->set_message(200,'下线成功');
        }
        $map['time'] = date('Y-m-d');
        $map['equipment_num'] = $request['equipment_num'];
        $map['promote_id'] = $request['promote_id'];
        $map['game_id'] = $request['game_id'];
        $loginmodel = new EquipmentLoginModel();
        $result = $loginmodel->down($map,$request);
        $this->set_message(200,'下线成功');
    }

    /**
     * [获取域名]
     * @author 郭家屯[gjt]
     */
    public function get_domain()
    {
        $data['url'] = $_SERVER['HTTP_HOST'];
        $this->set_message(200,'获取成功',$data);
    }

    /**
     *短信验证
     */
    private function sms_verify($phone = "", $code = "", $type = 2)
    {
        $session = session($phone);
        if (empty($session)) {
            $this->set_message(1017, "请先获取验证码");
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");

        if ($time > 180) {//$tiem > 60
            $this->set_message(1018, "验证码已失效，请重新获取");
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            $this->set_message(1019, "验证码不正确，请重新输入");
        }
        if ($type == 1) {
            $this->set_message(200, "正确");
        } else {
            return true;
        }

    }

    /**
     *短信发送
     */

    public function send_sms()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $phone = $request['phone'];
        $account = $request['account'];
        if (!cmf_check_mobile($phone)) {
            $this->set_message(1020, "手机号码格式错误");
        }
        $userModel = new UserModel();
        if ($request['reg'] == 1) { /* 注册检查 */
            if(is_third_platform($request['promote_id'])){
                $UserLogic = new WebUserLogic($request['promote_id']);
                $userId = $UserLogic->get_user_info($phone,2);
                if($userId > 0){
                    $this->set_message(1014, "该手机号已被注册或绑定过");
                }
            }else{
                $user = $userModel->where('phone|account', $phone)->find();
                if ($user) {
                    $this->set_message(1014, "该手机号已被注册或绑定过");
                }
            }
        } elseif ($request['reg'] == 2) {/* 忘记密码检查 */
            if(is_third_platform($request['promote_id'])){
                $UserLogic = new WebUserLogic($request['promote_id']);
                $userId = $UserLogic->get_user_info($phone,2);
                if(!$userId){
                    $this->set_message(1005, "用户不存在");
                }
            }else {
                $user = $userModel->where('account', $account)->where('phone', $phone)->find();
                if (empty($user)) {
                    $this->set_message(1005, "用户名不存在");
                }
            }

        } elseif ($request['reg'] == 3) {/* 解绑绑定检查 */
            if(is_third_platform($request['promote_id'])){
                $UserLogic = new WebUserLogic($request['promote_id']);
                $userId = $UserLogic->get_user_info($phone,2);
                if(!$userId){
                    $this->set_message(1005, "用户不存在");
                }
            }else {
                $user = $userModel->where('account', $account)->where('phone', $phone)->find();
                if (empty($user)) {
                    $this->set_message(1005, "用户名不存在");
                }
            }
        } elseif ($request['reg'] == 4) {/* 绑定手机检查  */
            if(is_third_platform($request['promote_id'])){
                $UserLogic = new WebUserLogic($request['promote_id']);
                $userId = $UserLogic->get_user_info($phone,2);
                if($userId > 0){
                    $this->set_message(1014, "该手机号已被注册或绑定过");
                }
            }else {
                $user = $userModel->where('phone', $phone)->find();
                if ($user) {
                    $this->set_message(1014, "该手机号已被绑定过");
                }
            }
        } else {
            $this->set_message(1059, "未知发送类型");
        }
        $msg = new MsgModel();
        $data = $msg::get(1);
        if (empty($data)) {
            $this->set_message(1016, "没有配置短信发送");
        }
        $data = $data->toArray();
        if ($data['status'] == 1) {
            $xigu = new Xigu($data);
            sdkchecksendcode($phone, $data['client_send_max'], 2);
            $sms_code = session($phone);//发送没有session 每次都是新的
            $sms_rand = sms_rand($phone);
            $rand = $sms_rand['rand'];
            $new_rand = $sms_rand['rand'];
            /// 产生手机安全码并发送到手机且存到session
            $param = $rand . "," . '3';
            $result = json_decode($xigu->sendSM( $phone, $data['captcha_tid'], $param), true);
            $result['create_time'] = time();
            $result['pid'] = 0;
            $result['phone'] = $phone;
            $result['create_ip'] = get_client_ip();
            Db::table('tab_sms_log')->field(true)->insert($result);
            #TODO 短信验证数据
            if ($result['send_status'] == '000000') {
                $safe_code['code'] = $rand;
                $safe_code['phone'] = $phone;
                $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                $safe_code['delay'] = 3;
                $safe_code['create'] = $result['create_time'];
                session($phone, $safe_code);
                $this->set_message(200, "验证码发送成功");
            } else {
                $this->set_message(1015, "验证码发送失败，请重新获取");
            }
        } else {
            $this->set_message(1016, "没有配置短信发送");
        }
    }

    /**
     * [sdk验证邮箱]
     * @author 郭家屯[gjt]
     */
    private function verify_email($email, $code, $type = 2)
    {
        $code_result = $this->emailVerify($email, $code);
        if ($code_result == 1) {
            if ($type == 1) {
                $this->set_message(200,"验证成功");
            } else {
                return true;
            }
        } else {
            if ($code_result == 2) {
                $this->set_message(1025, "请先获取验证码");
            } elseif ($code_result == -98) {
                $this->set_message(1026, "验证码超时");
            } elseif ($code_result == -97) {
                $this->set_message(1027, "验证码不正确，请重新输入");
            }
        }
    }


    /**
     * @param $email
     * @param $code
     * @param int $time
     * @return int
     * 验证 邮箱验证码
     */
    private function emailVerify($email, $code, $time = 30)
    {
        $session = session($email);
        if (empty($session)) {
            return 2;
        } elseif ((time() - $session['create_time']) > $time * 60) {
            return -98;
        } elseif ($session['code'] != $code) {
            return -97;
        }
        session($email, null);
        return 1;
    }

    /**
     * 发送邮件验证码 注册传1 解绑传2 绑定传3
     */

    public function send_email()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $code_type = $data['code_type'];
        $email = $data['email'];
        $account = $data['account'];
        $pattern = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";

        if (!preg_match($pattern, $email)) {
            $this->set_message(1021, "邮箱地址格式错误");
        }
        $usermodel = new UserModel();
        if ($code_type == 1) {/* 注册 */
            if(is_third_platform($data['promote_id'])){
                $UserLogic = new WebUserLogic($data['promote_id']);
                $userId = $UserLogic->get_user_info($email,3);
                if($userId > 0){
                    $this->set_message(1022, "该邮箱已被注册或绑定过");
                }
            }else {
                $user = $usermodel->where('email|account', $email)->find();
                if ($user) {
                    $this->set_message(1022, "该邮箱已被注册或绑定过");
                }
            }
        } elseif ($code_type == 2) {/* 忘记密码 */
            if(is_third_platform($data['promote_id'])){
                $UserLogic = new WebUserLogic($data['promote_id']);
                $userId = $UserLogic->get_user_info($email,3);
                if(!$userId){
                    $this->set_message(1022, "用户名不存在");
                }
            }else {
                $user = $usermodel->where('account', $account)->where('email', $email)->find();
                if (empty($user)) {
                    $this->set_message(1005, "用户名不存在");
                }
            }
        } elseif ($code_type == 3) {/* 解绑 */
            if(is_third_platform($data['promote_id'])){
                $UserLogic = new WebUserLogic($data['promote_id']);
                $userId = $UserLogic->get_user_info($email,3);
                if(!$userId){
                    $this->set_message(1022, "用户名不存在");
                }
            }else {
                $user = $usermodel->where('account', $account)->where('email', $email)->find();
                if (empty($user)) {
                    $this->set_message(1005, "用户名不存在");
                }
            }
        } elseif ($code_type == 4) {/* 绑定 */
            if(is_third_platform($data['promote_id'])){
                $UserLogic = new WebUserLogic($data['promote_id']);
                $userId = $UserLogic->get_user_info($email,3);
                if($userId>0){
                    $this->set_message(1022, "该邮箱已被注册或绑定过");
                }
            }else {
                $user = $usermodel->where('email', $email)->find();
                if ($user) {
                    $this->set_message(1022, "该邮箱已被注册或绑定过");
                }
            }
        }
        $session = session($data['email']);
        if (!empty($session) && (time() - $session['create_time']) < 60) {
            $this->set_message(1024, "验证码发送过于频繁，请稍后再试");
        }
        $email = $data['email'];
        $code = rand(100000, 999999);
        $smtpSetting = cmf_get_option('email_template_verification_code');
        $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
        $result = cmf_send_email($data['email'], $smtpSetting['subject'], $smtpSetting['template']);
        if ($result['error'] == 0) {
            session($email, ['code' => $code, 'create_time' => time()]);
            $this->set_message(200, "发送成功");
        } else {
            $this->set_message(1023, "发送失败，请检查邮箱地址是否正确");
        }
    }

    /**
     * @函数或方法说明
     * @获取实名认证状态
     * @author: 郭家屯
     * @since: 2019/11/29 9:31
     */
    public function get_real_auth_status(){
        $data['real_pay_status'] = get_user_config_info('age')['real_pay_status']?:0;
        $data['real_pay_msg'] = get_user_config_info('age')['real_pay_msg']?:'根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！';
        $this->set_message(200, "获取成功", $data);
    }

    /**
     * 用户协议
     *
     * @return mixed
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 15:20
     */
    public function protocol()
    {
        $data = Cache::get('sdk_post_title');
        if(empty($data)){
            $portalPostModel = new \app\site\model\PortalPostModel;
            $data = $portalPostModel->where('id', 27)->find();
            Cache::set('sdk_post_title',$data,86400);
        }
        $this->assign('data', $data);

        return $this->fetch();

    }

    /**
     * @函数或方法说明
     * @获取渠道折扣
     * @author: 郭家屯
     * @since: 2020/1/15 11:42
     */
    public function get_user_discount()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $game_id = $request['game_id'];
        $user_id = $request['user_id'];
        $user = get_user_entity($user_id,false,'promote_id,parent_id');
        $promote_id = $user['promote_id'];
        $paylogic = new PayLogic();
        $data = $paylogic->get_discount($game_id,$promote_id,$user_id);
        $data['discount'] = $data['discount']*10;
        $this->set_message(200, "获取成功", $data);
    }

    /**
     * 获取授权码
     * @descript author
     * @author yyh
     * @since 2020-05-26
     */
    public function get_auth_code()
    {
        if(!$this->request_data['user_id']){
            $this->set_message(1003, "账号信息丢失");
        }
        $code = $this->request_data['user_token'].'_xigu_'.$this->request_data['game_id'].'_xigu_'.$this->request->ip().'_xigu_'.sp_random_string(10);
        $data['code'] = $code;
        $data['ip'] = $this->request->ip();
        $data['create_time'] = time();
        $data['update_time'] = time();
        $res = Db::table('tab_user_auth_code')->insertGetId($data);
        if($res){
            $this->set_message(200,'授权码获取成功',$code);
        }else{
            $this->set_message(1085,'授权码获取失败');
        }
    }

    /**
     * @函数或方法说明
     * @author: 郭家屯
     * @since: 2020/9/30 15:11
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    // 1. 此功能因为兼容以前的版本所以保留
    // 2. 接口开关信息已在 9.5.5版 本提取到get_switch()(获取各种开端 独立接口)
    // 3. 后期极有可能废除 (by wjd 2021-5-17 10:28:04)
    public function get_switch_status()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        //获取试玩任务
        $model = new UserTplayModel();
        $map['status'] = 1;
        $map['start_time'] = ['lt',time()];
        $map['game_id'] = $request['game_id'];
        $task = $model->field('id')->where($map)->find();
        $data['task_id'] = $task['id']?1:0;

        $this->set_message(200, "获取成功",$data);
    }

    /**
     * @函数或方法说明
     * @获取实名认证结果
     * @author: Juncl
     * @time: 2021/03/04 10:32
     */
    public function get_age_result(){
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['game_id']) || empty($request['user_id'])){
            $this->set_message(200, "参数不能为空");
        }
        if(get_game_age_type($request['game_id']) != 2){
            $this->set_message(200, "该游戏未开启国家实名认证");
        }
        $logic = new AntiaddictionLogic($request['game_id']);
        $result = $logic->queryUser($request['user_id'],$request['game_id']);
        if($result['status'] == 1){
            $this->set_message(200, "实名认证成功");
        }elseif($result['status'] == 2){
            $this->set_message(2001, "提交成功，请等待认证结果");
        }else{
            $this->set_message(2001, "认证失败，请重新提交认证");
        }
    }

    public function collect_login(){
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['game_id']) || empty($request['user_id']) || empty($request['talking_code'])){
            $this->set_message(200, "参数不能为空");
        }
        if(get_game_age_type($request['game_id']) != 2){
            $this->set_message(200, "该游戏未开启国家实名认证");
        }
        if($request['login_type'] != 0 && $request['login_type'] != 1){
            $this->set_message(200, "用户行为错误");
        }
        $logic = new AntiaddictionLogic($request['game_id']);
        $result = $logic->collectUser($request['user_id'],$request['talking_code'],$request['login_type'],$request['equipment_num'],$request['game_id']);
        $this->set_message(200, "上报成功",$result);
    }

    /**
     * 查询SDK内玩家(渠道/设备号/ip)是否被封禁
     * by:byh-2021-7-16 09:49:08
     */
    public function get_ban_status()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['game_id']) || empty($request['ban_type'])){
            $this->set_message(200, "参数不能为空");
        }
        $msg = [
            'success',//0
            '您当前被禁止登录，请联系客服',//1登录封禁
            '您当前被禁止注册，请联系客服',//2注册封禁
            '您当前被禁止充值，请联系客服',//3充值封禁
            '您当前被禁止下载游戏，请联系客服',//4下载封禁
        ];
        $ban_type = $request['ban_type']??2;
        $promote_id = $request['promote_id']??0;
        $user_id = $request['user_id']??0;
        $res = judge_user_ban_status($promote_id,$request['game_id'],$user_id,$request['equipment_num'],get_client_ip(),$ban_type);
        if($res){//true,未封禁
            $this->set_message(200, $msg[0]);
        }
        $this->set_message(1017, $msg[$ban_type]);
    }


    /**
     * @注销账号
     *
     * @author: zsl
     * @since: 2021/7/29 19:29
     */
    public function unsubscribe()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $lUnsubscribe = new UnsubscribeLogic();
        if ($request['type'] == '1') {
            //注销
            $result = $lUnsubscribe -> unsubscribe($request['user_id']);
        } else {
            //取消注销账号
            $result = $lUnsubscribe -> cancelUnsub($request['user_id']);
        }
        if ($result['code'] == 0) {
            $this -> set_message(1091, $result['msg']);
        } else {
            $this -> set_message(200, $result['msg']);
        }
    }

    /**
     * 发送短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 10:46
     */
    public function verifyUnsub()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $lUnsubscribe = new UnsubscribeLogic();
        $userInfo = get_user_entity($request['user_id'],false,'phone,email');
        // 手机号和邮箱都未绑定则直接注销
        if(empty($userInfo['phone']) &&empty($userInfo['email'])){
            $result = $lUnsubscribe -> unsubscribe($request['user_id']);
            if ($result['code'] == 0) {
                $this -> set_message(1002, $result['msg']);
            } else {
                $this -> set_message(200, $result['msg'],['type'=>3]);
            }
        }else {
            $result = $lUnsubscribe->sendVerifySms($request['user_id']);
            if ($result['status']) {
                $this->set_message(200, $result['msg'], $result['data']);
            } else {
                $this->set_message(1001, $result['msg']);
            }
        }
    }

    /**
     * 验证发送邮箱/短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 19:14
     */
    public function verifyCode()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        if(empty($request['user_id'])){
            $this -> set_message(1001,'用户信息不能为空');
        }
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe->verifyCode($request['code'],$request['type'],$request['phone']);
        if($result['status']){
            // 短信验证成功则直接注销
            $res = $lUnsubscribe -> unsubscribe($request['user_id']);
            if ($res['code'] == 0) {
                $this -> set_message(1002, $res['msg']);
            } else {
                $this -> set_message(200, $res['msg']);
            }
        }else{
            $this -> set_message(1002, $result['msg']);
        }
    }



}
