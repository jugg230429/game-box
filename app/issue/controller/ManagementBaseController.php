<?php

namespace app\issue\controller;

use app\issue\logic\PlatformLogic;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Request;

class ManagementBaseController extends HomeBaseController
{


    public function __construct(Request $request = null)
    {
        parent ::__construct($request);
        $this -> checkLogin();
        //当前登录管理员id
        define('OID', session('issue_open_user_info.id'));
        //验证认证权限
        $this -> checkAuthStatus();
        //设置当前选择平台
        $this -> setPlatform();
        define('PID', session('platform_id'));

    }


    /**
     * @检查用户登录状态
     *
     * @author: zsl
     * @since: 2020/7/11 14:22
     */
    private function checkLogin()
    {
        if (!session('issue_open_user_info')) {
            $this -> error('登录信息过期,请重新登录', url('issue/index/index'));
        }
    }

    /**
     * @检查认证状态
     *
     * @author: zsl
     * @since: 2020/7/20 15:52
     */
    private function checkAuthStatus()
    {
        $auth_status = Db::table('tab_issue_open_user')->where(['id'=>OID])->value('auth_status');
        $action = $this->request->controller();
        if($action!='Auth'){
            if($auth_status!='1'){
                $this->redirect(url('issue/auth/index'));
            }
        }

    }


    /**
     * @设置当前选中平台
     *
     * @author: zsl
     * @since: 2020/7/13 19:22
     */
    private function setPlatform()
    {

        //查询平台列表
        $lPlatform = new PlatformLogic();
        $platformList = $lPlatform -> getUserPlatform(['open_user_id' => OID]);
        if (empty($platformList)) {
            $this -> error('暂无平台,请联系管理员', url('issue/index/index'));
        }
        //手动选择
        $chooseId = input('platform_id', 0, 'intval');
        if (!empty($chooseId)) {
            //验证当前用户是否有此平台权限
            $ids = array_column($platformList, 'id');
            if (!in_array($chooseId, $ids)) {
                $this -> error('平台不存在,请重新选择', url('issue/management/index'));
            }
            session('platform_id', $chooseId);
            $path = $this -> request -> module() . '/' . $this -> request -> controller() . '/' . $this -> request -> action();
            $this -> redirect(url($path));
            return;
        }
        //自动选择
        $currentId = session('platform_id');
        if (!empty($currentId)) {
            return;
        }
        session('platform_id', $platformList[0]['id']);
        return;
    }


}