<?php

namespace app\thirdgame\model;

use app\game\model\CpModel;
use think\Model;

class PlatformModel extends Model
{
    protected $table = 'tab_platform';

    protected $autoWriteTimestamp = true;

    /**
     * @获取平台列表
     * @param array $map
     * @param int $limite
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function get_platform($map = [])
    {
        $data = $this
              ->field('id,platform_name,platform_url,api_key,pay_type,status,create_time,marking')
              ->where($map)
              ->order('id asc')
              ->select()
              ->toArray();
        return $data?:[];

    }

    /**
     * @新增平台
     * @param array $param
     * @return int|string
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function add($param = [])
    {
        $data = [];
        $data['platform_name'] = $param['platform_name'];
        $data['platform_url'] = $param['platform_url'];
        $data['api_key'] = $param['api_key'];
        $data['pay_type'] = $param['pay_type'];
        $data['marking'] = $param['marking'];
        $data['cp_id'] = $param['cp_id'];
        $data['select_game_url'] = $param['select_game_url'];
        $data['import_game_url'] = $param['import_game_url'];
        $data['import_gift_url'] = $param['import_gift_url'];
        $data['import_server_url'] = $param['import_server_url'];
        $data['import_source_url'] = $param['import_source_url'];
        $data['import_spend_url'] = $param['import_spend_url'];
        $data['import_pay_url'] = $param['import_pay_url'];
        $data['status'] = 1;
        $data['create_time'] =time();
        $data['update_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $data['op_nickname'] = cmf_get_current_admin_name();
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * @获取平台详情
     * @param int $id
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function detail($id = 0, $field='*')
    {
        if($id > 0){
            $data = $this->field($field)->where('id',$id)->find();
        }else{
            $data = $this->field($field)->order('id asc')->find();
        }
        return $data;
    }

    /**
     * @编辑额平台
     * @param array $param
     * @return PlatformModel
     * @author: Juncl
     * @time: 2021/08/11 11:45
     */
    public function edit($param = [])
    {
        $data['id'] = $param['id'];
        $data['platform_name'] = $param['platform_name'];
        $data['platform_url'] = $param['platform_url'];
        $data['api_key'] = $param['api_key'];
        $data['status'] = $param['status'];
        $data['marking'] = $param['marking'];
        $data['select_game_url'] = $param['select_game_url'];
        $data['import_game_url'] = $param['import_game_url'];
        $data['import_gift_url'] = $param['import_gift_url'];
        $data['import_server_url'] = $param['import_server_url'];
        $data['import_source_url'] = $param['import_source_url'];
        $data['import_spend_url'] = $param['import_spend_url'];
        $data['import_pay_url'] = $param['import_pay_url'];
        $data['update_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $data['op_nickname'] = cmf_get_current_admin_name();
        $result = $this->Update($data);
        return $result;
    }

    /**
     * @修改平台属性
     * @param int $id
     * @param string $field
     * @param string $value
     * @return int
     * @author: Juncl
     * @time: 2021/08/11 11:45
     */
    public function setStatus($id = 0,$field = 'status', $value='1')
    {
        $result = $this->where('id',$id)->setField($field, $value);
        return $result;
    }

    /**
     * 新增CP
     *
     * @param array $param
     * @return bool|int|string
     * @author: Juncl
     * @time: 2021/08/26 16:24
     */
    public function add_cp($param=[])
    {
        $GameCpModel = new CpModel();
        $id = $GameCpModel->where('cp_name',$param['platform_name'])->value('id');
        // cp已存在则拼接_1
        if($id >0){
            $param['platform_name'] = $param['platform_name'] . '_1';
        }
        $data['cp_name'] = $param['platform_name'];
        $data['cp_attribute'] = 1;
        $data['create_time'] =  time();
        $data['update_time'] = time();
        $data['remark'] = '第三方平台自动生成';
        $cp_id = $GameCpModel->insertGetId($data);
        if($cp_id>0){
            return $cp_id;
        }else{
            return false;
        }
    }


}