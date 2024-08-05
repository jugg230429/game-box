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
use app\promote\model\PromoteapplyModel;
use app\common\controller\BaseHomeController;

class GameController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
    }
    // 游戏列表
    // yyh
    public function apply_game()
    {

        if(PERMI < 1){
            return redirect(url('apply_game_pc'));
        }
        $gamemodel = new \app\game\model\GameModel;
        $base = new BaseHomeController;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $promote = $this->promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        if(PID_LEVEL == 1){
            $ids = get_promote_apply_game_id(PID); // 获取联盟站已申请游戏列表
            //渠道禁止申请游戏
            if ($promote['game_ids']) {
                $ids = array_merge($ids, explode(',', $promote['game_ids']));
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_merge($ids,$game_ids);
            }
            $map['id'] = ['notin', $ids];
            $flag = 1;

        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_diff($ids,$game_ids);
            }
            if($ids){
                $map['id'] = ['in',$ids];
            }else{
                $map['id'] = -1;
            }
            $flag = 2;
        }

        $map['game_status'] = 1;
        $map['sdk_area'] = 0; // 不显示海外游戏
        $map['down_port'] = ['in',[1,3]];
        $map['third_party_url'] = '';
        $map['sdk_version'] = ['lt',3];
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = PID;
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db::table('tab_game')->select()->toArray();
        if(!empty($tmp_game_info)){
            foreach($tmp_game_info as $k=>$v){
                if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if(!in_array($current_promote_id, $only_for_promote_ids)){
                        $forbid_game_ids[] = $v['id'];
                    }else{
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['id'] = ['notin', $ids];
        }
        if($flag == 2 && !empty($allow_game_ids)){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $this->assign('map_id',$map['id']);

        $exend['field'] = 'group_concat(id) as id,game_name,sort,game_type_id,game_type_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,create_time';
        $exend['order'] = 'sdk_version asc,sort desc';
        $exend['group'] = 'relation_game_name';
        $data = $base->data_list($gamemodel, $map, $exend)->each(function ($item, $key) {
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['game_size'] = explode(',', $item['game_size']);
        });
        $gmap['game_status'] = 1;
        $gmap['sdk_version'] = ['lt',3];
        $gmap['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $gmap['id'] = $map['id'];  // 测试游戏不显示,但可以正常进入
        $gmap['sdk_version'] = ['lt',3];
        $aa = get_game_list('id,game_name,relation_game_id,relation_game_name',$gmap,'relation_game_name','sort desc ,id desc');

        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data", $data);
        return $this->fetch();
    }


    public function apply_game_h5()
    {

        if(PERMI < 1){
            return redirect(url('apply_game_pc'));
        }
        $gamemodel = new \app\game\model\GameModel;
        $base = new BaseHomeController;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $promote = $this->promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        if(PID_LEVEL == 1){
            $ids = get_promote_apply_game_id(PID); // 获取联盟站已申请游戏列表
            //渠道禁止申请游戏
            if ($promote['game_ids']) {
                $ids = array_merge($ids, explode(',', $promote['game_ids']));
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_merge($ids,$game_ids);
            }
            $map['id'] = ['notin', $ids];
            $flag = 1;

        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_diff($ids,$game_ids);
            }
            if($ids){
                $map['id'] = ['in',$ids];
            }else{
                $map['id'] = -1;
            }
            $flag = 2;
        }

        $map['game_status'] = 1;
        $map['sdk_area'] = 0; // 不显示海外游戏
        $map['down_port'] = ['in',[1,3]];
        $map['third_party_url'] = '';
        $map['sdk_version'] = 3;
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = PID;
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db::table('tab_game')->select()->toArray();
        if(!empty($tmp_game_info)){
            foreach($tmp_game_info as $k=>$v){
                if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if(!in_array($current_promote_id, $only_for_promote_ids)){
                        $forbid_game_ids[] = $v['id'];
                    }else{
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['id'] = ['notin', $ids];
        }
        if($flag == 2 && !empty($allow_game_ids)){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $this->assign('map_id',$map['id']);

        $exend['field'] = 'group_concat(id) as id,game_name,sort,game_type_id,game_type_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,create_time';
        $exend['order'] = 'sdk_version asc,sort desc';
        $exend['group'] = 'relation_game_name';
        $data = $base->data_list($gamemodel, $map, $exend)->each(function ($item, $key) {
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['game_size'] = explode(',', $item['game_size']);
        });

        $gmap['game_status'] = 1;
        $gmap['sdk_version'] = 3;
        $gmap['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $gmap['id'] = $map['id'];  // 测试游戏不显示,但可以正常进入
        $gmap['sdk_version'] = 3;
        $aa = get_game_list('id,game_name,relation_game_id,relation_game_name',$gmap,'relation_game_name','sort desc ,id desc');

        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data", $data);
        return $this->fetch();
    }

    // 游戏列表
    // yyh
    public function apply_game_pc()
    {
        $gamemodel = new \app\game\model\GameModel;
        $base = new BaseHomeController;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        $promote = $this->promote;
        if(PID_LEVEL == 1){
            $ids = get_promote_apply_game_id(PID);
            //渠道禁止申请游戏
            if ($promote['game_ids']) {
                $ids = array_merge($ids, explode(',', $promote['game_ids']));
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_merge($ids,$game_ids);
            }
            $map['id'] = ['notin', $ids];
            $flag = 1;
        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_diff($ids,$game_ids);
            }
            if($ids){
                $map['id'] = ['in',$ids];
            }else{
                $map['id'] = -1;
            }
            $flag = 2;
        }
        $map['game_status'] = 1;
        $map['down_port'] = ['in',[1,3]];
        $map['third_party_url'] = '';
        $map['sdk_version'] = 4;
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = PID;
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db::table('tab_game')->select()->toArray();
        if(!empty($tmp_game_info)){
            foreach($tmp_game_info as $k=>$v){
                if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if(!in_array($current_promote_id, $only_for_promote_ids)){
                        $forbid_game_ids[] = $v['id'];
                    }else{
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['id'] = ['notin', $ids];
        }
        if($flag == 2 && !empty($allow_game_ids)){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $this->assign('map_id',$map['id']);

        $exend['field'] = 'id,game_name,sort,game_type_id,game_type_name,icon,ratio,money,sdk_version,relation_game_id,relation_game_name,game_size,create_time';
        $exend['order'] = 'sort desc,id desc';
        //$exend['group'] = 'relation_game_name';
        $data = $base->data_list($gamemodel, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data", $data);
        return $this->fetch();
    }
    //申请游戏  包括批量申请
    //yyh
    public function apply()
    {
        $data = $this->request->param();
        //批量申请
        if ($data['is_pi'] == 1) {
            $relation_game_ids = explode(',', $data['game_id']);
            $relation_game_ids = array_unique(array_filter($relation_game_ids));
            $relation_game_ids = get_relation_game_id($relation_game_ids);
            $idstr = implode(',', $relation_game_ids);
            //渠道禁止申请游戏
            $promote = $this->promote;
            if ($promote['game_ids']) {
                $where['id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $game_ids = Db::table('tab_game')->field('id')->where(['game_status' => 1, 'relation_game_id' => ['in', $idstr],'down_port'=>['in',[1,3]]])->where($where)->select()->toarray();
            $game_ids = array_column($game_ids, 'id');
            $map['promote_id'] = PID;
            $all = count($relation_game_ids);//勾选的游戏数量
            static $ok = 0;
            static $error = 0;
            $model = new PromoteapplyModel;
            $model->startTrans();
            foreach ($game_ids as $key => $value) {
                $map['game_id'] = $value;
                $res = $model->field('id')->where($map)->find();
                if (!empty($res)) {
                    $error++;
                } else {
                    $game = Db::table('tab_game')->field('game_name,sdk_version,money,ratio,down_port,ios_game_address')->find($value);
                    if (empty($game)) {
                        $error++;
                    }
                    $add['game_id'] = $value;
                    $add['promote_id'] = PID;
                    $add['sdk_version'] = $game['sdk_version'];
                    $add['promote_money'] = PID_LEVEL == 1 ? $game['money']:0;
                    $add['promote_ratio'] = PID_LEVEL == 1 ? $game['ratio']:0;
                    $add['down_port'] = $game['down_port'];
                    $add['ios_game_address'] = $game['ios_game_address'];
                    $apply = $model->apply($add);
                    if ($apply) {
                        $ok++;
                    } else {
                        $error++;
                        // $model->rollback();
                    }
                }
            }
            $error = $all - $ok;
            $model->commit();
            $this->success('申请成功');
        } else {
            $map['game_id'] = $data['game_id'];
            $map['promote_id'] = PID;
            $res = Db::table('tab_promote_apply')->field('id')->where($map)->find();
            if (!empty($res)) {
                $this->error('已申请过，请不要重复申请');
            } else {
                $game = Db::table('tab_game')->field('game_name,sdk_version,money,ratio,down_port,ios_game_address')->find($data['game_id']);
                if (empty($game)) {
                    $this->error('申请失败，游戏不存在');
                }
                $add['game_id'] = $data['game_id'];
                $add['promote_id'] = PID;
                $add['sdk_version'] = $game['sdk_version'];
                $add['promote_money'] = PID_LEVEL == 1 ?$game['money']:0;
                $add['promote_ratio'] = PID_LEVEL == 1 ?$game['ratio']:0;
                $add['down_port'] = $game['down_port'];
                $add['ios_game_address'] = $game['ios_game_address'];
                $model = new PromoteapplyModel;
                $apply = $model->apply($add);
                if ($apply) {
                    $this->success('申请成功');
                } else {
                    $this->error('申请失败');
                }
            }
        }
    }
    //我的游戏
    //yyh
    public function my_game()
    {
        if(PERMI < 1){
            return $this->redirect('my_yy_game');
        }
        if(PERMI == 2){
            return $this->redirect('my_h5_game');
        }
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $data = $this->request->param();
        $map['status'] = $data['type'] ? 0 : 1;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $map['tab_promote_apply.sdk_version'] = ['in',[1,2]];
        $promote = $this -> promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        //渠道禁止申请游戏
        if(PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $flag = 1;
        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $map['game_id'] = ['in',$ids];
            if($ids){
                $map['game_id'] = ['in',$ids];
            }else{
                $map['game_id'] = -1;
            }
            $flag = 2;
        }

        $map['g.game_status'] = 1;
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $map['promote_id'] = PID;
        $map['g.down_port'] = ['in',[1,3]];
        $map['g.third_party_url'] = '';
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入

         // 渠道独占 ----------------------------- START
         $current_promote_id = PID;
         $forbid_game_ids = [];
         $allow_game_ids = [];
         $tmp_game_info = Db::table('tab_game')->select()->toArray();
         if(!empty($tmp_game_info)){
             foreach($tmp_game_info as $k=>$v){
                 if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                     $only_for_promote_ids = explode(',', $v['promote_ids2']);
                     if(!in_array($current_promote_id, $only_for_promote_ids)){
                         $forbid_game_ids[] = $v['id'];
                     }else{
                         $allow_game_ids[] = $v['id'];
                     }
                 }
             }
         }
         if(empty($ids)){
            $ids = [];
         }
         if($flag == 1){
             $ids = array_merge($ids, $forbid_game_ids);
             $map['game_id'] = ['notin', $ids];
         }
         if($flag == 2 && !empty($allow_game_ids)){
             if($map['id'] != -1){
                 $ids = array_intersect($ids, $allow_game_ids);
                 $map['game_id'] = ['in',$ids];
             }
         }
         // 渠道独占 ----------------------------- END
         $this->assign('map_id',$map['game_id']);

        $map['game_is_open'] = 1;//渠道申请游戏数据的显示状态-20210803-byh

        $exend['field'] = 'group_concat(game_id) as id,group_concat(g.down_port) as down_port,game_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(g.sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,group_concat(status) as applystatus,group_concat(enable_status) as enable_status,group_concat(dow_url) as dow_url,group_concat(pack_type) as pack_type,group_concat(promote_ratio) as promote_ratio,group_concat(promote_money) as promote_money,group_concat(material_url) as material_url,group_concat(pack_url) as pack_url,group_concat(is_upload) as is_upload,is_h5_share_show';
        $exend['order'] = 'tab_promote_apply.id desc';
        $exend['group'] = 'g.relation_game_id';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend)->each(function ($item, $key) {
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['promote_ratio'] = explode(',', $item['promote_ratio']);
            $item['promote_money'] = explode(',', $item['promote_money']);
            $item['game_size'] = explode(',', $item['game_size']);
            $item['material_url'] = explode(',', $item['material_url']);
            $item['pack_url'] = explode(',', $item['pack_url']);
            $item['enable_status'] = explode(',', $item['enable_status']);
            $item['is_upload'] = explode(',', $item['is_upload']);
            $item['pack_type'] = explode(',', $item['pack_type']);
            $item['down_port'] = explode(',', $item['down_port']);
        });
        // dump($data->toarray());exit;
        // 获取分页显示
        $page = $data->render();
        $this->assign('promote_level',PID_LEVEL);
        $this->assign("page", $page);
        $this->assign("data_lists", $data);


        // 获取已申请游戏id
        //渠道禁止申请游戏
        if (PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
        } else {
            $promote_id = $this -> promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if ($promote['game_ids']) {
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids, $top_ids);
            }
            $map['game_id'] = ['in', $ids];
        }
        $applyWhere = [];
        $applyWhere['promote_id'] = PID;
        $applyWhere['sdk_version'] = ['in', [1, 2]];
        $mPromoteApply = new PromoteapplyModel();
        $apply_game_id = $mPromoteApply -> where($applyWhere) -> column('game_id');
        $this -> assign('apply_game_id', $apply_game_id);

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @H5游戏列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/6/28 17:52
     */
    public function my_h5_game()
    {
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $data = $this->request->param();
        $map['status'] = $data['type'] ? 0 : 1;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $map['tab_promote_apply.sdk_version'] = 3;
        $promote = $this -> promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        //渠道禁止申请游戏
        if(PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $flag = 1;
        }else{
            $promote_id = $this->promote['top_promote_id']?:$this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $map['game_id'] = ['in',$ids];
            if($ids){
                $map['game_id'] = ['in',$ids];
            }else{
                $map['game_id'] = -1;
            }
            $flag = 2;
        }
        $map['g.game_status'] = 1;
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = PID;
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db::table('tab_game')->select()->toArray();
        if(!empty($tmp_game_info)){
            foreach($tmp_game_info as $k=>$v){
                if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if(!in_array($current_promote_id, $only_for_promote_ids)){
                        $forbid_game_ids[] = $v['id'];
                    }else{
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if(empty($ids)){
           $ids = [];
        }
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['game_id'] = ['notin', $ids];
        }
        if($flag == 2 && !empty($allow_game_ids)){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['game_id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $this->assign('map_id',$map['game_id']);

        $map['promote_id'] = PID;
        $map['game_is_open'] = 1;//渠道申请游戏数据的显示状态-20210803-byh
        $exend['field'] = 'game_id as id,game_name,icon,ratio,money,g.material_url,g.sdk_version,relation_game_id,relation_game_name,status,promote_ratio,promote_money,and_url,ios_url,and_status,ios_status,and_upload,ios_upload,down_port';
        $exend['order'] = 'tab_promote_apply.id desc';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign('promote_level',PID_LEVEL);
        $this->assign("page", $page);
        $this->assign("data_lists", $data);

        // 获取已申请游戏id
        $applyWhere = [];
        $applyWhere['promote_id'] = PID;
        $applyWhere['sdk_version'] = 3;
        $mPromoteApply = new PromoteapplyModel();
        $apply_game_id = $mPromoteApply -> where($applyWhere) -> column('game_id');
        $this -> assign('apply_game_id', $apply_game_id);

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @我的页游游戏
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/21 15:01
     */
    public function my_yy_game()
    {
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $data = $this->request->param();
        $map['status'] = $data['type'] ? 0 : 1;
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $map['tab_promote_apply.sdk_version'] = 4;
        $promote = $this -> promote;

        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
        //渠道禁止申请游戏
        if(PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $flag = 1;
        }else{
            $promote_id = $this->promote['top_promote_id']?:$this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $map['game_id'] = ['in',$ids];

            if($ids){
                $map['game_id'] = ['in',$ids];
            }else{
                $map['game_id'] = -1;
            }
            $flag = 2;
        }
        $map['g.game_status'] = 1;
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = PID;
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db::table('tab_game')->select()->toArray();
        if(!empty($tmp_game_info)){
            foreach($tmp_game_info as $k=>$v){
                if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if(!in_array($current_promote_id, $only_for_promote_ids)){
                        $forbid_game_ids[] = $v['id'];
                    }else{
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if(empty($ids)){
           $ids = [];
        }
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['game_id'] = ['notin', $ids];
        }
        if($flag == 2 && !empty($allow_game_ids)){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['game_id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $this->assign('map_id',$map['game_id']);

        $map['promote_id'] = PID;
        $map['game_is_open'] = 1;//渠道申请游戏数据的显示状态-20210803-byh
        $exend['field'] = 'game_id as id,game_name,icon,ratio,money,g.material_url,g.sdk_version,relation_game_id,relation_game_name,status,promote_ratio,promote_money';
        $exend['order'] = 'tab_promote_apply.id desc';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign('promote_level',PID_LEVEL);
        $this->assign("page", $page);
        $this->assign("data_lists", $data);

        // 获取已申请游戏id
        //渠道禁止申请游戏
        if (PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
        } else {
            $promote_id = $this -> promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if ($promote['game_ids']) {
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids, $top_ids);
            }
            $map['game_id'] = ['in', $ids];
        }
        $applyWhere = [];
        $applyWhere['promote_id'] = PID;
        $applyWhere['sdk_version'] = 4;
        $mPromoteApply = new PromoteapplyModel();
        $apply_game_id = $mPromoteApply -> where($applyWhere) -> column('game_id');
        $this -> assign('apply_game_id', $apply_game_id);

        return $this->fetch();
    }

    //渠道打包
    //yyh
    public function package()
    {
        $data = $this->request->param();
        $map['game_id'] = $data['game_id'];
        $map['promote_id'] = PID;
        $res = Db::table('tab_promote_apply')->field('id,status,sdk_version')->where($map)->find();
        if (empty($res)) {
            $this->error('您还未申请该游戏，请先去申请游戏');
        } elseif ($res['status'] == 0) {
            $this->error('游戏还未审核通过，请耐心等待');
        } else {
            $game = Db::table('tab_game_source')->field('id,game_name,file_url')->where(['game_id' => $data['game_id']])->find();
            if (empty($game) || $game['file_url'] == '') {
                $this->error('申请失败，游戏原包不存在');
            }
            $model = new PromoteapplyModel;
            if($res['sdk_version'] == 3){
                $sdk_version = $this->request->param('sdk_version');
                if($sdk_version == 1){
                    $save['and_status'] = 2;
                }else{
                    $save['ios_status'] = 2;
                }
                $apply = $model->where(['id' => $res['id']])->update($save);//准备渠道打包
            }else{
                $apply = $model->where(['id' => $res['id']])->update(['enable_status' => 2, 'pack_type' => 1]);//准备渠道打包
            }
            if ($apply) {
                $this->success('操作成功，已加入打包队列，请耐心等待');
            } else {
                $this->error('操作失败');
            }
        }
    }

    // 子渠道游戏
    public function child_game()
    {
        if(PERMI == 0){
            return $this->redirect('child_game_pc');
        }
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $map['promote_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $promote_id = $this->request->param('promote_id');
        if ($promote_id != '' && in_array($promote_id, array_column($ids, 'id'))) {
            $map['promote_id'] = $promote_id;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['apply_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['apply_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['apply_time'] = ['egt', strtotime($start_time)];
        }
        $map['status'] = 1;
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $map['g.down_port'] = ['in',[1,3]];
        $map['g.game_status'] = 1;
        $map['g.sdk_version'] = ['in',[1,2]];
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $exend['field'] = 'group_concat(tab_promote_apply.id ORDER BY tab_promote_apply.sdk_version) as apply_id,promote_id,group_concat(tab_promote_apply.game_is_open ORDER BY tab_promote_apply.sdk_version) as game_is_open,group_concat(game_id ORDER BY tab_promote_apply.sdk_version) as id,game_name,icon,group_concat(ratio ORDER BY tab_promote_apply.sdk_version) as ratio,group_concat(money ORDER BY tab_promote_apply.sdk_version) as money,group_concat(g.sdk_version ORDER BY tab_promote_apply.sdk_version) AS sdk_version,relation_game_id,game_type_name,relation_game_name,group_concat(game_size ORDER BY tab_promote_apply.sdk_version) as game_size,group_concat(status ORDER BY tab_promote_apply.sdk_version) as applystatus,group_concat(enable_status ORDER BY tab_promote_apply.sdk_version) as enable_status,group_concat(dow_url ORDER BY tab_promote_apply.sdk_version) as dow_url,group_concat(pack_type ORDER BY tab_promote_apply.sdk_version) as pack_type,group_concat(promote_ratio ORDER BY tab_promote_apply.sdk_version) as promote_ratio,group_concat(promote_money ORDER BY tab_promote_apply.sdk_version) as promote_money,group_concat(apply_time ORDER BY tab_promote_apply.sdk_version) as apply_time,max(apply_time) as apply_time1';
        $exend['order'] = 'apply_time1 desc,tab_promote_apply.id desc';
        $exend['group'] = 'promote_id,g.relation_game_name';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend)->each(function ($item, $key) {
            $item['apply_id'] = explode(',',$item['apply_id']);
            $item['game_is_open'] = explode(',',$item['game_is_open']);
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['promote_ratio'] = explode(',', $item['promote_ratio']);
            $item['promote_money'] = explode(',', $item['promote_money']);
            $item['game_size'] = explode(',', $item['game_size']);
            $item['apply_time'] = explode(',', $item['apply_time']);
            $item['enable_status'] = explode(',', $item['enable_status']);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }


    public function child_game_h5()
    {
        if(PERMI == 0){
            return $this->redirect('child_game_pc');
        }
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $map['promote_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $promote_id = $this->request->param('promote_id');
        if ($promote_id != '' && in_array($promote_id, array_column($ids, 'id'))) {
            $map['promote_id'] = $promote_id;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['apply_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['apply_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['apply_time'] = ['egt', strtotime($start_time)];
        }
        $map['status'] = 1;
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $map['g.down_port'] = ['in',[1,3]];
        $map['g.game_status'] = 1;
        $map['g.sdk_version'] = 3;
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $exend['field'] = 'group_concat(tab_promote_apply.id ORDER BY tab_promote_apply.sdk_version) as apply_id,promote_id,game_is_open,group_concat(game_id ORDER BY tab_promote_apply.sdk_version) as id,game_name,icon,group_concat(ratio ORDER BY tab_promote_apply.sdk_version) as ratio,group_concat(money ORDER BY tab_promote_apply.sdk_version) as money,group_concat(g.sdk_version ORDER BY tab_promote_apply.sdk_version) AS sdk_version,relation_game_id,game_type_name,relation_game_name,group_concat(game_size ORDER BY tab_promote_apply.sdk_version) as game_size,group_concat(status ORDER BY tab_promote_apply.sdk_version) as applystatus,group_concat(enable_status ORDER BY tab_promote_apply.sdk_version) as enable_status,group_concat(dow_url ORDER BY tab_promote_apply.sdk_version) as dow_url,group_concat(pack_type ORDER BY tab_promote_apply.sdk_version) as pack_type,group_concat(promote_ratio ORDER BY tab_promote_apply.sdk_version) as promote_ratio,group_concat(promote_money ORDER BY tab_promote_apply.sdk_version) as promote_money,group_concat(apply_time ORDER BY tab_promote_apply.sdk_version) as apply_time,max(apply_time) as apply_time1';
        $exend['order'] = 'apply_time1 desc,tab_promote_apply.id desc';
        $exend['group'] = 'promote_id,g.relation_game_name';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend)->each(function ($item, $key) {
            $item['apply_id'] = explode(',',$item['apply_id']);
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['promote_ratio'] = explode(',', $item['promote_ratio']);
            $item['promote_money'] = explode(',', $item['promote_money']);
            $item['game_size'] = explode(',', $item['game_size']);
            $item['apply_time'] = explode(',', $item['apply_time']);
            $item['enable_status'] = explode(',', $item['enable_status']);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    // 子渠道游戏
    public function child_game_pc()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $map['promote_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $game_name = $this->request->param('game_name');
        if ($game_name != '') {
            $map['relation_game_name'] = $game_name;
        }
        $promote_id = $this->request->param('promote_id');
        if ($promote_id != '' && in_array($promote_id, array_column($ids, 'id'))) {
            $map['promote_id'] = $promote_id;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['apply_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['apply_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['apply_time'] = ['egt', strtotime($start_time)];
        }
        $map['status'] = 1;
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $map['g.game_status'] = 1;
        $map['g.sdk_version'] = 4;
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $exend['field'] = 'tab_promote_apply.id as apply_id,promote_id,game_id as id,game_name,icon,ratio,money,game_is_open,g.sdk_version,relation_game_id,game_type_name,relation_game_name,status as applystatus,dow_url,promote_ratio,promote_money,apply_time';
        $exend['order'] = 'id desc';
        $exend['group'] = 'promote_id,g.relation_game_name';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * 设置分成比例
     */
    public function setRatioMoney()
    {
        $data = $this->request->param();
        $applyModel = new PromoteapplyModel();
        if($data['value'] <0){
            $this->error('修改数值必须大于0');
        }
        if (!preg_match('/^(((\d|[1-9]\d)(\.\d{1,2})?)|100|100.0|100.00)$/', $data['value'])) {
            $this->error("输入错误，0-100之间的两位小数");
        }
        $map['id'] = $data['promote_id'];
        $map['parent_id'] = PID;
        $res = Db::table('tab_promote')->field('id')->where($map)->find();
        if (empty($res)) {
            $this->error('渠道数据错误');
        }
        if ($applyModel->setRatioMoney($data)) {
            $this->success("修改成功");
        } else {
            $this->error("修改失败");
        }

    }

    /**
     * @函数或方法说明
     * @设置子渠道的分成
     * @author: 郭家屯
     * @since: 2020/9/28 19:06
     */
    public function setprofit()
    {
        $ids = $this->request->param('apply_id');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        $value = $this->request->param('value',0);
        if (!preg_match('/^(((\d|[1-9]\d)(\.\d{1,2})?)|100|100.0|100.00)$/', $value)) {
            $this->error("输入错误，0-100之间的两位小数");
        }

        $model = new PromoteapplyModel();
        $list = $model->field('id,game_id')->where('id','in',$ids)->select()->toArray();
        $is_cpa = $this->promote['pattern'] == 1 ? 1 : 0;
        $promote_ids = get_zi_promote_id(PID);
        Db::startTrans();
        try{
            foreach ($list as $key=>$v){
                $parent = $model->field('promote_ratio,promote_money')->where('promote_id',PID)->where('game_id',$v['game_id'])->find();
                if($is_cpa){
                    $model->where('id',$v['id'])->where('promote_id','in',$promote_ids)->setField('promote_money',($parent['promote_money']-$value) > 0 ? round($parent['promote_money']-$value,2): 0);
                }else{
                    $model->where('id',$v['id'])->where('promote_id','in',$promote_ids)->setField('promote_ratio',($parent['promote_ratio']-$value) > 0 ? round($parent['promote_ratio']-$value,2): 0);
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }

    /**
     * [生成二维码]
     * @param string $url
     * @param int $level
     * @param int $size
     * @author 郭家屯[gjt]
     */
    public function qrcode($url = 'pc.vlcms.com', $level = 3, $size = 4)
    {
        $url = base64_decode(base64_decode($url));
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        ob_clean();
        $object = new \QRcode();
        echo $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    /**
     * 更改子渠道游戏及下级全部游戏的开启状态
     * by:byh-2021-8-3 17:32:31
     */
    public function change_game_is_open()
    {
        $data = $this->request->param();
        if(empty($data['zid'])) $this->error('参数错误');
        $zid = $data['zid'];
        $is_open = empty($data['open_status'])?1:0;
        //修改子渠道游戏的数据开启状态
        $model = new PromoteapplyModel();
        $res = $model->where('id',$zid)->update(['game_is_open'=>$is_open]);
        if($res){
            //查询判断是否有下一级渠道申请的此游戏,存在,则同步操作-当前子渠道为二级或三级
            $z_data = $model->field('game_id,promote_id')->where('id',$zid)->find();
            $zz_promote = Db::table('tab_promote')->where('parent_id',$z_data['game_id'])->column('id');
            //如果存在下下级渠道,查询是否有申请的此游戏,讯在,同步操作
            if(!empty($zz_promote)){
                $map['game_id'] = $z_data['game_id'];
                $map['promote_id'] = ['IN',$zz_promote];
                $model->where($map)->update(['game_is_open'=>$is_open]);
            }
            $this->success('设置成功');
        }else{
            $this->error('操作失败');
        }
    }
}
