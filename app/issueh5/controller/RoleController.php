<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\issueh5\controller;

use app\issueh5\logic\RoleLogic;
use app\issueh5\validate\RoleValidate;
use cmf\controller\HomeBaseController;

class RoleController extends HomeBaseController
{
    /**
     * @保存角色信息
     *
     * @author: zsl
     * @since: 2020/6/13 14:51
     */
    public function createRole(RoleLogic $lRole)
    {
        $param = $this -> request -> post();
        $validate = new RoleValidate();
        $result = $validate -> scene('create') -> check($param);
        if (!$result) {
            return json(['code'=>1000,'msg'=>$validate -> getError()]);
        }
        $result = $lRole -> createRole($param);
        return json($result);
    }

}