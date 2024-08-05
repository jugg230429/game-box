<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\common\controller;

use cmf\controller\HomeBaseController;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteunionModel;
use app\game\model\GameModel;
use think\Db;

class BaseHomeController extends HomeBaseController
{


    //单表pagin查询
    public function data_list($model = null, $where = [], $extend = [],$where_str='')
    {
        $model || $this -> error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int) $extend['row'] ?: ($this -> request -> param('row') ?: config('paginate.list_rows'));//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'] ?: 'id desc';
        $group = $extend['group'] ?: null;
        $whereOr = $extend['whereor'];
        if ($extend['no_paginate']) {
            //不分页
            $data = $model
                    -> field($field)
                    -> whereOr($whereOr)
                    -> where($where)
                    -> where($where_str)
                    -> order($order)
                    -> group($group)
                    -> select();
        } else {
            $param = $this -> request -> param();
            unset($param['c'],$param['s']);
            $data = $model
                    -> field($field)
                    -> whereOr($whereOr)
                    -> where($where)
                    -> where($where_str)
                    -> order($order)
                    -> group($group)
                    -> paginate($row, false, ['query' => $param]);//https://www.kancloud.cn/manual/thinkphp5/154294
        }
        return $data;
    }

    //多表pagin查询
    public function data_list_join($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'];
        $group = $extend['group'] ?: null;
        $join1 = $extend['join1'];
        $join2 = $extend['join2'];
        $join3 = $extend['join3'];
        $param = $this -> request -> param();
        unset($param['c'],$param['s']);
        $data = $model
            ->field($field)
            ->join($join1[0], $join1[1], $join1[2])
            ->join($join2[0], $join2[1], $join2[2])
            ->join($join3[0], $join3[1], $join3[2])
            ->where($where)
            ->order($order)
            ->group($group)
            ->paginate($row, false, ['query' => $param]);
        return $data;
    }

    //多表pagin查询
    public function data_list_join_time($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'];
        $group = $extend['group'] ?: null;
        $join1 = $extend['join1'];
        $join2 = $extend['join2'];
        $join3 = $extend['join3'];
        $param = $this -> request -> param();
        unset($param['c'],$param['s']);
        $data = $model
                ->field($field)
                ->join($join1[0], $join1[1], $join1[2])
                ->join($join2[0], $join2[1], $join2[2])
                ->join($join3[0], $join3[1], $join3[2])
                ->where($where)
                ->where('start_time',[['elt',time()],['eq',0]],'or')
                ->where('end_time',[['gt',time()],['eq',0]],'or')
                ->order($order)
                ->group($group)
                ->paginate($row, false, ['query' => $param]);
        return $data;
    }

    //单表select查询
    public function data_list_select($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: config('paginate.list_rows');//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'] ?: 'id desc';
        $group = $extend['group'] ?: null;
        $data = $model
            ->field($field)
            ->where($where)
            ->order($order)
            ->group($group)
            ->select()->toarray();
        return $data;
    }

    //多表select查询
    public function data_list_join_select($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: config('paginate.list_rows');//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'];
        $group = $extend['group'] ?: null;
        $join1 = $extend['join1'];
        $join2 = $extend['join2'];
        $join3 = $extend['join3'];
        $data = $model
            ->field($field)
            ->join($join1[0], $join1[1], $join1[2])
            ->join($join2[0], $join2[1], $join2[2])
            ->join($join3[0], $join3[1], $join3[2])
            ->where($where)
            ->order($order)
            ->group($group)
            ->select()->toarray();
        return $data;
    }



    /**
     * [游戏下载]
     * @author 郭家屯[gjt]
     */
    public function down_file($game_id = 0, $sdk_version = 0, $user_id = 0, $promote_id = 0)
    {
        if (AUTH_PROMOTE == 1) {
            $serverhost = $_SERVER['HTTP_HOST'];
            $serverhostarr = [$serverhost, "http://" . $serverhost, "https://" . $serverhost];
            $union_model = new PromoteunionModel();
            $host = $union_model->where('domain_url', 'in', $serverhostarr)->find();

            if(empty($promote_id)){
                $promote_id = $host['union_id'] ? $host['union_id'] : $promote_id;
            }
        }
        if (cmf_is_mobile()) {
            $type = get_devices_type();
        } else {
            $type = $sdk_version;
        }
        if (empty($promote_id)) {
            $this->official_down_file($game_id, $type, $user_id);
        } else {
            $this->promote_down_file($game_id, $promote_id, $type, $user_id);
        }
    }

    /**
     * [微端下载]
     * @author 郭家屯[gjt]
     */
    public function down_weiduan_file($game_id = 0, $sdk_version = 0, $user_id = 0, $promote_id = 0)
    {
        if (AUTH_PROMOTE == 1) {
            $serverhost = $_SERVER['HTTP_HOST'];
            $serverhostarr = [$serverhost, "http://" . $serverhost, "https://" . $serverhost];
            $union_model = new PromoteunionModel();
            $host = $union_model->where('domain_url', 'in', $serverhostarr)->find();

            $promote_id = $host['union_id'] ? $host['union_id'] : $promote_id;
        }
        if (cmf_is_mobile()) {
            $type = get_devices_type();
        } else {
            $type = $sdk_version;
        }
        if (empty($promote_id)) {
            $this->official_weiduan_down_file($game_id, $type, $user_id);
        } else {
            $this->promote_weiduan_down_file($game_id, $promote_id, $type, $user_id);
        }
    }

    /**
     *官方游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     * @param int $system_type [下载系统类型] 0 window 1 苹果手机或ipa
     */
    public function official_down_file($game_id = 0, $sdk_version = 1, $user_id = 0)
    {
        $model = new GameModel();
        $map['relation_game_id'] = $game_id;
        $data = $model
            ->alias('g')
            ->field('g.sdk_version,g.game_name,g.id as game_id,add_game_address,ios_game_address,and_dow_address,ios_dow_address,file_url,plist_url,ios_dow_plist,down_port,platform_id')
            ->join(['tab_game_source' => 'gs'], 'gs.game_id = g.id', 'left')
            ->where($map)
            ->order('sdk_version asc')
            ->select();
        if (empty($data)) $this->error('游戏不存在');
        $data = $data->toArray();
        $first_data = reset($data);
        $end_data = end($data);
        if (empty($first_data) && $sdk_version == 1) {
            $this->error('暂无安卓原包！');
        }

        if (empty($end_data) && $sdk_version == 2) {
            $this->error('暂无苹果原包！');
        }
        if ($sdk_version == 1 && is_weixin()) {

            $weixinHtml = $this->fetch('weixin_tishi');
            exit($weixinHtml);
//            exit("<h1 style='text-align:center'>请使用外部浏览器下载</h1>");
        }
        if ($sdk_version == 2 && is_weixin()) {
            $weixinHtml = $this->fetch('weixin_tishi');
            exit($weixinHtml);
//            exit("<h1 style='text-align:center'>请使用Safari浏览器下载</h1>");
        }
        $model->where('id', $game_id)->setInc('dow_num');

        switch ($sdk_version) {
            case 1:
                $this->add_down_stat($first_data['game_id'], $user_id);
                if ($first_data['down_port'] == 1) {
                    if (varify_url(cmf_get_file_download_url($first_data['and_dow_address']))) {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . cmf_get_file_download_url($first_data['and_dow_address']));
                        exit();
                    } else {
                        $this->error('原包地址错误！');
                    }
                } else {
                    if (varify_url($first_data['add_game_address'])) {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . $first_data['add_game_address']);
                        exit();
                    } else {
                        $this->error('原包不存在！');
                    }
                }
                break;
            default:
                $this->add_down_stat($end_data['game_id'], $user_id);
                if ($end_data['down_port'] == 1) {
                    if (varify_url(cmf_get_file_download_url($end_data['ios_dow_address']))) {
                        if (cmf_is_mobile()) {
                            Header("HTTP/1.1 303 See Other");
                            Header("Location: " . "itms-services://?action=download-manifest&url=https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $end_data['plist_url']);
                            exit();
                        } else {
                            Header("HTTP/1.1 303 See Other");
                            Header("Location:" .cmf_get_file_download_url($end_data['ios_dow_address']));
                            exit();
                        }
                    } else {
                        $this->error('原包地址错误！');
                    }
                } else {
                    if (varify_url($end_data['ios_game_address'])) {

                        //超级签拼接版本号信息
                        if ($end_data['down_port'] == 3 && $end_data['platform_id'] == 0) {
                            $info['MCHPromoteID'] = (string)'0';
                            $info['XiguSuperSignVersion'] = (string)super_sign_version($end_data['game_id']);
                            $end_data['ios_game_address'] = $end_data['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
                        }

                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . $end_data['ios_game_address']);
                        exit();
                    } else {
                        $this->error('下载地址未设置！');
                    }
                }
                break;
        }
    }

    /**
     *官方游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     * @param int $system_type [下载系统类型] 0 window 1 苹果手机或ipa
     */
    public function official_weiduan_down_file($game_id = 0, $sdk_version = 1, $user_id = 0)
    {
        $model = new GameModel();
        $map['relation_game_id'] = $game_id;
        $data = $model
                ->field('sdk_version,game_name,id as game_id,add_game_address,ios_game_address,and_dow_address,ios_dow_address,ios_dow_plist')
                ->where($map)
                ->find();
        if (empty($data)) $this->error('游戏不存在');
        $data = $data->toArray();
        if (is_weixin()) {
            exit("<h1>请使用安卓浏览器下载</h1>");
        }
        switch ($sdk_version) {
            case 1:
                if ($data['and_dow_address'] && varify_url(cmf_get_file_download_url($data['and_dow_address']))) {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . cmf_get_file_download_url($data['and_dow_address']));
                    exit();
                }else if (varify_url($data['add_game_address'])) {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . $data['add_game_address']);
                    exit();
                } else {
                    $this->error('原包不存在！');
                }
                break;
            case 2:
                if ($data['ios_dow_address'] && varify_url(cmf_get_file_download_url($data['ios_dow_address']))) {
                    if (cmf_is_mobile()) {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . "itms-services://?action=download-manifest&url=https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['ios_dow_plist']);
                        exit();
                    } else {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . cmf_get_file_download_url($data['ios_dow_address']));
                        exit();
                    }
                }elseif ($data['ios_game_address']) {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . $data['ios_game_address']);
                    exit();
                } else {
                    $this->error('下载地址未设置！');
                }
                break;
            default:
                $this->error('下载地址未设置！');
        }
    }

    /**
     *推广游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $promote_id [渠道id]
     * @param int $system_type [系统环境] 1 win  2 iphone 或 iPad
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     */
    public function promote_down_file($game_id = 0, $promote_id = 0, $sdk_version = 0, $user_id = 0)
    {
        //不可推广游戏
        $promote = get_promote_entity($promote_id);
        if ($promote['game_ids']) {
            $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
        }
        $applyModel = new PromoteapplyModel();
        $map['status'] = 1;
        $map['enable_status'] = 1;
        $map['game_status'] = 1;
        $map['pack_type'] = ['neq', 2];
        $map['promote_id'] = $promote_id;
        $data = $applyModel
            ->alias('a')
            ->join(['tab_game' => 'g'], "g.id=a.game_id and relation_game_id = $game_id and a.sdk_version=$sdk_version", 'left')
            ->field('game_id,g.game_name,promote_id,relation_game_id,pack_url,plist_url,status,enable_status,a.sdk_version,is_upload,pack_type')
            ->where($map)
            ->find();
        if (empty($data)) {
            $this->official_down_file($game_id, $sdk_version, $user_id);
        } else {
            $model = new GameModel();
            $model->where('id', $data['game_id'])->setInc('dow_num');
            $this->add_down_stat($data['game_id'], $user_id);
            $pack_url = $data['pack_url'];
            if ($pack_url) {
                if ($sdk_version == 1) {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . promote_game_get_file_download_url($pack_url, $data['is_upload']));
                    exit();
                } else {
                    //排除超级签，按照安卓下载方式
                    if (cmf_is_mobile() && $data['pack_type'] != 4) {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . "itms-services://?action=download-manifest&url=https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['plist_url']);
                        exit();
                    } else {
                        Header("HTTP/1.1 303 See Other");
                        Header("Location: " . promote_game_get_file_download_url($pack_url, $data['is_upload']));
                        exit();
                    }
                }
            } else {
                $this->error('原包地址不存在');
            }
        }
    }

    /**
     *推广游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $promote_id [渠道id]
     * @param int $system_type [系统环境] 1 win  2 iphone 或 iPad
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     */
    public function promote_weiduan_down_file($game_id = 0, $promote_id = 0, $sdk_version = 0, $user_id = 0)
    {
        //不可推广游戏
        $promote = get_promote_entity($promote_id);
        if ($promote['game_ids']) {
            $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
        }
        $applyModel = new PromoteapplyModel();
        $map['status'] = 1;
        if($sdk_version == 1){
            $map['and_status'] = 1;
        }else{
            $map['ios_status'] = 1;
        }
        $map['promote_id'] = $promote_id;
		$map['game_id'] = $game_id;
        $data = $applyModel
                ->alias('a')
                ->join(['tab_game' => 'g'], "g.id=a.game_id and relation_game_id = $game_id and a.sdk_version=$sdk_version", 'left')
                ->field('game_id,g.game_name,promote_id,relation_game_id,plist_url,status,enable_status,a.sdk_version,is_upload,and_url,ios_url,ios_status,and_status,and_upload,ios_upload')
                ->where($map)
                ->find();
        if (empty($data)) {
           return false;
        }
        if($sdk_version == 1){
            $pack_url = $data['and_url'];
        }else{
            $pack_url = $data['ios_url'];
        }
        if ($pack_url) {
            if ($sdk_version == 1) {
                Header("HTTP/1.1 303 See Other");
                Header("Location: " . promote_game_get_file_download_url($pack_url, $data['and_upload']));
                exit();
            } else {
                if (cmf_is_mobile()) {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . "itms-services://?action=download-manifest&url=https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['plist_url']);
                    exit();
                } else {
                    Header("HTTP/1.1 303 See Other");
                    Header("Location: " . promote_game_get_file_download_url($pack_url, $data['ios_upload']));
                    exit();
                }
            }
        } else {
            $this->error('原包地址不存在');
        }
    }

    /**
     *游戏下载统计
     */
    public function add_down_stat($game_id = 0, $user_id = 0)
    {
        if ($user_id) {
            $result = Db::table('tab_game_down_record')
                ->field('id')
                ->where('game_id', $game_id)
                ->where('user_id', $user_id)
                ->find();
            if (empty($result)) {
                $save['game_id'] = $game_id;
                $save['user_id'] = $user_id;
                $save['sdk_version'] = get_game_entity($game_id,'sdk_version')['sdk_version'];
                $save['create_time'] = time();
                Db::table('tab_game_down_record')->insert($save);
            } else {
                $save['create_time'] = time();
                Db::table('tab_game_down_record')->where('id', $result['id'])->update($save);
            }
        }
    }

    /**
     * [统一调用生成excel类]
     * @param $expTitle
     * @param $expCellName
     * @param $expTableData
     * @author 郭家屯[gjt]
     */
    public function exportExcel($expTitle, $expCellName, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        //        $fileName = session('user_auth.username').date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $fileName = $expTitle;
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean();//清除缓冲区,避免乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }


}
