<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-19
 */
namespace app\site\controller;

use app\common\controller\BaseController;
use app\site\model\ProtocolModel;
use app\site\validate\ProtocolValidate;
use cmf\controller\AdminBaseController;

class ProtocolController extends AdminBaseController
{
    public function lists()
    {
        $model = new ProtocolModel();
        $base = new BaseController();
        $data = $base->data_list($model);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new ProtocolValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new ProtocolModel();
            $data['create_time'] = time();
            $data['update_time'] = time();
            $result = $model->insert($data);
            if ($result) {
                write_action_log("用户协议设置");
                $this->success('添加成功', url('lists'));
            } else {
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    public function edit()
    {
        $model = new ProtocolModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new ProtocolValidate();
            if (!$validate->scene('edit')->check($data)) {
                $this->error($validate->getError());
            }
            $data['update_time'] = time();
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                write_action_log("用户协议设置");
                $this->success('编辑成功', url('lists'));
            } else {
                $this->error('编辑失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->where('id', $id)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }


    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new ProtocolModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("用户协议设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

}