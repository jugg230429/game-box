<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\model;

use think\Model;

class UserModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    public function checkUser($data)
    {
        $name = $data["username"];
        if (empty($name)) {
            return ['code'=>0,'msg'=>lang('USERNAME_OR_EMAIL_EMPTY')];
        }
        if (strpos($name, "@") > 0) {//邮箱登陆
            $where['user_email'] = $name;
        } else {
            $where['user_login'] = $name;
        }
        $result = $this->where($where)->find();
        return ['code'=>1,'result'=>(empty($result)?[]:$result->toArray())];
    }
}