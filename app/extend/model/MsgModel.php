<?php

namespace app\extend\model;

use think\Model;

class MsgModel extends Model
{

    protected $table = 'tab_sms_config';

    protected $autoWriteTimestamp = true;

    public function statusToStr($code)
    {
        $arr = [0 => '发送成功', 1 => '超过发送限制', 2 => '网络异常', 3 => '24小时内针对同一手机号最多发送10条'];
        return $arr[$code];
    }
}