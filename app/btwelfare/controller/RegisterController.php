<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\RegisterLogic;
use cmf\controller\AdminBaseController;

class RegisterController extends AdminBaseController
{


    /**
     * @注册福利列表
     *
     * @author: zsl
     * @since: 2021/1/14 17:17
     */
    public function lists()
    {
        $param = $this -> request -> param();
        $lRegister = new RegisterLogic();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        $lists = $lRegister -> adminLists($param)->each(function($item) use ($ys_show_admin){

            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $item['user_account'] = get_ys_string($item['user_account'],$ys_show_admin['account_show_admin']);
            }
            return $item;
        });

        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();
    }


}