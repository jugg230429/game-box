<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\game\model\CommentfollowModel;
use app\game\model\GamecollectModel;
use app\game\model\GamecommentModel;
use app\game\model\GamefollowModel;
use app\game\model\GameModel;
use app\game\model\GiftbagModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteapplyModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\site\model\PortalPostModel;
use app\site\service\PostService;
use think\Db;
use think\SensitiveThesaurus;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class GameLogic{

    /**
     * @函数或方法说明
     * @领取优惠券
     * @param int $user_id
     * @param int $coupon_id
     *
     * @author: 郭家屯
     * @since: 2020/2/5 11:30
     */
    public function getCoupon($user_id=0,$coupon_id=0)
    {
        $model = new CouponModel();
        $coupon = $model->where('id',$coupon_id)->where('is_delete',0)->find();
        if(!$coupon)return false;
        $coupon = $coupon->toArray();
        $checkres = $this->checkCouponAuth($user_id,$coupon);//检测用户是否可领取
        if(!$checkres){
            return false;
        }
        $recordmodel = new CouponRecordModel();
        $count = $recordmodel->where('user_id',$user_id)->where('coupon_id',$coupon_id)->count();
        if($count>=$coupon['limit'])return false;
        $add['user_id'] = $user_id;
        $add['user_account'] = get_user_entity($user_id,false,'account')['account'];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = $coupon['coupon_name'];
        $add['game_id'] = $coupon['game_id'];
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['start_time'] = $coupon['start_time'];
        $add['end_time'] = $coupon['end_time'];
        $add['limit'] = $coupon['limit'];
        Db::startTrans();
        try{
            $recordmodel->insert($add);
            $model->where('id',$coupon_id)->setDec('stock',1);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

    private function checkCouponAuth($user_id,$coupon_data){
        $user = get_user_entity($user_id,false,'promote_id,parent_id');
        if($user['promote_id']>0){
            if($coupon_data['type']==2){
                return false;//官方
            }elseif($coupon_data['type']==4){
                $coupon_promote_data = Db::table('tab_coupon_promote')->where(['coupon_id'=>$coupon_data['id'],'promote_id'=>$user['promote_id']])->find();
                if(empty($coupon_promote_data)){
                    return false;//部分渠道不含用户渠道
                }
            }
            return true;
        }else{
            if($coupon_data['type']==3||$coupon_data['type']==4){
                return false;//渠道
            }
            return true;
        }
    }

    /**
     * @函数或方法说明
     * @查询结果
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 9:58
     */
    public function getsearch($request=[],$permi=3)
    {
        $game = $this->getkeygame($request,$permi);
        foreach ($game as $key=>$v){
            if($v['sdk_version'] == 3){
                $game[$key]['game_name'] = $v['game_name']."H5";
            }
        }
        $game_ids = array_column($game,'game_id');

        if(empty($game_ids)){
            $gift = [];
            $article = [];
        }else{
            $gift = $this->getkeygift($request,$game_ids);
            $article = $this->getkeyarticle($request,get_promote_relation_game_id($game_ids));
        }
        $data['game'] = $game;
        $data['gift'] = $gift;
        $data['article'] = $article;
        return $data;
    }

    /**
     * @函数或方法说明
     * @查询文章
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 10:32
     */
    public function getkeyarticle($request=[],$game_ids=[])
    {
        $logic = new PostService();
        $map['category'] = [3,4];
        $map['game_id'] = $game_ids;
        $map['post_status'] = 1;
        $article = $logic->GamePostList($map, false, 50)->toArray();
        $data = [];
        foreach ($article as $key=>$v){
            $data[$key]['article_id'] = $v['id'];
            $data[$key]['post_title'] = $v['post_title'];
            $data[$key]['game_name'] = get_game_entity($v['game_id'],'relation_game_id')['relation_game_id'];
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取查询礼包
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 10:12
     */
    public function getkeygift($request=[],$game_ids=[])
    {
        $model = new GiftbagModel();
        $map['and_id|ios_id|h5_id'] = ['in',$game_ids];
        $map['remain_num'] = ['gt',0];
        $map['giftbag_version'] = $map['g.sdk_version']  = ['like','%'.$request['sdk_version'].'%'];
        $data = $model->getkeygift($map);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取查询游戏
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 10:03
     */
    public function getkeygame($request=[],$permi=3)
    {
        $model = new GameModel();
        if($request['sdk_version']==1){
            $app_model = cmf_get_option('app_set')['model_and'] ? : 3;
            if($permi == 3 && $app_model == 3){
                $map['sdk_version'] = ['in',[$request['sdk_version'],3]];
            }else{
                if($request['model'] == 1){
                    $map['sdk_version'] =  1;
                }else{
                    $map['sdk_version'] = 3;
                }
            }
        }else{
            $app_model = cmf_get_option('app_set')['model_ios'] ? : 3;
            if($permi == 3 && $app_model == 3){
                $map['sdk_version'] = ['in',[$request['sdk_version'],3]];
            }else{
                if($request['model'] == 1){
                    $map['sdk_version'] =  2;
                }else{
                    $map['sdk_version'] = 3;
                }
            }
        }
        $map['game_name'] =['like','%'.$request['keyword'].'%'];
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['id'] = ['in',$game_ids];
        }
        $data = $model->getSearchGame($map);
        return $data;
    }
    /**
     * @函数或方法说明
     * @获取所有推荐游戏
     * @author: 郭家屯
     * @since: 2020/2/17 15:40
     */
    public function getRecommendGameLists($request=[])
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        if($request['model'] == 1){
            // 手游
            $map['sdk_version'] = $request['sdk_version'];
        }else{
            // H5
            $map['sdk_version'] = 3;
        }
        $map['recommend_status'] = ['like','%1%'];
        $limit = $request['limit'] ? : 50;
        $data = $this->getGameList($map,$limit);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取热门游戏
     * @author: 郭家屯
     * @since: 2020/2/17 15:40
     */
    public function getHotGameLists($request=[],$limit =0,$type=0,$permi=3)
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        if($type == 1){
            if($request['model'] == 1){
                $map['sdk_version'] = $request['sdk_version'];
            }else{
                $map['sdk_version'] = 3;
            }
        }else{
            if($request['sdk_version']==1){
                $model = cmf_get_option('app_set')['model_and'] ? : 3;
                if($permi == 3 && $model == 3){
                    $map['sdk_version'] = ['in',[$request['sdk_version'],3]];
                }else{
                    if($request['model'] == 1){
                        $map['sdk_version'] =  1;
                    }else{
                        $map['sdk_version'] = 3;
                    }
                }
            }else{
                $model = cmf_get_option('app_set')['model_ios'] ? : 3;
                if($permi == 3 && $model == 3){
                    $map['sdk_version'] = ['in',[$request['sdk_version'],3]];
                }else{
                    if($request['model'] == 1){
                        $map['sdk_version'] =  2;
                    }else{
                        $map['sdk_version'] = 3;
                    }
                }
            }
        }
        $map['recommend_status'] = ['like','%2%'];
        $data = $this->getGameList($map,$limit);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取最新游戏
     * @author: 郭家屯
     * @since: 2020/2/17 15:40
     */
    public function getNewGameLists($request=[])
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        if($request['model'] == 1){
            $map['sdk_version'] = $request['sdk_version'];
        }else{
            $map['sdk_version'] = 3;
        }
        $map['recommend_status'] = ['like','%3%'];
        $data = $this->getGameList($map,8);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取分类游戏
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/8/18 15:46
     */
    public function getCategoryGameLists($request=[])
    {
        $test_game_status = $request['test_game_status'] ?? -1; // 测试游戏不显示,但可以正常进入
        if( $test_game_status == 0){
            $map['test_game_status'] = 0;
        }
        if($request['model'] == 1){
            $map['sdk_version'] = $request['sdk_version'];
        }else{
            $map['sdk_version'] = 3;
        }
        $where_str = '';//游戏类型查询更改
        if ($request['category_id'] > 0) {
            $where_str = "FIND_IN_SET('".$request['category_id']."',game_type_id)";
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['id'] = ['in',$game_ids];
        }
        $model = new GameModel();
        $data = $model->getMoreGame($map,'sort desc,id desc',$request['page']?:1,$request['limit']?:10,null,$where_str);
        $data_list = [];
        foreach ($data as $key=>$v){
            $game['game_id'] = $v['id'];
            $game['game_name'] = $v['relation_game_name'];
            $game['relation_game_name'] = $v['relation_game_name'];
            $game['icon'] = cmf_get_image_url($v['icon']);
            $game['game_type_name'] = $v['game_type_name'];
            $game['tag_name'] = $v['tag_name']? explode(',',$v['tag_name']) : [];
            $game['features'] = $v['features'];
            $game['down_port'] = $v['down_port'];
            $game['sdk_version'] = $v['sdk_version'];
            $game['game_size'] = $v['game_size'];
            $game['dow_num'] = $v['dow_num'];
            $data_list[] = $game;
        }
        return $data_list;
    }

    /**
     * @函数或方法说明
     * @获取视频列表
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/8/19 9:13
     */
    public function getVideoGameLists($request=[])
    {
        $map['sdk_version'] = ['in',[$request['sdk_version'],3]];
        $map['video_url|video'] = ['neq',''];
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['id'] = ['in',$game_ids];
        }
        $model = new GameModel();
        $data = $model->getMoreGame($map,'sort desc,id desc',$request['page']?:1,$request['limit']?:10);
        $data_list = [];
        foreach ($data as $key=>$v){
            $game['game_id'] = $v['id'];
            $game['game_name'] = $v['relation_game_name'];
            $game['relation_game_name'] = $v['relation_game_name'];
            $game['icon'] = cmf_get_image_url($v['icon']);
            $game['video_cover'] = cmf_get_image_url($v['video_cover']);
            $game['game_type_name'] = $v['game_type_name'];
            $game['tag_name'] = $v['tag_name']? explode(',',$v['tag_name']) : [];
            $game['features'] = $v['features'];
            $game['down_port'] = $v['down_port'];
            $game['sdk_version'] = $v['sdk_version'];
            $game['game_size'] = $v['game_size'];
            $game['dow_num'] = $v['dow_num'];

            //此处需要判断video是否为空以及是否是多视频-20210624-byh-start
            if(!empty($v['video'])){
                $_video = json_decode($v['video'], true);
                if(!empty($_video)){//数组
                    $_num = array_rand($_video,1);
                    $v['video'] = $_video[$_num];//取出一个
//                    shuffle($_video);//数组随机打乱
//                    $v['video'] = $_video[0];//取出一个
                }
            }
            //此处需要判断video是否为空以及是否是多视频-20210624-byh-end

            $game['video_url'] = $v['video_url']?:cmf_get_domain() . '/upload/' .$v['video'];
            if($v['sdk_version'] == 3){
                $game['down_status'] = get_weiduan_down_status($v['relation_game_id'],$map['sdk_version'],PROMOTE_ID) ? 1:0 ;
            }else{
                $game['down_status'] = get_down_status2($v['relation_game_id'],$map['sdk_version']) ? 1:0 ;
            }
            //点赞状态
            if($request['user_id']){
                $follow = Db::table('tab_game_follow')->where('game_id',$v['id'])->where('user_id',$request['user_id'])->where('status',1)->find();
                if($follow){
                    $game['follow_status'] = 1;
                }else{
                    $game['follow_status'] = 0;
                }
            }else{
                $game['follow_status'] = 0;
            }
            $game['follow_num'] = Db::table('tab_game_follow')->where('game_id',$v['id'])->where('status',1)->count()?:0;
            $game['comment'] = $this->getCommentLists($v['id']);
            //评论数
            $game['comment_num'] = Db::table('tab_game_comment')->where('game_id',$v['id'])->where('status',1)->where('top_id',0)->count()?:0;
            $server = $this->get_new_server($v['id']);
            $game = array_merge($game,$server);
            $data_list[] = $game;
        }
        return $data_list;
    }

    /**
     * @函数或方法说明
     * @获取游戏列表
     * @param array $map
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/2/17 16:34
     */
    protected function getGameList($map=[],$limit=50){
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['id'] = ['in',$game_ids];
        }
        $model = new GameModel();
        $data = $model->getRecommendGameLists($map,$limit);
        $data_list = [];
        foreach ($data as $key=>$v){
            $game['game_id'] = $v['id'];
            $game['game_name'] = $v['relation_game_name'];
            $game['relation_game_name'] = $v['relation_game_name'];
            $game['icon'] = cmf_get_image_url($v['icon']);
            $game['game_type_name'] = $v['game_type_name'];
            $game['tag_name'] = $v['tag_name']? explode(',',$v['tag_name']) : [];
            $game['features'] = $v['features'];
            $game['down_port'] = $v['down_port'];
            $game['sdk_version'] = $v['sdk_version'];
            if($v['sdk_version'] == 3){
                $game['down_status'] = get_weiduan_down_status($v['relation_game_id'],$map['sdk_version'],PROMOTE_ID) ? 1:0 ;
            }else{
                $game['down_status'] = get_down_status2($v['relation_game_id'],$map['sdk_version']) ? 1:0 ;
            }
            $server = $this->get_new_server($v['id']);
            $game = array_merge($game,$server);
            $data_list[] = $game;
        }
        return $data_list;
    }

    /**
     * @函数或方法说明
     * @获取信心区服信息
     * @param int $game_id
     *
     * @author: 郭家屯
     * @since: 2020/2/17 15:56
     */
    public function get_new_server($game_id=0)
    {
        $model = new ServerModel();
        $server = $model->field('server_name,start_time')
                ->where('game_id',$game_id)
                ->where('start_time','>',time())
                ->order('start_time asc')
                ->find();
        if(!$server){
            $server = $model->field('server_name,start_time')
                    ->where('game_id',$game_id)
                    ->where('start_time','<=',time())
                    ->order('start_time desc')
                    ->find();
        }
        if(!$server){
            $data['server_name'] = '';
            $data['server_time'] = '';
        }else{
            $server = $server->toArray();
            $data['server_name'] = $server['server_name'];
            if(date('m-d',$server['start_time']) == date('m-d')){
                $data['server_time'] = " 今天".date('H:i',$server['start_time']);
            }else{
                $data['server_time'] = date('m/d H:i',$server['start_time']);
            }
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取信心区服信息
     * @param int $game_id
     *
     * @author: 郭家屯
     * @since: 2020/2/17 15:56
     */
    public function get_game_server($game_id=0)
    {
        $model = new ServerModel();
        $server = $model->field('server_name,start_time')
                ->where('game_id',$game_id)
                ->order('start_time desc')
                ->find();
        return $server?$server->toArray():[];
    }

    /**
     * @函数或方法说明
     * @获取详情页面数据
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/17 19:23
     */
    public function getdetail($request=[])
    {
        //获取游戏详情
        $game = $this->getGameDetail($request);
        return $game;
    }

    /**
     * @函数或方法说明
     * @游戏详情礼包
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 17:21
     */
    public function getgamedetailgift($request=[])
    {
        //获取礼包
        $gift = $this->getGameGift($request);
        return $gift;
    }

    /**
     * @函数或方法说明
     * @游戏详情礼包数量
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 17:21
     */
    public function getgamedetailgiftcount($request=[])
    {
        $model = new GiftbagModel();
        $gift = $model->getGameGiftcount($request['game_id'],USER_ID,$request['sdk_version']);
        return $gift;
    }

    /**
     * @函数或方法说明
     * @获取游戏详情页区服
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/19 16:53
     */
    public function getgamedetailserver($request=[])
    {
        //获取区服
        $server = $this->getGameServer($request);
        return $server;
    }

    /**
     * @函数或方法说明
     * @获取区服数量
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/3/2 17:15
     */
    public function getgamedetailservercount($request=[])
    {
        $model = new ServerModel();
        $map['game_id'] = $request['game_id'];
        $server = $model->getservercount($map);
        return $server;
    }

    /**
     * @函数或方法说明
     * @获取游戏详情页文章
     * @param array $request
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/2/19 16:48
     * @throws \think\exception\DbException
     */
    public function getgamedetailarticle($request=[])
    {
        //获取活动资讯
        $article = $this->getGameArticle($request);
        return $article;
    }

    /**
     * @函数或方法说明
     * @获取游戏礼包
     * @param array $request
     *
     * @return array|false|\PDOStatement|string|\think\Collection
     *
     * @author: 郭家屯
     * @since: 2020/2/17 20:27
     */
    protected function getGameGift($request=[])
    {
        $model = new GiftbagModel();
        $page = $request['page']?:1;
        $row = $request['limit']?:10;
        $gift = $model->getGameGiftPage($request['game_id'],USER_ID,$page,$row,"gb.sort desc,gb.id desc",$request['sdk_version']);
        return $gift;
    }

    /**
     * @函数或方法说明
     * @获取区服
     * @ param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/17 20:02
     */
    protected function getGameServer($request=[])
    {
        $model = new ServerModel();
        $map['game_id'] = $request['game_id'];
        $page = $request['page']?:1;
        $row = $request['limit']?:10;
        $server = $model->getserver($map,$page,$row);
        $data = [];
        foreach ($server as $key=>$v){
            $data[$key]['server_name'] = $v['server_name'];
            $data[$key]['start_time'] = $v['start_time'];
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取游戏文章
     * @param array $request
     * @param string $game_name
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/2/17 19:57
     * @throws \think\exception\DbException
     */
    protected function getGameArticle($request=[])
    {
        $postService = new PostService();
        $map['game_id'] = get_promote_relation_game_id(get_game_entity($request['game_id'],'relation_game_id')['relation_game_id']);
        $map['post_status'] = 1;
        $data = [];
        $map['category'] = [2,3,4,5];
        $limit = $request['limit']?:10;
        $article = $postService->ArticleList($map, false, $limit)->toArray();
        foreach ($article['data'] as $key=>$v){
            $data[$key]['category_id'] = $v['id'];
            $data[$key]['category_name'] = $this->get_category_name($v['category_id']);
            $data[$key]['post_title'] = $v['post_title'];
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取游戏详情
     * @param array $request
     *
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/17 19:35
     */
    public function getGameDetail($request=[])
    {
        $model = new GameModel();
        $map['id'] = $request['game_id'];
        $game = $model->getGameDetail($map);
        $data['game_id'] = $game['id'];
        $data['game_name'] = $game['game_name'];
        $data['features'] = $game['features'];
        $data['introduction'] = $game['introduction'];
        $data['icon'] = cmf_get_image_url($game['icon']);
        $data['game_type_name'] = $game['game_type_name'];
        $data['tag_name'] = $game['tag_name']? explode(',',$game['tag_name']) : [];
        $data['dow_num'] = $game['dow_num'];
        $data['game_size'] = $game['down_port'] == 1 ? $game['game_size'] : $game['game_address_size']."MB";
        $data['game_score'] = $game['game_score'];
        $data['screenshot'] = $game['screenshot'] ? explode(',',$game['screenshot']) : [];
        $data['sdk_version'] = $game['sdk_version'];
        $data['screen_type'] = $game['screen_type'];
        $data['fullscreen'] = $game['fullscreen'];
        $data['down_port'] = $game['down_port'];
        $data['vip_table_pic'] = $game['vip_table_pic'];
        if($data['sdk_version'] == 3){
            $data['down_status'] = get_weiduan_down_status($game['relation_game_id'],$request['sdk_version'],PROMOTE_ID) ? 1:0 ;
        }else{
            $data['down_status'] = get_down_status2($game['relation_game_id'],$request['sdk_version']) ? 1:0 ;
        }
        $data['sdk_scheme'] = $game['sdk_scheme'];
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取分类名称
     * @param $category_id
     *
     * @author: 郭家屯
     * @since: 2020/2/17 19:54
     */
    protected function get_category_name($category_id=0)
    {
        switch ($category_id){
            case 2:
                $category_name = "资讯";
                break;
            case 3:
                $category_name = "活动";
                break;
            case 4:
                $category_name = "公告";
                break;
            case 5:
                $category_name = "攻略";
                break;
            default :
                $category_name = "未知";
        }
        return $category_name;
    }
    public function collect_option($user_id,$game_id)
    {
        $collectmodel = new GamecollectModel();
        $collect = $collectmodel->field('id,status')->where('user_id', $user_id)->where('game_id', $game_id)->find();
        if ($collect) {
            $save['status'] = $collect['status'] == 1 ? 2 : 1;
            $save['create_time'] = time();
            $result = $collectmodel->where('id', $collect['id'])->update($save);
        } else {
            $save['status'] = 1;
            $save['user_id'] = $user_id;
            $save['game_id'] = $game_id;
            $save['create_time'] = time();
            $result = $collectmodel->insert($save);
        }
        return ['result'=>$result,'status'=>$save['status']];
    }


    /**
     * @函数或方法说明
     * @获取轮播评论（一级评论）
     * @param array $request
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/8/19 10:16
     */
    protected function getCommentLists($game_id=0)
    {
        $model = new GamecommentModel();
        $map['game_id'] = $game_id;
        $map['status'] = 1;
        $map['top_id'] = 0;
        $data = $model->getLimit($map,100);
        if($data){
            $data = array_column($data,'content');
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取所有评论
     * @param array $request
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/8/19 10:16
     */
    public function getAllCommentLists($request=[])
    {
        $model = new GamecommentModel();
        $map['game_id'] = $request['game_id'];
        $map['status'] = 1;
        $map['top_id'] = 0;
        $data = $model->getCommetList($map,$request['user_id'],$request['page'],$request['limit']);
        return $data;
    }

    /**
     * @函数或方法说明
     * @游戏点赞
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/8/19 11:41
     */
    public function GameFollow($request=[])
    {
        if(!$request['user_id'] || !$request['game_id']){
            return '请先登录';
        }
        $model = new GamefollowModel();
        $result = $model->follow($request['user_id'],$request['game_id']);
        return $result;
    }

    /**
     * @函数或方法说明
     * @评论点赞
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/8/19 11:41
     */
    public function CommentFollow($request=[])
    {
        if(!$request['user_id'] || !$request['comment_id']){
            return '请先登录';
        }
        $model = new CommentfollowModel();
        $result = $model->follow($request['user_id'],$request['comment_id']);
        return $result;
    }

    /**
     * @函数或方法说明
     * @评论功能
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/8/19 13:48
     */
    public function Comment($request=[])
    {
        if(!$request['user_id']){
            return '请先登录';
        }
        if(strlen($request['content']) < 10){
            return '评论字数不可少于10个';
        }
        //内容过滤
        $sensitive = new SensitiveThesaurus();
        $request['content'] = $sensitive->sensitive($request['content']);
        $model = new GamecommentModel();
        $result = $model->game_comment($request['game_id'],$request['user_id'],$request['content'],$request['comment_id'],$request['comment_account']);
        if($result){
            return '评论成功';
        }else{
            return '评论失败';
        }
    }

    /**
     * @函数或方法说明
     * @点赞列表
     * @param $request
     *
     * @author: 郭家屯
     * @since: 2020/8/19 15:30
     */
    public function getFollowLists($request=[])
    {
        $map['g.sdk_version'] = ['in',[$request['sdk_version'],3]];
        $map['g.video_url|g.video'] = ['neq',''];
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['g.id'] = ['in',$game_ids];
        }
        $map['user_id'] = $request['user_id'];
        $model = new GamefollowModel();
        $data = $model->follow_list($map,$request['page']?:1,$request['limit']?:10);
        $data_list = [];
        foreach ($data as $key=>$v){
            $game['game_id'] = $v['id'];
            $game['game_name'] = $v['relation_game_name'];
            $game['relation_game_name'] = $v['relation_game_name'];
            $game['icon'] = cmf_get_image_url($v['icon']);
            $game['video_cover'] = cmf_get_image_url($v['video_cover']);
            $game['game_type_name'] = $v['game_type_name'];
            $game['tag_name'] = $v['tag_name']? explode(',',$v['tag_name']) : [];
            $game['features'] = $v['features'];
            $game['down_port'] = $v['down_port'];
            $game['sdk_version'] = $v['sdk_version'];
            $game['game_size'] = $v['game_size'];
            $game['dow_num'] = $v['dow_num'];

            //此处需要判断video是否为空以及是否是多视频-20210624-byh-start
            if(!empty($v['video'])){
                $_video = json_decode($v['video'], true);
                if(!empty($_video)){//数组
                    shuffle($_video);//数组随机打乱
                    $v['video'] = $_video[0];//取出一个
                }
            }
            //此处需要判断video是否为空以及是否是多视频-20210624-byh-end

            $game['video_url'] = $v['video_url']?:cmf_get_domain() . '/upload/' .$v['video'];
            if($v['sdk_version'] == 3){
                $game['down_status'] = get_weiduan_down_status($v['relation_game_id'],$map['sdk_version'],PROMOTE_ID) ? 1:0 ;
            }else{
                $game['down_status'] = get_down_status2($v['relation_game_id'],$map['sdk_version']) ? 1:0 ;
            }
            //点赞状态
            if($request['user_id']){
                $follow = Db::table('tab_game_follow')->where('game_id',$v['id'])->where('user_id',$request['user_id'])->where('status',1)->find();
                if($follow){
                    $game['follow_status'] = 1;
                }else{
                    $game['follow_status'] = 0;
                }
            }else{
                $game['follow_status'] = 0;
            }
            $game['follow_num'] = Db::table('tab_game_follow')->where('game_id',$v['id'])->where('status',1)->count()?:0;
            //评论数
            $game['comment'] = $this->getCommentLists($v['id']);
            $game['comment_num'] = Db::table('tab_game_comment')->where('game_id',$v['id'])->where('status',1)->count();
            $server = $this->get_new_server($v['id']);
            $game = array_merge($game,$server);
            $data_list[] = $game;
        }
        return $data_list;
    }








}
