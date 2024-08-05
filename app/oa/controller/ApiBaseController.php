<?php

namespace app\oa\controller;

use app\oa\model\StudioModel;
use think\Controller;
use think\Response;

class ApiBaseController extends Controller
{

    /**
     * @获取平台信息
     *
     * @author: zsl
     * @since: 2021/3/3 19:30
     */
    protected function getStudioInfo($appid)
    {
        if (empty($appid)) {
            return false;
        }
        $mStudio = new StudioModel();
        $field = "id,studio_name,api_key,status";
        $where = [];
        $where['appid'] = $appid;
        $where['status'] = 1;
        $studioInfo = $mStudio -> field($field) -> where($where) -> find();
        return $studioInfo;
    }


    /**
     * @验证超时时间
     *
     * @author: zsl
     * @since: 2021/3/2 11:13
     */
    public function vTimeOut($time)
    {
        if (empty($time)) {
            return false;
        }
        if (abs(time() - $time) > 60 * 5) {
            return false;
        }
        return true;
    }


    /**
     *生成加密字符串
     */
    public function vSign($data, $token)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if ($key == 'sign') {
                continue;
            }
            $str .= $key . '=' . $value . '&';
        }
        $str .= 'key=' . $token;
        return md5($str);
    }


    protected function response($data)
    {
        Response ::create($data, 'json') -> send();
        die();
    }

}