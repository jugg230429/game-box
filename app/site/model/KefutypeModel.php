<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class KefutypeModel extends Model
{

    protected $table = 'tab_kefuquestion_type';

    protected $autoWriteTimestamp = true;

    /**
     * [获取推广所有问题]
     * @author 郭家屯[gjt]
     */
    public function getPromoteLists()
    {
        $lists = $this->alias('kt')->field('kt.id,kt.name,zititle,content,k.sort')
            ->join(['tab_kefuquestion' => 'k'], 'k.type=kt.id and k.status=1', 'left')
            ->where('kt.status', 1)
            ->where('kt.platform_type', 2)
            ->order('kt.sort desc,id desc')
            ->select()->toArray();
        return $lists;
    }


    /**
     * @获取PC官网分类列表
     *
     * @author: zsl
     * @since: 2021/4/20 20:11
     */
    public function getPcTypeLists($limit=99)
    {
        $field = "id,name,icon";
        $where = [];
        $where['platform_type'] = 3;
        $where['status'] = 1;
        $lists = $this -> field($field) -> where($where) -> order('sort desc') ->limit($limit) -> select();
        return $lists ? $lists : [];
    }

}
