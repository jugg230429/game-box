<?php

namespace app\api\controller;

use app\promote\model\PromoteappModel;
use app\site\model\AppModel;
use think\Collection;

class AppPackController extends Collection
{


    public function __construct($items = [])
    {
        parent ::__construct($items);
        //白名单列表
        $white_list = [
                '127.0.0.1', '115.238.251.140'
        ];
        $client_ip = get_client_ip();
        if (!in_array($client_ip, $white_list)) {
            die('err');
        }
    }


    /**
     * @获取待打包数据
     *
     * @since: 2020/11/13 9:17
     * @author: zsl
     */
    public function get()
    {
        if (request() -> isPost()) {
            $result = ['code' => 0, 'data' => []];
            $sdk_version = input('sdk_version', 1, 'intval');
            //获取官方APP下载地址
            $mApp = new AppModel();
            $field = "id,name,file_url,version";
            $where = [];
            $where['version'] = 1;
            $appInfo = $mApp -> field($field) -> where($where) -> find();
            if (empty($appInfo) || empty($appInfo['file_url'])) {
                return $result;
            }
            //获取打包数据
            $mPromoteApp = new PromoteappModel();
            if ($sdk_version == '1') {
                $field = "id,promote_id,app_version,enable_status,is_user_define,app_new_name,app_new_icon,app_new_icon,start_img1,start_img2,create_time,update_time";
            } else {
                $field = "";
            }
            $where = [];
            $where['enable_status'] = 2;//待打包
            $where['app_version'] = $sdk_version;
            $where['status'] = 1;
            $where['is_user_define'] = 1;
            $data = $mPromoteApp -> field($field) -> where($where) -> order('update_time,id') -> find();
            if (!empty($data)) {
                $data -> enable_status = 3;//正在打包
                $data -> isUpdate(true) -> allowField(true) -> save();
                $data['app_new_icon'] = cmf_get_image_url($data['app_new_icon']);
                $data['start_img1'] = cmf_get_image_url($data['start_img1']);
                $data['start_img2'] = cmf_get_image_url($data['start_img2']);
                $data['app_url'] = cmf_get_image_url($appInfo['file_url']);
                $data['promote_account'] = get_promote_name($data['promote_id']);
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
     * @since: 2020/10/23 14:17
     */
    public function success()
    {
        if (request() -> isPost()) {
            $data = input('post.');
            if (empty($data)) {
                return json(['code' => 0, 'msg' => '请求体不能为空']);
            }
            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '缺少id参数']);
            }
            if (empty($data['dow_url'])) {
                return json(['code' => 0, 'msg' => '缺少dow_url参数']);
            }
            $mPromoteApp = new PromoteappModel();
            $where = [];
            $where['id'] = $data['id'];
            $where['enable_status'] = 3;
            $where['is_user_define'] = 1;
            $packItem = $mPromoteApp -> where($where) -> find();
            if (empty($packItem)) {
                return json(['code' => 0, 'msg' => '打包记录不存在']);
            }
            $packItem -> dow_url = $data['dow_url'];
            $packItem -> enable_status = 1;
            $packItem -> isUpdate(true) -> allowField(true) -> save();
            return json(['code' => 1, 'msg' => 'success']);
        }
        return json(['code' => 0, 'msg' => '非法请求']);
    }


    /**
     * @打包失败
     *
     * @author: zsl
     * @since: 2020/10/23 14:23
     */
    public function failed($tag = '')
    {
        if (request() -> isPost()) {
            $data = input('post.');
            if (empty($data)) {
                return json(['code' => 0, 'msg' => '请求体不能为空']);
            }
            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '缺少id参数']);
            }
            $mPromoteApp = new PromoteappModel();
            $where = [];
            $where['enable_status'] = 3;
            $where['id'] = $data['id'];
            $packItem = $mPromoteApp -> where($where) -> find();
            if (empty($packItem)) {
                return json(['code' => 0, 'msg' => '打包订单不存在']);
            }
            $packItem -> enable_status = -1;//打包失败
            $packItem -> isUpdate(true) -> allowField(true) -> save();
            return json(['code' => 1, 'msg' => '']);
        }
        return json(['code' => 0, 'msg' => '非法请求']);
    }

}