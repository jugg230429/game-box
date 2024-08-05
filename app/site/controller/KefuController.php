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
use app\site\model\KefuModel;
use app\site\validate\KefuValidate;
use app\site\model\KefutypeModel;
use think\Db;


class KefuController extends AdminBaseController
{
    public function lists()
    {
        $model = new KefuModel();
        $base = new BaseController();
        $title = $this->request->param('title');
        $platform_type = $this->request->param('platform_type', 1, 'intval');
        $map['tab_kefuquestion.platform_type'] = $platform_type;
        if ($title != '') {
            $map['zititle'] = ['like', '%' . $title . '%'];
        }
        $status = $this->request->param('status');
        if ($status != '') {
            $map['tab_kefuquestion.status'] = $status;
        }
        $type = $this->request->param('type', 0, 'intval');
        if ($type) {
            $map['type'] = $type;
        }
        $extend['field'] = 'tab_kefuquestion.*,kt.name as title';
        $extend['join1'] = [['tab_kefuquestion_type' => 'kt'], 'tab_kefuquestion.type=kt.id', 'left'];
        $extend['order'] = 'tab_kefuquestion.sort desc,tab_kefuquestion.id desc';
        $data = $base->data_list_join($model, $map, $extend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加客服问题]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new KefuValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new KefuModel();
            $data['create_time'] = time();
            $result = $model->insert($data);
            if ($result) {
                write_action_log("客服问题设置");
                $this->success('添加问题成功', url('lists', ['platform_type' => $data['platform_type']]));
            } else {
                $this->error('添加问题失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑问题]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new KefuModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new KefuValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                write_action_log("客服问题设置");
                $this->success('编辑问题成功', url('lists', ['platform_type' => $data['platform_type']]));
            } else {
                $this->error('编辑问题失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除问题]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new KefuModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("客服问题设置");
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
        $model = new KefuModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $status = $this->request->param('status', 0, 'intval');
        $new_status = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $new_status);
        if ($result) {
            write_action_log("客服问题设置");
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }


    /**
     * @修改排序
     *
     * @author: zsl
     * @since: 2021/4/23 9:59
     */
    public function setSort()
    {
        $id = $this -> request -> param('id/d');
        $value = $this -> request -> param('data/d');
        $mKefuQuestion = new KefuModel();
        $result = $mKefuQuestion -> where('id', $id) -> setField('sort', $value);
        if ($result !== false) {
            $this -> success('设置成功');
        } else {
            $this -> error('设置失败');
        }
    }


}
