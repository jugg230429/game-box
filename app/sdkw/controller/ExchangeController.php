<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-21
 */
namespace app\sdkw\controller;

use app\sdkw\logic\ExchangeLogic;
use think\Request;

class ExchangeController extends BaseController
{
    private $obj;
    /**
     * ExchangeController constructor.
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> obj = new ExchangeLogic();
    }

    /**
     * 方法 exchange
     *
     * @descript 苹果内购
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 16:49
     */
    public function exchange()
    {
        $this -> response($this -> obj -> exchange($this -> input()));
    }

    /**
     * 方法 verify
     *
     * @descript 苹果支付验证
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 17:10
     */
    public function verify()
    {
        $this -> response($this -> obj -> verify($this ->input()));
    }

}