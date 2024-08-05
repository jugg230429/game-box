<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\app\controller;


use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\game\logic\WelfareLogic;
use app\game\model\GamecollectModel;
use app\game\model\GameModel;
use app\game\model\ServerModel;
use app\game\model\ServerNoticeModel;
use app\recharge\model\CouponRecordModel;
use app\site\model\AdvModel;
use app\site\service\PostService;
use think\Db;

class GameController extends BaseController
{

    /**
     * [轮播图]
     * @param int $pos_id
     * @author 郭家屯[gjt]
     */
    protected function slide($request=[])
    {
        $model = new AdvModel();
        if($request['sdk_version'] == 2){//苹果
            $slider = $model->getAdv('ios_slider',$request['promote_id']);
            $hot_game_adv = $model->getAdv('ios_hot',$request['promote_id']);
            $new_game_adv = $model->getAdv('ios_new',$request['promote_id']);

        }else{
            $slider = $model->getAdv('app_slider',$request['promote_id']);
            $hot_game_adv = $model->getAdv('app_hot',$request['promote_id']);
            $new_game_adv = $model->getAdv('app_new',$request['promote_id']);
        }
        if(PERMI == 3){
            if($request['sdk_version'] == 1){
                $model = cmf_get_option('app_set')['model_and']?:3;
            }else{
                $model = cmf_get_option('app_set')['model_ios']?:3;
            }
            if($model == 1){
                foreach ($slider as $key=>$v){
                    if($v['sdk_version']!=$request['sdk_version'] && $v['type'] == 1){
                        unset($slider[$key]);
                    }
                }
                if($hot_game_adv['sdk_version']!=$request['sdk_version'] && $hot_game_adv['type'] == 1){
                    $hot_game_adv = [];
                }
                if($new_game_adv['sdk_version']!=$request['sdk_version'] && $new_game_adv['type'] == 1){
                    $new_game_adv = [];
                }
            }elseif($model == 2){
                foreach ($slider as $key=>$v){
                    if($v['sdk_version']!=3 && $v['type'] == 1){
                        unset($slider[$key]);
                    }
                }
                if($hot_game_adv['sdk_version']!=3 && $hot_game_adv['type'] == 1){
                    $hot_game_adv = [];
                }
                if($new_game_adv['sdk_version']!=3 && $new_game_adv['type'] == 1){
                    $new_game_adv = [];
                }
            }
        }
        $list['slide'] = array_values($slider);
        $list['hot'] = $hot_game_adv?:(object)[];
        $list['news'] = $new_game_adv?:(object)[];
        return $list;
    }

    /**
     * @函数或方法说明
     * @开机广告
     * @author: 郭家屯
     * @since: 2020/3/28 16:16
     */
    public function open_adv()
    {
        $model = new AdvModel();
        $request = $this->request->param();
        if($request['sdk_version'] == 1){
            // var_dump(object);exit;
            $data = $model->getAdv('app_open')?:(object)[];
        }else{
            $data = $model->getAdv('ios_open')?:(object)[];
        }
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @引导页
     */
    public function guide()
    {
        $config = cmf_get_option('app_set');
        $data = [];
        if($config['guide_1']){
            $data[] = cmf_get_image_url($config['guide_1']);
        }
        if($config['guide_2']){
            $data[] = cmf_get_image_url($config['guide_2']);
        }
        if($config['guide_3']){
            $data[] = cmf_get_image_url($config['guide_3']);
        }
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @首页获取所有数据
     * @author: 郭家屯
     * @since: 2020/2/19 16:25
     */
    public function index()
    {
        $request = $this->request->param();
        $request['model'] = MODEL;//获取当前模块
        //轮播图
        $adv = $this->slide($request);
        // model =1 是手游,model=2是H5
        $recent_play = []; // 最近在玩

        // if($request['model'] == 1){
        //     //推荐游戏
        //     $recommend = $this->getrecommendlists($request);
            
        // }else{
        //     if($request['user_id']){
        //         $recommend = $this->get_play_game($request['user_id'],8); // 最近在玩游戏
        //     }else{
        //         $recommend = [];
        //     }
        // }

        if($request['user_id'] && $request['model'] != 1){
            $recent_play = $this->get_play_game($request['user_id'],8); // 最近在玩游戏
        }

        $recommend = $this->getrecommendlists($request);

        //热门游戏
        $hot = $this->gethotlists($request,1);
        //最新游戏
        $new = $this->getnewlists($request);
        //文章
        $article = $this->article($request);
        $gonggao = $this->gonggao($request);
        $data['adv'] = $adv;
        $data['recommend'] = $recommend;  // 推荐游戏
        $data['recent_play'] = $recent_play;  // 最近在玩游戏
        $data['hot'] = $hot;  // 热门游戏
        $data['news'] = $new; // 最新游戏
        $data['article'] = $article;
        $data['gonggao'] = $gonggao;
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * [公告]
     * @author chen
     */
    public function gonggao($request=[])
    {
        if(PROMOTE_ID > 0){
            $game_idss = get_promote_relation_game_id(get_promote_game_id(PROMOTE_ID));
            $map['game_id'] = $game_idss;
            $map['game_id'][] = '0';
        }
        $map['category'] = 4;
        $map['post_status'] = 1;
        $postService = new PostService();
        $list = $postService->WapPostList($map);
        foreach ($list as $key=>$v){
            $data[$key]['article_id'] = $v['id'];
            $data[$key]['thumbnail'] = cmf_get_image_url($v['thumbnail']);
            $data[$key]['post_title'] = $v['post_title'];
            $data[$key]['create_time'] = date('m-d H:i',$v['create_time']);
        }
        return $data?:[];
    }

    /**
     * @函数或方法说明
     * @最近在玩游戏
     * @param int $user_id
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/6/16 19:55
     */
    protected function get_play_game($user_id=0,$limit=10)
    {
        $model = new \app\game\model\GameModel();

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['user_id']=$user_id;
        $map['is_del'] =0;
        $map['g.sdk_version'] = 3;
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            if(empty($game_ids)){
                return [];
            }
            $map['game_id'] = ['in',$game_ids];
        }
        $game = $model->get_play_game($map,$limit);
        foreach ($game as $key=>$v){
            $game[$key]['icon'] = cmf_get_image_url($v['icon']);
        }
       return $game?:[];
    }

    /**
     * @函数或方法说明
     * @首页活动公告
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/17 17:06
     */
    protected function article($request=[])
    {
        if(PROMOTE_ID > 0){
            $game_idss = get_promote_relation_game_id(get_promote_game_id(PROMOTE_ID));
            $map['game_id'] = $game_idss;
            $map['game_id'][] = '0';
        }
        $postService = new PostService();
        $map['category'] = [3,4];
        $map['post_status'] = 1;
        $lists = $postService->ArticleList($map, false, 4)->toArray();
        $data_list = [];
        foreach ($lists['data'] as $key=>$v){
            $data_list[$key]['article_id'] = $v['id'];
            $data_list[$key]['thumbnail'] = cmf_get_image_url($v['thumbnail']);
            $data_list[$key]['post_title'] = $v['post_title'];
            $data_list[$key]['create_time'] = date('m-d H:i',$v['create_time']);
        }
        return $data_list;
    }

    /**
     * @函数或方法说明
     * @获取所有推荐游戏
     * @author: 郭家屯
     * @since: 2020/2/17 15:40
     */
    public function getrecommendlists($request=[])
    {
        $model = new GameLogic();
        $data = $model->getRecommendGameLists($request);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取热门游戏
     * @author: 郭家屯
     * @since: 2020/2/17 16:34
     */
    public function gethotlists($request=[],$type=0)
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $limit = $type ? 7 : 8;
        $data = $model->getHotGameLists($request,$limit,$type,PERMI);
        foreach ($data as $key=>$v){
            if($v['sdk_version'] == 3){
                $data[$key]['relation_game_name'] = $v['relation_game_name'].'H5';
            }else{
                $data[$key]['game_name'] = $data[$key]['relation_game_name'];
            }
        }
        if($type == 1){
            return $data;
        }else{

            $this->set_message(200,'获取成功',$data);
        }
    }

    /**
     * @函数或方法说明
     * @获取最新游戏
     * @author: 郭家屯
     * @since: 2020/2/17 16:35
     */
    public function getnewlists($request=[])
    {
        $model = new GameLogic();
        $data = $model->getNewGameLists($request);
        return $data;
    }

    /**
     * @函数或方法说明
     * @游戏详情页
     * @author: 郭家屯
     * @since: 2020/2/17 19:19
     */
    public function getdetail()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getdetail($request);
        $img = [];
        foreach ($data['screenshot'] as $key=>$v){
            $asset = Db::table('sys_asset')->field('more')->where('file_path',$v)->find();
            $arr['type'] = $asset['more']?json_decode($asset['more'],true)['type']:1;
            $arr['url'] = cmf_get_image_url($v);
            $img[] = $arr;
        }
        $data['screenshot'] = $img;
        $data['article_count'] = $this->getdetailarticlecount($request);
        $data['server_count'] = $this->getdetailservercount();
        $data['gift_count'] = $this->getdetailgiftcount();
        $data['is_collect'] = 2;//未收藏
        $data['scheme'] = $data['sdk_scheme'] ? $data['sdk_scheme'] : '';
        if (USER_ID) {
            $collectmodel = new GamecollectModel();
            $collect = $collectmodel->where('user_id', USER_ID)->where('game_id', $request['game_id'])->where('status', 1)->find();
            if ($collect) {
                $data['is_collect'] = 1;
            }
        }
        // vip表显示
        if(!empty($data['vip_table_pic'])){
            $data['vip_table_pic'] = cmf_get_image_url($data['vip_table_pic']);
        }
        // 获取开关
        $showStatus = get_game_show_status($request['game_id']);
        $data['discount_show_status'] = $showStatus['discount_show_status'];
        $data['coupon_show_status'] = $showStatus['coupon_show_status'];

        $this->set_message(200,'获取成功',$data);
    }

    public function getdetailarticlecount($request=[])
    {
        $model = new GameLogic();
        $data = $model->getgamedetailarticle($request);
        return count($data);
    }

    /**
     * @函数或方法说明
     * @详情活动
     * @author: 郭家屯
     * @since: 2020/2/19 17:36
     */
    public function getdetailarticle()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getgamedetailarticle($request);

        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @详情区服
     * @author: 郭家屯
     * @since: 2020/2/19 17:37
     */
    public function getdetailserver()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getgamedetailserver($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @获取数量
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/2 17:14
     */
    public function getdetailservercount()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getgamedetailservercount($request);
        return $data;
    }

    /**
     * @函数或方法说明
     * @详情礼包
     * @author: 郭家屯
     * @since: 2020/2/19 17:38
     */
    public function getdetailgift()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getgamedetailgift($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @详情礼包数量
     * @author: 郭家屯
     * @since: 2020/2/19 17:38
     */
    public function getdetailgiftcount()
    {
        $request = $this->request->param();
        $model = new GameLogic();
        $data = $model->getgamedetailgiftcount($request);
        return $data;
    }

    /**
     * @函数或方法说明
     * @返利折扣
     * @author: 郭家屯
     * @since: 2020/2/18 9:37
     */
    public function get_game_welfare()
    {
        $request = $this->request->param();
        $logic = new WelfareLogic();
        $list = $logic->get_game_welfare($request['user_id'],$request['game_id'],'app');
        $this->set_message(200, "获取成功",$list);
    }

    /**
     * @函数或方法说明
     * @获取代金券
     * @author: 郭家屯
     * @since: 2020/2/6 9:55
     */
    public function get_coupon()
    {
        $request = $this->request->param();
        $relation_game_id = get_game_entity($request['game_id'],'relation_game_id')['relation_game_id'];
        if(empty($request['user_id'])){
            $promote_id = 0;
        }else{
            $user = get_user_entity($request['user_id'],false,'promote_id');
            if($user['promote_id'] == 0){
                $promote_id = 0;
            }else{
                $promote_id = get_top_promote_id($user['promote_id']);
            }
        }
        $paylogic = new PayLogic();
        $data = $paylogic->get_detail_coupon($request['user_id'],$relation_game_id,$promote_id);
        foreach ($data as $key=>$v){
            $data[$key]['start_time'] = $v['start_time'] ? date('y.m.d',$v['start_time']) : '永久';
            $data[$key]['end_time'] = $v['end_time'] ? date('y.m.d',$v['end_time']) : '永久';
        }
        $this->set_message(200, "获取成功",$data);
    }

    /**
     * @函数或方法说明
     * @查询结果查询
     * @author: 郭家屯
     * @since: 2020/2/19 9:49
     */
    public function search()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $data = $logic->getsearch($request,PERMI);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @可领取列表
     * @author: 郭家屯
     * @since: 2020/2/5 16:14
     */
    protected function get_reciver_coupon($user_id=0,$promote_id=0,$game_id=0)
    {
        $paylogic = new PayLogic();
        $coupon = $paylogic->get_coupon_lists($user_id,$promote_id,$game_id);
        return $coupon;
    }
    /**
     * @函数或方法说明
     * @我的优惠券
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/5 16:15
     */
    protected function get_my_coupon($user_id=0,$game_id=0)
    {
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($user_id,3,$game_id);
        return $coupon;
    }

    /**
     * @函数或方法说明
     * @领取优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 11:19
     */
    public function getcoupon()
    {
        $request = $this->request->param('');
        if(empty($request['user_id']))$this->set_message(1002,"信息过期，请重新登录");
        $logic = new GameLogic();
        $result = $logic->getCoupon($request['user_id'],$request['coupon_id']);
        if($result){
            $this->set_message(200,'领取成功',[]);
            $this->success();
        }else{
            $this->set_message(1033,'领取失败',[]);
        }
    }
    public function collect_option()
    {
        $request = $this->request->param('');
        if(empty($request['user_id']))$this->set_message(1002,"信息过期，请重新登录");
        $logic = new GameLogic();
        $res = $logic->collect_option($request['user_id'],$request['game_id']);
        if($res['result']){
            if($res['status']==1){
                $this->set_message(200,'已收藏',$res['status']);
            }else{
                $this->set_message(200,'已取消收藏',$res['status']);
            }
        }else{
            $this->set_message(1033,'收藏失败',[]);
        }
    }

    /**
     * @函数或方法说明
     * @获取平台模块
     * @author: 郭家屯
     * @since: 2020/7/1 9:36
     */
    public function getModel()
    {
        $request = $this->request->param();
        $model = PERMI;
        if($model == 3){
            if($request['sdk_version'] == 1){
                $model = cmf_get_option('app_set')['model_and']?:3;
            }else{
                $model = cmf_get_option('app_set')['model_ios']?:3;
            }
        }
        $data['model'] = $model;//平台模块
        $app_set = cmf_get_option('app_set');
        $data['discount_show'] = $app_set['discount_show'] == '0' ? '0':1;
        $data['server_show'] = $app_set['server_show'] == '0' ? '0':1;
        $data['rank_show'] = $app_set['rank_show']  == '0' ? '0':1;
        $data['category_show'] = $app_set['category_show']  == '0' ? '0':1;
        $data['trade_show'] = cmf_get_option('wap_set')['trade_show']  == '0' ? '0':1;
        $data['discount_entry'] = cmf_get_option('wap_set')['discount_entry']  == '0' ? 0:1;
        $data['coupon_entry'] = cmf_get_option('wap_set')['coupon_entry']  == '0' ? 0:1;
        $data['video_show'] = $app_set['video_show'] == '0' ? '0':1;
        $data['real_register'] = get_user_config_info('age')['real_register_status']?:0;//实名注册开关
        $data['mcard_status'] = cmf_get_option('mcard_set')['status'] ? : 0;
        $data['vip_status'] = cmf_get_option('vip_set')['vip']?1:0;//vip开启状态
        
        $app_set_info = cmf_get_option('app_set');
        // 默认首先显示手游或H5(1:首先显示手游,2:首先显示H5,0:默认不设置)
        if($request['sdk_version'] == 1){
            // 安卓
            $data['default_show_sy_h5'] = intval($app_set_info['android_default_show_sy_h5'] ?? 0);
        }
        if($request['sdk_version'] == 2){
            // iOS
            $data['default_show_sy_h5'] = intval($app_set_info['ios_default_show_sy_h5'] ?? 0);
        }
       
       // APP端是否强制更新(1:开启强制更新,0:默认关闭强制更新)
        if (!empty($request['ios_wxpay_scheme'])) {
            if ($request['ios_wxpay_scheme'] != $app_set_info['scheme']) {
                $app_set_info['scheme'] = $request['ios_wxpay_scheme'];
            }
            cmf_set_option('app_set', $app_set_info);
        }
        // 读取wap站-赚金开关
        $wap_set = cmf_get_option('wap_set');
        $data['zhuanjin'] = (int)$wap_set['zhuanjin'];
        $data['forced_update'] = $app_set_info['forced_update'] ? 1:0;
        $this->set_message(200,'获取成功',$data);
    }

    //折扣返利游戏
    public function discount_game(){
        $request = $this->request->param();
        $data = [];
        //绑币折扣游戏
        $bind_game = $this->get_bind($request);
        foreach ($bind_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        //首冲续充游戏
        $recharge_game = $this->get_recharge($request);
        foreach ($recharge_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        //返利游戏
        $rebate_game = $this->get_rebate($request);
        foreach ($rebate_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        foreach ($data as $key=>$v){
            $data[$key] = array_reduce($v, 'array_merge', array());
        }
        foreach ($data as $key=>$v){
            $data[$key]['ratio'] = $v['ratio'] ? : "0";
            $data[$key]['first_status'] = $v['first_status'] ? $v['first_status']: 0;
            $data[$key]['first_discount'] = $v['first_status'] ? $v['first_discount']: "0";
            $data[$key]['continue_status'] = $v['continue_status'] ? $v['continue_status']: 0;
            $data[$key]['continue_discount'] = $v['continue_discount'] ? $v['continue_discount']: "0";
//            $data[$key]['bind_recharge_discount'] = $v['bind_recharge_discount'] ? : "0";
        }
        array_multisort(array_column($data,'sort'),SORT_DESC,SORT_NUMERIC,$data);
        $this->set_message(200,'获取成功',array_values($data));
    }
    /**
     * @函数或方法说明
     * @获取返利游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_rebate($request=[])
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $logic = new WelfareLogic();
        $user_id = $request['user_id']?:0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        if($request['model'] == 1){
            $map['g.sdk_version'] = $request['sdk_version'];
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['g.id'] = ['in',$game_ids];
        }
        $data = $logic->get_game_rebate_list($promote_id,$map);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取首冲续充游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_recharge($request=[])
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $logic = new WelfareLogic();
        $user_id = $request['user_id']?:0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = $request['promote_id']?:0;
        }
        if($request['model'] == 1){
            $map['g.sdk_version'] = $request['sdk_version'];
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['g.id'] = ['in',$game_ids];
        }
        $data = $logic->get_recharge_list($promote_id,$map,$user_id);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取绑币充值折扣游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_bind($request=[])
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $logic = new WelfareLogic();
        if($request['model'] == 1){
            $map['g.sdk_version'] = $request['sdk_version'];
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['g.id'] = ['in',$game_ids];
        }
        $user_id = $request['user_id']??0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = $request['promote_id']?:0;
        }
        $data = $logic->get_bind_list($map,$promote_id,$user_id);
        return $data;
    }

    /**
     * @函数或方法说明
     * @区服列表
     * @author: 郭家屯
     * @since: 2020/7/1 14:10
     */
    public function server_list()
    {
        $request = $this->request->param();
        $p = $request['page']?:1;
        $row = $request['limit'] ?:10;
        if($request['model'] == 1){
            $map['s.sdk_version'] = $request['sdk_version'];
        }else{
            $map['s.sdk_version'] = 3;
        }
        if (PROMOTE_ID > 0) {
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['game_id'] = ['in', $game_ids];
        }

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $model = new ServerModel();
        if($request['type'] == 1){
            $map['start_time'] = ['between',total(1, 2)];
            $order = "start_time asc";
            $list = $model->getlists($map,$order);
            if($request['type'] == 1){
                foreach($list as $key=>$v){
                    $newkey = date('H:i',$v['start_time']);
                    $v['icon'] = cmf_get_image_url($v['icon']);
                    $v['tag_name'] = $v['tag_name']?explode(',',$v['tag_name']):[];
                    $v['start_time'] = get_time($v['start_time']);
                    $v['game_name'] = $v['relation_game_name'];
                    if($v['sdk_version'] == 3){
                        $v['down_status'] = get_weiduan_down_status($v['relation_game_id'],$v['sdk_version'],PROMOTE_ID) ? 1:0 ;
                    }else{
                        $v['down_status'] = get_down_status2($v['relation_game_id'],$v['sdk_version']) ? 1:0 ;
                    }
                    $new_data[$newkey][] = $v;
                }
                foreach ($new_data as $key=>$v){
                    $arr['time'] = $key;
                    $arr['list'] = $v;
                    $data[] = $arr;
                }
                $data = $data ? :[];
            }
        }elseif($request['type'] == 2){
            $map['start_time'] = ['between',[strtotime(date("Y-m-d", strtotime("-1 week"))), strtotime(date("Y-m-d")) - 1]];
            $order = "start_time desc";
            $data = $model->getserver($map,$p,$row,$order);
            foreach ($data as $key=>$v){
                $data[$key]['icon'] = cmf_get_image_url($v['icon']);
                $data[$key]['start_time'] = get_time($v['start_time']);
                $data[$key]['tag_name'] = $v['tag_name']?explode(',',$v['tag_name']):[];
                $data[$key]['game_name'] = $v['relation_game_name'];
                if($v['sdk_version'] == 3){
                    $data[$key]['down_status'] = get_weiduan_down_status($v['relation_game_id'],$v['sdk_version'],PROMOTE_ID) ? 1:0 ;
                }else{
                    $data[$key]['down_status'] = get_down_status2($v['relation_game_id'],$v['sdk_version']) ? 1:0 ;
                }
            }
        }else{
            $map['start_time'] = ['between',[strtotime(date("Y-m-d", strtotime("+1 day"))), strtotime(date("Y-m-d", strtotime("+1 week"))) + 86399]];
            $order = "start_time asc";
            $data = $model->getserver($map,$p,$row,$order);
            foreach ($data as $key=>$v){
                $data[$key]['server_id'] = $v['id'];
                $data[$key]['icon'] = cmf_get_image_url($v['icon']);
                $data[$key]['start_time'] = get_time($v['start_time']);
                $data[$key]['game_name'] = $v['relation_game_name'];
                $data[$key]['tag_name'] = $v['tag_name']?explode(',',$v['tag_name']):[];
                if($v['sdk_version'] == 3){
                    $data[$key]['down_status'] = get_weiduan_down_status($v['relation_game_id'],$v['sdk_version'],PROMOTE_ID) ? 1:0 ;
                }else{
                    $data[$key]['down_status'] = get_down_status2($v['relation_game_id'],$v['sdk_version']) ? 1:0 ;
                }
                $status = 0;
                if($request['user_id']){
                    $notice = Db::table('tab_game_server_notice')->field('status')->where('user_id',$request['user_id'])->where('server_id',$v['id'])->find();
                    $status = $notice ? $notice['status'] : 0;
                }
                $data[$key]['notice_status'] = $status;
            }
        }
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @获取排行列表
     * @author: 郭家屯
     * @since: 2019/10/16 16:30
     */
    public function rank_list()
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $request = $this->request->param();
        $p = $request['page'];
        $limit = $request['limit'];
        $order = "sort desc,id desc";
        if($request['model'] == 1){
            $map['sdk_version'] = $request['sdk_version'];
        }else{
            $map['sdk_version'] = 3;
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['id'] = ['in',$game_ids];
        }
        switch ($request['type']){
            case 1:
                $map['recommend_status'] = ['like', '%2%'];
                break;
            case 2:
                $map['recommend_status'] = ['like', '%3%'];
                break;
            case 3:
                $map['recommend_status'] = ['like', '%1%'];
                break;
            case 4:
                $order = 'dow_num desc,id desc';
                break;
            default:
                break;
        }
        $model = new GameModel();
        $data = $model->getMoreGame($map,$order,$p,$limit)->each(function($item,$key){
            $item['icon'] = cmf_get_image_url($item['icon']);
            if($item['sdk_version'] == 3){
                $item['down_status'] = get_weiduan_down_status($item['relation_game_id'],$item['sdk_version'],PROMOTE_ID) ? 1:0 ;
            }else{
                $item['down_status'] = get_down_status2($item['relation_game_id'],$item['sdk_version']) ? 1:0 ;
            }
            $item['game_name'] = $item['relation_game_name'];
            return $item;
        });
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @分类列表
     * @author: 郭家屯
     * @since: 2020/8/18 15:18
     */
    public function category_list()
    {
        $data = get_game_type_all('id as category_id,type_name');
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @获取分类数据
     * @author: 郭家屯
     * @since: 2020/8/18 15:32
     */
    public function category_game_list()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $request['test_game_status'] = 0; // 测试游戏不显示,但可以正常进入
        $request['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $data = $logic->getCategoryGameLists($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @视频列表
     * @author: 郭家屯
     * @since: 2020/8/18 17:59
     */
    public function video_list()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $data = $logic->getVideoGameLists($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @获取所有评论
     * @author: 郭家屯
     * @since: 2020/8/19 10:23
     */
    public function get_all_comment()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $data = $logic->getAllCommentLists($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @游戏点赞
     * @author: 郭家屯
     * @since: 2020/8/19 11:37
     */
    public function game_follow()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $msg = $logic->GameFollow($request);
        $this->set_message(200,$msg);
    }

    /**
     * @函数或方法说明
     * @评论点赞
     * @author: 郭家屯
     * @since: 2020/8/19 11:37
     */
    public function comment_follow()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $msg = $logic->CommentFollow($request);
        $this->set_message(200,$msg);
    }

    /**
     * @函数或方法说明
     * @评论功能
     * @author: 郭家屯
     * @since: 2020/8/19 13:47
     */
    public function comment()
    {
        $request = $this->request->param();
        $logic = new GameLogic();
        $msg = $logic->Comment($request);
        $this->set_message(200,$msg);
    }

    /**
     * @函数或方法说明
     * @点赞列表
     * @author: 郭家屯
     * @since: 2020/8/19 15:25
     */
    public function follow_list()
    {
        $request = $this->request->param();
        if(empty($request['user_id'])){
            $this->set_message(1008,'用户不存在');
        }
        $logic = new GameLogic();
        $data = $logic->getFollowLists($request);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @设置消息提醒
     * @author: 郭家屯
     * @since: 2020/9/15 10:14
     */
    public function set_notice()
    {
        $request = $this->request->param();
        if(empty($request['user_id']) || empty($request['server_id'])){
            $this->set_message(1093,'参数不完整');
        }
        $model = new ServerNoticeModel();
        $result = $model->set_notice($request['user_id'],$request['server_id']);
        if($result){
            $this->set_message(200,'设置成功');
        }else{
            $this->set_message(1094,'设置失败');
        }
    }

    /**
     * @函数或方法说明
     * @获取当前版本的游戏id
     * @author: 郭家屯
     * @since: 2020/9/15 14:42
     */
    public function get_version_game_id()
    {
        $request = $this->request->param();
        $game = get_game_entity($request['tip_game_id'],'id,relation_game_id,sdk_version');
        if($game['sdk_version'] == 3){
            $this->set_message(200,'获取成功',$game['id']);
        }elseif($game['sdk_version'] == $request['sdk_version'] && $game['sdk_version'] < 3){
            $this->set_message(200,'获取成功',$game['id']);
        }else{
            $relation_game = Db::table('tab_game')->field('id')->where('relation_game_id',$game['relation_game_id'])
                    ->where('sdk_version',$request['sdk_version'])->find();
            if($relation_game){
                $this->set_message(200,'获取成功',$relation_game['id']);
            }else{
                $this->set_message(200,'获取成功',0);
            }
        }
    }







}