<?php
/**
 * @属性说明
 *
 * @var ${TYPE_HINT}
 */

namespace app\issue\logic;

use app\common\controller\BaseHomeController as BaseController;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use cmf\controller\HomeBaseController;

class PlatformLogic extends HomeBaseController
{


    public function page($request)
    {
        $base = new BaseController();
        $model = new PlatformModel();
        $map = [];
        if (!empty($request['id'])) {
            $map['id'] = $request['id'];
        }
        if (!empty($request['account'])) {
            $map['open_user_id'] = $request['account'];
        }
        if (!empty($request['pl_account'])) {
            $map['account'] = ['like', '%' . $request['pl_account'] . '%'];
        }
        if ($request['status'] === '0' || $request['status'] === '1') {
            $map['status'] = $request['status'];
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
        $exend['field'] = 'id,account,open_user_id,pt_type,total_pay,total_register,min_balance,create_time,status,game_ids';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,id desc';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }


    public function add($param = [])
    {
        $data = [];
        $data['account'] = $param['account'];
        $data['open_user_id'] = $param['open_user_id'];
        $data['website'] = $param['website'];
        $data['status'] = $param['status'];
        $type = 0;
        if($param['pt_type']['h5'] == '1'){
            $type += 1;
        }
        if($param['pt_type']['sy'] == '1'){
            $type += 2;
        }
        if($param['pt_type']['yy'] == '1'){
            $type += 4;
        }
        $type = $type == 7 ? 0 : $type;
        if(in_array($type,[0,2,3,6])){
            $data['sdk_config_name'] = $param['sdk_config_name'];
            $data['sdk_config_version'] = $param['sdk_config_version'];
        }
        $data['pt_type'] = $type;
        $data['platform_config_h5'] = str_replace(PHP_EOL, ',', $param['platform_config_h5']);
        $data['platform_config_sy'] = str_replace(PHP_EOL, ',', $param['platform_config_sy']);
        $data['platform_config_yy'] = str_replace(PHP_EOL, ',', $param['platform_config_yy']);
        $data['service_config'] = str_replace(PHP_EOL, ',', $param['service_config']);
        $data['order_notice_url_h5'] = $param['order_notice_url_h5'];
        $data['pay_notice_url_h5'] = $param['pay_notice_url_h5'];
        $data['order_notice_url_sy'] = $param['order_notice_url_sy'];
        $data['pay_notice_url_sy'] = $param['pay_notice_url_sy'];
        $data['order_notice_url_yy'] = $param['order_notice_url_yy'];
        $data['pay_notice_url_yy'] = $param['pay_notice_url_yy'];
        $data['controller_name_h5'] = $param['controller_name_h5'];
        $data['controller_name_sy'] = $param['controller_name_sy'];
        $data['controller_name_yy'] = $param['controller_name_yy'];
        $usermodel = new PlatformModel();
        $res = $usermodel -> save($data);
        return $res;
    }


    public function edit($param)
    {
        $mUser = new PlatformModel();
        $param['platform_config_h5'] = str_replace(PHP_EOL, ',', $param['platform_config_h5']);
        $param['platform_config_sy'] = str_replace(PHP_EOL, ',', $param['platform_config_sy']);
        $param['service_config'] = str_replace(PHP_EOL, ',', $param['service_config']);
        $res = $mUser -> save($param, ['id' => $param['id']]);
        return $res;
    }


    public function info($id)
    {
        $mPlatform = new PlatformModel();
        $where = [];
        $where['id'] = $id;
        $detail = $mPlatform -> where($where) -> find();
        return $detail;
    }


    /**
     * @获取配置
     *
     * @author: zsl
     * @since: 2020/7/13 11:20
     */
    public function getConfigure($param)
    {
        $mPlatform = new PlatformModel();
        $field = 'id,account,platform_config_h5,platform_config_sy,platform_config_yy,controller_name_h5,controller_name_sy,pt_type,service_config';
        $where = [];
        $where['id'] = $param['id'];
        $configure = $mPlatform -> field($field) -> where($where) -> find();
        if (!empty($configure)) {
            $configure['platform_config_h5'] = str_replace(',', PHP_EOL, $configure['platform_config_h5']);
            $configure['platform_config_sy'] = str_replace(',', PHP_EOL, $configure['platform_config_sy']);
            $configure['platform_config_yy'] = str_replace(',', PHP_EOL, $configure['platform_config_yy']);
            $configure['service_config'] = str_replace(',', PHP_EOL, $configure['service_config']);
        }
        return $configure;
    }

    /**
     * @保存配置
     *
     * @author: zsl
     * @since: 2020/7/13 10:47
     */
    public function saveConfigure($param)
    {
        $mPlatform = new PlatformModel();
        $where = [];
        $where['id'] = $param['id'];
        $platformConfig = explode(PHP_EOL, $param['platform_config']);//转为数组
        $platformConfig = array_filter($platformConfig);//去除空值
        $platformConfig = implode(',', $platformConfig);//转为字符串
        $data = [];
        $data['platform_config_' . $param['game_type']] = $platformConfig;
        $data['controller_name_' . $param['game_type']] = $param['controller_name'];
        $result = $mPlatform -> allowField(true) -> save($data, $where);
        return $result;
    }


    /**
     * @获取用户平台列表
     *
     * @author: zsl
     * @since: 2020/7/13 17:42
     */
    public function getUserPlatform($param = [])
    {
        $mPlatform = new PlatformModel();
        $field = "id,account,open_user_id";
        $where = [];
        $where['open_user_id'] = $param['open_user_id'];
        $where['status'] = 1;
        $lists = $mPlatform -> field($field) -> where($where) -> order('create_time') -> select() -> toArray();
        return $lists;
    }


    /**
     * @获取平台禁用游戏
     *
     * @author: zsl
     * @since: 2020/7/13 20:09
     */
    public function banGameIds($platformId)
    {
        $mPlatform = new PlatformModel();
        $ids = $mPlatform -> where(['id' => $platformId]) -> value('game_ids');
        return $ids ? $ids : '';
    }
}