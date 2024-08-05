<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\common\logic;

use app\common\lib\Util\SlideUtil;

class CaptionLogic
{


    /**
     * 初始获取 验证码
     */
    public function init()
    {
        //echo \Mine\Slide::instance();
        return SlideUtil ::instance();
    }

    /**
     * 获取验证码的html
     *
     * @param GET   : (source)
     *
     * @echo Html | String
     */
    public function captchar()
    {
        //echo Slide::instance(1);
        return SlideUtil ::instance();
    }

    /**
     * 验证码，校验
     *
     * @param POST  : (int)x_value 横坐标，用户滑动结果
     *
     * @echo String : {"Err":"","out":""}
     */
    public function check()
    {
        // 参数过滤
        //Slide::instance(2);
        $msg = SlideUtil ::verify();
        return $msg;

    }

    // 示例验证 [普通版]，示例：
    public function demo()
    {
        // 先验证，如 Readme.md 里面所示过程
        //Slide::instance(3);
        SlideUtil ::instance();
    }


    /**
     * @验证token
     *
     * @author: zsl
     * @since: 2020/1/9 17:33
     */
    public function checkToken($token, $tag,$remove = 1)
    {
        return SlideUtil ::checkToken($token, $tag,$remove);
    }

    /**
     * 清除token
     * @param $tag
     */
    public function clearToken($tag)
    {
        return SlideUtil ::clearToken($tag);
    }
}