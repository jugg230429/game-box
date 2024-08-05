<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\channelwap\controller;

use app\site\model\KefutypeModel;
use think\Db;
use app\promote\model\PromoteModel;
use app\portal\service\ApiService;
use app\promote\validate\PromoteValidate1;
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
        if(PID > 0){
            $this->redirect(url('Promote/index'));
        }
        if(cookie('p_promote_login_account') && cookie('p_promote_login_password')){
            $model = new PromoteModel;
            $data['account'] = cookie('p_promote_login_account');
            $data['password'] = cookie('p_promote_login_password');
            $res = $model->login($data);
            if ($res > 0) {
                $this->redirect(url('promote/index'));
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @注册第一步
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/18 9:53
     */
     public function regstep1()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['account'] = $data['account'];
            $data['password'] = $data['password'];
            $data['password_confirm'] = $data['repassword'];
            $rule = [
                    'account' => 'require|min:6|max:30|checkaccount|regex:^[A-Za-z0-9]+$',
                    'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$|confirm',
            ];
            $msg = [
                    'account.checkaccount'=>'用户名已存在',
                    'password.confirm' => '两次密码不一致',
            ];

            $validate = new PromoteValidate1($rule,$msg);
            $valires = $validate->scene('step1')->check($data);
            if (!$valires) {
                $this->error($validate->getError());
            }
            $save = json_encode($data);
            session('register_data',$save);
            return json(['code'=>1,'url'=>url('regstep2')]);
        }
        $articleapi = new ApiService;
        $param['where']['post.id'] = 15;
        $articles = $articleapi->articles($param);
        $data_tmp = empty($articles) ? [] : $articles['articles']->toarray();
        
        $post_title = $data_tmp[0]['post_title'] ?? '';
        // var_dump($data_tmp);exit;
        $this->assign('post_title', $post_title);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @注册第二步
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/18 11:04
     */
    public function regstep2()
    {
        $promote_auto_audit = Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit'])->find();
        $this->assign('promote_auto_audit', $promote_auto_audit['status']);
        return $this->fetch();
    }


    public function agreement()
    {
        return $this->fetch();
    }
	
    /**
     * @函数或方法说明
     * @登录
     * @author: 郭家屯
     * @since: 2020/4/2 17:24
     */
    public function login()
    {
        $model = new PromoteModel;
        $data = $this->request->param();
        $account = $data['account'];
        $password = $data['password'];
        if (empty($account)) {
            $this->error('账号不能为空');
        }
        if (empty($password)) {
            $this->error('密码不能为空');
        }
        $res = $model->login($data);
        cookie('promote_account', $account, array('expire' => 3600 * 24 * 7));
        cookie('promote_login_account',$account,array('expire' => 3600 * 24 * 7, 'prefix' => 'p_'));
        cookie('promote_login_password',$password,array('expire' => 3600 * 24 * 7, 'prefix' => 'p_'));
        if ($res > 0) {
            $this->success('登录成功', url('promote/index'));
        } elseif ($res == -1) {
            $this->error('账号不存在或被禁用');
        } elseif ($res == -2) {
            $this->error('密码错误');
        }
    }

    /**
     * @函数或方法说明
     * @注册
     * @author: 郭家屯
     * @since: 2020/4/2 17:24
     */
    public function register()
    {
        $config = cmf_get_option('promote_set');
        if ($config['ch_user_allow_register'] != 1) {
            $this->error('注册功能已关闭');
        }
        $step1 = session('register_data');
        if(empty($step1))$this->error('请返回上一步填写信息');
        $step = json_decode($step1,true);
        $model = new PromoteModel;
        $data = $this->request->param();
        $data = array_merge($data,$step);
        $rule = [
            'account' => 'require|min:6|checkaccount|max:30|regex:^[A-Za-z0-9]+$',
            'password' => 'require|min:6|max:30|regex:^[A-Za-z0-9]+$|confirm',
            'real_name' => 'checkChineseName',
            'mobile_phone' => 'isMobileNO',
        ];
        $msg = [
            'password.confirm' => '两次密码不一致',
            'account.checkaccount'=>'用户名已存在',
        ];
        $validate = new PromoteValidate1($rule, $msg);
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
                    return json(['code'=>1,'msg'=>'注册成功','url'=>url('Promote/index')]);
                } else {
                    return json(['code'=>1,'msg'=>'注册成功','url'=>url('index/index')]);
                }
            } else {
                return json(['code'=>0,'msg'=>'注册失败']);
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
        // var_dump($data);exit;
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
        cookie(null, 'p_');
        $this->redirect('Index/index');
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
                ];
            } else {
                $data_list[$v['name']] = [];
            }
        }
        $this->assign('data', $data_list);
        return $this->fetch();
    }

}
