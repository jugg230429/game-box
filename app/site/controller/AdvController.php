<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use cmf\controller\AdminBaseController;
use app\site\model\AdvModel;
use app\site\model\AdvposModel;
use app\common\controller\BaseController;
use think\Db;


class AdvController extends AdminBaseController
{
    /**
     * [广告位]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function adv_pos()
    {
        $module = $this -> request -> param('module');
        $where = [];
        if (!empty($module)) {
            $where['module'] = $module;
        }
        $model = new AdvposModel();
        $data = $model -> field('id,name,title,module,width,height,type,status') -> where($where) -> order('module') -> select();
        foreach ($data as $key => $v) {
            if ($v['type'] == 1) {
                $adv = Db ::table('tab_adv') -> field('id') -> where('pos_id', $v['id']) -> find();
                if ($adv) {
                    $data[$key]['add_status'] = 1;
                }
            }
        }
        $this -> assign('data_lists', $data);
        return $this -> fetch();
    }

    /**
     * [广告列表]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function adv_adv()
    {
        $module = input('module', '');
        $pos_id = input('pos_id', '');
        $where = [];
        if (!empty($module)) {
            $where['ap.module'] = $module;
        }
        if (!empty($pos_id)) {
            $where['tab_adv.pos_id'] = $pos_id;
        }
        $model = new AdvModel();
        $base = new BaseController;
        $exend['field'] = 'tab_adv.id,game_id,tab_adv.title,url,sort,start_time,end_time,target,ap.title as pos_title,ap.module';
        $exend['join1'] = [['tab_adv_pos' => 'ap'], 'tab_adv.pos_id=ap.id', 'left'];
        $exend['order'] = 'sort desc';
        $data = $base -> data_list_join($model,$where, $exend) -> each(function($item, $key){
            $item['start_time'] = empty($item['start_time']) ? "永久" : date('Y-m-d H:i:s', $item['start_time']);
            $item['end_time'] = empty($item['end_time']) ? "永久" : date('Y-m-d H:i:s', $item['end_time']);
            return $item;
        });
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('data_lists', $data);
        $this -> assign('page', $page);
        // 获取广告位列表
        $mAdvPos = new AdvposModel();
        $advPostLists = $mAdvPos -> field('id,title') -> where(['status' => 1]) -> select();
        $this -> assign('adv_post_lists', $advPostLists);
        return $this -> fetch();
    }

    /**
     * [编辑广告位]
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new AdvposModel();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $adv_pos = $model->find($id);
        if (empty($adv_pos)) $this->error('广告位不存在');
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $model->where('id', $data['id'])->update($data);
            if ($result !== false) {
                write_action_log("广告位设置");
                $this->success('设置成功', url('adv_pos'));
            } else {
                $this->error('设置失败');
            }
        }
        // 判断是否能修改, 将不能修改的字段, 不允许多图选择的做个区分
        // 广告标识
        // 单图多图
        $not_allow = [
            'app_open',
            'app_hot',
            'app_new'
        ];

        $tmp_name = $adv_pos['name'] ?? '';
        $not_allow_switch = 0;
        if(in_array($tmp_name, $not_allow)){
            $not_allow_switch = 1; // 不允许修改, 只能单图
        }
        $this->assign('not_allow_switch', $not_allow_switch);
        $this->assign('data', $adv_pos);
        return $this->fetch();
    }

    /**
     * [添加广告]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add_adv()
    {
        $id = $this->request->param('id', 0, 'intval');

        $advposmodel = new AdvposModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['data'])) $this->error('请上传广告图');
            if ($data['post_id'] == 4 && empty($data['icon'])) $this->error('请上传广告icon');
            $adv_pos = $advposmodel->field('id,type,module,width,height')->find($data['post_id'])->toArray();
            if (empty($adv_pos)) $this->error('广告位不存在');
            $model = new AdvModel();
            if ($adv_pos['type'] == 1) {
                $adv = $model->where('pos_id', $adv_pos['id'])->field('id')->find();
                if ($adv) {
                    $this->error('单图类型只允许添加一张广告图');
                }
            }
            if($data['type'] == 1 && empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $data['start_time'] = empty($data['start_time']) ? 0 : strtotime($data['start_time']);
            $data['end_time'] = empty($data['end_time']) ? 0 : strtotime($data['end_time']);
            $data['create_time'] = time();
            $result = $model->insert($data);
            if ($result) {
                write_action_log("广告新增");
                $this->success('添加成功', url('adv_adv'));
            } else {
                $this->error('添加失败');
            }
        }
        if (empty($id)) $this->error('参数错误');
        $adv_pos = $advposmodel->field('id,type,module,width,height')->find($id)->toArray();
        $this->assign('pos_id', $id);
        $this->assign('adv_pos',$adv_pos);
        return $this->fetch();
    }

    /**
     * [编辑广告]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit_adv()
    {
        $id = $this->request->param('id', 0, 'intval');
        $model = new AdvModel();
        $adv = $model->find($id);
        $advposmodel = new AdvposModel();
        $adv_pos = $advposmodel->find($adv['pos_id']);
        if (empty($adv)) $this->error('广告不存在');
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['data'])) $this->error('请上传广告图');
            if ($data['post_id'] == 4 && empty($data['icon'])) $this->error('请上传广告icon');
            if (empty($adv_pos)) $this->error('广告位不存在');
            if($data['type'] == 1 && empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $data['start_time'] = empty($data['start_time']) ? 0 : strtotime($data['start_time']);
            $data['end_time'] = empty($data['end_time']) ? 0 : strtotime($data['end_time']);
            $data['create_time'] = time();
            if ($data['type'] == '0') {
                $data['game_id'] = 0;
            }

            $result = $model->where('id', $id)->update($data);
            if ($result !== false) {
                write_action_log("广告编辑");
                $this->success('编辑成功', url('adv_adv'));
            } else {
                $this->error('编辑失败');
            }
        }

        if (empty($id)) $this->error('参数错误');
        $this->assign('data', $adv);
        $this->assign('adv_pos',$adv_pos);
        return $this->fetch();
    }

    public function del_adv()
    {
        $id = $this->request->param('id', 0, 'intval');
        $model = new AdvModel();
        $adv = $model->find($id);
        $result = $model->where('id', $id)->delete();
        if ($result) {
            write_action_log("删除广告");
            $advposmodel = new AdvposModel();
            $adv_pos = $advposmodel->find($adv['pos_id']);
            $this->success('删除成功', url('adv_adv'));
        } else {
            $this->error('删除失败');
        }
    }


    /**
     * @获取广告位
     *
     * @author: zsl
     * @since: 2021/4/25 11:45
     */
    public function get_adv_pos_lists()
    {
        if ($this -> request -> isPost()) {
            $module = $this -> request -> post('module');
            $mAdvPos = new AdvposModel();
            $moduleLists = $mAdvPos -> where(['module' => $module]) -> select();
            $this -> success('请求成功', '', $moduleLists);
        }
        $this -> error('error');
    }


}
