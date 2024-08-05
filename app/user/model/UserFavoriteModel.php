<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\user\model;

use think\Db;
use think\Model;

class UserFavoriteModel extends Model
{
    public function favorites()
    {
        $userId = cmf_get_current_user_id();
        $userQuery = Db::name("UserFavorite");
        $favorites = $userQuery->where('user_id', $userId)->order('id desc')->paginate(10);
        $data['page'] = $favorites->render();
        $data['lists'] = $favorites->items();
        return $data;
    }

    public function deleteFavorite($id)
    {
        $userId = cmf_get_current_user_id();
        $userQuery = Db::name("UserFavorite");
        $where['id'] = $id;
        $where['user_id'] = $userId;
        $data = $userQuery->where($where)->delete();
        return $data;
    }

}