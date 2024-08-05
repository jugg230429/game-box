<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:06
 */

namespace app\mobile\controller;

use app\common\controller\BaseHomeController;
use app\game\model\GameModel;
use app\common\logic\AppLogic;
use app\game\model\GameiospaytodownloadorderModel;
use app\site\model\AppModel;
use app\site\model\AppSupersignOrderModel;
use think\Db;
use think\Request;
use think\weixinsdk\Weixin;

class DownfileController extends BaseController
{

    /**
     * @推广注册页面
     *
     * @author: zsl
     * @since: 2019/3/30 13:44
     */
    public function index()
    {
        $gid = intval(input('gid'));  // 游戏id
        $pid = intval(input('pid'));  // 渠道id

        //判断访问设备
        if (cmf_is_android()) {
            $map['sdk_version'] = '1';

        } else if (cmf_is_ios()) {
            $map['sdk_version'] = '2';

        } else {

            //PC端打开,生成二维码
            $current_url = $this->request->domain() . $this->request->url();
            $qrcode = url('qrcode', ['url' => base64_encode(base64_encode($current_url))]);
            $this->assign('qrcode', $qrcode);
        }

        //查询游戏信息
        $map['relation_game_id'] = $gid;
//        $map['pa.promote_id'] = $pid;
        $game = new GameModel();
        $game_info = $game
            ->field('id,game_name,features,game_type_name,icon,cover,sdk_version,relation_game_id,relation_game_name,back_describe,dow_icon,back_map,introduction,screenshot,dow_num,dow_status,create_time,update_time')
//            ->['tab_promote_apply'=>'pa'],'g.id = pa.game_id','left')
            ->where($map)
            ->order('sdk_version asc')
            ->find();
        if(empty($game_info)){
            $this->error('游戏数据不存在');
        }
        //处理游戏分类为数组
        $game_info = $game_info->toArray();
        $game_info['game_type_id'] = explode(',',$game_info['game_type_id']);

        // 判断H5分享页面是否允许展示 =================================START
        // tab_promote_apply 中 is_h5_share_show 控制H5分享页面是否显示, 0 显示, 1隐藏(不允许展示)
        if ($pid) {
            $game_id_tmp = $game_info['id'];
            $map = [];
            $map['game_id'] = $game_id_tmp;
            $map['promote_id'] = $pid;
            $is_h5_share_show = Db ::table('tab_promote_apply') -> where($map) -> value('is_h5_share_show');
            if ($is_h5_share_show == 1) {
                // 不允许展示
                return $this -> fetch('error404');
            }
        }
        // 判断H5分享页面是否允许展示 =================================END


        if(MOBILE_PID>0 && empty($pid)){
            $pid = MOBILE_PID;
        }
        $game_info['promote_id'] = $pid ? $pid : 0;
        $this->assign('game_info', $game_info);
        $freelogin = 1;
        if ($pid && $gid) {
            $map1['promote_id'] = $pid;
            $map1['game_id'] = $game_info['id'];
            $map1['status'] = 1;
            $map1['enable_status'] = 1;
            $map1['pack_type'] = ['in', [1,2,3]];
            $apply = Db::table('tab_promote_apply')->field('id,pack_type,is_upload')->where($map1)->find();
            if ($apply) {
                if($apply['pack_type']==2){
                    $freelogin = 0;//需要登录
                }
            }
        }
        $this->assign('freelogin',$freelogin);
        $this->assign('title', $game_info['back_describe']);


        //获取社区信息
        $wjsq = [];
        $promote_info = Db::table('tab_promote')->field('id,account,parent_id')->where(['id' => $pid])->find();
        if ($promote_info['parent_id'] == '0') {
            $union_set = Db::table('tab_promote_union')->field('union_set')->where(['union_id' => $promote_info['id']])->find();
            if ($union_set) {
                $union_set = json_decode($union_set['union_set'], true);
                if ($union_set['app_weixin'] && $union_set['qrcode']) {
                    $wjsq['qrcode_name'] = $union_set['app_weixin'];
                    $wjsq['qrcode'] = $union_set['qrcode'];
                }
            }
        }

        // 获取版权信息
        $copyrightInfo = cmf_get_option('media_set');
        $this->assign('copyright_info', $copyrightInfo); // pc_set_copyright
        // 所属CP 根据游戏查询所属CP商
        $cpInfo = Db::table('tab_game')->alias('g')
            ->join(['tab_game_cp' => 'cp'], 'g.cp_id=cp.id', 'right')
            ->where(['g.id'=>$gid])
            ->field('g.cp_id as g_cp_id,cp.id as cp_id,cp.cp_name')
            ->find();
        $this->assign('cp_info', $cpInfo);  // cp_name
        $portalPostModel = new \app\site\model\PortalPostModel;
        $portal_info = $portalPostModel->where('id', 'in',[16,1])->order('id desc')->select();
        $portal['application_privilege_title'] = $portal_info[1]['post_title'];
        $portal['yinsi_title'] = $portal_info[0]['post_title'];
        $this->assign('portal', $portal);
        // 获取版本号 仅原包有
        $source_info = Db::table('tab_game_source')->where(['game_id'=>$gid])->field('source_version,create_time')->find();
        $source_version = $source_info['source_version'] ?? '';
        $source_create_time = $source_info['create_time'] ?? '';
        $this->assign('source_version', $source_version);
        $this->assign('source_create_time', $source_create_time);

        $this->assign('wjsq', $wjsq);

        $this->assign('system', $game_info['sdk_version'] == 2 ? '苹果' : '安卓');

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @H5推广页面
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/6/29 9:32
     */
    public function indexh5()
    {
        $gid = input('gid');
        $pid = input('pid');
        //判断访问设备
        if (!cmf_is_mobile()) {
            //PC端打开,生成二维码
            $current_url = $this->request->domain() . $this->request->url();
            $qrcode = url('qrcode', ['url' => base64_encode(base64_encode($current_url))]);
            $this->assign('qrcode', $qrcode);
        }
        //查询游戏信息
        $map['relation_game_id'] = $gid;
        $map['sdk_version'] = 3;
        $game = new GameModel();
        $game_info = $game
                ->field('id,game_name,features,game_type_name,icon,cover,sdk_version,relation_game_id,relation_game_name,back_describe,dow_icon,back_map,introduction,screenshot,dow_num,dow_status,create_time,update_time')
                ->where($map)
                ->order('sdk_version asc')
                ->find();
        if(empty($game_info)){
            $this->error('游戏数据不存在');
        }
        //处理游戏分类为数组
        $game_info = $game_info->toArray();
        $game_info['game_type_name'] = explode(',',$game_info['game_type_name']);

        // 判断H5分享页面是否允许展示 =================================START
        // tab_promote_apply 中 is_h5_share_show 控制H5分享页面是否显示, 0 显示, 1隐藏(不允许展示)
        if ($pid) {

            $game_id_tmp = $game_info['id'];
            $map = [];
            $map['game_id'] = $game_id_tmp;
            $map['promote_id'] = $pid;
            $is_h5_share_show = Db ::table('tab_promote_apply') -> where($map) -> value('is_h5_share_show');
            if ($is_h5_share_show == 1) {
                // 不允许展示
                return $this -> fetch('error404');
            }
        }
        // 判断H5分享页面是否允许展示 =================================END

        if(MOBILE_PID>0 && empty($pid)){
            $pid = MOBILE_PID;
        }
        $game_info['promote_id'] = $pid ? $pid : 0;
        $this->assign('game_info', $game_info);
        $this->assign('title', $game_info['back_describe']);
        //获取社区信息
        if($pid){
            $wjsq = [];
            $promote_info = Db::table('tab_promote')->field('id,account,parent_id')->where(['id' => $pid])->find();
            if ($promote_info['parent_id'] == '0') {
                $union_set = Db::table('tab_promote_union')->field('union_set')->where(['union_id' => $promote_info['id']])->find();
                if ($union_set) {
                    $union_set = json_decode($union_set['union_set'], true);
                    if ($union_set['app_weixin'] && $union_set['qrcode']) {
                        $wjsq['qrcode_name'] = $union_set['app_weixin'];
                        $wjsq['qrcode'] = $union_set['qrcode'];
                    }
                }
            }
            $this->assign('wjsq', $wjsq);
        }
        // 获取版权信息
        $copyrightInfo = cmf_get_option('media_set');
        $this->assign('copyright_info', $copyrightInfo); // pc_set_copyright
        // 所属CP 根据游戏查询所属CP商
        $cpInfo = Db::table('tab_game')->alias('g')
            ->join(['tab_game_cp' => 'cp'], 'g.cp_id=cp.id', 'right')
            ->where(['g.id'=>$gid])
            ->field('g.cp_id as g_cp_id,cp.id as cp_id,cp.cp_name')
            ->find();
        $this->assign('cp_info', $cpInfo);  // cp_name
        $portalPostModel = new \app\site\model\PortalPostModel;
        $portal_info = $portalPostModel->where('id', 'in',[16,1])->order('id desc')->select();
        $portal['application_privilege_title'] = $portal_info[1]['post_title'];
        $portal['yinsi_title'] = $portal_info[0]['post_title'];
        $this->assign('portal', $portal);
         // 获取版本号 仅原包有
         $source_info = Db::table('tab_game_source')->where(['game_id'=>$gid])->field('source_version,create_time')->find();
         $source_version = $source_info['source_version'] ?? '';
         $source_create_time = $source_info['create_time'] ?? '';

        $this->assign('source_version', $source_version);
        $this->assign('source_create_time', $source_create_time);

        $this->assign('system', $game_info['sdk_version'] == 2 ? '苹果' : '安卓');
        return $this->fetch();
    }
    // 用户注册协议
    public function privacy()
    {

        $portalPostModel = new \app\site\model\PortalPostModel;

        $data = $portalPostModel->where('id', 16)->find();

        $this->assign('data', $data);
        return $this->fetch();

    }

    public function protocol()
    {
        $param = $this ->request -> param('language_type');

        if (is_numeric($param) && $param >= 0) {

           $ProtocolModel =  new \app\site\model\ProtocolModel;

           $data = $ProtocolModel -> field('title as post_title, content as post_content')
               -> where('language', $param)
               -> order('update_time desc')
               -> find();
           $data['post_content'] = htmlspecialchars_decode($data['post_content']);
        } else {

            $portalPostModel = new \app\site\model\PortalPostModel;

            $data = $portalPostModel->where('id', 1)->find();
        }

        $this->assign('data', $data);

        return $this->fetch();

    }

    /**
     * [下载方法]
     * @author 郭家屯[gjt]
     */
    public function down()
    {
        $data = $this->request->param();
        if(empty($data['sdk_version'])){
            $data['sdk_version'] = 1;
        }
        //封禁判断-20210712-byh
        $game_id = \think\Db::table('tab_game')->where(['relation_game_id'=>$data['game_id'],'sdk_version'=>$data['sdk_version']])->value('id');
        if(!judge_user_ban_status($data['promote_id'],$game_id,session('member_auth.user_id'),'',get_client_ip(),$type=4)){
            $this->error('您当前被禁止下载游戏，请联系客服');
        }
        // 判断是否是需要付费的ios超级签下载
        // var_dump("超级签付费下载!");exit; 传入relation_game_id
        $ios_pay_to_download_info = get_ios_pay_to_download($game_id);
        if($ios_pay_to_download_info['pay_download'] != 0 && $ios_pay_to_download_info['pay_price'] > 0){
            // 需要付费下载
            $tmp_down_url = url('mobile/Downfile/index',['gid'=>$data['game_id']],'',true);
            // Header("Location: ",);
            header($tmp_down_url);

        }
        $base = new BaseHomeController();
        $base->down_file($data['game_id'], $data['sdk_version'], session('member_auth.user_id'), $data['promote_id']);
    }
    /**
     * 判断游戏是否封禁状态-ajax请求-下载
     * by:byh-2021-7-14 10:35:00
     */
    public function get_ban_status()
    {
        $data = $this->request->param();
        //判断类型
        if($data['type'] != 4) return json(['code'=>0,'msg'=>'类型错误']);
        //判断游戏
        if(empty($data['game_id'])) return json(['code'=>0,'msg'=>'数据错误']);
        //判断版本对应的游戏id
        if (empty(($data['sdk_version']))) {
            $data['sdk_version'] = 1;
        }
        if(!in_array($data['sdk_version'],[1,2,3])) return json(['code'=>0,'msg'=>'版本类型错误']);
        $game_id = \think\Db::table('tab_game')->where(['relation_game_id'=>$data['game_id'],'sdk_version'=>$data['sdk_version']])->value('id');
        $res = judge_user_ban_status($data['promote_id'],$game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$data['type']);
        if(!$res){
            return json(['code'=>0,'msg'=>'您当前被禁止下载游戏，请联系客服']);
        }
        return json(['code'=>1,'msg'=>'success']);

    }

    /**
     * @函数或方法说明
     * @微端下载
     * @author: 郭家屯
     * @since: 2020/6/24 14:10
     */
    public function weiduan_down()
    {
        $data = $this->request->param();
        if(empty($data['sdk_version'])){
            $data['sdk_version'] = 1;
        }
        $base = new BaseHomeController();
        $base->down_weiduan_file($data['game_id'], $data['sdk_version'], session('member_auth.user_id'), $data['promote_id']);
    }

    /**
     * @函数或方法说明
     * @H5页面模板
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/4/13 14:45
     */
    public function show()
    {
        return $this->fetch();
    }

    public function get_gid(){
        $gid = $this->request->param('gid');
        if(empty($gid)){
            return json(['code'=>0,'gid'=>$gid]);
        }else{
            $device = get_devices_type();
            $game_data = Db::table('tab_game')->field('id')->where(['relation_game_id'=>$gid,'sdk_version'=>$device])->find();
            if(empty($game_data)){
                return json(['code'=>1,'gid'=>$gid]);
            }else{
                return json(['code'=>1,'gid'=>$game_data['id']]);
            }
        }
    }
    /**
     * [生成二维码]
     * @param string $url
     * @param int $level
     * @param int $size
     * @author 郭家屯[gjt]
     */
    public function qrcode($url = 'pc.vlcms.com', $level = 3, $size = 4)
    {
        $url = base64_decode(base64_decode($url));
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        ob_clean();
        $object = new \QRcode();
        echo $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    // app下载页
    public function download_app()
    {
        $promote_id = $this->request->param('promote_id');
        if($promote_id > 0){
            $union_model = new \app\promote\model\PromoteunionModel();
            $host = $union_model->where('union_id', $promote_id)->find();
            if ($host && $host['status'] == 1) {
                $union_set = json_decode($host['union_set'], true);
                $this -> assign('union_set', $union_set);
            }
        }
        $device = get_devices_type();  // 1:安卓 2: ios
        $logic = new AppLogic();
        $res = $logic->down_app($device,$promote_id);
        // var_dump($res);
        // exit;
        // var_dump($device);exit;
        $this->assign('devices_type', $device);
        $this->assign('app_data',$res);
        $this->assign('user_agent_md5',md5($this->request->server('HTTP_USER_AGENT')));
        return $this->fetch();
    }

    // 执行下载
    public function downapp()
    {
        $promote_id = $this->request->param('promote_id');
        $device = $this->request->param('device') ? :get_devices_type();
        $logic = new AppLogic();
        $res = $logic->down_app($device,$promote_id);

        if($res['promote_id']){
            $url = $device==2?(cmf_is_mobile()?"paplist_url":'dow_url'):'dow_url';  // paplist_url: tab_promote_app.plist_url
            $url = $res['app_data']['patype'] == 1 ? 'dow_url' : $url; //超级签判断 // patype : tab_promote_app.type
            //IOS官方app是超级签，渠道APP打包方式是默认
            if($res['app_data']['type'] == 1 && $res['app_data']['patype'] == 0 && $device == 2){
                $url = 'dow_url';
            }
            // 判断如果渠道的苹果游戏盒子设置了自定义 填充 $res['app_data'][] 数组-----------------------START
            if($device==2){
                $promote_id = $res['promote_id'];
                $single_define_ios = Db::table('tab_promote_app')->where("promote_id = $promote_id AND app_version = 2 AND is_user_define = 1")->find();
                if(!empty($single_define_ios)){
                    if($single_define_ios['type'] == 0){
                        // 原包上传
                        if($url == 'paplist_url'){
                            $res['app_data'][$url] = $single_define_ios['plist_url'];
                        }
                        if($url == 'dow_url'){
                            $res['app_data'][$url] = $single_define_ios['dow_url'];
                        }
                    }
                    if($single_define_ios['type'] == 1){
                        // 超级签
                        $res['app_data'][$url] = $single_define_ios['super_url'];
                    }

                }

            }
            // 判断如果渠道的苹果游戏盒子设置了自定义 填充 $res['app_data'][] 数组-----------------------END

        }else{
            $url = $device==2?(cmf_is_mobile()?"plist_url":'file_url'):'file_url';
            $url = $res['app_data']['type'] == 1 ? 'file_url' : $url;//超级签判断
        }
        if ($device == 1 && is_weixin()) {
            exit("<h1>请使用浏览器下载</h1>");
        }
        // var_dump($res);exit;
        if ($res['app_data'][$url]) {
            if ($device == 1) {
                Header("HTTP/1.1 303 See Other");
                Header("Location: " . promote_game_get_file_download_url($res['app_data'][$url]));
                exit();
            } else {
                if (cmf_is_mobile()) {
                    if($res['app_data']['type'] == 1){
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . promote_game_get_file_download_url($res['app_data'][$url]));
                        exit();
                    }else{
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . "itms-services://?action=download-manifest&url=https://" . cmf_get_option('admin_set')['web_site'] . '/upload/' . $res['app_data'][$url]);
                        exit();
                    }
                } else {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . promote_game_get_file_download_url($res['app_data'][$url]));
                    exit();
                }
            }
        } else {
            $this->error('APP不存在');
        }
    }

    /**
     * @IOS下载APP展示页面
     *
     */
    public function downapp_ios()
    {
        $iosPackageInfo = Db::table('tab_app')->where(['version'=>2])->find();

        // 直接跳转到超级签地址
        if($iosPackageInfo['type']=='1'){
            $this->downapp();
            // $this->redirect($iosPackageInfo['file_url']);
        }

        $iosPackageName = $iosPackageInfo['bao_name'] ?? '苹果游戏盒子安装包';
        $this->assign('ios_package_name', $iosPackageName);
        return $this -> fetch();
    }


    /**
     * @查询是否需要支付
     *
     * @author: zsl
     * @since: 2021/7/12 16:36
     */
    public function checkAppDownStatus()
    {
        $userAgent = $this -> request -> server('HTTP_USER_AGENT');
        if (false === $this -> _appDownStatus($userAgent)) {
            $this -> error('请进行支付');
        }
        $this -> success('success');

    }


    /**
     * @支付
     *
     * @author: zsl
     * @since: 2021/7/12 20:00
     */
    public function doPay()
    {
        $param = $this -> request -> param();
        //获取app设置
        $mApp = new AppModel();
        $where = [];
        $where['id'] = '2';
        $where['type'] = '1'; //超级签
        $where['pay_download'] = '1'; //开启付费下载
        $appInfo = $mApp -> where($where) -> find();
        if (empty($appInfo)) {
            $this -> error('不需要付费下载，请刷新页面重新下载');
        }
        $pay_price = $appInfo['pay_price'];
        if ($pay_price < 0.01) {
            $this -> error('金额配置错误，请联系客服');
        }
        if ($param['pay_method'] == 'wxpay') {
            //微信支付
            if (pay_type_status('wxscan') != 1) {
                $this -> error('微信支付未开启');
            }
        } else {
            //支付宝支付
            $config = get_pay_type_set('zfb');
            if ($config['status'] != 1) {
                $this -> error('支付宝支付未开启');
            }
        }
        //生成订单数据
        $mAppOrder = new AppSupersignOrderModel();
        $mAppOrder -> order_number = '';
        $mAppOrder -> pay_order_number = 'SS_' . cmf_get_order_sn();
        $mAppOrder -> user_agent = $this -> request -> server('HTTP_USER_AGENT');
        $mAppOrder -> user_agent_md5 = md5($this -> request -> server('HTTP_USER_AGENT'));
        $mAppOrder -> pay_amount = $pay_price;
        $mAppOrder -> pay_way = $param['pay_method'] == 'wxpay' ? '4' : '3';
        $mAppOrder -> pay_ip = get_client_ip();
        $mAppOrder -> pay_status = 0;
        $res = $mAppOrder -> save();
        if (false === $res) {
            $this -> error('创建订单失败，请重试');
        }
        //开始支付
        if ($param['pay_method'] == 'wxpay') {
            //微信H5支付
            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay('超级签支付', $mAppOrder -> pay_order_number, $pay_price, 'MWEB'), true);
            if ($is_pay['status'] == 1) {
                $data = [];
                $data['pay_url'] = $is_pay['mweb_url'];
                $this -> success('success', '', $data);
            }
        } else {
            //支付宝H5支付
            $pay = new \think\Pay('alipay', $config['config']);
            $vo = new \think\pay\PayVo();
            $vo -> setBody('超级签支付')
                    -> setFee($pay_price)//支付金额
                    -> setTitle('超级签')
                    -> setOrderNo($mAppOrder -> pay_order_number)
                    -> setService('alipay.wap.create.direct.pay.by.user')
                    -> setSignType("MD5")
                    -> setPayMethod('wap')
                    -> setTable('app_supersign_order')
                    -> setPayWay(3);
            $pay_url = $pay -> buildRequestForm($vo);
            $data = [];
            $data['pay_url'] = $pay_url;
            $this -> success('success', '', $data);
        }
    }



    /**
     * @获取APP下载状态
     *
     * @since: 2021/7/12 19:19
     * @author: zsl
     */
    private function _appDownStatus($userAgent)
    {
        //是否开启付费下载
        $mApp = new AppModel();
        $where = [];
        $where['id'] = '2';
        $where['type'] = '1'; //超级签
        $where['pay_download'] = '1'; //开启付费下载
        $appInfo = $mApp -> where($where) -> find();
        if (empty($appInfo)) {
            return true;
        }
        //判断当前账号是否购买
        $mAppOrder = new AppSupersignOrderModel();
        $where = [];
        $where['user_agent_md5'] = md5($userAgent);
        $where['pay_status'] = 1;
        $order = $mAppOrder -> where($where) -> find();
        if (!empty($order)) {
            return true;
        }
        return false;
    }

    /**
     * 判断ios游戏是否是超级签付费下载游戏
    */
    public function checkiosPayDownload(Request $request)
    {
        $data = $this->request->param();
        //判断游戏
        if(empty($data['game_id'])) return json(['code'=>-1,'msg'=>'数据错误']);
        //判断版本对应的游戏id
        // if ($data['sdk_version'] != 2) {
        //     return json(['code'=>-1,'msg'=>'非苹果游戏版本']);
        // }
        $sdk_version = get_devices_type();
        if($sdk_version !==1 && $sdk_version !==2){
            $sdk_version = $data['down_version'];
        }

        $game_info = \think\Db::table('tab_game')
            ->where(['relation_game_id'=>$data['game_id'],'sdk_version'=>$sdk_version])
            ->field('id,down_port')
            ->find();

        $game_id = $game_info['id'] ;
        if($game_info['down_port'] != 3){  // 1: 官方原包下载, 2: 第三方地址 3: 超级签下载
            return json(['code'=>2,'msg'=>'可以直接下载!', 'data'=>[]]);
        }

        if(empty($game_id)){
            // return json(['code'=>-1,'msg'=>'未找到对应版本的游戏!', 'data'=>[]]);
            return json(['code'=>-1,'msg'=>'此为超级签付费包, 请在苹果手机端打开!', 'data'=>[]]);
        }
        // 判断是否是需要付费的ios超级签下载
        $ios_pay_to_download_info = get_ios_pay_to_download($game_id);
        if($ios_pay_to_download_info['pay_download'] != 0 && $ios_pay_to_download_info['pay_price'] > 0){
            // 判断是否已经付费过
            $user_agent = $this -> request -> server('HTTP_USER_AGENT');
            $user_agent_md5 = md5($this -> request -> server('HTTP_USER_AGENT'));
            $download_record_info = Db::table('tab_game_ios_pay_to_download_record')->where(['user_agent_md5'=>$user_agent_md5, 'game_id'=>$game_id])->find();
            if(!empty($download_record_info)){
                return json(['code'=>2,'msg'=>'可以直接下载!', 'data'=>[]]);
            }
            // 需要付费下载
            $returnData = [
                'pay_download'=>$ios_pay_to_download_info['pay_download'],
                'pay_price'=>$ios_pay_to_download_info['pay_price']
            ];
            return json(['code'=>1,'msg'=>'此为超级签付费包, 请在苹果手机端打开,付费后下载!', 'data'=>$returnData]);
            // return json(['code'=>-1,'msg'=>'此为超级签付费包, 请在苹果手机端打开!', 'data'=>[]]);
        }else{
            return json(['code'=>2,'msg'=>'可以直接下载!', 'data'=>[]]);
        }

    }

    /**
     * 游戏下载支付, 生成订单, 支付
    */
    public function doPay2()
    {
        $param = $this -> request -> param();
        $game_id = $param['game_id'];
        $promote_id = $param['promote_id'] ?? 0;
        $flag = 0;
        $ios_pay_to_download_info = get_ios_pay_to_download($game_id);

        if($ios_pay_to_download_info['pay_download'] != 0 && $ios_pay_to_download_info['pay_price'] > 0){
            // 判断是否已经付费过
            $user_agent = $this -> request -> server('HTTP_USER_AGENT');
            $user_agent_md5 = md5($this -> request -> server('HTTP_USER_AGENT'));
            $download_record_info = Db::table('tab_game_ios_pay_to_download_record')->where(['user_agent_md5'=>$user_agent_md5, 'game_id'=>$game_id])->find();
            if(!empty($download_record_info)){
                // $flag = 1;
                $this -> error('不需要付费下载，请刷新页面重新下载');
            }
        }else{
            $this -> error('不需要付费下载，请刷新页面重新下载');
        }

        $pay_price = $ios_pay_to_download_info['pay_price'];
        if ($pay_price < 0.01) {
            $this -> error('金额配置错误，请联系客服');
        }
        if ($param['pay_method'] == 'wxpay') {
            //微信支付
            if (pay_type_status('wxscan') != 1) {
                $this -> error('微信支付未开启');
            }
        } else {
            //支付宝支付
            $config = get_pay_type_set('zfb');
            if ($config['status'] != 1) {
                $this -> error('支付宝支付未开启');
            }
        }
        //生成订单数据
        $GameiospaytodownloadorderM = new GameiospaytodownloadorderModel();
        $GameiospaytodownloadorderM -> game_id = $game_id;
        $GameiospaytodownloadorderM -> promote_id = $promote_id;

        $GameiospaytodownloadorderM -> order_number = '';
        $GameiospaytodownloadorderM -> pay_order_number = 'PG_' . cmf_get_order_sn();  // pay game 付费下载游戏
        $GameiospaytodownloadorderM -> user_agent = $this -> request -> server('HTTP_USER_AGENT');
        $GameiospaytodownloadorderM -> user_agent_md5 = md5($this -> request -> server('HTTP_USER_AGENT'));
        $GameiospaytodownloadorderM -> pay_price = $pay_price;
        $GameiospaytodownloadorderM -> pay_way = $param['pay_method'] == 'wxpay' ? '4' : '3';
        $GameiospaytodownloadorderM -> pay_ip = get_client_ip();
        $GameiospaytodownloadorderM -> pay_status = 0;
        $res = $GameiospaytodownloadorderM -> save();
        if (false === $res) {
            $this -> error('创建订单失败，请重试');
        }
        //开始支付
        if ($param['pay_method'] == 'wxpay') {
            //微信H5支付
            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay('超级签支付', $GameiospaytodownloadorderM -> pay_order_number, $pay_price, 'MWEB'), true);
            if ($is_pay['status'] == 1) {
                $data = [];
                $data['pay_url'] = $is_pay['mweb_url'];
                $this -> success('success', '', $data);
            }
        } else {
            //支付宝H5支付
            $pay = new \think\Pay('alipay', $config['config']);
            $vo = new \think\pay\PayVo();
            $vo -> setBody('超级签游戏支付')
                    -> setFee($pay_price)//支付金额
                    -> setTitle('超级签游戏')
                    -> setOrderNo($GameiospaytodownloadorderM -> pay_order_number)
                    -> setService('alipay.wap.create.direct.pay.by.user')
                    -> setSignType("MD5")
                    -> setPayMethod('wap')
                    -> setTable('app_supersign_order')
                    -> setPayWay(3);
            $pay_url = $pay -> buildRequestForm($vo);
            $data = [];
            $data['pay_url'] = $pay_url;
            $this -> success('success', '', $data);
        }
    }



}
