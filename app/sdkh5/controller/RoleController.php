<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\sdkh5\controller;

use app\sdkh5\BaseController;
use app\sdkh5\logic\RoleLogic;
use app\sdkh5\validate\RoleValidate;

class RoleController extends BaseController
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
            return $this -> failResult($validate -> getError());
        }
        $result = $lRole -> createRole($param);
        if ($result['code'] == 200) {
            if(is_third_platform($param['promote_id'])){
                try {
                    $UserLogic = new \app\webplatform\logic\UserLogic($param['promote_id']);
                    $UserLogic->saveUserPlayInfo($param);
                }catch (\Exception $e){}
            }
            return $this -> successResult([], $result['message']);
        } else {
            return $this -> failResult($result['message']);
        }
    }

}