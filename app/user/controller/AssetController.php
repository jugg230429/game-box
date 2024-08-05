<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\facade\View;

/**
 * 附件上传控制器
 * Class Asset
 * @package app\asset\controller
 */
class AssetController extends AdminBaseController
{
    public function initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId = cmf_get_current_user_id();
        $promoteId = is_p_login();
        $issue_id = is_issue_login();
        if (empty($adminId) && empty($userId) && empty($promoteId) && empty($issue_id)) {
            $this->error("非法上传！");
        }
    }

    /**
     * webuploader 上传
     */
    public function webuploader()
    {
        if ($this->request->isPost()) {
            //判断原包是否需要上传oss
            $local = $this->request->param('local/d', 0);

            $uploader = new Upload();

            $result = $uploader->upload($local);

            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                $this->success("上传成功!", '', $result);
            }

        } else {
            $uploadSetting = cmf_get_upload_setting();

            $arrFileTypes = [
                'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
                'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
                'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
                'file' => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']]
            ];

            $arrData = $this->request->param();
            $arrData["allower_button"] = '2,3';//仅支持上传文件按钮
            //type为1只保留文件上传
            if ($arrData["allower_button"]) {
                $type = explode(',', $arrData["allower_button"]);
                foreach ($type as $key => $v) {
                    $this->assign('munu' . $v, $v);
                }
            }
            //获取是否为原包上传
            if ($arrData['retain_local'] == 1) {
                $this->assign('local', 1);
            } else {
                $this->assign('local', 0);
            }
            if (empty($arrData["filetype"])) {
                $arrData["filetype"] = "image";
            }

            $fileType = $arrData["filetype"];

            if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
                $extensions = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
                $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
            } else {
                $this->error('上传文件类型配置错误！');
            }


            View::share('filetype', $arrData["filetype"]);
            View::share('extensions', $extensions);
            View::share('upload_max_filesize', $fileTypeUploadMaxFileSize * 1024);
            View::share('upload_max_filesize_mb', intval($fileTypeUploadMaxFileSize / 1024));
            $maxFiles = intval($uploadSetting['max_files']);
            if($arrData['max']) {$maxFiles = $arrData['max'];}
            $maxFiles = empty($maxFiles) ? 20 : $maxFiles;
            $chunkSize = intval($uploadSetting['chunk_size']);
            $chunkSize = empty($chunkSize) ? 512 : $chunkSize;
            View::share('max_files', $arrData["multi"] ? $maxFiles : 1);
            View::share('chunk_size', $chunkSize); //// 单位KB
            View::share('multi', $arrData["multi"]);
            View::share('app', $arrData["app"]);

            $content = hook_one('fetch_upload_view');
            $tabs = ['local', 'url', 'cloud'];

            $tab = !empty($arrData['tab']) && in_array($arrData['tab'], $tabs) ? $arrData['tab'] : 'local';

            if (!empty($content)) {
                $this->assign('has_cloud_storage', true);
            }

            if (!empty($content) && $tab == 'cloud') {
                return $content;
            }

            $tab = $tab == 'cloud' ? 'local' : $tab;

            $this->assign('tab', $tab);
            return $this->fetch(":webuploader");

        }
    }

}
