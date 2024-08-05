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
use app\site\model\KefutypeModel;
use app\site\validate\KefutypeValidate;
use think\Db;


class KefutypeController extends AdminBaseController
{
    /**
     * @函数或方法说明
     * @客服问题类型列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/6/25 20:22
     */
    public function lists()
    {
        $model = new KefutypeModel();
        $base = new BaseController();
        $type = $this->request->param('type', 1, 'intval');
        if (!in_array($type, [1, 2, 3])) {
            $type = 1;
        }
        $map['platform_type'] = $type;
        $extend['order'] = 'sort desc,id desc';
        $data = $base->data_list($model, $map, $extend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加客服问题类型]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $model = new KefutypeModel();
            $validate = new KefutypeValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if(empty($data['platform_type'])){
                $data['platform_type'] = 1;
            }
            $data['create_time'] = time();
            $data['admin_name'] = get_admin_name(cmf_get_current_admin_id());
            $result = $model->insert($data);
            if ($result) {
                write_action_log("客服问题类型设置");
                $this->success('添加问题类型成功', url('lists', ['type' => $data['platform_type']]));
            } else {
                $this->error('添加问题类型失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑问题类型]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new KefutypeModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new KefutypeValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $result = $model->where('id', $data['id'])->update($data);
            if ($result !== false) {
                write_action_log("客服问题类型设置");
                $this->success('编辑问题类型成功', url('lists', ['type' => $data['platform_type']]));
            } else {
                $this->error('编辑问题类型失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除问题类型]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new KefutypeModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("客服问题类型删除");
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
        $model = new KefutypeModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $status = $this->request->param('status', 0, 'intval');
        $new_status = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $new_status);
        if ($result) {
            write_action_log("客服问题类型设置");
            $this->success('设置成功', url('lists',['type'=>$this->request->param('type',1,'intval')]));
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * @函数或方法说明
     * @排序设置
     * @author: 郭家屯
     * @since: 2019/6/26 9:26
     */
    public function setsort()
    {
        $model = new KefutypeModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $value = $this->request->param('value', 0, 'intval');
        $result = $model->where('id', $id)->setField('sort', $value);
        if ($result !== false) {
            write_action_log("客服问题类型排序设置");
            $this->success('设置成功', url('lists'));
        } else {
            $this->error('设置失败');
        }
    }


}
