<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\extend\controller;

use app\extend\model\MsgModel;
use cmf\controller\AdminBaseController;
use think\Request;

class AdminMsgController extends AdminBaseController
{

    public function index()
    {

        $msg = new MsgModel;
        $data = $msg::get(1);
        $this->assign('data', $data);

        return $this->fetch();
    }

    public function doSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            $msg = new MsgModel;
            if ($msg->save($data, ['id' => 1])) {
                write_action_log("短信设置");
                $this->success('修改成功');
            }
        }
        $this->error('修改失败');
    }

}