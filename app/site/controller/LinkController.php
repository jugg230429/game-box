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
use app\site\model\LinkModel;
use app\site\validate\LinkValidate;
use think\Db;


class LinkController extends AdminBaseController
{
    public function lists()
    {
        $model = new LinkModel();
        $base = new BaseController();
        $title = $this->request->param('title');
        if ($title) {
            $map['title'] = ['like', '%' . $title . '%'];
        }
        $data = $base->data_list($model, $map)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加友情链接]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new LinkValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new LinkModel();
            $data['create_time'] = time();
            $result = $model->insert($data);
            if ($result) {
                write_action_log("友情链接设置");
                $this->success('添加友情链接成功', url('lists'));
            } else {
                $this->error('添加友情链接失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑友情链接]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new LinkModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new LinkValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                write_action_log("友情链接设置");
                $this->success('编辑友情链接成功', url('lists'));
            } else {
                $this->error('编辑友情链接失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除友情链接]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new LinkModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("友情链接设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * [设置启用状态]
     * @author 郭家屯[gjt]
     */
    public function setstatus()
    {
        $model = new LinkModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $status = $this->request->param('status', 0, 'intval');
        $new_status = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $new_status);
        if ($result) {
            write_action_log("友情链接设置");
            $this->success('设置成功', url('lists'));
        } else {
            $this->error('设置失败');
        }
    }


}