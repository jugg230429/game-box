<?php

namespace app\media\controller;

use cmf\controller\HomeBaseController;
use app\site\model\AdvModel;
use app\site\model\LinkModel;
use think\Db;

class BaseController extends HomeBaseController
{
    protected static $userSession;

    //初始验证
    protected function _initialize()
    {
        app_auth_value();
        //手机端自动切换
        if (cmf_is_mobile() || ((request()->action() == 'IndexController' || request()->action() == 'qrcode') && request()->controller() == 'Downfile')) {
            if ($_SERVER['REQUEST_URI'] == '/') {
                $url = '/mobile';
            } else {
                $url = str_replace('media', 'mobile', $_SERVER['REQUEST_URI']);
            }
            $headerurl = cmf_get_domain() . $url;
            if(request()->controller() != 'Wappay' && request()->controller() != 'Issuepay'){
                $this->redirect($headerurl);
            }
        }
        $serverhost = $_SERVER['HTTP_HOST'];
        $config = cmf_get_option('admin_set');
        if ($config['web_cache'] == 0) {
            exit('站点已关闭');
        }
        if ($serverhost != $config['web_site']) {
            session('for_third', $config['web_site']);
        }
        if (AUTH_PROMOTE == 1) {
            $serverhostarr = [$serverhost, "http://" . $serverhost, "https://" . $serverhost];
            $union_model = new \app\promote\model\PromoteunionModel();
            $host = $union_model->where('domain_url', 'in', $serverhostarr)->find();
            if (empty($host) && $serverhost != $config['web_site']) {
                define('MEDIA_PID', 0);
                // die('<h1>404 not found</h1>The requested URL /media was not found on this server');
            } else {
                if ($host) {
                    $host = $host->toArray();
                    if ($host['status'] == 1) {
                        session('union_host', $host);
                        $union_set = json_decode($host['union_set'], true);
                        $this->assign('union_set', $union_set);
                        define('MEDIA_PID', $host['union_id']);

                        //联盟站底部文章
                        $articleLists = Db ::table('tab_promote_union_article') -> where(['promote_id' => MEDIA_PID]) -> select();
                        $this -> assign('articleLists', $articleLists);

                    } else {
                        die('<h1>The site is not audited</h1>');
                    }
                }else{
                    define('MEDIA_PID', 0);
                }
            }
        }
        if (AUTH_USER == 1) {
            //登录按钮
            $thirloginstr = ["qq_login", "weixin_login"];
            $tool = Db::table('tab_user_config')->field('name,status')->where('name', 'in', $thirloginstr)->select()->toArray();
            foreach ($tool as $key => $val) {
                $this->assign($tool[$key]['name'], $val['status']);
            }
        }
        $this->assign('action_name', request()->action());
        $this->assign('controller_name', request()->controller());
        //获取seo信息
        $seo = Db::table('tab_seo')->field('seo_title,seo_keyword,seo_description,name')->where('name', 'like', 'media_%')->select()->toArray();
        foreach ($seo as $key => $v) {
            $seokey[$v['name']] = ['seo_title' => $v['seo_title'], 'seo_keyword' => $v['seo_keyword'], 'seo_description' => $v['seo_description']];
        }
        $this->assign('seo', $seokey);
        //不符合状态 强制退出
        if (session('member_auth') ) {
            $userdata = get_user_entity(session('member_auth.user_id'),false,'password,lock_status');
            if(session('member_auth.password')!=$userdata['password']||$userdata['lock_status']!=1){
                session('member_auth', null);
                return $this->redirect('Index/index');
            }
        }
        self::$userSession = $session = array(
            'login_auth' => session('member_auth') ? 1 : 0,
            'login_user_id' => session('member_auth.user_id'),
            'login_account' => session('member_auth.account'),
            'is_union' => session('union_host') ? 1 : 0,
            'login_head_img' => cmf_get_image_url(session('member_auth.head_img'))
        );
        $this->assign('session', $session);
        //注册协议
        $portalPostModel = new \app\site\model\PortalPostModel;
        $portal_info = $portalPostModel->where('id', 'in',[16,27])->order('id desc')->select();
        $portal['post_title'] = $portal_info[0]['post_title'];
        $portal['post_title_yinsi'] = $portal_info[1]['post_title'];
        $this->assign('portal', $portal);
        //拉黑处理
        $ip = get_client_ip();
        $blank_ip = cmf_get_option('admin_set')['reg_blank_ip'];
        if($blank_ip){
            $blank_ip = explode(',',$blank_ip);
            if(in_array($ip,$blank_ip)){
                $this->assign('is_blank',1);
            }
        }
        $this->links();//友情链接
    }

    /**
     * [友情链接]
     * @author 郭家屯[gjt]
     */
    public function links()
    {
        $model = new LinkModel();
        $data = $model->getLists();
        $this->assign('links', $data);
    }

    /**
     * [轮播图]
     * @param int $pos_id
     * @author 郭家屯[gjt]
     */
    public function slide($name = '')
    {
        $model = new AdvModel();
        $list = $model->getAdv($name,MEDIA_PID);
        $this->assign("carousel", $list);
    }

    /*
    *平台币充值记录
    */
    public function add_deposit($data)
    {
        $deposit = new \app\recharge\model\SpendBalanceModel();
        $deposit_data = $this->deposit_param($data);
        $result = $deposit->field(true)->insert($deposit_data);
        return $result;
    }

    /**
     *平台币充值记录表 参数
     */
    private function deposit_param($param = array())
    {
        $user_entity = get_user_entity($param['user_id'],false,'promote_id');
        $data_deposit['order_number'] = $param["order_number"] ? $param["order_number"] : '';
        $data_deposit['pay_order_number'] = $param["pay_order_number"];
        $data_deposit['user_id'] = $param["user_id"];
        $data_deposit['promote_id'] = $user_entity["promote_id"];
        $data_deposit['pay_amount'] = $param["price"];
        $data_deposit['cost'] = $param["price"];
        $data_deposit['pay_status'] = $param["pay_status"];
        $data_deposit['pay_way'] = $param["pay_way"];
        $data_deposit['pay_ip'] = $param["spend_ip"];
        $data_deposit['pay_time'] = time();
        return $data_deposit;
    }

    /**
     * 获取用户登录状态
     * yyh
     */
    public function get_user_status()
    {
        $userSession = self::$userSession;
        $userSession['pc_user_allow_register'] = cmf_get_option('media_set')['pc_user_allow_register'];
        $userSession['cookie_login_account'] = cookie('login_account');
        $userSession['cookie_login_password'] = simple_decode(cookie('login_password')) ?: null;
        $userSession['cookie_last_login_account'] = cookie('last_login_account');
        $userSession['menu_controller_name'] = strtolower(request()->controller());
        if (empty($userSession['login_user_id'])) {
            return json(['status' => 0, 'data' => $userSession]);
        } else {
            return json(['status' => 1, 'data' => $userSession]);
        }
    }
    
}
