<?php
/**
 * @属性说明
 *
 * @var ${TYPE_HINT}
 */

namespace app\issue\validate;

use think\Validate;

class PlatformValidate extends Validate
{


    protected $rule = [
            'open_user_id' => 'require',
            'account' => 'require|unique:platform',
            'website' => 'require',
            'pt_type' => 'require',
            'controller_name_h5' => 'requireIf:pt_type.h5,1',
            'controller_name_sy' => 'requireIf:pt_type.sy,1',
            'sdk_config_name' => 'requireIf:pt_type.sy,1',
            'sdk_config_version' => 'requireIf:pt_type.sy,1',
    ];
    protected $message = [
            'open_user_id.require' => '请选择用户账号',
            'account.require' => '请输入平台名称',
            'account.unique' => '平台名称已存在',
            'website.unique' => '请输入平台网址',
            'pt_type.require' => '请选择平台权限',
            'controller_name_h5.requireIf' => '请填写H5控制器名称',
            'controller_name_sy.requireIf' => '请填写手游控制器名称',
            'sdk_config_name.requireIf' => '请选择SDK配置',
            'sdk_config_version.requireIf' => '请选择SDK版本',
    ];
    protected $scene = [
            'create' => ['open_user_id', 'account', 'website', 'pt_type', 'controller_name_h5', 'controller_name_sy', 'sdk_config_name', 'sdk_config_version'],
            'edit' => ['open_user_id', 'website', 'pt_type', 'sdk_config_name', 'sdk_config_version'],
    ];


}