<?php

namespace app\channelwap\controller;

use app\promote\model\PromoteappModel;
use app\promote\model\PromoteunionModel;
use app\site\model\AppModel;
use think\Db;
use think\Validate;

class UnionController extends BaseController
{


    /**
     * @申请站点
     *
     * @author: zsl
     * @since: 2020/10/26 11:08
     */
    public function siteApply()
    {

        //联盟站申请
        $config = cmf_get_option('admin_set');
        $this -> assign('admin_set', $config);
        //申请列表
        $model = new AppModel();
        $data = $model -> getPromotelist(PID);
        $this -> assign('data_lists', $data);
        // 获取联盟站配置
        $mPromoteUnion = new PromoteunionModel();
        $union_set = $mPromoteUnion -> field('id,union_id,union_set') -> where(['union_id' => PID]) -> value('union_set');
        $this -> assign('union_set', json_decode($union_set, true));


        return $this -> fetch();
    }


    //申请APP链接
    public function apply()
    {
        if ($this -> request -> isPost()) {
            $app_id = $this -> request -> param('app_id');
            $model = new PromoteappModel();
            $app_apply = $model -> where('app_id', $app_id) -> where('promote_id', PID) -> find();
            if ($app_apply) {
                $this -> error('您已申请过了');
            }
            $result = $model -> apply($app_id, PID);
            if ($result) {
                $config = Db ::table('tab_promote_config') -> field('status') -> where(array('name' => 'promote_auto_audit_app')) -> find();
                if ($config['status'] == 1) {
                    $this -> success('申请成功');
                } else {
                    $this -> success('申请提交成功，请等待审核');
                }
            } else {
                $this -> error('申请失败');
            }
        } else {
            $this -> error('申请失败');
        }
    }


    //申请站点
    public function apply_domain_url()
    {
        $request = $this -> request -> param();
        $model = new PromoteunionModel;
        $site_url = $request['site_url'];
        if (strpos($site_url, "http") !== false) {
            $post['domain_url'] = $site_url;
        } else {
            $post['domain_url'] = "http://$site_url";
        }
        if ($request['is_bohui'] == '1') {
            $r = $model ::field('id,status') -> where(['union_id' => PID]) -> find();
            $r = empty($r) ? array() : $r -> toArray();
            if ($r['status'] != - 1) {
                $this -> error('数据错误，申请失败');
            }
            $is_update = 1;
            $data['id'] = $r['id'];
        } else {//申请
            $r = $model -> where(['domain_url' => $post['domain_url']]) -> find();
            $r = empty($r) ? array() : $r -> toArray();
            if (!empty($r) && $r['union_id'] != PID) {
                $this -> error("该域名已被绑定~");
            } elseif ($r['union_id'] == PID) {
                $this -> error('您已申请过,请不要重复申请');
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
        if (!$validate -> check($post)) {
            $this -> error($validate -> getError());
        }
        $data['union_id'] = PID;
        $data['union_account'] = PNAME;
        $data['domain_url'] = $post['domain_url'];
        $data['apply_domain_type'] = $request['apply_domain_type'] ?: 0;
        $model = new PromoteunionModel;
        if ($is_update) {
            $res = $model -> update_union($data);
        } else {
            $res = $model -> apply_union($data);
        }
        if ($res === false) {
            $this -> error('申请失败');
        } else {
            $this -> success('申请提交成功，请等待审核');
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

    /**
     * @函数或方法说明
     * @配置保存
     * @author: 郭家屯
     * @since: 2020/11/6 14:20
     */
    public function domain_set()
    {
        $request = $this->request->param();
        $model = new PromoteunionModel;
        $map['union_id'] = PID;
        $resule = $model->field('union_set')->where($map)->find();

        if(!empty($resule['union_set'])){
            $config = json_decode($resule['union_set'], true);
            $data = array_merge($config,$request);
        }else{
            $data = $request;
        }
        $data = json_encode($data);
        $result = $model->where($map)->setField('union_set', $data);
        
        if ($result !== false) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    public function create_group()
    {
        return $this->fetch();
    }

    /**
     * @文档管理
     *
     * @author: zsl
     * @since: 2020/9/1 14:41
     */
    public function article()
    {
        return $this -> fetch();
    }

    public function get_article()
    {
        $lists = Db ::table('tab_promote_union_article') -> where('promote_id', '=', PID) -> select();
        return json($lists);
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
            $res = Db ::table('tab_promote_union_article')->field(true) -> insert($param);
            if (false === $res) {
                $this -> error('添加失败');
            }
            $this -> success('添加成功');

        }
        return $this -> fetch();
    }

}
