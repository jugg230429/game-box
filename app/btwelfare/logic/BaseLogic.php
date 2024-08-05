<?php

namespace app\btwelfare\logic;

class BaseLogic
{

    protected $result;

    public function __construct()
    {
        $this -> result = ['code' => 200, 'msg' => '请求成功', 'data' => []];
    }


    /**
     * @获取道具内容
     *
     * @author: zsl
     * @since: 2021/1/14 16:52
     */
    public function getPropContent($ids)
    {
        $propIds = explode(',', $ids);
        $where = [];
        $where['id'] = ['in', $propIds];
        $where['status'] = 1;
        $propList = $this -> mBtProp -> field('id,prop_name,prop_tag,number') -> where($where) -> select();
        return json_encode($propList);
    }


}