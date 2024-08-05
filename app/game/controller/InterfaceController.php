<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\common\controller\BaseController;
use app\game\model\GameInterfaceModel;
use app\game\validate\GameInterfaceValidate;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

class InterfaceController extends AdminBaseController
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
        $model = new GameInterfaceModel();
        $base = new BaseController();
        $name = $this->request->param('name');
        if ($name != '') {
            $map['name'] = ['like','%'.$name.'%'];
        }
        $exend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $exend);
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
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GameInterfaceValidate();
            $data['name'] = trim($data['name']);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $data['create_time'] = time();
            $model = new GameInterfaceModel();
            $result = $model->insert($data);
            if ($result) {
                $this->success('添加成功', url('lists'));
            } else {
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑游戏类型
     */
    public function edit()
    {
        $model = new GameInterfaceModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GameInterfaceValidate();
            $data['name'] = trim($data['name']);
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
        $type = $model->where('id',$id)->find();
        if (!$type) $this->error('接口不存在');
        $this->assign('data', $type);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @删除
     * @author: 郭家屯
     * @since: 2020/9/14 15:44
     */
    public function del()
    {
        $id = $this -> request -> param('id');
        $ids = $this -> request -> param('ids/a');
        if (empty($id) && empty($ids)) {
            $this -> error('请选择要删除的数据');
        }
        $model = new GameInterfaceModel();
        $result = false;
        if (!empty($id)) {
            $result = $model -> where('id', $id) -> delete();
        }
        if (!empty($ids)) {
            $result = $model -> where(['id' => ['in', $ids]]) -> delete();
        }
        if ($result) {
            $this -> success('删除成功');
        } else {
            $this -> error('删除失败');
        }
    }


}
