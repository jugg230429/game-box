<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\game\model\ServerModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\game\validate\ServerValidate;
use think\Request;
use think\Db;

class ServerController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    public function lists()
    {
        $base = new BaseController();
        $model = new ServerModel();
        //添加搜索条件
        $data = $this->request->param();
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }
        $sdk_version = $data['sdk_version'];
        if ($sdk_version) {
            $map['sdk_version'] = $sdk_version;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        if ($start_time && $end_time) {
            $map['start_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['start_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['start_time'] = ['egt', strtotime($start_time)];
        }
        //查询字段
        $exend['field'] = 'id,game_id,server_name,status,start_time,sdk_version';
        //开服时间倒序
        $exend['order'] = 'start_time desc';
        $data = $base->data_list($model, $map, $exend)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [添加区服]
     * @author 郭家屯[gjt]
     */
    protected function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new ServerValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $game = get_game_entity($data['game_id'],'game_name,sdk_version');
            if (empty($game)) $this->error('游戏不存在');
            $model = new ServerModel();
            $data['create_time'] = time();
            $data['start_time'] = strtotime($data['start_time']);
            $data['sdk_version'] = $game['sdk_version'];
            $result = $model->field(true)->insert($data);
            if ($result) {
                write_action_log("新增游戏【" . $game['game_name'] . "】区服");
                $this->success('游戏区服添加成功', url('lists'));
            } else {
                $this->error('游戏区服添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑区服]
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new ServerModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new ServerValidate();
            if (!$validate->scene('edit')->check($data)) {
                $this->error($validate->getError());
            }
            $data['start_time'] = strtotime($data['start_time']);
            $result = $model->where('id', $data['id'])->update($data);
            if ($result!==false) {
                $server = $model->field('game_id')->find($data['id']);
                write_action_log("编辑游戏【" . get_game_name($server['game_id']) . "】区服");
                $this->success('游戏区服编辑成功', url('lists'));
            } else {
                $this->error('游戏区服编辑失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        if (empty($data)) $this->error('区服不存在');
        $this->assign('data', $data);
        return $this->fetch();
    }


    /**
     * [设置开启状态]
     * @return string
     * @author 郭家屯[gjt]
     */
    public function setstatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new ServerModel();
        $status = $this->request->param('status', 0, 'intval');
        $newstatus = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $newstatus);
        if ($result) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * [删除区服]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new ServerModel();
        if (count($ids) == 1) {
            $server = $model->find($ids);
        }
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            if (count($ids) > 1) {
                write_action_log("批量删除区服");
            } else {

                write_action_log("删除游戏【" . get_game_name($server['game_id']) . "】区服");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * [批量添加区服]
     * @author 郭家屯[gjt]
     */
    public function batch()
    {
        if ($this->request->isPost()) {
            $type = $this->request->param('type', 0, 'intval');
            switch ($type) {
                case 1:
                    $this->add();
                    break;
                case 2:
                    $this->batchExcel();
                    break;
                case 3:
                    $this->batchImport();
                    break;
            }
        }
        return $this->fetch();
    }

    /**
     * [批量导入]
     * @author 郭家屯[gjt]
     */
    protected function batchImport()
    {
        $content = $this->request->param('content');
        $content = str_replace(array("\r\n", "\r", "\n"), "", $content);
        $content = array_filter(explode(';', $content));
        $num = count($content);
        if ($num < 1) {
            $this->error('请添加区服数据');
        }
        if ($num > 100) {
            $this->error('区服数量过多，最多只允许添加100个！');
        }
        $server_list = array();
        $check_list = array();
        foreach ($content as $key => $value) {
            $server = array();
            $arr = explode(',', $value);
            $string = '';
            foreach ($arr as $k => $v) {
                $att = explode('=', $v);
                if (empty($att[1])) $this->error('参数不能为空');
                if ($att[0] == 'time') {
                    $server['start_time'] = strtotime($att[1]);
                } else {
                    $server[$att[0]] = $att[1];
                }
                if ($att[0] == 'game_id' || $att[0] == 'server_name') {
                    $string .= $att[1];
                }
            }
            $game = get_game_entity($server['game_id']);
            if(!$game){
                $this->error('游戏不存在');
            }
            $server['create_time'] = time();
            $server['status'] = 1;
            $server['sdk_version'] = $game['sdk_version'];
            $server_list[] = $server;
            $check_list[] = $string;
        }
        if (count($check_list) != count(array_unique($check_list))) $this->error('区服名称重复');
        $validate = new ServerValidate();
        foreach ($server_list as $key => $vo) {
            if (!$validate->check($vo)) {
                $this->error($validate->getError());
            }
        }
        $model = new ServerModel();
        $result = $model->insertAll($server_list, true);
        if ($result) {
            write_action_log("批量添加游戏区服");
            $this->success('批量导入区服成功', url('lists'));
        } else {
            $this->error('批量导入区服失败');
        }
    }

    /**
     * [get_game_server 获取游戏区服]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function get_game_server()
    {
        $game_id = $this->request->param('game_id');
        if (empty($game_id)) {
            return json_encode(['code' => 1, 'msg' => '确少game_id', 'data' => []]);
        } else {
            $server = new ServerModel;
            $base = new BaseController;
            $map['game_id'] = $game_id;
            $extend['field'] = 'id,server_name';
            $base_data = $base->data_list_select($server, $map, $extend);
            echo json_encode(['code' => 1, 'msg' => '', 'data' => $base_data]);
        }
    }

    /**
     * [excel批量导入]
     * @author 郭家屯[gjt]
     */
    protected function batchExcel()
    {
        header("Content-Type:text/html;charset=utf-8");
        //保存文件
        $file = request()->file('server_list');
        if (!$file) $this->error('请上传文件');
        $info = $file->validate(['size' => 3145728, 'ext' => 'xls, xlsx'])->move('../public/upload/excel');
        if (!$info) {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
        try {
            // 判断文件是什么格式
            $type = pathinfo($info->getFilename());
            $type = strtolower($type["extension"]);
            ini_set('max_execution_time', '0');
            // 判断使用哪种格式
            switch ($type) {
                case 'xlsx':
                    $objReader = new \PHPExcel_Reader_Excel2007();
                    break;
                case 'xls':
                    $objReader = new \PHPExcel_Reader_Excel5();
                    //$objReader = \PHPExcel_IOFactory::createReader($type);
                    break;
            }
            $objPHPExcel = $objReader->load($info->getPathname());
            $sheet = $objPHPExcel->getSheet(0);
            // 取得总行数
            $highestRow = $sheet->getHighestRow();
            // 取得总列数
            $highestColumn = $sheet->getHighestColumn();
            //循环读取excel文件,读取一条,插入一条
            $data = array();
            //从第一行开始读取数据
            //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
            for ($currentRow = 2; $currentRow <= $highestRow; $currentRow++) {
                //从哪列开始，A表示第一列
                for ($currentColumn = 'A'; $currentColumn <= $highestColumn; $currentColumn++) {
                    //数据坐标
                    $address = $currentColumn . $currentRow;

                    $cell = $objPHPExcel->getActiveSheet()->getCell($address);
                    //读取到的数据，保存到数组$arr中
                    if ($currentColumn == "B") {
                        $currentvalue = $sheet->getCell($address)->getValue();
                        if (empty($currentvalue)) {
                            $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
                            unset($info);
                            unlink($str1);
                            throw new \Exception($address . '列 区服名称为空');
                        }
                        $data[$currentRow][$currentColumn] = $sheet->getCell($address)->getValue();
                    } elseif ($currentColumn == "C") {
                        $currentvalue = $sheet->getCell($address)->getValue();
                        if (empty($currentvalue)) {
                            $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
                            unset($info);
                            unlink($str1);
                            throw new \Exception($address . '列 开服时间为空');
                        }
                        if ($cell->getDataType() == \PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                            $cellstyleformat = $cell->getStyle($address)->getNumberFormat();
                            $formatcode = $cellstyleformat->getFormatCode();
                            if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
                                $date = \PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($address)->getValue());
                                $datetime = new \DateTime("@$date"); //DateTime类的bug，加入@可以将Unix时间戳作为参数传入
                                $datetime->setTimezone(new \DateTimeZone('GMT'));
                                $data[$currentRow][$currentColumn] = $datetime->format("Y-m-d H:i:s");
                            } else {
                                $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
                                unset($info);
                                unlink($str1);
                                throw new \Exception($address . "列 时间格式输入错误");
                            }
                        } else {
                            $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
                            unset($info);
                            unlink($str1);
                            throw new \Exception($address . "列 时间格式输入错误");
                        }
                    } else {
                        $currentvalue = $sheet->getCell($address)->getValue();
                        if (empty($currentvalue)) {
                            $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
                            unset($info);
                            unlink($str1);
                            throw new \Exception($address . '列 数据错误，请重新上传');
                        }
                        $data[$currentRow][$currentColumn] = $sheet->getCell($address)->getValue();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        //删除文件
        $str1 = ROOT_PATH . str_replace('\\', '/', str_replace('..', '', $info->getPathname()));
        unset($info);
        unlink($str1);
        //处理数组并写入数据库
        $server_list = array();
        $check_list = array();
        foreach ($data as $key => $vo) {
            $game = get_game_entity($vo['A']);
            if(!$game){
                $this->error('游戏不存在');
            }
            $server_list[$key]['game_id'] = $vo['A'];
            $server_list[$key]['server_name'] = $vo['B'];
            $server_list[$key]['start_time'] = strtotime($vo['C']);
            $server_list[$key]['server_num'] = $vo['D'];
            $server_list[$key]['create_time'] = time();
            $server_list[$key]['status'] = 1;
            $server_list[$key]['sdk_version'] = get_game_entity($vo['A'])['sdk_version'];
            $check_list[] = $vo['A'] . $vo['B'];
        }
        if (count($check_list) != count(array_unique($check_list))) $this->error('区服重复，添加失败');
        $validate = new ServerValidate();
        foreach ($server_list as $key => $vo) {
            if (!$validate->check($vo)) {
                $this->error('A'.$key . '行' . $validate->getError());
            }
        }
        $model = new ServerModel();
        $result = $model->insertAll($server_list, true);
        if ($result) {
            write_action_log("批量添加游戏区服");
            $this->success('批量导入区服成功', url('lists'));
        } else {
            $this->error('批量导入区服失败');
        }

    }
}