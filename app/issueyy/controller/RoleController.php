<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\issueyy\controller;

use app\issueyy\logic\RoleLogic;
use app\issueyy\validate\RoleValidate;
use cmf\controller\HomeBaseController;

class RoleController extends HomeBaseController
{
    /**
     * @保存角色信息
     *
     * @author: zsl
     * @since: 2020/6/13 14:51
     */
    public function get_role_info(RoleLogic $lRole)
    {
        $param = $this -> request -> param();
        $validate = new RoleValidate();
        $result = $validate -> scene('create') -> check($param);
        if (!$result) {
            return json(['code'=>1000,'msg'=>$validate -> getError()]);
        }
        $result = $lRole -> createRole($param);
        return json($result);
    }

    /**
     * @函数或方法说明
     * @分发获取角色信息
     * @param RoleLogic $lRole
     *
     * @author: 郭家屯
     * @since: 2020/11/12 16:08
     */
    public function role(RoleLogic $lRole)
    {
        $param = $this -> request -> param();
        $result = $lRole -> getRole($param);
        echo json_encode($result);die;
    }

}