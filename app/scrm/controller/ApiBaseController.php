<?php

namespace app\scrm\controller;

use app\scrm\lib\Security;
use think\Controller;
use think\Request;
use think\Response;

class ApiBaseController extends Controller
{

    protected $s;
    protected $param;

    public function __construct(Request $request = null)
    {
        parent ::__construct($request);
        //获取请求参数
        if (true === config('scrm.debug')) {
            //开发环境,不加密参数
            $this -> param = $this -> request -> param();
        } else {
            //生产环境,进行验签
            $this -> s = new Security(config('scrm.key'));
            $str = $this -> request -> param('request');
            $param = $this -> s -> decrypt($str);
            if (false === $param) {
                $this -> response(0, '验签失败');
            }
            $this -> param = json_decode($param, true);
        }
    }


    protected function response($code = 200, $msg = '请求成功', $data = [])
    {
        $result = ['code' => $code, 'message' => $msg, 'data' => $data];
        Response ::create($result, 'json') -> send();
        exit();
    }

}
