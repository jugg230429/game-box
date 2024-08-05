<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/04
 * Time: 14:03
 */

namespace app\media\controller;

use app\site\service\PostService;
use app\site\model\PortalPostModel;
use think\Db;

class DiscountController extends BaseController
{
    public function index()
    {
        return $this->fetch();
    }

}