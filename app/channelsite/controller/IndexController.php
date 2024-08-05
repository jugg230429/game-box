<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\channelsite\controller;

use app\site\model\KefutypeModel;
use think\Db;
use app\promote\model\PromoteModel;
use app\portal\service\ApiService;
use app\promote\validate\PromoteValidate;
use app\common\controller\BaseHomeController;
use geetest\lib\GeetestLib;
use app\site\model\PortalPostModel;
use app\site\service\PostService;
use app\game\model\ServerModel;
use app\game\model\GameModel;
class IndexController extends BaseController
{
    public $jyparam = [];

    public function __construct()
    {
        parent::__construct();

        $this->jyparam = [
            "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];
    }

    /**
     * @函数或方法说明
     * @首页
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/8/9 11:45
     */
    public function index()
    {
        $promote_auto_audit = Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit'])->find();
        $this->assign('promote_auto_audit', $promote_auto_audit['status']);
        $map['game_status'] = 1;
        $map['recommend_status'] = ['like', '%1%'];
        $game = get_game_list('id,icon,relation_game_id,relation_game_name as game_name',$map,'relation_game_id','sort desc,id desc');
        $game = array_chunk($game, 12);
        $this->assign('game', $game);
        // 渠道站 推广后台注册协议名称写活----------- START added by wjd 2021-6-8 19:17:41
        $articleapi = new ApiService;
        $param['where']['post.id'] = 15;
        $articles = $articleapi->articles($param);
        $data = empty($articles) ? [] : $articles['articles']->toarray();
        $tmp_post_title = $data[0]['post_title'] ?? '《最终用户协议》';
        $this->assign('tmp_post_title', $tmp_post_title);
        // 渠道站 推广后台注册协议名称写活----------- END

        return $this->fetch();
    }

    // public function a(){
    //     $data = $this->request->param();
    //     var_dump(cmf_password(123456));exit;
    // }

    public function login()
    {
        $model = new PromoteModel;
        $data = $this->request->param();

        $account = $data['account'];
        $password = $data['password'];
        if (empty($account) || empty($password)) {
            $this->error('缺少必要参数');
        }
        if (empty($data['geetest_challenge']) || empty($data['geetest_validate']) || empty($data['geetest_seccode'])) {
            $this->error('请先图形验证');
        }
        $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
        $geetest = new GeetestLib($geetest_id, $geetest_key);
        if (session('pro_gtserver') == 1) {
            $validay = $geetest->success_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'], $this->jyparam);
        } else { //宕机
            $validay = $geetest->fail_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode']);
        }
        if ($validay == 0) {
            $this->error('图形验证码验证失败');
        }
        $res = $model->login($data);

        if ($data['remm'] == 'on') {
            cookie('account', $account, array('expire' => 3600 * 24 * 7, 'prefix' => 'p_'));
        } else {
            cookie(null, 'p_'); //  清空指定前缀的所有cookie值
        }
        if ($res > 0) {
            $this->success('登录成功', url('promote/index'));
        } elseif ($res == -1) {
            $this->error('账户不存在或账户被禁用', url('index'));
        } elseif ($res == -2) {
            return json(['code'=>-2,'msg'=>'密码错误','url'=>url('index')]);
        }
    }

    public function register()
    {
        $config = cmf_get_option('promote_set');
        if ($config['ch_user_allow_register'] != 1) {
            $this->error('注册功能已关闭');
        }
        $model = new PromoteModel;
        $data = $this->request->param();
        $data['account'] = $data['reaccount'];
        $data['password'] = $data['regpassword'];
        $data['password_confirm'] = $data['regrepassword'];
        unset($data['reaccount'], $data['regpassword'], $data['regrepassword'], $data['remember']);
        $rule = [
            'account' => 'require|min:6|max:30|unique:promote|regex:^[A-Za-z0-9]+$',
            'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$|confirm',
            'real_name' => 'checkChineseName',
            'mobile_phone' => 'isMobileNO',
        ];
        $msg = [
            'password.confirm' => '两次密码不一致',
        ];
        $validate = new PromoteValidate($rule, $msg);
        $valires = $validate->scene('register')->check($data);
        if (!$valires) {
            $this->error($validate->getError());
        } else {
            $res = $model->register($data);
            if ($res) {
                //判断是否自动审核
                $autoconfig = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit'))->find();
                if ($autoconfig['status'] == 1) {
                    $save['last_login_time'] = time();
                    $model->where('id', $res)->update($save);
                    session('PID', $res);
                    session('PNAME', $data['account']);
                    session('PARENT_ID', 0);
                    $this->success('注册成功', url('Promote/index'));
                } else {
                    $this->success('注册成功');
                }
            } else {
                $this->error('注册失败');
            }
        }
    }

    //注册协议
    public function register_doc()
    {
        $articleapi = new ApiService;
        $param['where']['post.id'] = 15;
        $articles = $articleapi->articles($param);
        $data = empty($articles) ? [] : $articles['articles']->toarray();
        $this->assign('data', $data);
        return $this->fetch();
    }

    //全部应用
    public function game_list()
    {
        if (AUTH_GAME != 1) {
            $this->error('请先购买游戏权限');
        }
        $base = new BaseHomeController;
        $model = new GameModel;
        $request = $this->request->param();
        if ($request['gt'] != '') {
            $map['game_type_id'] = $request['gt'];
        }
        if ($request['dt'] != '') {
            $map['sdk_version'] = $request['dt'];
        }
        $map['game_status'] = 1;
        $exend['field'] = 'MAX(id) as game_id,group_concat(id) as id,game_name,sort,game_type_id,game_type_name,icon,relation_game_id,relation_game_name,group_concat(sdk_version) as sdk_version';
        $exend['order'] = 'sort desc ,game_id desc';
        $exend['group'] = 'relation_game_id';
        $exend['row'] = 20;
        $data = $base->data_list($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    // 联盟公告
    public function notice()
    {
        //文章
        $category = $this->request->param('type') == 2 ? 2 : 4;
        $postService = new PostService();
        $map['category'] = $category;
        $map['post_status'] = 1;
        $lists = $postService->ArticleList($map, false, 10);
        $this->assign('page', $lists->render());
        $this->assign('articles', $lists);

        //开服
        $sdata = [];
        if (AUTH_GAME == 1) {
            $sdata = $this->get_server();
        }
        $this->assign('server', $sdata);
        return $this->fetch();
    }

    //yyh 获取区服
    private function get_server()
    {
        $servermodel = new ServerModel;
        $serverdata = $servermodel->get_promote_server();
        $serverdata = array_chunk($serverdata, 10);
        return $serverdata;
    }
    //文章详情
    //yyh
    public function notice_details()
    {
        // 文章
        $id = $this->request->param('id/d',0);
        $portalPostModel = new PortalPostModel();
        $data = $portalPostModel->where('id', $id)->find();
        if ($data) {
            $portalPostModel->where('id', $data['id'])->setInc('post_hits');
        }
        //开服
        $sdata = [];
        if (AUTH_GAME == 1) {
            $sdata = $this->get_server();
        }
        $this->assign('server', $sdata);
        $this->assign('data', $data);
        return $this->fetch();
    }

    // 关于我们
    public function about()
    {
        $config = cmf_get_option('promote_set');
        $this->assign('promote_set', $config);
        $articleapi = new ApiService;
        $param['where']['post.id'] = 13;//关于我们id
        $articles = $articleapi->articles($param);
        $data = empty($articles) ? [] : $articles['articles']->toarray();
        $this->assign('data', $data);
        return $this->fetch();
    }

    //退出
    public function logout()
    {
        session('PID', null);
        session('PNAME', null);
        session('PARENT_ID', null);
    }

    /**
     * @函数或方法说明
     * 添加极验方法
     * @author: 郭家屯
     * @since: 2019/3/27 15:07
     */
    public function checkgeetest()
    {
        $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
        $geetest = new GeetestLib($geetest_id, $geetest_key);
        $status = $geetest->pre_process($this->jyparam, 1);
        session('pro_gtserver', $status);
        // session('pro_jiyan_user_id', $this->jyparam['user_id']);
        echo $geetest->get_response_str();
        exit;
    }

    /**
     * @函数或方法说明
     * @帮助中心
     * @author: 郭家屯
     * @since: 2019/7/11 10:59
     */
    public function service()
    {
        $model = new KefutypeModel();
        $list = $model->getPromoteLists();
        $data_list = [];
        foreach ($list as $key => $v) {
            if ($v['zititle']) {
                $data_list[$v['name']][] = [
                    'title' => $v['zititle'],
                    'content' => $v['content'],
                    'sort' => $v['sort'],
                ];
            } else {
                $data_list[$v['name']] = [];
            }
        }

        //修改排序
        foreach ($data_list as &$v) {
            $sort = array_column($v, 'sort');
            array_multisort($sort, SORT_DESC, $v);
        }

        $this->assign('data', $data_list);
        return $this->fetch();
    }
}
