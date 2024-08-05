<?php

namespace app\promote\model;

use think\Model;
use think\Db;

class PromotecoinModel extends Model
{

    protected $table = 'tab_promote_coin';

    protected $autoWriteTimestamp = true;

    //记录转移数据
    public function insert_data($data = [])
    {
        $add['promote_id'] = $data['promote_id'];//获得平台币id
        $add['promote_type'] = $data['promote_type'];//来源渠道等级
        $add['num'] = $data['num'];//数量
        $add['type'] = $data['type'];//1发放 2扣除
        $add['source_id'] = $data['source_id'];//获得平台币id
        $add['create_time'] = time();
        return $this->insert($add);
    }

    public function lists($map = [], $field = '*')
    {
        $data = $this->field($field)->where($map)->select();
        return $data;
    }

    /**
     * @函数或方法说明
     * @转移平台币
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2019/8/10 9:21
     */
    public function transfer($data=[]){
        //记录扣除
        $add[0]['promote_id'] = $data['promotezi'];
        $add[0]['num'] = $data['amount'];
        $add[0]['type'] = 2;
        $add[0]['promote_type'] = 2;
        $add[0]['source_id'] = $data['pid'];//一级
        $add[0]['create_time'] = time();
        //记录发放
        $add[1]['promote_id'] = $data['pid'];
        $add[1]['num'] = $data['amount'];
        $add[1]['type'] = 1;
        $add[1]['promote_type'] = 1;
        $add[1]['source_id'] = $data['promotezi'];
        $add[1]['create_time'] = time();
        Db::startTrans();
        try {
           $this->insertAll($add);
            Db::table('tab_promote')->where(['id' => $data['pid']])->setDec('balance_coin', $data['amount']);
            Db::table('tab_promote')->where(['id' => $data['promotezi']])->setInc('balance_coin', $data['amount']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

}