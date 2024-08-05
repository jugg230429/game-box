<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use app\user\model\UserModel;
use think\Validate;

class PublicController extends HomeBaseController
{

    // 用户头像api
    public function avatar()
    {
        $id = $this->request->param("id", 0, "intval");
        $user = UserModel::get($id);

        $avatar = '';
        if (!empty($user)) {
            $avatar = cmf_get_user_avatar_url($user['avatar']);
            if (strpos($avatar, "/") === 0) {
                $avatar = $this->request->domain() . $avatar;
            }
        }

        if (empty($avatar)) {
            $cdnSettings = cmf_get_option('cdn_settings');
            if (empty($cdnSettings['cdn_static_root'])) {
                $avatar = $this->request->domain() . "/static/images/headicon.png";
            } else {
                $cdnStaticRoot = rtrim($cdnSettings['cdn_static_root'], '/');
                $avatar = $cdnStaticRoot . "/static/images/headicon.png";
            }

        }

        return redirect($avatar);
    }

}
