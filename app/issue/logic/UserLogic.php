<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issue\logic;

use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserLoginRecordModel;
use app\issue\model\UserModel;
use app\issue\model\UserPlayModel;
use think\Request;
use think\Db;
use app\common\controller\BaseController;

class UserLogic extends BaseController
{
    public function page($request)
    {
        $base = new BaseController();
        $model = new PlatformModel();
        $map = [];
        //查询字段
        $exend['field'] = 'id,account,password,nickname,status,create_time';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,id desc';
        //关联游戏类型表
        $data = $base->data_list($model, $map, $exend);
        return $data;
    }

    public function add($request)
    {
        $data['account'] = $request['account'];
        $data['password'] = $request['password'];
        $data['nickname'] = $request['nickname'];
        $data['status'] = $request['status'] ?: 0;
        $usermodel = new PlatformModel();
        $res = $usermodel -> save($data);
        return $res;
    }


    /**
     * @获取用户信息
     *
     * @author: zsl
     * @since: 2021/8/12 20:16
     */
    public function info($param)
    {
        if (empty($param['id'])) {
            return [];
        }
        $mUser = new UserModel();
        $field = 'id,account,game_id,platform_id,cumulative,lock_status,register_ip,last_login_time,last_login_ip,openid,open_user_id,create_time,equipment_num';
        $where = [];
        $where['id'] = $param['id'];
        $info = $mUser -> field($field) -> where($where) -> find();
        if (empty($info)) {
            return $info;
        }
        //登录次数
        $mUserLoginRecord = new UserLoginRecordModel();
        $where = [];
        $where['user_id'] = $param['id'];
        $info -> login_times = $mUserLoginRecord -> where($where) -> count();
        //首次付费时间
        $mSpend = new SpendModel();
        $where = [];
        $where['user_id'] = $param['id'];
        $where['pay_status'] = 1;
        $info -> first_pay_time = $mSpend -> where($where) -> order('pay_time asc') -> value('pay_time');
        //支付次数
        $info -> pay_times = $mSpend -> where($where) -> count();
        //最后付费时间
        $info -> last_pay_time = $mSpend -> where($where) -> order('pay_time desc') -> value('pay_time');
        return $info;
    }


    /**
     * @获取用户登录记录
     *
     * @author: zsl
     * @since: 2021/8/13 9:08
     */
    public function loginRecord($param)
    {
        if (empty($param['id'])) {
            return [];
        }
        $mUserLoginRecord = new UserLoginRecordModel();
        $field = "id,game_id,user_id,platform_id,login_time,login_ip,equipment_num";
        $where = [];
        $where['user_id'] = $param['id'];
        $lists = $mUserLoginRecord -> field($field) -> where($where)->order('login_time desc') -> paginate();
        return $lists;
    }


    /**
     * @用户付费记录
     *
     * @author: zsl
     * @since: 2021/8/13 9:28
     */
    public function spendRecord($param)
    {
        if (empty($param['id'])) {
            return [];
        }
        $mSpend = new SpendModel();
        $field = "id,user_id,platform_id,platform_account,pay_way,game_id,game_name,pay_order_number,pay_amount,pay_time,pay_status,pay_game_status,create_time";
        $where = [];
        $where['user_id'] = $param['id'];
        $lists = $mSpend -> field($field) -> where($where) -> order('create_time desc') -> paginate();
        return $lists;
    }


    /**
     * @用户游戏激活记录
     *
     * @author: zsl
     * @since: 2021/8/13 11:23
     */
    public function activationRecord($param)
    {
        if (empty($param['id'])) {
            return [];
        }
        $mUserPlay = new UserPlayModel();
        $field = "id,game_id,platform_id,create_time,equipment_num";
        $where = [];
        $where['user_id'] = $param['id'];
        $lists = $mUserPlay -> field($field) -> where($where) -> order('create_time desc') -> paginate();
        return $lists;
    }


    /**
     * @获取绑定用户列表
     *
     * @author: zsl
     * @since: 2021/8/18 20:46
     */
    public function get_bind_lists($param)
    {
        if (empty($param['user_id'])) {
            return [];
        }
        $mUser = new UserModel();
        //查询下级是否有用户
        $where = [];
        $where['parent_id'] = $param['user_id'];
        $hasUser = $mUser -> where($where) -> count();
        if (!empty($hasUser)) {
            return [];
        }
        //查询可绑定用户
        $field = "id,parent_id,account,openid";
        $where = [];
        $where['parent_id'] = 0;
        $where['id'] = ['neq', $param['user_id']];
        $lists = $mUser -> field($field) -> where($where) -> select();
        return $lists;
    }


    /**
     * @绑定玩家
     *
     * @author: zsl
     * @since: 2021/8/18 21:27
     */
    public function bind($param)
    {
        $result = ['code' => 1, 'msg' => '绑定成功', 'data' => []];
        if (empty($param['id'])) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        //绑定用户
        $mUser = new UserModel();
        $user = $mUser -> where(['id' => $param['id']]) -> find();
        $user -> parent_id = $param['parent_id'];
        $res = $user -> save();
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '绑定失败';
            return $result;
        }
        return $result;
    }

}
