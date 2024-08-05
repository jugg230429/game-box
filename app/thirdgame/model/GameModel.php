<?php

namespace app\thirdgame\model;

use think\Model;
use app\thirdgame\model\GameAttrModel;
use app\thirdgame\model\GameSetModel;
use think\Db;

class GameModel extends Model
{
    protected $table = 'tab_game';

    protected $autoWriteTimestamp = true;

    /**
     * 修改游戏字段
     *
     * @param array $map
     * @param string $name
     * @param string $value
     * @author: Juncl
     * @time: 2021/08/14 13:40
     */
    public function setField($map=[], $name='', $value='')
    {
        if(check_game_attr($name)){
            $GameAttrModel = new GameAttrModel();
            $result = $GameAttrModel->where($map)->setField($name,$value);
        }else{
            $result = $this->where($map)->setField($name,$value);
        }
        return $result;
    }

    /**
     * 获取游戏详情
     *
     * @param int $id
     * @author: Juncl
     * @time: 2021/08/17 11:30
     */
    public function gameDetail($id=0)
    {
        $data = $this
              ->alias('g')
              ->field('g.*,a.is_control,a.promote_declare,a.cp_ratio,a.cp_pay_ratio,a.support_url,a.promote_level_limit,a.discount_show_status,a.coupon_show_status')
              ->join(['tab_game_attr'=>'a'],'tab_game.id=a.game_id','left')
              ->where('g.id', $id)->find();
        return $data;
    }

    /**
     * 编辑游戏
     *
     * @param array $data
     * @author: Juncl
     * @time: 2021/08/18 19:59
     */
    public function editGame($data=[])
    {
         $gameData['game_type_id'] = $data['game_type_id'];
         $gameData['game_type_name'] = $data['game_type_name'];
         $gameData['recommend_status'] = $data['recommend_status'];
         $gameData['sort'] = $data['sort'];
         $gameData['dow_num'] = $data['dow_num'];
         $gameData['game_score'] = $data['game_score'];
         $gameData['short'] = $data['short'];
         $gameData['features'] = $data['features'];
         $gameData['introduction'] = $data['introduction'];
         $gameData['tag_name'] = $data['tag_name'];
         $gameData['icon'] = $data['icon'];
         $gameData['cover'] = $data['cover'];
         $gameData['hot_cover'] = $data['hot_cover'];
         $gameData['groom'] = $data['groom'];
         $gameData['video_cover'] = $data['video_cover'];
         $gameData['video'] = $data['video'];
         $gameData['video_url'] = $data['video_url'];
         $gameData['vip_table_pic'] = $data['vip_table_pic'];
         $gameData['game_status'] = $data['game_status'];
         $gameData['test_game_status'] = $data['test_game_status'];
         $gameData['only_for_promote'] = $data['only_for_promote'];
         $gameData['ratio'] = $data['ratio'];
         $gameData['money'] = $data['money'];
         $gameData['first_support_num'] = $data['first_support_num'];
         $gameData['following_support_rate'] = $data['following_support_rate'];
         $gameData['back_describe'] = $data['back_describe'];
         $gameData['back_map'] = $data['back_map'];
         $result = $this->where('id',$data['id'])->update($gameData);
         if($result !== false){
             //修改关联表 game_attr,没有则新增
             $GameAttrModel = new GameAttrModel();
             $gameAttrData['promote_declare'] = $data['promote_declare']??'';
             $gameAttrData['cp_ratio'] = $data['cp_ratio']?:0;
             $gameAttrData['is_control'] = $data['is_control']?:0;
             $gameAttrData['cp_pay_ratio'] = $data['cp_pay_ratio']?:0;
             $gameAttrData['support_url'] = $data['support_url']?:0;
             $gameAttrData['promote_level_limit'] = $data['promote_level_limit']??0;
             $gameAttrData['discount_show_status'] = $data['discount_show_status']??1;
             $gameAttrData['coupon_show_status'] = $data['coupon_show_status']??1;
             if($data['old_ratio'] != $data['ratio']){
                 $gameAttrData['ratio_begin_time'] = time();
             }
             if($data['old_money'] != $data['money']){
                 $gameAttrData['money_begin_time'] = time();
             }
             $game_attr_id = $GameAttrModel->where('game_id',$data['id'])->value('id');
             if($game_attr_id > 0){
                 $GameAttrModel->where('game_id',$data['id'])->update($gameAttrData);
             }else{
                 $gameAttrData['game_id'] = $data['id'];
                 $GameAttrModel->insert($gameAttrData);
             }
             return ['status'=>1];
         }else{
             return ['status'=>0,'msg'=>'编辑失败'];
         }
    }

    /**
     * 批量存入游戏
     *
     * @author: Juncl
     * @time: 2021/08/20 15:58
     */
    public function importGame($platform_id=0,$param=[])
    {
         // 导入失败个数
         $err_num = 0;
         // 导入成功个数
         $suc_num = 0;
         // 第一个游戏分类
         $game_type = get_first_type();
         $GameModel = new GameModel();
         $GameSetModel = new GameSetModel();
         $GameAttrModel = new GameAttrModel();
         //获取CP
         $cp_id = get_platform_entity_by_map($platform_id,'cp_id')['cp_id'];
         foreach ($param as $key => $val)
         {
             //检查游戏名是否存在
             if(get_game_by_name($val['game_name']) === false){
                 $err_num++;
                 continue;
             };
             Db::startTrans();
             try {
                 //导入游戏数据
                 $gameData = [];
                 $gameData['game_name'] = $val['game_name'] ?: '';//游戏名
                 $gameData['sort'] = $val['sort'] ?: 0;//排序
                 $gameData['short'] = $val['short'] ?: '';//游戏简写
                 $gameData['game_type_id'] = $game_type['id'];//游戏分类ID
                 $gameData['game_type_name'] = $game_type['type_name'];//游戏分类名称
                 $gameData['game_score'] = $val['game_score'] ?: 0;//游戏评分
                 $gameData['tag_name'] = $val['tag_name'] ?: '';//tag_name
                 $gameData['features'] = $val['features'] ?: '';//游戏简介
                 $gameData['introduction'] = $val['introduction'] ?: '';//详细介绍
                 $gameData['recommend_status'] = $val['recommend_status'] ?: 0;//推荐状态(0:不推荐,1推荐 2热门 3最新)
                 $gameData['icon'] = $val['icon'] ?: '';//游戏图标
                 $gameData['cover'] = $val['cover'] ?: '';//游戏封面
                 $gameData['material_url'] = $val['material_url'] ?: '';//游戏素材
                 $gameData['hot_cover'] = $val['hot_cover'] ?: '';//热门推荐图
                 $gameData['screenshot'] = $val['screenshot'] ?: '';//游戏截图
                 $gameData['groom'] = $val['groom'] ?: '';//推荐图
                 $gameData['sdk_version'] = $val['sdk_version'] ?: 1;//游戏版本1安卓2苹果3H54页游
                 if($val['sdk_version'] == 3 || $val['sdk_version'] == 4){
                     $gameData['add_game_address'] = $val['third_party_url'] ?: '';//H5和页游游戏地址
                 }else{
                     $gameData['add_game_address'] = $val['add_game_address'] ?: '';//安卓包下载地址
                 }
                 $gameData['ios_game_address'] = $val['ios_game_address'] ?: '';//苹果包下载地址
                 $gameData['dow_num'] = $val['dow_num'] ?: '';//下载数量
                 $gameData['create_time'] = time();
                 $gameData['game_appid'] = generate_game_appid();//
                 $gameData['game_address_size'] = $val['game_size'] ?: '';//游戏包大小
                 $gameData['down_port'] = $val['down_port'];//1官方 3超级签
                 $gameData['cp_game_id'] = $val['game_id'] ?: 0;//cp游戏ID
                 $gameData['platform_id'] = $platform_id;//所属平台
                 $gameData['game_status'] = 1;
                 $gameData['cp_id'] = $cp_id?:0;
                 $gameId = $GameModel->insertGetId($gameData);
                 if ($gameId > 0) {
                     $save['relation_game_id'] = $gameId;
                     $save['relation_game_name'] = $val['relation_game_name'];//关联游戏名
                     $GameModel->where('id', $gameId)->update($save);
                     //新增game_set表和game_attr表
                     $GameSetData = [];
                     $GameSetData['id'] = $gameId;
                     $GameSetData['game_id'] = $gameId;
                     $setId = $GameSetModel->insert($GameSetData);
                     $GameAttrData = [];
                     $GameAttrData['game_id'] = $gameId;
                     $GameAttrData['cp_ratio'] = $val['ratio']?:0;
                     $source_info['promote_id'] = $val['promote_id'];
                     $source_info['promote_account'] = $val['promote_account'];
                     $GameAttrData['third_source_info'] = json_encode($source_info);
                     $attrId = $GameAttrModel->insertGetId($GameAttrData);
                     // 超级签游戏存入超级签付费下载
                     if($val['down_port'] == 3){
                         $PayDownloadModel = new GameIosPayToDownloadModel();
                         $payDownData['game_id'] = $gameId;
                         $payDownData['pay_download'] = $val['pay_download'];
                         $payDownData['pay_price'] = $val['pay_price'];
                         $payDownData['update_time'] = time();
                         $payDownData['create_time'] = time();
                         $PayDownloadModel->insert($payDownData);
                     }
                     if ($setId > 0 && $attrId > 0) {
                         Db::commit();
                         $suc_num++;
                         continue;
                     } else {
                         Db::rollback();
                         $err_num++;
                         continue;
                     }
                 }
             }catch (\Exception $e){
                 Db::rollback();
                 $err_num++;
                 continue;
             }
         }
         $data = [];
         $data['err_num'] = $err_num;
         $data['suc_num'] = $suc_num;
         return $data;
    }

}