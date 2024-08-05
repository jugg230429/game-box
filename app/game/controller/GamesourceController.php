<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\common\logic\PackResultLogic;
use app\common\logic\V2SignPackLogic;
use app\game\model\GamesourceModel;
use app\common\controller\BaseController;
use app\game\model\GameModel;
use cmf\controller\AdminBaseController;
use cmf\lib\Storage;
use think\Request;
use think\Db;

class GamesourceController extends AdminBaseController
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

    /**
     * [游戏原包列表]
     * @author 郭家屯[gjt]
     */
    public function lists()
    {
        if(PERMI == 2){
            return $this->redirect('hlists');
        }
        $model = new GamesourceModel();
        $base = new BaseController();
        $game_id = $this->request->param('game_id');
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $map['sdk_version'] = 0;
        $file_type = $this->request->param('file_type');
        if ($file_type != '') {
            $map['file_type'] = $file_type;
        }
        $data = $base->data_list($model, $map, $exend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [H5游戏原包列表]
     * @author 郭家屯[gjt]
     */
    public function hlists()
    {
        $model = new GamesourceModel();
        $base = new BaseController();
        $game_id = $this->request->param('game_id');
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $map['sdk_version'] = 3;
        $file_type = $this->request->param('file_type');
        if ($file_type != '') {
            $map['file_type'] = $file_type;
        }
        $data = $base->data_list($model, $map, $exend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [新增游戏原包]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        $model = new GamesourceModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['game_id'])) {
                return json(['code' => -1, 'msg' => '游戏名称不能为空']);
            }
            if (empty($data['file_url'])) {
                return json(['code' => -1, 'msg' => '未上传游戏原包']);
            }
            if (empty($data['bao_name']) && $data['sdk_version'] == '2') {
                return json(['code' => -1, 'msg' => '包名不能为空']);
            }
            if (!empty($data['bao_name']) && preg_match("/([\x81-\xfe][\x40-\xfe])/", $data['bao_name'])) {
                return json(['code' => -1, 'msg' => '包名不能含有中文']);
            }
            if (empty($data['source_version'])) {
                return json(['code' => -1, 'msg' => '版本号不能为空']);
            }
            if(empty($data['source_name'])){
                return json(['code' => -1, 'msg' => '版本名不能为空']);
            }
            $game_source = $model->where('game_id', $data['game_id'])->find();
            if (!empty($game_source)) {
                return json(['code' => -1, 'msg' => '游戏已存在原包']);
            }
            $game = get_game_entity($data['game_id']);
            if ($game['game_status'] == 0) {
                return json(['code' => -1, 'msg' => '游戏已关闭']);
            }
            $file = Db::name('asset')->field('id,file_size,filename,file_path,suffix')->where('file_path', $data['file_url'])->find();
            if (empty($file)) {
                return json(['code' => -1, 'msg' => '文件不存在']);
            }
            if ($game['sdk_version'] == 1 && $file['suffix'] != 'apk') {
                return json(['code' => -1, 'msg' => '请选择文件后缀为apk的文件']);
            }
            if ($game['sdk_version'] == 2 && $file['suffix'] != 'ipa') {
                return json(['code' => -1, 'msg' => '请选择文件后缀为ipa的文件']);
            }
            $data['file_size'] = round($file['file_size'] / pow(1024, 2), 2) . "MB";
            $data['file_type'] = $game['sdk_version'];
            $data['game_name'] = $game['game_name'];
            $data['op_id'] = cmf_get_current_admin_id();
            $data['op_account'] = cmf_get_current_admin_name();
            $data['create_time'] = time();
            if ($file['suffix'] == 'apk') {
                //生成新包并删除原包
                $new_filename = "./public/upload/android/game_package" . $game['id'] . ".apk";
                copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                $data['file_url'] = "android/game_package" . $game['id'] . ".apk";
            }
            if ($file['suffix'] == 'ipa') {
                //生成新包并删除原包
                $new_filename = "./public/upload/ios/game_package" . $game['id'] . ".ipa";
                copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                //生成plist文件
                $data['plist_url'] = "sourceplist/" . $data['game_id'] . ".plist";
                $data['file_url'] = "ios/game_package" . $game['id'] . ".ipa";
                $pilst = new PlistController();
                $pilst->create_plist($data['game_id'], 0, $data['bao_name'], $data['file_url']);
            }
            // 原------------------------------------------START
            // //删除文件记录
            // Db::name('asset')->delete($file['id']);
            // $result = $model->insert($data);
            // 原----------------------------------------------END
            // insertGetId
            $result = $model->insertGetId($data);
            if ($result) {
                //原包打包
                $this->soure_pack($data['game_id'], './upload/' . $data['file_url'], $data['file_type'], $data['source_version']);
                //oss上传
                $tmp_flag=1;
                $upload_res = $this->uploadcloud($data['file_url'], './upload/' . $data['file_url'], $data['file_type'], $file['suffix'],$tmp_flag);
                // 如果上传失败 防止页面卡死在哪里
                $upload_code = $upload_res['code'] ?? 0;
                if($upload_code == -1){
                    // 上传失败
                    // 删除记录
                    $model->where(['id'=>$result])->delete();
                    $this->error('云存储配置参数有误!',url('lists'));
                    exit;
                }
                //删除文件记录
                Db::name('asset')->delete($file['id']);
                //更新游戏大小
                $this->update_game_size($data);
                write_action_log("新增游戏【" . $data['game_name'] . "】原包");
                $this->success('游戏原包上传成功', url('lists'));
            } else {
                return json(['code' => -1, 'msg' => '游戏原包上传失败']);
            }
        }
        return $this->fetch();
    }

    /**
     * [新增H5游戏原包]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function hadd()
    {
        $model = new GamesourceModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['game_id'])) {
                return json(['code' => -1, 'msg' => '游戏名称不能为空']);
            }
            if (empty($data['file_url'])) {
                return json(['code' => -1, 'msg' => '未上传游戏原包']);
            }
            if (empty($data['bao_name'])) {
                return json(['code' => -1, 'msg' => '包名不能为空']);
            }
            $game_source = $model->where('game_id', $data['game_id'])->where('file_type',$data['sdk_version'])->find();
            if (!empty($game_source)) {
                return json(['code' => -1, 'msg' => '游戏已存在原包']);
            }
            $game = get_game_entity($data['game_id']);
            if ($game['game_status'] == 0) {
                return json(['code' => -1, 'msg' => '游戏已关闭']);
            }
            $file = Db::name('asset')->field('id,file_size,filename,file_path,suffix')->where('file_path', $data['file_url'])->find();
            if (empty($file)) {
                return json(['code' => -1, 'msg' => '文件不存在']);
            }
            if ($data['sdk_version'] == 1 && $file['suffix'] != 'apk') {
                return json(['code' => -1, 'msg' => '请选择文件后缀为apk的文件']);
            }
            if ($data['sdk_version'] == 2 && $file['suffix'] != 'ipa') {
                return json(['code' => -1, 'msg' => '请选择文件后缀为ipa的文件']);
            }
            $data['file_size'] = round($file['file_size'] / pow(1024, 2), 2) . "MB";
            $data['file_type'] = $data['sdk_version'];
            $data['game_name'] = $game['game_name'];
            $data['op_id'] = cmf_get_current_admin_id();
            $data['op_account'] = cmf_get_current_admin_name();
            $data['create_time'] = time();
            $data['sdk_version'] = 3;
            if ($file['suffix'] == 'apk') {
                //生成新包并删除原包
                $new_filename = "./public/upload/android/game_package" . $game['id'] . ".apk";
                copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                $data['file_url'] = "android/game_package" . $game['id'] . ".apk";
            }
            if ($file['suffix'] == 'ipa') {
                //生成新包并删除原包
                $new_filename = "./public/upload/ios/game_package" . $game['id'] . ".ipa";
                copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                //生成plist文件
                $data['plist_url'] = "sourceplist/" . $data['game_id'] . ".plist";
                $data['file_url'] = "ios/game_package" . $game['id'] . ".ipa";
                $pilst = new PlistController();
                $pilst->create_plist($data['game_id'], 0, $data['bao_name'], $data['file_url']);
            }
            // $result = $model->insert($data);
            // insertGetId
            $result = $model->insertGetId($data);

            if ($result) {
                //原包打包
                $this->soure_pack($data['game_id'], './upload/' . $data['file_url'], $data['file_type'], $data['source_version']);
                //oss上传
                $tmp_flag=1;
                $upload_res = $this->uploadcloud($data['file_url'], './upload/' . $data['file_url'], $data['file_type'], $file['suffix'],$tmp_flag);
                // 如果上传失败 防止页面卡死在哪里
                $upload_code = $upload_res['code'] ?? 0;
                if($upload_code == -1){
                    // 上传失败
                    // 删除记录
                    $model->where(['id'=>$result])->delete();
                    $this->error('云存储配置参数有误!',url('lists'));
                    exit;
                }
                //删除文件记录
                Db::name('asset')->delete($file['id']);

                //更新游戏大小
                $this->update_game_size($data);
                //渠道包更新
                if (AUTH_PROMOTE == 1) {
                    $appmodel = new \app\promote\model\PromoteapplyModel();
                    $app_map['status'] = 1;
                    if($data['file_type'] == 1){
                        $app_map['and_status'] = 0;
                    }else{
                        $app_map['ios_status'] = 0;
                    }
                    $app_map['game_id'] = $game['id'];
                    $app_data = $appmodel->field('id')->where($app_map)->select();
                    if ($app_data) {
                        if($data['file_type'] == 1){
                            $appmodel->where($app_map)->setField('and_status', 2);
                        }else{
                            $appmodel->where($app_map)->setField('ios_status', 2);
                        }
                    }
                }
                write_action_log("新增游戏【" . $data['game_name'] . "】原包");
                $this->success('游戏原包上传成功', url('hlists'));
            } else {
                return json(['code' => -1, 'msg' => '游戏原包上传失败']);
            }
        }
        return $this->fetch();
    }


    /**
     * [非本地上传]
     * @author 郭家屯[gjt]
     */
    protected function uploadcloud($file_url = '', $upload_url = '', $filetype = '', $suffix = '',$tmp_flag=0)
    {
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'Local') { //  增加存储驱动
            $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
            session_write_close();
            $upload_res = $storage->upload($file_url, './upload/' . $file_url, $filetype,'',$tmp_flag);
            return $upload_res;
        }
    }

    /**
     *原包打包
     */
    protected function soure_pack($game_id = 0, $file_url = "", $file_type = 1, $source_version = 1)
    {
        $game_info = get_game_entity($game_id);
        $data = array(
            "game_id" => $game_info['id'],
            "game_name" => $game_info['game_name'],
            "game_appid" => $game_info['game_appid'],
            "promote_id" => 0,
            "promote_account" => "自然注册",
            'source_version' => $source_version,
        );
        if ($file_type == 1) {
            $lV2 = new V2SignPackLogic();
            $v2FileUrl = CMF_ROOT.'public/'.$file_url;
            $v2signRes = $lV2 -> pack($v2FileUrl, 0, $game_info['id'],$source_version);
            if ($v2signRes) {
                //V2打包完成
                return true;
            }
            $zip = new \ZipArchive;
            $res = $zip->open($file_url, \ZipArchive::CREATE);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (stripos($name, 'xigu~') !== false) {
                    $zip->deleteName($name);
                    break;
                }
            }
            $zip->addFromString('META-INF/mch.properties', json_encode($data));
            $zip->close();
        }
        if ($file_type == 2) {
            //自动获取文件夹名称
            $zip_open_game_source = zip_open($file_url);
            if ($zip_open_game_source) {
                while ($zip_entry = zip_read($zip_open_game_source)) {
                    if (preg_match("/.app/", zip_entry_name($zip_entry))) {
                        $ios_app = substr(zip_entry_name($zip_entry), 8) . "<br/>";
                    }
                    $new_ios_1 = explode("/", $ios_app);
                    if (preg_match("/MCHChannel/", zip_entry_name($zip_entry))) {
                        $deletedir[] = zip_entry_name($zip_entry);
                    }
                }
            }
            zip_close($zip_open_game_source);
            //$url_ver = "Payload/" . $new_ios_1[0] . "/_CodeSignature/MCHChannelID";
            $dirname = '0#&guanfang#&'.$source_version;
            $url_ver = "Payload/" . $new_ios_1[0] . "/MCHChannel/".$dirname;
            $zip = new \ZipArchive;
            $res = $zip->open($file_url, \ZipArchive::CREATE);
            if($deletedir){
                foreach ($deletedir as $key=>$v){
                    $zip->deleteName($v);
                }
            }
            $zip->addEmptyDir($url_ver);
            $zip->close();
        }

    }

    /**
     * [更新游戏下载链接和游戏大小]
     * @param null $param
     * @author 郭家屯[gjt]
     */
    protected function update_game_size($param = null)
    {
        $model = new GameModel();
        $map['id'] = $param['game_id'];
        $data['game_size'] = $param['file_size'];
        if ($param['file_type'] == 1) {
            $data['and_dow_address'] = $param['file_url'];
        } else {
            $data['ios_dow_address'] = $param['file_url'];
            $data['ios_dow_plist'] = $param['plist_url'];
        }
        $model->where($map)->update($data);
    }

    /**
     * 更新原包
     */
    public function edit()
    {
        $model = new GamesourceModel();

        if ($this->request->isPost()) {
            $data = $this->request->param();
            $source = $model->find($data['id']);
            $game = get_game_entity($source['game_id']);

            if ($game['game_status'] == 0) {
                return json(['code' => -1, 'msg' => '游戏已关闭']);
            }
            if (empty($data['file_url'])) {
                $save['remark'] = $data['remark'];
                $save['create_time'] = time();
                $result = $model->where('id', $data['id'])->update($save);
                if ($result) {
                    $this->success('更新成功', url('lists'));
                } else {
                    return json(['code' => -1, 'msg' => '更新失败']);
                }
            } else {
                if (empty($data['bao_name']) && $data['sdk_version'] == '2') {
                    return json(['code' => -1, 'msg' => '包名不能为空']);
                }
                if (empty($data['source_version'])) {
                    return json(['code' => -1, 'msg' => '版本号不能为空']);
                }
                if (empty($data['source_name'])) {
                    return json(['code' => -1, 'msg' => '版本名不能为空']);
                }
                $file = Db::name('asset')->field('id,file_size,filename,file_path,suffix')->where('file_path', $data['file_url'])->find();
                if (empty($file)) {
                    return json(['code' => -1, 'msg' => '文件不存在']);
                }
                if ($source['file_type'] == 1 && $file['suffix'] != 'apk') {
                    return json(['code' => -1, 'msg' => '请选择文件后缀为apk的文件']);
                }
                if ($source['file_type'] == 2 && $file['suffix'] != 'ipa') {
                    return json(['code' => -1, 'msg' => '请选择文件后缀为ipa的文件']);
                }

                $data['file_size'] = round($file['file_size'] / pow(1024, 2), 2) . "MB";
                $data['create_time'] = time();
                $data['file_type'] = $source['file_type'];
                $data['game_id'] = $source['game_id'];
                if ($file['suffix'] == 'apk') {
                    //生成新包并删除原包
                    $new_filename = "./public/upload/android/game_package" . $game['id'] . ".apk";
                    copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                    @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                    $data['file_url'] = "android/game_package" . $game['id'] . ".apk";
                }
                if ($file['suffix'] == 'ipa') {
                    //生成新包并删除原包
                    $new_filename = "./public/upload/ios/game_package" . $game['id'] . ".ipa";
                    copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                    @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                    //生成plist文件
                    $data['plist_url'] = "sourceplist/" . $game['id'] . ".plist";
                    $data['file_url'] = "ios/game_package" . $game['id'] . ".ipa";
                    $pilst = new PlistController();
                    $pilst->create_plist($game['id'], 0, $data['bao_name'], $data['file_url']);
                }
                //删除文件记录
                // 启动事务
                Db::startTrans();

                Db::name('asset')->delete($file['id']);
                // $result = $model->where('id', $data['id'])->update($data);
                $result = Db::table('tab_game_source')->where('id', $data['id'])->update($data);
                if ($result !== false) {
                    //原包打包
                    $this->soure_pack($game['id'], './upload/' . $data['file_url'], $source['file_type'], $data['source_version']);
                    //oss上传
                    $tmp_flag=1;
                    $upload_res = $this->uploadcloud($data['file_url'], './upload/' . $data['file_url'], $data['file_type'], $file['suffix'], $tmp_flag);
                    // 如果上传失败 防止页面卡死在哪里
                    $upload_code = $upload_res['code'] ?? 0;
                    if($upload_code == -1){
                        // 上传失败
                        // 回滚事务
                        Db::rollback();

                        $this->error('云存储配置参数有误!',url('lists'));
                        exit;
                    }
                    // 提交事务
                    Db::commit();

                    //更新游戏大小
                    $this->update_game_size($data);
                    //渠道包更新
                    if (AUTH_PROMOTE == 1) {
                        $appmodel = new \app\promote\model\PromoteapplyModel();
                        $app_map['status'] = 1;
                        $app_map['enable_status'] = 1;
                        $app_map['game_id'] = $game['id'];
                        $app_map['pack_type'] = ['neq', 2];
                        $app_data = $appmodel->where($app_map)->select();
                        if ($app_data) {
                            $appmodel->where($app_map)->setField('enable_status', 2);
                        }
                    }
                    write_action_log("更新游戏【" . $game['game_name'] . "】原包");
                    $this->success('更新成功', url('lists'));
                } else {
                    // 回滚事务
                    Db::rollback();

                    return json(['code' => -1, 'msg' => '更新失败', 'url' => url('gamesource/lists')]);
                }
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $gamesource = $model->find($id);
        if (!$gamesource) $this->error('游戏原包不存在');
        $this->assign('data', $gamesource);
        return $this->fetch();
    }

    /**
     * 更新原包
     */
    public function hedit()
    {
        $model = new GamesourceModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $source = $model->find($data['id']);
            $game = get_game_entity($source['game_id']);
            if ($game['game_status'] == 0) {
                return json(['code' => -1, 'msg' => '游戏已关闭']);
            }
            if (empty($data['file_url'])) {
                $save['remark'] = $data['remark'];
                $save['create_time'] = time();
                $result = $model->where('id', $data['id'])->update($save);
                if ($result) {
                    $this->success('更新成功', url('lists'));
                } else {
                    return json(['code' => -1, 'msg' => '更新失败']);
                }
            } else {
                if (empty($data['bao_name'])) {
                    return json(['code' => -1, 'msg' => '包名不能为空']);
                }
                $file = Db::name('asset')->field('id,file_size,filename,file_path,suffix')->where('file_path', $data['file_url'])->find();
                if (empty($file)) {
                    return json(['code' => -1, 'msg' => '文件不存在']);
                }
                if ($source['file_type'] == 1 && $file['suffix'] != 'apk') {
                    return json(['code' => -1, 'msg' => '请选择文件后缀为apk的文件']);
                }
                if ($source['file_type'] == 2 && $file['suffix'] != 'ipa') {
                    return json(['code' => -1, 'msg' => '请选择文件后缀为ipa的文件']);
                }
                $data['file_size'] = round($file['file_size'] / pow(1024, 2), 2) . "MB";
                $data['create_time'] = time();
                $data['file_type'] = $source['file_type'];
                $data['game_id'] = $source['game_id'];
                if ($file['suffix'] == 'apk') {
                    //生成新包并删除原包
                    $new_filename = "./public/upload/android/game_package" . $game['id'] . ".apk";
                    copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                    @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                    $data['file_url'] = "android/game_package" . $game['id'] . ".apk";
                }
                if ($file['suffix'] == 'ipa') {
                    //生成新包并删除原包
                    $new_filename = "./public/upload/ios/game_package" . $game['id'] . ".ipa";
                    copy(ROOT_PATH . './public/upload/' . $data['file_url'], ROOT_PATH . $new_filename);
                    @unlink(ROOT_PATH . './public/upload/' . $data['file_url']);
                    //生成plist文件
                    $data['plist_url'] = "sourceplist/" . $game['id'] . ".plist";
                    $data['file_url'] = "ios/game_package" . $game['id'] . ".ipa";
                    $pilst = new PlistController();
                    $pilst->create_plist($game['id'], 0, $data['bao_name'], $data['file_url']);
                }
                //删除文件记录
                // 启动事务
                Db::startTrans();

                Db::name('asset')->delete($file['id']);
                // $result = $model->where('id', $data['id'])->update($data);
                $result = Db::table('tab_game_source')->where('id', $data['id'])->update($data);
                Db::name('asset')->delete($file['id']);

                if ($result !== false) {
                    //原包打包
                    $this->soure_pack($game['id'], './upload/' . $data['file_url'], $source['file_type'], $data['source_version']);
                    //oss上传
                    $tmp_flag=1;
                    $upload_res = $this->uploadcloud($data['file_url'], './upload/' . $data['file_url'], $data['file_type'], $file['suffix'],$tmp_flag);
                    // 如果上传失败 防止页面卡死在哪里
                    $upload_code = $upload_res['code'] ?? 0;
                    if($upload_code == -1){
                        // 上传失败
                        // 回滚事务
                        Db::rollback();

                        $this->error('云存储配置参数有误!',url('lists'));
                        exit;
                    }
                    // 提交事务
                    Db::commit();

                    //更新游戏大小
                    $this->update_game_size($data);
                    //渠道包更新
                    if (AUTH_PROMOTE == 1) {
                        $appmodel = new \app\promote\model\PromoteapplyModel();
                        $app_map['status'] = 1;
                        $app_map['game_id'] = $game['id'];
                        $app_data = $appmodel->field('id')->where($app_map)->select();
                        if ($app_data) {
                            if($source['file_type'] == 1){
                                $appmodel->where($app_map)->setField('and_status', 2);
                            }else{
                                $appmodel->where($app_map)->setField('ios_status', 2);
                            }
                        }
                    }
                    write_action_log("更新游戏【" . $game['game_name'] . "】原包");
                    $this->success('更新成功', url('hlists'));
                } else {
                    // 回滚事务
                    Db::rollback();

                    return json(['code' => -1, 'msg' => '更新失败', 'url' => url('gamesource/hlists')]);
                }
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $gamesource = $model->find($id);
        if (!$gamesource) $this->error('游戏原包不存在');
        $this->assign('data', $gamesource->toArray());
        return $this->fetch();
    }
    /**
     * [删除原包]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        $sourcemodel = new GamesourceModel();
        $source = $sourcemodel->find($id);
        if (empty($source)) $this->error('原包不存在');
        $gamemodel = new GameModel();
        if($source['file_type'] == 1){
            $data['and_dow_address'] = '';
            $file_url = "android/game_package" . $source['game_id'] . ".apk";
        }else{
            $data['ios_dow_address'] = '';
            $data['ios_dow_plist'] = '';
            $file_url = "ios/game_package" . $source['game_id'] . ".ipa";
        }
        //$data['game_size'] = '0MB';
        $gamemodel->where('id', $source['game_id'])->update($data);
        if ($source['file_url']) {
            unlink('/upload/'.$source['file_url']);
        }
        if ($source['plist_url']) {
            unlink('/upload/'.$source['plist_url']);
        }
        $result = $sourcemodel->where('id', $id)->delete();
        if ($result) {
            $this->deletecloud($file_url);
            write_action_log("删除游戏【" . $source['game_name'] . "】原包");
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 批量删除原包
     */
    public function batch_del()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $sourcemodel = new GamesourceModel();
        $gamemodel = new GameModel();
        foreach ($ids as $k => $id){
            $source = $sourcemodel->find($id);
            if (empty($source)) $this->error($source['game_name'].'原包不存在');
            if($source['file_type'] == 1){
                $data['and_dow_address'] = '';
                $file_url = "android/game_package" . $source['game_id'] . ".apk";
            }else{
                $data['ios_dow_address'] = '';
                $data['ios_dow_plist'] = '';
                $file_url = "ios/game_package" . $source['game_id'] . ".ipa";
            }
            //$data['game_size'] = '0MB';
            $gamemodel->where('id', $source['game_id'])->update($data);
            if ($source['file_url']) {
                unlink('/upload/'.$source['file_url']);
            }
            if ($source['plist_url']) {
                unlink('/upload/'.$source['plist_url']);
            }
            $result = $sourcemodel->where('id', $id)->delete();
            if ($result) {
                $this->deletecloud($file_url);
                write_action_log("删除游戏【" . $source['game_name'] . "】原包");
            } else {
                $this->error($source['game_name'] .'删除失败！');
            }
        }
        $this->success('删除成功');
    }


    /**
     * [非本地上传]
     * @author 郭家屯[gjt]
     */
    protected function deletecloud($file_url = '')
    {
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'Local') { //  增加存储驱动
            $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
            session_write_close();
            $storage->delete_file($file_url);

        }
    }


    /**
     * @获取IOS包名
     *
     * @author: zsl
     * @since: 2021/7/17 11:10s
     */
    public function getPackName()
    {
        $param = $this -> request -> param();
        if (empty($param['pack_url'])) {
            $this -> error('文件不存在，请重新上传原包');
        }
        $lPackResult = new PackResultLogic();
        $data = [];
        $data['file_path'] = CMF_ROOT . 'public/upload/' . $param['pack_url'];
        $pathInfo = pathInfo($data['file_path']);
        if ($pathInfo['extension'] == 'ipa') {
            //获取info.plist文件信息
            $result = $lPackResult -> getInfoPlist($data);
            $return = [];
            $return['bao_name'] = $result['CFBundleIdentifier'];
        } else {
            //获取安卓包名
            $result = $lPackResult -> getApkInfo($data);
            $return = [];
            $return['bao_name'] = $result['package_name'];

        }
        if (false === $result) {
            $this -> error('解析失败');
        }
        $this -> success('获取成功', '', $return);
    }

}
