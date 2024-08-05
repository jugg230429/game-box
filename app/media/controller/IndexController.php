<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 16:26
 */

namespace app\media\controller;

use app\common\logic\PointTypeLogic;
use app\site\model\AdvModel;
use app\site\service\PostService;
use think\Db;
use think\facade\Url;

class IndexController extends BaseController
{

    /**
     * [首页]
     * @author 郭家屯[gjt]
     */
    public function index()
    {
        $this->slide('slider_media');//轮播图
        if (AUTH_GAME == 1) {
            $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
            $map2['test_game_status'] = 0; // 测试游戏不显示,但可以正常进入
            $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示
            $map2['only_for_promote'] = 0;  // 渠道独占的游戏不显示

            if (MEDIA_PID > 0) {
                $game_ids = get_promote_game_id(MEDIA_PID);
                $map['id'] = ['in', $game_ids];
                $map1['game_id'] = get_promote_relation_game_id($game_ids);
                $map1['game_id'][] = '0';//资讯条件
                $map2['game_id'] = ['in', $game_ids];
                $map3['and_id|ios_id|h5_id'] = ['in',$game_ids];//礼包条件
            }
            $this->zixun($map1);//公告资讯
            $this->huodong($map1);//活动
            $this->recommendGame($map);//推荐游戏
            $this->hotGame($map);//热门游戏
            $this->server($map2);//区服列表
            $this->gift($map3);//礼包列表
            $this->get_play_game(session('member_auth.user_id'),$map2,5);//最近在玩
            //用户信息
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,balance,vip_level,point,cumulative,head_img');
            $this->assign('user', $user);
            $res = (new PointTypeLogic())->user_vip(session('member_auth.user_id'));
            $this->assign('vip_upgrade',$res['vip_upgrade']);
            $this->assign('next_pay',$res['next_pay']);
        }

        return $this->fetch();
    }


    /**
     * [公告]
     * @author 郭家屯[gjt]
     */
    public function zixun($map = [])
    {
        $postService = new PostService();
        $map['category'] = 4;
        $map['post_status'] = 1;
        $data = $postService->ArticleList($map, false, 7);
        $this->assign('article', $data);
    }

    /**
     * [活动]
     * @author 郭家屯[gjt]
     */
    public function huodong($map = [])
    {
        $postService = new PostService();
        $map['category'] = 3;
        $map['post_status'] = 1;
        $data = $postService->ArticleList($map, false, 9);
        $this->assign('huodong', $data->toArray()['data']);
    }

    /**
     * [推荐游戏]
     * @author 郭家屯[gjt]
     */
    public function recommendGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%1%'];
        if(PERMI != 2){
            $map['sdk_version'] = ['in',[1,2]];
            $data = $model->getGameLists($map, 4)->each(function ($item, $key) {
                $url = url('Game/detail', ['game_id' => $item['id']],true,true);
                $item['qrcode'] = url('qrcode', ['url' => base64_encode(base64_encode($url))]);
                return $item;
            })->toArray();
            $this->assign('recommend_game_sy', $data);
        }
        if(PERMI != 1){
            $map['sdk_version'] = 3;
            $data = $model->getGameLists($map, 4)->each(function ($item, $key) {
                $url = url('Game/detail', ['game_id' => $item['id']],true,true);
                $item['qrcode'] = url('qrcode', ['url' => base64_encode(base64_encode($url))]);
                return $item;
            })->toArray();
            $this->assign('recommend_game_h5', $data);
        }
        if(YPERMI == 1){
            $map['sdk_version'] = 4;
            $data = $model->getGameLists($map, 4);
            $this->assign('recommend_game_pc', $data);
        }

    }

    /**
     * [热门游戏]
     * @author 郭家屯[gjt]
     */
    public function hotGame($map = [])
    {
        $map['recommend_status'] = ['like', '%2%'];
        $model = new \app\game\model\GameModel();
        if(PERMI != 2){
            //手游热门游戏
            $map['sdk_version'] = ['in',[1,2]];
            $sy_data = $model->getGameLists($map, 11)->each(function ($item, $key) {
                $url = url('Game/detail', ['game_id' => $item['id']],true,true);
                $item['qrcode'] = url('qrcode', ['url' => base64_encode(base64_encode($url))]);
                return $item;
            })->toArray();
            $this->assign('sy_hot_game', $sy_data);
        }
        if(PERMI != 1){
            //H5热门游戏
            $map['sdk_version'] = 3;
            $h5_data = $model->getGameLists($map, 11)->each(function ($item, $key) {
                $url = url('Game/detail', ['game_id' => $item['id']],true,true);
                $item['qrcode'] = url('qrcode', ['url' => base64_encode(base64_encode($url))]);
                return $item;
            })->toArray();
            $this->assign('h5_hot_game', $h5_data);
        }
        if(YPERMI == 1){
            //页游热门游戏
            $map['sdk_version'] = 4;
            $h5_data = $model->getGameLists($map, 11)->toArray();
            $this->assign('pc_hot_game', $h5_data);
        }
    }

    /**
     * [首页区服显示]
     * @author 郭家屯[gjt]
     */
    public function server($map = [])
    {
        $model = new \app\game\model\ServerModel();
        if (empty($map['game_id'])) {
            $game_ids = Db::table('tab_game')->field('id')->where('game_status', 1)->where('dow_status', 1)->select()->toArray();
            $game_ids = array_column($game_ids, 'id');
            $map['game_id'] = ['in', $game_ids];
        }
        if(PERMI != 2) {
            $map['g.sdk_version'] = ['in',[1,2]];
            $data = $model -> getServerLists($map, 8);
            $this -> assign('server', $data);
        }
        if(PERMI != 1){
            $map['g.sdk_version'] = 3;
            $data = $model -> getServerLists($map, 8);
            $this -> assign('h5_server', $data);
        }
        if(YPERMI == 1){
            $map['g.sdk_version'] = 4;
            $data = $model -> getServerLists($map, 8);
            $this -> assign('pc_server', $data);
        }
    }

    /**
     * [礼包列表]
     * @author 郭家屯[gjt]
     */
    public function gift($map = [])
    {
        $model = new \app\game\model\GiftbagModel();
        //排除测试状态中的游戏
        $test_game_ids = get_test_game_ids(true);
        $map['and_id'] = ['not in', $test_game_ids];
        $map['ios_id'] = ['not in', $test_game_ids];
        $map['h5_id'] = ['not in', $test_game_ids];
        $map['pc_id'] = ['not in', $test_game_ids];
        $map['gb.remain_num'] = ['gt', 0];
        $data = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 16,"gb.create_time desc,gb.id desc");
        $this->assign('gift', $data);
    }


    /**
     * [首页ajax获取游戏]
     * @author 郭家屯[gjt]
     */
    public function search()
    {
        if (AUTH_GAME == 1) {
            $game_name = $this->request->param('game_name');
            $map['game_name'] = ['like', '%' . $game_name . '%'];
            if (MEDIA_PID > 0) {
                $map['id'] = ['in', get_promote_game_id(MEDIA_PID)];
            }
            $map['game_status'] = 1;
            $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
            $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

            $model = new \app\game\model\GameModel();
            $data = $model->getGameLists($map, 50)->each(function ($item, $key) {
                if($item['sdk_version'] == 4){
                    $item['url'] = url('game/ydetail', ['game_id' => $item['id']]);
                }elseif($item['sdk_version'] == 3){
                    $item['url'] = url('Game/hdetail', ['game_id' => $item['id']]);
                }else{
                    $item['url'] = url('Game/detail', ['game_id' => $item['id']]);
                }
                return $item;
            })->toArray();
            if($data){
                return json(['code' => 1, 'data' => $data]);
            }else{
                return json(['code' => 0, 'data' => $data]);
            }

        }

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
     * @最近在玩游戏
     *
     * @author: zsl
     * @since: 2021/4/22 16:03
     */
    protected function get_play_game($user_id = 0, $map = [], $limit = 10)
    {
        $model = new \app\game\model\GameModel();
        $map['user_id'] = $user_id;
        $map['is_del'] = 0;
        $game = $model -> get_play_game($map, $limit);
        $this -> assign('play_game', $game);
    }

}
