<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issue\logic;

use app\issue\model\OpenUserModel as UserModel;
use think\Request;
use think\Db;
use app\common\controller\BaseController;

class OpenUserLogic extends BaseController
{
    public function page($request)
    {


        $base = new BaseController();
        $model = new UserModel();
        $map = [];
        if (!empty($request['id'])) {
            $map['id'] = $request['id'];
        }
        if (!empty($request['account'])) {
            $map['id'] = $request['account'];
        }
        if (!empty($request['nickname'])) {
            $map['nickname'] = ['like', '%' . $request['nickname'] . '%'];
        }
        if ($request['status'] === '0' || $request['status'] === '1') {
            $map['status'] = $request['status'];
        }
        if($request['settle_type'] != ''){
            $map['settle_type'] = $request['settle_type'];
        }
        $start_time = $request['start_time'];
        $end_time = $request['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        //查询字段
        $exend['field'] = 'id,account,password,nickname,status,create_time,balance,auth_status,settle_type';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,id desc';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }

    public function add($request)
    {
        $data['account'] = $request['account'];
        $data['password'] = cmf_password($request['password']);
        $data['nickname'] = $request['nickname'];
        $data['status'] = $request['status'] ?: 0;
        $usermodel = new UserModel();
        $res = $usermodel -> save($data);
        return $res;
    }


    public function edit($request)
    {
        if (empty($request['password'])) {
            unset($request['password']);
        } else {
            $request['password'] = cmf_password($request['password']);
        }
        $mUser = new UserModel();
        $res = $mUser -> save($request, ['id' => $request['id']]);
        return $res;
    }


    public function info($id)
    {
        $mUser = new UserModel();
        $where = [];
        $where['id'] = $id;
        $detail = $mUser -> where($where) -> find();
        return $detail;
    }

}