<?php

namespace app\api\controller;

use app\promote\model\PromoteapplyModel;
use think\Controller;
use think\Db;

class SdkPackController extends Controller
{


    /**
     * @获取待打包数据
     *
     * @author: zsl
     * @since: 2021/7/30 11:42
     */
    public function get()
    {
        if (request() -> isPost()) {
            $result = ['code' => 0, 'data' => []];
            $sdk_version = input('sdk_version', 1, 'intval');
            $mPromoteApply = new PromoteapplyModel();
            $field = "id,game_id,promote_id,pack_url";
            $where = [];
            $where['sdk_version'] = $sdk_version;
            $where['enable_status'] = 2;
            $where['pack_type'] = 3;
            $data = $mPromoteApply -> field($field) -> where($where) -> find();
            if (!empty($data)) {
                $data -> enable_status = 3;//正在打包
                $data -> isUpdate(true) -> allowField(true) -> save();
                $data['promote_account'] = get_promote_name($data['promote_id']);
                $data['game_name'] = get_game_name($data['game_id']);
                $data['version'] = get_system_version();
                $data['game_appid'] = get_game_list('game_appid', ['id' => $data["game_id"]])[0]['game_appid'];
                $result['code'] = 1;
                $result['data'] = $data;
            }
            return json($result);
        }
    }


    /**
     * @打包成功
     *
     * @author: zsl
     * @since: 2021/7/30 14:47
     */
    public function success()
    {
        if (request() -> isPost()) {

            $param = input('post.');
            if (empty($param)) {
                return json(['code' => 0, 'msg' => '请求体不能为空']);
            }
            if (empty($param['id'])) {
                return json(['code' => 0, 'msg' => '缺少id参数']);
            }
            if (empty($param['pack_url'])) {
                return json(['code' => 0, 'msg' => '缺少pack_url参数']);
            }
            $data['id'] = $param['id'];
            $data['pack_url'] = $param['pack_url'];
            $data['enable_status'] = 1;
            $data['is_upload'] = 1;
            Db ::table('tab_promote_apply') -> update($data);
        }
    }


    /**
     * @打包失败
     *
     * @author: zsl
     * @since: 2021/7/30 15:29
     */
    public function fail()
    {
        if (request() -> isPost()) {
            $param = input('post.');
            if (empty($param)) {
                return json(['code' => 0, 'msg' => '请求体不能为空']);
            }
            if (empty($param['id'])) {
                return json(['code' => 0, 'msg' => '缺少id参数']);
            }
            $data['id'] = $param['id'];
            $data['enable_status'] = - 1;
            Db ::table('tab_promote_apply') -> update($data);
        }
    }


}
