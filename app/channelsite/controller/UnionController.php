<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelsite\controller;

use think\Db;
use think\Request;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteunionModel;
use app\common\controller\BaseHomeController;
use think\Validate;

class UnionController extends BaseController
{
    // 申请站点
    public function siteApply()
    {
        $config = cmf_get_option('admin_set');
        $this->assign('admin_set', $config);
        return $this->fetch();
    }

    //申请站点
    public function apply_domain_url()
    {
        $request = $this->request->param();
        $model = new PromoteunionModel;
        $site_url = $request['site_url'];
        if (strpos($site_url, "http") !== 0) {
            $post['domain_url'] = "http://$site_url";
        } else {
            $post['domain_url'] = $site_url;
        }
        if ($request['is_bohui']) {
            $r = $model::field('id,status')->where(['union_id' => PID])->find();
            $r = empty($r) ? array() : $r->toArray();
            if ($r['status'] != -1) {
                $this->error('数据错误，申请失败');
            }
            $is_update = 1;
            $data['id'] = $r['id'];
        } else {//申请
            $r = $model->where(['domain_url' => $post['domain_url']])->find();
            $r = empty($r) ? array() : $r->toArray();
            if (!empty($r) && $r['union_id'] != PID) {
                $this->error("该域名已被绑定~");
            } elseif ($r['union_id'] == PID) {
                $this->error('您已申请过,请不要重复申请');
            }
        }
        $rules = [
            'domain_url' => 'require|url',
        ];
        $msg = [
            'domain_url.require' => '域名不能为空',
            'domain_url.url' => '域名格式不正确',
        ];
        $validate = new Validate($rules, $msg);
        if (!$validate->check($post)) {
            $this->error($validate->getError());
        }
        $data['union_id'] = PID;
        $data['union_account'] = PNAME;
        $data['domain_url'] = $post['domain_url'];
        $data['apply_domain_type'] = $request['apply_domain_type'] ?: 0;
        $model = new PromoteunionModel;
        if ($is_update) {
            $res = $model->update_union($data);
        } else {
            $res = $model->apply_union($data);
        }
        if ($res === false) {
            $this->error('申请失败');
        } else {
            $this->success('申请提交成功，请等待审核');
        }

    }

    // 基本信息
    public function siteBase()
    {
        $model = new PromoteunionModel;
        $map['union_id'] = PID;
        $resule = $model->field('union_set')->where($map)->find();
        $resule = empty($resule) ? [] : $resule->toarray();
        $this->assign('config', json_decode($resule['union_set'], true));
        return $this->fetch();
    }

    public function domain_set()
    {
        $request = $this->request->param();
        $model = new PromoteunionModel;
        $map['union_id'] = PID;
        $data = json_encode($request);
        $resule = $model->where($map)->setField('union_set', $data);

        if ($resule !== false) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    public function creat_group()
    {
        return $this->fetch();
    }


    /**
     * @子渠道站点
     *
     * @author: zsl
     * @since: 2020/8/28 13:27
     */
    public function childSite()
    {

        $base = new BaseHomeController;
        $model = new PromoteunionModel();
        //添加搜索条件
        $data = $this -> request -> param();
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        if (!empty($data['union_id'])) {
            $map['union_id'] = $data['union_id'];
        } else {
            $map['union_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        }
        $exend['order'] = 'apply_time desc';
        $exend['field'] = '*';
        $data = $base -> data_list($model, $map, $exend);
        // 获取分页显示
        $page = $data -> render();
        $this -> assign("data_lists", $data);
        $this -> assign("page", $page);
        return $this -> fetch();
    }


    /**
     * @修改审核状态
     *
     * @author: zsl
     * @since: 2020/8/28 14:01
     */
    public function changeStatus()
    {
        $param = $this -> request -> param();
        $model = new PromoteunionModel();
        $where = [];
        $where['id'] = $param['id'];
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $where['union_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $save = [];
        $save['status'] = $param['status'] == '1' ? '1' : '-1';
        $save['dispose_id'] = 0;
        $save['dispose_time'] = time();
        $save['remark'] = $this -> request -> param('remark', '');
        $result = $model -> where($where) -> update($save);
        if (false === $result) {
            $this -> error('操作失败');
        }
        $this -> success('操作成功');
    }


    /**
     * @文档管理
     *
     * @author: zsl
     * @since: 2020/9/1 14:41
     */
    public function article()
    {

        $lists = Db ::table('tab_promote_union_article') -> where('promote_id', '=', PID) -> select();
        $this -> assign('data_lists', $lists);
        return $this -> fetch();
    }

    /**
     * @添加文章
     *
     * @author: zsl
     * @since: 2020/9/1 14:57
     */
    public function articleAdd()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if(empty($param['title'])){
                $this->error('请输入标题');
            }
            if(empty($param['content'])){
                $this->error('请输入内容');
            }
            $param['promote_id'] = PID;
            $param['create_time'] = time();
            $param['update_time'] = time();
            $param['status'] = 1;
            $res = Db ::table('tab_promote_union_article') -> insert($param);
            if (false === $res) {
                $this -> error('添加失败');
            }
            $this -> success('添加成功');

        }
        return $this -> fetch();
    }

    /**
     * @编辑文章
     *
     * @author: zsl
     * @since: 2020/9/1 15:34
     */
    public function articleEdit()
    {
        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {
            if(empty($param['title'])){
                $this->error('请输入标题');
            }
            if(empty($param['content'])){
                $this->error('请输入内容');
            }
            $where = [];
            $where['id'] = $param['id'];
            $where['promote_id'] = PID;
            $param['update_time'] = time();
            $res = Db ::table('tab_promote_union_article') -> where($where) -> update($param);
            if (false === $res) {
                $this -> error('修改失败');
            }
            $this -> success('修改成功');
        }
        $info = Db ::table('tab_promote_union_article') -> where(['id' => $param['id'], 'promote_id' => PID]) -> find();
        $this -> assign('info', $info);
        return $this -> fetch();
    }


    /**
     * @删除文章
     *
     * @author: zsl
     * @since: 2020/9/1 15:53
     */
    public function articleDel()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $where['id'] = $param['id'];
            $where['promote_id'] = PID;
            $res = Db ::table('tab_promote_union_article') -> where($where) -> delete();
            if (false === $res) {
                $this -> error('删除失败');
            }
            $this -> success('删除成功');
        }
    }





}