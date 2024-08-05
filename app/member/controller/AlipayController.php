<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\member\controller;

use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use app\member\model\AlipayModel;
use think\Db;


class AlipayController extends AdminBaseController
{
    public function lists()
    {
        $model = new AlipayModel();
        $base = new BaseController();
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $extend['field'] = 'id,game_name,appid,status';
        $data = $base->data_list($model, $map, $extend)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [添加认证信息]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $model = new AlipayModel();
            $data['create_time'] = time();
            $game = get_game_entity($data['game_id']);
            if (empty($game)) $this->error('游戏不存在');
            if ($model->where('game_id', $data['game_id'])->find()) $this->error('该游戏已添加认证信息');
            $data['game_name'] = $game['game_name'];
            $result = $model->insert($data);
            if ($result) {
                write_action_log("支付宝快捷认证设置");
                $this->success('添加认证信息成功', url('lists'));
            } else {
                $this->error('添加认证信息失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑认证信息]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new AlipayModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $model->where('id', $data['id'])->update($data);
            if ($result) {
                write_action_log("支付宝快捷认证设置");
                $this->success('编辑认证信息成功', url('lists'));
            } else {
                $this->error('编辑认证信息失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [删除认证信息]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new AlipayModel();
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("支付宝快捷认证设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * [设置状态]
     * @author 郭家屯[gjt]
     */
    public function setstatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $new_status = $status == 1 ? 0 : 1;
        $model = new AlipayModel();
        $result = $model->where('id', $id)->setField('status', $new_status);
        if ($result) {
            write_action_log("支付宝快捷认证设置");
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }


}