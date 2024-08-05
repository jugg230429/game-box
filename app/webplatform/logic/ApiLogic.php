<?php

namespace app\webplatform\logic;

use app\game\model\GameModel;
use app\game\model\GamesourceModel;
use app\game\model\GiftbagModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteapplyModel;
use app\webplatform\model\SpendModel;

class ApiLogic
{
    /**
     * 查询可导入游戏数量
     *
     * @param int $promote_id
     * @param array $game_ids
     * @return int
     * @author: Juncl
     * @time: 2021/08/20 11:34
     */
     public function selectGames($promote_id=0, $game_ids=[])
     {
         $AppleModel = new PromoteapplyModel();
         // 排除已导入的游戏和第三方游戏
         $GameModel = new GameModel();
         $third_games = $GameModel->where('platform_id','gt',0)->column('id');
         if(!empty($game_ids)){
             $third_games = array_merge($third_games,$game_ids);
         }
         if(!empty($third_games)){
             $_map['game_id'] = array('notin',$third_games);
         }
         // 获取可导入的H5和页游游戏
         $_map['promote_id'] = $promote_id;
         $_map['status'] = 1;
         $_map['sdk_version'] = array('in',[3,4]);
         $game_lists_h5 = $AppleModel->where($_map)->column('game_id');
         // 获取可导入的手游
         $_map['sdk_version'] = array('in',[1,2]);
         $_map['enable_status'] = 1;
         // 原包打包和超级签打包
         $_map['pack_type'] = array('in',[1,4]);
         $game_lists_sy = $AppleModel->where($_map)->column('game_id');
         $game_lists = array_merge($game_lists_h5,$game_lists_sy);
         return count($game_lists);
     }

    /**
     * 查询可游戏并返回
     *
     * @param int $promote_id
     * @param array $game_ids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/21 9:14
     */
     public function importGame($promote_id=0, $game_ids=[])
     {
         $AppleModel = new PromoteapplyModel();
         $GameModel = new GameModel();
         // 排除已导入的游戏
         if(!empty($game_ids)){
             $_map['game_id'] = array('notin',$game_ids);
         }
         // 获取可导入的H5和页游游戏
         $_map['promote_id'] = $promote_id;
         $_map['status'] = 1;
         $_map['sdk_version'] = array('in',[3,4]);
         $game_lists_h5 = $AppleModel->where($_map)->column('game_id');
         // 获取可导入的手游
         $_map['sdk_version'] = array('in',[1,2]);
         $_map['enable_status'] = 1;
         $_map['pack_type'] = array('in',[1,4]);
         $game_lists_sy = $AppleModel->where($_map)->column('game_id');
         $game_lists = array_merge($game_lists_h5,$game_lists_sy);
         $map['g.id'] = array('in',$game_lists);
         $map['g.platform_id'] = 0;
         $gameData = $GameModel->alias('g')
                   ->field('g.id as game_id,g.game_name,g.sort,g.short,g.game_score,g.tag_name,g.features,g.introduction,g.recommend_status,g.icon,g.cover,g.material_url,g.hot_cover,g.screenshot,g.groom,g.dow_num,g.sdk_version,g.game_size,g.relation_game_name,pa.promote_ratio as ratio,pa.pack_url,pa.promote_id,g.down_port,pa.pack_type')
                   ->join(['tab_promote_apply'=>'pa'],'g.id=pa.game_id and pa.promote_id='.$promote_id)
                   ->where($map)
                   ->select()
                   ->each(function ($item, $key){
                       // 超级签打包
                       if($item['pack_type'] == 4){
                           $payDownload = get_ios_pay_to_download($item['game_id']);
                           $item['pay_download'] = $payDownload['pay_download'];
                           $item['pay_price'] = $payDownload['pay_price'];
                       }else{
                           $item['pay_download'] = 0;
                           $item['pay_price'] = 0;
                       }
                       $item['material_url'] = cmf_get_file_download_url($item['material_url']);
                       $item['icon'] = cmf_get_image_url($item['icon']);
                       $item['cover'] = cmf_get_image_url($item['cover']);
                       $item['hot_cover'] = cmf_get_image_url($item['hot_cover']);
                       $item['groom'] = cmf_get_image_url($item['groom']);
                       $screenshot = [];
                       $screenshot = explode(',',$item['screenshot']);
                       foreach ($screenshot as $k =>$v){
                           $screenshot[$k] = cmf_get_image_url($v);
                       }
                       $item['screenshot'] = implode(',',$screenshot);
                       switch ($item['sdk_version']){
                           case 1:
                               $item['add_game_address'] = cmf_get_file_download_url($item['pack_url']);
                               break;
                           case 2:
                               $item['ios_game_address'] = cmf_get_file_download_url($item['pack_url']);
                               break;
                           case 3:
                               $item['third_party_url'] = cmf_get_domain().'/mobile/game/open_game/game_id/'.$item['game_id'].'/pid/'.$item['promote_id'];
                               break;
                           case 4:
                               $item['third_party_url'] = cmf_get_domain().'/mobile/game/ydetail/game_id/'.$item['game_id'].'/pid/'.$item['promote_id'];
                               break;
                       }
                       return $item;
                   });
         return $gameData->toArray();
     }

    /**
     * 获取游戏区服并返回
     *
     * @param int $game_id
     * @param array $server_ids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/24 9:52
     */
     public function importServer($game_id=0, $server_ids=[])
     {
          $ServerModel = new ServerModel();
          $map['game_id'] = $game_id;
          if(!empty($server_ids)){
              $map['id'] = array('notin',$server_ids);
          }
          $data = $ServerModel
                ->field('id,server_name,server_num,start_time,desride')
                ->where($map)
                ->select()
                ->toArray();
          return $data;
     }

    /**
     * 获取游戏礼包并返回
     *
     * @param int $platform_id
     * @param int $game_id
     * @param array $gift_ids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/24 9:52
     */
    public function importGift($platform_id=0, $game_id=0, $gift_ids=[])
    {
        $GiftbagModel = new GiftbagModel();
        $map['game_id'] = $game_id;
        if(!empty($gift_ids)){
            $map['id'] = array('notin',$gift_ids);
        }
        $map['platform_id'] = $platform_id;
        $data = $GiftbagModel
            ->field('id,giftbag_name,novice,digest,desribe,competence,notice,start_time,end_time,novice_num,remain_num,sort,type,vip')
            ->where($map)
            ->select()
            ->toArray();
        return $data;
    }

    /**
     * 获取渠道平台包并返回
     *
     * @param int $platform_id
     * @param int $game_id
     * @author: Juncl
     * @time: 2021/08/24 9:53
     */
    public function importSource($promote_id=0, $game_id=0)
    {
         $ApplyModel = new PromoteapplyModel();
         $SourceModel = new GamesourceModel();
         // 游戏信息
         $gameInfo = get_game_entity($game_id,'game_name,game_appid,game_size');
         $returnData['game_id'] = $game_id;
         $returnData['game_name'] = $gameInfo['game_name'];
         $returnData['game_appid'] = $gameInfo['game_appid'];
         $returnData['promote_id'] = $promote_id;
         $returnData['promote_account'] = get_promote_name($promote_id);
         // 查询游戏申请记录
         $map['promote_id'] = $promote_id;
         $map['game_id'] = $game_id;
         $map['status']  = 1;
         $map['enable_status'] = 1;
         $applyData = $ApplyModel
                ->field('pack_url,pack_type')
                ->where($map)
                ->find();
         if(empty($applyData)){
             return [];
         }
         // 超级签打包
         if($applyData['pack_type'] == 4){
             $returnData['file_url'] = $applyData['pack_url'];
             $returnData['bao_name'] = '';
             $returnData['file_size'] = $gameInfo['game_size'];
             $returnData['source_version'] = '';
             $returnData['source_name'] = '';
             $returnData['remark'] = '';
             $returnData['down_port'] = 3;
             $payDownload = get_ios_pay_to_download($game_id);
             $returnData['pay_download'] = $payDownload['pay_download'];
             $returnData['pay_price'] = $payDownload['pay_price'];
         }else{
             $sourceData = $SourceModel
                 ->field('bao_name,file_size,source_version,source_name,remark')
                 ->where('game_id',$game_id)
                 ->find();
             if(empty($sourceData)){
                 return [];
             }
             $returnData['file_url'] = cmf_get_file_download_url($applyData['pack_url']);
             $returnData['bao_name'] = $sourceData['bao_name'];
             $returnData['file_size'] = $sourceData['file_size'];
             $returnData['source_version'] = $sourceData['source_version'];
             $returnData['source_name'] = $sourceData['source_name'];
             $returnData['remark'] = $sourceData['remark'];
             $returnData['pay_download'] = 0;
             $returnData['pay_price'] = 0;
             $returnData['down_port'] = 1;
         }
        return $returnData;
    }

    /**
     * 查询导入订单
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/31 15:59
     */
    public function importOrders($param=[])
    {
          $map['pay_time'] = array('gt',$param['last_pay_time']);
          $map['pay_status'] = 1;
          $map['promote_id'] = $param['promote_id'];
          $SpendModel = new SpendModel();
          $data = $SpendModel
                ->field('webplatform_user_id as user_id,game_id,spend_ip,pay_way,pay_amount,cost,pay_promote_id,pay_order_number as trade_no,order_number,pay_time,pay_status,pay_game_status,discount_type,discount,server_id,server_name,game_player_id,game_player_name,role_level,props_name')
               ->where($map)
               ->order('pay_time asc')
               ->limit($param['limit'])
               ->select()
               ->each(function ($item,$key){
                   $item['pay_type'] = $item['pay_promote_id']>0 ? 1 : 2;
                   return $item;
               });
          return $data->toArray();
    }

    /**
     * 查询游戏通知状态
     *
     * @param string $trade_no
     * @return bool
     * @author: Juncl
     * @time: 2021/08/31 16:42
     */
    public function updatePayStatus($trade_no='')
    {
        $SpendModel = new SpendModel();
        $data = $SpendModel->field('pay_game_status')->where('pay_order_number',$trade_no)->find();
        if(empty($data)){
            return false;
        }else{
            return $data;
        }
    }
}