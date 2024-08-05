<?php

namespace app\issuesy\controller;

use app\issue\model\IssueGameApplyModel;
use app\issue\model\PlatformModel;
use app\issuesy\logic\sdk\BaseLogic;
use app\issuesy\logic\sdk\PlatformLogic;
use app\issuesy\validate\PayValidate;
use think\Request;

class CallbackController extends BaseLogic
{
    public function pay_callback(PlatformLogic $lplat)
    {
        $url_data = [];//回调url自带参数
        $url_data['channel_code'] = $this->request->param('channel_code');
        $url_data['game_id'] = $this->request->param('game_id');

        $request_data = $callback_data = $this->request->param();
        file_put_contents(dirname(__FILE__).'/aaaa.txt',json_encode($request_data));
        $validate = new PayValidate();
        $result = $validate -> scene('callback') -> check($request_data);
        if (!$result) {
            return json(['code'=>0,'msg'=>$validate -> getError()]);
        }
        if(isset($callback_data['channel_code'])) unset($callback_data['channel_code']);
        if(isset($callback_data['game_id'])) unset($callback_data['game_id']);
        $result = $lplat->pay_callback($url_data,$callback_data,$request_data);
        return json($result);
    }
}