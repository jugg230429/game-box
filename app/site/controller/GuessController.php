<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use app\site\model\GuessModel;
use app\site\validate\GuessValidate;
use think\Db;


class GuessController extends AdminBaseController
{
    public function lists()
    {
        $model = new GuessModel();
        $base = new BaseController();
        $data = $base->data_list($model)->each(function ($item, $key) {
            $item['start_time'] = empty($item['start_time']) ? "永久" : date('Y-m-d H:i:s', $item['start_time']);
            $item['end_time'] = empty($item['end_time']) ? "永久" : date('Y-m-d H:i:s', $item['end_time']);
            return $item;
        });
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加链接]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GuessValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new GuessModel();
            $data['create_time'] = time();
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            $result = $model->insert($data);
            if ($result) {
                write_action_log("猜你喜欢设置");
                $this->success('添加链接成功', url('lists'));
            } else {
                $this->error('添加链接失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑链接]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new GuessModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GuessValidate();
            if (!$validate->scene('edit')->check($data)) {
                $this->error($validate->getError());
            }
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                write_action_log("猜你喜欢设置");
                $this->success('编辑链接成功', url('lists'));
            } else {
                $this->error('编辑链接失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除链接]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new GuessModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("猜你喜欢设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


}