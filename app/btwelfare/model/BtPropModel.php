<?php

namespace app\btwelfare\model;

use think\Model;

class BtPropModel extends Model
{

    protected $table = 'tab_bt_prop';
    protected $autoWriteTimestamp = true;


    /**
     * @新增道具
     *
     * @author: zsl
     * @since: 2021/1/13 16:27
     */
    public function add($param)
    {
        $this -> game_id = $param['game_id'];
        $this -> prop_name = $param['prop_name'];
        $this -> prop_tag = $param['prop_tag'];
        $this -> number = $param['number'];
        $this -> status = $param['status'];
        $result = $this -> isUpdate(false) -> save();
        return $result;
    }


    /**
     * @编辑道具
     *
     * @author: zsl
     * @since: 2021/1/13 16:36
     */
    public function edit($param)
    {
        $this -> game_id = $param['game_id'];
        $this -> prop_name = $param['prop_name'];
        $this -> prop_tag = $param['prop_tag'];
        $this -> number = $param['number'];
        $this -> status = $param['status'];
        $result = $this -> allowField(true) -> isUpdate(true) -> save($this, ['id' => $param['id']]);
        return $result;
    }


}

