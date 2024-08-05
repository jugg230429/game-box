<?php
/**
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-25 10:41
 */

namespace app\mobile\controller;

use app\site\model\AdvModel;
use app\site\service\PostService;
use think\Db;

class IndexController extends BaseController
{
    /**
     *[首页]
     * @return mixed
     * @author chen
     */
    public function index()
    {
        $this->slide('wap_index');//轮播图
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map1['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map2['test_game_status'] = 0; // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map1['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map2['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        if (AUTH_GAME == 1) {
            if (MOBILE_PID > 0) {
                $game_ids = get_promote_game_id(MOBILE_PID);
                $map['id'] = ['in', $game_ids];
                $map1['game_id'] = get_promote_relation_game_id($game_ids);
                $map1['game_id'][] = '0';
                $map2['game_id'] = ['in', $game_ids];
            }
            $this->adv();//广告图
            $this->huodong($map1);//活动
            $this->gonggao($map1);//公告
            // MODEL:1 手游, MODEL:2 H5, 
            if(MODEL == 1) {
                $this -> recommendGame($map);//推荐游戏
            }else{
                if(session('member_auth.user_id')){
                    $this->get_play_game(session('member_auth.user_id'),$map2,8);
                }
            }
            // 新增 H5 加上精品游戏推荐
            if (MODEL == 2) {
                $this -> recommendGame($map);//推荐游戏
            }

            $this->notRecommendGame($map);//不推荐游戏
            $this->hotGame($map);//热门游戏
            $this->newGame($map);//最新游戏
        }
        return $this->fetch();

    }

    /**
     * [广告图]
     * @author chen
     */
    public function adv()
    {
        $sdk_version = get_devices_type();
        $model = new AdvModel();

        $hot_game_adv = $model->getAdv('wap_hot_game' . ($sdk_version == 1 ? '' : '_ios'));
        $new_game_adv = $model->getAdv('wap_new_game' . ($sdk_version == 1 ? '' : '_ios'));
        //确认模块
        if(PERMI == 3){
            $model = cmf_get_option('wap_set')['model_wap']?:3;
            if($model == 1){
                if(!in_array($hot_game_adv['sdk_version'],[1,2]) && $hot_game_adv['type'] == 1){
                    $hot_game_adv = [];
                }
                if(!in_array($new_game_adv['sdk_version'],[1,2]) && $new_game_adv['type'] == 1){
                    $new_game_adv = [];
                }
            }elseif($model == 2){
                if($hot_game_adv['sdk_version']!=3 && $hot_game_adv['type'] == 1){
                    $hot_game_adv = [];
                }
                if($new_game_adv['sdk_version']!=3 && $new_game_adv['type'] == 1){
                    $new_game_adv = [];
                }
            }
        }
        
        $this->assign("hot_game_adv", $hot_game_adv);
        $this->assign("new_game_adv", $new_game_adv);
    }


    /**
 * [活动公告]
 * @author chen
 */
    public function huodong($map = [])
    {
        $postService = new PostService();
        $map['category'] = [3, 4];
        $map['post_status'] = 1;
        $data = $postService->ArticleList($map, false, 4);
        $this->assign('huodong', $data);
    }

    /**
     * [公告]
     * @author chen
     */
    public function gonggao($map = [])
    {
        $postService = new PostService();
        $map['category'] = 4;
        $map['post_status'] = 1;
        $data = $postService->WapPostList($map);
        $this->assign('gonggao', $data);
    }

    /**
     * [推荐游戏]
     * @author chen
     */
    public function recommendGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%1%'];
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3; // H5
        }
        // 新增 H5 推荐游戏
        if(MODEL == 2){
            $map['sdk_version'] = 3; // H5
        }

        $data = $model->getRecommendGameLists($map, null)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['tag_name'] = explode(',', $item['tag_name']);
            return $item;
        });
        $this->assign('recommend_game', $data);
    }

    /**
     * [不推荐游戏]
     * @author chen
     */
    public function notRecommendGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%0%'];
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        $data = $model->getRecommendGameLists($map, 99)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['tag_name'] = explode(',', $item['tag_name']);
            return $item;
        });
        $this->assign('not_recommend_game', $data);
    }

    /**
     * [热门游戏]
     * @author chen
     */
    public function hotGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%2%'];
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        $data = $model->getRecommendGameLists($map, null)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['tag_name'] = explode(',', $item['tag_name']);
            return $item;
        });
        $this->assign('hot_game', $data);
    }

    /**
     * [最新游戏]
     * @author chen
     */
    public function newGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%3%'];
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        $data = $model->getRecommendGameLists($map, null)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['tag_name'] = explode(',', $item['tag_name']);
            return $item;
        });
        $this->assign('new_game', $data);
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
    protected function get_play_game($user_id=0,$map=[],$limit=10)
    {
        $model = new \app\game\model\GameModel();
        $map['user_id']=$user_id;
        $map['is_del'] =0;
        $map['g.sdk_version'] = 3;
        $game = $model->get_play_game($map,$limit);
        $this->assign('play_game',$game);
    }
}
