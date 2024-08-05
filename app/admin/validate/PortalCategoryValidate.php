<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\validate;

use app\admin\model\RouteModel;
use think\Validate;

class PortalCategoryValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
        'alias' => 'checkAlias',
    ];
    protected $message = [
        'name.require' => '分类名称不能为空',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

    // 自定义验证规则
    protected function checkAlias($value, $rule, $data)
    {
        if (empty($value)) {
            return true;
        }

        if (preg_match("/^\d+$/", $value)) {
            return "别名不能为纯数字!";
        }

        $routeModel = new RouteModel();
        if (isset($data['id']) && $data['id'] > 0) {
            $fullUrl = $routeModel->buildFullUrl('portal/List/index', ['id' => $data['id']]);
        } else {
            $fullUrl = $routeModel->getFullUrlByUrl($data['alias']);
        }
        if (!$routeModel->existsRoute($value, $fullUrl)) {
            return true;
        } else {
            return "别名已经存在!";
        }

    }
}