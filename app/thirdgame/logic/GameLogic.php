<?php

namespace app\thirdgame\logic;

use app\common\controller\BaseController;
use app\promote\controller\PromoteapplyController;
use app\thirdgame\model\GameAttrModel;
use app\thirdgame\model\GameModel;
use app\game\model\GameTypeModel;
use app\thirdgame\model\PlatformModel;
use app\thirdgame\validate\GameValidate;
use app\member\model\WarningModel;

class GameLogic
{
    /**
     * 第三方游戏列表
     *
     * @author: Juncl
     * @time: 2021/08/11 20:18
     */
    public function lists($map = [],$where_str=''){
         $GameModel = new GameModel();
         $base = new BaseController();
         $exend['field'] = 'tab_game.id,game_name,tab_game.icon,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id,promote_declare,cp_ratio,cp_pay_ratio,platform_id,game_score,ratio,money,material_url,add_game_address,ios_game_address';
        //排序优先，ID在后
         $exend['order'] = 'tab_game.sort desc,tab_game.id desc';
        //关联游戏类型表
         $exend['join1'] = [['tab_game_attr' => 't'], 'tab_game.id=t.game_id', 'left'];
         $status_arr =  ['0' => '已下架', '1' => '上架中'];
         $data = $base->data_list_join($GameModel, $map, $exend,$where_str)->each(function ($item, $key) use($status_arr) {
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= " " . get_info_status($vo, 7);
            }
            $item['recommend_status'] = $recommend_status;
            $item['relation_status'] = get_relation_game($item['id'], $item['relation_game_id']) === -1 ?: (get_relation_game($item['id'], $item['relation_game_id']) ? 1 : 0);
            $item['status_name'] = $status_arr[$item['game_status']];
            if($item['promote_ids']){
                $item['promote_ids'] = count(array_filter(explode(',',$item['promote_ids'])));
            }else{
                $item['promote_ids'] = 0;
            }
            return $item;
         });
         return $data?:[];
    }

    /**
     * 编辑游戏
     *
     * @param array $data
     * @return array
     * @author: Juncl
     * @time: 2021/08/18 19:59
     */
    public function editGame($data=[])
    {
        //处理游戏类型-20210820-byh-s
        if(!empty($data['game_type_id'])){
            if(count($data['game_type_id'])>3){
                return ['status'=>0,'msg'=>'最多可以选择三个游戏类型'];
            }
            $data['game_type_id'] = implode(',',$data['game_type_id']);
        }else{
            return ['status'=>0,'msg'=>'最少选择一个游戏类型'];
        }
        //处理游戏类型-20210820-byh-e
        // 视频处理
        if(!empty($data['video'])){
            foreach ($data['video'] as $k => $v){
                if(empty($v)){
                    unset($data['video'][$k]);
                }
            }
            $data['video'] = array_values($data['video']);//键从0开始处理
            $data['video'] = json_encode($data['video']);
        }
        $GameValidate = new GameValidate();
        if (!$GameValidate->scene('edit')->check($data)) {
            return ['status'=>0,'msg'=>$GameValidate->getError()];
        }
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $GameModel = new GameModel();
        // 获取原有渠道等级限制
        $GameAttrModel = new GameAttrModel();
        $oldLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
        // 获取原有的分成比例和注册单价，如果修改 则更新开始时间
        $game_info = get_game_entity($data['id'],'ratio,money');
        $data['old_ratio'] = $game_info['ratio'];
        $data['old_money'] = $game_info['money'];
        $result = $GameModel->editGame($data);
        if($result['status']){
            // 删除渠道等级小于现在等级的所有已申请游戏
            $newLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
            if($newLevel > 0 && $newLevel > $oldLevel){
                $applyRecord = new PromoteapplyController();
                $applyRecord->delApplyRecord($newLevel,$data['id']);
            }
            return ['status'=>1,'msg'=>'修改成功'];
        }else{
            return ['status'=>0,'msg'=>'修改失败'];
        }
    }

    /**
     * 编辑游戏获取游戏详情
     *
     * @param int $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @author: Juncl
     * @time: 2021/08/17 11:30
     */
    public function gameDetail($id=0)
    {
         $GameModel = new GameModel();
         $game = $GameModel->gameDetail($id);
         if(empty($game)) return [];
         $game = $game->toArray();
         $tag_name = explode(',', $game['tag_name']);
         $game['tag_name_one'] = $tag_name[0];
         $game['tag_name_two'] = $tag_name[1];
         $game['tag_name_three'] = $tag_name[2];
         $game['apple_in_app_set'] = json_decode($game['apple_in_app_set'], true);
        if(!empty($game['video'])){
            $_video = json_decode($game['video'], true);
            if(empty($_video)){//为null则是原单个数据
                $_video[] = $game['video'];
            }
            $game['video'] = $_video;
        }
         return $game;
    }

    /**
     * 获取所有游戏分类
     *
     * @return array
     * @author: Juncl
     * @time: 2021/08/14 15:40
     */
     public function getGameType()
     {
          $model = new GameTypeModel();
          $data = $model->field('id,type_name')->where('status', 1)->select();
          return $data?:[];
     }

    /**
     * 修改游戏字段
     *
     * @param int $id
     * @param string $name
     * @param string $value
     * @author: Juncl
     * @time: 2021/08/14 13:44
     */
     public function setGameField($id=0, $name='', $value='')
     {
         $GameModel = new GameModel();
         if(is_array($id)){
             if(check_game_attr($name)){
                 $map['game_id'] = array('in',$id);
             }else{
                 $map['id'] = array('in',$id);
             }
             $attrMap['game_id'] = array('in',$id);
         }elseif(is_numeric($id)){
             if(check_game_attr($name)) {
                 $map['game_id'] = $id;
             }else{
                 $map['id'] = $id;
             }
             $attrMap['game_id'] = $id;
         }else{
            return ['status'=>0,'msg'=>'参数异常'];
         }
         $GameValidate = new GameValidate();
         $data = [];
         $data[$name] = $value;
         if(!$GameValidate->scene('setField')->check($data)){
             return ['status'=>0,'msg'=>$GameValidate->getError()];
         }
         $result = $GameModel->setField($map,$name,$value);
         if($result === false){
             return ['status'=>0,'msg'=>'修改失败'];
         }else{
             $GameAttrModel = new GameAttrModel();
             // 修改分成比例和注册单间更新时间
             if($name == 'ratio'){
                 $GameAttrModel->where($attrMap)->setField('ratio_begin_time',time());
             }else if($name == 'money'){
                 $GameAttrModel->where($attrMap)->setField('money_begin_time',time());
             }
             return ['status'=>1,'msg'=>'修改成功'];
         }
     }

    /**
     * @函数或方法说明
     * @代充折扣预警处理
     * @param $discount
     * @param $game_id
     *
     * @author: juncl
     * @since: 2021/8/18 10:12
     */
    public function edit_warning($discount=0, $game_id=0)
    {
        $WarningModel = new WarningModel();
        $warning = $WarningModel->field('id')->where('game_id',$game_id)->where('record_id',$game_id)->where('type',4)->where('status',0)->find();
        if($warning){
            if($discount > 3){
                $save['op_id'] = cmf_get_current_admin_id();
                $save['op_account'] = cmf_get_current_admin_name();
                $save['status'] = 1;
                $save['op_time'] = time();
                $WarningModel->where('id',$warning['id'])->update($save);
            }else{
                $WarningModel->where('id',$warning['id'])->setField('discount_first',$discount);
            }
        }else{
            if($discount <= 3){
                $warning_data = [
                    'type'=>4,
                    'game_id'=>$game_id,
                    'target'=>1,
                    'record_id'=>$game_id,
                    'discount_first'=>$discount,
                    'create_time'=>time()
                ];
                $WarningModel->insert($warning_data);
            }
        }
    }
}