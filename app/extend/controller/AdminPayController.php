<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\extend\controller;

use app\extend\model\PayModel;
use cmf\controller\AdminBaseController;
use think\Request;

class AdminPayController extends AdminBaseController
{

    public function config(Request $request)
    {
        $pay_name = empty($request->param('name')) ? 'alipay' : $request->param('name');
        $pay = new PayModel;
        $data = $pay->where(['name' => $pay_name])->find()->getData();
        $data['config'] = json_decode($data['config'], true);

        $this->assign('data', $data);

        $titles = $pay->field('name,title')->select()->toArray();
        $this->assign('titles', $titles);
        $this->assign('pay_name', $pay_name);
        return $this->fetch('config');
    }

    public function doSave(Request $request)
    {
        if ($request->isAjax()) {
            $data = $request->post();
            $id = $data['id'];
            unset($data['id']);
            $pay = new PayModel;
            if ($pay->save(['config' => json_encode($data, JSON_UNESCAPED_UNICODE)], ['id' => $id])) {
                $this->success('支付配置修改成功');
            }
        }
        $this->error('支付配置修改失败');
    }

}