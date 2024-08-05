<?php

namespace app\issue\controller;

use app\issue\model\IssueGameModel;
use cmf\controller\HomeBaseController;
use geetest\lib\GeetestLib;

class IndexController extends HomeBaseController
{

    public $jyparam = [];

    public function __construct()
    {
        parent ::__construct();
        $this -> jyparam = [
                "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
                "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];
    }


    /**
     * @首页
     *
     * @author: zsl
     * @since: 2020/7/11 10:59
     */
    public function index()
    {

        //查询游戏库手游
        $game = new IssueGameModel();
        $field = 'id,game_name,icon,status';
        $where = [];
        $where['status'] = 1;
        $where['sdk_version'] = ['neq', 3];
        $syList = $game -> field($field) -> where($where) -> group('game_name') -> limit(6) -> order('sort desc,id desc') -> select();
        $where['sdk_version'] = '3';
        $h5List = $game -> field($field) -> where($where) -> limit(6) -> order('sort desc,id desc') -> select();
        $this -> assign('syList', $syList);
        $this -> assign('h5List', $h5List);
        return $this -> fetch();
    }

    /**
     * @联运介绍
     *
     * @author: zsl
     * @since: 2020/7/11 10:59
     */
    public function introduce()
    {

        return $this -> fetch();
    }

    /**
     * @游戏库
     *
     * @author: zsl
     * @since: 2020/7/11 11:07
     */
    public function library()
    {

        //查询游戏库手游
        $game = new IssueGameModel();
        $field = 'id,game_name,icon,status';
        $where = [];
        $where['status'] = 1;
        $where['sdk_version'] = ['neq', 3];
        $syList = $game -> field($field) -> where($where) ->group('game_name') -> order('sort desc,id desc') ->limit('24') -> select();
        $where['sdk_version'] = '3';
        $h5List = $game -> field($field) -> where($where) -> order('sort desc,id desc') ->limit('24') -> select();
        $this -> assign('syList', $syList);
        $this -> assign('h5List', $h5List);

        return $this -> fetch();
    }

    /**
     * @联系我们
     *
     * @author: zsl
     * @since: 2020/7/11 11:13
     */
    public function contact()
    {

        return $this -> fetch();
    }


    /**
     * @函数或方法说明
     * 添加极验方法
     *
     * @author: 郭家屯
     * @since: 2019/3/27 15:07
     */
    public function checkgeetest()
    {
        $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
        $geetest = new GeetestLib($geetest_id, $geetest_key);
        $status = $geetest -> pre_process($this -> jyparam, 1);
        session('pro_gtserver', $status);
        // session('pro_jiyan_user_id', $this->jyparam['user_id']);
        echo $geetest -> get_response_str();
        exit;
    }


}