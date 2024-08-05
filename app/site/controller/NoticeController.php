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
use app\site\model\NoticeModel;
use app\site\validate\NoviceValidate;
use think\Db;


class NoticeController extends AdminBaseController
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

    public function lists()
    {
        $model = new NoticeModel();
        $base = new BaseController();
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id) {
            $map['game_id'] = $game_id;
        }
        $data = $base->data_list($model, $map);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加系统公告]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $game = get_game_entity($data['game_id']);
            if (empty($game)) $this->error('游戏名称不能为空');
            $validate = new NoviceValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new NoticeModel();
            $data['create_time'] = time();
            $data['game_name'] = get_game_name($data['game_id']);
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            $result = $model->insert($data);
            if ($result) {
                write_action_log("公告通知设置");
                $this->success('添加公告成功', url('lists'));
            } else {
                $this->error('添加公告失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑系统公告]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new NoticeModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new NoviceValidate();
            if (!$validate->scene('edit')->check($data)) {
                $this->error($validate->getError());
            }
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                write_action_log("公告通知设置");
                $this->success('编辑公告成功', url('lists'));
            } else {
                $this->error('编辑公告失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除游戏公告]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new NoticeModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("公告通知设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


}