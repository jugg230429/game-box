<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-13
 */

namespace app\issuesy\logic\sdk;

use cmf\controller\HomeBaseController;
use think\Request;

class BaseLogic extends HomeBaseController
{
    /**
     * 返回输出
     * @param int $status 状态
     * @param string $return_msg 错误信息
     * @param array $data 返回数据
     * author: gjt 280564871@qq.com
     */
    public function set_message($code=200, $msg = '', $data = [], $type = 0)
    {
        $msg = array(
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        );
        if ($type == 1) {
            echo json_encode($msg, JSON_FORCE_OBJECT);
        } elseif ($type == 2) {
            echo json_encode($msg, true);
        } else {
            echo json_encode($msg,JSON_PRESERVE_ZERO_FRACTION);
        }
        exit;
    }
    /**
     *对数据进行排序
     */
    private function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     *MD5验签加密
     */
    protected function encrypt_md5($param = "", $key = "")
    {
        #对数组进行排序拼接
        if (is_array($param)) {
            $md5Str = implode($this->arrSort($param));
        } else {
            $md5Str = $param;
        }
        $md5 = md5($md5Str . $key);
        return '' === $param ? 'false' : $md5;
    }
}