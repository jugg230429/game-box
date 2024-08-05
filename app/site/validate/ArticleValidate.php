<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\validate;

use think\Validate;

class ArticleValidate extends Validate
{
    protected $rule = [
        'categories' => 'require',
        'post_title' => 'require',
        'post_excerpt' => 'require',
        //'game_id' => 'require',
//        'thumbnail' => 'require',
    ];
    protected $message = [
        'categories.require' => '请指定文章分类！',
        'post_title.require' => '文章标题不能为空！',
        'post_excerpt.require' => '文章描述不能为空！',
        //'game_id.require' => '游戏名称不能为空',
//        'thumbnail.require' => '封面图不能为空',
    ];

    protected $scene = [
        'wendang' => ['categories,post_title,post_excerpt'],
    ];
}
