<?php
namespace app\game\model;
use think\Db;
use think\Model;
use think\Pinyin;

/**
 * CP商表 模型
 * wjd
 */
class CpModel extends Model{

    protected $table = 'tab_game_cp';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 验证CP商名称
    public function checkGameName($cp_name = '',$id = 0)
    {
        if (empty($cp_name)) {
            return ['status' => 1, 'msg' => 'cp商名称不能为空'];
        }
        $map = [];
        $map['cp_name'] = $cp_name;
        if($id == 0){
            $data = $this->field('id, cp_name')->where($map)->find();
        }else{
            // $map['id'] = [];
            $data = $this->field('id, cp_name')->where($map)->where("id<>$id")->find();
        }
       
        if (!empty($data)) {
            $msg = 'cp商名称已存在，请重新输入！';
            return ['status' => 1, 'msg' => $msg];
        }else{
            $msg = 'cp商名称可以使用';
            return ['status' => 0, 'msg' => $msg];
        }
    }
    // 与游戏关联
    public function hasGame(){
        return $this->hasMany('GameModel', 'cp_id', 'id');
    }
    // 查询cp商列表
    public function cpLists($map){
        $data = $this->withCount(['hasGame'])->where($map)->select();
    }
    // 带条件查询cp商列表
    public function cpLists2($map,$game_ids){
        $data = $this->withCount(['hasGame'=>function($query) use ($game_ids){
            $query->where('id','in',$game_ids);
        }])
        ->where($map)
        ->select();

        return $data;
    }

    // 修改cp商信息
    public function edit($data, $option_type){
        // if($option_type == 1){

        // }
        // if(){

        // }
        $update_res = $this->isUpdate(true)->save($data);
        return $update_res;
    }
}