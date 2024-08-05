<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\channelwap\controller;

use app\promote\model\PromoteLevelModel;
use cmf\controller\HomeBaseController;
use cmf\paginator\Bootstrap;
use think\WechatAuth;
use think\Db;

class BaseController extends HomeBaseController
{
    public $promote;
    public function __construct()
    {
        parent::__construct();
        $config = cmf_get_option('admin_set');
        if ($config['web_cache'] == 0) {
            exit('站点已关闭');
        }
        // 验证渠道单端登录
        $promote_id = session('PID');
        $flag_single_end_login_promote = 0; // 当前渠道是否开启单端登录 0 未开启, 1 已开启
        $safe_center_info = Db::table('tab_safe_center')->where(['id'=>4])->field('id,config,ids')->find();
        $safe_center_config = $safe_center_info['config'] ?? '';
        if(!empty($safe_center_config)){
            $safe_center_config_arr = json_decode($safe_center_config, true);
            $single_end_login_promote_switch = $safe_center_config_arr['single_end_login_promote']; // 0:关闭 1:开启
            // var_dump($single_end_login_promote_switch);
            if($single_end_login_promote_switch == 1){
                $forbid_promote_ids = json_decode($safe_center_info['ids'], true);
                if(in_array($promote_id, $forbid_promote_ids)){
                    $flag_single_end_login_promote = 1;
                }
            }
        }
        if($flag_single_end_login_promote == 1){
            $latest_session_id = Db::table('tab_promote')->where(['id'=>$promote_id])->field('id,latest_session_id')->find()['latest_session_id'];
            $session_id = $this->getSessionId();
            if($session_id != $latest_session_id){
                $promote_id = session('PID', null);
                $this->error('您的登录已过期,请重新登录!', url('promote/index'));
                // echo 'not equal';
            }
        }

        //PC端自动切换
        if (!cmf_is_mobile()) {
            $headerurl = cmf_get_domain() . '/channelsite';
            $this->redirect($headerurl);
        }
        $pid = is_p_login();
        if (!$pid && request()->controller() != 'Index' && request()->action() != 'checkaccount') {
            $this->redirect(url('index/index'));
        } else {
            define(PID, $pid);
            define(PNAME, session('PNAME'));
            $baseinfo = get_promote_entity(PID);
            $this->promote = $baseinfo;
            if($baseinfo['parent_id'] == 0 ){
                define(PID_LEVEL, 1);
            }else{
                define(PID_LEVEL, $baseinfo['promote_level']);
            }
            $PromoteLevel = new PromoteLevelModel();
            // 渠道充值等级
            $level = $PromoteLevel->field('promote_level')->where('promote_id',PID)->find();
            if(empty($level)){
                define(PROMOTE_LEVEL,0);
            }else{
                define(PROMOTE_LEVEL,$level['promote_level']);
            }
            $this->assign('baseinfo',$baseinfo);
        }
        if (AUTH_USER != 1 || AUTH_PROMOTE != 1) {
            exit('请购买用户权限和推广权限');
        }
        $config = cmf_get_option('promote_set');
        $this->assign('promote_set', $config);
        $union = Db::table('tab_promote_union')->where(['union_id' => PID])->find();
        $this->assign('union', $union);
        $this->assign('action_name', request()->action());
        $this->assign('controller_name', request()->controller());
        //获取seo信息
        $seo = Db::table('tab_seo')->where('name', 'like', 'channel_%')->select()->toArray();
        foreach ($seo as $key => $v) {
            $seokey[$v['name']] = ['seo_title' => $v['seo_title'], 'seo_keyword' => $v['seo_keyword'], 'seo_description' => $v['seo_description']];
        }
        $this->assign('seo', $seokey);
        $wechat_status = get_user_config('wechat');
        $full_url = '//' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $shareparams['title'] = cmf_get_option('promote_set')['ch_share_title'] ? : '手游联运推广联盟中心';
        $shareparams['desc'] = cmf_get_option('promote_set')['ch_share_describe'] ? : '手游渠道合作首选平台，让您边赚边玩。';
        $shareparams['imgUrl'] = cmf_get_option('promote_set')['ch_set_share_logo'] ? cmf_get_image_url(cmf_get_option('promote_set')['ch_set_share_logo']) : cmf_get_domain().'/static/images/empty.jpg';
        $shareparams['link'] = $full_url;
        $this->assign('shareparams', $shareparams);
        if (cmf_is_wechat() && $wechat_status == 1) {  //微信分享
            //微信分享参数
            $signPackage = $this->sharefun();
            $this->assign('signPackage', $signPackage);
        }
        $array = ['wechatlogin'];
        $action = request()->action();
        if (!in_array($action, $array) && !session('wechat_token') && cmf_is_wechat()) {
            $this->wechat_login();
        }
        //判断权限  重要
        $pd_promote_id = $this->request->param('promote_id');
        if($pd_promote_id > 0){
            $zi = array_column(get_song_promote_lists(PID),'id');
            $zi = array_merge($zi,[PID]);
            if(!in_array($pd_promote_id,$zi)){
                $this->error('数据无权限');
            }
        }
    }
	
    // 获取当前的session_id
    protected function getSessionId()
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        return session_id();
    }
    
    /** * 第三方微信公众号登陆 * */
    public function wechat_login($state = 0)
    {
        if (!session("wechat_token") && is_weixin() && get_user_config('wechat') == 1) {
            //手动点击微信按钮 跳回用户中心  解决无限登录问题
            if(request()->action()!='thirdlogin'){
                session('redirect_url', cmf_get_domain() . $_SERVER['REQUEST_URI']);
            }else{
                session('redirect_url', url('user/index'));
            }
            $config = get_user_config_info('wechat');
            $appid = $config['appid'];
            $appsecret = $config['appsecret'];
            $auth = new WechatAuth($appid, $appsecret);
            $gid = $this->request->param('gid', 0, 'intval');
            $pid = $this->request->param('pid', 0, 'intval');
            $admin_config = cmf_get_option('admin_set');

            $redirect_uri = (is_https()?"https://":"http://") . $admin_config['web_site'] . "/channelwap/Thirdlogin/wechatlogin";
            $this->redirect($auth->getRequestCodeURL($redirect_uri, $state));
        }
    }

    /**
     *[微信分享]
     * @return mixed
     * @author chen
     */
    private function sharefun()
    {
        $result = auto_get_access_token('access_token_validity.txt');
        $user_config = get_user_config_info('wechat');
        $appid = $user_config['appid'];
        $appsecret = $user_config['appsecret'];
        if (!$result['is_validity']) {
            $auth = new WechatAuth($appid, $appsecret);
            $token = $auth->getAccessToken();
            $token['expires_in_validity'] = time() + $token['expires_in'];
            wite_text(json_encode($token), 'access_token_validity.txt');
            $result = auto_get_access_token('access_token_validity.txt');
        }
        $auth = new WechatAuth($appid, $appsecret, $result['access_token']);
        $ticket = auto_get_ticket(dirname(__FILE__) . '/ticket.txt');
        if (!$ticket['is_validity']) {
            $jsapiTicketjson = $auth->getticket();
            $jsapiTicketarr = json_decode($jsapiTicketjson, true);

            $jsapiTicketarr['expires_in_validity'] = time() + $jsapiTicketarr['expires_in'];
            wite_text(json_encode($jsapiTicketarr), dirname(__FILE__) . '/ticket.txt');
            $ticket = auto_get_ticket(dirname(__FILE__) . '/ticket.txt');
        }

        $signPackage = $auth->getSignPackage($ticket['ticket']);

        return $signPackage;

    }

    //分页 yyh
    public function array_page($data, $request)
    {
        $page = intval($request['page']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = intval($request['row']) ?: config('paginate.list_rows');//每页数量
        $dataTo = array_chunk($data, $row);
        if ($dataTo) {
            $showdata = $dataTo[$page - 1];
        } else {
            $showdata = null;
        }

        $p = Bootstrap::make($showdata, $row, $page, count($data), false, [
                'var_page' => 'page',
                'path' => url($this->request->action()),
                'query' => [],
                'fragment' => '',
        ]);
        if (!empty($_GET)) {
            $p->appends($_GET);
        } else {
            $p->appends(request()->param());
        }
        // 获取分页显示
        $page = $p->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $p);
    }

    //二位数组排序 yyh
    public function array_order($data = [], $sortkey = '', $sort_order = 1)
    {
        if (empty($sortkey)) {
            return $data;
        } else {
            if ($sort_order == 1) {
                return my_sort($data, '', '');//1为不排序
            }
            $sort_order = $sort_order == 2 ? SORT_ASC : SORT_DESC;
            return my_sort($data, $sortkey, (int)$sort_order);
        }

    }
}