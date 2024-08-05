<?php

namespace app\thirdgame\logic;

use app\thirdgame\model\WebPlatformModel;
use app\common\controller\BaseController;
use app\thirdgame\validate\WebPlatValidate;

class WebPlatformLogic
{
    /**
     * @查询平台列表
     * @param array $map
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/11 11:42
     */
    public function lists($map = [])
    {
        $PlatModel = new WebPlatformModel();
        $BaseController = new BaseController();
        $extend['order'] = 'id desc';
        $data = $BaseController->data_list($PlatModel, $map, $extend);
        return $data;
    }

    /**
     * @新增平台
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/11 11:42
     */
    public function add($param = [])
    {
        $PlatModel = new WebPlatformModel();
        $validate = new WebPlatValidate();
        if (!$validate->check($param)) {
            return ['status' => 0, 'msg' => $validate->getError()];
        }
        $id = $PlatModel->add($param);
        if ($id > 0) {
            return ['status' => 1, 'msg' => '新增成功'];
        } else {
            return ['status' => 0, 'msg' => '新增失败'];
        }
    }

    /**
     * @编辑平台
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/11 11:42
     */
    public function edit($param = [])
    {
        $model = new WebPlatformModel();
        $validate = new WebPlatValidate();
        if(!$param['id']){
            return ['status' => 0, 'msg' => '参数异常'];
        }
        $isData = $model->detail($param['id'],'id');
        if(empty($isData)){
            return ['status' => 0, 'msg' => '数据不存在'];
        }
        if (!$validate->check($param)) {
            return ['status' => 0, 'msg' => $validate->getError()];
        }
        $result = $model->edit($param);
        if(!$result){
            return ['status' => 0, 'msg' => '修改失败'];
        }else{
            return ['status' => 1, 'msg' => '修改成功'];
        }
    }

    /**
     * @获取平台详情
     * @param int $id
     * @param string $field
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @author: Juncl
     * @time: 2021/08/11 11:43
     */
    public function detail($id = 0, $field='*')
    {
        if(!$id){
            return false;
        }
        $model = new WebPlatformModel();
        $data = $model->detail($id,$field);
        if(!$data){
            return false;
        }else{
            return $data;
        }
    }

    /**
     * @修改平台状态
     * @param int $id
     * @param string $field
     * @param string $value
     * @return array|bool|int
     * @author: Juncl
     * @time: 2021/08/11 11:43
     */
    public function setStatus($id = 0,$field = 'status', $value='1')
    {
        if(!$id){
            return false;
        }
        if($value != 0 && $value != 1){
            return false;
        }
        $model = new WebPlatformModel();
        $isData = $model->detail($id,$field);
        if(empty($isData)){
            return ['status' => 0, 'msg' => '数据不存在'];
        }
        $result = $model->setStatus($id,$field,$value);
        return $result;
    }

}