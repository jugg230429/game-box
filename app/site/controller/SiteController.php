<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use cmf\controller\AdminBaseController;

use think\Db;


class SiteController extends AdminBaseController
{

    /**
     * [SDK站点设置]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function sdk_set()
    {
        $data = cmf_get_option('sdk_set');
        $this->assign('data', $data);
        $this->assign("name", 'sdk_set');
        return $this->fetch();
    }

    /**
     * 方法 sdkw_set
     *
     * @descript 海外sdk配置
     *
     * @return mixed
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 14:12
     */
    public function sdkw_set()
    {
        $data = cmf_get_option('sdkw_set');
        $this->assign('data', $data);
        $this->assign("name", 'sdkw_set');
        return $this->fetch();
    }

    /**
     * sdk精简版sdksimplify_set
     * by:byh-20210517
     * @return mixed
     */
    public function sdksimplify_set()
    {
        $data = cmf_get_option('sdksimplify_set');
        $this->assign('data', $data);
        $this->assign("name", 'sdksimplify_set');
        return $this->fetch();
    }

    /**
     * [站点设置保存]
     * @author 郭家屯[gjt]
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $set_type = $data['set_type'];
            if (($set_type == 'admin_set') && empty($data['web_site'])) {
                $this->error('网站域名不能为空');
            }
//            if (($set_type == 'admin_set') && (empty($data['auto_verify_index'])||empty($data['auto_verify_admin']))) {
//                $this->error('用户配置-极验配置不能为空');
//            }
            if (isset($data['pc_cache']) && !preg_match("/^[1-9]\d*|0$/", $data['pc_cache'])) {
                $this->error('网站静态化必须填写非负整数');
            }
            cmf_set_option($set_type, $data);
            write_action_log("网站配置设置");
            $this->success("保存成功！");
        }
    }

    /**
     * [VIP设置]
     * @author 郭家屯[gjt]
     */
    public function vip_set()
    {
        $data = cmf_get_option('vip_set');
        $this->assign('data', $data);
        $this->assign("name", 'vip_set');
        return $this->fetch();
    }

    /**
     * [wap站点设置]
     * @author 郭家屯[gjt]
     */
    public function wap_set()
    {
        $data = cmf_get_option('wap_set');
        $this->assign('data', $data);
        $this->assign("name", 'wap_set');
        return $this->fetch();
    }

    /**
     * [PC站点设置]
     * @author 郭家屯[gjt]
     */
    public function media_set()
    {
        $data = cmf_get_option('media_set');
        $this->assign('data', $data);
        $this->assign("name", 'media_set');
        return $this->fetch();
    }

    /**
     * [商务后台设置]
     * @author 郭家屯[gjt]
     */
    public function business_set()
    {
        $data = cmf_get_option('business_set');
        $this->assign('data', $data);
        $this->assign("name", 'business_set');
        return $this->fetch();
    }

    public function app_set()
    {
        $data = cmf_get_option('app_set');
        $this->assign('data', $data);
        // var_dump($data);exit;
        $this->assign("name", 'app_set');
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @客服设置
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/10/14 15:21
     */
    public function kefu_set(){
        $data = cmf_get_option('kefu_set');
        $this->assign('data', $data);
        $this->assign("name", 'kefu_set');
        return $this->fetch();
    }

    /**
     * [推广站点设置]
     * @author 郭家屯[gjt]
     */
    public function promote_set()
    {
        $data = cmf_get_option('promote_set');
        $this->assign('data', $data);
        $this->assign("name", 'promote_set');
        return $this->fetch();
    }

    /**
     * [管理后台设置]
     * @author 郭家屯[gjt]
     */
    public function admin_set()
    {
        $data = cmf_get_option('admin_set');
        $this->assign('data', $data);
        $this->assign("name", 'admin_set');
        return $this->fetch();
    }

    /**
     * [查看生成qq群key文档]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function creat_group()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @渠道提现设置
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/6/21 14:27
     */
    public function cash_set()
    {
        $data = cmf_get_option('cash_set');
        $this->assign('data', $data);
        $this->assign("name", 'cash_set');
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @小号交易设置
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/28 18:00
     */
    public function transaction_set()
    {
        $data = cmf_get_option('transaction_set');
        $this->assign('data', $data);
        $this->assign("name", 'transaction_set');
        return $this->fetch();
    }


    /**
     * @联运分发站点设置
     *
     * @author: zsl
     * @since: 2020/7/20 14:25
     */
    public function issue_set()
    {
        $data = cmf_get_option('issue_set');
        $this->assign('data', $data);
        $this->assign("name", 'issue_set');
        return $this->fetch();
    }

    /**
     * [玩家平台币提现设置]
     * @return mixed
     * @author byh-20210624
     */
    public function ptb_cash_out_set()
    {
        $data = cmf_get_option('ptb_cash_out_set');
        $this->assign('data', $data);
        $this->assign("name", 'ptb_cash_out_set');
        return $this->fetch();
    }

}
