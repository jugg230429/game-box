<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class StorageController extends AdminBaseController
{

    /**
     * 文件存储
     * @adminMenu(
     *     'name'   => '文件存储',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文件存储',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $storage = cmf_get_option('storage');
        if (empty($storage)) {
            $storage['type'] = 'Local';
            $storage['storages'] = ['Local' => ['name' => '本地']];
        } else {
            if (empty($storage['type'])) {
                $storage['type'] = 'Local';
            }

            if (empty($storage['storages']['Local'])) {
                $storage['storages']['Local'] = ['name' => '本地'];
            }
        }

        $this->assign($storage);
        return $this->fetch();
    }

    /**
     * 文件存储
     * @adminMenu(
     *     'name'   => '文件存储设置提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文件存储设置提交',
     *     'param'  => ''
     * )
     */
    public function settingPost()
    {
        $post = $this->request->post();

        $storage = cmf_get_option('storage');

        $storage['type'] = $post['type'];
        cmf_set_option('storage', $storage);
        write_action_log("文件存储设置");
        $this->success("设置成功！", '');

    }


}