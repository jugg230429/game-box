<?php

namespace app\api\controller;

use app\member\model\UserLoginRecordModel;
use app\member\model\UserLoginRecordMongodbModel;
use cmf\controller\HomeBaseController;

class ToMongoController extends HomeBaseController
{

    /**
     * @ 转移tab_user_login_record到mongodb
     * @ url : /api/to_mongo/userLoginRecord
     *
     * @author: zsl
     * @since: 2021/7/15 19:21
     */
    public function userLoginRecord()
    {
        if (file_exists('mongo.lock')) {
            die('已完成迁移,如需再次执行请删除/public/mongo.lock文件');
        }

        try{

            $page = $this -> request -> param('page', 1, 'intval');
            $mUserLoginRecord = new UserLoginRecordModel();
            $mUserLoginRecordMongodb = new UserLoginRecordMongodbModel();
            $lists = $mUserLoginRecord -> page($page, 100000) -> select();
            if (!$lists -> isEmpty()) {
                $mUserLoginRecordMongodb -> insertAll($lists -> toArray());
                $jumpUrl = url('api/ToMongo/userLoginRecord', ['page' => $page + 1]);
                echo "<script type='text/javascript'> location.href= '" . $jumpUrl . "' </script>";
                die();
            }
            touch('mongo.lock');
            echo 'end';
        }catch (\Exception $e){
            die('同步失败,请检查mongodb服务是否开启');
        }


    }

}
