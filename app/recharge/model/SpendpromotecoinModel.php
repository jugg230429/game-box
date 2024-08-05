<?php

namespace app\recharge\model;

use think\Model;
use think\Db;

/**
 * yyh
 */
class SpendpromotecoinModel extends Model
{

    protected $table = 'tab_spend_promote_coin';

    protected $autoWriteTimestamp = true;

    /**
     * [sendcoin 给渠道发放平台币]
     * @return [type] [description]
     * yyh
     */
    public function sendcoin($data = [])
    {
        $this->data([
            'promote_id' => $data['promote_id'],
            'promote_type' => $data['type'],
            'num' => $data['num'],
            'op_id' => cmf_get_current_admin_id(),
            'create_time' => time(),
        ]);
        $this->startTrans();
        $res = $this->isUpdate(false)->allowField(true)->save();
        if ($res) {
            $res2 = Db::table('tab_promote')->where(['id' => $data['promote_id']])->setInc('balance_coin', $data['num']);
            if ($res2) {
                //异常预警提醒
                if($data['num'] >= 500){
                    $warning = [
                            'type'=>6,
                            'promote_id'=>$data['promote_id'],
                            'promote_account'=>get_promote_entity($data['promote_id'],'account')['account'],
                            'target'=>2,
                            'record_id'=>$res,
                            'unusual_money'=>$data['num'],
                            'create_time'=>time()
                    ];
                    Db::table('tab_warning')->insert($warning);
                }
                $this->commit();
                return true;
            } else {
                $this->rollback();
                return false;
            }
        } else {
            $this->rollback();
            return false;
        }
    }

    /**
     * [sendcoin 给渠道收回平台币]
     * @return [type] [description]
     * yyh
     */
    public function deductcoin($data = [])
    {
        $this->data([
            'promote_id' => $data['promote_id'],
            'promote_type' => $data['type'],
            'num' => $data['num'],
            'op_id' => cmf_get_current_admin_id(),
            'type' => 2,
            'create_time' => time(),
        ]);
        $this->startTrans();
        $res = $this->isUpdate(false)->allowField(true)->save();
        if ($res) {
            $res2 = Db::table('tab_promote')->where(['id' => $data['promote_id']])->setDec('balance_coin', $data['num']);
            if ($res2) {
                $this->commit();
                return true;
            } else {
                $this->rollback();
                return false;
            }
        } else {
            $this->rollback();
            return false;
        }
    }

    //yyh
    public function lists($map = [], $field = '*')
    {
        $data = $this->field($field)->where($map)->select();
        return $data;
    }
}