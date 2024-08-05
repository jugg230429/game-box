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
namespace app\channelwap\controller;

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

    /**
     * @函数或方法说明
     * @我的游戏
     * @author: 郭家屯
     * @since: 2020/4/7 11:21
     */
    public function index()
    {
        if(PERMI < 1){
            return $this->redirect('indexpc');
        }
        if(PERMI == 2){
            return $this->redirect('indexh5');
        }
        $promote_level = PID_LEVEL;
        $this->assign('promote_level',$promote_level);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @H5游戏列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/6/30 15:11
     */
    public function indexh5()
    {
        $promote_level = PID_LEVEL;
        $this->assign('promote_level',$promote_level);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @页游游戏列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/6/30 15:11
     */
    public function indexpc()
    {
        $promote_level = PID_LEVEL;
        $this->assign('promote_level',$promote_level);
        return $this->fetch();
    }

    //我的游戏
    //yyh
    public function my_game()
    {
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $data = $this->request->param();
        $map['status'] = $data['type'] ? 0 : 1;
        $game_id = $this->request->param('game_id');
        if ($game_id > 0) {
            $map['relation_game_id'] = $game_id;
        }
        $version = $data['version'];
        if($version == 'h5'){
            $map['g.sdk_version'] = 3;
        }elseif($version == 'pc'){
            $map['g.sdk_version'] = 4;
        }else{
            $map['g.sdk_version'] = ['in',[1,2]];
        }
        $promote = $this -> promote;
        //渠道禁止申请游戏
        if(PID_LEVEL == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $map['game_id'] = ['in',$ids];
        }
        $map['g.game_status'] = 1;
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $map['promote_id'] = PID;
        $map['g.down_port'] = ['in',[1,3]];
        $map['g.third_party_url'] = '';
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['game_is_open'] = 1;//渠道申请游戏数据的显示状态-20210803-byh

        $exend['field'] = 'group_concat(game_id) as id,status,game_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(g.sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,group_concat(status) as applystatus,group_concat(enable_status) as enable_status,group_concat(dow_url) as dow_url,group_concat(pack_type) as pack_type,group_concat(promote_ratio) as promote_ratio,group_concat(promote_money) as promote_money,group_concat(material_url) as material_url,group_concat(pack_url) as pack_url,group_concat(is_upload) as is_upload';
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
            if($item['sdk_version'][0] == 3){
                $item['down_status'] = 1;
                $item['down_url'] = url('mobile/Downfile/indexh5',['gid'=>$item['relation_game_id'],'pid'=>PID],'',true);
                $item['img_url'] = url('Game/qrcode',['url'=>base64_encode(base64_encode($item['down_url']))],'',true);
            }if($item['sdk_version'][0] == 4){
                $item['down_status'] = 1;
                $item['down_url'] = url('@media/Game/ydetail',['game_id'=>$item['relation_game_id'],'pid'=>PID],'',true);
                $item['img_url'] = url('Game/qrcode',['url'=>base64_encode(base64_encode($item['down_url']))],'',true);
            }else{
                $item['down_status'] = 0;
                foreach ($item['enable_status'] as $key=>$v){
                    if($v == 1){
                        $item['down_status'] = 1;
                        $item['down_url'] = url('mobile/Downfile/index',['gid'=>$item['relation_game_id'],'pid'=>PID],'',true);
                        $item['img_url'] = url('Game/qrcode',['url'=>base64_encode(base64_encode($item['down_url']))],'',true);
                    }
                }
            }
        });
       return json($data);
    }
    // 游戏列表
    // yyh
    public function apply_game()
    {
        if(PERMI < 1){
            return redirect(url('apply_ygame'));
        }
        $promote_level = PID_LEVEL;
        if($promote_level <= 1){
            $promote_level = 1;
        }
        $this->assign('promote_level',$promote_level);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @申请页游
     * @author: 郭家屯
     * @since: 2020/9/23 13:41
     */
    public function apply_ygame()
    {
        $promote_level = PID_LEVEL;
        if($promote_level <= 1){
            $promote_level = 1;
        }
        $this->assign('promote_level',$promote_level);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取申请游戏列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/7 15:29\
     */
    public function get_apply_game()
    {
        $gamemodel = new \app\game\model\GameModel;
        $base = new BaseHomeController;
        $game_name = $this->request->param('game_name');
        if (!empty($game_name)) {
            $map['relation_game_name'] = $game_name;
        }
        $promote = $this->promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
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
            $map['tab_game.id'] = ['notin', $ids];
            $flag = 1;

        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
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
        $map['sdk_version'] = ['in',[1,2,3]];
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
        $map2 = [];
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['tab_game.id'] = ['notin', $ids];
        }
        if($flag == 2){
            if($map['tab_game.id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['tab_game.id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END

        $exend['field'] = 'group_concat(tab_game.id) as id,game_name,sort,game_type_id,game_type_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,create_time,group_concat(is_control) as is_control,group_concat(promote_level_limit) as promote_level_limit,group_concat(promote_declare) as promote_declare';
        $exend['order'] = 'sdk_version asc,sort desc';
        $exend['group'] = 'relation_game_name';
        $exend['join1'][] = ['tab_game_attr' => 'a'];
        $exend['join1'][] = 'a.game_id = tab_game.id';
        $data = $base->data_list_join($gamemodel, $map, $exend)->each(function ($item, $key) {
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['game_size'] = explode(',', $item['game_size']);
            $item['is_control'] = explode(',', $item['is_control']);
            $item['promote_level_limit'] = explode(',', $item['promote_level_limit']);
            // 查询渠道等级限制
            foreach ($item['id'] as $k => $v)
            {
                $limit[$k] = get_game_level($v,1);
                $ratio_time[$k] = get_game_begin_time($v,'ratio');
                $money_time[$k] = get_game_begin_time($v,'money');
            }
            $item['ratio_begin_time'] = $ratio_time;
            $item['money_begin_time'] = $money_time;
            // 根据游戏渠道等级限制判断能否申请该游戏
            foreach ($item['promote_level_limit'] as $kk => $vv){
                $is_apply[$kk] = $vv > PROMOTE_LEVEL ? 0 : 1;
            }
            $item['promote_level_limit'] = $limit;
            $item['is_apply'] = $is_apply;
            $item['promote_declare'] = explode(',', $item['promote_declare']);
            return $item;
        });
        return json($data);
    }

    /**
     * @函数或方法说明
     * @获取申请游戏列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/7 15:29\
     */
    public function get_apply_ygame()
    {
        $gamemodel = new \app\game\model\GameModel;
        $base = new BaseHomeController;
        $game_name = $this->request->param('game_name');
        if (!empty($game_name)) {
            $map['relation_game_name'] = $game_name;
        }
        $promote = $this->promote;
        $flag = 0; // 用于标记map[id] 是 in状态 还是 notin 状态
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
            $map['tab_game.id'] = ['notin', $ids];
            $flag = 1;
        }else{
            $promote_id = $this->promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if($promote['game_ids']){
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids,$top_ids);
            }
            $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏
            if($game_ids){
                $ids = array_diff($ids,$game_ids);
            }
            if($ids){
                $map['tab_game.id'] = ['in',$ids];
            }else{
                $map['tab_game.id'] = -1;
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
        $map2 = [];
        if($flag == 1){
            $ids = array_merge($ids, $forbid_game_ids);
            $map['tab_game.id'] = ['notin', $ids];
        }
        if($flag == 2){
            if($map['id'] != -1){
                $ids = array_intersect($ids, $allow_game_ids);
                $map['tab_game.id'] = ['in',$ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $exend['field'] = 'group_concat(tab_game.id) as id,game_name,sort,game_type_id,game_type_name,icon,group_concat(ratio) as ratio,group_concat(money) as money,group_concat(sdk_version) AS sdk_version,relation_game_id,relation_game_name,group_concat(game_size) as game_size,create_time,group_concat(is_control) as is_control,group_concat(promote_level_limit) as promote_level_limit,group_concat(promote_declare) as promote_declare';
        $exend['order'] = 'sdk_version asc,sort desc';
        $exend['group'] = 'relation_game_name';
        $exend['join1'][] = ['tab_game_attr' => 'a'];
        $exend['join1'][] = 'a.game_id = tab_game.id';
        $data = $base->data_list_join($gamemodel, $map, $exend)->each(function ($item, $key) {
            $item['sdk_version'] = explode(',', $item['sdk_version']);
            $item['id'] = explode(',', $item['id']);
            $item['ratio'] = explode(',', $item['ratio']);
            $item['money'] = explode(',', $item['money']);
            $item['game_size'] = explode(',', $item['game_size']);
            $item['is_control'] = explode(',', $item['is_control']);
            $item['promote_level_limit'] = explode(',', $item['promote_level_limit']);
            // 查询渠道等级限制
            foreach ($item['id'] as $k => $v)
            {
                $limit[$k] = get_game_level($v,1);
                $ratio_time[$k] = get_game_begin_time($v,'ratio');
                $money_time[$k] = get_game_begin_time($v,'money');
            }
            $item['ratio_begin_time'] = $ratio_time;
            $item['money_begin_time'] = $money_time;
            // 根据游戏渠道等级限制判断能否申请该游戏
            foreach ($item['promote_level_limit'] as $kk => $vv){
                $is_apply[$kk] = $vv > PROMOTE_LEVEL ? 0 : 1;
            }
            $item['promote_level_limit'] = $limit;
            $item['is_apply'] = $is_apply;
            $item['promote_declare'] = explode(',', $item['promote_declare']);
            return $item;
        });
        return json($data);
    }

    //申请游戏  包括批量申请
    //yyh
    public function apply()
    {
        $data = $this->request->param();
        $map['game_id'] = $data['game_id'];
        $map['promote_id'] = PID;
        $res = Db::table('tab_promote_apply')->field('id')->where($map)->find();
        if (!empty($res)) {
            $this->error('已申请过，请不要重复申请');
        }
        $game = Db::table('tab_game')->field('game_name,sdk_version,money,ratio,down_port,ios_game_address')->find($data['game_id']);
        if (empty($game)) {
            $this->error('申请失败，游戏不存在');
        }
        $add['game_id'] = $data['game_id'];
        $add['promote_id'] = PID;
        $add['sdk_version'] = $game['sdk_version'];
        $add['promote_money'] = PID_LEVEL == 1 ? $game['money']:0;
        $add['promote_ratio'] = PID_LEVEL == 1 ? $game['ratio']:0;
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

    //渠道打包
    //yyh
    public function package()
    {
        $data = $this->request->param();
        $map['game_id'] = $data['game_id'];
        $map['promote_id'] = PID;
        $res = Db::table('tab_promote_apply')->field('id,status')->where($map)->find();
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
            $apply = $model->where(['id' => $res['id']])->update(['enable_status' => 2, 'pack_type' => 1]);//准备渠道打包
            if ($apply) {
                $this->success('操作成功，已加入打包队列，请耐心等待');
            } else {
                $this->error('操作失败');
            }
        }
    }

    /**
     * @函数或方法说明
     * @子渠道游戏
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/7 16:05
     */
    public function child(){
        if(PERMI == 0){
            return $this->redirect('child_pc');
        }
        return $this->fetch();
    }
    // 子渠道游戏
    public function get_child_game()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $map['promote_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $game_name = $this->request->param('game_name');
        if (!empty($game_name)) {
            $map['relation_game_name'] = $game_name;
        }
        $promote_id = $this->request->param('promote_id');
        if ($promote_id >0 && in_array($promote_id, array_column($ids, 'id'))) {
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
        $map['g.sdk_version'] = ['in',[1,2,3]];
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入

        $exend['field'] = 'group_concat(tab_promote_apply.id ORDER BY tab_promote_apply.sdk_version) as apply_id,promote_id,group_concat(game_id ORDER BY tab_promote_apply.sdk_version) as id,game_name,icon,group_concat(ratio ORDER BY tab_promote_apply.sdk_version) as ratio,group_concat(money ORDER BY tab_promote_apply.sdk_version) as money,group_concat(g.sdk_version ORDER BY tab_promote_apply.sdk_version) AS sdk_version,relation_game_id,game_type_name,relation_game_name,group_concat(game_size ORDER BY tab_promote_apply.sdk_version) as game_size,group_concat(status ORDER BY tab_promote_apply.sdk_version) as applystatus,group_concat(enable_status ORDER BY tab_promote_apply.sdk_version) as enable_status,group_concat(dow_url ORDER BY tab_promote_apply.sdk_version) as dow_url,group_concat(pack_type ORDER BY tab_promote_apply.sdk_version) as pack_type,group_concat(promote_ratio ORDER BY tab_promote_apply.sdk_version) as promote_ratio,group_concat(promote_money ORDER BY tab_promote_apply.sdk_version) as promote_money,group_concat(apply_time ORDER BY tab_promote_apply.sdk_version) as apply_time,max(apply_time) as apply_time1';
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
            $item['promote_account'] = get_promote_name($item['promote_id']);
        });
        return json($data);
    }

    public function child_pc(){
        return $this->fetch();
    }

    // 子渠道游戏
    public function get_child_game_pc()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $zimap['parent_id'] = PID;
        $ids = get_promote_list($zimap);
        $map['promote_id'] = empty($ids) ? '-1' : ['in', implode(',', array_column($ids, 'id'))];
        $game_name = $this->request->param('game_name');
        if (!empty($game_name)) {
            $map['relation_game_name'] = $game_name;
        }
        $promote_id = $this->request->param('promote_id');
        if ($promote_id >0 && in_array($promote_id, array_column($ids, 'id'))) {
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
        
        $exend['field'] = 'tab_promote_apply.id as apply_id,promote_id,game_id as id,game_name,icon,ratio,money,g.sdk_version,relation_game_id,game_type_name,relation_game_name,status as applystatus,dow_url,promote_ratio,promote_money,apply_time';
        $exend['order'] = 'id desc';
        $exend['group'] = 'promote_id,g.relation_game_name';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend)->each(function ($item, $key) {
            $item['promote_account'] = get_promote_name($item['promote_id']);
        });
        return json($data);
    }


    /**
     * 设置分成比例
     */
    public function setRatioMoney()
    {
        $data = $this->request->param();
        $applyModel = new PromoteapplyModel();
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
        $value = $this->request->param('value',0);
        if (!preg_match('/^(((\d|[1-9]\d)(\.\d{1,2})?)|100|100.0|100.00)$/', $value)) {
            $this->error("输入错误，0-100之间的两位小数");
        }
        if(empty($ids)){
            $this->error('请选择要操作的数据');
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
}
