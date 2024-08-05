<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteunionModel;
use think\Request;
use think\Db;

class PromoteunionController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
    }

    /**
     * [渠道列表]
     * @return mixed
     * @author yyh
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new PromoteunionModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['union_id'] = $promote_id;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
        $apply_domain_type = $data['apply_domain_type'];
        if ($apply_domain_type != '') {
            $map['apply_domain_type'] = $apply_domain_type;
        }
        $status = $data['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
        $exend['order'] = 'apply_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_union'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * [修改审核状态]
     * @author yyh
     */
    public function changeStatus()
    {
        $ids = $this->request->param('ids/a');
        if (empty($ids)) $this->error('请选择要操作的数据');
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 1 ? -1 : 1;
        $save['dispose_id'] = cmf_get_current_admin_id();
        $save['dispose_time'] = time();
        $save['remark'] = $this->request->param('remark');
        $model = new PromoteunionModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $map['id'] = $value;
            $result = $model->where($map)->update($save);
            if ($result === false) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    /**
     * [set_config_auto_audit 设置渠道]
     * @param string $val [description]
     * @param string $config_key [description]
     * @author [yyh] <[<email address>]>
     */
    public function set_config_auto_audit_union($status = '')
    {
        $config['status'] = $status == 0 ? 1 : 0;
        $res = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_union'))->update($config);

        if ($res !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function view_detail(Request $request)
    {
        $param = $request->param();
        $promote_id = $param['promote_id'] ?? 0;
        if($promote_id > 0){
            $promoteunionModel = new PromoteunionModel;
            $map['union_id'] = $promote_id;
            $resule = $promoteunionModel->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            $baseinfo = json_decode($resule['union_set'], true);
            $this->assign('data',$baseinfo);
            // var_dump($baseinfo);
            return $this->fetch();
        }
        return $this->error('您查看的信息不存在!');

    }
}