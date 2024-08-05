<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ActionlogController extends AdminBaseController
{


    /**
     * 行为日志列表
     */
    public function index()
    {
        $row = $this->request->param('row') ?: config('paginate.list_rows');
        $lists = Db::table('tab_admin_action_log')
            ->where(['is_delete' => 1])
            ->order('action_time desc')
            ->paginate($row);
        $this->assign('lists', $lists);
        // 获取分页显示
        $page = $lists->render();
        $this->assign("page", $page);
        return $this->fetch();
    }


    /**
     * 删除行为日志
     */
    public function delete()
    {
        $this->error('功能已禁用');//20210722-byh-956
        $ids = input('ids/a');
        if (!empty($ids)) {

            $res = Db::table('tab_admin_action_log')->where(['id' => ['in', $ids]])->update(['is_delete' => 0]);

            if ($res !== false) {

                write_action_log('删除' . $res . '条行为日志');

                $this->success('成功删除' . $res . '条数据');
            } else {
                $this->error('发生错误,删除失败');
            }
        }
    }

    //清空行为日志
    public function clear()
    {

        $this->error('功能已禁用');//20210722-byh-956
        $res = Db::table('tab_admin_action_log')->where(['is_delete' => 1])->update(['is_delete' => 0]);
        if ($res !== false) {

            write_action_log('清空' . $res . '条行为日志');

            $this->success('成功清理' . $res . '条数据');
        } else {
            $this->error('发生错误,清理失败');
        }

    }


}