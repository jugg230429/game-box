<?php

namespace app\oa\model;

use think\Model;

class StudioModel extends Model
{

    protected $table = 'tab_oa_studio';

    protected $autoWriteTimestamp = true;


    /**
     * @新增工作室
     *
     * @author: zsl
     * @since: 2021/3/1 15:23
     */
    public function add($param)
    {
        $this -> studio_name = $param['studio_name'];
        $this -> appid = $param['appid'];
        $this -> domain = $param['domain'];
        $this -> api_key = $param['api_key'];
        $this -> create_type = $param['create_type'];
        $this -> status = $param['status'];
        $result = $this -> isUpdate(false) -> save();
        return $result;
    }

    /**
     * @编辑工作室
     *
     * @author: zsl
     * @since: 2021/1/13 16:36
     */
    public function edit($param)
    {
        $this -> id = $param['id'];
        $this -> studio_name = $param['studio_name'];
        $this -> domain = $param['domain'];
        $this -> api_key = $param['api_key'];
        $this -> status = $param['status'];
        $result = $this -> isUpdate(true) -> save();
        return $result;
    }


}