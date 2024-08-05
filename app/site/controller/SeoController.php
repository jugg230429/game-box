<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use cmf\controller\AdminBaseController;
use app\site\model\SeoModel;
use think\Db;


class SeoController extends AdminBaseController
{

    /**
     * [wap站点seo]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function wap_seo()
    {
        $type = $this->request->param('type', 'wap_index');
        $model = new SeoModel();
        $data = $model->where('name', $type)->find();
        if (empty($data)) $this->error('数据错误');
        $list = $model->field('id,name,title')->where('name', 'like', 'wap_%')->select();
        $this->assign('list', $list);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [seo设置保存]
     * @author 郭家屯[gjt]
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $model = new SeoModel();
            $result = $model->where('name', $data['name'])->update($data);
            if ($result!==false) {
                write_action_log("SEO设置");
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * [PC官网seo]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function media_seo()
    {
        $type = $this->request->param('type', 'media_index');
        $model = new SeoModel();
        $data = $model->where('name', $type)->find();
        if (empty($data)) $this->error('数据错误');
        $list = $model->field('id,name,title')->where('name', 'like', 'media_%')->select();
        $this->assign('list', $list);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [推广平台seo]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function promote_seo()
    {
        $type = $this->request->param('type', 'channel_index');
        $model = new SeoModel();
        $data = $model->where('name', $type)->find();
        if (empty($data)) $this->error('数据错误');
        $list = $model->field('id,name,title')->where('name', 'like', 'channel_%')->order('id asc')->select();
        $this->assign('list', $list);
        $this->assign('data', $data);
        return $this->fetch();
    }

}