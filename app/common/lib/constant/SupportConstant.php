<?php

namespace app\common\lib\constant;

class SupportConstant
{
    //扶持申请类型
    const FIRST = 0;        //首次申请
    const FOLLOWING = 1;    //后续申请


    //扶持申请状态
    const UNCHECKED = 0;    //未审核
    const CHECKED = 1;      //已审核
    const DENY = 2;         //已拒绝
    const SEND = 3;         //已发放
    const DELETE = - 1;     //已删除


    //rmb : 元宝/钻石 比例
    const RATIO = 100;


}