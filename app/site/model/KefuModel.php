<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class KefuModel extends Model
{

    protected $table = 'tab_kefuquestion';

    protected $autoWriteTimestamp = true;

    /**
     * [获取SDK所有客服问题]
     * @author 郭家屯[gjt]
     */
    public function getLists()
    {
        $lists = $this->alias('k')->field('GROUP_CONCAT(zititle ORDER BY k.sort desc,k.id desc) as second_title,type as mark,name as first_title,kt.icon')
            ->join(['tab_kefuquestion_type' => 'kt'], 'k.type=kt.id', 'left')
            ->where('k.status', 1)
            ->where('k.platform_type', 1)
            ->group('type')
            ->order('kt.sort desc')
            ->select()->toArray();
        return $lists;
    }

    public function getTypeLists($type = 0)
    {
        $quesition = $this->field('zititle,content')
            ->where('status', 1)
            ->where('type', $type)
            ->order('sort desc,id desc')
            ->select()->toArray();

        return $quesition;
    }
}