<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\game\model\GameTypeModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\game\validate\GameTypeValidate;
use think\Request;
use think\Db;

class GameTypeController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    /**
     * [游戏类型列表]
     * @author 郭家屯[gjt]
     */
    public function lists()
    {
        $model = new GameTypeModel();
        $base = new BaseController();
        $status = $this->request->param('status');
        if ($status != '') {
            $map['status'] = $status;
        }
        $exend['order'] = 'sort desc,id desc';
        $data = $base->data_list($model, $map, $exend)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [新增游戏类型]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        $model = new GameTypeModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GameTypeValidate();
            $data['type_name'] = trim($data['type_name']);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $data['status'] = 1;
            $data['create_time'] = time();
            $data['op_id'] = cmf_get_current_admin_id();
            $data['op_nickname'] = cmf_get_current_admin_name();
            $result = $model->insert($data);
            if ($result) {
                $this->success('类型添加成功', url('lists'));
            } else {
                $this->error('类型添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑游戏类型
     */
    public function edit()
    {
        $model = new GameTypeModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GameTypeValidate();
            $data['type_name'] = trim($data['type_name']);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $result = $model->field(true)->update($data);
            if ($result!==false) {
                $this->success('编辑成功', url('lists'));
            } else {
                $this->error('编辑失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $type = $model->find($id);
        if (!$type) $this->error('游戏类型不存在');
        $this->assign('data', $type);
        return $this->fetch();
    }
    public function setsort()
    {
        $model = new GameTypeModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $sort = $this->request->param('sort', 0, 'intval');
        $result = $model->where('id', $id)->setField('sort', $sort);
        if ($result !== false) {
            $this->success('设置成功', url('lists'));
        } else {
            $this->error('设置失败');
        }
    }
    /**
     * [设置开关状态]
     * @author 郭家屯[gjt]
     */
    public function setstatus()
    {
        $model = new GameTypeModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $status = $this->request->param('status', 0, 'intval');
        $new_status = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $new_status);
        if ($result) {
            $this->success('设置成功', url('lists'));
        } else {
            $this->error('设置失败');
        }
    }


}