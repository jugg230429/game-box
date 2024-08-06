-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2024-04-10 20:11:04
-- 服务器版本： 5.7.44-log
-- PHP 版本： 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `db_test`
--

-- --------------------------------------------------------

--
-- 表的结构 `sys_admin_menu`
--

CREATE TABLE `sys_admin_menu` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父菜单id',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '菜单类型;1:有界面可访问菜单,2:无界面可访问菜单,0:只作为菜单',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态;1:显示,0:不显示',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `app` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '应用名',
  `controller` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '控制器名',
  `action` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '操作名称',
  `param` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '额外参数',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '菜单图标',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台菜单表';

--
-- 转存表中的数据 `sys_admin_menu`
--

INSERT INTO `sys_admin_menu` (`id`, `parent_id`, `type`, `status`, `list_order`, `app`, `controller`, `action`, `param`, `name`, `icon`, `remark`) VALUES
(1, 6, 0, 0, 1000, 'admin', 'Plugin', 'default', '', '插件中心', 'cloud', '插件中心'),
(2, 1, 1, 1, 10000, 'admin', 'Hook', 'index', '', '钩子管理', '', '钩子管理'),
(3, 2, 1, 0, 10000, 'admin', 'Hook', 'plugins', '', '钩子插件管理', '', '钩子插件管理'),
(4, 2, 2, 0, 10000, 'admin', 'Hook', 'pluginListOrder', '', '钩子插件排序', '', '钩子插件排序'),
(5, 2, 1, 0, 10000, 'admin', 'Hook', 'sync', '', '同步钩子', '', '同步钩子'),
(6, 0, 0, 1, 60, 'admin', 'Setting', 'default', '', '系统设置', 'user-circle', '系统设置入口'),
(7, 165, 1, 0, 6, 'admin', 'Link', 'index', '', '友情链接', '', '友情链接管理'),
(8, 7, 1, 0, 10000, 'admin', 'Link', 'add', '', '添加友情链接', '', '添加友情链接'),
(9, 7, 2, 0, 10000, 'admin', 'Link', 'addPost', '', '添加友情链接提交保存', '', '添加友情链接提交保存'),
(10, 7, 1, 0, 10000, 'admin', 'Link', 'edit', '', '编辑友情链接', '', '编辑友情链接'),
(11, 7, 2, 0, 10000, 'admin', 'Link', 'editPost', '', '编辑友情链接提交保存', '', '编辑友情链接提交保存'),
(12, 7, 2, 0, 10000, 'admin', 'Link', 'delete', '', '删除友情链接', '', '删除友情链接'),
(13, 7, 2, 0, 10000, 'admin', 'Link', 'listOrder', '', '友情链接排序', '', '友情链接排序'),
(14, 7, 2, 0, 10000, 'admin', 'Link', 'toggle', '', '友情链接显示隐藏', '', '友情链接显示隐藏'),
(15, 6, 1, 1, 10, 'admin', 'Mailer', 'index', '', '邮箱配置', '', '邮箱配置'),
(16, 15, 2, 0, 10000, 'admin', 'Mailer', 'indexPost', '', '邮箱配置提交保存', '', '邮箱配置提交保存'),
(17, 15, 1, 0, 10000, 'admin', 'Mailer', 'template', '', '邮件模板', '', '邮件模板'),
(18, 15, 2, 0, 10000, 'admin', 'Mailer', 'templatePost', '', '邮件模板提交', '', '邮件模板提交'),
(19, 15, 1, 0, 10000, 'admin', 'Mailer', 'test', '', '邮件发送测试', '', '邮件发送测试'),
(20, 6, 1, 0, 10000, 'admin', 'Menu', 'index', '', '后台菜单', '', '后台菜单管理'),
(21, 20, 1, 0, 10000, 'admin', 'Menu', 'lists', '', '所有菜单', '', '后台所有菜单列表'),
(22, 20, 1, 0, 10000, 'admin', 'Menu', 'add', '', '后台菜单添加', '', '后台菜单添加'),
(23, 20, 2, 0, 10000, 'admin', 'Menu', 'addPost', '', '后台菜单添加提交保存', '', '后台菜单添加提交保存'),
(24, 20, 1, 0, 10000, 'admin', 'Menu', 'edit', '', '后台菜单编辑', '', '后台菜单编辑'),
(25, 20, 2, 0, 10000, 'admin', 'Menu', 'editPost', '', '后台菜单编辑提交保存', '', '后台菜单编辑提交保存'),
(26, 20, 2, 0, 10000, 'admin', 'Menu', 'delete', '', '后台菜单删除', '', '后台菜单删除'),
(27, 20, 2, 0, 10000, 'admin', 'Menu', 'listOrder', '', '后台菜单排序', '', '后台菜单排序'),
(28, 20, 1, 0, 10000, 'admin', 'Menu', 'getActions', '', '导入新后台菜单', '', '导入新后台菜单'),
(29, 165, 1, 0, 30, 'admin', 'Nav', 'index', '', '导航管理', '', '导航管理'),
(30, 29, 1, 0, 10000, 'admin', 'Nav', 'add', '', '添加导航', '', '添加导航'),
(31, 29, 2, 0, 10000, 'admin', 'Nav', 'addPost', '', '添加导航提交保存', '', '添加导航提交保存'),
(32, 29, 1, 0, 10000, 'admin', 'Nav', 'edit', '', '编辑导航', '', '编辑导航'),
(33, 29, 2, 0, 10000, 'admin', 'Nav', 'editPost', '', '编辑导航提交保存', '', '编辑导航提交保存'),
(34, 29, 2, 0, 10000, 'admin', 'Nav', 'delete', '', '删除导航', '', '删除导航'),
(35, 29, 1, 0, 10000, 'admin', 'NavMenu', 'index', '', '导航菜单', '', '导航菜单'),
(36, 35, 1, 0, 10000, 'admin', 'NavMenu', 'add', '', '添加导航菜单', '', '添加导航菜单'),
(37, 35, 2, 0, 10000, 'admin', 'NavMenu', 'addPost', '', '添加导航菜单提交保存', '', '添加导航菜单提交保存'),
(38, 35, 1, 0, 10000, 'admin', 'NavMenu', 'edit', '', '编辑导航菜单', '', '编辑导航菜单'),
(39, 35, 2, 0, 10000, 'admin', 'NavMenu', 'editPost', '', '编辑导航菜单提交保存', '', '编辑导航菜单提交保存'),
(40, 35, 2, 0, 10000, 'admin', 'NavMenu', 'delete', '', '删除导航菜单', '', '删除导航菜单'),
(41, 35, 2, 0, 10000, 'admin', 'NavMenu', 'listOrder', '', '导航菜单排序', '', '导航菜单排序'),
(42, 1, 1, 1, 10000, 'admin', 'Plugin', 'index', '', '插件列表', '', '插件列表'),
(43, 42, 2, 0, 10000, 'admin', 'Plugin', 'toggle', '', '插件启用禁用', '', '插件启用禁用'),
(44, 42, 1, 0, 10000, 'admin', 'Plugin', 'setting', '', '插件设置', '', '插件设置'),
(45, 42, 2, 0, 10000, 'admin', 'Plugin', 'settingPost', '', '插件设置提交', '', '插件设置提交'),
(46, 42, 2, 0, 10000, 'admin', 'Plugin', 'install', '', '插件安装', '', '插件安装'),
(47, 42, 2, 0, 10000, 'admin', 'Plugin', 'update', '', '插件更新', '', '插件更新'),
(48, 42, 2, 0, 10000, 'admin', 'Plugin', 'uninstall', '', '卸载插件', '', '卸载插件'),
(50, 6, 1, 1, 1, 'admin', 'Rbac', 'index', '', '角色权限', '', '角色管理'),
(51, 50, 1, 0, 10000, 'admin', 'Rbac', 'roleAdd', '', '添加角色', '', '添加角色'),
(52, 50, 2, 0, 10000, 'admin', 'Rbac', 'roleAddPost', '', '添加角色提交', '', '添加角色提交'),
(53, 50, 1, 0, 10000, 'admin', 'Rbac', 'roleEdit', '', '编辑角色', '', '编辑角色'),
(54, 50, 2, 0, 10000, 'admin', 'Rbac', 'roleEditPost', '', '编辑角色提交', '', '编辑角色提交'),
(55, 50, 2, 0, 10000, 'admin', 'Rbac', 'roleDelete', '', '删除角色', '', '删除角色'),
(56, 50, 1, 0, 10000, 'admin', 'Rbac', 'authorize', '', '设置角色权限', '', '设置角色权限'),
(57, 50, 2, 0, 10000, 'admin', 'Rbac', 'authorizePost', '', '角色授权提交', '', '角色授权提交'),
(58, 0, 1, 0, 10000, 'admin', 'RecycleBin', 'index', '', '回收站', '', '回收站'),
(59, 58, 2, 0, 10000, 'admin', 'RecycleBin', 'restore', '', '回收站还原', '', '回收站还原'),
(60, 58, 2, 0, 10000, 'admin', 'RecycleBin', 'delete', '', '回收站彻底删除', '', '回收站彻底删除'),
(61, 165, 1, 0, 7, 'admin', 'Route', 'index', '', 'URL美化', '', 'URL规则管理'),
(62, 61, 1, 0, 10000, 'admin', 'Route', 'add', '', '添加路由规则', '', '添加路由规则'),
(63, 61, 2, 0, 10000, 'admin', 'Route', 'addPost', '', '添加路由规则提交', '', '添加路由规则提交'),
(64, 61, 1, 0, 10000, 'admin', 'Route', 'edit', '', '路由规则编辑', '', '路由规则编辑'),
(65, 61, 2, 0, 10000, 'admin', 'Route', 'editPost', '', '路由规则编辑提交', '', '路由规则编辑提交'),
(66, 61, 2, 0, 10000, 'admin', 'Route', 'delete', '', '路由规则删除', '', '路由规则删除'),
(67, 61, 2, 0, 10000, 'admin', 'Route', 'ban', '', '路由规则禁用', '', '路由规则禁用'),
(68, 61, 2, 0, 10000, 'admin', 'Route', 'open', '', '路由规则启用', '', '路由规则启用'),
(69, 61, 2, 0, 10000, 'admin', 'Route', 'listOrder', '', '路由规则排序', '', '路由规则排序'),
(70, 61, 1, 0, 10000, 'admin', 'Route', 'select', '', '选择URL', '', '选择URL'),
(71, 6, 1, 1, 5, 'admin', 'Setting', 'site', '', '网站信息', '', '网站信息'),
(72, 71, 2, 0, 10000, 'admin', 'Setting', 'sitePost', '', '网站信息设置提交', '', '网站信息设置提交'),
(73, 6, 1, 0, 10000, 'admin', 'Setting', 'password', '', '密码修改', '', '密码修改'),
(74, 73, 2, 0, 10000, 'admin', 'Setting', 'passwordPost', '', '密码修改提交', '', '密码修改提交'),
(75, 6, 1, 1, 8, 'admin', 'Setting', 'upload', '', '上传设置', '', '上传设置'),
(76, 75, 2, 0, 10000, 'admin', 'Setting', 'uploadPost', '', '上传设置提交', '', '上传设置提交'),
(77, 6, 1, 0, 10000, 'admin', 'Setting', 'clearCache', '', '清除缓存', '', '清除缓存'),
(78, 165, 1, 0, 40, 'admin', 'Slide', 'index', '', '幻灯片管理', '', '幻灯片管理'),
(79, 78, 1, 0, 10000, 'admin', 'Slide', 'add', '', '添加幻灯片', '', '添加幻灯片'),
(80, 78, 2, 0, 10000, 'admin', 'Slide', 'addPost', '', '添加幻灯片提交', '', '添加幻灯片提交'),
(81, 78, 1, 0, 10000, 'admin', 'Slide', 'edit', '', '编辑幻灯片', '', '编辑幻灯片'),
(82, 78, 2, 0, 10000, 'admin', 'Slide', 'editPost', '', '编辑幻灯片提交', '', '编辑幻灯片提交'),
(83, 78, 2, 0, 10000, 'admin', 'Slide', 'delete', '', '删除幻灯片', '', '删除幻灯片'),
(84, 78, 1, 0, 10000, 'admin', 'SlideItem', 'index', '', '幻灯片页面列表', '', '幻灯片页面列表'),
(85, 84, 1, 0, 10000, 'admin', 'SlideItem', 'add', '', '幻灯片页面添加', '', '幻灯片页面添加'),
(86, 84, 2, 0, 10000, 'admin', 'SlideItem', 'addPost', '', '幻灯片页面添加提交', '', '幻灯片页面添加提交'),
(87, 84, 1, 0, 10000, 'admin', 'SlideItem', 'edit', '', '幻灯片页面编辑', '', '幻灯片页面编辑'),
(88, 84, 2, 0, 10000, 'admin', 'SlideItem', 'editPost', '', '幻灯片页面编辑提交', '', '幻灯片页面编辑提交'),
(89, 84, 2, 0, 10000, 'admin', 'SlideItem', 'delete', '', '幻灯片页面删除', '', '幻灯片页面删除'),
(90, 84, 2, 0, 10000, 'admin', 'SlideItem', 'ban', '', '幻灯片页面隐藏', '', '幻灯片页面隐藏'),
(91, 84, 2, 0, 10000, 'admin', 'SlideItem', 'cancelBan', '', '幻灯片页面显示', '', '幻灯片页面显示'),
(92, 84, 2, 0, 10000, 'admin', 'SlideItem', 'listOrder', '', '幻灯片页面排序', '', '幻灯片页面排序'),
(93, 6, 1, 1, 9, 'admin', 'Storage', 'index', '', '文件存储', '', '文件存储'),
(94, 93, 2, 0, 10000, 'admin', 'Storage', 'settingPost', '', '文件存储设置提交', '', '文件存储设置提交'),
(95, 165, 1, 0, 20, 'admin', 'Theme', 'index', '', '模板管理', '', '模板管理'),
(96, 95, 1, 0, 10000, 'admin', 'Theme', 'install', '', '安装模板', '', '安装模板'),
(97, 95, 2, 0, 10000, 'admin', 'Theme', 'uninstall', '', '卸载模板', '', '卸载模板'),
(98, 95, 2, 0, 10000, 'admin', 'Theme', 'installTheme', '', '模板安装', '', '模板安装'),
(99, 95, 2, 0, 10000, 'admin', 'Theme', 'update', '', '模板更新', '', '模板更新'),
(100, 95, 2, 0, 10000, 'admin', 'Theme', 'active', '', '启用模板', '', '启用模板'),
(101, 95, 1, 0, 10000, 'admin', 'Theme', 'files', '', '模板文件列表', '', '启用模板'),
(102, 95, 1, 0, 10000, 'admin', 'Theme', 'fileSetting', '', '模板文件设置', '', '模板文件设置'),
(103, 95, 1, 0, 10000, 'admin', 'Theme', 'fileArrayData', '', '模板文件数组数据列表', '', '模板文件数组数据列表'),
(104, 95, 2, 0, 10000, 'admin', 'Theme', 'fileArrayDataEdit', '', '模板文件数组数据添加编辑', '', '模板文件数组数据添加编辑'),
(105, 95, 2, 0, 10000, 'admin', 'Theme', 'fileArrayDataEditPost', '', '模板文件数组数据添加编辑提交保存', '', '模板文件数组数据添加编辑提交保存'),
(106, 95, 2, 0, 10000, 'admin', 'Theme', 'fileArrayDataDelete', '', '模板文件数组数据删除', '', '模板文件数组数据删除'),
(107, 95, 2, 0, 10000, 'admin', 'Theme', 'settingPost', '', '模板文件编辑提交保存', '', '模板文件编辑提交保存'),
(108, 95, 1, 0, 10000, 'admin', 'Theme', 'dataSource', '', '模板文件设置数据源', '', '模板文件设置数据源'),
(109, 95, 1, 0, 10000, 'admin', 'Theme', 'design', '', '模板设计', '', '模板设计'),
(110, 0, 0, 1, 10, 'member', 'user', 'empty', '', '运营管理', 'address-book-o', '用户管理'),
(111, 6, 1, 1, 0, 'admin', 'User', 'index', '', '管理员', '', '管理员管理'),
(112, 111, 1, 0, 10000, 'admin', 'User', 'add', '', '管理员添加', '', '管理员添加'),
(113, 111, 2, 0, 10000, 'admin', 'User', 'addPost', '', '管理员添加提交', '', '管理员添加提交'),
(114, 111, 1, 0, 10000, 'admin', 'User', 'edit', '', '管理员编辑', '', '管理员编辑'),
(115, 111, 2, 0, 10000, 'admin', 'User', 'editPost', '', '管理员编辑提交', '', '管理员编辑提交'),
(116, 111, 1, 0, 10000, 'admin', 'User', 'userInfo', '', '个人信息', '', '管理员个人信息修改'),
(117, 111, 2, 0, 10000, 'admin', 'User', 'userInfoPost', '', '管理员个人信息修改提交', '', '管理员个人信息修改提交'),
(118, 111, 2, 0, 10000, 'admin', 'User', 'delete', '', '管理员删除', '', '管理员删除'),
(119, 111, 2, 0, 10000, 'admin', 'User', 'ban', '', '停用管理员', '', '停用管理员'),
(120, 111, 2, 0, 10000, 'admin', 'User', 'cancelBan', '', '启用管理员', '', '启用管理员'),
(121, 0, 0, 0, 70, 'portal', 'AdminIndex', 'default', '', '门户管理', 'th', '门户管理'),
(122, 121, 1, 1, 10000, 'portal', 'AdminArticle', 'index', '', '文章管理', '', '文章列表'),
(123, 122, 1, 0, 10000, 'portal', 'AdminArticle', 'add', '', '添加文章', '', '添加文章'),
(124, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'addPost', '', '添加文章提交', '', '添加文章提交'),
(125, 122, 1, 0, 10000, 'portal', 'AdminArticle', 'edit', '', '编辑文章', '', '编辑文章'),
(126, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'editPost', '', '编辑文章提交', '', '编辑文章提交'),
(127, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'delete', '', '文章删除', '', '文章删除'),
(128, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'publish', '', '文章发布', '', '文章发布'),
(129, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'top', '', '文章置顶', '', '文章置顶'),
(130, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'recommend', '', '文章推荐', '', '文章推荐'),
(131, 122, 2, 0, 10000, 'portal', 'AdminArticle', 'listOrder', '', '文章排序', '', '文章排序'),
(132, 121, 1, 1, 10000, 'portal', 'AdminCategory', 'index', '', '分类管理', '', '文章分类列表'),
(133, 132, 1, 0, 10000, 'portal', 'AdminCategory', 'add', '', '添加文章分类', '', '添加文章分类'),
(134, 132, 2, 0, 10000, 'portal', 'AdminCategory', 'addPost', '', '添加文章分类提交', '', '添加文章分类提交'),
(135, 132, 1, 0, 10000, 'portal', 'AdminCategory', 'edit', '', '编辑文章分类', '', '编辑文章分类'),
(136, 132, 2, 0, 10000, 'portal', 'AdminCategory', 'editPost', '', '编辑文章分类提交', '', '编辑文章分类提交'),
(137, 132, 1, 0, 10000, 'portal', 'AdminCategory', 'select', '', '文章分类选择对话框', '', '文章分类选择对话框'),
(138, 132, 2, 0, 10000, 'portal', 'AdminCategory', 'listOrder', '', '文章分类排序', '', '文章分类排序'),
(139, 132, 2, 0, 10000, 'portal', 'AdminCategory', 'delete', '', '删除文章分类', '', '删除文章分类'),
(140, 121, 1, 1, 10000, 'portal', 'AdminPage', 'index', '', '页面管理', '', '页面管理'),
(141, 140, 1, 0, 10000, 'portal', 'AdminPage', 'add', '', '添加页面', '', '添加页面'),
(142, 140, 2, 0, 10000, 'portal', 'AdminPage', 'addPost', '', '添加页面提交', '', '添加页面提交'),
(143, 140, 1, 0, 10000, 'portal', 'AdminPage', 'edit', '', '编辑页面', '', '编辑页面'),
(144, 140, 2, 0, 10000, 'portal', 'AdminPage', 'editPost', '', '编辑页面提交', '', '编辑页面提交'),
(145, 140, 2, 0, 10000, 'portal', 'AdminPage', 'delete', '', '删除页面', '', '删除页面'),
(146, 121, 1, 1, 10000, 'portal', 'AdminTag', 'index', '', '文章标签', '', '文章标签'),
(147, 146, 1, 0, 10000, 'portal', 'AdminTag', 'add', '', '添加文章标签', '', '添加文章标签'),
(148, 146, 2, 0, 10000, 'portal', 'AdminTag', 'addPost', '', '添加文章标签提交', '', '添加文章标签提交'),
(149, 146, 2, 0, 10000, 'portal', 'AdminTag', 'upStatus', '', '更新标签状态', '', '更新标签状态'),
(150, 146, 2, 0, 10000, 'portal', 'AdminTag', 'delete', '', '删除文章标签', '', '删除文章标签'),
(151, 0, 1, 0, 10000, 'user', 'AdminAsset', 'index', '', '资源管理', 'file', '资源管理列表'),
(152, 151, 2, 0, 10000, 'user', 'AdminAsset', 'delete', '', '删除文件', '', '删除文件'),
(153, 254, 1, 1, 10000, 'member', 'user', 'userinfo', '', '用户列表', '', '用户组'),
(163, 0, 1, 1, 0, 'admin', 'main', 'default', '', '后台首页', '', ''),
(164, 6, 1, 1, 2, 'admin', 'Actionlog', 'index', '', '行为日志', '', ''),
(165, 6, 1, 0, 3, 'admin', 'Config', 'index', '', '配置管理', '', ''),
(166, 6, 0, 0, 100, 'extend', 'AdminIndex', 'default', '', '扩展工具', '', ''),
(167, 6, 1, 1, 10000, 'extend', 'admin_msg', 'index', '', '短信设置', '', ''),
(168, 0, 0, 1, 12, 'recharge', 'default', 'default', '', '财务管理', 'dollar', ''),
(169, 168, 0, 1, 10000, 'recharge', 'spend', 'defualt1', '', '订单管理', '', ''),
(170, 169, 1, 1, 10000, 'recharge', 'spend', 'lists', '', '游戏订单', '', ''),
(172, 169, 1, 0, 10000, 'recharge', 'spend', 'summary', '', '充值汇总', '', ''),
(174, 168, 1, 1, 10001, 'recharge', 'Ptbspend', 'default', '', '平台币管理', '', ''),
(175, 174, 1, 1, 10000, 'recharge', 'Ptbspend', 'lists', '', '充值', '', ''),
(177, 174, 1, 1, 10004, 'recharge', 'Ptbspend', 'senduserlists', '', '玩家发放', '', ''),
(178, 177, 1, 1, 10000, 'recharge', 'Ptbspend', 'senduser', '', '给玩家发放', '', ''),
(180, 254, 1, 1, 10000, 'member', 'mend', 'index', '', '补链记录', '', '用户组'),
(181, 0, 0, 1, 1, 'game', 'default', 'default', '', '游戏管理', 'gamepad', '游戏组'),
(182, 181, 1, 1, 1, 'game', 'game', 'lists', '', '游戏列表', '', '游戏组'),
(183, 181, 1, 1, 2, 'game', 'gameType', 'lists', '', '游戏类型', '', '游戏组'),
(184, 181, 1, 1, 4, 'game', 'server', 'lists', '', '开服表', '', '游戏组'),
(185, 174, 1, 1, 10006, 'recharge', 'Ptbpromote', 'sendpromotelists', '', '渠道发放', '', ''),
(186, 185, 1, 1, 10000, 'recharge', 'Ptbpromote', 'sendpromote', '', '给推广员发放', '', ''),
(187, 185, 0, 0, 10000, 'recharge', 'Ptbpromote', 'get_promote_coin', '', '查询余额', '', ''),
(188, 169, 1, 1, 10030, 'recharge', 'paytype', 'lists', '', '支付设置', '', ''),
(189, 181, 1, 1, 5, 'game', 'giftbag', 'lists', '', '礼包列表', '', '游戏组'),
(190, 254, 1, 1, 10000, 'member', 'user', 'login_record', '', '登录记录', '', '游戏组'),
(191, 254, 1, 1, 10000, 'member', 'user', 'role', '', '角色查询', '', '游戏组'),
(192, 181, 1, 1, 3, 'game', 'gamesource', 'lists', '', '原包管理', '', '游戏组'),
(193, 0, 0, 1, 14, 'promote', 'PromoteIndex', 'default', '', '推广平台', 'handshake-o', ''),
(194, 479, 1, 1, 10000, 'promote', 'promote', 'lists', '', '渠道列表', '', ''),
(196, 479, 1, 1, 10003, 'promote', 'promoteapply', 'lists', '', '游戏分包', '', ''),
(197, 479, 1, 1, 10001, 'promote', 'promoteunion', 'lists', '', '联盟申请', '', ''),
(198, 0, 0, 1, 50, 'site', 'site', 'default', '', '站点管理', 'sitemap', '站点组'),
(200, 256, 1, 1, 10001, 'site', 'site', 'sdk_set', '', '手游SDK', '', ''),
(202, 256, 1, 1, 10002, 'site', 'site', 'wap_set', '', 'WAP站', '', ''),
(204, 259, 1, 1, 10012, 'site', 'notice', 'lists', '', 'SDK公告', '', ''),
(210, 6, 1, 1, 10000, 'admin', 'category', 'index', '', '文章分类', '', ''),
(211, 259, 1, 1, 10000, 'site', 'article', 'lists', 'category=2', '资讯管理', '', ''),
(212, 259, 1, 1, 10000, 'site', 'article', 'lists_doc', 'category=7', '文档管理', '', ''),
(214, 256, 1, 1, 10003, 'site', 'site', 'media_set', '', 'PC官网', '', ''),
(218, 174, 1, 1, 10010, 'promote', 'datamanage', 'coinTransfer', '', '转移记录', '', ''),
(220, 259, 1, 1, 10011, 'site', 'seo', 'media_seo', '', 'SEO设置', '', ''),
(221, 259, 1, 1, 10007, 'site', 'adv', 'adv_pos', '', '广告管理', '', ''),
(222, 259, 1, 1, 10008, 'site', 'link', 'lists', '', '友情链接', '', ''),
(223, 259, 1, 1, 10010, 'site', 'kefu', 'lists', '', '帮助中心', '', ''),
(227, 256, 1, 1, 10004, 'site', 'site', 'promote_set', '', '推广平台', '', ''),
(233, 256, 1, 1, 10000, 'site', 'site', 'admin_set', '', '管理后台', '', ''),
(234, 168, 1, 1, 10002, 'promote', 'settlement', 'empty', '', '渠道结算', '', ''),
(235, 234, 1, 1, 10001, 'promote', 'settlement', 'promote_settlement', '', '渠道结算', '', ''),
(236, 234, 1, 1, 10003, 'promote', 'settlement', 'promote_withdraw', '', '收益提现', '', ''),
(240, 174, 1, 1, 10002, 'recharge', 'ptbdeduct', 'lists', '', '收回', '', ''),
(241, 254, 1, 1, 10003, 'member', 'user', 'balance_update_lists', '', '修改记录', '', ''),
(242, 210, 1, 0, 10000, 'admin', 'Category', 'add', '', '添加文章分类', '', '添加文章分类'),
(243, 210, 2, 0, 10000, 'admin', 'Category', 'addPost', '', '添加文章分类提交', '', '添加文章分类提交'),
(244, 210, 1, 0, 10000, 'admin', 'Category', 'edit', '', '编辑文章分类', '', '编辑文章分类'),
(245, 210, 2, 0, 10000, 'admin', 'Category', 'editPost', '', '编辑文章分类提交', '', '编辑文章分类提交'),
(246, 210, 1, 0, 10000, 'admin', 'Category', 'select', '', '文章分类选择对话框', '', '文章分类选择对话框'),
(247, 210, 2, 0, 10000, 'admin', 'Category', 'listOrder', '', '文章分类排序', '', '文章分类排序'),
(248, 210, 2, 0, 10000, 'admin', 'Category', 'toggle', '', '文章分类显示隐藏', '', '文章分类显示隐藏'),
(249, 210, 2, 0, 10000, 'admin', 'Category', 'delete', '', '删除文章分类', '', '删除文章分类'),
(251, 0, 0, 1, 20, 'datareport', 'user', 'default', '', '数据报表', 'database', ''),
(252, 251, 2, 0, 1, 'datareport', 'User', 'default1', '', '用户数据', '', ''),
(253, 251, 1, 1, 9000, 'datareport', 'User', 'basedata', '', '日报数据', '', ''),
(254, 110, 0, 1, 10000, 'member', 'user', 'manage', '', '用户管理', '', ''),
(255, 254, 1, 1, 10010, 'member', 'user', 'age', '', '用户设置', '', ''),
(256, 198, 0, 1, 10000, 'site', 'site', 'admin', '', '站点配置', '', ''),
(257, 251, 0, 0, 2, 'datareport', 'game', 'default', '', '游戏数据', '', ''),
(258, 251, 1, 1, 10000, 'datareport', 'game', 'game_data', '', '游戏数据', '', ''),
(259, 198, 0, 1, 10000, 'site', 'site', 'empty', '', '其他配置', '', ''),
(260, 251, 1, 1, 10000, 'datareport', 'game', 'game_survey', '', '游戏概况', '', ''),
(261, 251, 0, 0, 3, 'datareport', 'promote', 'default', '', '渠道数据', '', ''),
(262, 251, 1, 1, 10000, 'datareport', 'promote', 'promote_data', '', '渠道数据', '', ''),
(264, 259, 1, 1, 10009, 'site', 'kefutype', 'lists', '', '问题分类', '', ''),
(265, 234, 1, 1, 10004, 'promote', 'settlement', 'promote_exchange', '', '收益兑换', '', ''),
(266, 234, 1, 1, 10002, 'promote', 'settlement', 'promote_summary', '', '结算汇总', '', ''),
(267, 251, 1, 1, 10000, 'datareport', 'promote', 'promote_rank', '', '渠道排行', '', ''),
(268, 251, 1, 0, 4, 'datareport', 'User', 'default2', '', '数据分析', '', ''),
(269, 251, 1, 1, 10000, 'datareport', 'User', 'user_analysis', '', '用户分析', '', ''),
(270, 251, 1, 1, 10000, 'datareport', 'User', 'register_retain', '', '留存分析', '', ''),
(271, 164, 1, 0, 10000, 'admin', 'actionlog', 'clear', '', '清空', '', ''),
(272, 164, 1, 0, 10000, 'admin', 'actionlog', 'delete', '', '删除', '', ''),
(273, 167, 1, 0, 10000, 'extend', 'admin_msg', 'dosave', '', '保存短信设置', '', ''),
(274, 153, 1, 0, 10000, 'member', 'mend', 'add', '', '补链', '', ''),
(275, 153, 1, 0, 10000, 'member', 'user', 'ban', '', '锁定解锁', '', ''),
(276, 153, 1, 0, 10000, 'member', 'user', 'edit', '', '查看', '', ''),
(277, 153, 1, 0, 10000, 'member', 'user', 'balance_edit', '', '修改用户平台币', '', ''),
(278, 153, 1, 0, 10000, 'member', 'user', 'changephone', '', '修改用户手机号', '', ''),
(279, 173, 1, 0, 10000, 'member', 'wechat', 'save_tool', '', '保存设置', '', ''),
(280, 255, 1, 0, 10000, 'member', 'thirdlogin', 'wx_thirdparty', '', '微信参数', '', ''),
(281, 176, 1, 0, 10000, 'member', 'thirdlogin', 'wx_param_lists', '', '单个游戏微信参数', '', ''),
(282, 176, 1, 0, 10000, 'member', 'thirdlogin', 'qq_param_lists', '', '单个游戏QQ参数', '', ''),
(283, 176, 1, 0, 10000, 'member', 'thirdlogin', 'add_qq', '', '新增qq参数', '', ''),
(284, 176, 1, 0, 10000, 'member', 'thirdlogin', 'edit_qq', '', '编辑qq参数', '', ''),
(285, 176, 1, 0, 10000, 'member', 'thirdlogin', 'del', '', '删除', '', ''),
(286, 207, 1, 0, 10000, 'member', 'user', 'sitepost', '', '保存', '', ''),
(287, 182, 1, 0, 10000, 'game', 'game', 'add', '', '新增', '', ''),
(288, 182, 1, 0, 10000, 'game', 'game', 'edit', '', '编辑', '', ''),
(289, 182, 1, 0, 10000, 'game', 'game', 'setgamedatafield', '', '修改排序、下载次数', '', ''),
(290, 182, 1, 0, 10000, 'game', 'game', 'get_game_set', '', '查看对接参数', '', ''),
(291, 183, 1, 0, 10000, 'game', 'game_type', 'add', '', '新增游戏类型', '', ''),
(292, 183, 1, 0, 10000, 'game', 'game_type', 'edit', '', '编辑游戏类型', '', ''),
(293, 183, 1, 0, 10000, 'game', 'game_type', 'setstatus', '', '修改类型状态', '', ''),
(294, 192, 1, 0, 10000, 'game', 'gamesource', 'add', '', '上传原包', '', ''),
(295, 192, 1, 0, 10000, 'game', 'gamesource', 'edit', '', '更新原包', '', ''),
(296, 192, 1, 0, 10000, 'game', 'gamesource', 'del', '', '删除原包', '', ''),
(297, 184, 1, 0, 10000, 'game', 'server', 'add', '', '新增区服', '', ''),
(298, 184, 1, 0, 10000, 'game', 'server', 'edit', '', '编辑区服', '', ''),
(299, 184, 1, 0, 10000, 'game', 'server', 'del', '', '删除区服', '', ''),
(300, 184, 1, 0, 10000, 'game', 'server', 'batch', '', '批量新增区服', '', ''),
(301, 189, 1, 0, 10000, 'game', 'giftbag', 'record', '', '领取记录', '', ''),
(302, 189, 1, 0, 10000, 'game', 'giftbag', 'add', '', '新增礼包', '', ''),
(303, 189, 1, 0, 10000, 'game', 'giftbag', 'edit', '', '编辑礼包', '', ''),
(304, 189, 1, 0, 10000, 'game', 'giftbag', 'del', '', '删除礼包', '', ''),
(305, 170, 1, 0, 10000, 'recharge', 'spend', 'repair', '', '补单', '', ''),
(306, 175, 1, 0, 10000, 'promote', 'datamanage', 'coinrecord', '', '渠道充值', '', ''),
(307, 177, 1, 0, 10000, 'recharge', 'ptbspend', 'recharge', '', '充值', '', ''),
(308, 188, 1, 0, 10000, 'recharge', 'paytype', 'set_pay', '', '保存设置', '', ''),
(309, 265, 1, 0, 10000, 'promote', 'settlement', 'changestatus', '', '收益状态', '', ''),
(310, 235, 1, 0, 10000, 'promote', 'settlement', 'generatesettlementall', '', '确认结算', '', ''),
(311, 235, 1, 0, 10000, 'promote', 'settlement', 'promote_settlement_already', '', '已结算订单', '', ''),
(312, 235, 1, 0, 10000, 'promote', 'settlement', 'promote_settlement_never', '', '不参与结算订单', '', ''),
(313, 235, 1, 0, 10000, 'promote', 'settlement', 'changecheckstatus', '', '参与状态', '', ''),
(314, 194, 1, 0, 10000, 'promote', 'promote', 'changestatus', '', '修改状态', '', ''),
(315, 194, 1, 0, 10000, 'promote', 'promote', 'add', '', '新增渠道', '', ''),
(316, 194, 1, 0, 10000, 'promote', 'promote', 'set_config_auto_audit', '', '自动审核', '', ''),
(317, 194, 1, 0, 10000, 'promote', 'promote', 'edit', '', '编辑渠道', '', ''),
(318, 194, 1, 0, 10000, 'promote', 'promote', 'getpromotegame', '', '查看可推广游戏', '', ''),
(319, 197, 1, 0, 10000, 'promote', 'promoteunion', 'changestatus', '', '修改联盟状态', '', ''),
(320, 197, 1, 0, 10000, 'promote', 'promoteunion', 'set_config_auto_audit_union', '', '自动审核', '', ''),
(321, 196, 1, 0, 10000, 'promote', 'promoteapply', 'changestatus', '', '审核', '', ''),
(322, 196, 1, 0, 10000, 'promote', 'promoteapply', 'allpackage', '', '打包', '', ''),
(323, 198, 1, 0, 10000, 'site', 'site', 'sitepost', '', '保存站点配置', '', ''),
(324, 204, 1, 0, 10000, 'site', 'notice', 'add', '', '新增公告', '', ''),
(325, 204, 1, 0, 10000, 'site', 'notice', 'edit', '', '编辑公告', '', ''),
(326, 204, 1, 0, 10000, 'site', 'notice', 'del', '', '删除公告', '', ''),
(327, 200, 1, 0, 10000, 'site', 'guess', 'add', '', '猜你喜欢新增链接', '', ''),
(328, 200, 1, 0, 10000, 'site', 'guess', 'edit', '', '猜你喜欢编辑链接', '', ''),
(329, 200, 1, 0, 10000, 'site', 'guess', 'del', '', '猜你喜欢删除链接', '', ''),
(330, 259, 1, 0, 10000, 'site', 'article', 'add', '', '新增文章', '', ''),
(331, 259, 1, 0, 10000, 'site', 'article', 'delete', '', '删除文章', '', ''),
(332, 259, 1, 0, 10000, 'site', 'article', 'edit', '', '编辑文章', '', ''),
(333, 259, 1, 0, 10000, 'site', 'article', 'sethits', '', '文章浏览量设置', '', ''),
(334, 220, 1, 0, 10000, 'site', 'seo', 'save', '', 'seo保存', '', ''),
(335, 221, 1, 0, 10000, 'site', 'adv', 'edit', '', '设置广告位', '', ''),
(336, 221, 1, 0, 10000, 'site', 'adv', 'add_adv', '', '新增广告', '', ''),
(337, 221, 1, 0, 10000, 'site', 'adv', 'adv_adv', '', '广告列表', '', ''),
(338, 222, 1, 0, 10000, 'site', 'link', 'add', '', '新增友链', '', ''),
(339, 222, 1, 0, 10000, 'site', 'link', 'edit', '', '编辑友链', '', ''),
(340, 222, 1, 0, 10000, 'site', 'link', 'del', '', '删除友链', '', ''),
(341, 223, 1, 0, 10000, 'site', 'kefu', 'add', '', '新增问题', '', ''),
(342, 223, 1, 0, 10000, 'site', 'kefu', 'edit', '', '编辑问题', '', ''),
(343, 223, 1, 0, 10000, 'site', 'kefu', 'del', '', '删除问题', '', ''),
(344, 264, 1, 0, 10000, 'site', 'kefutype', 'add', '', '新增分类', '', ''),
(345, 264, 1, 0, 10000, 'site', 'kefutype', 'edit', '', '编辑分类', '', ''),
(346, 264, 1, 0, 10000, 'site', 'kefutype', 'del', '', '删除分类', '', ''),
(348, 0, 1, 0, 10000, 'admin', 'User', 'default', '', '--new--', '', ''),
(349, 6, 1, 1, 10001, 'upgrade', 'index', 'index', '', '系统更新', 'arrow-circle-o-up', ''),
(350, 168, 0, 1, 10002, 'recharge', 'bindspend', 'default', '', '绑币管理', '', ''),
(351, 350, 1, 1, 10000, 'recharge', 'Bindspend', 'lists', '', '充值', '', ''),
(352, 350, 1, 1, 10020, 'recharge', 'Bindspend', 'senduserlists', '', '发放', '', ''),
(353, 350, 1, 1, 10010, 'recharge', 'Binddeduct', 'lists', '', '收回', '', ''),
(355, 256, 1, 1, 10007, 'site', 'site', 'kefu_set', '', '客服信息', '', ''),
(356, 168, 1, 1, 10004, 'recharge', 'rebate', 'default', '', '返利折扣', '', ''),
(357, 356, 1, 1, 10000, 'recharge', 'rebate', 'lists', '', '充值返利', '', ''),
(358, 356, 1, 1, 10000, 'recharge', 'rebate', 'welfare', '', '首充续充', '', ''),
(359, 356, 1, 1, 10000, 'recharge', 'rebate', 'agent', '', '会长代充', '', ''),
(360, 168, 1, 0, 10005, 'recharge', 'coupon', 'default', '', '代金券管理', '', ''),
(361, 169, 1, 1, 10010, 'recharge', 'coupon', 'lists', '', '代金券', '', ''),
(362, 169, 1, 1, 10020, 'recharge', 'coupon', 'record', '', '代金券领取', '', ''),
(363, 0, 1, 0, 45, 'business', 'Business', 'default', '', '商务专员', 'coffee', ''),
(364, 480, 1, 1, 10000, 'business', 'Business', 'lists', '', '商务列表', '', ''),
(365, 364, 1, 0, 10000, 'business', 'Business', 'add', '', '新增', '', ''),
(366, 364, 1, 0, 10000, 'business', 'Business', 'edit', '', '编辑', '', ''),
(367, 364, 1, 0, 10000, 'business', 'Business', 'changeStatus', '', '锁定', '', ''),
(368, 110, 1, 1, 10001, 'member', 'pointshop', 'default', '', '积分商城', '', ''),
(369, 368, 1, 1, 10000, 'member', 'invitation', 'record', '', '邀请奖励', '', ''),
(370, 256, 1, 1, 10005, 'site', 'site', 'business_set', '', '商务后台', '', ''),
(371, 256, 1, 1, 10003, 'site', 'site', 'app_set', '', '盒子APP', '', ''),
(372, 479, 1, 1, 10004, 'promote', 'promoteapply', 'app_list', '', 'APP分包', '', ''),
(373, 110, 1, 1, 10002, 'member', 'transaction', 'default', '', '小号交易', '', ''),
(374, 373, 1, 1, 10000, 'member', 'transaction', 'lists', '', '小号商品', '', ''),
(375, 373, 1, 1, 10000, 'member', 'transaction', 'order', '', '小号订单', '', ''),
(376, 373, 1, 1, 10000, 'site', 'site', 'transaction_set', '', '卖号设置', '', ''),
(378, 368, 1, 1, 10000, 'member', 'Tplay', 'task', '', '试玩任务', '', ''),
(379, 251, 1, 1, 10000, 'datareport', 'device', 'survey', '', '应用概况', '', ''),
(380, 368, 1, 1, 10000, 'member', 'point', 'task', '', '积分商城', '', ''),
(381, 368, 1, 1, 10000, 'member', 'point', 'point_record', '', '积分明细', '', ''),
(382, 0, 1, 0, 48, 'issueh5', 'index', 'default', '', '联运分发', 'arrows', ''),
(383, 382, 1, 1, 30, 'issue', 'game', 'lists', '', '联运游戏', '', ''),
(384, 382, 1, 1, 0, 'issue', 'user', 'lists', '', '用户列表', '', ''),
(385, 384, 1, 0, 10000, 'issue', 'User', 'add', '', '新增', '', ''),
(386, 384, 1, 0, 10000, 'issue', 'User', 'edit', '', '编辑', '', ''),
(387, 384, 2, 0, 10000, 'issue', 'User', 'changestatus', '', '修改状态', '', ''),
(388, 382, 1, 1, 10, 'issue', 'Platform', 'lists', '', '平台列表', '', ''),
(389, 388, 1, 0, 10000, 'issue', 'Platform', 'add', '', '新增', '', ''),
(390, 388, 1, 0, 10000, 'issue', 'Platform', 'edit', '', '编辑', '', ''),
(391, 388, 2, 0, 10000, 'issue', 'Platform', 'changestatus', '', '修改状态', '', ''),
(392, 382, 1, 1, 20, 'issue', 'Apply', 'lists', '', '联运申请', '', ''),
(393, 256, 1, 1, 10006, 'site', 'site', 'issue_set', '', '分发平台', '', ''),
(394, 382, 1, 1, 40, 'issue', 'User', 'register', '', '玩家注册', '', ''),
(395, 394, 2, 0, 10000, 'issue', 'User', 'changeUserStatus', '', '修改玩家状态', '', ''),
(396, 382, 1, 1, 50, 'issue', 'User', 'recharge', '', '游戏充值', '', ''),
(397, 396, 1, 0, 10000, 'issue', 'User', 'paySummary', '', '充值汇总', '', ''),
(398, 388, 2, 0, 10000, 'issue', 'Platform', 'saveplatformgame', '', '禁止申请游戏', '', ''),
(399, 388, 2, 0, 10000, 'issue', 'Platform', 'getplatformgame', '', '获取游戏列表', '', ''),
(400, 392, 2, 0, 10000, 'issue', 'Apply', 'gameconfig', '', '获取游戏配置', '', ''),
(401, 392, 2, 0, 10000, 'issue', 'Apply', 'changefield', '', '修改字段值', '', ''),
(402, 392, 2, 0, 10000, 'issue', 'Apply', 'audit', '', '审核', '', ''),
(403, 383, 1, 0, 10000, 'issueh5', 'AdminGame', 'edit', '', '编辑', '', ''),
(404, 383, 2, 0, 10000, 'issueh5', 'AdminGame', 'xt_lists', '', '获取游戏列表', '', ''),
(405, 383, 2, 0, 10000, 'issueh5', 'AdminGame', 'add', '', '添加游戏', '', ''),
(406, 383, 2, 0, 10000, 'issueh5', 'AdminGame', 'delete', '', '删除游戏', '', ''),
(407, 383, 2, 0, 10000, 'issueh5', 'AdminGame', 'changestatus', '', '修改状态', '', ''),
(408, 383, 1, 0, 10000, 'issuesy', 'AdminGame', 'edit', '', '编辑', '', ''),
(409, 383, 2, 0, 10000, 'issuesy', 'AdminGame', 'xt_lists', '', '获取游戏列表', '', ''),
(410, 383, 2, 0, 10000, 'issuesy', 'AdminGame', 'add', '', '添加游戏', '', ''),
(411, 383, 2, 0, 10000, 'issuesy', 'AdminGame', 'delete', '', '删除游戏', '', ''),
(412, 383, 2, 0, 10000, 'issuesy', 'AdminGame', 'changestatus', '', '修改状态', '', ''),
(413, 181, 1, 1, 10000, 'game', 'comment', 'lists', '', '评论列表 ', '', ''),
(414, 368, 1, 1, 10000, 'member', 'mcard', 'lists', '', '尊享卡', '', ''),
(415, 413, 2, 0, 10000, 'game', 'comment', 'changestatus', '', '审核', '', ''),
(416, 413, 2, 0, 10000, 'game', 'comment', 'set_config_auto_audit', '', '自动审核', '', ''),
(417, 414, 1, 0, 10000, 'member', 'mcard', 'set', '', '尊享卡设置', '', ''),
(418, 254, 1, 1, 10001, 'member', 'warning', 'lists', '', '异常预警', '', ''),
(419, 181, 1, 1, 6, 'game', 'interface', 'lists', '', '页游接口', '', ''),
(420, 419, 1, 0, 10000, 'game', 'interface', 'add', '', '新增', '', ''),
(421, 419, 1, 0, 10000, 'game', 'interface', 'edit', '', '编辑', '', ''),
(422, 419, 2, 0, 10000, 'game', 'interface', 'del', '', '删除', '', ''),
(423, 479, 1, 1, 10005, 'promote', 'Support', 'lists', '', '扶持发放', '', ''),
(424, 423, 2, 0, 10000, 'promote', 'Support', 'audit', '', '审核扶持', '', ''),
(425, 423, 2, 0, 10000, 'promote', 'Support', 'send', '', '发放扶持', '', ''),
(426, 423, 2, 0, 10000, 'promote', 'Support', 'deny', '', '拒绝扶持', '', ''),
(427, 423, 2, 0, 10000, 'promote', 'Support', 'changeAutoAudit', '', '自动审核', '', ''),
(428, 181, 1, 1, 10000, 'game', 'Cp', 'lists', '', 'CP列表', '', 'CP列表'),
(429, 479, 1, 1, 10006, 'promote', 'promotecps', 'lists', '', '结算设置', '', ''),
(430, 193, 1, 0, 10005, 'promote', 'promotecpa', 'lists', '', 'CPA单价设置', '', ''),
(431, 255, 1, 0, 10000, 'member', 'wechat', 'index', '', '公众号设置', '', ''),
(432, 255, 1, 0, 10000, 'member', 'user', 'vip_set', '', 'VIP设置', '', ''),
(433, 255, 1, 0, 10000, 'member', 'thirdlogin', 'qq_thirdparty', '', 'QQ', '', ''),
(434, 182, 2, 0, 10000, 'game', 'Game', 'addSupserVersion', '', '更新超级签版本号', '', ''),
(435, 193, 1, 1, 10006, 'btwelfare', 'Bt', 'index', '', 'BT福利管理', '', ''),
(436, 435, 1, 1, 10000, 'btwelfare', 'Register', 'lists', '', '注册福利', '', ''),
(437, 435, 1, 1, 10000, 'btwelfare', 'Recharge', 'lists', '', '充值福利', '', ''),
(438, 435, 1, 1, 10000, 'btwelfare', 'Total', 'lists', '', '累充福利', '', ''),
(439, 435, 1, 1, 10000, 'btwelfare', 'Monthcard', 'lists', '', '月卡福利', '', ''),
(440, 435, 1, 1, 10000, 'btwelfare', 'Weekcard', 'lists', '', '周卡福利', '', ''),
(441, 435, 1, 1, 10000, 'btwelfare', 'Bt', 'setting', '', '福利设置', '', ''),
(442, 435, 1, 1, 10000, 'btwelfare', 'Prop', 'lists', '', '道具管理', '', ''),
(443, 442, 1, 0, 10000, 'btwelfare', 'Prop', 'add', '', '添加道具', '', ''),
(444, 442, 1, 0, 10000, 'btwelfare', 'Prop', 'edit', '', '编辑道具', '', ''),
(445, 442, 2, 0, 10000, 'btwelfare', 'Prop', 'del', '', '删除道具', '', ''),
(446, 442, 2, 0, 10000, 'btwelfare', 'Prop', 'changeStatus', '', '修改状态', '', ''),
(447, 441, 1, 0, 10000, 'btwelfare', 'Bt', 'add', '', '新增福利', '', ''),
(448, 441, 1, 0, 10000, 'btwelfare', 'Bt', 'edit', '', '编辑福利', '', ''),
(449, 441, 2, 0, 10000, 'btwelfare', 'Bt', 'del', '', '删除福利', '', ''),
(450, 441, 2, 0, 10000, 'btwelfare', 'Bt', 'ajaxGetPromote', '', '获取推广员列表', '', ''),
(451, 441, 2, 0, 10000, 'btwelfare', 'Bt', 'changestatus', '', '修改状态', '', ''),
(452, 442, 2, 0, 10000, 'btwelfare', 'Prop', 'getGameProp', '', '获取游戏下道具列表', '', ''),
(453, 479, 1, 1, 10002, 'promote', 'promote', 'prepayment_record', '', '预付款', '', ''),
(454, 453, 1, 0, 10000, 'promote', 'promote', 'prepayment_deduct_record', '', '渠道预付款消费记录', '', ''),
(455, 453, 1, 0, 10000, 'promote', 'promote', 'sendPrepaymentRecord', '', '渠道预付款发放记录', '', ''),
(456, 194, 2, 0, 10000, 'promote', 'promote', 'sendPrepayment', '', '发放预付款', '', ''),
(457, 169, 2, 0, 10000, 'recharge', 'Spend', 'getNotifyInfo', '', '获取游戏回调信息', '', ''),
(458, 0, 1, 0, 80, 'oa', 'Oa', 'default', '', '工作室管理', 'dashboard', ''),
(459, 480, 1, 1, 10000, 'oa', 'Studio', 'lists', '', '工作室列表', '', ''),
(460, 459, 1, 0, 10000, 'oa', 'Studio', 'add', '', '添加工作室', '', ''),
(461, 459, 1, 0, 10000, 'oa', 'Studio', 'edit', '', '编辑工作室', '', ''),
(462, 459, 2, 0, 10000, 'oa', 'Studio', 'del', '', '删除工作室', '', ''),
(463, 256, 1, 1, 10001, 'site', 'site', 'sdkw_set', '', '海外SDK', '', ''),
(464, 463, 1, 0, 10000, 'site', 'protocol', 'lists', '', '用户注册协议', '', ''),
(465, 463, 1, 0, 10000, 'site', 'protocol', 'add', '', '新增用户注册协议', '', ''),
(466, 463, 1, 0, 10000, 'site', 'protocol', 'edit', '', '编辑用户注册协议', '', ''),
(467, 463, 1, 0, 10000, 'site', 'protocol', 'del', '', '删除用户注册协议', '', ''),
(468, 163, 1, 1, 10000, 'admin', 'main', 'index', '', '后台首页', 'desktop-item', ''),
(469, 254, 1, 1, 10002, 'member', 'user', 'feedback', '', '投诉反馈', '', ''),
(470, 469, 2, 0, 10000, 'member', 'user', 'feedbackremark', '', '修改备注', '', ''),
(471, 163, 1, 0, 10000, 'admin', 'setting', 'system', '', '系统设置', '', ''),
(472, 223, 2, 0, 10000, 'site', 'kefu', 'setsort', '', '修改排序', '', ''),
(473, 221, 2, 0, 10000, 'site', 'adv', 'get_adv_pos_lists', '', '获取广告位列表', '', ''),
(474, 153, 2, 0, 10000, 'member', 'user', 'changeemail', '', '修改用户邮箱', '', ''),
(475, 382, 1, 1, 10000, 'issue', 'UserBalance', 'lists', '', '联运币充值', '', ''),
(476, 169, 1, 1, 10000, 'recharge', 'spend', 'bingLog', '', '订单绑定', '', ''),
(477, 110, 0, 0, 10003, 'member', 'user', 'default2', '', '用户设置', '', ''),
(478, 168, 0, 0, 10006, 'recharge', 'paytype', 'default', '', '支付设置', '', ''),
(479, 193, 0, 1, 10000, 'promote', 'promote', 'default', '', '渠道管理', '', ''),
(480, 0, 0, 1, 49, 'admin', 'other', 'default', '', '其他', '', ''),
(481, 197, 1, 0, 10000, 'promote', 'promoteunion', 'view_detail', '', '渠道申请信息', '', ''),
(482, 256, 1, 1, 10001, 'site', 'site', 'sdksimplify_set', '', '简化SDK', '', ''),
(483, 177, 2, 0, 10000, 'recharge', 'ptbspend', 'set_ptb_send_quota', '', '发放限额配置', '', ''),
(484, 185, 2, 0, 10000, 'recharge', 'ptbspend', 'set_ptb_channel_send_quota', '', '发放限额配置', '', ''),
(485, 254, 1, 1, 10000, 'member', 'user', 'safecenter', '', '安全中心', '', ''),
(486, 168, 0, 1, 10007, 'recharge', 'CashOut', 'default', '', '提现管理', '', ''),
(487, 486, 1, 1, 10000, 'site', 'site', 'ptb_cash_out_set', '', '提现配置', '', ''),
(488, 486, 1, 1, 10000, 'recharge', 'CashOut', 'lists', '', '提现列表', '', ''),
(489, 153, 1, 0, 10000, 'member', 'user', 'batchCreate', '', '批量创建用户', '', ''),
(490, 234, 1, 1, 10000, 'promote', 'settlement', 'promote_period_settlement', '', '结算打款', '', ''),
(491, 153, 2, 0, 10000, 'member', 'user', 'promoteGame', '', '获取推广员已申请游戏', '', ''),
(492, 254, 1, 1, 10000, 'member', 'user', 'userstage', '', '玩家阶段', '', ''),
(493, 182, 2, 0, 10000, 'game', 'game', 'changeGameName', '', '修改游戏名称', '', ''),
(494, 182, 1, 0, 10000, 'game', 'game', 'changeGameNameLog', '', '修改游戏名称记录', '', ''),
(495, 492, 1, 0, 10000, 'member', 'user', 'setuserstage', '', '设置玩家阶段', '', ''),
(496, 492, 1, 0, 10000, 'member', 'user', 'edit_follow_remind', '', '编辑跟进提醒', '', ''),
(497, 192, 1, 0, 10000, 'game', 'gamesource', 'batch_del', '', '批量删除', '', ''),
(498, 492, 2, 0, 10000, 'member', 'user', 'change_user_score', '', '修改玩家评分', '', ''),
(499, 492, 2, 0, 10000, 'member', 'user', 'change_user_stage_remark', '', '修改用户阶段备注', '', ''),
(500, 492, 1, 0, 10000, 'member', 'user', 'delete_stage', '', '删除阶段', '', ''),
(501, 492, 2, 0, 10000, 'member', 'user', 'do_delete_stage', '', '执行删除操作', '', ''),
(502, 492, 2, 0, 10000, 'member', 'user', 'edit_stage', '', '编辑阶段管理', '', ''),
(503, 492, 2, 0, 10000, 'member', 'user', 'add_stage', '', '添加/编辑阶段管理', '', ''),
(504, 492, 2, 0, 10000, 'member', 'user', 'change_stage_order', '', '用户阶段更改顺序', '', ''),
(505, 492, 1, 0, 10000, 'member', 'user', 'add_user_stage', '', '未进入阶段的玩家', '', ''),
(506, 181, 1, 0, 10000, 'thirdgame', 'game', 'lists', '', '其他游戏', '', ''),
(507, 181, 1, 0, 10000, 'thirdgame', 'platform', 'lists', '', '其他平台', '', ''),
(509, 480, 1, 0, 10000, 'webplatform', 'web_platform', 'lists', '', '简化系统列表', '', ''),
(510, 382, 1, 1, 10000, 'issue', 'Channel', 'lists', '', '渠道大全', '', ''),
(511, 382, 1, 1, 10000, 'issue', 'Data', 'overview', '', '数据总览', '', ''),
(512, 382, 1, 1, 10000, 'issue', 'Data', 'daily', '', '日报数据', '', ''),
(513, 382, 1, 1, 10000, 'issue', 'Data', 'dailyHour', '', '日报表(时)', '', ''),
(514, 506, 1, 0, 10000, 'thirdgame', 'game', 'edit', '', '编辑', '', ''),
(515, 506, 1, 0, 10000, 'thirdgame', 'game', 'changestatus', '', '修改游戏状态', '', ''),
(516, 506, 1, 0, 10000, 'thirdgame', 'game', 'changepaytype', '', '修改支付状态', '', ''),
(517, 506, 1, 0, 10000, 'thirdgame', 'game', 'importGame', '', '导入游戏', '', ''),
(518, 507, 1, 0, 10000, 'thirdgame', 'platform', 'add', '', '新增', '', ''),
(519, 507, 1, 0, 10000, 'thirdgame', 'platform', 'edit', '', '编辑', '', ''),
(520, 507, 1, 0, 10000, 'thirdgame', 'platform', 'setstatus', '', '修改状态', '', ''),
(521, 509, 1, 0, 10000, 'webplatform', 'web_platform', 'add', '', '新增', '', ''),
(522, 509, 1, 0, 10000, 'webplatform', 'web_platform', 'edit', '', '编辑', '', ''),
(523, 509, 1, 0, 10000, 'webplatform', 'web_platform', 'setstatus', '', '修改状态', '', ''),
(524, 394, 1, 0, 10000, 'issue', 'User', 'detail', '', '用户详情', '', ''),
(525, 394, 1, 0, 10000, 'issue', 'User', 'activationRecord', '', '激活游戏记录', '', ''),
(526, 394, 1, 0, 10000, 'issue', 'User', 'loginRecord', '', '近期登陆记录', '', ''),
(527, 394, 1, 0, 10000, 'issue', 'User', 'spendRecord', '', '用户付费记录', '', ''),
(528, 394, 2, 0, 10000, 'issue', 'User', 'bind', '', '用户绑定', '', ''),
(529, 356, 1, 1, 10001, 'recharge', 'rebate', 'bind_discount', '', '绑币充值', '', ''),
(530, 181, 1, 1, 10001, 'game', 'Interflow', 'lists', '', '游戏互通', '', ''),
(531, 530, 1, 0, 10000, 'game', 'Interflow', 'add', '', '添加游戏互通', '', ''),
(532, 530, 1, 0, 10000, 'game', 'Interflow', 'append', '', '追加互通游戏', '', ''),
(533, 530, 2, 0, 10000, 'game', 'Interflow', 'delete', '', '删除游戏互通', '', ''),
(534, 510, 2, 0, 10000, 'issue', 'Channel', 'apply', '', '渠道申请接入', '', ''),
(535, 510, 2, 0, 10000, 'issue', 'Channel', 'delete', '', '删除已接入渠道', '', ''),
(536, 153, 1, 0, 10000, 'member', 'user', 'user_data_analyze', '', '玩家画像', '', ''),
(537, 168, 0, 1, 10003, 'cp', 'settlement', 'empty', '', 'CP结算', '', ''),
(538, 537, 1, 1, 10000, 'cp', 'settlement', 'game_settlement', '', '游戏结算', '', ''),
(539, 537, 1, 1, 10000, 'cp', 'settlement', 'settlement_record', '', '结算记录', '', ''),
(540, 537, 1, 1, 10000, 'cp', 'settlement', 'remit_record', '', '打款记录', '', ''),
(541, 537, 1, 0, 10000, 'cp', 'settlement', 'create_settlement', '', '创建打款', '', ''),
(542, 153, 2, 0, 10000, 'member', 'export', 'expuser', '', '导出', '', ''),
(543, 153, 2, 0, 10000, 'member', 'user', 'search', '', '查看/检索注册信息', '', ''),
(544, 182, 2, 0, 10000, 'game', 'game', 'downDockingFile', '', '下载游戏参数对接文件', '', '');

-- --------------------------------------------------------

--
-- 表的结构 `sys_asset`
--

CREATE TABLE `sys_asset` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件大小,单位B',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '上传时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态;1:可用,0:不可用',
  `download_times` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '下载次数',
  `file_key` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '文件惟一码',
  `filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `file_path` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '文件路径,相对于upload目录,可以为url',
  `file_md5` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '文件md5值',
  `file_sha1` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `suffix` varchar(10) NOT NULL DEFAULT '' COMMENT '文件后缀名,不包括点',
  `more` text COMMENT '其它详细信息,JSON格式'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资源表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_auth_access`
--

CREATE TABLE `sys_auth_access` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '角色',
  `rule_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则唯一英文标识,全小写',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '权限规则分类,请加应用前缀,如admin_'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限授权表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_auth_rule`
--

CREATE TABLE `sys_auth_rule` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '规则id,自增主键',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否有效(0:无效,1:有效)',
  `app` varchar(40) NOT NULL DEFAULT '' COMMENT '规则所属app',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '权限规则分类，请加应用前缀,如admin_',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则唯一英文标识,全小写',
  `param` varchar(100) NOT NULL DEFAULT '' COMMENT '额外url参数',
  `title` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则描述',
  `condition` varchar(200) NOT NULL DEFAULT '' COMMENT '规则附加条件'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限规则表';

--
-- 转存表中的数据 `sys_auth_rule`
--

INSERT INTO `sys_auth_rule` (`id`, `status`, `app`, `type`, `name`, `param`, `title`, `condition`) VALUES
(1, 1, 'admin', 'admin_url', 'admin/Hook/index', '', '钩子管理', ''),
(2, 1, 'admin', 'admin_url', 'admin/Hook/plugins', '', '钩子插件管理', ''),
(3, 1, 'admin', 'admin_url', 'admin/Hook/pluginListOrder', '', '钩子插件排序', ''),
(4, 1, 'admin', 'admin_url', 'admin/Hook/sync', '', '同步钩子', ''),
(5, 1, 'admin', 'admin_url', 'admin/Link/index', '', '友情链接', ''),
(6, 1, 'admin', 'admin_url', 'admin/Link/add', '', '添加友情链接', ''),
(7, 1, 'admin', 'admin_url', 'admin/Link/addPost', '', '添加友情链接提交保存', ''),
(8, 1, 'admin', 'admin_url', 'admin/Link/edit', '', '编辑友情链接', ''),
(9, 1, 'admin', 'admin_url', 'admin/Link/editPost', '', '编辑友情链接提交保存', ''),
(10, 1, 'admin', 'admin_url', 'admin/Link/delete', '', '删除友情链接', ''),
(11, 1, 'admin', 'admin_url', 'admin/Link/listOrder', '', '友情链接排序', ''),
(12, 1, 'admin', 'admin_url', 'admin/Link/toggle', '', '友情链接显示隐藏', ''),
(13, 1, 'admin', 'admin_url', 'admin/Mailer/index', '', '邮箱配置', ''),
(14, 1, 'admin', 'admin_url', 'admin/Mailer/indexPost', '', '邮箱配置提交保存', ''),
(15, 1, 'admin', 'admin_url', 'admin/Mailer/template', '', '邮件模板', ''),
(16, 1, 'admin', 'admin_url', 'admin/Mailer/templatePost', '', '邮件模板提交', ''),
(17, 1, 'admin', 'admin_url', 'admin/Mailer/test', '', '邮件发送测试', ''),
(18, 1, 'admin', 'admin_url', 'admin/Menu/index', '', '后台菜单', ''),
(19, 1, 'admin', 'admin_url', 'admin/Menu/lists', '', '所有菜单', ''),
(20, 1, 'admin', 'admin_url', 'admin/Menu/add', '', '后台菜单添加', ''),
(21, 1, 'admin', 'admin_url', 'admin/Menu/addPost', '', '后台菜单添加提交保存', ''),
(22, 1, 'admin', 'admin_url', 'admin/Menu/edit', '', '后台菜单编辑', ''),
(23, 1, 'admin', 'admin_url', 'admin/Menu/editPost', '', '后台菜单编辑提交保存', ''),
(24, 1, 'admin', 'admin_url', 'admin/Menu/delete', '', '后台菜单删除', ''),
(25, 1, 'admin', 'admin_url', 'admin/Menu/listOrder', '', '后台菜单排序', ''),
(26, 1, 'admin', 'admin_url', 'admin/Menu/getActions', '', '导入新后台菜单', ''),
(27, 1, 'admin', 'admin_url', 'admin/Nav/index', '', '导航管理', ''),
(28, 1, 'admin', 'admin_url', 'admin/Nav/add', '', '添加导航', ''),
(29, 1, 'admin', 'admin_url', 'admin/Nav/addPost', '', '添加导航提交保存', ''),
(30, 1, 'admin', 'admin_url', 'admin/Nav/edit', '', '编辑导航', ''),
(31, 1, 'admin', 'admin_url', 'admin/Nav/editPost', '', '编辑导航提交保存', ''),
(32, 1, 'admin', 'admin_url', 'admin/Nav/delete', '', '删除导航', ''),
(33, 1, 'admin', 'admin_url', 'admin/NavMenu/index', '', '导航菜单', ''),
(34, 1, 'admin', 'admin_url', 'admin/NavMenu/add', '', '添加导航菜单', ''),
(35, 1, 'admin', 'admin_url', 'admin/NavMenu/addPost', '', '添加导航菜单提交保存', ''),
(36, 1, 'admin', 'admin_url', 'admin/NavMenu/edit', '', '编辑导航菜单', ''),
(37, 1, 'admin', 'admin_url', 'admin/NavMenu/editPost', '', '编辑导航菜单提交保存', ''),
(38, 1, 'admin', 'admin_url', 'admin/NavMenu/delete', '', '删除导航菜单', ''),
(39, 1, 'admin', 'admin_url', 'admin/NavMenu/listOrder', '', '导航菜单排序', ''),
(40, 1, 'admin', 'admin_url', 'admin/Plugin/default', '', '插件中心', ''),
(41, 1, 'admin', 'admin_url', 'admin/Plugin/index', '', '插件列表', ''),
(42, 1, 'admin', 'admin_url', 'admin/Plugin/toggle', '', '插件启用禁用', ''),
(43, 1, 'admin', 'admin_url', 'admin/Plugin/setting', '', '插件设置', ''),
(44, 1, 'admin', 'admin_url', 'admin/Plugin/settingPost', '', '插件设置提交', ''),
(45, 1, 'admin', 'admin_url', 'admin/Plugin/install', '', '插件安装', ''),
(46, 1, 'admin', 'admin_url', 'admin/Plugin/update', '', '插件更新', ''),
(47, 1, 'admin', 'admin_url', 'admin/Plugin/uninstall', '', '卸载插件', ''),
(48, 1, 'admin', 'admin_url', 'admin/Rbac/index', '', '角色权限', ''),
(49, 1, 'admin', 'admin_url', 'admin/Rbac/roleAdd', '', '添加角色', ''),
(50, 1, 'admin', 'admin_url', 'admin/Rbac/roleAddPost', '', '添加角色提交', ''),
(51, 1, 'admin', 'admin_url', 'admin/Rbac/roleEdit', '', '编辑角色', ''),
(52, 1, 'admin', 'admin_url', 'admin/Rbac/roleEditPost', '', '编辑角色提交', ''),
(53, 1, 'admin', 'admin_url', 'admin/Rbac/roleDelete', '', '删除角色', ''),
(54, 1, 'admin', 'admin_url', 'admin/Rbac/authorize', '', '设置角色权限', ''),
(55, 1, 'admin', 'admin_url', 'admin/Rbac/authorizePost', '', '角色授权提交', ''),
(56, 1, 'admin', 'admin_url', 'admin/RecycleBin/index', '', '回收站', ''),
(57, 1, 'admin', 'admin_url', 'admin/RecycleBin/restore', '', '回收站还原', ''),
(58, 1, 'admin', 'admin_url', 'admin/RecycleBin/delete', '', '回收站彻底删除', ''),
(59, 1, 'admin', 'admin_url', 'admin/Route/index', '', 'URL美化', ''),
(60, 1, 'admin', 'admin_url', 'admin/Route/add', '', '添加路由规则', ''),
(61, 1, 'admin', 'admin_url', 'admin/Route/addPost', '', '添加路由规则提交', ''),
(62, 1, 'admin', 'admin_url', 'admin/Route/edit', '', '路由规则编辑', ''),
(63, 1, 'admin', 'admin_url', 'admin/Route/editPost', '', '路由规则编辑提交', ''),
(64, 1, 'admin', 'admin_url', 'admin/Route/delete', '', '路由规则删除', ''),
(65, 1, 'admin', 'admin_url', 'admin/Route/ban', '', '路由规则禁用', ''),
(66, 1, 'admin', 'admin_url', 'admin/Route/open', '', '路由规则启用', ''),
(67, 1, 'admin', 'admin_url', 'admin/Route/listOrder', '', '路由规则排序', ''),
(68, 1, 'admin', 'admin_url', 'admin/Route/select', '', '选择URL', ''),
(69, 1, 'admin', 'admin_url', 'admin/Setting/default', '', '系统设置', ''),
(70, 1, 'admin', 'admin_url', 'admin/Setting/site', '', '网站信息', ''),
(71, 1, 'admin', 'admin_url', 'admin/Setting/sitePost', '', '网站信息设置提交', ''),
(72, 1, 'admin', 'admin_url', 'admin/Setting/password', '', '密码修改', ''),
(73, 1, 'admin', 'admin_url', 'admin/Setting/passwordPost', '', '密码修改提交', ''),
(74, 1, 'admin', 'admin_url', 'admin/Setting/upload', '', '上传设置', ''),
(75, 1, 'admin', 'admin_url', 'admin/Setting/uploadPost', '', '上传设置提交', ''),
(76, 1, 'admin', 'admin_url', 'admin/Setting/clearCache', '', '清除缓存', ''),
(77, 1, 'admin', 'admin_url', 'admin/Slide/index', '', '幻灯片管理', ''),
(78, 1, 'admin', 'admin_url', 'admin/Slide/add', '', '添加幻灯片', ''),
(79, 1, 'admin', 'admin_url', 'admin/Slide/addPost', '', '添加幻灯片提交', ''),
(80, 1, 'admin', 'admin_url', 'admin/Slide/edit', '', '编辑幻灯片', ''),
(81, 1, 'admin', 'admin_url', 'admin/Slide/editPost', '', '编辑幻灯片提交', ''),
(82, 1, 'admin', 'admin_url', 'admin/Slide/delete', '', '删除幻灯片', ''),
(83, 1, 'admin', 'admin_url', 'admin/SlideItem/index', '', '幻灯片页面列表', ''),
(84, 1, 'admin', 'admin_url', 'admin/SlideItem/add', '', '幻灯片页面添加', ''),
(85, 1, 'admin', 'admin_url', 'admin/SlideItem/addPost', '', '幻灯片页面添加提交', ''),
(86, 1, 'admin', 'admin_url', 'admin/SlideItem/edit', '', '幻灯片页面编辑', ''),
(87, 1, 'admin', 'admin_url', 'admin/SlideItem/editPost', '', '幻灯片页面编辑提交', ''),
(88, 1, 'admin', 'admin_url', 'admin/SlideItem/delete', '', '幻灯片页面删除', ''),
(89, 1, 'admin', 'admin_url', 'admin/SlideItem/ban', '', '幻灯片页面隐藏', ''),
(90, 1, 'admin', 'admin_url', 'admin/SlideItem/cancelBan', '', '幻灯片页面显示', ''),
(91, 1, 'admin', 'admin_url', 'admin/SlideItem/listOrder', '', '幻灯片页面排序', ''),
(92, 1, 'admin', 'admin_url', 'admin/Storage/index', '', '文件存储', ''),
(93, 1, 'admin', 'admin_url', 'admin/Storage/settingPost', '', '文件存储设置提交', ''),
(94, 1, 'admin', 'admin_url', 'admin/Theme/index', '', '模板管理', ''),
(95, 1, 'admin', 'admin_url', 'admin/Theme/install', '', '安装模板', ''),
(96, 1, 'admin', 'admin_url', 'admin/Theme/uninstall', '', '卸载模板', ''),
(97, 1, 'admin', 'admin_url', 'admin/Theme/installTheme', '', '模板安装', ''),
(98, 1, 'admin', 'admin_url', 'admin/Theme/update', '', '模板更新', ''),
(99, 1, 'admin', 'admin_url', 'admin/Theme/active', '', '启用模板', ''),
(100, 1, 'admin', 'admin_url', 'admin/Theme/files', '', '模板文件列表', ''),
(101, 1, 'admin', 'admin_url', 'admin/Theme/fileSetting', '', '模板文件设置', ''),
(102, 1, 'admin', 'admin_url', 'admin/Theme/fileArrayData', '', '模板文件数组数据列表', ''),
(103, 1, 'admin', 'admin_url', 'admin/Theme/fileArrayDataEdit', '', '模板文件数组数据添加编辑', ''),
(104, 1, 'admin', 'admin_url', 'admin/Theme/fileArrayDataEditPost', '', '模板文件数组数据添加编辑提交保存', ''),
(105, 1, 'admin', 'admin_url', 'admin/Theme/fileArrayDataDelete', '', '模板文件数组数据删除', ''),
(106, 1, 'admin', 'admin_url', 'admin/Theme/settingPost', '', '模板文件编辑提交保存', ''),
(107, 1, 'admin', 'admin_url', 'admin/Theme/dataSource', '', '模板文件设置数据源', ''),
(108, 1, 'admin', 'admin_url', 'admin/Theme/design', '', '模板设计', ''),
(109, 1, 'admin', 'admin_url', 'admin/User/default', '', '管理组', ''),
(110, 1, 'admin', 'admin_url', 'admin/User/index', '', '管理员', ''),
(111, 1, 'admin', 'admin_url', 'admin/User/add', '', '管理员添加', ''),
(112, 1, 'admin', 'admin_url', 'admin/User/addPost', '', '管理员添加提交', ''),
(113, 1, 'admin', 'admin_url', 'admin/User/edit', '', '管理员编辑', ''),
(114, 1, 'admin', 'admin_url', 'admin/User/editPost', '', '管理员编辑提交', ''),
(115, 1, 'admin', 'admin_url', 'admin/User/userInfo', '', '个人信息', ''),
(116, 1, 'admin', 'admin_url', 'admin/User/userInfoPost', '', '管理员个人信息修改提交', ''),
(117, 1, 'admin', 'admin_url', 'admin/User/delete', '', '管理员删除', ''),
(118, 1, 'admin', 'admin_url', 'admin/User/ban', '', '停用管理员', ''),
(119, 1, 'admin', 'admin_url', 'admin/User/cancelBan', '', '启用管理员', ''),
(120, 1, 'portal', 'admin_url', 'portal/AdminArticle/index', '', '文章管理', ''),
(121, 1, 'portal', 'admin_url', 'portal/AdminArticle/add', '', '添加文章', ''),
(122, 1, 'portal', 'admin_url', 'portal/AdminArticle/addPost', '', '添加文章提交', ''),
(123, 1, 'portal', 'admin_url', 'portal/AdminArticle/edit', '', '编辑文章', ''),
(124, 1, 'portal', 'admin_url', 'portal/AdminArticle/editPost', '', '编辑文章提交', ''),
(125, 1, 'portal', 'admin_url', 'portal/AdminArticle/delete', '', '文章删除', ''),
(126, 1, 'portal', 'admin_url', 'portal/AdminArticle/publish', '', '文章发布', ''),
(127, 1, 'portal', 'admin_url', 'portal/AdminArticle/top', '', '文章置顶', ''),
(128, 1, 'portal', 'admin_url', 'portal/AdminArticle/recommend', '', '文章推荐', ''),
(129, 1, 'portal', 'admin_url', 'portal/AdminArticle/listOrder', '', '文章排序', ''),
(130, 1, 'portal', 'admin_url', 'portal/AdminCategory/index', '', '分类管理', ''),
(131, 1, 'portal', 'admin_url', 'portal/AdminCategory/add', '', '添加文章分类', ''),
(132, 1, 'portal', 'admin_url', 'portal/AdminCategory/addPost', '', '添加文章分类提交', ''),
(133, 1, 'portal', 'admin_url', 'portal/AdminCategory/edit', '', '编辑文章分类', ''),
(134, 1, 'portal', 'admin_url', 'portal/AdminCategory/editPost', '', '编辑文章分类提交', ''),
(135, 1, 'portal', 'admin_url', 'portal/AdminCategory/select', '', '文章分类选择对话框', ''),
(136, 1, 'portal', 'admin_url', 'portal/AdminCategory/listOrder', '', '文章分类排序', ''),
(137, 1, 'portal', 'admin_url', 'portal/AdminCategory/delete', '', '删除文章分类', ''),
(138, 1, 'portal', 'admin_url', 'portal/AdminIndex/default', '', '门户管理', ''),
(139, 1, 'portal', 'admin_url', 'portal/AdminPage/index', '', '页面管理', ''),
(140, 1, 'portal', 'admin_url', 'portal/AdminPage/add', '', '添加页面', ''),
(141, 1, 'portal', 'admin_url', 'portal/AdminPage/addPost', '', '添加页面提交', ''),
(142, 1, 'portal', 'admin_url', 'portal/AdminPage/edit', '', '编辑页面', ''),
(143, 1, 'portal', 'admin_url', 'portal/AdminPage/editPost', '', '编辑页面提交', ''),
(144, 1, 'portal', 'admin_url', 'portal/AdminPage/delete', '', '删除页面', ''),
(145, 1, 'portal', 'admin_url', 'portal/AdminTag/index', '', '文章标签', ''),
(146, 1, 'portal', 'admin_url', 'portal/AdminTag/add', '', '添加文章标签', ''),
(147, 1, 'portal', 'admin_url', 'portal/AdminTag/addPost', '', '添加文章标签提交', ''),
(148, 1, 'portal', 'admin_url', 'portal/AdminTag/upStatus', '', '更新标签状态', ''),
(149, 1, 'portal', 'admin_url', 'portal/AdminTag/delete', '', '删除文章标签', ''),
(150, 1, 'user', 'admin_url', 'user/AdminAsset/index', '', '资源管理', ''),
(151, 1, 'user', 'admin_url', 'user/AdminAsset/delete', '', '删除文件', ''),
(152, 1, 'member', 'admin_url', 'member/user/empty', '', '运营管理', ''),
(153, 1, 'member', 'admin_url', 'member/user/userinfo', '', '用户列表', ''),
(154, 1, 'user', 'admin_url', 'user/AdminIndex/index', '', '本站用户', ''),
(155, 1, 'user', 'admin_url', 'user/AdminIndex/ban', '', '本站用户拉黑', ''),
(156, 1, 'user', 'admin_url', 'user/AdminIndex/cancelBan', '', '本站用户启用', ''),
(157, 1, 'user', 'admin_url', 'user/AdminOauth/index', '', '第三方用户', ''),
(158, 1, 'user', 'admin_url', 'user/AdminOauth/delete', '', '删除第三方用户绑定', ''),
(159, 1, 'user', 'admin_url', 'user/AdminUserAction/index', '', '用户操作管理', ''),
(160, 1, 'user', 'admin_url', 'user/AdminUserAction/edit', '', '编辑用户操作', ''),
(161, 1, 'user', 'admin_url', 'user/AdminUserAction/editPost', '', '编辑用户操作提交', ''),
(162, 1, 'user', 'admin_url', 'user/AdminUserAction/sync', '', '同步用户操作', ''),
(163, 1, 'admin', 'admin_url', 'admin/main/default', '', '后台首页', ''),
(164, 1, 'admin', 'admin_url', 'admin/Actionlog/index', '', '行为日志', ''),
(165, 1, 'admin', 'admin_url', 'admin/Config/index', '', '配置管理', ''),
(166, 1, 'extend', 'admin_url', 'extend/AdminIndex/default', '', '扩展工具', ''),
(167, 1, 'extend', 'admin_url', 'extend/admin_msg/index', '', '短信设置', ''),
(168, 1, 'recharge', 'admin_url', 'recharge/default/default', '', '财务管理', ''),
(169, 1, 'recharge', 'admin_url', 'recharge/spend/defualt1', '', '订单管理', ''),
(170, 1, 'recharge', 'admin_url', 'recharge/spend/lists', '', '游戏订单', ''),
(171, 1, 'member', 'admin_url', 'member/user/age', '', '实名认证', ''),
(172, 1, 'recharge', 'admin_url', 'recharge/spend/summary', '', '充值汇总', ''),
(173, 1, 'member', 'admin_url', 'member/wechat/index', '', '公众号设置', ''),
(174, 1, 'recharge', 'admin_url', 'recharge/Ptbspend/default', '', '平台币管理', ''),
(175, 1, 'recharge', 'admin_url', 'recharge/Ptbspend/lists', '', '充值', ''),
(176, 1, 'member', 'admin_url', 'member/thirdlogin/qq_thirdparty', '', '第三方登录', ''),
(177, 1, 'recharge', 'admin_url', 'recharge/Ptbspend/index', '', '后台发放（玩家）', ''),
(178, 1, 'recharge', 'admin_url', 'recharge/Ptbspend/senduserlists', '', '玩家发放', ''),
(179, 1, 'recharge', 'admin_url', 'recharge/Ptbspend/senduser', '', '给玩家发放', ''),
(180, 1, 'member', 'admin_url', 'member/mend/index', '', '补链记录', ''),
(181, 1, 'game', 'admin_url', 'game/default/default', '', '游戏管理', ''),
(182, 1, 'game', 'admin_url', 'game/game/lists', '', '游戏列表', ''),
(183, 1, 'game', 'admin_url', 'game/gametype/lists', '', '游戏类型', ''),
(184, 1, 'game', 'admin_url', 'game/server/lists', '', '开服表', ''),
(185, 1, 'recharge', 'admin_url', 'recharge/Ptbpromote/sendpromotelists', '', '渠道发放', ''),
(186, 1, 'recharge', 'admin_url', 'recharge/Ptbpromote/sendpromote', '', '给推广员发放', ''),
(187, 1, 'recharge', 'admin_url', 'recharge/Ptbpromote/get_promote_coin', '', '查询余额', ''),
(188, 1, 'recharge', 'admin_url', 'recharge/paytype/lists', '', '支付设置', ''),
(189, 1, 'game', 'admin_url', 'game/giftbag/lists', '', '礼包列表', ''),
(190, 1, 'member', 'admin_url', 'member/user/login_record', '', '登录记录', ''),
(191, 1, 'member', 'admin_url', 'member/user/role', '', '角色查询', ''),
(192, 1, 'game', 'admin_url', 'game/gamesource/lists', '', '原包管理', ''),
(193, 1, 'promote', 'admin_url', 'promote/PromoteIndex/default', '', '推广平台', ''),
(194, 1, 'promote', 'admin_url', 'promote/promote/lists', '', '渠道列表', ''),
(196, 1, 'promote', 'admin_url', 'promote/promoteapply/lists', '', '游戏分包', ''),
(197, 1, 'promote', 'admin_url', 'promote/promoteunion/lists', '', '联盟申请', ''),
(198, 1, 'site', 'admin_url', 'site/site/default', '', '站点管理', ''),
(199, 1, 'site', 'admin_url', 'site/site/sdk', '', '双SDK', ''),
(200, 1, 'site', 'admin_url', 'site/site/sdk_set', '', '手游SDK', ''),
(201, 1, 'site', 'admin_url', 'site/site/wap', '', 'WAP站', ''),
(202, 1, 'site', 'admin_url', 'site/site/wap_set', '', 'WAP站', ''),
(203, 1, 'site', 'admin_url', 'site/adv/sdk_pos', '', '广告管理', ''),
(204, 1, 'site', 'admin_url', 'site/notice/lists', '', '系统公告', ''),
(205, 1, 'site', 'admin_url', 'site/guess/lists', '', '猜你喜欢', ''),
(206, 1, 'member', 'admin_url', 'member/alipay/lists', '', '支付宝快捷认证', ''),
(207, 1, 'member', 'admin_url', 'member/user/vip_set', '', 'VIP设置', ''),
(208, 1, 'site', 'admin_url', 'site/seo/wap_seo', '', 'SEO设置', ''),
(209, 1, 'site', 'admin_url', 'site/adv/wap_pos', '', '广告管理', ''),
(210, 1, 'admin', 'admin_url', 'admin/category/index', '', '文章分类', ''),
(211, 1, 'site', 'admin_url', 'site/article/lists', 'category=2', '资讯管理', ''),
(212, 1, 'site', 'admin_url', 'site/article/lists_doc', 'category=7', '文档管理', ''),
(213, 1, 'site', 'admin_url', 'site/site/media', '', 'PC官网', ''),
(214, 1, 'site', 'admin_url', 'site/site/media_set', '', 'PC官网', ''),
(215, 1, 'promote', 'admin_url', 'promote/dataManage/empty', '', '数据管理', ''),
(216, 1, 'promote', 'admin_url', 'promote/dataManage/ch_reg_list', '', '实时注册', ''),
(217, 1, 'promote', 'admin_url', 'promote/dataManage/spend_list', '', '实时充值', ''),
(218, 1, 'promote', 'admin_url', 'promote/dataManage/coinTransfer', '', '转移记录', ''),
(219, 1, 'promote', 'admin_url', 'promote/dataManage/coinRecord', '', '渠道充值', ''),
(220, 1, 'site', 'admin_url', 'site/seo/media_seo', '', 'SEO设置', ''),
(221, 1, 'site', 'admin_url', 'site/adv/adv_pos', '', '广告管理', ''),
(222, 1, 'site', 'admin_url', 'site/link/lists', '', '友情链接', ''),
(223, 1, 'site', 'admin_url', 'site/kefu/lists', '', '帮助中心', ''),
(224, 1, 'site', 'admin_url', 'site/article/media', 'category=7', '活动资讯', ''),
(225, 1, 'site', 'admin_url', 'site/article/media_doc', 'category=11', '文档管理', ''),
(226, 1, 'site', 'admin_url', 'site/site/promote', '', '推广平台', ''),
(227, 1, 'site', 'admin_url', 'site/site/promote_set', '', '推广平台', ''),
(228, 1, 'site', 'admin_url', 'site/seo/promote_seo', '', 'SEO设置', ''),
(229, 1, 'site', 'admin_url', 'site/adv/promote_pos', '', '广告管理', ''),
(230, 1, 'site', 'admin_url', 'site/article/promote', 'category=13', '公告资讯', ''),
(231, 1, 'site', 'admin_url', 'site/article/promote_doc', '', '文档管理', ''),
(232, 1, 'site', 'admin_url', 'site/site/admin', '', '站点配置', ''),
(233, 1, 'site', 'admin_url', 'site/site/admin_set', '', '管理后台', ''),
(234, 1, 'promote', 'admin_url', 'promote/settlement/empty', '', '渠道结算', ''),
(235, 1, 'promote', 'admin_url', 'promote/settlement/promote_settlement', '', '渠道结算', ''),
(236, 1, 'promote', 'admin_url', 'promote/settlement/promote_withdraw', '', '收益提现', ''),
(237, 1, 'admin', 'admin_url', 'admin/setting/sms', '', '短信设置', ''),
(240, 1, 'recharge', 'admin_url', 'recharge/ptbdeduct/lists', '', '收回', ''),
(241, 1, 'member', 'admin_url', 'member/user/balance_update_lists', '', '修改记录', ''),
(242, 1, 'admin', 'admin_url', 'admin/Category/add', '', '添加文章分类', ''),
(243, 1, 'admin', 'admin_url', 'admin/Category/addPost', '', '添加文章分类提交', ''),
(244, 1, 'admin', 'admin_url', 'admin/Category/edit', '', '编辑文章分类', ''),
(245, 1, 'admin', 'admin_url', 'admin/Category/editPost', '', '编辑文章分类提交', ''),
(246, 1, 'admin', 'admin_url', 'admin/Category/select', '', '文章分类选择对话框', ''),
(247, 1, 'admin', 'admin_url', 'admin/Category/listOrder', '', '文章分类排序', ''),
(248, 1, 'admin', 'admin_url', 'admin/Category/toggle', '', '文章分类显示隐藏', ''),
(249, 1, 'admin', 'admin_url', 'admin/Category/delete', '', '删除文章分类', ''),
(250, 1, 'datareport', 'admin_url', 'datareport/user/default', '', '数据报表', ''),
(251, 1, 'datareport', 'admin_url', 'datareport/User/default1', '', '用户数据', ''),
(252, 1, 'datareport', 'admin_url', 'datareport/User/basedata', '', '日报数据', ''),
(253, 1, 'member', 'admin_url', 'member/user/manage', '', '用户管理', ''),
(254, 1, 'member', 'admin_url', 'member/user/config', '', '用户设置', ''),
(255, 1, 'site', 'admin_url', 'site/site/default1', '', '站点配置', ''),
(256, 1, 'datareport', 'admin_url', 'datareport/game/default', '', '游戏数据', ''),
(257, 1, 'datareport', 'admin_url', 'datareport/game/game_data', '', '游戏数据', ''),
(258, 1, 'site', 'admin_url', 'site/site/empty', '', '其他配置', ''),
(259, 1, 'datareport', 'admin_url', 'datareport/game/game_survey', '', '游戏概况', ''),
(260, 1, 'datareport', 'admin_url', 'datareport/promote/default', '', '渠道数据', ''),
(261, 1, 'datareport', 'admin_url', 'datareport/promote/promote_data', '', '渠道数据', ''),
(262, 1, 'site', 'admin_url', 'site/link/empty', '', '友情链接', ''),
(263, 1, 'site', 'admin_url', 'site/kefutype/lists', '', '问题分类', ''),
(264, 1, 'promote', 'admin_url', 'promote/settlement/promote_exchange', '', '收益兑换', ''),
(265, 1, 'promote', 'admin_url', 'promote/settlement/promote_summary', '', '结算汇总', ''),
(266, 1, 'datareport', 'admin_url', 'datareport/promote/promote_rank', '', '渠道排行榜', ''),
(267, 1, 'datareport', 'admin_url', 'datareport/User/default2', '', '数据分析', ''),
(268, 1, 'datareport', 'admin_url', 'datareport/User/user_analysis', '', '用户分析', ''),
(269, 1, 'datareport', 'admin_url', 'datareport/User/register_retain', '', '留存统计', ''),
(270, 1, 'admin', 'admin_url', 'admin/actionlog/clear', '', '清空', ''),
(271, 1, 'admin', 'admin_url', 'admin/actionlog/delete', '', '删除', ''),
(272, 1, 'extend', 'admin_url', 'extend/admin_msg/dosave', '', '保存短信设置', ''),
(273, 1, 'member', 'admin_url', 'member/mend/add', '', '补链', ''),
(274, 1, 'member', 'admin_url', 'member/user/ban', '', '锁定解锁', ''),
(275, 1, 'member', 'admin_url', 'member/user/edit', '', '查看', ''),
(276, 1, 'member', 'admin_url', 'member/user/balance_edit', '', '修改用户平台币', ''),
(277, 1, 'member', 'admin_url', 'member/user/changephone', '', '修改用户手机号', ''),
(278, 1, 'member', 'admin_url', 'member/wechat/save_tool', '', '保存设置', ''),
(279, 1, 'member', 'admin_url', 'member/thirdlogin/wx_thirdparty', '', '微信参数', ''),
(280, 1, 'member', 'admin_url', 'member/thirdlogin/wx_param_lists', '', '单个游戏微信参数', ''),
(281, 1, 'member', 'admin_url', 'member/thirdlogin/qq_param_lists', '', '单个游戏QQ参数', ''),
(282, 1, 'member', 'admin_url', 'member/thirdlogin/add_qq', '', '新增qq参数', ''),
(283, 1, 'member', 'admin_url', 'member/thirdlogin/edit_qq', '', '编辑qq参数', ''),
(284, 1, 'member', 'admin_url', 'member/thirdlogin/del', '', '删除', ''),
(285, 1, 'member', 'admin_url', 'member/user/sitepost', '', '保存', ''),
(286, 1, 'game', 'admin_url', 'game/game/add', '', '新增', ''),
(287, 1, 'game', 'admin_url', 'game/game/edit', '', '编辑', ''),
(288, 1, 'game', 'admin_url', 'game/game/setgamedatafield', '', '修改排序、下载次数', ''),
(289, 1, 'game', 'admin_url', 'game/game/get_game_set', '', '查看对接参数', ''),
(290, 1, 'game', 'admin_url', 'game/game_type/add', '', '新增游戏类型', ''),
(291, 1, 'game', 'admin_url', 'game/game_type/edit', '', '编辑游戏类型', ''),
(292, 1, 'game', 'admin_url', 'game/game_type/setstatus', '', '修改类型状态', ''),
(293, 1, 'game', 'admin_url', 'game/gamesource/add', '', '上传原包', ''),
(294, 1, 'game', 'admin_url', 'game/gamesource/edit', '', '更新原包', ''),
(295, 1, 'game', 'admin_url', 'game/gamesource/del', '', '删除原包', ''),
(296, 1, 'game', 'admin_url', 'game/server/add', '', '新增区服', ''),
(297, 1, 'game', 'admin_url', 'game/server/edit', '', '编辑区服', ''),
(298, 1, 'game', 'admin_url', 'game/server/del', '', '删除区服', ''),
(299, 1, 'game', 'admin_url', 'game/server/batch', '', '批量新增区服', ''),
(300, 1, 'game', 'admin_url', 'game/giftbag/record', '', '领取记录', ''),
(301, 1, 'game', 'admin_url', 'game/giftbag/add', '', '新增礼包', ''),
(302, 1, 'game', 'admin_url', 'game/giftbag/edit', '', '编辑礼包', ''),
(303, 1, 'game', 'admin_url', 'game/giftbag/del', '', '删除礼包', ''),
(304, 1, 'recharge', 'admin_url', 'recharge/spend/repair', '', '补单', ''),
(305, 1, 'recharge', 'admin_url', 'recharge/ptbspend/recharge', '', '充值', ''),
(306, 1, 'recharge', 'admin_url', 'recharge/paytype/set_pay', '', '保存设置', ''),
(307, 1, 'promote', 'admin_url', 'promote/settlement/changestatus', '', '收益状态', ''),
(308, 1, 'promote', 'admin_url', 'promote/settlement/generatesettlementall', '', '确认结算', ''),
(309, 1, 'promote', 'admin_url', 'promote/settlement/promote_settlement_already', '', '已结算订单', ''),
(310, 1, 'promote', 'admin_url', 'promote/settlement/promote_settlement_never', '', '不参与结算订单', ''),
(311, 1, 'promote', 'admin_url', 'promote/settlement/changecheckstatus', '', '参与状态', ''),
(312, 1, 'promote', 'admin_url', 'promote/promote/changestatus', '', '修改状态', ''),
(313, 1, 'promote', 'admin_url', 'promote/promote/add', '', '新增渠道', ''),
(314, 1, 'promote', 'admin_url', 'promote/promote/set_config_auto_audit', '', '自动审核', ''),
(315, 1, 'promote', 'admin_url', 'promote/promote/edit', '', '编辑渠道', ''),
(316, 1, 'promote', 'admin_url', 'promote/promote/getpromotegame', '', '查看可推广游戏', ''),
(317, 1, 'promote', 'admin_url', 'promote/promoteunion/changestatus', '', '修改联盟状态', ''),
(318, 1, 'promote', 'admin_url', 'promote/promoteunion/set_config_auto_audit_union', '', '自动审核', ''),
(319, 1, 'promote', 'admin_url', 'promote/promoteapply/changestatus', '', '审核', ''),
(320, 1, 'promote', 'admin_url', 'promote/promoteapply/allpackage', '', '打包', ''),
(321, 1, 'site', 'admin_url', 'site/site/sitepost', '', '保存站点配置', ''),
(322, 1, 'site', 'admin_url', 'site/notice/add', '', '新增公告', ''),
(323, 1, 'site', 'admin_url', 'site/notice/edit', '', '编辑公告', ''),
(324, 1, 'site', 'admin_url', 'site/notice/del', '', '删除公告', ''),
(325, 1, 'site', 'admin_url', 'site/guess/add', '', '猜你喜欢新增链接', ''),
(326, 1, 'site', 'admin_url', 'site/guess/edit', '', '猜你喜欢编辑链接', ''),
(327, 1, 'site', 'admin_url', 'site/guess/del', '', '猜你喜欢删除链接', ''),
(328, 1, 'site', 'admin_url', 'site/article/add', '', '新增文章', ''),
(329, 1, 'site', 'admin_url', 'site/article/delete', '', '删除文章', ''),
(330, 1, 'site', 'admin_url', 'site/article/edit', '', '编辑文章', ''),
(331, 1, 'site', 'admin_url', 'site/article/sethits', '', '文章浏览量设置', ''),
(332, 1, 'site', 'admin_url', 'site/seo/save', '', 'seo保存', ''),
(333, 1, 'site', 'admin_url', 'site/adv/edit', '', '设置广告位', ''),
(334, 1, 'site', 'admin_url', 'site/adv/add_adv', '', '新增广告', ''),
(335, 1, 'site', 'admin_url', 'site/adv/adv_adv', '', '广告列表', ''),
(336, 1, 'site', 'admin_url', 'site/link/add', '', '新增友链', ''),
(337, 1, 'site', 'admin_url', 'site/link/edit', '', '编辑友链', ''),
(338, 1, 'site', 'admin_url', 'site/link/del', '', '删除友链', ''),
(339, 1, 'site', 'admin_url', 'site/kefu/add', '', '新增问题', ''),
(340, 1, 'site', 'admin_url', 'site/kefu/edit', '', '编辑问题', ''),
(341, 1, 'site', 'admin_url', 'site/kefu/del', '', '删除问题', ''),
(342, 1, 'site', 'admin_url', 'site/kefutype/add', '', '新增分类', ''),
(343, 1, 'site', 'admin_url', 'site/kefutype/edit', '', '编辑分类', ''),
(344, 1, 'site', 'admin_url', 'site/kefutype/del', '', '删除分类', ''),
(345, 1, 'user', 'admin_url', 'user/asset/webuploader', '', '上传文件', ''),
(346, 1, 'game', 'admin_url', 'game/comment/lists', '', '评论列表 ', ''),
(347, 1, 'member', 'admin_url', 'member/mcard/lists', '', '尊享卡', ''),
(348, 1, 'game', 'admin_url', 'game/comment/changestatus', '', '审核', ''),
(349, 1, 'game', 'admin_url', 'game/comment/set_config_auto_audit', '', '自动审核', ''),
(350, 1, 'member', 'admin_url', 'member/pointshop/default', '', '积分商城', ''),
(351, 1, 'member', 'admin_url', 'member/invitation/record', '', '邀请奖励', ''),
(352, 1, 'member', 'admin_url', 'member/Tplay/task', '', '试玩任务', ''),
(353, 1, 'member', 'admin_url', 'member/point/task', '', '积分商城', ''),
(354, 1, 'member', 'admin_url', 'member/point/point_record', '', '积分明细', ''),
(355, 1, 'member', 'admin_url', 'member/transaction/default', '', '小号交易', ''),
(356, 1, 'member', 'admin_url', 'member/transaction/lists', '', '小号商品', ''),
(357, 1, 'member', 'admin_url', 'member/transaction/order', '', '小号订单', ''),
(358, 1, 'site', 'admin_url', 'site/site/transaction_set', '', '卖号设置', ''),
(359, 1, 'datareport', 'admin_url', 'datareport/device/survey', '', '应用概况', ''),
(360, 1, 'recharge', 'admin_url', 'recharge/bindspend/default', '', '绑币管理', ''),
(361, 1, 'recharge', 'admin_url', 'recharge/Bindspend/lists', '', '充值', ''),
(362, 1, 'recharge', 'admin_url', 'recharge/Bindspend/senduserlists', '', '发放', ''),
(363, 1, 'recharge', 'admin_url', 'recharge/Binddeduct/lists', '', '收回', ''),
(364, 1, 'recharge', 'admin_url', 'recharge/rebate/default', '', '返利折扣', ''),
(365, 1, 'recharge', 'admin_url', 'recharge/rebate/lists', '', '充值返利', ''),
(366, 1, 'recharge', 'admin_url', 'recharge/rebate/welfare', '', '首充续充', ''),
(367, 1, 'recharge', 'admin_url', 'recharge/rebate/agent', '', '会长代充', ''),
(368, 1, 'recharge', 'admin_url', 'recharge/coupon/default', '', '代金券管理', ''),
(369, 1, 'recharge', 'admin_url', 'recharge/coupon/lists', '', '代金券', ''),
(370, 1, 'recharge', 'admin_url', 'recharge/coupon/record', '', '代金券领取', ''),
(371, 1, 'promote', 'admin_url', 'promote/promoteapply/app_list', '', 'APP分包', ''),
(372, 1, 'business', 'admin_url', 'business/Business/default', '', '商务专员', ''),
(373, 1, 'business', 'admin_url', 'business/Business/lists', '', '商务列表', ''),
(374, 1, 'business', 'admin_url', 'business/Business/add', '', '新增', ''),
(375, 1, 'business', 'admin_url', 'business/Business/edit', '', '编辑', ''),
(376, 1, 'business', 'admin_url', 'business/Business/changeStatus', '', '锁定', ''),
(377, 1, 'issueh5', 'admin_url', 'issueh5/index/default', '', '联运分发', ''),
(378, 1, 'issue', 'admin_url', 'issue/user/lists', '', '平台用户', ''),
(379, 1, 'issue', 'admin_url', 'issue/User/add', '', '新增', ''),
(380, 1, 'issue', 'admin_url', 'issue/User/edit', '', '编辑', ''),
(381, 1, 'issue', 'admin_url', 'issue/User/changestatus', '', '修改状态', ''),
(382, 1, 'issue', 'admin_url', 'issue/Platform/lists', '', '平台列表', ''),
(383, 1, 'issue', 'admin_url', 'issue/Platform/add', '', '新增', ''),
(384, 1, 'issue', 'admin_url', 'issue/Platform/edit', '', '编辑', ''),
(385, 1, 'issue', 'admin_url', 'issue/Platform/changestatus', '', '修改状态', ''),
(386, 1, 'issue', 'admin_url', 'issue/Platform/saveplatformgame', '', '禁止申请游戏', ''),
(387, 1, 'issue', 'admin_url', 'issue/Platform/getplatformgame', '', '获取游戏列表', ''),
(388, 1, 'issue', 'admin_url', 'issue/Apply/lists', '', '联运申请', ''),
(389, 1, 'issue', 'admin_url', 'issue/Apply/gameconfig', '', '获取游戏配置', ''),
(390, 1, 'issue', 'admin_url', 'issue/Apply/changefield', '', '修改字段值', ''),
(391, 1, 'issue', 'admin_url', 'issue/Apply/audit', '', '审核', ''),
(392, 1, 'issue', 'admin_url', 'issue/game/lists', '', '联运游戏', ''),
(393, 1, 'issueh5', 'admin_url', 'issueh5/AdminGame/edit', '', '编辑', ''),
(394, 1, 'issueh5', 'admin_url', 'issueh5/AdminGame/xt_lists', '', '获取游戏列表', ''),
(395, 1, 'issueh5', 'admin_url', 'issueh5/AdminGame/add', '', '添加游戏', ''),
(396, 1, 'issueh5', 'admin_url', 'issueh5/AdminGame/delete', '', '删除游戏', ''),
(397, 1, 'issueh5', 'admin_url', 'issueh5/AdminGame/changestatus', '', '修改状态', ''),
(398, 1, 'issuesy', 'admin_url', 'issuesy/AdminGame/edit', '', '编辑', ''),
(399, 1, 'issuesy', 'admin_url', 'issuesy/AdminGame/xt_lists', '', '获取游戏列表', ''),
(400, 1, 'issuesy', 'admin_url', 'issuesy/AdminGame/add', '', '添加游戏', ''),
(401, 1, 'issuesy', 'admin_url', 'issuesy/AdminGame/delete', '', '删除游戏', ''),
(402, 1, 'issuesy', 'admin_url', 'issuesy/AdminGame/changestatus', '', '修改状态', ''),
(403, 1, 'issue', 'admin_url', 'issue/User/register', '', '玩家注册', ''),
(404, 1, 'issue', 'admin_url', 'issue/User/changeUserStatus', '', '修改玩家状态', ''),
(405, 1, 'issue', 'admin_url', 'issue/User/recharge', '', '游戏充值', ''),
(406, 1, 'issue', 'admin_url', 'issue/User/paySummary', '', '充值汇总', ''),
(407, 1, 'site', 'admin_url', 'site/site/app_set', '', '盒子APP', ''),
(408, 1, 'site', 'admin_url', 'site/site/kefu_set', '', '客服信息', ''),
(409, 1, 'site', 'admin_url', 'site/site/business_set', '', '商务后台', ''),
(410, 1, 'site', 'admin_url', 'site/site/issue_set', '', '联运分发配置', ''),
(411, 1, 'upgrade', 'admin_url', 'upgrade/index/index', '', '系统更新', ''),
(412, 1, 'member', 'admin_url', 'member/mcard/set', '', '尊享卡设置', ''),
(413, 1, 'member', 'admin_url', 'member/warning/lists', '', '异常预警', ''),
(414, 1, 'game', 'admin_url', 'game/Interface/lists', '', '页游接口', ''),
(415, 1, 'game', 'admin_url', 'game/interface/add', '', '新增', ''),
(416, 1, 'game', 'admin_url', 'game/interface/edit', '', '编辑', ''),
(417, 1, 'game', 'admin_url', 'game/interface/del', '', '删除', ''),
(418, 1, 'promote', 'admin_url', 'promote/Support/lists', '', '扶持发放', ''),
(419, 1, 'promote', 'admin_url', 'promote/Support/audit', '', '审核扶持', ''),
(420, 1, 'promote', 'admin_url', 'promote/Support/send', '', '发放扶持', ''),
(421, 1, 'promote', 'admin_url', 'promote/Support/deny', '', '拒绝扶持', ''),
(422, 1, 'promote', 'admin_url', 'promote/Support/changeAutoAudit', '', '自动审核', ''),
(423, 1, 'game', 'admin_url', 'game/Cp/lists', '', 'CP列表', ''),
(424, 1, 'promote', 'admin_url', 'promote/promotecps/lists', '', '结算批量设置', ''),
(425, 1, 'promote', 'admin_url', 'promote/promotecpa/lists', '', 'CPA单价设置', ''),
(426, 1, 'game', 'admin_url', 'game/Game/addSupserVersion', '', '更新超级签版本号', ''),
(427, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/index', '', 'BT福利管理', ''),
(428, 1, 'btwelfare', 'admin_url', 'btwelfare/Register/lists', '', '注册福利', ''),
(429, 1, 'btwelfare', 'admin_url', 'btwelfare/Recharge/lists', '', '充值福利', ''),
(430, 1, 'btwelfare', 'admin_url', 'btwelfare/Total/lists', '', '累充福利', ''),
(431, 1, 'btwelfare', 'admin_url', 'btwelfare/Monthcard/lists', '', '月卡福利', ''),
(432, 1, 'btwelfare', 'admin_url', 'btwelfare/weekcard/lists', '', '周卡福利', ''),
(433, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/setting', '', '福利设置', ''),
(434, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/lists', '', '道具管理', ''),
(435, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/add', '', '添加道具', ''),
(436, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/edit', '', '编辑道具', ''),
(437, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/del', '', '删除道具', ''),
(438, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/changeStatus', '', '修改状态', ''),
(439, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/add', '', '新增福利', ''),
(440, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/edit', '', '编辑福利', ''),
(441, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/del', '', '删除福利', ''),
(442, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/ajaxGetPromote', '', '获取推广员列表', ''),
(443, 1, 'btwelfare', 'admin_url', 'btwelfare/Bt/changestatus', '', '修改状态', ''),
(444, 1, 'btwelfare', 'admin_url', 'btwelfare/Prop/getGameProp', '', '获取游戏下道具列表', ''),
(445, 1, 'promote', 'admin_url', 'promote/promote/prepayment_record', '', '渠道预付款', ''),
(446, 1, 'promote', 'admin_url', 'promote/promote/prepayment_deduct_record', '', '渠道预付款消费记录', ''),
(447, 1, 'promote', 'admin_url', 'promote/promote/sendPrepaymentRecord', '', '渠道预付款发放记录', ''),
(448, 1, 'promote', 'admin_url', 'promote/promote/sendPrepayment', '', '发放预付款', ''),
(449, 1, 'recharge', 'admin_url', 'recharge/Spend/getNotifyInfo', '', '获取游戏回调信息', ''),
(450, 1, 'oa', 'admin_url', 'oa/Oa/default', '', '工作室管理', ''),
(451, 1, 'oa', 'admin_url', 'oa/Studio/lists', '', '工作室列表', ''),
(452, 1, 'oa', 'admin_url', 'oa/Studio/add', '', '添加工作室', ''),
(453, 1, 'oa', 'admin_url', 'oa/Studio/edit', '', '编辑工作室', ''),
(454, 1, 'oa', 'admin_url', 'oa/Studio/del', '', '删除工作室', ''),
(455, 1, 'site', 'admin_url', 'site/site/sdkw_set', '', '海外SDK', ''),
(456, 1, 'site', 'admin_url', 'site/protocol/lists', '', '用户注册协议', ''),
(457, 1, 'site', 'admin_url', 'site/protocol/add', '', '新增用户注册协议', ''),
(458, 1, 'site', 'admin_url', 'site/protocol/edit', '', '编辑用户注册协议', ''),
(459, 1, 'site', 'admin_url', 'site/protocol/del', '', '删除用户注册协议', ''),
(460, 1, 'admin', 'admin_url', 'admin/main/index', '', '后台首页', ''),
(461, 1, 'member', 'admin_url', 'member/user/feedback', '', '投诉反馈', ''),
(462, 1, 'member', 'admin_url', 'member/user/feedbackremark', '', '修改备注', ''),
(463, 1, 'admin', 'admin_url', 'admin/setting/system', '', '系统设置', ''),
(464, 1, 'site', 'admin_url', 'site/kefu/setsort', '', '修改排序', ''),
(465, 1, 'site', 'admin_url', 'site/adv/get_adv_pos_lists', '', '获取广告位列表', ''),
(466, 1, 'member', 'admin_url', 'member/user/changeemail', '', '修改用户邮箱', ''),
(467, 1, 'issue', 'admin_url', 'issue/UserBalance/lists', '', '联运币充值订单', ''),
(468, 1, 'recharge', 'admin_url', 'recharge/spend/bingLog', '', '订单绑定', ''),
(469, 1, 'member', 'admin_url', 'member/user/default2', '', '用户设置', ''),
(470, 1, 'recharge', 'admin_url', 'recharge/paytype/default', '', '支付设置', ''),
(471, 1, 'promote', 'admin_url', 'promote/promote/default', '', '渠道管理', ''),
(472, 1, 'admin', 'admin_url', 'admin/other/default', '', '其他', ''),
(473, 1, 'promote', 'admin_url', 'promote/promoteunion/view_detail', '', '渠道申请信息', ''),
(474, 1, 'site', 'admin_url', 'site/site/sdksimplify_set', '', '精简SDK', ''),
(475, 1, 'recharge', 'admin_url', 'recharge/ptbspend/set_ptb_send_quota', '', '发放限额配置', ''),
(476, 1, 'recharge', 'admin_url', 'recharge/ptbspend/set_ptb_channel_send_quota', '', '发放限额配置', ''),
(477, 1, 'member', 'admin_url', 'member/user/safecenter', '', '安全中心', ''),
(478, 1, 'recharge', 'admin_url', 'recharge/CashOut/default', '', '提现管理', ''),
(479, 1, 'site', 'admin_url', 'site/site/ptb_cash_out_set', '', '提现配置', ''),
(480, 1, 'recharge', 'admin_url', 'recharge/CashOut/lists', '', '提现列表', ''),
(481, 1, 'member', 'admin_url', 'member/user/batchCreate', '', '批量创建用户', ''),
(482, 1, 'promote', 'admin_url', 'promote/settlement/promote_period_settlement', '', '结算打款', ''),
(483, 1, 'member', 'admin_url', 'member/user/promoteGame', '', '获取推广员已申请游戏', ''),
(484, 1, 'member', 'admin_url', 'member/user/userstage', '', '玩家阶段', ''),
(485, 1, 'game', 'admin_url', 'game/game/changeGameName', '', '修改游戏名称', ''),
(486, 1, 'game', 'admin_url', 'game/game/changeGameNameLog', '', '修改游戏名称记录', ''),
(487, 1, 'member', 'admin_url', 'member/user/setuserstage', '', '设置玩家阶段', ''),
(488, 1, 'member', 'admin_url', 'member/user/edit_follow_remind', '', '编辑跟进提醒', ''),
(489, 1, 'game', 'admin_url', 'game/gamesource/batch_del', '', '批量删除', ''),
(490, 1, 'member', 'admin_url', 'member/user/change_user_score', '', '修改玩家评分', ''),
(491, 1, 'member', 'admin_url', 'member/user/change_user_stage_remark', '', '修改用户阶段备注', ''),
(492, 1, 'member', 'admin_url', 'member/user/delete_stage', '', '删除阶段', ''),
(493, 1, 'member', 'admin_url', 'member/user/do_delete_stage', '', '执行删除操作', ''),
(494, 1, 'member', 'admin_url', 'member/user/edit_stage', '', '编辑阶段管理', ''),
(495, 1, 'member', 'admin_url', 'member/user/add_stage', '', '添加/编辑阶段管理', ''),
(496, 1, 'member', 'admin_url', 'member/user/change_stage_order', '', '用户阶段更改顺序', ''),
(497, 1, 'member', 'admin_url', 'member/user/add_user_stage', '', '未进入阶段的玩家', ''),
(502, 1, 'issue', 'admin_url', 'issue/Channel/lists', '', '渠道大全', ''),
(503, 1, 'issue', 'admin_url', 'issue/Data/overview', '', '数据总览', ''),
(504, 1, 'issue', 'admin_url', 'issue/Data/daily', '', '日报数据', ''),
(505, 1, 'issue', 'admin_url', 'issue/Data/dailyHour', '', '日报表(时)', ''),
(516, 1, 'issue', 'admin_url', 'issue/User/detail', '', '用户详情', ''),
(517, 1, 'issue', 'admin_url', 'issue/User/activationRecord', '', '激活游戏记录', ''),
(518, 1, 'issue', 'admin_url', 'issue/User/loginRecord', '', '近期登陆记录', ''),
(519, 1, 'issue', 'admin_url', 'issue/User/spendRecord', '', '用户付费记录', ''),
(520, 1, 'issue', 'admin_url', 'issue/User/bind', '', '用户绑定', ''),
(521, 1, 'recharge', 'admin_url', 'recharge/rebate/bind_discount', '', '绑币充值', ''),
(522, 1, 'game', 'admin_url', 'game/Interflow/lists', '', '游戏互通', ''),
(523, 1, 'game', 'admin_url', 'game/Interflow/add', '', '添加游戏互通', ''),
(524, 1, 'game', 'admin_url', 'game/Interflow/append', '', '追加互通游戏', ''),
(525, 1, 'game', 'admin_url', 'game/Interflow/delete', '', '删除游戏互通', ''),
(526, 1, 'issue', 'admin_url', 'issue/Channel/apply', '', '渠道申请接入', ''),
(527, 1, 'issue', 'admin_url', 'issue/Channel/delete', '', '删除已接入渠道', ''),
(528, 1, 'member', 'admin_url', 'member/user/user_data_analyze', '', '玩家画像', ''),
(529, 1, 'cp', 'admin_url', 'cp/settlement/empty', '', 'CP结算', ''),
(530, 1, 'cp', 'admin_url', 'cp/settlement/game_settlement', '', '游戏结算', ''),
(531, 1, 'cp', 'admin_url', 'cp/settlement/settlement_record', '', '结算记录', ''),
(532, 1, 'cp', 'admin_url', 'cp/settlement/remit_record', '', '打款记录', ''),
(533, 1, 'cp', 'admin_url', 'cp/settlement/create_settlement', '', '创建打款', ''),
(536, 1, 'game', 'admin_url', 'game/game/downDockingFile', '', '下载游戏参数对接文件', '');

-- --------------------------------------------------------

--
-- 表的结构 `sys_comment`
--

CREATE TABLE `sys_comment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '被回复的评论id',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发表评论的用户id',
  `to_user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '被评论的用户id',
  `object_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论内容 id',
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞数',
  `dislike_count` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '不喜欢数',
  `floor` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '楼层数',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:已审核,0:未审核',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '评论类型；1实名评论',
  `table_name` varchar(64) NOT NULL DEFAULT '' COMMENT '评论内容所在表，不带表前缀',
  `full_name` varchar(50) NOT NULL DEFAULT '' COMMENT '评论者昵称',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT '评论者邮箱',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '层级关系',
  `url` text COMMENT '原文地址',
  `content` text CHARACTER SET utf8mb4 COMMENT '评论内容',
  `more` text CHARACTER SET utf8mb4 COMMENT '扩展属性'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='评论表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_date_list`
--

CREATE TABLE `sys_date_list` (
  `time` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日期表';

--
-- 转存表中的数据 `sys_date_list`
--

INSERT INTO `sys_date_list` (`time`) VALUES
('2018-01-01'),
('2018-01-02'),
('2018-01-03'),
('2018-01-04'),
('2018-01-05'),
('2018-01-06'),
('2018-01-07'),
('2018-01-08'),
('2018-01-09'),
('2018-01-10'),
('2018-01-11'),
('2018-01-12'),
('2018-01-13'),
('2018-01-14'),
('2018-01-15'),
('2018-01-16'),
('2018-01-17'),
('2018-01-18'),
('2018-01-19'),
('2018-01-20'),
('2018-01-21'),
('2018-01-22'),
('2018-01-23'),
('2018-01-24'),
('2018-01-25'),
('2018-01-26'),
('2018-01-27'),
('2018-01-28'),
('2018-01-29'),
('2018-01-30'),
('2018-01-31'),
('2018-02-01'),
('2018-02-02'),
('2018-02-03'),
('2018-02-04'),
('2018-02-05'),
('2018-02-06'),
('2018-02-07'),
('2018-02-08'),
('2018-02-09'),
('2018-02-10'),
('2018-02-11'),
('2018-02-12'),
('2018-02-13'),
('2018-02-14'),
('2018-02-15'),
('2018-02-16'),
('2018-02-17'),
('2018-02-18'),
('2018-02-19'),
('2018-02-20'),
('2018-02-21'),
('2018-02-22'),
('2018-02-23'),
('2018-02-24'),
('2018-02-25'),
('2018-02-26'),
('2018-02-27'),
('2018-02-28'),
('2018-03-01'),
('2018-03-02'),
('2018-03-03'),
('2018-03-04'),
('2018-03-05'),
('2018-03-06'),
('2018-03-07'),
('2018-03-08'),
('2018-03-09'),
('2018-03-10'),
('2018-03-11'),
('2018-03-12'),
('2018-03-13'),
('2018-03-14'),
('2018-03-15'),
('2018-03-16'),
('2018-03-17'),
('2018-03-18'),
('2018-03-19'),
('2018-03-20'),
('2018-03-21'),
('2018-03-22'),
('2018-03-23'),
('2018-03-24'),
('2018-03-25'),
('2018-03-26'),
('2018-03-27'),
('2018-03-28'),
('2018-03-29'),
('2018-03-30'),
('2018-03-31'),
('2018-04-01'),
('2018-04-02'),
('2018-04-03'),
('2018-04-04'),
('2018-04-05'),
('2018-04-06'),
('2018-04-07'),
('2018-04-08'),
('2018-04-09'),
('2018-04-10'),
('2018-04-11'),
('2018-04-12'),
('2018-04-13'),
('2018-04-14'),
('2018-04-15'),
('2018-04-16'),
('2018-04-17'),
('2018-04-18'),
('2018-04-19'),
('2018-04-20'),
('2018-04-21'),
('2018-04-22'),
('2018-04-23'),
('2018-04-24'),
('2018-04-25'),
('2018-04-26'),
('2018-04-27'),
('2018-04-28'),
('2018-04-29'),
('2018-04-30'),
('2018-05-01'),
('2018-05-02'),
('2018-05-03'),
('2018-05-04'),
('2018-05-05'),
('2018-05-06'),
('2018-05-07'),
('2018-05-08'),
('2018-05-09'),
('2018-05-10'),
('2018-05-11'),
('2018-05-12'),
('2018-05-13'),
('2018-05-14'),
('2018-05-15'),
('2018-05-16'),
('2018-05-17'),
('2018-05-18'),
('2018-05-19'),
('2018-05-20'),
('2018-05-21'),
('2018-05-22'),
('2018-05-23'),
('2018-05-24'),
('2018-05-25'),
('2018-05-26'),
('2018-05-27'),
('2018-05-28'),
('2018-05-29'),
('2018-05-30'),
('2018-05-31'),
('2018-06-01'),
('2018-06-02'),
('2018-06-03'),
('2018-06-04'),
('2018-06-05'),
('2018-06-06'),
('2018-06-07'),
('2018-06-08'),
('2018-06-09'),
('2018-06-10'),
('2018-06-11'),
('2018-06-12'),
('2018-06-13'),
('2018-06-14'),
('2018-06-15'),
('2018-06-16'),
('2018-06-17'),
('2018-06-18'),
('2018-06-19'),
('2018-06-20'),
('2018-06-21'),
('2018-06-22'),
('2018-06-23'),
('2018-06-24'),
('2018-06-25'),
('2018-06-26'),
('2018-06-27'),
('2018-06-28'),
('2018-06-29'),
('2018-06-30'),
('2018-07-01'),
('2018-07-02'),
('2018-07-03'),
('2018-07-04'),
('2018-07-05'),
('2018-07-06'),
('2018-07-07'),
('2018-07-08'),
('2018-07-09'),
('2018-07-10'),
('2018-07-11'),
('2018-07-12'),
('2018-07-13'),
('2018-07-14'),
('2018-07-15'),
('2018-07-16'),
('2018-07-17'),
('2018-07-18'),
('2018-07-19'),
('2018-07-20'),
('2018-07-21'),
('2018-07-22'),
('2018-07-23'),
('2018-07-24'),
('2018-07-25'),
('2018-07-26'),
('2018-07-27'),
('2018-07-28'),
('2018-07-29'),
('2018-07-30'),
('2018-07-31'),
('2018-08-01'),
('2018-08-02'),
('2018-08-03'),
('2018-08-04'),
('2018-08-05'),
('2018-08-06'),
('2018-08-07'),
('2018-08-08'),
('2018-08-09'),
('2018-08-10'),
('2018-08-11'),
('2018-08-12'),
('2018-08-13'),
('2018-08-14'),
('2018-08-15'),
('2018-08-16'),
('2018-08-17'),
('2018-08-18'),
('2018-08-19'),
('2018-08-20'),
('2018-08-21'),
('2018-08-22'),
('2018-08-23'),
('2018-08-24'),
('2018-08-25'),
('2018-08-26'),
('2018-08-27'),
('2018-08-28'),
('2018-08-29'),
('2018-08-30'),
('2018-08-31'),
('2018-09-01'),
('2018-09-02'),
('2018-09-03'),
('2018-09-04'),
('2018-09-05'),
('2018-09-06'),
('2018-09-07'),
('2018-09-08'),
('2018-09-09'),
('2018-09-10'),
('2018-09-11'),
('2018-09-12'),
('2018-09-13'),
('2018-09-14'),
('2018-09-15'),
('2018-09-16'),
('2018-09-17'),
('2018-09-18'),
('2018-09-19'),
('2018-09-20'),
('2018-09-21'),
('2018-09-22'),
('2018-09-23'),
('2018-09-24'),
('2018-09-25'),
('2018-09-26'),
('2018-09-27'),
('2018-09-28'),
('2018-09-29'),
('2018-09-30'),
('2018-10-01'),
('2018-10-02'),
('2018-10-03'),
('2018-10-04'),
('2018-10-05'),
('2018-10-06'),
('2018-10-07'),
('2018-10-08'),
('2018-10-09'),
('2018-10-10'),
('2018-10-11'),
('2018-10-12'),
('2018-10-13'),
('2018-10-14'),
('2018-10-15'),
('2018-10-16'),
('2018-10-17'),
('2018-10-18'),
('2018-10-19'),
('2018-10-20'),
('2018-10-21'),
('2018-10-22'),
('2018-10-23'),
('2018-10-24'),
('2018-10-25'),
('2018-10-26'),
('2018-10-27'),
('2018-10-28'),
('2018-10-29'),
('2018-10-30'),
('2018-10-31'),
('2018-11-01'),
('2018-11-02'),
('2018-11-03'),
('2018-11-04'),
('2018-11-05'),
('2018-11-06'),
('2018-11-07'),
('2018-11-08'),
('2018-11-09'),
('2018-11-10'),
('2018-11-11'),
('2018-11-12'),
('2018-11-13'),
('2018-11-14'),
('2018-11-15'),
('2018-11-16'),
('2018-11-17'),
('2018-11-18'),
('2018-11-19'),
('2018-11-20'),
('2018-11-21'),
('2018-11-22'),
('2018-11-23'),
('2018-11-24'),
('2018-11-25'),
('2018-11-26'),
('2018-11-27'),
('2018-11-28'),
('2018-11-29'),
('2018-11-30'),
('2018-12-01'),
('2018-12-02'),
('2018-12-03'),
('2018-12-04'),
('2018-12-05'),
('2018-12-06'),
('2018-12-07'),
('2018-12-08'),
('2018-12-09'),
('2018-12-10'),
('2018-12-11'),
('2018-12-12'),
('2018-12-13'),
('2018-12-14'),
('2018-12-15'),
('2018-12-16'),
('2018-12-17'),
('2018-12-18'),
('2018-12-19'),
('2018-12-20'),
('2018-12-21'),
('2018-12-22'),
('2018-12-23'),
('2018-12-24'),
('2018-12-25'),
('2018-12-26'),
('2018-12-27'),
('2018-12-28'),
('2018-12-29'),
('2018-12-30'),
('2018-12-31'),
('2019-01-01'),
('2019-01-02'),
('2019-01-03'),
('2019-01-04'),
('2019-01-05'),
('2019-01-06'),
('2019-01-07'),
('2019-01-08'),
('2019-01-09'),
('2019-01-10'),
('2019-01-11'),
('2019-01-12'),
('2019-01-13'),
('2019-01-14'),
('2019-01-15'),
('2019-01-16'),
('2019-01-17'),
('2019-01-18'),
('2019-01-19'),
('2019-01-20'),
('2019-01-21'),
('2019-01-22'),
('2019-01-23'),
('2019-01-24'),
('2019-01-25'),
('2019-01-26'),
('2019-01-27'),
('2019-01-28'),
('2019-01-29'),
('2019-01-30'),
('2019-01-31'),
('2019-02-01'),
('2019-02-02'),
('2019-02-03'),
('2019-02-04'),
('2019-02-05'),
('2019-02-06'),
('2019-02-07'),
('2019-02-08'),
('2019-02-09'),
('2019-02-10'),
('2019-02-11'),
('2019-02-12'),
('2019-02-13'),
('2019-02-14'),
('2019-02-15'),
('2019-02-16'),
('2019-02-17'),
('2019-02-18'),
('2019-02-19'),
('2019-02-20'),
('2019-02-21'),
('2019-02-22'),
('2019-02-23'),
('2019-02-24'),
('2019-02-25'),
('2019-02-26'),
('2019-02-27'),
('2019-02-28'),
('2019-03-01'),
('2019-03-02'),
('2019-03-03'),
('2019-03-04'),
('2019-03-05'),
('2019-03-06'),
('2019-03-07'),
('2019-03-08'),
('2019-03-09'),
('2019-03-10'),
('2019-03-11'),
('2019-03-12'),
('2019-03-13'),
('2019-03-14'),
('2019-03-15'),
('2019-03-16'),
('2019-03-17'),
('2019-03-18'),
('2019-03-19'),
('2019-03-20'),
('2019-03-21'),
('2019-03-22'),
('2019-03-23'),
('2019-03-24'),
('2019-03-25'),
('2019-03-26'),
('2019-03-27'),
('2019-03-28'),
('2019-03-29'),
('2019-03-30'),
('2019-03-31'),
('2019-04-01'),
('2019-04-02'),
('2019-04-03'),
('2019-04-04'),
('2019-04-05'),
('2019-04-06'),
('2019-04-07'),
('2019-04-08'),
('2019-04-09'),
('2019-04-10'),
('2019-04-11'),
('2019-04-12'),
('2019-04-13'),
('2019-04-14'),
('2019-04-15'),
('2019-04-16'),
('2019-04-17'),
('2019-04-18'),
('2019-04-19'),
('2019-04-20'),
('2019-04-21'),
('2019-04-22'),
('2019-04-23'),
('2019-04-24'),
('2019-04-25'),
('2019-04-26'),
('2019-04-27'),
('2019-04-28'),
('2019-04-29'),
('2019-04-30'),
('2019-05-01'),
('2019-05-02'),
('2019-05-03'),
('2019-05-04'),
('2019-05-05'),
('2019-05-06'),
('2019-05-07'),
('2019-05-08'),
('2019-05-09'),
('2019-05-10'),
('2019-05-11'),
('2019-05-12'),
('2019-05-13'),
('2019-05-14'),
('2019-05-15'),
('2019-05-16'),
('2019-05-17'),
('2019-05-18'),
('2019-05-19'),
('2019-05-20'),
('2019-05-21'),
('2019-05-22'),
('2019-05-23'),
('2019-05-24'),
('2019-05-25'),
('2019-05-26'),
('2019-05-27'),
('2019-05-28'),
('2019-05-29'),
('2019-05-30'),
('2019-05-31'),
('2019-06-01'),
('2019-06-02'),
('2019-06-03'),
('2019-06-04'),
('2019-06-05'),
('2019-06-06'),
('2019-06-07'),
('2019-06-08'),
('2019-06-09'),
('2019-06-10'),
('2019-06-11'),
('2019-06-12'),
('2019-06-13'),
('2019-06-14'),
('2019-06-15'),
('2019-06-16'),
('2019-06-17'),
('2019-06-18'),
('2019-06-19'),
('2019-06-20'),
('2019-06-21'),
('2019-06-22'),
('2019-06-23'),
('2019-06-24'),
('2019-06-25'),
('2019-06-26'),
('2019-06-27'),
('2019-06-28'),
('2019-06-29'),
('2019-06-30'),
('2019-07-01'),
('2019-07-02'),
('2019-07-03'),
('2019-07-04'),
('2019-07-05'),
('2019-07-06'),
('2019-07-07'),
('2019-07-08'),
('2019-07-09'),
('2019-07-10'),
('2019-07-11'),
('2019-07-12'),
('2019-07-13'),
('2019-07-14'),
('2019-07-15'),
('2019-07-16'),
('2019-07-17'),
('2019-07-18'),
('2019-07-19'),
('2019-07-20'),
('2019-07-21'),
('2019-07-22'),
('2019-07-23'),
('2019-07-24'),
('2019-07-25'),
('2019-07-26'),
('2019-07-27'),
('2019-07-28'),
('2019-07-29'),
('2019-07-30'),
('2019-07-31'),
('2019-08-01'),
('2019-08-02'),
('2019-08-03'),
('2019-08-04'),
('2019-08-05'),
('2019-08-06'),
('2019-08-07'),
('2019-08-08'),
('2019-08-09'),
('2019-08-10'),
('2019-08-11'),
('2019-08-12'),
('2019-08-13'),
('2019-08-14'),
('2019-08-15'),
('2019-08-16'),
('2019-08-17'),
('2019-08-18'),
('2019-08-19'),
('2019-08-20'),
('2019-08-21'),
('2019-08-22'),
('2019-08-23'),
('2019-08-24'),
('2019-08-25'),
('2019-08-26'),
('2019-08-27'),
('2019-08-28'),
('2019-08-29'),
('2019-08-30'),
('2019-08-31'),
('2019-09-01'),
('2019-09-02'),
('2019-09-03'),
('2019-09-04'),
('2019-09-05'),
('2019-09-06'),
('2019-09-07'),
('2019-09-08'),
('2019-09-09'),
('2019-09-10'),
('2019-09-11'),
('2019-09-12'),
('2019-09-13'),
('2019-09-14'),
('2019-09-15'),
('2019-09-16'),
('2019-09-17'),
('2019-09-18'),
('2019-09-19'),
('2019-09-20'),
('2019-09-21'),
('2019-09-22'),
('2019-09-23'),
('2019-09-24'),
('2019-09-25'),
('2019-09-26'),
('2019-09-27'),
('2019-09-28'),
('2019-09-29'),
('2019-09-30'),
('2019-10-01'),
('2019-10-02'),
('2019-10-03'),
('2019-10-04'),
('2019-10-05'),
('2019-10-06'),
('2019-10-07'),
('2019-10-08'),
('2019-10-09'),
('2019-10-10'),
('2019-10-11'),
('2019-10-12'),
('2019-10-13'),
('2019-10-14'),
('2019-10-15'),
('2019-10-16'),
('2019-10-17'),
('2019-10-18'),
('2019-10-19'),
('2019-10-20'),
('2019-10-21'),
('2019-10-22'),
('2019-10-23'),
('2019-10-24'),
('2019-10-25'),
('2019-10-26'),
('2019-10-27'),
('2019-10-28'),
('2019-10-29'),
('2019-10-30'),
('2019-10-31'),
('2019-11-01'),
('2019-11-02'),
('2019-11-03'),
('2019-11-04'),
('2019-11-05'),
('2019-11-06'),
('2019-11-07'),
('2019-11-08'),
('2019-11-09'),
('2019-11-10'),
('2019-11-11'),
('2019-11-12'),
('2019-11-13'),
('2019-11-14'),
('2019-11-15'),
('2019-11-16'),
('2019-11-17'),
('2019-11-18'),
('2019-11-19'),
('2019-11-20'),
('2019-11-21'),
('2019-11-22'),
('2019-11-23'),
('2019-11-24'),
('2019-11-25'),
('2019-11-26'),
('2019-11-27'),
('2019-11-28'),
('2019-11-29'),
('2019-11-30'),
('2019-12-01'),
('2019-12-02'),
('2019-12-03'),
('2019-12-04'),
('2019-12-05'),
('2019-12-06'),
('2019-12-07'),
('2019-12-08'),
('2019-12-09'),
('2019-12-10'),
('2019-12-11'),
('2019-12-12'),
('2019-12-13'),
('2019-12-14'),
('2019-12-15'),
('2019-12-16'),
('2019-12-17'),
('2019-12-18'),
('2019-12-19'),
('2019-12-20'),
('2019-12-21'),
('2019-12-22'),
('2019-12-23'),
('2019-12-24'),
('2019-12-25'),
('2019-12-26'),
('2019-12-27'),
('2019-12-28'),
('2019-12-29'),
('2019-12-30'),
('2019-12-31'),
('2020-01-01'),
('2020-01-02'),
('2020-01-03'),
('2020-01-04'),
('2020-01-05'),
('2020-01-06'),
('2020-01-07'),
('2020-01-08'),
('2020-01-09'),
('2020-01-10'),
('2020-01-11'),
('2020-01-12'),
('2020-01-13'),
('2020-01-14'),
('2020-01-15'),
('2020-01-16'),
('2020-01-17'),
('2020-01-18'),
('2020-01-19'),
('2020-01-20'),
('2020-01-21'),
('2020-01-22'),
('2020-01-23'),
('2020-01-24'),
('2020-01-25'),
('2020-01-26'),
('2020-01-27'),
('2020-01-28'),
('2020-01-29'),
('2020-01-30'),
('2020-01-31'),
('2020-02-01'),
('2020-02-02'),
('2020-02-03'),
('2020-02-04'),
('2020-02-05'),
('2020-02-06'),
('2020-02-07'),
('2020-02-08'),
('2020-02-09'),
('2020-02-10'),
('2020-02-11'),
('2020-02-12'),
('2020-02-13'),
('2020-02-14'),
('2020-02-15'),
('2020-02-16'),
('2020-02-17'),
('2020-02-18'),
('2020-02-19'),
('2020-02-20'),
('2020-02-21'),
('2020-02-22'),
('2020-02-23'),
('2020-02-24'),
('2020-02-25'),
('2020-02-26'),
('2020-02-27'),
('2020-02-28'),
('2020-02-29'),
('2020-03-01'),
('2020-03-02'),
('2020-03-03'),
('2020-03-04'),
('2020-03-05'),
('2020-03-06'),
('2020-03-07'),
('2020-03-08'),
('2020-03-09'),
('2020-03-10'),
('2020-03-11'),
('2020-03-12'),
('2020-03-13'),
('2020-03-14'),
('2020-03-15'),
('2020-03-16'),
('2020-03-17'),
('2020-03-18'),
('2020-03-19'),
('2020-03-20'),
('2020-03-21'),
('2020-03-22'),
('2020-03-23'),
('2020-03-24'),
('2020-03-25'),
('2020-03-26'),
('2020-03-27'),
('2020-03-28'),
('2020-03-29'),
('2020-03-30'),
('2020-03-31'),
('2020-04-01'),
('2020-04-02'),
('2020-04-03'),
('2020-04-04'),
('2020-04-05'),
('2020-04-06'),
('2020-04-07'),
('2020-04-08'),
('2020-04-09'),
('2020-04-10'),
('2020-04-11'),
('2020-04-12'),
('2020-04-13'),
('2020-04-14'),
('2020-04-15'),
('2020-04-16'),
('2020-04-17'),
('2020-04-18'),
('2020-04-19'),
('2020-04-20'),
('2020-04-21'),
('2020-04-22'),
('2020-04-23'),
('2020-04-24'),
('2020-04-25'),
('2020-04-26'),
('2020-04-27'),
('2020-04-28'),
('2020-04-29'),
('2020-04-30'),
('2020-05-01'),
('2020-05-02'),
('2020-05-03'),
('2020-05-04'),
('2020-05-05'),
('2020-05-06'),
('2020-05-07'),
('2020-05-08'),
('2020-05-09'),
('2020-05-10'),
('2020-05-11'),
('2020-05-12'),
('2020-05-13'),
('2020-05-14'),
('2020-05-15'),
('2020-05-16'),
('2020-05-17'),
('2020-05-18'),
('2020-05-19'),
('2020-05-20'),
('2020-05-21'),
('2020-05-22'),
('2020-05-23'),
('2020-05-24'),
('2020-05-25'),
('2020-05-26'),
('2020-05-27'),
('2020-05-28'),
('2020-05-29'),
('2020-05-30'),
('2020-05-31'),
('2020-06-01'),
('2020-06-02'),
('2020-06-03'),
('2020-06-04'),
('2020-06-05'),
('2020-06-06'),
('2020-06-07'),
('2020-06-08'),
('2020-06-09'),
('2020-06-10'),
('2020-06-11'),
('2020-06-12'),
('2020-06-13'),
('2020-06-14'),
('2020-06-15'),
('2020-06-16'),
('2020-06-17'),
('2020-06-18'),
('2020-06-19'),
('2020-06-20'),
('2020-06-21'),
('2020-06-22'),
('2020-06-23'),
('2020-06-24'),
('2020-06-25'),
('2020-06-26'),
('2020-06-27'),
('2020-06-28'),
('2020-06-29'),
('2020-06-30'),
('2020-07-01'),
('2020-07-02'),
('2020-07-03'),
('2020-07-04'),
('2020-07-05'),
('2020-07-06'),
('2020-07-07'),
('2020-07-08'),
('2020-07-09'),
('2020-07-10'),
('2020-07-11'),
('2020-07-12'),
('2020-07-13'),
('2020-07-14'),
('2020-07-15'),
('2020-07-16'),
('2020-07-17'),
('2020-07-18'),
('2020-07-19'),
('2020-07-20'),
('2020-07-21'),
('2020-07-22'),
('2020-07-23'),
('2020-07-24'),
('2020-07-25'),
('2020-07-26'),
('2020-07-27'),
('2020-07-28'),
('2020-07-29'),
('2020-07-30'),
('2020-07-31'),
('2020-08-01'),
('2020-08-02'),
('2020-08-03'),
('2020-08-04'),
('2020-08-05'),
('2020-08-06'),
('2020-08-07'),
('2020-08-08'),
('2020-08-09'),
('2020-08-10'),
('2020-08-11'),
('2020-08-12'),
('2020-08-13'),
('2020-08-14'),
('2020-08-15'),
('2020-08-16'),
('2020-08-17'),
('2020-08-18'),
('2020-08-19'),
('2020-08-20'),
('2020-08-21'),
('2020-08-22'),
('2020-08-23'),
('2020-08-24'),
('2020-08-25'),
('2020-08-26'),
('2020-08-27'),
('2020-08-28'),
('2020-08-29'),
('2020-08-30'),
('2020-08-31'),
('2020-09-01'),
('2020-09-02'),
('2020-09-03'),
('2020-09-04'),
('2020-09-05'),
('2020-09-06'),
('2020-09-07'),
('2020-09-08'),
('2020-09-09'),
('2020-09-10'),
('2020-09-11'),
('2020-09-12'),
('2020-09-13'),
('2020-09-14'),
('2020-09-15'),
('2020-09-16'),
('2020-09-17'),
('2020-09-18'),
('2020-09-19'),
('2020-09-20'),
('2020-09-21'),
('2020-09-22'),
('2020-09-23'),
('2020-09-24'),
('2020-09-25'),
('2020-09-26'),
('2020-09-27'),
('2020-09-28'),
('2020-09-29'),
('2020-09-30'),
('2020-10-01'),
('2020-10-02'),
('2020-10-03'),
('2020-10-04'),
('2020-10-05'),
('2020-10-06'),
('2020-10-07'),
('2020-10-08'),
('2020-10-09'),
('2020-10-10'),
('2020-10-11'),
('2020-10-12'),
('2020-10-13'),
('2020-10-14'),
('2020-10-15'),
('2020-10-16'),
('2020-10-17'),
('2020-10-18'),
('2020-10-19'),
('2020-10-20'),
('2020-10-21'),
('2020-10-22'),
('2020-10-23'),
('2020-10-24'),
('2020-10-25'),
('2020-10-26'),
('2020-10-27'),
('2020-10-28'),
('2020-10-29'),
('2020-10-30'),
('2020-10-31'),
('2020-11-01'),
('2020-11-02'),
('2020-11-03'),
('2020-11-04'),
('2020-11-05'),
('2020-11-06'),
('2020-11-07'),
('2020-11-08'),
('2020-11-09'),
('2020-11-10'),
('2020-11-11'),
('2020-11-12'),
('2020-11-13'),
('2020-11-14'),
('2020-11-15'),
('2020-11-16'),
('2020-11-17'),
('2020-11-18'),
('2020-11-19'),
('2020-11-20'),
('2020-11-21'),
('2020-11-22'),
('2020-11-23'),
('2020-11-24'),
('2020-11-25'),
('2020-11-26'),
('2020-11-27'),
('2020-11-28'),
('2020-11-29'),
('2020-11-30'),
('2020-12-01'),
('2020-12-02'),
('2020-12-03'),
('2020-12-04'),
('2020-12-05'),
('2020-12-06'),
('2020-12-07'),
('2020-12-08'),
('2020-12-09'),
('2020-12-10'),
('2020-12-11'),
('2020-12-12'),
('2020-12-13'),
('2020-12-14'),
('2020-12-15'),
('2020-12-16'),
('2020-12-17'),
('2020-12-18'),
('2020-12-19'),
('2020-12-20'),
('2020-12-21'),
('2020-12-22'),
('2020-12-23'),
('2020-12-24'),
('2020-12-25'),
('2020-12-26'),
('2020-12-27'),
('2020-12-28'),
('2020-12-29'),
('2020-12-30'),
('2020-12-31'),
('2021-01-01'),
('2021-01-02'),
('2021-01-03'),
('2021-01-04'),
('2021-01-05'),
('2021-01-06'),
('2021-01-07'),
('2021-01-08'),
('2021-01-09'),
('2021-01-10'),
('2021-01-11'),
('2021-01-12'),
('2021-01-13'),
('2021-01-14'),
('2021-01-15'),
('2021-01-16'),
('2021-01-17'),
('2021-01-18'),
('2021-01-19'),
('2021-01-20'),
('2021-01-21'),
('2021-01-22'),
('2021-01-23'),
('2021-01-24'),
('2021-01-25'),
('2021-01-26'),
('2021-01-27'),
('2021-01-28'),
('2021-01-29'),
('2021-01-30'),
('2021-01-31'),
('2021-02-01'),
('2021-02-02'),
('2021-02-03'),
('2021-02-04'),
('2021-02-05'),
('2021-02-06'),
('2021-02-07'),
('2021-02-08'),
('2021-02-09'),
('2021-02-10'),
('2021-02-11'),
('2021-02-12'),
('2021-02-13'),
('2021-02-14'),
('2021-02-15'),
('2021-02-16'),
('2021-02-17'),
('2021-02-18'),
('2021-02-19'),
('2021-02-20'),
('2021-02-21'),
('2021-02-22'),
('2021-02-23'),
('2021-02-24'),
('2021-02-25'),
('2021-02-26'),
('2021-02-27'),
('2021-02-28'),
('2021-03-01'),
('2021-03-02'),
('2021-03-03'),
('2021-03-04'),
('2021-03-05'),
('2021-03-06'),
('2021-03-07'),
('2021-03-08'),
('2021-03-09'),
('2021-03-10'),
('2021-03-11'),
('2021-03-12'),
('2021-03-13'),
('2021-03-14'),
('2021-03-15'),
('2021-03-16'),
('2021-03-17'),
('2021-03-18'),
('2021-03-19'),
('2021-03-20'),
('2021-03-21'),
('2021-03-22'),
('2021-03-23'),
('2021-03-24'),
('2021-03-25'),
('2021-03-26'),
('2021-03-27'),
('2021-03-28'),
('2021-03-29'),
('2021-03-30'),
('2021-03-31'),
('2021-04-01'),
('2021-04-02'),
('2021-04-03'),
('2021-04-04'),
('2021-04-05'),
('2021-04-06'),
('2021-04-07'),
('2021-04-08'),
('2021-04-09'),
('2021-04-10'),
('2021-04-11'),
('2021-04-12'),
('2021-04-13'),
('2021-04-14'),
('2021-04-15'),
('2021-04-16'),
('2021-04-17'),
('2021-04-18'),
('2021-04-19'),
('2021-04-20'),
('2021-04-21'),
('2021-04-22'),
('2021-04-23'),
('2021-04-24'),
('2021-04-25'),
('2021-04-26'),
('2021-04-27'),
('2021-04-28'),
('2021-04-29'),
('2021-04-30'),
('2021-05-01'),
('2021-05-02'),
('2021-05-03'),
('2021-05-04'),
('2021-05-05'),
('2021-05-06'),
('2021-05-07'),
('2021-05-08'),
('2021-05-09'),
('2021-05-10'),
('2021-05-11'),
('2021-05-12'),
('2021-05-13'),
('2021-05-14'),
('2021-05-15'),
('2021-05-16'),
('2021-05-17'),
('2021-05-18'),
('2021-05-19'),
('2021-05-20'),
('2021-05-21'),
('2021-05-22'),
('2021-05-23'),
('2021-05-24'),
('2021-05-25'),
('2021-05-26'),
('2021-05-27'),
('2021-05-28'),
('2021-05-29'),
('2021-05-30'),
('2021-05-31'),
('2021-06-01'),
('2021-06-02'),
('2021-06-03'),
('2021-06-04'),
('2021-06-05'),
('2021-06-06'),
('2021-06-07'),
('2021-06-08'),
('2021-06-09'),
('2021-06-10'),
('2021-06-11'),
('2021-06-12'),
('2021-06-13'),
('2021-06-14'),
('2021-06-15'),
('2021-06-16'),
('2021-06-17'),
('2021-06-18'),
('2021-06-19'),
('2021-06-20'),
('2021-06-21'),
('2021-06-22'),
('2021-06-23'),
('2021-06-24'),
('2021-06-25'),
('2021-06-26'),
('2021-06-27'),
('2021-06-28'),
('2021-06-29'),
('2021-06-30'),
('2021-07-01'),
('2021-07-02'),
('2021-07-03'),
('2021-07-04'),
('2021-07-05'),
('2021-07-06'),
('2021-07-07'),
('2021-07-08'),
('2021-07-09'),
('2021-07-10'),
('2021-07-11'),
('2021-07-12'),
('2021-07-13'),
('2021-07-14'),
('2021-07-15'),
('2021-07-16'),
('2021-07-17'),
('2021-07-18'),
('2021-07-19'),
('2021-07-20'),
('2021-07-21'),
('2021-07-22'),
('2021-07-23'),
('2021-07-24'),
('2021-07-25'),
('2021-07-26'),
('2021-07-27'),
('2021-07-28'),
('2021-07-29'),
('2021-07-30'),
('2021-07-31'),
('2021-08-01'),
('2021-08-02'),
('2021-08-03'),
('2021-08-04'),
('2021-08-05'),
('2021-08-06'),
('2021-08-07'),
('2021-08-08'),
('2021-08-09'),
('2021-08-10'),
('2021-08-11'),
('2021-08-12'),
('2021-08-13'),
('2021-08-14'),
('2021-08-15'),
('2021-08-16'),
('2021-08-17'),
('2021-08-18'),
('2021-08-19'),
('2021-08-20'),
('2021-08-21'),
('2021-08-22'),
('2021-08-23'),
('2021-08-24'),
('2021-08-25'),
('2021-08-26'),
('2021-08-27'),
('2021-08-28'),
('2021-08-29'),
('2021-08-30'),
('2021-08-31'),
('2021-09-01'),
('2021-09-02'),
('2021-09-03'),
('2021-09-04'),
('2021-09-05'),
('2021-09-06'),
('2021-09-07'),
('2021-09-08'),
('2021-09-09'),
('2021-09-10'),
('2021-09-11'),
('2021-09-12'),
('2021-09-13'),
('2021-09-14'),
('2021-09-15'),
('2021-09-16'),
('2021-09-17'),
('2021-09-18'),
('2021-09-19'),
('2021-09-20'),
('2021-09-21'),
('2021-09-22'),
('2021-09-23'),
('2021-09-24'),
('2021-09-25'),
('2021-09-26'),
('2021-09-27'),
('2021-09-28'),
('2021-09-29'),
('2021-09-30'),
('2021-10-01'),
('2021-10-02'),
('2021-10-03'),
('2021-10-04'),
('2021-10-05'),
('2021-10-06'),
('2021-10-07'),
('2021-10-08'),
('2021-10-09'),
('2021-10-10'),
('2021-10-11'),
('2021-10-12'),
('2021-10-13'),
('2021-10-14'),
('2021-10-15'),
('2021-10-16'),
('2021-10-17'),
('2021-10-18'),
('2021-10-19'),
('2021-10-20'),
('2021-10-21'),
('2021-10-22'),
('2021-10-23'),
('2021-10-24'),
('2021-10-25'),
('2021-10-26'),
('2021-10-27'),
('2021-10-28'),
('2021-10-29'),
('2021-10-30'),
('2021-10-31'),
('2021-11-01'),
('2021-11-02'),
('2021-11-03'),
('2021-11-04'),
('2021-11-05'),
('2021-11-06'),
('2021-11-07'),
('2021-11-08'),
('2021-11-09'),
('2021-11-10'),
('2021-11-11'),
('2021-11-12'),
('2021-11-13'),
('2021-11-14'),
('2021-11-15'),
('2021-11-16'),
('2021-11-17'),
('2021-11-18'),
('2021-11-19'),
('2021-11-20'),
('2021-11-21'),
('2021-11-22'),
('2021-11-23'),
('2021-11-24'),
('2021-11-25'),
('2021-11-26'),
('2021-11-27'),
('2021-11-28'),
('2021-11-29'),
('2021-11-30'),
('2021-12-01'),
('2021-12-02'),
('2021-12-03'),
('2021-12-04'),
('2021-12-05'),
('2021-12-06'),
('2021-12-07'),
('2021-12-08'),
('2021-12-09'),
('2021-12-10'),
('2021-12-11'),
('2021-12-12'),
('2021-12-13'),
('2021-12-14'),
('2021-12-15'),
('2021-12-16'),
('2021-12-17'),
('2021-12-18'),
('2021-12-19'),
('2021-12-20'),
('2021-12-21'),
('2021-12-22'),
('2021-12-23'),
('2021-12-24'),
('2021-12-25'),
('2021-12-26'),
('2021-12-27'),
('2021-12-28'),
('2021-12-29'),
('2021-12-30'),
('2021-12-31'),
('2022-01-01'),
('2022-01-02'),
('2022-01-03'),
('2022-01-04'),
('2022-01-05'),
('2022-01-06'),
('2022-01-07'),
('2022-01-08'),
('2022-01-09'),
('2022-01-10'),
('2022-01-11'),
('2022-01-12'),
('2022-01-13'),
('2022-01-14'),
('2022-01-15'),
('2022-01-16'),
('2022-01-17'),
('2022-01-18'),
('2022-01-19'),
('2022-01-20'),
('2022-01-21'),
('2022-01-22'),
('2022-01-23'),
('2022-01-24'),
('2022-01-25'),
('2022-01-26'),
('2022-01-27'),
('2022-01-28'),
('2022-01-29'),
('2022-01-30'),
('2022-01-31'),
('2022-02-01'),
('2022-02-02'),
('2022-02-03'),
('2022-02-04'),
('2022-02-05'),
('2022-02-06'),
('2022-02-07'),
('2022-02-08'),
('2022-02-09'),
('2022-02-10'),
('2022-02-11'),
('2022-02-12'),
('2022-02-13'),
('2022-02-14'),
('2022-02-15'),
('2022-02-16'),
('2022-02-17'),
('2022-02-18'),
('2022-02-19'),
('2022-02-20'),
('2022-02-21'),
('2022-02-22'),
('2022-02-23'),
('2022-02-24'),
('2022-02-25'),
('2022-02-26'),
('2022-02-27'),
('2022-02-28'),
('2022-03-01'),
('2022-03-02'),
('2022-03-03'),
('2022-03-04'),
('2022-03-05'),
('2022-03-06'),
('2022-03-07'),
('2022-03-08'),
('2022-03-09'),
('2022-03-10'),
('2022-03-11'),
('2022-03-12'),
('2022-03-13'),
('2022-03-14'),
('2022-03-15'),
('2022-03-16'),
('2022-03-17'),
('2022-03-18'),
('2022-03-19'),
('2022-03-20'),
('2022-03-21'),
('2022-03-22'),
('2022-03-23'),
('2022-03-24'),
('2022-03-25'),
('2022-03-26'),
('2022-03-27'),
('2022-03-28'),
('2022-03-29'),
('2022-03-30'),
('2022-03-31'),
('2022-04-01'),
('2022-04-02'),
('2022-04-03'),
('2022-04-04'),
('2022-04-05'),
('2022-04-06'),
('2022-04-07'),
('2022-04-08'),
('2022-04-09'),
('2022-04-10'),
('2022-04-11'),
('2022-04-12'),
('2022-04-13'),
('2022-04-14'),
('2022-04-15'),
('2022-04-16'),
('2022-04-17'),
('2022-04-18'),
('2022-04-19'),
('2022-04-20'),
('2022-04-21'),
('2022-04-22'),
('2022-04-23'),
('2022-04-24'),
('2022-04-25'),
('2022-04-26'),
('2022-04-27'),
('2022-04-28'),
('2022-04-29'),
('2022-04-30'),
('2022-05-01'),
('2022-05-02'),
('2022-05-03'),
('2022-05-04'),
('2022-05-05'),
('2022-05-06'),
('2022-05-07'),
('2022-05-08'),
('2022-05-09'),
('2022-05-10'),
('2022-05-11'),
('2022-05-12'),
('2022-05-13'),
('2022-05-14'),
('2022-05-15'),
('2022-05-16'),
('2022-05-17'),
('2022-05-18'),
('2022-05-19'),
('2022-05-20'),
('2022-05-21'),
('2022-05-22'),
('2022-05-23'),
('2022-05-24'),
('2022-05-25'),
('2022-05-26'),
('2022-05-27'),
('2022-05-28'),
('2022-05-29'),
('2022-05-30'),
('2022-05-31'),
('2022-06-01'),
('2022-06-02'),
('2022-06-03'),
('2022-06-04'),
('2022-06-05'),
('2022-06-06'),
('2022-06-07'),
('2022-06-08'),
('2022-06-09'),
('2022-06-10'),
('2022-06-11'),
('2022-06-12'),
('2022-06-13'),
('2022-06-14'),
('2022-06-15'),
('2022-06-16'),
('2022-06-17'),
('2022-06-18'),
('2022-06-19'),
('2022-06-20'),
('2022-06-21'),
('2022-06-22'),
('2022-06-23'),
('2022-06-24'),
('2022-06-25'),
('2022-06-26'),
('2022-06-27'),
('2022-06-28'),
('2022-06-29'),
('2022-06-30'),
('2022-07-01'),
('2022-07-02'),
('2022-07-03'),
('2022-07-04'),
('2022-07-05'),
('2022-07-06'),
('2022-07-07'),
('2022-07-08'),
('2022-07-09'),
('2022-07-10'),
('2022-07-11'),
('2022-07-12'),
('2022-07-13'),
('2022-07-14'),
('2022-07-15'),
('2022-07-16'),
('2022-07-17'),
('2022-07-18'),
('2022-07-19'),
('2022-07-20'),
('2022-07-21'),
('2022-07-22'),
('2022-07-23'),
('2022-07-24'),
('2022-07-25'),
('2022-07-26'),
('2022-07-27'),
('2022-07-28'),
('2022-07-29'),
('2022-07-30'),
('2022-07-31'),
('2022-08-01'),
('2022-08-02'),
('2022-08-03'),
('2022-08-04'),
('2022-08-05'),
('2022-08-06'),
('2022-08-07'),
('2022-08-08'),
('2022-08-09'),
('2022-08-10'),
('2022-08-11'),
('2022-08-12'),
('2033-07-26'),
('2033-07-27'),
('2033-07-28'),
('2033-07-29'),
('2033-07-30'),
('2033-07-31'),
('2033-08-01'),
('2033-08-02'),
('2033-08-03'),
('2033-08-04'),
('2033-08-05'),
('2033-08-06'),
('2033-08-07'),
('2033-08-08'),
('2033-08-09'),
('2033-08-10'),
('2033-08-11'),
('2033-08-12'),
('2033-08-13'),
('2033-08-14'),
('2033-08-15'),
('2033-08-16'),
('2033-08-17'),
('2033-08-18'),
('2033-08-19'),
('2033-08-20'),
('2033-08-21'),
('2033-08-22'),
('2033-08-23'),
('2033-08-24'),
('2033-08-25'),
('2033-08-26'),
('2033-08-27'),
('2033-08-28'),
('2033-08-29'),
('2033-08-30'),
('2033-08-31'),
('2033-09-01'),
('2033-09-02'),
('2033-09-03'),
('2033-09-04'),
('2033-09-05'),
('2033-09-06'),
('2033-09-07'),
('2033-09-08'),
('2033-09-09'),
('2033-09-10'),
('2033-09-11'),
('2033-09-12'),
('2033-09-13'),
('2033-09-14'),
('2033-09-15'),
('2033-09-16'),
('2033-09-17'),
('2033-09-18'),
('2033-09-19'),
('2033-09-20'),
('2033-09-21'),
('2033-09-22'),
('2033-09-23'),
('2033-09-24'),
('2033-09-25'),
('2033-09-26'),
('2033-09-27'),
('2033-09-28'),
('2033-09-29'),
('2033-09-30'),
('2033-10-01'),
('2033-10-02'),
('2033-10-03'),
('2033-10-04'),
('2033-10-05'),
('2033-10-06'),
('2033-10-07'),
('2033-10-08'),
('2033-10-09'),
('2033-10-10'),
('2033-10-11'),
('2033-10-12'),
('2033-10-13'),
('2033-10-14'),
('2033-10-15'),
('2033-10-16'),
('2033-10-17'),
('2033-10-18'),
('2033-10-19'),
('2033-10-20'),
('2033-10-21'),
('2033-10-22'),
('2033-10-23'),
('2033-10-24'),
('2033-10-25'),
('2033-10-26'),
('2033-10-27'),
('2033-10-28'),
('2033-10-29'),
('2033-10-30'),
('2033-10-31'),
('2033-11-01'),
('2033-11-02'),
('2033-11-03'),
('2033-11-04'),
('2033-11-05'),
('2033-11-06'),
('2033-11-07'),
('2033-11-08'),
('2033-11-09'),
('2033-11-10'),
('2033-11-11'),
('2033-11-12'),
('2033-11-13'),
('2033-11-14'),
('2033-11-15'),
('2033-11-16'),
('2033-11-17'),
('2033-11-18'),
('2033-11-19'),
('2033-11-20'),
('2033-11-21'),
('2033-11-22'),
('2033-11-23'),
('2033-11-24'),
('2033-11-25'),
('2033-11-26'),
('2033-11-27'),
('2033-11-28'),
('2033-11-29'),
('2033-11-30'),
('2033-12-01'),
('2033-12-02'),
('2033-12-03'),
('2033-12-04'),
('2033-12-05'),
('2033-12-06'),
('2033-12-07'),
('2033-12-08'),
('2033-12-09'),
('2033-12-10'),
('2033-12-11'),
('2033-12-12'),
('2033-12-13'),
('2033-12-14'),
('2033-12-15'),
('2033-12-16'),
('2033-12-17'),
('2033-12-18'),
('2033-12-19'),
('2033-12-20'),
('2033-12-21'),
('2033-12-22'),
('2033-12-23'),
('2033-12-24'),
('2033-12-25'),
('2033-12-26'),
('2033-12-27'),
('2033-12-28'),
('2033-12-29'),
('2033-12-30'),
('2033-12-31'),
('2034-01-01'),
('2034-01-02'),
('2034-01-03'),
('2034-01-04'),
('2034-01-05'),
('2034-01-06'),
('2034-01-07'),
('2034-01-08'),
('2034-01-09'),
('2034-01-10'),
('2034-01-11'),
('2034-01-12'),
('2034-01-13'),
('2034-01-14'),
('2034-01-15'),
('2034-01-16'),
('2034-01-17'),
('2034-01-18'),
('2034-01-19'),
('2034-01-20'),
('2034-01-21'),
('2034-01-22'),
('2034-01-23'),
('2034-01-24'),
('2034-01-25'),
('2034-01-26'),
('2034-01-27'),
('2034-01-28'),
('2034-01-29'),
('2034-01-30'),
('2034-01-31'),
('2034-02-01'),
('2034-02-02'),
('2034-02-03'),
('2034-02-04'),
('2034-02-05'),
('2034-02-06'),
('2034-02-07'),
('2034-02-08'),
('2034-02-09'),
('2034-02-10'),
('2034-02-11'),
('2034-02-12'),
('2034-02-13'),
('2034-02-14'),
('2034-02-15'),
('2034-02-16'),
('2034-02-17'),
('2034-02-18'),
('2034-02-19'),
('2034-02-20'),
('2034-02-21'),
('2034-02-22'),
('2034-02-23'),
('2034-02-24'),
('2034-02-25'),
('2034-02-26'),
('2034-02-27'),
('2034-02-28'),
('2034-03-01'),
('2034-03-02'),
('2034-03-03'),
('2034-03-04'),
('2034-03-05'),
('2034-03-06'),
('2034-03-07'),
('2034-03-08'),
('2034-03-09'),
('2034-03-10'),
('2034-03-11'),
('2034-03-12'),
('2034-03-13'),
('2034-03-14'),
('2034-03-15'),
('2034-03-16'),
('2034-03-17'),
('2034-03-18'),
('2034-03-19'),
('2034-03-20'),
('2034-03-21'),
('2034-03-22'),
('2034-03-23'),
('2034-03-24'),
('2034-03-25'),
('2034-03-26'),
('2034-03-27'),
('2034-03-28'),
('2034-03-29'),
('2034-03-30'),
('2034-03-31'),
('2034-04-01'),
('2034-04-02'),
('2034-04-03'),
('2034-04-04'),
('2034-04-05'),
('2034-04-06'),
('2034-04-07'),
('2034-04-08'),
('2034-04-09'),
('2034-04-10'),
('2034-04-11'),
('2034-04-12'),
('2034-04-13'),
('2034-04-14'),
('2034-04-15'),
('2034-04-16'),
('2034-04-17'),
('2034-04-18'),
('2034-04-19'),
('2034-04-20'),
('2034-04-21'),
('2034-04-22'),
('2034-04-23'),
('2034-04-24'),
('2034-04-25'),
('2034-04-26'),
('2034-04-27'),
('2034-04-28'),
('2034-04-29'),
('2034-04-30'),
('2034-05-01'),
('2034-05-02'),
('2034-05-03'),
('2034-05-04'),
('2034-05-05'),
('2034-05-06'),
('2034-05-07'),
('2034-05-08'),
('2034-05-09'),
('2034-05-10'),
('2034-05-11'),
('2034-05-12'),
('2034-05-13'),
('2034-05-14'),
('2034-05-15'),
('2034-05-16'),
('2034-05-17'),
('2034-05-18'),
('2034-05-19'),
('2034-05-20'),
('2034-05-21'),
('2034-05-22'),
('2034-05-23'),
('2034-05-24'),
('2034-05-25'),
('2034-05-26'),
('2034-05-27'),
('2034-05-28'),
('2034-05-29'),
('2034-05-30'),
('2034-05-31'),
('2034-06-01'),
('2034-06-02'),
('2034-06-03'),
('2034-06-04'),
('2034-06-05'),
('2034-06-06'),
('2034-06-07'),
('2034-06-08'),
('2034-06-09'),
('2034-06-10'),
('2034-06-11'),
('2034-06-12'),
('2034-06-13'),
('2034-06-14'),
('2034-06-15'),
('2034-06-16'),
('2034-06-17'),
('2034-06-18'),
('2034-06-19'),
('2034-06-20'),
('2034-06-21'),
('2034-06-22'),
('2034-06-23'),
('2034-06-24'),
('2034-06-25'),
('2034-06-26'),
('2034-06-27'),
('2034-06-28'),
('2034-06-29'),
('2034-06-30'),
('2034-07-01'),
('2034-07-02'),
('2034-07-03'),
('2034-07-04'),
('2034-07-05'),
('2034-07-06'),
('2034-07-07'),
('2034-07-08'),
('2034-07-09'),
('2034-07-10'),
('2034-07-11'),
('2034-07-12'),
('2034-07-13'),
('2034-07-14'),
('2034-07-15'),
('2034-07-16'),
('2034-07-17'),
('2034-07-18'),
('2034-07-19'),
('2034-07-20'),
('2034-07-21'),
('2034-07-22'),
('2034-07-23'),
('2034-07-24'),
('2034-07-25'),
('2034-07-26'),
('2034-07-27'),
('2034-07-28'),
('2034-07-29'),
('2034-07-30'),
('2034-07-31'),
('2034-08-01'),
('2034-08-02'),
('2034-08-03'),
('2034-08-04'),
('2034-08-05'),
('2034-08-06'),
('2034-08-07'),
('2034-08-08'),
('2034-08-09'),
('2034-08-10'),
('2034-08-11'),
('2034-08-12'),
('2034-08-13'),
('2034-08-14'),
('2034-08-15'),
('2034-08-16'),
('2034-08-17'),
('2034-08-18'),
('2034-08-19'),
('2034-08-20'),
('2034-08-21'),
('2034-08-22'),
('2034-08-23'),
('2034-08-24'),
('2034-08-25'),
('2034-08-26'),
('2034-08-27'),
('2034-08-28'),
('2034-08-29'),
('2034-08-30'),
('2034-08-31'),
('2034-09-01'),
('2034-09-02'),
('2034-09-03'),
('2034-09-04'),
('2034-09-05'),
('2034-09-06'),
('2034-09-07'),
('2034-09-08'),
('2034-09-09'),
('2034-09-10'),
('2034-09-11'),
('2034-09-12'),
('2034-09-13'),
('2034-09-14'),
('2034-09-15'),
('2034-09-16'),
('2034-09-17'),
('2034-09-18'),
('2034-09-19'),
('2034-09-20'),
('2034-09-21'),
('2034-09-22'),
('2034-09-23'),
('2034-09-24'),
('2034-09-25'),
('2034-09-26'),
('2034-09-27'),
('2034-09-28'),
('2034-09-29'),
('2034-09-30'),
('2034-10-01'),
('2034-10-02'),
('2034-10-03'),
('2034-10-04'),
('2034-10-05'),
('2034-10-06'),
('2034-10-07'),
('2034-10-08'),
('2034-10-09'),
('2034-10-10'),
('2034-10-11'),
('2034-10-12'),
('2034-10-13'),
('2034-10-14'),
('2034-10-15'),
('2034-10-16'),
('2034-10-17'),
('2034-10-18'),
('2034-10-19'),
('2034-10-20'),
('2034-10-21'),
('2034-10-22'),
('2034-10-23'),
('2034-10-24'),
('2034-10-25'),
('2034-10-26'),
('2034-10-27'),
('2034-10-28'),
('2034-10-29'),
('2034-10-30'),
('2034-10-31'),
('2034-11-01'),
('2034-11-02'),
('2034-11-03'),
('2034-11-04'),
('2034-11-05'),
('2034-11-06'),
('2034-11-07'),
('2034-11-08'),
('2034-11-09'),
('2034-11-10'),
('2034-11-11'),
('2034-11-12'),
('2034-11-13'),
('2034-11-14'),
('2034-11-15'),
('2034-11-16'),
('2034-11-17'),
('2034-11-18'),
('2034-11-19'),
('2034-11-20'),
('2034-11-21'),
('2034-11-22'),
('2034-11-23'),
('2034-11-24'),
('2034-11-25'),
('2034-11-26'),
('2034-11-27'),
('2034-11-28'),
('2034-11-29'),
('2034-11-30'),
('2034-12-01'),
('2034-12-02'),
('2034-12-03'),
('2034-12-04'),
('2034-12-05'),
('2034-12-06'),
('2034-12-07'),
('2034-12-08'),
('2034-12-09'),
('2034-12-10'),
('2034-12-11'),
('2034-12-12'),
('2034-12-13'),
('2034-12-14'),
('2034-12-15'),
('2034-12-16'),
('2034-12-17'),
('2034-12-18'),
('2034-12-19'),
('2034-12-20'),
('2034-12-21'),
('2034-12-22'),
('2034-12-23'),
('2034-12-24'),
('2034-12-25'),
('2034-12-26'),
('2034-12-27'),
('2034-12-28'),
('2034-12-29'),
('2034-12-30'),
('2034-12-31'),
('2035-01-01'),
('2035-01-02'),
('2035-01-03'),
('2035-01-04'),
('2035-01-05'),
('2035-01-06'),
('2035-01-07'),
('2035-01-08'),
('2035-01-09'),
('2035-01-10'),
('2035-01-11'),
('2035-01-12'),
('2035-01-13'),
('2035-01-14'),
('2035-01-15'),
('2035-01-16'),
('2035-01-17'),
('2035-01-18'),
('2035-01-19'),
('2035-01-20'),
('2035-01-21'),
('2035-01-22'),
('2035-01-23'),
('2035-01-24'),
('2035-01-25'),
('2035-01-26'),
('2035-01-27'),
('2035-01-28'),
('2035-01-29'),
('2035-01-30'),
('2035-01-31'),
('2035-02-01'),
('2035-02-02'),
('2035-02-03'),
('2035-02-04'),
('2035-02-05'),
('2035-02-06'),
('2035-02-07'),
('2035-02-08'),
('2035-02-09'),
('2035-02-10'),
('2035-02-11'),
('2035-02-12'),
('2035-02-13'),
('2035-02-14'),
('2035-02-15'),
('2035-02-16'),
('2035-02-17'),
('2035-02-18'),
('2035-02-19'),
('2035-02-20'),
('2035-02-21'),
('2035-02-22'),
('2035-02-23'),
('2035-02-24'),
('2035-02-25'),
('2035-02-26'),
('2035-02-27'),
('2035-02-28'),
('2035-03-01'),
('2035-03-02'),
('2035-03-03'),
('2035-03-04'),
('2035-03-05'),
('2035-03-06'),
('2035-03-07'),
('2035-03-08'),
('2035-03-09'),
('2035-03-10'),
('2035-03-11'),
('2035-03-12'),
('2035-03-13'),
('2035-03-14'),
('2035-03-15'),
('2035-03-16'),
('2035-03-17'),
('2035-03-18'),
('2035-03-19'),
('2035-03-20'),
('2035-03-21'),
('2035-03-22'),
('2035-03-23'),
('2035-03-24'),
('2035-03-25'),
('2035-03-26'),
('2035-03-27'),
('2035-03-28'),
('2035-03-29'),
('2035-03-30'),
('2035-03-31'),
('2035-04-01'),
('2035-04-02'),
('2035-04-03'),
('2035-04-04'),
('2035-04-05'),
('2035-04-06'),
('2035-04-07'),
('2035-04-08'),
('2035-04-09'),
('2035-04-10'),
('2035-04-11'),
('2035-04-12'),
('2035-04-13'),
('2035-04-14'),
('2035-04-15'),
('2035-04-16'),
('2035-04-17'),
('2035-04-18'),
('2035-04-19'),
('2035-04-20'),
('2035-04-21'),
('2035-04-22'),
('2035-04-23'),
('2035-04-24'),
('2035-04-25'),
('2035-04-26'),
('2035-04-27'),
('2035-04-28'),
('2035-04-29'),
('2035-04-30'),
('2035-05-01'),
('2035-05-02'),
('2035-05-03'),
('2035-05-04'),
('2035-05-05'),
('2035-05-06'),
('2035-05-07'),
('2035-05-08'),
('2035-05-09'),
('2035-05-10'),
('2035-05-11'),
('2035-05-12'),
('2035-05-13'),
('2035-05-14'),
('2035-05-15'),
('2035-05-16'),
('2035-05-17'),
('2035-05-18'),
('2035-05-19'),
('2035-05-20'),
('2035-05-21'),
('2035-05-22'),
('2035-05-23'),
('2035-05-24'),
('2035-05-25'),
('2035-05-26'),
('2035-05-27'),
('2035-05-28'),
('2035-05-29'),
('2035-05-30'),
('2035-05-31'),
('2035-06-01'),
('2035-06-02'),
('2035-06-03'),
('2035-06-04'),
('2035-06-05'),
('2035-06-06'),
('2035-06-07'),
('2035-06-08'),
('2035-06-09'),
('2035-06-10'),
('2035-06-11'),
('2035-06-12'),
('2035-06-13'),
('2035-06-14'),
('2035-06-15'),
('2035-06-16'),
('2035-06-17'),
('2035-06-18'),
('2035-06-19'),
('2035-06-20'),
('2035-06-21'),
('2035-06-22'),
('2035-06-23'),
('2035-06-24'),
('2035-06-25'),
('2035-06-26'),
('2035-06-27'),
('2035-06-28'),
('2035-06-29'),
('2035-06-30'),
('2035-07-01'),
('2035-07-02'),
('2035-07-03'),
('2035-07-04'),
('2035-07-05'),
('2035-07-06'),
('2035-07-07'),
('2035-07-08'),
('2035-07-09'),
('2035-07-10'),
('2035-07-11'),
('2035-07-12'),
('2035-07-13'),
('2035-07-14'),
('2035-07-15'),
('2035-07-16'),
('2035-07-17'),
('2035-07-18'),
('2035-07-19'),
('2035-07-20'),
('2035-07-21'),
('2035-07-22'),
('2035-07-23'),
('2035-07-24'),
('2035-07-25'),
('2035-07-26'),
('2035-07-27'),
('2035-07-28'),
('2035-07-29'),
('2035-07-30'),
('2035-07-31'),
('2035-08-01'),
('2035-08-02'),
('2035-08-03'),
('2035-08-04'),
('2035-08-05'),
('2035-08-06'),
('2035-08-07'),
('2035-08-08'),
('2035-08-09'),
('2035-08-10'),
('2035-08-11'),
('2035-08-12'),
('2035-08-13'),
('2035-08-14'),
('2035-08-15'),
('2035-08-16'),
('2035-08-17'),
('2035-08-18'),
('2035-08-19'),
('2035-08-20'),
('2035-08-21'),
('2035-08-22'),
('2035-08-23'),
('2035-08-24'),
('2035-08-25'),
('2035-08-26'),
('2035-08-27'),
('2035-08-28'),
('2035-08-29'),
('2035-08-30'),
('2035-08-31'),
('2035-09-01'),
('2035-09-02'),
('2035-09-03'),
('2035-09-04'),
('2035-09-05'),
('2035-09-06'),
('2035-09-07'),
('2035-09-08'),
('2035-09-09'),
('2035-09-10'),
('2035-09-11'),
('2035-09-12'),
('2035-09-13'),
('2035-09-14'),
('2035-09-15'),
('2035-09-16'),
('2035-09-17'),
('2035-09-18'),
('2035-09-19'),
('2035-09-20'),
('2035-09-21'),
('2035-09-22'),
('2035-09-23'),
('2035-09-24'),
('2035-09-25'),
('2035-09-26'),
('2035-09-27'),
('2035-09-28'),
('2035-09-29'),
('2035-09-30'),
('2035-10-01'),
('2035-10-02'),
('2035-10-03'),
('2035-10-04'),
('2035-10-05'),
('2035-10-06'),
('2035-10-07'),
('2035-10-08'),
('2035-10-09'),
('2035-10-10'),
('2035-10-11'),
('2035-10-12'),
('2035-10-13'),
('2035-10-14'),
('2035-10-15'),
('2035-10-16'),
('2035-10-17'),
('2035-10-18'),
('2035-10-19'),
('2035-10-20'),
('2035-10-21'),
('2035-10-22'),
('2035-10-23'),
('2035-10-24'),
('2035-10-25'),
('2035-10-26'),
('2035-10-27'),
('2035-10-28'),
('2035-10-29'),
('2035-10-30'),
('2035-10-31'),
('2035-11-01'),
('2035-11-02'),
('2035-11-03'),
('2035-11-04'),
('2035-11-05'),
('2035-11-06'),
('2035-11-07'),
('2035-11-08'),
('2035-11-09'),
('2035-11-10'),
('2035-11-11'),
('2035-11-12'),
('2035-11-13'),
('2035-11-14'),
('2035-11-15'),
('2035-11-16'),
('2035-11-17'),
('2035-11-18'),
('2035-11-19'),
('2035-11-20'),
('2035-11-21'),
('2035-11-22'),
('2035-11-23'),
('2035-11-24'),
('2035-11-25'),
('2035-11-26'),
('2035-11-27'),
('2035-11-28'),
('2035-11-29'),
('2035-11-30'),
('2035-12-01'),
('2035-12-02'),
('2035-12-03'),
('2035-12-04'),
('2035-12-05'),
('2035-12-06'),
('2035-12-07'),
('2035-12-08'),
('2035-12-09'),
('2035-12-10'),
('2035-12-11'),
('2035-12-12'),
('2035-12-13'),
('2035-12-14'),
('2035-12-15'),
('2035-12-16'),
('2035-12-17'),
('2035-12-18'),
('2035-12-19'),
('2035-12-20'),
('2035-12-21'),
('2035-12-22'),
('2035-12-23'),
('2035-12-24'),
('2035-12-25'),
('2035-12-26'),
('2035-12-27'),
('2035-12-28'),
('2035-12-29'),
('2035-12-30'),
('2035-12-31'),
('2036-01-01'),
('2036-01-02'),
('2036-01-03'),
('2036-01-04'),
('2036-01-05'),
('2036-01-06'),
('2036-01-07'),
('2036-01-08'),
('2036-01-09'),
('2036-01-10'),
('2036-01-11'),
('2036-01-12'),
('2036-01-13'),
('2036-01-14'),
('2036-01-15'),
('2036-01-16'),
('2036-01-17'),
('2036-01-18'),
('2036-01-19'),
('2036-01-20'),
('2036-01-21'),
('2036-01-22'),
('2036-01-23'),
('2036-01-24'),
('2036-01-25'),
('2036-01-26'),
('2036-01-27'),
('2036-01-28'),
('2036-01-29'),
('2036-01-30'),
('2036-01-31'),
('2036-02-01'),
('2036-02-02'),
('2036-02-03'),
('2036-02-04'),
('2036-02-05'),
('2036-02-06'),
('2036-02-07'),
('2036-02-08'),
('2036-02-09'),
('2036-02-10'),
('2036-02-11'),
('2036-02-12'),
('2036-02-13'),
('2036-02-14'),
('2036-02-15'),
('2036-02-16'),
('2036-02-17'),
('2036-02-18'),
('2036-02-19'),
('2036-02-20'),
('2036-02-21'),
('2036-02-22'),
('2036-02-23'),
('2036-02-24'),
('2036-02-25'),
('2036-02-26'),
('2036-02-27'),
('2036-02-28'),
('2036-02-29'),
('2036-03-01'),
('2036-03-02'),
('2036-03-03'),
('2036-03-04'),
('2036-03-05'),
('2036-03-06'),
('2036-03-07'),
('2036-03-08'),
('2036-03-09'),
('2036-03-10'),
('2036-03-11'),
('2036-03-12'),
('2036-03-13'),
('2036-03-14'),
('2036-03-15'),
('2036-03-16'),
('2036-03-17'),
('2036-03-18'),
('2036-03-19'),
('2036-03-20'),
('2036-03-21'),
('2036-03-22'),
('2036-03-23'),
('2036-03-24'),
('2036-03-25'),
('2036-03-26'),
('2036-03-27'),
('2036-03-28'),
('2036-03-29'),
('2036-03-30'),
('2036-03-31'),
('2036-04-01'),
('2036-04-02'),
('2036-04-03'),
('2036-04-04'),
('2036-04-05'),
('2036-04-06'),
('2036-04-07'),
('2036-04-08'),
('2036-04-09'),
('2036-04-10'),
('2036-04-11'),
('2036-04-12'),
('2036-04-13'),
('2036-04-14'),
('2036-04-15'),
('2036-04-16'),
('2036-04-17'),
('2036-04-18'),
('2036-04-19'),
('2036-04-20'),
('2036-04-21'),
('2036-04-22'),
('2036-04-23'),
('2036-04-24'),
('2036-04-25'),
('2036-04-26'),
('2036-04-27'),
('2036-04-28'),
('2036-04-29'),
('2036-04-30'),
('2036-05-01'),
('2036-05-02'),
('2036-05-03'),
('2036-05-04'),
('2036-05-05'),
('2036-05-06'),
('2036-05-07'),
('2036-05-08'),
('2036-05-09'),
('2036-05-10'),
('2036-05-11'),
('2036-05-12'),
('2036-05-13'),
('2036-05-14'),
('2036-05-15'),
('2036-05-16'),
('2036-05-17'),
('2036-05-18'),
('2036-05-19'),
('2036-05-20'),
('2036-05-21'),
('2036-05-22'),
('2036-05-23'),
('2036-05-24'),
('2036-05-25'),
('2036-05-26'),
('2036-05-27'),
('2036-05-28'),
('2036-05-29'),
('2036-05-30'),
('2036-05-31'),
('2036-06-01'),
('2036-06-02'),
('2036-06-03'),
('2036-06-04'),
('2036-06-05'),
('2036-06-06'),
('2036-06-07'),
('2036-06-08'),
('2036-06-09'),
('2036-06-10'),
('2036-06-11'),
('2036-06-12'),
('2036-06-13'),
('2036-06-14'),
('2036-06-15'),
('2036-06-16'),
('2036-06-17'),
('2036-06-18'),
('2036-06-19'),
('2036-06-20'),
('2036-06-21'),
('2036-06-22'),
('2036-06-23'),
('2036-06-24'),
('2036-06-25'),
('2036-06-26'),
('2036-06-27'),
('2036-06-28'),
('2036-06-29'),
('2036-06-30'),
('2036-07-01'),
('2036-07-02'),
('2036-07-03'),
('2036-07-04'),
('2036-07-05'),
('2036-07-06'),
('2036-07-07'),
('2036-07-08'),
('2036-07-09'),
('2036-07-10'),
('2036-07-11'),
('2036-07-12'),
('2036-07-13'),
('2036-07-14'),
('2036-07-15'),
('2036-07-16'),
('2036-07-17'),
('2036-07-18'),
('2036-07-19'),
('2036-07-20'),
('2036-07-21'),
('2036-07-22'),
('2036-07-23'),
('2036-07-24'),
('2036-07-25'),
('2036-07-26'),
('2036-07-27'),
('2036-07-28'),
('2036-07-29'),
('2036-07-30'),
('2036-07-31'),
('2036-08-01'),
('2036-08-02'),
('2036-08-03'),
('2036-08-04'),
('2036-08-05'),
('2036-08-06'),
('2036-08-07'),
('2036-08-08'),
('2036-08-09'),
('2036-08-10'),
('2036-08-11'),
('2036-08-12'),
('2036-08-13'),
('2036-08-14'),
('2036-08-15'),
('2036-08-16'),
('2036-08-17'),
('2036-08-18'),
('2036-08-19'),
('2036-08-20'),
('2036-08-21'),
('2036-08-22'),
('2036-08-23'),
('2036-08-24'),
('2036-08-25'),
('2036-08-26'),
('2036-08-27'),
('2036-08-28'),
('2036-08-29'),
('2036-08-30'),
('2036-08-31'),
('2036-09-01'),
('2036-09-02'),
('2036-09-03'),
('2036-09-04'),
('2036-09-05'),
('2036-09-06'),
('2036-09-07'),
('2036-09-08'),
('2036-09-09'),
('2036-09-10'),
('2036-09-11'),
('2036-09-12'),
('2036-09-13'),
('2036-09-14'),
('2036-09-15'),
('2036-09-16'),
('2036-09-17'),
('2036-09-18'),
('2036-09-19'),
('2036-09-20'),
('2036-09-21'),
('2036-09-22'),
('2036-09-23'),
('2036-09-24'),
('2036-09-25'),
('2036-09-26'),
('2036-09-27'),
('2036-09-28'),
('2036-09-29'),
('2036-09-30'),
('2036-10-01'),
('2036-10-02'),
('2036-10-03'),
('2036-10-04'),
('2036-10-05'),
('2036-10-06'),
('2036-10-07'),
('2036-10-08'),
('2036-10-09'),
('2036-10-10'),
('2036-10-11'),
('2036-10-12'),
('2036-10-13'),
('2036-10-14'),
('2036-10-15'),
('2036-10-16'),
('2036-10-17'),
('2036-10-18'),
('2036-10-19'),
('2036-10-20'),
('2036-10-21'),
('2036-10-22'),
('2036-10-23'),
('2036-10-24'),
('2036-10-25'),
('2036-10-26'),
('2036-10-27'),
('2036-10-28'),
('2036-10-29'),
('2036-10-30'),
('2036-10-31'),
('2036-11-01'),
('2036-11-02'),
('2036-11-03'),
('2036-11-04'),
('2036-11-05'),
('2036-11-06'),
('2036-11-07'),
('2036-11-08'),
('2036-11-09'),
('2036-11-10'),
('2036-11-11'),
('2036-11-12'),
('2036-11-13'),
('2036-11-14'),
('2036-11-15'),
('2036-11-16'),
('2036-11-17'),
('2036-11-18'),
('2036-11-19'),
('2036-11-20'),
('2036-11-21'),
('2036-11-22'),
('2036-11-23'),
('2036-11-24'),
('2036-11-25'),
('2036-11-26'),
('2036-11-27'),
('2036-11-28'),
('2036-11-29'),
('2036-11-30'),
('2036-12-01'),
('2036-12-02'),
('2036-12-03'),
('2036-12-04'),
('2036-12-05'),
('2036-12-06'),
('2036-12-07'),
('2036-12-08'),
('2036-12-09'),
('2036-12-10'),
('2036-12-11'),
('2036-12-12'),
('2036-12-13'),
('2036-12-14'),
('2036-12-15'),
('2036-12-16'),
('2036-12-17'),
('2036-12-18'),
('2036-12-19'),
('2036-12-20'),
('2036-12-21'),
('2036-12-22'),
('2036-12-23'),
('2036-12-24'),
('2036-12-25'),
('2036-12-26'),
('2036-12-27'),
('2036-12-28'),
('2036-12-29'),
('2036-12-30'),
('2036-12-31'),
('2037-01-01'),
('2037-01-02'),
('2037-01-03'),
('2037-01-04'),
('2037-01-05'),
('2037-01-06'),
('2037-01-07'),
('2037-01-08'),
('2037-01-09'),
('2037-01-10'),
('2037-01-11'),
('2037-01-12'),
('2037-01-13'),
('2037-01-14'),
('2037-01-15'),
('2037-01-16'),
('2037-01-17'),
('2037-01-18'),
('2037-01-19'),
('2037-01-20'),
('2037-01-21'),
('2037-01-22'),
('2037-01-23'),
('2037-01-24'),
('2037-01-25'),
('2037-01-26'),
('2037-01-27'),
('2037-01-28'),
('2037-01-29'),
('2037-01-30'),
('2037-01-31'),
('2037-02-01'),
('2037-02-02'),
('2037-02-03'),
('2037-02-04'),
('2037-02-05'),
('2037-02-06'),
('2037-02-07'),
('2037-02-08'),
('2037-02-09'),
('2037-02-10'),
('2037-02-11'),
('2037-02-12'),
('2037-02-13'),
('2037-02-14'),
('2037-02-15'),
('2037-02-16'),
('2037-02-17'),
('2037-02-18'),
('2037-02-19'),
('2037-02-20'),
('2037-02-21'),
('2037-02-22'),
('2037-02-23'),
('2037-02-24'),
('2037-02-25'),
('2037-02-26'),
('2037-02-27'),
('2037-02-28'),
('2037-03-01'),
('2037-03-02'),
('2037-03-03'),
('2037-03-04'),
('2037-03-05'),
('2037-03-06'),
('2037-03-07'),
('2037-03-08'),
('2037-03-09'),
('2037-03-10'),
('2037-03-11'),
('2037-03-12'),
('2037-03-13'),
('2037-03-14'),
('2037-03-15'),
('2037-03-16'),
('2037-03-17'),
('2037-03-18'),
('2037-03-19'),
('2037-03-20'),
('2037-03-21'),
('2037-03-22'),
('2037-03-23'),
('2037-03-24'),
('2037-03-25'),
('2037-03-26'),
('2037-03-27'),
('2037-03-28'),
('2037-03-29'),
('2037-03-30'),
('2037-03-31'),
('2037-04-01'),
('2037-04-02'),
('2037-04-03'),
('2037-04-04'),
('2037-04-05'),
('2037-04-06'),
('2037-04-07'),
('2037-04-08'),
('2037-04-09'),
('2037-04-10'),
('2037-04-11'),
('2037-04-12'),
('2037-04-13'),
('2037-04-14'),
('2037-04-15'),
('2037-04-16'),
('2037-04-17'),
('2037-04-18'),
('2037-04-19'),
('2037-04-20'),
('2037-04-21'),
('2037-04-22'),
('2037-04-23'),
('2037-04-24'),
('2037-04-25'),
('2037-04-26'),
('2037-04-27'),
('2037-04-28'),
('2037-04-29'),
('2037-04-30'),
('2037-05-01'),
('2037-05-02'),
('2037-05-03'),
('2037-05-04'),
('2037-05-05'),
('2037-05-06'),
('2037-05-07'),
('2037-05-08'),
('2037-05-09'),
('2037-05-10'),
('2037-05-11'),
('2037-05-12'),
('2037-05-13'),
('2037-05-14'),
('2037-05-15'),
('2037-05-16'),
('2037-05-17'),
('2037-05-18'),
('2037-05-19'),
('2037-05-20'),
('2037-05-21'),
('2037-05-22'),
('2037-05-23'),
('2037-05-24'),
('2037-05-25'),
('2037-05-26'),
('2037-05-27'),
('2037-05-28'),
('2037-05-29'),
('2037-05-30'),
('2037-05-31'),
('2037-06-01'),
('2037-06-02'),
('2037-06-03'),
('2037-06-04'),
('2037-06-05'),
('2037-06-06'),
('2037-06-07'),
('2037-06-08'),
('2037-06-09'),
('2037-06-10'),
('2037-06-11'),
('2037-06-12'),
('2037-06-13'),
('2037-06-14'),
('2037-06-15'),
('2037-06-16'),
('2037-06-17'),
('2037-06-18'),
('2037-06-19'),
('2037-06-20'),
('2037-06-21'),
('2037-06-22'),
('2037-06-23'),
('2037-06-24'),
('2037-06-25'),
('2037-06-26'),
('2037-06-27'),
('2037-06-28'),
('2037-06-29'),
('2037-06-30'),
('2037-07-01'),
('2037-07-02'),
('2037-07-03'),
('2037-07-04'),
('2037-07-05'),
('2037-07-06'),
('2037-07-07'),
('2037-07-08'),
('2037-07-09'),
('2037-07-10'),
('2037-07-11'),
('2037-07-12'),
('2037-07-13'),
('2037-07-14'),
('2037-07-15'),
('2037-07-16'),
('2037-07-17'),
('2037-07-18'),
('2037-07-19'),
('2037-07-20'),
('2037-07-21'),
('2037-07-22'),
('2037-07-23'),
('2037-07-24'),
('2037-07-25'),
('2037-07-26'),
('2037-07-27'),
('2037-07-28'),
('2037-07-29'),
('2037-07-30'),
('2037-07-31'),
('2037-08-01'),
('2037-08-02'),
('2037-08-03'),
('2037-08-04'),
('2037-08-05'),
('2037-08-06'),
('2037-08-07'),
('2037-08-08'),
('2037-08-09'),
('2037-08-10'),
('2037-08-11'),
('2037-08-12'),
('2037-08-13'),
('2037-08-14'),
('2037-08-15'),
('2037-08-16'),
('2037-08-17'),
('2037-08-18'),
('2037-08-19'),
('2037-08-20'),
('2037-08-21'),
('2037-08-22'),
('2037-08-23'),
('2037-08-24'),
('2037-08-25'),
('2037-08-26'),
('2037-08-27'),
('2037-08-28'),
('2037-08-29'),
('2037-08-30'),
('2037-08-31'),
('2037-09-01'),
('2037-09-02'),
('2037-09-03'),
('2037-09-04'),
('2037-09-05'),
('2037-09-06'),
('2037-09-07'),
('2037-09-08'),
('2037-09-09'),
('2037-09-10'),
('2037-09-11'),
('2037-09-12'),
('2037-09-13'),
('2037-09-14'),
('2037-09-15'),
('2037-09-16'),
('2037-09-17'),
('2037-09-18'),
('2037-09-19'),
('2037-09-20'),
('2037-09-21'),
('2037-09-22'),
('2037-09-23'),
('2037-09-24'),
('2037-09-25'),
('2037-09-26'),
('2037-09-27'),
('2037-09-28'),
('2037-09-29'),
('2037-09-30'),
('2037-10-01'),
('2037-10-02'),
('2037-10-03'),
('2037-10-04'),
('2037-10-05'),
('2037-10-06'),
('2037-10-07'),
('2037-10-08'),
('2037-10-09'),
('2037-10-10'),
('2037-10-11'),
('2037-10-12'),
('2037-10-13'),
('2037-10-14'),
('2037-10-15'),
('2037-10-16'),
('2037-10-17'),
('2037-10-18'),
('2037-10-19'),
('2037-10-20'),
('2037-10-21'),
('2037-10-22'),
('2037-10-23'),
('2037-10-24'),
('2037-10-25'),
('2037-10-26'),
('2037-10-27'),
('2037-10-28'),
('2037-10-29'),
('2037-10-30'),
('2037-10-31'),
('2037-11-01'),
('2037-11-02'),
('2037-11-03'),
('2037-11-04'),
('2037-11-05'),
('2037-11-06'),
('2037-11-07'),
('2037-11-08'),
('2037-11-09'),
('2037-11-10'),
('2037-11-11'),
('2037-11-12'),
('2037-11-13'),
('2037-11-14'),
('2037-11-15'),
('2037-11-16'),
('2037-11-17'),
('2037-11-18'),
('2037-11-19'),
('2037-11-20'),
('2037-11-21'),
('2037-11-22'),
('2037-11-23'),
('2037-11-24'),
('2037-11-25'),
('2037-11-26'),
('2037-11-27'),
('2037-11-28'),
('2037-11-29'),
('2037-11-30'),
('2037-12-01'),
('2037-12-02'),
('2037-12-03'),
('2037-12-04'),
('2037-12-05'),
('2037-12-06'),
('2037-12-07'),
('2037-12-08'),
('2037-12-09'),
('2037-12-10'),
('2037-12-11'),
('2037-12-12'),
('2037-12-13'),
('2037-12-14'),
('2037-12-15'),
('2037-12-16'),
('2037-12-17'),
('2037-12-18'),
('2037-12-19'),
('2037-12-20'),
('2037-12-21'),
('2037-12-22'),
('2037-12-23'),
('2037-12-24'),
('2037-12-25'),
('2037-12-26'),
('2037-12-27'),
('2037-12-28'),
('2037-12-29'),
('2037-12-30'),
('2037-12-31'),
('2038-01-01'),
('2038-01-02'),
('2038-01-03'),
('2038-01-04'),
('2038-01-05'),
('2038-01-06'),
('2038-01-07'),
('2038-01-08'),
('2038-01-09'),
('2038-01-10'),
('2038-01-11'),
('2038-01-12'),
('2038-01-13'),
('2038-01-14'),
('2038-01-15'),
('2038-01-16'),
('2038-01-17'),
('2038-01-18'),
('2038-01-19'),
('2038-01-20'),
('2038-01-21'),
('2038-01-22'),
('2038-01-23'),
('2038-01-24'),
('2038-01-25'),
('2038-01-26'),
('2038-01-27'),
('2038-01-28'),
('2038-01-29'),
('2038-01-30'),
('2038-01-31'),
('2038-02-01'),
('2038-02-02'),
('2038-02-03'),
('2038-02-04'),
('2038-02-05'),
('2038-02-06'),
('2038-02-07'),
('2038-02-08'),
('2038-02-09'),
('2038-02-10'),
('2038-02-11'),
('2038-02-12'),
('2038-02-13'),
('2038-02-14'),
('2038-02-15'),
('2038-02-16'),
('2038-02-17'),
('2038-02-18'),
('2038-02-19'),
('2038-02-20'),
('2038-02-21'),
('2038-02-22'),
('2038-02-23'),
('2038-02-24'),
('2038-02-25'),
('2038-02-26'),
('2038-02-27'),
('2038-02-28'),
('2038-03-01'),
('2038-03-02'),
('2038-03-03'),
('2038-03-04'),
('2038-03-05'),
('2038-03-06'),
('2038-03-07'),
('2038-03-08'),
('2038-03-09'),
('2038-03-10'),
('2038-03-11'),
('2038-03-12'),
('2038-03-13'),
('2038-03-14'),
('2038-03-15'),
('2038-03-16'),
('2038-03-17'),
('2038-03-18'),
('2038-03-19'),
('2038-03-20'),
('2038-03-21'),
('2038-03-22'),
('2038-03-23'),
('2038-03-24'),
('2038-03-25'),
('2038-03-26'),
('2038-03-27'),
('2038-03-28'),
('2038-03-29'),
('2038-03-30'),
('2038-03-31'),
('2038-04-01'),
('2038-04-02'),
('2038-04-03'),
('2038-04-04'),
('2038-04-05'),
('2038-04-06'),
('2038-04-07'),
('2038-04-08'),
('2038-04-09'),
('2038-04-10'),
('2038-04-11'),
('2038-04-12'),
('2038-04-13'),
('2038-04-14'),
('2038-04-15'),
('2038-04-16'),
('2038-04-17'),
('2038-04-18'),
('2038-04-19'),
('2038-04-20'),
('2038-04-21'),
('2038-04-22'),
('2038-04-23'),
('2038-04-24'),
('2038-04-25'),
('2038-04-26'),
('2038-04-27'),
('2038-04-28'),
('2038-04-29'),
('2038-04-30'),
('2038-05-01'),
('2038-05-02'),
('2038-05-03'),
('2038-05-04'),
('2038-05-05'),
('2038-05-06'),
('2038-05-07'),
('2038-05-08'),
('2038-05-09'),
('2038-05-10'),
('2038-05-11'),
('2038-05-12'),
('2038-05-13'),
('2038-05-14'),
('2038-05-15'),
('2038-05-16'),
('2038-05-17'),
('2038-05-18'),
('2038-05-19'),
('2038-05-20'),
('2038-05-21'),
('2038-05-22'),
('2038-05-23'),
('2038-05-24'),
('2038-05-25'),
('2038-05-26'),
('2038-05-27'),
('2038-05-28'),
('2038-05-29'),
('2038-05-30'),
('2038-05-31'),
('2038-06-01'),
('2038-06-02'),
('2038-06-03'),
('2038-06-04'),
('2038-06-05'),
('2038-06-06'),
('2038-06-07'),
('2038-06-08'),
('2038-06-09'),
('2038-06-10'),
('2038-06-11'),
('2038-06-12'),
('2038-06-13'),
('2038-06-14'),
('2038-06-15'),
('2038-06-16'),
('2038-06-17'),
('2038-06-18'),
('2038-06-19'),
('2038-06-20'),
('2038-06-21'),
('2038-06-22'),
('2038-06-23'),
('2038-06-24'),
('2038-06-25'),
('2038-06-26'),
('2038-06-27'),
('2038-06-28'),
('2038-06-29'),
('2038-06-30'),
('2038-07-01'),
('2038-07-02'),
('2038-07-03'),
('2038-07-04'),
('2038-07-05'),
('2038-07-06'),
('2038-07-07'),
('2038-07-08'),
('2038-07-09'),
('2038-07-10'),
('2038-07-11'),
('2038-07-12'),
('2038-07-13'),
('2038-07-14'),
('2038-07-15'),
('2038-07-16'),
('2038-07-17'),
('2038-07-18'),
('2038-07-19'),
('2038-07-20'),
('2038-07-21'),
('2038-07-22'),
('2038-07-23'),
('2038-07-24'),
('2038-07-25'),
('2038-07-26'),
('2038-07-27'),
('2038-07-28'),
('2038-07-29'),
('2038-07-30'),
('2038-07-31'),
('2038-08-01'),
('2038-08-02'),
('2038-08-03'),
('2038-08-04'),
('2038-08-05'),
('2038-08-06'),
('2038-08-07'),
('2038-08-08'),
('2038-08-09'),
('2038-08-10'),
('2038-08-11'),
('2038-08-12'),
('2038-08-13'),
('2038-08-14'),
('2038-08-15'),
('2038-08-16'),
('2038-08-17'),
('2038-08-18'),
('2038-08-19'),
('2038-08-20'),
('2038-08-21'),
('2038-08-22'),
('2038-08-23'),
('2038-08-24'),
('2038-08-25'),
('2038-08-26'),
('2038-08-27'),
('2038-08-28'),
('2038-08-29'),
('2038-08-30'),
('2038-08-31'),
('2038-09-01'),
('2038-09-02'),
('2038-09-03'),
('2038-09-04'),
('2038-09-05'),
('2038-09-06'),
('2038-09-07'),
('2038-09-08'),
('2038-09-09'),
('2038-09-10'),
('2038-09-11'),
('2038-09-12'),
('2038-09-13'),
('2038-09-14'),
('2038-09-15'),
('2038-09-16'),
('2038-09-17'),
('2038-09-18'),
('2038-09-19'),
('2038-09-20');
INSERT INTO `sys_date_list` (`time`) VALUES
('2038-09-21'),
('2038-09-22'),
('2038-09-23'),
('2038-09-24'),
('2038-09-25'),
('2038-09-26'),
('2038-09-27'),
('2038-09-28'),
('2038-09-29'),
('2038-09-30'),
('2038-10-01'),
('2038-10-02'),
('2038-10-03'),
('2038-10-04'),
('2038-10-05'),
('2038-10-06'),
('2038-10-07'),
('2038-10-08'),
('2038-10-09'),
('2038-10-10'),
('2038-10-11'),
('2038-10-12'),
('2038-10-13'),
('2038-10-14'),
('2038-10-15'),
('2038-10-16'),
('2038-10-17'),
('2038-10-18'),
('2038-10-19'),
('2038-10-20'),
('2038-10-21'),
('2038-10-22'),
('2038-10-23'),
('2038-10-24'),
('2038-10-25'),
('2038-10-26'),
('2038-10-27'),
('2038-10-28'),
('2038-10-29'),
('2038-10-30'),
('2038-10-31'),
('2038-11-01'),
('2038-11-02'),
('2038-11-03'),
('2038-11-04'),
('2038-11-05'),
('2038-11-06'),
('2038-11-07'),
('2038-11-08'),
('2038-11-09'),
('2038-11-10'),
('2038-11-11'),
('2038-11-12'),
('2038-11-13'),
('2038-11-14'),
('2038-11-15'),
('2038-11-16'),
('2038-11-17'),
('2038-11-18'),
('2038-11-19'),
('2038-11-20'),
('2038-11-21'),
('2038-11-22'),
('2038-11-23'),
('2038-11-24'),
('2038-11-25'),
('2038-11-26'),
('2038-11-27'),
('2038-11-28'),
('2038-11-29'),
('2038-11-30'),
('2038-12-01'),
('2038-12-02'),
('2038-12-03'),
('2038-12-04'),
('2038-12-05'),
('2038-12-06'),
('2038-12-07'),
('2038-12-08'),
('2038-12-09'),
('2038-12-10'),
('2038-12-11'),
('2038-12-12'),
('2038-12-13'),
('2038-12-14'),
('2038-12-15'),
('2038-12-16'),
('2038-12-17'),
('2038-12-18'),
('2038-12-19'),
('2038-12-20'),
('2038-12-21'),
('2038-12-22'),
('2038-12-23'),
('2038-12-24'),
('2038-12-25'),
('2038-12-26'),
('2038-12-27'),
('2038-12-28'),
('2038-12-29'),
('2038-12-30'),
('2038-12-31'),
('2039-01-01'),
('2039-01-02'),
('2039-01-03'),
('2039-01-04'),
('2039-01-05'),
('2039-01-06'),
('2039-01-07'),
('2039-01-08'),
('2039-01-09'),
('2039-01-10'),
('2039-01-11'),
('2039-01-12'),
('2039-01-13'),
('2039-01-14'),
('2039-01-15'),
('2039-01-16'),
('2039-01-17'),
('2039-01-18'),
('2039-01-19'),
('2039-01-20'),
('2039-01-21'),
('2039-01-22'),
('2039-01-23'),
('2039-01-24'),
('2039-01-25'),
('2039-01-26'),
('2039-01-27'),
('2039-01-28'),
('2039-01-29'),
('2039-01-30'),
('2039-01-31'),
('2039-02-01'),
('2039-02-02'),
('2039-02-03'),
('2039-02-04'),
('2039-02-05'),
('2039-02-06'),
('2039-02-07'),
('2039-02-08'),
('2039-02-09'),
('2039-02-10'),
('2039-02-11'),
('2039-02-12'),
('2039-02-13'),
('2039-02-14'),
('2039-02-15'),
('2039-02-16'),
('2039-02-17'),
('2039-02-18'),
('2039-02-19'),
('2039-02-20'),
('2039-02-21'),
('2039-02-22'),
('2039-02-23'),
('2039-02-24'),
('2039-02-25'),
('2039-02-26'),
('2039-02-27'),
('2039-02-28'),
('2039-03-01'),
('2039-03-02'),
('2039-03-03'),
('2039-03-04'),
('2039-03-05'),
('2039-03-06'),
('2039-03-07'),
('2039-03-08'),
('2039-03-09'),
('2039-03-10'),
('2039-03-11'),
('2039-03-12'),
('2039-03-13'),
('2039-03-14'),
('2039-03-15'),
('2039-03-16'),
('2039-03-17'),
('2039-03-18'),
('2039-03-19'),
('2039-03-20'),
('2039-03-21'),
('2039-03-22'),
('2039-03-23'),
('2039-03-24'),
('2039-03-25'),
('2039-03-26'),
('2039-03-27'),
('2039-03-28'),
('2039-03-29'),
('2039-03-30'),
('2039-03-31'),
('2039-04-01'),
('2039-04-02'),
('2039-04-03'),
('2039-04-04'),
('2039-04-05'),
('2039-04-06'),
('2039-04-07'),
('2039-04-08'),
('2039-04-09'),
('2039-04-10'),
('2039-04-11'),
('2039-04-12'),
('2039-04-13'),
('2039-04-14'),
('2039-04-15'),
('2039-04-16'),
('2039-04-17'),
('2039-04-18'),
('2039-04-19'),
('2039-04-20'),
('2039-04-21'),
('2039-04-22'),
('2039-04-23'),
('2039-04-24'),
('2039-04-25'),
('2039-04-26'),
('2039-04-27'),
('2039-04-28'),
('2039-04-29'),
('2039-04-30'),
('2039-05-01'),
('2039-05-02'),
('2039-05-03'),
('2039-05-04'),
('2039-05-05'),
('2039-05-06'),
('2039-05-07'),
('2039-05-08'),
('2039-05-09'),
('2039-05-10'),
('2039-05-11'),
('2039-05-12'),
('2039-05-13'),
('2039-05-14'),
('2039-05-15'),
('2039-05-16'),
('2039-05-17'),
('2039-05-18'),
('2039-05-19'),
('2039-05-20'),
('2039-05-21'),
('2039-05-22'),
('2039-05-23'),
('2039-05-24'),
('2039-05-25'),
('2039-05-26'),
('2039-05-27'),
('2039-05-28'),
('2039-05-29'),
('2039-05-30'),
('2039-05-31'),
('2039-06-01'),
('2039-06-02'),
('2039-06-03'),
('2039-06-04'),
('2039-06-05'),
('2039-06-06'),
('2039-06-07'),
('2039-06-08'),
('2039-06-09'),
('2039-06-10'),
('2039-06-11'),
('2039-06-12'),
('2039-06-13'),
('2039-06-14'),
('2039-06-15'),
('2039-06-16'),
('2039-06-17'),
('2039-06-18'),
('2039-06-19'),
('2039-06-20'),
('2039-06-21'),
('2039-06-22'),
('2039-06-23'),
('2039-06-24'),
('2039-06-25'),
('2039-06-26'),
('2039-06-27'),
('2039-06-28'),
('2039-06-29'),
('2039-06-30'),
('2039-07-01'),
('2039-07-02'),
('2039-07-03'),
('2039-07-04'),
('2039-07-05'),
('2039-07-06'),
('2039-07-07'),
('2039-07-08'),
('2039-07-09'),
('2039-07-10'),
('2039-07-11'),
('2039-07-12'),
('2039-07-13'),
('2039-07-14'),
('2039-07-15'),
('2039-07-16'),
('2039-07-17'),
('2039-07-18'),
('2039-07-19'),
('2039-07-20'),
('2039-07-21'),
('2039-07-22'),
('2039-07-23'),
('2039-07-24'),
('2039-07-25'),
('2039-07-26'),
('2039-07-27'),
('2039-07-28'),
('2039-07-29'),
('2039-07-30'),
('2039-07-31'),
('2039-08-01'),
('2039-08-02'),
('2039-08-03'),
('2039-08-04'),
('2039-08-05'),
('2039-08-06'),
('2039-08-07'),
('2039-08-08'),
('2039-08-09'),
('2039-08-10'),
('2039-08-11'),
('2039-08-12'),
('2039-08-13'),
('2039-08-14'),
('2039-08-15'),
('2039-08-16'),
('2039-08-17'),
('2039-08-18'),
('2039-08-19'),
('2039-08-20'),
('2039-08-21'),
('2039-08-22'),
('2039-08-23'),
('2039-08-24'),
('2039-08-25'),
('2039-08-26'),
('2039-08-27'),
('2039-08-28'),
('2039-08-29'),
('2039-08-30'),
('2039-08-31'),
('2039-09-01'),
('2039-09-02'),
('2039-09-03'),
('2039-09-04'),
('2039-09-05'),
('2039-09-06'),
('2039-09-07'),
('2039-09-08'),
('2039-09-09'),
('2039-09-10'),
('2039-09-11'),
('2039-09-12'),
('2039-09-13'),
('2039-09-14'),
('2039-09-15'),
('2039-09-16'),
('2039-09-17'),
('2039-09-18'),
('2039-09-19'),
('2039-09-20'),
('2039-09-21'),
('2039-09-22'),
('2039-09-23'),
('2039-09-24'),
('2039-09-25'),
('2039-09-26'),
('2039-09-27'),
('2039-09-28'),
('2039-09-29'),
('2039-09-30'),
('2039-10-01'),
('2039-10-02'),
('2039-10-03'),
('2039-10-04'),
('2039-10-05'),
('2039-10-06'),
('2039-10-07'),
('2039-10-08'),
('2039-10-09'),
('2039-10-10'),
('2039-10-11'),
('2039-10-12'),
('2039-10-13'),
('2039-10-14'),
('2039-10-15'),
('2039-10-16'),
('2039-10-17'),
('2039-10-18'),
('2039-10-19'),
('2039-10-20'),
('2039-10-21'),
('2039-10-22'),
('2039-10-23'),
('2039-10-24'),
('2039-10-25'),
('2039-10-26'),
('2039-10-27'),
('2039-10-28'),
('2039-10-29'),
('2039-10-30'),
('2039-10-31'),
('2039-11-01'),
('2039-11-02'),
('2039-11-03'),
('2039-11-04'),
('2039-11-05'),
('2039-11-06'),
('2039-11-07'),
('2039-11-08'),
('2039-11-09'),
('2039-11-10'),
('2039-11-11'),
('2039-11-12'),
('2039-11-13'),
('2039-11-14'),
('2039-11-15'),
('2039-11-16'),
('2039-11-17'),
('2039-11-18'),
('2039-11-19'),
('2039-11-20'),
('2039-11-21'),
('2039-11-22'),
('2039-11-23'),
('2039-11-24'),
('2039-11-25'),
('2039-11-26'),
('2039-11-27'),
('2039-11-28'),
('2039-11-29'),
('2039-11-30'),
('2039-12-01'),
('2039-12-02'),
('2039-12-03'),
('2039-12-04'),
('2039-12-05'),
('2039-12-06'),
('2039-12-07'),
('2039-12-08'),
('2039-12-09'),
('2039-12-10'),
('2039-12-11'),
('2039-12-12'),
('2039-12-13'),
('2039-12-14'),
('2039-12-15'),
('2039-12-16'),
('2039-12-17'),
('2039-12-18'),
('2039-12-19'),
('2039-12-20'),
('2039-12-21'),
('2039-12-22'),
('2039-12-23'),
('2039-12-24'),
('2039-12-25'),
('2039-12-26'),
('2039-12-27'),
('2039-12-28'),
('2039-12-29'),
('2039-12-30'),
('2039-12-31'),
('2040-01-01'),
('2040-01-02'),
('2040-01-03'),
('2040-01-04'),
('2040-01-05'),
('2040-01-06'),
('2040-01-07'),
('2040-01-08'),
('2040-01-09'),
('2040-01-10'),
('2040-01-11'),
('2040-01-12'),
('2040-01-13'),
('2040-01-14'),
('2040-01-15'),
('2040-01-16'),
('2040-01-17'),
('2040-01-18'),
('2040-01-19'),
('2040-01-20'),
('2040-01-21'),
('2040-01-22'),
('2040-01-23'),
('2040-01-24'),
('2040-01-25'),
('2040-01-26'),
('2040-01-27'),
('2040-01-28'),
('2040-01-29'),
('2040-01-30'),
('2040-01-31'),
('2040-02-01'),
('2040-02-02'),
('2040-02-03'),
('2040-02-04'),
('2040-02-05'),
('2040-02-06'),
('2040-02-07'),
('2040-02-08'),
('2040-02-09'),
('2040-02-10'),
('2040-02-11'),
('2040-02-12'),
('2040-02-13'),
('2040-02-14'),
('2040-02-15'),
('2040-02-16'),
('2040-02-17'),
('2040-02-18'),
('2040-02-19'),
('2040-02-20'),
('2040-02-21'),
('2040-02-22'),
('2040-02-23'),
('2040-02-24'),
('2040-02-25'),
('2040-02-26'),
('2040-02-27'),
('2040-02-28'),
('2040-02-29'),
('2040-03-01'),
('2040-03-02'),
('2040-03-03'),
('2040-03-04'),
('2040-03-05'),
('2040-03-06'),
('2040-03-07'),
('2040-03-08'),
('2040-03-09'),
('2040-03-10'),
('2040-03-11'),
('2040-03-12'),
('2040-03-13'),
('2040-03-14'),
('2040-03-15'),
('2040-03-16'),
('2040-03-17'),
('2040-03-18'),
('2040-03-19'),
('2040-03-20'),
('2040-03-21'),
('2040-03-22'),
('2040-03-23'),
('2040-03-24'),
('2040-03-25'),
('2040-03-26'),
('2040-03-27'),
('2040-03-28'),
('2040-03-29'),
('2040-03-30'),
('2040-03-31'),
('2040-04-01'),
('2040-04-02'),
('2040-04-03'),
('2040-04-04'),
('2040-04-05'),
('2040-04-06'),
('2040-04-07'),
('2040-04-08'),
('2040-04-09'),
('2040-04-10'),
('2040-04-11'),
('2040-04-12'),
('2040-04-13'),
('2040-04-14'),
('2040-04-15'),
('2040-04-16'),
('2040-04-17'),
('2040-04-18'),
('2040-04-19'),
('2040-04-20'),
('2040-04-21'),
('2040-04-22'),
('2040-04-23'),
('2040-04-24'),
('2040-04-25'),
('2040-04-26'),
('2040-04-27'),
('2040-04-28'),
('2040-04-29'),
('2040-04-30'),
('2040-05-01'),
('2040-05-02'),
('2040-05-03'),
('2040-05-04'),
('2040-05-05'),
('2040-05-06'),
('2040-05-07'),
('2040-05-08'),
('2040-05-09'),
('2040-05-10'),
('2040-05-11'),
('2040-05-12'),
('2040-05-13'),
('2040-05-14'),
('2040-05-15'),
('2040-05-16'),
('2040-05-17'),
('2040-05-18'),
('2040-05-19'),
('2040-05-20'),
('2040-05-21'),
('2040-05-22'),
('2040-05-23'),
('2040-05-24'),
('2040-05-25'),
('2040-05-26'),
('2040-05-27'),
('2040-05-28'),
('2040-05-29'),
('2040-05-30'),
('2040-05-31'),
('2040-06-01'),
('2040-06-02'),
('2040-06-03'),
('2040-06-04'),
('2040-06-05'),
('2040-06-06'),
('2040-06-07'),
('2040-06-08'),
('2040-06-09'),
('2040-06-10'),
('2040-06-11'),
('2040-06-12'),
('2040-06-13'),
('2040-06-14'),
('2040-06-15'),
('2040-06-16'),
('2040-06-17'),
('2040-06-18'),
('2040-06-19'),
('2040-06-20'),
('2040-06-21'),
('2040-06-22'),
('2040-06-23'),
('2040-06-24'),
('2040-06-25'),
('2040-06-26'),
('2040-06-27'),
('2040-06-28'),
('2040-06-29'),
('2040-06-30'),
('2040-07-01'),
('2040-07-02'),
('2040-07-03'),
('2040-07-04'),
('2040-07-05'),
('2040-07-06'),
('2040-07-07'),
('2040-07-08'),
('2040-07-09'),
('2040-07-10'),
('2040-07-11'),
('2040-07-12'),
('2040-07-13'),
('2040-07-14'),
('2040-07-15'),
('2040-07-16'),
('2040-07-17'),
('2040-07-18'),
('2040-07-19'),
('2040-07-20'),
('2040-07-21'),
('2040-07-22'),
('2040-07-23'),
('2040-07-24'),
('2040-07-25'),
('2040-07-26'),
('2040-07-27'),
('2040-07-28'),
('2040-07-29'),
('2040-07-30'),
('2040-07-31'),
('2040-08-01'),
('2040-08-02'),
('2040-08-03'),
('2040-08-04'),
('2040-08-05'),
('2040-08-06'),
('2040-08-07'),
('2040-08-08'),
('2040-08-09'),
('2040-08-10'),
('2040-08-11'),
('2040-08-12'),
('2040-08-13'),
('2040-08-14'),
('2040-08-15'),
('2040-08-16'),
('2040-08-17'),
('2040-08-18'),
('2040-08-19'),
('2040-08-20'),
('2040-08-21'),
('2040-08-22'),
('2040-08-23'),
('2040-08-24'),
('2040-08-25'),
('2040-08-26'),
('2040-08-27'),
('2040-08-28'),
('2040-08-29'),
('2040-08-30'),
('2040-08-31'),
('2040-09-01'),
('2040-09-02'),
('2040-09-03'),
('2040-09-04'),
('2040-09-05'),
('2040-09-06'),
('2040-09-07'),
('2040-09-08'),
('2040-09-09'),
('2040-09-10'),
('2040-09-11'),
('2040-09-12'),
('2040-09-13'),
('2040-09-14'),
('2040-09-15'),
('2040-09-16'),
('2040-09-17'),
('2040-09-18'),
('2040-09-19'),
('2040-09-20'),
('2040-09-21'),
('2040-09-22'),
('2040-09-23'),
('2040-09-24'),
('2040-09-25'),
('2040-09-26'),
('2040-09-27'),
('2040-09-28'),
('2040-09-29'),
('2040-09-30'),
('2040-10-01'),
('2040-10-02'),
('2040-10-03'),
('2040-10-04'),
('2040-10-05'),
('2040-10-06'),
('2040-10-07'),
('2040-10-08'),
('2040-10-09'),
('2040-10-10'),
('2040-10-11'),
('2040-10-12'),
('2040-10-13'),
('2040-10-14'),
('2040-10-15'),
('2040-10-16'),
('2040-10-17'),
('2040-10-18'),
('2040-10-19'),
('2040-10-20'),
('2040-10-21'),
('2040-10-22'),
('2040-10-23'),
('2040-10-24'),
('2040-10-25'),
('2040-10-26'),
('2040-10-27'),
('2040-10-28'),
('2040-10-29'),
('2040-10-30'),
('2040-10-31'),
('2040-11-01'),
('2040-11-02'),
('2040-11-03'),
('2040-11-04'),
('2040-11-05'),
('2040-11-06'),
('2040-11-07'),
('2040-11-08'),
('2040-11-09'),
('2040-11-10'),
('2040-11-11'),
('2040-11-12'),
('2040-11-13'),
('2040-11-14'),
('2040-11-15'),
('2040-11-16'),
('2040-11-17'),
('2040-11-18'),
('2040-11-19'),
('2040-11-20'),
('2040-11-21'),
('2040-11-22'),
('2040-11-23'),
('2040-11-24'),
('2040-11-25'),
('2040-11-26'),
('2040-11-27'),
('2040-11-28'),
('2040-11-29'),
('2040-11-30'),
('2040-12-01'),
('2040-12-02'),
('2040-12-03'),
('2040-12-04'),
('2040-12-05'),
('2040-12-06'),
('2040-12-07'),
('2040-12-08'),
('2040-12-09'),
('2040-12-10'),
('2040-12-11'),
('2040-12-12'),
('2040-12-13'),
('2040-12-14'),
('2040-12-15'),
('2040-12-16'),
('2040-12-17'),
('2040-12-18'),
('2040-12-19'),
('2040-12-20'),
('2040-12-21'),
('2040-12-22'),
('2040-12-23'),
('2040-12-24'),
('2040-12-25'),
('2040-12-26'),
('2040-12-27'),
('2040-12-28'),
('2040-12-29'),
('2040-12-30'),
('2040-12-31'),
('2041-01-01'),
('2041-01-02'),
('2041-01-03'),
('2041-01-04'),
('2041-01-05'),
('2041-01-06'),
('2041-01-07'),
('2041-01-08'),
('2041-01-09'),
('2041-01-10'),
('2041-01-11'),
('2041-01-12'),
('2041-01-13'),
('2041-01-14'),
('2041-01-15'),
('2041-01-16'),
('2041-01-17'),
('2041-01-18'),
('2041-01-19'),
('2041-01-20'),
('2041-01-21'),
('2041-01-22'),
('2041-01-23'),
('2041-01-24'),
('2041-01-25'),
('2041-01-26'),
('2041-01-27'),
('2041-01-28'),
('2041-01-29'),
('2041-01-30'),
('2041-01-31'),
('2041-02-01'),
('2041-02-02'),
('2041-02-03'),
('2041-02-04'),
('2041-02-05'),
('2041-02-06'),
('2041-02-07'),
('2041-02-08'),
('2041-02-09'),
('2041-02-10'),
('2041-02-11'),
('2041-02-12'),
('2041-02-13'),
('2041-02-14'),
('2041-02-15'),
('2041-02-16'),
('2041-02-17'),
('2041-02-18'),
('2041-02-19'),
('2041-02-20'),
('2041-02-21'),
('2041-02-22'),
('2041-02-23'),
('2041-02-24'),
('2041-02-25'),
('2041-02-26'),
('2041-02-27'),
('2041-02-28'),
('2041-03-01'),
('2041-03-02'),
('2041-03-03'),
('2041-03-04'),
('2041-03-05'),
('2041-03-06'),
('2041-03-07'),
('2041-03-08'),
('2041-03-09'),
('2041-03-10'),
('2041-03-11'),
('2041-03-12'),
('2041-03-13'),
('2041-03-14'),
('2041-03-15'),
('2041-03-16'),
('2041-03-17'),
('2041-03-18'),
('2041-03-19'),
('2041-03-20'),
('2041-03-21'),
('2041-03-22'),
('2041-03-23'),
('2041-03-24'),
('2041-03-25'),
('2041-03-26'),
('2041-03-27'),
('2041-03-28'),
('2041-03-29'),
('2041-03-30'),
('2041-03-31'),
('2041-04-01'),
('2041-04-02'),
('2041-04-03'),
('2041-04-04'),
('2041-04-05'),
('2041-04-06'),
('2041-04-07'),
('2041-04-08'),
('2041-04-09'),
('2041-04-10'),
('2041-04-11'),
('2041-04-12'),
('2041-04-13'),
('2041-04-14'),
('2041-04-15'),
('2041-04-16'),
('2041-04-17'),
('2041-04-18'),
('2041-04-19'),
('2041-04-20'),
('2041-04-21'),
('2041-04-22'),
('2041-04-23'),
('2041-04-24'),
('2041-04-25'),
('2041-04-26'),
('2041-04-27'),
('2041-04-28'),
('2041-04-29'),
('2041-04-30'),
('2041-05-01'),
('2041-05-02'),
('2041-05-03'),
('2041-05-04'),
('2041-05-05'),
('2041-05-06'),
('2041-05-07'),
('2041-05-08'),
('2041-05-09'),
('2041-05-10'),
('2041-05-11'),
('2041-05-12'),
('2041-05-13'),
('2041-05-14'),
('2041-05-15'),
('2041-05-16'),
('2041-05-17'),
('2041-05-18'),
('2041-05-19'),
('2041-05-20'),
('2041-05-21'),
('2041-05-22'),
('2041-05-23'),
('2041-05-24'),
('2041-05-25'),
('2041-05-26'),
('2041-05-27'),
('2041-05-28'),
('2041-05-29'),
('2041-05-30'),
('2041-05-31'),
('2041-06-01'),
('2041-06-02'),
('2041-06-03'),
('2041-06-04'),
('2041-06-05'),
('2041-06-06'),
('2041-06-07'),
('2041-06-08'),
('2041-06-09'),
('2041-06-10'),
('2041-06-11'),
('2041-06-12'),
('2041-06-13'),
('2041-06-14'),
('2041-06-15'),
('2041-06-16'),
('2041-06-17'),
('2041-06-18'),
('2041-06-19'),
('2041-06-20'),
('2041-06-21'),
('2041-06-22'),
('2041-06-23'),
('2041-06-24'),
('2041-06-25'),
('2041-06-26'),
('2041-06-27'),
('2041-06-28'),
('2041-06-29'),
('2041-06-30'),
('2041-07-01'),
('2041-07-02'),
('2041-07-03'),
('2041-07-04'),
('2041-07-05'),
('2041-07-06'),
('2041-07-07'),
('2041-07-08'),
('2041-07-09'),
('2041-07-10'),
('2041-07-11'),
('2041-07-12'),
('2041-07-13'),
('2041-07-14'),
('2041-07-15'),
('2041-07-16'),
('2041-07-17'),
('2041-07-18'),
('2041-07-19'),
('2041-07-20'),
('2041-07-21'),
('2041-07-22'),
('2041-07-23'),
('2041-07-24'),
('2041-07-25'),
('2041-07-26'),
('2041-07-27'),
('2041-07-28'),
('2041-07-29'),
('2041-07-30'),
('2041-07-31'),
('2041-08-01'),
('2041-08-02'),
('2041-08-03'),
('2041-08-04'),
('2041-08-05'),
('2041-08-06'),
('2041-08-07'),
('2041-08-08'),
('2041-08-09'),
('2041-08-10'),
('2041-08-11'),
('2041-08-12'),
('2041-08-13'),
('2041-08-14'),
('2041-08-15'),
('2041-08-16'),
('2041-08-17'),
('2041-08-18'),
('2041-08-19'),
('2041-08-20'),
('2041-08-21'),
('2041-08-22'),
('2041-08-23'),
('2041-08-24'),
('2041-08-25'),
('2041-08-26'),
('2041-08-27'),
('2041-08-28'),
('2041-08-29'),
('2041-08-30'),
('2041-08-31'),
('2041-09-01'),
('2041-09-02'),
('2041-09-03'),
('2041-09-04'),
('2041-09-05'),
('2041-09-06'),
('2041-09-07'),
('2041-09-08'),
('2041-09-09'),
('2041-09-10'),
('2041-09-11'),
('2041-09-12'),
('2041-09-13'),
('2041-09-14'),
('2041-09-15'),
('2041-09-16'),
('2041-09-17'),
('2041-09-18'),
('2041-09-19'),
('2041-09-20'),
('2041-09-21'),
('2041-09-22'),
('2041-09-23'),
('2041-09-24'),
('2041-09-25'),
('2041-09-26'),
('2041-09-27'),
('2041-09-28'),
('2041-09-29'),
('2041-09-30'),
('2041-10-01'),
('2041-10-02'),
('2041-10-03'),
('2041-10-04'),
('2041-10-05'),
('2041-10-06'),
('2041-10-07'),
('2041-10-08'),
('2041-10-09'),
('2041-10-10'),
('2041-10-11');

-- --------------------------------------------------------

--
-- 表的结构 `sys_hook`
--

CREATE TABLE `sys_hook` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '钩子类型(1:系统钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)',
  `once` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否只允许一个插件运行(0:多个;1:一个)',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '钩子名称',
  `hook` varchar(50) NOT NULL DEFAULT '' COMMENT '钩子',
  `app` varchar(15) NOT NULL DEFAULT '' COMMENT '应用名(只有应用钩子才用)',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统钩子表';

--
-- 转存表中的数据 `sys_hook`
--

INSERT INTO `sys_hook` (`id`, `type`, `once`, `name`, `hook`, `app`, `description`) VALUES
(1, 1, 0, '应用初始化', 'app_init', 'cmf', '应用初始化'),
(2, 1, 0, '应用开始', 'app_begin', 'cmf', '应用开始'),
(3, 1, 0, '模块初始化', 'module_init', 'cmf', '模块初始化'),
(4, 1, 0, '控制器开始', 'action_begin', 'cmf', '控制器开始'),
(5, 1, 0, '视图输出过滤', 'view_filter', 'cmf', '视图输出过滤'),
(6, 1, 0, '应用结束', 'app_end', 'cmf', '应用结束'),
(7, 1, 0, '日志write方法', 'log_write', 'cmf', '日志write方法'),
(8, 1, 0, '输出结束', 'response_end', 'cmf', '输出结束'),
(9, 1, 0, '后台控制器初始化', 'admin_init', 'cmf', '后台控制器初始化'),
(10, 1, 0, '前台控制器初始化', 'home_init', 'cmf', '前台控制器初始化'),
(11, 1, 1, '发送手机验证码', 'send_mobile_verification_code', 'cmf', '发送手机验证码'),
(12, 3, 0, '模板 body标签开始', 'body_start', '', '模板 body标签开始'),
(13, 3, 0, '模板 head标签结束前', 'before_head_end', '', '模板 head标签结束前'),
(14, 3, 0, '模板底部开始', 'footer_start', '', '模板底部开始'),
(15, 3, 0, '模板底部开始之前', 'before_footer', '', '模板底部开始之前'),
(16, 3, 0, '模板底部结束之前', 'before_footer_end', '', '模板底部结束之前'),
(17, 3, 0, '模板 body 标签结束之前', 'before_body_end', '', '模板 body 标签结束之前'),
(18, 3, 0, '模板左边栏开始', 'left_sidebar_start', '', '模板左边栏开始'),
(19, 3, 0, '模板左边栏结束之前', 'before_left_sidebar_end', '', '模板左边栏结束之前'),
(20, 3, 0, '模板右边栏开始', 'right_sidebar_start', '', '模板右边栏开始'),
(21, 3, 0, '模板右边栏结束之前', 'before_right_sidebar_end', '', '模板右边栏结束之前'),
(22, 3, 1, '评论区', 'comment', '', '评论区'),
(23, 3, 1, '留言区', 'guestbook', '', '留言区'),
(24, 2, 0, '后台首页仪表盘', 'admin_dashboard', 'admin', '后台首页仪表盘'),
(25, 4, 0, '后台模板 head标签结束前', 'admin_before_head_end', '', '后台模板 head标签结束前'),
(26, 4, 0, '后台模板 body 标签结束之前', 'admin_before_body_end', '', '后台模板 body 标签结束之前'),
(27, 2, 0, '后台登录页面', 'admin_login', 'admin', '后台登录页面'),
(28, 1, 1, '前台模板切换', 'switch_theme', 'cmf', '前台模板切换'),
(29, 3, 0, '主要内容之后', 'after_content', '', '主要内容之后'),
(30, 2, 0, '文章显示之前', 'portal_before_assign_article', 'portal', '文章显示之前'),
(31, 2, 0, '后台文章保存之后', 'portal_admin_after_save_article', 'portal', '后台文章保存之后'),
(32, 2, 1, '获取上传界面', 'fetch_upload_view', 'user', '获取上传界面'),
(33, 3, 0, '主要内容之前', 'before_content', 'cmf', '主要内容之前'),
(34, 1, 0, '日志写入完成', 'log_write_done', 'cmf', '日志写入完成'),
(35, 1, 1, '后台模板切换', 'switch_admin_theme', 'cmf', '后台模板切换'),
(36, 1, 1, '验证码图片', 'captcha_image', 'cmf', '验证码图片'),
(37, 2, 1, '后台模板设计界面', 'admin_theme_design_view', 'admin', '后台模板设计界面'),
(38, 2, 1, '后台设置网站信息界面', 'admin_setting_site_view', 'admin', '后台设置网站信息界面'),
(39, 2, 1, '后台清除缓存界面', 'admin_setting_clear_cache_view', 'admin', '后台清除缓存界面'),
(40, 2, 1, '后台导航管理界面', 'admin_nav_index_view', 'admin', '后台导航管理界面'),
(41, 2, 1, '后台友情链接管理界面', 'admin_link_index_view', 'admin', '后台友情链接管理界面'),
(42, 2, 1, '后台幻灯片管理界面', 'admin_slide_index_view', 'admin', '后台幻灯片管理界面'),
(43, 2, 1, '后台管理员列表界面', 'admin_user_index_view', 'admin', '后台管理员列表界面'),
(44, 2, 1, '后台角色管理界面', 'admin_rbac_index_view', 'admin', '后台角色管理界面'),
(45, 2, 1, '门户后台文章管理列表界面', 'portal_admin_article_index_view', 'portal', '门户后台文章管理列表界面'),
(46, 2, 1, '门户后台文章分类管理列表界面', 'portal_admin_category_index_view', 'portal', '门户后台文章分类管理列表界面'),
(47, 2, 1, '门户后台页面管理列表界面', 'portal_admin_page_index_view', 'portal', '门户后台页面管理列表界面'),
(48, 2, 1, '门户后台文章标签管理列表界面', 'portal_admin_tag_index_view', 'portal', '门户后台文章标签管理列表界面'),
(49, 2, 1, '用户管理本站用户列表界面', 'user_admin_index_view', 'user', '用户管理本站用户列表界面'),
(50, 2, 1, '资源管理列表界面', 'user_admin_asset_index_view', 'user', '资源管理列表界面'),
(51, 2, 1, '用户管理第三方用户列表界面', 'user_admin_oauth_index_view', 'user', '用户管理第三方用户列表界面'),
(52, 2, 1, '后台首页界面', 'admin_index_index_view', 'admin', '后台首页界面'),
(53, 2, 1, '后台回收站界面', 'admin_recycle_bin_index_view', 'admin', '后台回收站界面'),
(54, 2, 1, '后台菜单管理界面', 'admin_menu_index_view', 'admin', '后台菜单管理界面'),
(55, 2, 1, '后台自定义登录是否开启钩子', 'admin_custom_login_open', 'admin', '后台自定义登录是否开启钩子'),
(56, 4, 0, '门户后台文章添加编辑界面右侧栏', 'portal_admin_article_edit_view_right_sidebar', 'portal', '门户后台文章添加编辑界面右侧栏'),
(57, 4, 0, '门户后台文章添加编辑界面主要内容', 'portal_admin_article_edit_view_main', 'portal', '门户后台文章添加编辑界面主要内容'),
(58, 2, 1, '门户后台文章添加界面', 'portal_admin_article_add_view', 'portal', '门户后台文章添加界面'),
(59, 2, 1, '门户后台文章编辑界面', 'portal_admin_article_edit_view', 'portal', '门户后台文章编辑界面'),
(60, 2, 1, '门户后台文章分类添加界面', 'portal_admin_category_add_view', 'portal', '门户后台文章分类添加界面'),
(61, 2, 1, '门户后台文章分类编辑界面', 'portal_admin_category_edit_view', 'portal', '门户后台文章分类编辑界面'),
(62, 2, 1, '门户后台页面添加界面', 'portal_admin_page_add_view', 'portal', '门户后台页面添加界面'),
(63, 2, 1, '门户后台页面编辑界面', 'portal_admin_page_edit_view', 'portal', '门户后台页面编辑界面'),
(64, 2, 1, '后台幻灯片页面列表界面', 'admin_slide_item_index_view', 'admin', '后台幻灯片页面列表界面'),
(65, 2, 1, '后台幻灯片页面添加界面', 'admin_slide_item_add_view', 'admin', '后台幻灯片页面添加界面'),
(66, 2, 1, '后台幻灯片页面编辑界面', 'admin_slide_item_edit_view', 'admin', '后台幻灯片页面编辑界面'),
(67, 2, 1, '后台管理员添加界面', 'admin_user_add_view', 'admin', '后台管理员添加界面'),
(68, 2, 1, '后台管理员编辑界面', 'admin_user_edit_view', 'admin', '后台管理员编辑界面'),
(69, 2, 1, '后台角色添加界面', 'admin_rbac_role_add_view', 'admin', '后台角色添加界面'),
(70, 2, 1, '后台角色编辑界面', 'admin_rbac_role_edit_view', 'admin', '后台角色编辑界面'),
(71, 2, 1, '后台角色授权界面', 'admin_rbac_authorize_view', 'admin', '后台角色授权界面');

-- --------------------------------------------------------

--
-- 表的结构 `sys_hook_plugin`
--

CREATE TABLE `sys_hook_plugin` (
  `id` int(10) UNSIGNED NOT NULL,
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `hook` varchar(50) NOT NULL DEFAULT '' COMMENT '钩子名',
  `plugin` varchar(50) NOT NULL DEFAULT '' COMMENT '插件'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统钩子插件表';

--
-- 转存表中的数据 `sys_hook_plugin`
--

INSERT INTO `sys_hook_plugin` (`id`, `list_order`, `status`, `hook`, `plugin`) VALUES
(1, 10000, 1, 'fetch_upload_view', 'BaiduBac'),
(2, 10000, 1, 'fetch_upload_view', 'AliOss'),
(3, 10000, 1, 'fetch_upload_view', 'Qcloud');

-- --------------------------------------------------------

--
-- 表的结构 `sys_nav`
--

CREATE TABLE `sys_nav` (
  `id` int(10) UNSIGNED NOT NULL,
  `is_main` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否为主导航;1:是;0:不是',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '导航位置名称',
  `remark` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前台导航位置表';

--
-- 转存表中的数据 `sys_nav`
--

INSERT INTO `sys_nav` (`id`, `is_main`, `name`, `remark`) VALUES
(1, 1, '主导航', '主导航'),
(2, 0, '底部导航', '');

-- --------------------------------------------------------

--
-- 表的结构 `sys_nav_menu`
--

CREATE TABLE `sys_nav_menu` (
  `id` int(11) NOT NULL,
  `nav_id` int(11) NOT NULL DEFAULT '0' COMMENT '导航 id',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父 id',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态;1:显示;0:隐藏',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `target` varchar(10) NOT NULL DEFAULT '' COMMENT '打开方式',
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接',
  `icon` varchar(20) NOT NULL DEFAULT '' COMMENT '图标',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '层级关系'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前台导航菜单表';

--
-- 转存表中的数据 `sys_nav_menu`
--

INSERT INTO `sys_nav_menu` (`id`, `nav_id`, `parent_id`, `status`, `list_order`, `name`, `target`, `href`, `icon`, `path`) VALUES
(1, 1, 0, 1, 0, '首页', '', 'home', '', '0-1');

-- --------------------------------------------------------

--
-- 表的结构 `sys_option`
--

CREATE TABLE `sys_option` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `autoload` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否自动加载;1:自动加载;0:不自动加载',
  `option_name` varchar(64) NOT NULL DEFAULT '' COMMENT '配置名',
  `option_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='全站配置表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `sys_option`
--

INSERT INTO `sys_option` (`id`, `autoload`, `option_name`, `option_value`) VALUES
(1, 1, 'site_info', '{\"site_seo_title\":\"\",\"site_seo_keywords\":\"\",\"site_seo_description\":\"\"}'),
(2, 1, 'smtp_setting', '{\"from_name\":\"\\u6eaa\\u8c37\\u8054\\u8fd0\\u5e73\\u53f0\",\"from\":\"\",\"host\":\"smtp.exmail.qq.com\",\"smtp_secure\":\"ssl\",\"port\":\"465\",\"username\":\"\",\"password\":\"\"}'),
(3, 1, 'upload_setting', '{\"max_files\":\"20\",\"chunk_size\":\"512\",\"file_types\":{\"image\":{\"upload_max_filesize\":\"10240\",\"extensions\":\"jpg,jpeg,png,gif,bmp4\"},\"video\":{\"upload_max_filesize\":\"10240\",\"extensions\":\"mp4,avi,wmv,rm,rmvb,mkv\"},\"audio\":{\"upload_max_filesize\":\"10240\",\"extensions\":\"mp3,wma,wav\"},\"file\":{\"upload_max_filesize\":\"1024000\",\"extensions\":\"txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,apk,ipa\"}}}'),
(4, 1, 'cmf_settings', '{\"open_registration\":\"0\",\"banned_usernames\":\"\"}'),
(5, 1, 'cdn_settings', '{\"cdn_static_root\":\"\"}'),
(6, 1, 'admin_settings', ''),
(7, 1, 'storage', '{\"storages\":{\"AliOss\":{\"name\":\"\\u963f\\u91cc\\u4e91\\u5b58\\u50a8\",\"driver\":\"\\\\plugins\\\\ali_oss\\\\lib\\\\AliOss\"},\"BaiduBac\":{\"name\":\"\\u767e\\u5ea6\\u4e91\\u5b58\\u50a8\",\"driver\":\"\\\\plugins\\\\baidu_bac\\\\lib\\\\BaiduBac\"},\"Qcloud\":{\"name\":\"\\u817e\\u8baf\\u4e91\\u5b58\\u50a8\",\"driver\":\"\\\\plugins\\\\qcloud\\\\lib\\\\Qcloud\"}},\"type\":\"Local\"}'),
(8, 1, 'sdk_set', '{\"suspend_icon\":\"\",\"suspend_show_status\":\"1\",\"loginout_status\":\"1\",\"set_type\":\"sdk_set\"}'),
(9, 1, 'vip_set', NULL),
(10, 1, 'wap_set', NULL),
(11, 1, 'media_set', '{\"pc_set_title\":\"\",\"pc_set_for_the_record\":\"\",\"pc_set_telecom_license\":\"\",\"pc_set_license\":\"\",\"pc_set_for_the_record_icp\":\"\",\"pc_set_copyright\":\"\",\"pc_set_meta_desc\":\"\",\"pc_game_prompt\":\"\",\"pc_cache\":\"0\",\"pc_set_logo\":\"\",\"pc_set_ico\":\"\",\"pc_set_qrcode_name\":\"\",\"pc_set_qrcode\":\"\",\"pc_set_logo_foot\":\"\",\"set_type\":\"media_set\",\"pc_set_server_qq\":\"\",\"pc_set_server_emai\":\"\",\"pc_t_email\":\"\",\"pc_set_custody_tel\":\"\",\"pc_set_server_tel\":\"\",\"pc_set_fax\":\"\",\"pc_worker\":\"\",\"pc_qq_group\":\"\",\"pc_qq_group_key\":\"\",\"pc_work_time\":\"\",\"pc_set_address\":\"\",\"pc_recipients\":\"\",\"pc_zip_code\":\"\",\"pc_guardianship1\":\"\",\"pc_guardianship2\":\"\",\"pc_guardianship3\":\"\",\"pc_navigation_index\":\"\",\"pc_navigation_game\":\"\",\"pc_navigation_gift\":\"\",\"pc_navigation_spend\":\"\",\"pc_navigation_user\":\"\",\"pc_navigation_info\":\"\",\"pc_user_allow_register\":\"1\"}'),
(12, 1, 'promote_set', '{\"ch_set_title\":\"\",\"ch_set_meta_key\":\"\",\"ch_set_license\":\"\",\"ch_set_for_the_record\":\"\",\"ch_set_copyright\":\"\",\"ch_set_meta_desc\":\"\",\"ch_set_server_tel\":\"\",\"ch_set_server_qq\":\"\",\"ch_set_server_email\":\"\",\"ch_set_server_service_time\":\"\",\"ch_set_address\":\"\",\"ch_user_allow_register\":\"1\",\"ch_set_logo\":\"\",\"ch_set_ico\":\"\",\"ch_logo_backstage\":\"\",\"ch_set_about_us\":\"\",\"ch_set_share_logo\":\"\",\"ch_share_title\":\"\",\"ch_share_describe\":\"\",\"set_type\":\"promote_set\"}'),
(13, 1, 'admin_set', '{\"web_site_title\":\"\",\"web_site\":\"\",\"web_set_license\":\"\",\"web_set_for_the_record\":\"\",\"web_set_copyright\":\"\",\"web_set_meta_desc\":\"\",\"web_set_telecom_license\":\"\",\"web_set_ico\":\"\",\"web_set_logo\":\"\",\"set_type\":\"admin_set\",\"auto_verify_index\":\"test\",\"auto_verify_admin\":\"test\",\"admin_allow_ip\":\"\",\"web_cache\":\"1\"}'),
(14, 1, 'email_template_verification_code', '{\"subject\":\"\\u6ce8\\u518c\\u9a8c\\u8bc1\\u7801\",\"template\":\"&lt;p&gt;\\u672c\\u90ae\\u4ef6\\u6765\\u81ea***&lt;br style=&quot;white-space: normal;&quot;/&gt;&amp;nbsp; &amp;nbsp; &amp;nbsp;\\u5c0a\\u656c\\u7684\\u7528\\u6237\\uff0c\\u60a8\\u597d\\u3002\\u60a8\\u7684\\u9a8c\\u8bc1\\u7801\\u662f{$code}\\u3002&lt;/p&gt;\"}'),
(15, 1, 'cash_set', '{\"limit_money\":\"\",\"payment_fee\":\"\",\"set_type\":\"cash_set\"}'),
(16, 1, 'withdraw_set', '{\"limit_money\":\"0\",\"payment_fee\":\"\",\"limit_cash\":\"500\",\"limit_count\":\"14\",\"pay_type\":\"0\",\"alipay_show\":\"1\",\"weixin_show\":\"1\",\"set_type\":\"withdraw_set\"}'),
(17, 1, 'ptb_send_quota', '{\"value\":\"500\"}'),
(18, 1, 'ptb_channel_send_quota', '{\"value\":\"500\"}'),
(29, 1, 'ptb_cash_out_set', NULL),
(30, 1, 'promote_level_set', '');

-- --------------------------------------------------------

--
-- 表的结构 `sys_plugin`
--

CREATE TABLE `sys_plugin` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '自增id',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '插件类型;1:网站;8:微信',
  `has_admin` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否有后台管理,0:没有;1:有',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态;1:开启;0:禁用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '插件安装时间',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '插件标识名,英文字母(惟一)',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件名称',
  `demo_url` varchar(50) NOT NULL DEFAULT '' COMMENT '演示地址，带协议',
  `hooks` varchar(255) NOT NULL DEFAULT '' COMMENT '实现的钩子;以“,”分隔',
  `author` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件作者',
  `author_url` varchar(50) NOT NULL DEFAULT '' COMMENT '作者网站链接',
  `version` varchar(20) NOT NULL DEFAULT '' COMMENT '插件版本号',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '插件描述',
  `config` text COMMENT '插件配置'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='插件表';

--
-- 转存表中的数据 `sys_plugin`
--

INSERT INTO `sys_plugin` (`id`, `type`, `has_admin`, `status`, `create_time`, `name`, `title`, `demo_url`, `hooks`, `author`, `author_url`, `version`, `description`, `config`) VALUES
(1, 1, 0, 1, 0, 'BaiduBac', '百度BOS', '', '', 'Yyh', '', '1.0.0', '百度云存储', '{\"bucket\":\"test\",\"accesskeyid\":\"test\",\"accesskeysecret\":\"test\",\"domain\":\"test\"}'),
(2, 1, 0, 1, 0, 'AliOss', '阿里云存储', '', '', 'Yyh', '', '1.0.0', '阿里云存储', '{\"bucket\":\"test\",\"accesskeyid\":\"test\",\"accesskeysecret\":\"test\",\"domain\":\"test\",\"internal\":\"0\"}'),
(3, 1, 0, 1, 0, 'Qcloud', '腾讯云COS', '', '', 'Yyh', '', '2.0.8', '腾讯云存储', '{\"bucket\":\"test\",\"schema\":\"http\",\"region\":\"test\",\"secretId\":\"test\",\"secretKey\":\"test\"}');

-- --------------------------------------------------------

--
-- 表的结构 `sys_portal_category`
--

CREATE TABLE `sys_portal_category` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '分类id',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类父id',
  `post_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类文章数',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:发布,0:不发布',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '分类描述',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '分类层级关系路径',
  `seo_title` varchar(100) NOT NULL DEFAULT '',
  `seo_keywords` varchar(255) NOT NULL DEFAULT '',
  `seo_description` varchar(255) NOT NULL DEFAULT '',
  `list_tpl` varchar(50) NOT NULL DEFAULT '' COMMENT '分类列表模板',
  `one_tpl` varchar(50) NOT NULL DEFAULT '' COMMENT '分类文章页模板',
  `more` text COMMENT '扩展属性'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='portal应用 文章分类表';

--
-- 转存表中的数据 `sys_portal_category`
--

INSERT INTO `sys_portal_category` (`id`, `parent_id`, `post_count`, `status`, `delete_time`, `list_order`, `name`, `description`, `path`, `seo_title`, `seo_keywords`, `seo_description`, `list_tpl`, `one_tpl`, `more`) VALUES
(1, 0, 0, 1, 0, 10000, '活动资讯', '', '0-1', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(2, 1, 0, 1, 0, 10000, '资讯', '', '0-1-2', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(3, 1, 0, 1, 0, 10000, '活动', '', '0-1-3', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(4, 1, 0, 1, 0, 10000, '公告', '', '0-1-4', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(5, 1, 0, 1, 0, 10000, '攻略', '', '0-1-5', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(6, 0, 0, 1, 0, 10000, '文档管理', '', '0-6', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}'),
(7, 6, 0, 1, 0, 10000, '文档', '', '0-6-7', '', '', '', 'list', 'article', '{\"thumbnail\":\"\"}');

-- --------------------------------------------------------

--
-- 表的结构 `sys_portal_category_post`
--

CREATE TABLE `sys_portal_category_post` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章id',
  `category_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类id',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:发布;0:不发布'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='portal应用 分类文章对应表';

--
-- 转存表中的数据 `sys_portal_category_post`
--

INSERT INTO `sys_portal_category_post` (`id`, `post_id`, `category_id`, `list_order`, `status`) VALUES
(1, 14, 7, 10000, 1),
(2, 27, 7, 10000, 1),
(3, 12, 7, 10000, 1),
(4, 28, 7, 10000, 1),
(5, 13, 7, 10000, 1),
(6, 15, 7, 10000, 1),
(7, 16, 7, 10000, 1),
(8, 18, 7, 10000, 1),
(9, 17, 7, 10000, 1),
(10, 1, 7, 10000, 1),
(11, 2, 7, 10000, 1);

-- --------------------------------------------------------

--
-- 表的结构 `sys_portal_post`
--

CREATE TABLE `sys_portal_post` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父级id',
  `post_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '类型,1:文章;2:页面',
  `post_format` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '内容格式;1:html;2:md',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发表者用户id',
  `post_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态;1:已发布;0:未发布;',
  `comment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '评论状态;1:允许;0:不允许',
  `is_top` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否置顶;1:置顶;0:不置顶',
  `recommended` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否推荐;1:推荐;0:不推荐',
  `post_hits` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '查看数',
  `post_favorites` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '收藏数',
  `post_like` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论数',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `published_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
  `post_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'post标题',
  `post_keywords` varchar(150) NOT NULL DEFAULT '' COMMENT 'seo keywords',
  `post_excerpt` varchar(500) NOT NULL DEFAULT '' COMMENT 'post摘要',
  `post_source` varchar(150) NOT NULL DEFAULT '' COMMENT '转载文章的来源',
  `thumbnail` varchar(100) NOT NULL DEFAULT '' COMMENT '缩略图',
  `thumbnail2` varchar(100) NOT NULL DEFAULT '' COMMENT '封面图2',
  `post_content` mediumtext COMMENT '文章内容',
  `post_content_filtered` text COMMENT '处理过的文章内容',
  `more` text COMMENT '扩展属性,如缩略图;格式为json',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `website` int(2) NOT NULL DEFAULT '0' COMMENT '显示站点',
  `role_ids` text NOT NULL COMMENT '管理员角色id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='portal应用 文章表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `sys_portal_post`
--

INSERT INTO `sys_portal_post` (`id`, `parent_id`, `post_type`, `post_format`, `user_id`, `post_status`, `comment_status`, `is_top`, `recommended`, `post_hits`, `post_favorites`, `post_like`, `comment_count`, `create_time`, `update_time`, `published_time`, `delete_time`, `post_title`, `post_keywords`, `post_excerpt`, `post_source`, `thumbnail`, `thumbnail2`, `post_content`, `post_content_filtered`, `more`, `sort`, `game_id`, `start_time`, `end_time`, `website`, `role_ids`) VALUES
(1, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346531, 1624867284, 0, 0, '应用权限', '', '应用权限', '', '', '', '', '', '', 11, 0, 1624867284, 0, 2, ''),
(2, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1627551763, 1627551763, 0, 0, '用户注销协议', '', '用户注销协议', '', '', '', '', '', '', 11, 0, 1627551818, 0, 10, ''),
(12, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346588, 1568259239, 0, 0, '商务合作', '', '商务合作', '', '', '', NULL, '', '', 9, 0, 1568259239, 0, 1, ''),
(13, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346588, 1567754878, 0, 0, '关于我们', '', '关于我们', '', '', '', NULL, '', '', 6, 0, 1567754878, 0, 8, ''),
(14, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346503, 1568259126, 0, 0, '关于我们', '', '关于我们', '', '', '', NULL, '', '', 8, 0, 1568259126, 0, 1, ''),
(15, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346588, 1561346588, 0, 0, '最终用户协议', '', '最终用户协议', '', '', '', NULL, '', '', 5, 0, 1561346574, 0, 8, ''),
(16, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346588, 1561346588, 0, 0, '隐私协议', '', '隐私协议', '', '', '', '', '', '', 5, 0, 1561346574, 0, 9, ''),
(17, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346606, 1568260168, 0, 0, '合作规则说明', '', '合作规则说明', '', '', '', '', '', '', 0, 0, 1568260168, 0, 8, ''),
(18, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346588, 1561346588, 0, 0, '企业招聘', '', '企业招聘', '', '', '', '', '', '', 5, 0, 1561346574, 0, 9, ''),
(27, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346531, 1568260177, 0, 0, '用户注册协议', '', '用户注册协议', '', '', '', NULL, '', '', 7, 0, 1568260177, 0, 9, ''),
(28, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1561346606, 1568260168, 0, 0, '合作伙伴', '', '合作伙伴', '', '', '', NULL, '', '', 10, 0, 1568260168, 0, 1, '');

-- --------------------------------------------------------

--
-- 表的结构 `sys_portal_tag`
--

CREATE TABLE `sys_portal_tag` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '分类id',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:发布,0:不发布',
  `recommended` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否推荐;1:推荐;0:不推荐',
  `post_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '标签文章数',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='portal应用 文章标签表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_portal_tag_post`
--

CREATE TABLE `sys_portal_tag_post` (
  `id` bigint(20) NOT NULL,
  `tag_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '标签 id',
  `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章 id',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:发布;0:不发布'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='portal应用 标签文章对应表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_recycle_bin`
--

CREATE TABLE `sys_recycle_bin` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `object_id` int(11) DEFAULT '0' COMMENT '删除内容 id',
  `create_time` int(10) UNSIGNED DEFAULT '0' COMMENT '创建时间',
  `table_name` varchar(60) DEFAULT '' COMMENT '删除内容所在表名',
  `name` varchar(255) DEFAULT '' COMMENT '删除内容名称',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=' 回收站';

-- --------------------------------------------------------

--
-- 表的结构 `sys_role`
--

CREATE TABLE `sys_role` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父角色ID',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态;0:禁用;1:正常',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `list_order` float NOT NULL DEFAULT '0' COMMENT '排序',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

--
-- 转存表中的数据 `sys_role`
--

INSERT INTO `sys_role` (`id`, `parent_id`, `status`, `create_time`, `update_time`, `list_order`, `name`, `remark`) VALUES
(1, 0, 1, 1329633709, 1329633709, 0, '超级管理员', '拥有网站最高管理员权限！');

-- --------------------------------------------------------

--
-- 表的结构 `sys_role_user`
--

CREATE TABLE `sys_role_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '角色 id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色对应表';

--
-- 转存表中的数据 `sys_role_user`
--

INSERT INTO `sys_role_user` (`id`, `role_id`, `user_id`) VALUES
(2, 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `sys_route`
--

CREATE TABLE `sys_route` (
  `id` int(11) NOT NULL COMMENT '路由id',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态;1:启用,0:不启用',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'URL规则类型;1:用户自定义;2:别名添加',
  `full_url` varchar(255) NOT NULL DEFAULT '' COMMENT '完整url',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '实际显示的url'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='url路由表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_slide`
--

CREATE TABLE `sys_slide` (
  `id` int(11) NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:显示,0不显示',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
  `name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '幻灯片分类',
  `remark` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '分类备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='幻灯片表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_slide_item`
--

CREATE TABLE `sys_slide_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `slide_id` int(11) NOT NULL DEFAULT '0' COMMENT '幻灯片id',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态,1:显示;0:隐藏',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '幻灯片名称',
  `image` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '幻灯片图片',
  `url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '幻灯片链接',
  `target` varchar(10) NOT NULL DEFAULT '' COMMENT '友情链接打开方式',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '幻灯片描述',
  `content` text CHARACTER SET utf8 COMMENT '幻灯片内容',
  `more` text COMMENT '扩展信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='幻灯片子项表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_theme`
--

CREATE TABLE `sys_theme` (
  `id` int(11) NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '安装时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后升级时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '模板状态,1:正在使用;0:未使用',
  `is_compiled` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否为已编译模板',
  `theme` varchar(20) NOT NULL DEFAULT '' COMMENT '主题目录名，用于主题的维一标识',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '主题名称',
  `version` varchar(20) NOT NULL DEFAULT '' COMMENT '主题版本号',
  `demo_url` varchar(50) NOT NULL DEFAULT '' COMMENT '演示地址，带协议',
  `thumbnail` varchar(100) NOT NULL DEFAULT '' COMMENT '缩略图',
  `author` varchar(20) NOT NULL DEFAULT '' COMMENT '主题作者',
  `author_url` varchar(50) NOT NULL DEFAULT '' COMMENT '作者网站链接',
  `lang` varchar(10) NOT NULL DEFAULT '' COMMENT '支持语言',
  `keywords` varchar(50) NOT NULL DEFAULT '' COMMENT '主题关键字',
  `description` varchar(100) NOT NULL DEFAULT '' COMMENT '主题描述'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `sys_theme`
--

INSERT INTO `sys_theme` (`id`, `create_time`, `update_time`, `status`, `is_compiled`, `theme`, `name`, `version`, `demo_url`, `thumbnail`, `author`, `author_url`, `lang`, `keywords`, `description`) VALUES
(1, 0, 0, 0, 0, 'simpleboot3', 'simpleboot3', '1.0.2', 'http://demo.thinkcmf.com', '', 'ThinkCMF', 'http://www.thinkcmf.com', 'zh-cn', 'ThinkCMF模板', 'ThinkCMF默认模板');

-- --------------------------------------------------------

--
-- 表的结构 `sys_theme_file`
--

CREATE TABLE `sys_theme_file` (
  `id` int(11) NOT NULL,
  `is_public` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否公共的模板文件',
  `list_order` float NOT NULL DEFAULT '10000' COMMENT '排序',
  `theme` varchar(20) NOT NULL DEFAULT '' COMMENT '模板名称',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '模板文件名',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作',
  `file` varchar(50) NOT NULL DEFAULT '' COMMENT '模板文件，相对于模板根目录，如Portal/index.html',
  `description` varchar(100) NOT NULL DEFAULT '' COMMENT '模板文件描述',
  `more` text COMMENT '模板更多配置,用户自己后台设置的',
  `config_more` text COMMENT '模板更多配置,来源模板的配置文件',
  `draft_more` text COMMENT '模板更多配置,用户临时保存的配置'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `sys_theme_file`
--

INSERT INTO `sys_theme_file` (`id`, `is_public`, `list_order`, `theme`, `name`, `action`, `file`, `description`, `more`, `config_more`, `draft_more`) VALUES
(17, 0, 10, 'simpleboot3', '文章页', 'portal/Article/index', 'portal/article', '文章页模板文件', '{\"vars\":{\"hot_articles_category_id\":{\"title\":\"Hot Articles\\u5206\\u7c7bID\",\"value\":\"1\",\"type\":\"text\",\"tip\":\"\",\"rule\":[]}}}', '{\"vars\":{\"hot_articles_category_id\":{\"title\":\"Hot Articles\\u5206\\u7c7bID\",\"value\":\"1\",\"type\":\"text\",\"tip\":\"\",\"rule\":[]}}}', NULL),
(18, 0, 10, 'simpleboot3', '联系我们页', 'portal/Page/index', 'portal/contact', '联系我们页模板文件', '{\"vars\":{\"baidu_map_info_window_text\":{\"title\":\"\\u767e\\u5ea6\\u5730\\u56fe\\u6807\\u6ce8\\u6587\\u5b57\",\"name\":\"baidu_map_info_window_text\",\"value\":\"ThinkCMF<br\\/><span class=\'\'>\\u5730\\u5740\\uff1a\\u4e0a\\u6d77\\u5e02\\u5f90\\u6c47\\u533a\\u659c\\u571f\\u8def2601\\u53f7<\\/span>\",\"type\":\"text\",\"tip\":\"\\u767e\\u5ea6\\u5730\\u56fe\\u6807\\u6ce8\\u6587\\u5b57,\\u652f\\u6301\\u7b80\\u5355html\\u4ee3\\u7801\",\"rule\":[]},\"company_location\":{\"title\":\"\\u516c\\u53f8\\u5750\\u6807\",\"value\":\"\",\"type\":\"location\",\"tip\":\"\",\"rule\":{\"require\":true}},\"address_cn\":{\"title\":\"\\u516c\\u53f8\\u5730\\u5740\",\"value\":\"\\u4e0a\\u6d77\\u5e02\\u5f90\\u6c47\\u533a\\u659c\\u571f\\u8def0001\\u53f7\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"address_en\":{\"title\":\"\\u516c\\u53f8\\u5730\\u5740\\uff08\\u82f1\\u6587\\uff09\",\"value\":\"NO.0001 Xie Tu Road, Shanghai China\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"email\":{\"title\":\"\\u516c\\u53f8\\u90ae\\u7bb1\",\"value\":\"catman@thinkcmf.com\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"phone_cn\":{\"title\":\"\\u516c\\u53f8\\u7535\\u8bdd\",\"value\":\"021 1000 0001\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"phone_en\":{\"title\":\"\\u516c\\u53f8\\u7535\\u8bdd\\uff08\\u82f1\\u6587\\uff09\",\"value\":\"+8621 1000 0001\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"qq\":{\"title\":\"\\u8054\\u7cfbQQ\",\"value\":\"478519726\",\"type\":\"text\",\"tip\":\"\\u591a\\u4e2a QQ\\u4ee5\\u82f1\\u6587\\u9017\\u53f7\\u9694\\u5f00\",\"rule\":{\"require\":true}}}}', '{\"vars\":{\"baidu_map_info_window_text\":{\"title\":\"\\u767e\\u5ea6\\u5730\\u56fe\\u6807\\u6ce8\\u6587\\u5b57\",\"name\":\"baidu_map_info_window_text\",\"value\":\"ThinkCMF<br\\/><span class=\'\'>\\u5730\\u5740\\uff1a\\u4e0a\\u6d77\\u5e02\\u5f90\\u6c47\\u533a\\u659c\\u571f\\u8def2601\\u53f7<\\/span>\",\"type\":\"text\",\"tip\":\"\\u767e\\u5ea6\\u5730\\u56fe\\u6807\\u6ce8\\u6587\\u5b57,\\u652f\\u6301\\u7b80\\u5355html\\u4ee3\\u7801\",\"rule\":[]},\"company_location\":{\"title\":\"\\u516c\\u53f8\\u5750\\u6807\",\"value\":\"\",\"type\":\"location\",\"tip\":\"\",\"rule\":{\"require\":true}},\"address_cn\":{\"title\":\"\\u516c\\u53f8\\u5730\\u5740\",\"value\":\"\\u4e0a\\u6d77\\u5e02\\u5f90\\u6c47\\u533a\\u659c\\u571f\\u8def0001\\u53f7\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"address_en\":{\"title\":\"\\u516c\\u53f8\\u5730\\u5740\\uff08\\u82f1\\u6587\\uff09\",\"value\":\"NO.0001 Xie Tu Road, Shanghai China\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"email\":{\"title\":\"\\u516c\\u53f8\\u90ae\\u7bb1\",\"value\":\"catman@thinkcmf.com\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"phone_cn\":{\"title\":\"\\u516c\\u53f8\\u7535\\u8bdd\",\"value\":\"021 1000 0001\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"phone_en\":{\"title\":\"\\u516c\\u53f8\\u7535\\u8bdd\\uff08\\u82f1\\u6587\\uff09\",\"value\":\"+8621 1000 0001\",\"type\":\"text\",\"tip\":\"\",\"rule\":{\"require\":true}},\"qq\":{\"title\":\"\\u8054\\u7cfbQQ\",\"value\":\"478519726\",\"type\":\"text\",\"tip\":\"\\u591a\\u4e2a QQ\\u4ee5\\u82f1\\u6587\\u9017\\u53f7\\u9694\\u5f00\",\"rule\":{\"require\":true}}}}', NULL),
(19, 0, 5, 'simpleboot3', '首页', 'portal/Index/index', 'portal/index', '首页模板文件', '{\"vars\":{\"top_slide\":{\"title\":\"\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"admin\\/Slide\\/index\",\"multi\":false},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"tip\":\"\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"rule\":{\"require\":true}}},\"widgets\":{\"features\":{\"title\":\"\\u5feb\\u901f\\u4e86\\u89e3ThinkCMF\",\"display\":\"1\",\"vars\":{\"sub_title\":{\"title\":\"\\u526f\\u6807\\u9898\",\"value\":\"Quickly understand the ThinkCMF\",\"type\":\"text\",\"placeholder\":\"\\u8bf7\\u8f93\\u5165\\u526f\\u6807\\u9898\",\"tip\":\"\",\"rule\":{\"require\":true}},\"features\":{\"title\":\"\\u7279\\u6027\\u4ecb\\u7ecd\",\"value\":[{\"title\":\"MVC\\u5206\\u5c42\\u6a21\\u5f0f\",\"icon\":\"bars\",\"content\":\"\\u4f7f\\u7528MVC\\u5e94\\u7528\\u7a0b\\u5e8f\\u88ab\\u5206\\u6210\\u4e09\\u4e2a\\u6838\\u5fc3\\u90e8\\u4ef6\\uff1a\\u6a21\\u578b\\uff08M\\uff09\\u3001\\u89c6\\u56fe\\uff08V\\uff09\\u3001\\u63a7\\u5236\\u5668\\uff08C\\uff09\\uff0c\\u4ed6\\u4e0d\\u662f\\u4e00\\u4e2a\\u65b0\\u7684\\u6982\\u5ff5\\uff0c\\u53ea\\u662fThinkCMF\\u5c06\\u5176\\u53d1\\u6325\\u5230\\u4e86\\u6781\\u81f4\\u3002\"},{\"title\":\"\\u7528\\u6237\\u7ba1\\u7406\",\"icon\":\"group\",\"content\":\"ThinkCMF\\u5185\\u7f6e\\u4e86\\u7075\\u6d3b\\u7684\\u7528\\u6237\\u7ba1\\u7406\\u65b9\\u5f0f\\uff0c\\u5e76\\u53ef\\u76f4\\u63a5\\u4e0e\\u7b2c\\u4e09\\u65b9\\u7ad9\\u70b9\\u8fdb\\u884c\\u4e92\\u8054\\u4e92\\u901a\\uff0c\\u5982\\u679c\\u4f60\\u613f\\u610f\\u751a\\u81f3\\u53ef\\u4ee5\\u5bf9\\u5355\\u4e2a\\u7528\\u6237\\u6216\\u7fa4\\u4f53\\u7528\\u6237\\u7684\\u884c\\u4e3a\\u8fdb\\u884c\\u8bb0\\u5f55\\u53ca\\u5206\\u4eab\\uff0c\\u4e3a\\u60a8\\u7684\\u8fd0\\u8425\\u51b3\\u7b56\\u63d0\\u4f9b\\u6709\\u6548\\u53c2\\u8003\\u6570\\u636e\\u3002\"},{\"title\":\"\\u4e91\\u7aef\\u90e8\\u7f72\",\"icon\":\"cloud\",\"content\":\"\\u901a\\u8fc7\\u9a71\\u52a8\\u7684\\u65b9\\u5f0f\\u53ef\\u4ee5\\u8f7b\\u677e\\u652f\\u6301\\u4e91\\u5e73\\u53f0\\u7684\\u90e8\\u7f72\\uff0c\\u8ba9\\u4f60\\u7684\\u7f51\\u7ad9\\u65e0\\u7f1d\\u8fc1\\u79fb\\uff0c\\u5185\\u7f6e\\u5df2\\u7ecf\\u652f\\u6301SAE\\u3001BAE\\uff0c\\u6b63\\u5f0f\\u7248\\u5c06\\u5bf9\\u4e91\\u7aef\\u90e8\\u7f72\\u8fdb\\u884c\\u8fdb\\u4e00\\u6b65\\u4f18\\u5316\\u3002\"},{\"title\":\"\\u5b89\\u5168\\u7b56\\u7565\",\"icon\":\"heart\",\"content\":\"\\u63d0\\u4f9b\\u7684\\u7a33\\u5065\\u7684\\u5b89\\u5168\\u7b56\\u7565\\uff0c\\u5305\\u62ec\\u5907\\u4efd\\u6062\\u590d\\uff0c\\u5bb9\\u9519\\uff0c\\u9632\\u6cbb\\u6076\\u610f\\u653b\\u51fb\\u767b\\u9646\\uff0c\\u7f51\\u9875\\u9632\\u7be1\\u6539\\u7b49\\u591a\\u9879\\u5b89\\u5168\\u7ba1\\u7406\\u529f\\u80fd\\uff0c\\u4fdd\\u8bc1\\u7cfb\\u7edf\\u5b89\\u5168\\uff0c\\u53ef\\u9760\\uff0c\\u7a33\\u5b9a\\u7684\\u8fd0\\u884c\\u3002\"},{\"title\":\"\\u5e94\\u7528\\u6a21\\u5757\\u5316\",\"icon\":\"cubes\",\"content\":\"\\u63d0\\u51fa\\u5168\\u65b0\\u7684\\u5e94\\u7528\\u6a21\\u5f0f\\u8fdb\\u884c\\u6269\\u5c55\\uff0c\\u4e0d\\u7ba1\\u662f\\u4f60\\u5f00\\u53d1\\u4e00\\u4e2a\\u5c0f\\u529f\\u80fd\\u8fd8\\u662f\\u4e00\\u4e2a\\u5168\\u65b0\\u7684\\u7ad9\\u70b9\\uff0c\\u5728ThinkCMF\\u4e2d\\u4f60\\u53ea\\u662f\\u589e\\u52a0\\u4e86\\u4e00\\u4e2aAPP\\uff0c\\u6bcf\\u4e2a\\u72ec\\u7acb\\u8fd0\\u884c\\u4e92\\u4e0d\\u5f71\\u54cd\\uff0c\\u4fbf\\u4e8e\\u7075\\u6d3b\\u6269\\u5c55\\u548c\\u4e8c\\u6b21\\u5f00\\u53d1\\u3002\"},{\"title\":\"\\u514d\\u8d39\\u5f00\\u6e90\",\"icon\":\"certificate\",\"content\":\"\\u4ee3\\u7801\\u9075\\u5faaApache2\\u5f00\\u6e90\\u534f\\u8bae\\uff0c\\u514d\\u8d39\\u4f7f\\u7528\\uff0c\\u5bf9\\u5546\\u4e1a\\u7528\\u6237\\u4e5f\\u65e0\\u4efb\\u4f55\\u9650\\u5236\\u3002\"}],\"type\":\"array\",\"item\":{\"title\":{\"title\":\"\\u6807\\u9898\",\"value\":\"\",\"type\":\"text\",\"rule\":{\"require\":true}},\"icon\":{\"title\":\"\\u56fe\\u6807\",\"value\":\"\",\"type\":\"text\"},\"content\":{\"title\":\"\\u63cf\\u8ff0\",\"value\":\"\",\"type\":\"textarea\"}},\"tip\":\"\"}}},\"last_news\":{\"title\":\"\\u6700\\u65b0\\u8d44\\u8baf\",\"display\":\"1\",\"vars\":{\"last_news_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/Category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', '{\"vars\":{\"top_slide\":{\"title\":\"\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"admin\\/Slide\\/index\",\"multi\":false},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"tip\":\"\\u9876\\u90e8\\u5e7b\\u706f\\u7247\",\"rule\":{\"require\":true}}},\"widgets\":{\"features\":{\"title\":\"\\u5feb\\u901f\\u4e86\\u89e3ThinkCMF\",\"display\":\"1\",\"vars\":{\"sub_title\":{\"title\":\"\\u526f\\u6807\\u9898\",\"value\":\"Quickly understand the ThinkCMF\",\"type\":\"text\",\"placeholder\":\"\\u8bf7\\u8f93\\u5165\\u526f\\u6807\\u9898\",\"tip\":\"\",\"rule\":{\"require\":true}},\"features\":{\"title\":\"\\u7279\\u6027\\u4ecb\\u7ecd\",\"value\":[{\"title\":\"MVC\\u5206\\u5c42\\u6a21\\u5f0f\",\"icon\":\"bars\",\"content\":\"\\u4f7f\\u7528MVC\\u5e94\\u7528\\u7a0b\\u5e8f\\u88ab\\u5206\\u6210\\u4e09\\u4e2a\\u6838\\u5fc3\\u90e8\\u4ef6\\uff1a\\u6a21\\u578b\\uff08M\\uff09\\u3001\\u89c6\\u56fe\\uff08V\\uff09\\u3001\\u63a7\\u5236\\u5668\\uff08C\\uff09\\uff0c\\u4ed6\\u4e0d\\u662f\\u4e00\\u4e2a\\u65b0\\u7684\\u6982\\u5ff5\\uff0c\\u53ea\\u662fThinkCMF\\u5c06\\u5176\\u53d1\\u6325\\u5230\\u4e86\\u6781\\u81f4\\u3002\"},{\"title\":\"\\u7528\\u6237\\u7ba1\\u7406\",\"icon\":\"group\",\"content\":\"ThinkCMF\\u5185\\u7f6e\\u4e86\\u7075\\u6d3b\\u7684\\u7528\\u6237\\u7ba1\\u7406\\u65b9\\u5f0f\\uff0c\\u5e76\\u53ef\\u76f4\\u63a5\\u4e0e\\u7b2c\\u4e09\\u65b9\\u7ad9\\u70b9\\u8fdb\\u884c\\u4e92\\u8054\\u4e92\\u901a\\uff0c\\u5982\\u679c\\u4f60\\u613f\\u610f\\u751a\\u81f3\\u53ef\\u4ee5\\u5bf9\\u5355\\u4e2a\\u7528\\u6237\\u6216\\u7fa4\\u4f53\\u7528\\u6237\\u7684\\u884c\\u4e3a\\u8fdb\\u884c\\u8bb0\\u5f55\\u53ca\\u5206\\u4eab\\uff0c\\u4e3a\\u60a8\\u7684\\u8fd0\\u8425\\u51b3\\u7b56\\u63d0\\u4f9b\\u6709\\u6548\\u53c2\\u8003\\u6570\\u636e\\u3002\"},{\"title\":\"\\u4e91\\u7aef\\u90e8\\u7f72\",\"icon\":\"cloud\",\"content\":\"\\u901a\\u8fc7\\u9a71\\u52a8\\u7684\\u65b9\\u5f0f\\u53ef\\u4ee5\\u8f7b\\u677e\\u652f\\u6301\\u4e91\\u5e73\\u53f0\\u7684\\u90e8\\u7f72\\uff0c\\u8ba9\\u4f60\\u7684\\u7f51\\u7ad9\\u65e0\\u7f1d\\u8fc1\\u79fb\\uff0c\\u5185\\u7f6e\\u5df2\\u7ecf\\u652f\\u6301SAE\\u3001BAE\\uff0c\\u6b63\\u5f0f\\u7248\\u5c06\\u5bf9\\u4e91\\u7aef\\u90e8\\u7f72\\u8fdb\\u884c\\u8fdb\\u4e00\\u6b65\\u4f18\\u5316\\u3002\"},{\"title\":\"\\u5b89\\u5168\\u7b56\\u7565\",\"icon\":\"heart\",\"content\":\"\\u63d0\\u4f9b\\u7684\\u7a33\\u5065\\u7684\\u5b89\\u5168\\u7b56\\u7565\\uff0c\\u5305\\u62ec\\u5907\\u4efd\\u6062\\u590d\\uff0c\\u5bb9\\u9519\\uff0c\\u9632\\u6cbb\\u6076\\u610f\\u653b\\u51fb\\u767b\\u9646\\uff0c\\u7f51\\u9875\\u9632\\u7be1\\u6539\\u7b49\\u591a\\u9879\\u5b89\\u5168\\u7ba1\\u7406\\u529f\\u80fd\\uff0c\\u4fdd\\u8bc1\\u7cfb\\u7edf\\u5b89\\u5168\\uff0c\\u53ef\\u9760\\uff0c\\u7a33\\u5b9a\\u7684\\u8fd0\\u884c\\u3002\"},{\"title\":\"\\u5e94\\u7528\\u6a21\\u5757\\u5316\",\"icon\":\"cubes\",\"content\":\"\\u63d0\\u51fa\\u5168\\u65b0\\u7684\\u5e94\\u7528\\u6a21\\u5f0f\\u8fdb\\u884c\\u6269\\u5c55\\uff0c\\u4e0d\\u7ba1\\u662f\\u4f60\\u5f00\\u53d1\\u4e00\\u4e2a\\u5c0f\\u529f\\u80fd\\u8fd8\\u662f\\u4e00\\u4e2a\\u5168\\u65b0\\u7684\\u7ad9\\u70b9\\uff0c\\u5728ThinkCMF\\u4e2d\\u4f60\\u53ea\\u662f\\u589e\\u52a0\\u4e86\\u4e00\\u4e2aAPP\\uff0c\\u6bcf\\u4e2a\\u72ec\\u7acb\\u8fd0\\u884c\\u4e92\\u4e0d\\u5f71\\u54cd\\uff0c\\u4fbf\\u4e8e\\u7075\\u6d3b\\u6269\\u5c55\\u548c\\u4e8c\\u6b21\\u5f00\\u53d1\\u3002\"},{\"title\":\"\\u514d\\u8d39\\u5f00\\u6e90\",\"icon\":\"certificate\",\"content\":\"\\u4ee3\\u7801\\u9075\\u5faaApache2\\u5f00\\u6e90\\u534f\\u8bae\\uff0c\\u514d\\u8d39\\u4f7f\\u7528\\uff0c\\u5bf9\\u5546\\u4e1a\\u7528\\u6237\\u4e5f\\u65e0\\u4efb\\u4f55\\u9650\\u5236\\u3002\"}],\"type\":\"array\",\"item\":{\"title\":{\"title\":\"\\u6807\\u9898\",\"value\":\"\",\"type\":\"text\",\"rule\":{\"require\":true}},\"icon\":{\"title\":\"\\u56fe\\u6807\",\"value\":\"\",\"type\":\"text\"},\"content\":{\"title\":\"\\u63cf\\u8ff0\",\"value\":\"\",\"type\":\"textarea\"}},\"tip\":\"\"}}},\"last_news\":{\"title\":\"\\u6700\\u65b0\\u8d44\\u8baf\",\"display\":\"1\",\"vars\":{\"last_news_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/Category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', NULL),
(20, 0, 10, 'simpleboot3', '文章列表页', 'portal/List/index', 'portal/list', '文章列表模板文件', '{\"vars\":[],\"widgets\":{\"hottest_articles\":{\"title\":\"\\u70ed\\u95e8\\u6587\\u7ae0\",\"display\":\"1\",\"vars\":{\"hottest_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}},\"last_articles\":{\"title\":\"\\u6700\\u65b0\\u53d1\\u5e03\",\"display\":\"1\",\"vars\":{\"last_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', '{\"vars\":[],\"widgets\":{\"hottest_articles\":{\"title\":\"\\u70ed\\u95e8\\u6587\\u7ae0\",\"display\":\"1\",\"vars\":{\"hottest_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}},\"last_articles\":{\"title\":\"\\u6700\\u65b0\\u53d1\\u5e03\",\"display\":\"1\",\"vars\":{\"last_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', NULL),
(21, 0, 10, 'simpleboot3', '单页面', 'portal/Page/index', 'portal/page', '单页面模板文件', '{\"widgets\":{\"hottest_articles\":{\"title\":\"\\u70ed\\u95e8\\u6587\\u7ae0\",\"display\":\"1\",\"vars\":{\"hottest_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}},\"last_articles\":{\"title\":\"\\u6700\\u65b0\\u53d1\\u5e03\",\"display\":\"1\",\"vars\":{\"last_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', '{\"widgets\":{\"hottest_articles\":{\"title\":\"\\u70ed\\u95e8\\u6587\\u7ae0\",\"display\":\"1\",\"vars\":{\"hottest_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}},\"last_articles\":{\"title\":\"\\u6700\\u65b0\\u53d1\\u5e03\",\"display\":\"1\",\"vars\":{\"last_articles_category_id\":{\"title\":\"\\u6587\\u7ae0\\u5206\\u7c7bID\",\"value\":\"\",\"type\":\"text\",\"dataSource\":{\"api\":\"portal\\/category\\/index\",\"multi\":true},\"placeholder\":\"\\u8bf7\\u9009\\u62e9\\u5206\\u7c7b\",\"tip\":\"\",\"rule\":{\"require\":true}}}}}}', NULL),
(22, 0, 10, 'simpleboot3', '搜索页面', 'portal/search/index', 'portal/search', '搜索模板文件', '{\"vars\":{\"varName1\":{\"title\":\"\\u70ed\\u95e8\\u641c\\u7d22\",\"value\":\"1\",\"type\":\"text\",\"tip\":\"\\u8fd9\\u662f\\u4e00\\u4e2atext\",\"rule\":{\"require\":true}}}}', '{\"vars\":{\"varName1\":{\"title\":\"\\u70ed\\u95e8\\u641c\\u7d22\",\"value\":\"1\",\"type\":\"text\",\"tip\":\"\\u8fd9\\u662f\\u4e00\\u4e2atext\",\"rule\":{\"require\":true}}}}', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `sys_third_party_user`
--

CREATE TABLE `sys_third_party_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '本站用户id',
  `last_login_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `expire_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'access_token过期时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑定时间',
  `login_times` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录次数',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态;1:正常;0:禁用',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `third_party` varchar(20) NOT NULL DEFAULT '' COMMENT '第三方惟一码',
  `app_id` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方应用 id',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `access_token` varchar(512) NOT NULL DEFAULT '' COMMENT '第三方授权码',
  `openid` varchar(40) NOT NULL DEFAULT '' COMMENT '第三方用户id',
  `union_id` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方用户多个产品中的惟一 id,(如:微信平台)',
  `more` text COMMENT '扩展信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方用户表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_user`
--

CREATE TABLE `sys_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '用户类型;1:admin;2:会员',
  `sex` tinyint(2) NOT NULL DEFAULT '0' COMMENT '性别;0:保密,1:男,2:女',
  `birthday` int(11) NOT NULL DEFAULT '0' COMMENT '生日',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `coin` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '金币',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `user_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '用户状态;0:禁用,1:正常,2:未验证',
  `user_login` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `user_pass` varchar(64) NOT NULL DEFAULT '' COMMENT '登录密码;cmf_password加密',
  `user_nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `user_email` varchar(100) NOT NULL DEFAULT '' COMMENT '用户登录邮箱',
  `user_url` varchar(100) NOT NULL DEFAULT '' COMMENT '用户个人网址',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像',
  `signature` varchar(255) NOT NULL DEFAULT '' COMMENT '个性签名',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `user_activation_key` varchar(60) NOT NULL DEFAULT '' COMMENT '激活码',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '中国手机不带国家代码，国际手机号格式为：国家代码-手机号',
  `more` text COMMENT '扩展属性',
  `login_times` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录次数',
  `second_pass` varchar(255) NOT NULL DEFAULT '' COMMENT '二级密码',
  `view_game_ids` text NOT NULL COMMENT '查看指定的游戏',
  `login_show_page` varchar(255) NOT NULL DEFAULT '' COMMENT '登录跳转展示的页面',
  `latest_session_id` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员最后一次登录记录的session_id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

--
-- 转存表中的数据 `sys_user`
--

INSERT INTO `sys_user` (`id`, `user_type`, `sex`, `birthday`, `last_login_time`, `score`, `coin`, `balance`, `create_time`, `user_status`, `user_login`, `user_pass`, `user_nickname`, `user_email`, `user_url`, `avatar`, `signature`, `last_login_ip`, `user_activation_key`, `mobile`, `more`, `login_times`, `second_pass`, `view_game_ids`, `login_show_page`, `latest_session_id`) VALUES
(1, 1, 0, 0, 1712750879, 0, 0, '0.00', 1619504562, 1, 'admin', '###59734a7d6101f125f87cd8ab5f5e5338', 'admin', '123456@qq.com', '', '', '', '', '', '', NULL, 3, '', '', '', 'frb6558vs51ljq5t4336q4uetf');

-- --------------------------------------------------------

--
-- 表的结构 `sys_user_action`
--

CREATE TABLE `sys_user_action` (
  `id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '更改积分，可以为负',
  `coin` int(11) NOT NULL DEFAULT '0' COMMENT '更改金币，可以为负',
  `reward_number` int(11) NOT NULL DEFAULT '0' COMMENT '奖励次数',
  `cycle_type` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '周期类型;0:不限;1:按天;2:按小时;3:永久',
  `cycle_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '周期时间值',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户操作名称',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '用户操作名称',
  `app` varchar(50) NOT NULL DEFAULT '' COMMENT '操作所在应用名或插件名等',
  `url` text COMMENT '执行操作的url'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户操作表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_user_action_log`
--

CREATE TABLE `sys_user_action_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `count` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '访问次数',
  `last_visit_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后访问时间',
  `object` varchar(100) NOT NULL DEFAULT '' COMMENT '访问对象的id,格式:不带前缀的表名+id;如posts1表示xx_posts表里id为1的记录',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作名称;格式:应用名+控制器+操作名,也可自己定义格式只要不发生冲突且惟一;',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '用户ip'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问记录表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_user_favorite`
--

CREATE TABLE `sys_user_favorite` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户 id',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '收藏内容的标题',
  `thumbnail` varchar(100) NOT NULL DEFAULT '' COMMENT '缩略图',
  `url` varchar(255) DEFAULT NULL COMMENT '收藏内容的原文地址，JSON格式',
  `description` text COMMENT '收藏内容的描述',
  `table_name` varchar(64) NOT NULL DEFAULT '' COMMENT '收藏实体以前所在表,不带前缀',
  `object_id` int(10) UNSIGNED DEFAULT '0' COMMENT '收藏内容原来的主键id',
  `create_time` int(10) UNSIGNED DEFAULT '0' COMMENT '收藏时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户收藏表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_user_score_log`
--

CREATE TABLE `sys_user_score_log` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户 id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '用户操作名称',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '更改积分，可以为负',
  `coin` int(11) NOT NULL DEFAULT '0' COMMENT '更改金币，可以为负'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户操作积分等奖励日志表';

-- --------------------------------------------------------

--
-- 表的结构 `sys_user_token`
--

CREATE TABLE `sys_user_token` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
  `expire_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT ' 过期时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `token` varchar(64) NOT NULL DEFAULT '' COMMENT 'token',
  `device_type` varchar(10) NOT NULL DEFAULT '' COMMENT '设备类型;mobile,android,iphone,ipad,web,pc,mac,wxapp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户客户端登录 token 表';

--
-- 转存表中的数据 `sys_user_token`
--

INSERT INTO `sys_user_token` (`id`, `user_id`, `expire_time`, `create_time`, `token`, `device_type`) VALUES
(1, 1, 1728302680, 1712750680, '3057613f20992fda457029d945d8b7fa6fbede1f2fb6fe905d2e85808f9999e6', 'web');

-- --------------------------------------------------------

--
-- 表的结构 `sys_verification_code`
--

CREATE TABLE `sys_verification_code` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '表id',
  `count` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '当天已经发送成功的次数',
  `send_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后发送成功时间',
  `expire_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '验证码过期时间',
  `code` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '最后发送成功的验证码',
  `account` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '手机号或者邮箱'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机邮箱数字验证码表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_admin_action_log`
--

CREATE TABLE `tab_admin_action_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `action_name` varchar(255) NOT NULL DEFAULT '' COMMENT '操作名称',
  `admin_name` varchar(255) NOT NULL DEFAULT '' COMMENT '执行者',
  `action_time` int(11) NOT NULL DEFAULT '0' COMMENT '执行时间',
  `client_ip` varchar(255) NOT NULL DEFAULT '' COMMENT '执行ip',
  `action_url` varchar(255) NOT NULL DEFAULT '' COMMENT '操作url',
  `is_delete` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否删除(1:未删除,0:删除)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台管理员操作日志表';

--
-- 转存表中的数据 `tab_admin_action_log`
--

INSERT INTO `tab_admin_action_log` (`id`, `action_name`, `admin_name`, `action_time`, `client_ip`, `action_url`, `is_delete`) VALUES
(1, '管理员登录', 'admin', 1712750680, '117.60.9.158', '/admin/public/dologin.html', 1),
(2, '修改管理员【admin】信息', 'admin', 1712750698, '117.60.9.158', '/admin/user/editpost.html', 1),
(3, '管理员退出', 'admin', 1712750705, '117.60.9.158', '/admin/public/logout.html', 1),
(4, '管理员登录', 'admin', 1712750710, '117.60.9.158', '/admin/public/dologin.html', 1),
(5, '管理员退出', 'admin', 1712750873, '117.60.9.158', '/admin/public/logout.html', 1),
(6, '管理员登录', 'admin', 1712750879, '117.60.9.158', '/admin/public/dologin.html', 1);

-- --------------------------------------------------------

--
-- 表的结构 `tab_admin_remind_msg`
--

CREATE TABLE `tab_admin_remind_msg` (
  `id` int(11) NOT NULL,
  `user_stage_id` int(11) NOT NULL DEFAULT '0' COMMENT '阶段id',
  `remindtime` varchar(200) NOT NULL DEFAULT '' COMMENT '通知时间',
  `admin_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '需要通知的管理员',
  `remind_msg` text NOT NULL COMMENT '需要通知的信息',
  `stage_name` varchar(255) NOT NULL DEFAULT '' COMMENT '阶段名称',
  `not_login_num` int(11) NOT NULL DEFAULT '0' COMMENT '未登玩家个数',
  `not_login_days` int(11) NOT NULL DEFAULT '0' COMMENT '超过几天未登录',
  `not_recharge_num` int(11) NOT NULL DEFAULT '0' COMMENT '未付费玩家个数',
  `not_recharge_days` int(11) NOT NULL DEFAULT '0' COMMENT '超过几天未付费',
  `remind_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 0=未通知,1=通知成功,2=通知失败',
  `remind_fail_admin_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '需要重新通知的管理员',
  `remind_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '通知方式:1: 手机短信, 2:邮件',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='通知管理员的跟进提醒信息';

-- --------------------------------------------------------

--
-- 表的结构 `tab_adv`
--

CREATE TABLE `tab_adv` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '主键',
  `title` char(80) NOT NULL DEFAULT '' COMMENT '广告名称',
  `pos_id` int(11) NOT NULL DEFAULT '0' COMMENT '广告位置',
  `data` varchar(100) NOT NULL DEFAULT '' COMMENT '图片地址',
  `click_count` int(11) NOT NULL DEFAULT '0' COMMENT '点击量',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接地址',
  `sort` int(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常）',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `target` varchar(20) NOT NULL DEFAULT '_blank',
  `icon` varchar(100) NOT NULL DEFAULT '0' COMMENT '广告小图标',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '类型 0链接1详情',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `tab_adv_pos`
--

CREATE TABLE `tab_adv_pos` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '',
  `title` char(80) NOT NULL DEFAULT '' COMMENT '广告位置名称',
  `module` varchar(100) NOT NULL DEFAULT '' COMMENT '所在模块 模块/控制器/方法',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT '1' COMMENT '广告位类型 \r\n1.单图\r\n2.多图\r\n3.文字链接\r\n4.代码',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常）',
  `data` varchar(500) NOT NULL DEFAULT '' COMMENT '额外的数据',
  `width` char(20) NOT NULL DEFAULT '' COMMENT '广告位置宽度',
  `height` char(20) NOT NULL DEFAULT '' COMMENT '广告位置高度',
  `margin` varchar(50) NOT NULL DEFAULT '' COMMENT '边缘',
  `padding` varchar(50) NOT NULL DEFAULT '' COMMENT '留白',
  `theme` varchar(50) NOT NULL DEFAULT 'all' COMMENT '适用主题，默认为all，通用'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告位置表' ROW_FORMAT=DYNAMIC;

--
-- 转存表中的数据 `tab_adv_pos`
--

INSERT INTO `tab_adv_pos` (`id`, `name`, `title`, `module`, `type`, `status`, `data`, `width`, `height`, `margin`, `padding`, `theme`) VALUES
(1, 'slider_media', '首页轮播图', 'media', 2, 1, '', '1920px', '680px', '0', '0', 'all'),
(2, 'media_zixun_gift', '资讯详情页广告位', 'media', 1, 1, '22', '338px', '172px', '0', '0', 'all'),
(4, 'slider_game', '游戏页轮播图', 'media', 2, 1, '', '1200px', '380px', '0', '0', 'all'),
(5, 'slider_gift', '礼包页广告位', 'media', 1, 1, '20', '1920px', '270px', '0', '0', 'all'),
(7, 'wap_index', '首页轮播图（4个）', 'wap', 2, 1, '', '750px', '300px', '0', '0', 'all'),
(11, 'h5_close_account', 'H5注销账号显示', 'h5game', 1, 1, '', '750px', '1334px', '0', '0', 'all'),
(12, 'simple_sdk_close_account', '简化SDK内注销账号显示', 'simple_sdk', 1, 1, '', '750px', '1334px', '0', '0', 'all'),
(23, 'loginout_sdk', 'SDK账号注销广告位', 'sdk', 1, 1, '', '906px', '632px', '0', '0', 'all'),
(24, 'wap_new_game', '新游推荐广告图（安卓）', 'wap', 1, 1, '', '750px', '300px', '0', '0', 'all'),
(25, 'wap_new_game_ios', '新游推荐广告图（ios）', 'wap', 1, 1, '', '750px', '300px', '0', '0', 'all'),
(26, 'wap_hot_game', '热游推荐广告图（安卓）', 'wap', 1, 1, '', '750px', '300px', '0', '0', 'all'),
(27, 'wap_hot_game_ios', '热游推荐广告图（ios）', 'wap', 1, 1, '', '750px', '300px', '0', '0', 'all'),
(28, 'app_slider', '安卓APP首页轮播图', 'app', 2, 1, '', '1022px', '464px', '0', '0', 'all'),
(29, 'app_hot', '安卓人气广告位', 'app', 1, 1, '', '1008px', '372px', '0', '0', 'all'),
(30, 'app_new', '安卓新游广告位', 'app', 1, 1, '', '1008px', '372px', '0', '0', 'all'),
(31, 'app_open', '开机活动页', 'app', 1, 1, '', '750px', '1334px', '0', '0', 'all'),
(32, 'ios_slider', '苹果APP首页轮播图', 'app', 2, 1, '', '1022px', '464px', '0', '0', 'all'),
(33, 'ios_hot', '苹果人气广告位', 'app', 1, 1, '', '1008px', '372px', '0', '0', 'all'),
(34, 'ios_new', '苹果新游广告位', 'app', 1, 1, '', '1008px', '372px', '0', '0', 'all'),
(35, 'ios_open', '苹果开机活动页', 'app', 1, 1, '', '750px', '1334px', '0', '0', 'all');

-- --------------------------------------------------------

--
-- 表的结构 `tab_alipay_auth`
--

CREATE TABLE `tab_alipay_auth` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '编号',
  `game_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏编号',
  `game_name` varchar(40) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `appid` varchar(40) NOT NULL DEFAULT '' COMMENT '支付宝appid',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '认证状态(0:禁用，1:启用)',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付宝快捷认证';

-- --------------------------------------------------------

--
-- 表的结构 `tab_app`
--

CREATE TABLE `tab_app` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '自增主键',
  `name` varchar(30) NOT NULL DEFAULT '',
  `file_name` varchar(30) NOT NULL DEFAULT '' COMMENT '文件名称',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` varchar(30) NOT NULL DEFAULT '' COMMENT '文件大小',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `plist_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'plist文件地址',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人名称',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `version` int(2) NOT NULL DEFAULT '0' COMMENT '1:安卓 2：ios',
  `bao_name` varchar(60) NOT NULL DEFAULT '' COMMENT '包名',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分包模式(苹果有效) 0原包上传 1超级签',
  `pay_download` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否开启付费下载',
  `pay_price` decimal(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '付费金额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='app表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_app_supersign_order`
--

CREATE TABLE `tab_app_supersign_order` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `user_agent` varchar(1000) NOT NULL DEFAULT '' COMMENT '浏览器标识',
  `user_agent_md5` varchar(40) NOT NULL DEFAULT '' COMMENT '浏览器标识md5',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付方式 3:支付宝 4:微信',
  `pay_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '支付ip',
  `pay_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态(1:已支付 0:下单未付款)',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='APP超级签购买记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_prop`
--

CREATE TABLE `tab_bt_prop` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `prop_name` varchar(255) NOT NULL DEFAULT '' COMMENT '道具名称',
  `prop_tag` varchar(255) NOT NULL DEFAULT '' COMMENT '道具标识',
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '道具数量',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '状态(1:开启 0:关闭)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='BT福利游戏道具表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare`
--

CREATE TABLE `tab_bt_welfare` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `register_prop_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '注册奖励道具id',
  `recharge_prop_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '注册奖励道具id',
  `total_recharge_prop` varchar(2000) NOT NULL DEFAULT '' COMMENT '累计充值奖励',
  `month_card_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '月卡最低限额',
  `month_card_prop_ids` varchar(1000) NOT NULL DEFAULT '' COMMENT '月卡发放道具',
  `week_card_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '周卡最低限额',
  `week_card_prop_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '周卡发放道具',
  `start_time` int(10) NOT NULL DEFAULT '0' COMMENT '福利开启时间',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '福利结束时间',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '状态(1: 开启  0: 关闭)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='BT福利设置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_monthcard`
--

CREATE TABLE `tab_bt_welfare_monthcard` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` int(10) NOT NULL DEFAULT '0' COMMENT '角色id',
  `game_player_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `pay_time` int(10) NOT NULL DEFAULT '0' COMMENT '充值时间',
  `send_prop` text NOT NULL COMMENT '道具内容',
  `total_number` int(10) NOT NULL DEFAULT '0' COMMENT '累计获得月卡数',
  `already_send_num` int(10) NOT NULL DEFAULT '0' COMMENT '已发放天数',
  `gt_six_four_eight` int(10) NOT NULL DEFAULT '0' COMMENT '单笔充值大于648次数',
  `gt_thousand_of_day` int(10) NOT NULL DEFAULT '0' COMMENT '单日充值大于1000次数',
  `first_six_four_eight_time` int(10) NOT NULL DEFAULT '0' COMMENT '首次648日期',
  `expire_time` int(10) NOT NULL DEFAULT '0' COMMENT '月卡过期时间',
  `last_send_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后发放时间',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '0' COMMENT '0:待发放 1:已发放'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='月卡福利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_promote`
--

CREATE TABLE `tab_bt_welfare_promote` (
  `id` bigint(10) UNSIGNED NOT NULL,
  `bt_welfare_id` int(10) NOT NULL DEFAULT '0' COMMENT '变态福利id',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='BT福利推广员关联表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_recharge`
--

CREATE TABLE `tab_bt_welfare_recharge` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` int(10) NOT NULL DEFAULT '0' COMMENT '角色id',
  `game_player_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `pay_order_number` varchar(255) NOT NULL DEFAULT '' COMMENT '充值订单号',
  `pay_time` int(10) NOT NULL DEFAULT '0' COMMENT '充值时间',
  `send_prop` text NOT NULL COMMENT '道具内容',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '0' COMMENT '0:待发放 1:已发放'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='首充福利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_register`
--

CREATE TABLE `tab_bt_welfare_register` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` int(10) NOT NULL DEFAULT '0' COMMENT '角色id',
  `game_player_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `login_time` int(10) NOT NULL DEFAULT '0' COMMENT '登录时间',
  `send_prop` text NOT NULL COMMENT '道具内容',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '0' COMMENT '0:待发放 1:已发放'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='注册福利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_total_recharge`
--

CREATE TABLE `tab_bt_welfare_total_recharge` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` int(10) NOT NULL DEFAULT '0' COMMENT '角色id',
  `game_player_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `pay_time` int(10) NOT NULL DEFAULT '0' COMMENT '充值时间',
  `matched_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '累计充值触发规则金额',
  `total_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '当前累计充值金额',
  `send_prop` text NOT NULL COMMENT '道具内容',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '0' COMMENT '0:待发放 1:已发放'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='累充福利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_bt_welfare_weekcard`
--

CREATE TABLE `tab_bt_welfare_weekcard` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` int(10) NOT NULL DEFAULT '0' COMMENT '角色id',
  `game_player_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_name` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `pay_time` int(10) NOT NULL DEFAULT '0' COMMENT '充值时间',
  `send_prop` text NOT NULL COMMENT '道具内容',
  `total_number` int(10) NOT NULL DEFAULT '0' COMMENT '累计获得周卡数',
  `already_send_num` int(10) NOT NULL DEFAULT '0' COMMENT '已发放天数',
  `gt_six_four_eight` int(10) NOT NULL DEFAULT '0' COMMENT '单笔充值大于648次数',
  `gt_thousand_of_day` int(10) NOT NULL DEFAULT '0' COMMENT '单日充值大于1000次数',
  `expire_time` int(10) NOT NULL DEFAULT '0' COMMENT '周卡过期时间',
  `last_send_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后发放时间',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '0' COMMENT '0:待发放 1:已发放'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='周卡福利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_coupon`
--

CREATE TABLE `tab_coupon` (
  `id` int(11) NOT NULL,
  `coupon_name` varchar(60) NOT NULL DEFAULT '' COMMENT '代金券名称',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏真实id',
  `game_name` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '返利类型  1全站  2 官方  3渠道 4部分渠道',
  `mold` tinyint(2) NOT NULL DEFAULT '0' COMMENT '代金券类型 ： 0 通用 1 游戏',
  `money` int(5) NOT NULL DEFAULT '0' COMMENT '优惠金额',
  `limit_money` int(5) NOT NULL DEFAULT '0' COMMENT '限制消费金额',
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态(0关闭 1 开启)',
  `limit` tinyint(2) NOT NULL DEFAULT '0' COMMENT '限制领取数量',
  `stock` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '数量',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `receive_start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `receive_end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '删除状态 0 未删除 1已删除',
  `coupon_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0普通  1,2,3奖励 4尊享卡  5抽奖奖品 6推广员',
  `spend_limit` int(5) NOT NULL DEFAULT '0' COMMENT '消费限额',
  `category` varchar(30) NOT NULL DEFAULT '' COMMENT '分类',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `pgame_id` int(11) NOT NULL DEFAULT '-1' COMMENT '推广员游戏id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代金券设置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_coupon_promote`
--

CREATE TABLE `tab_coupon_promote` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '优惠券id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代金券设置推广员表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_coupon_record`
--

CREATE TABLE `tab_coupon_record` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户名称',
  `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '代金券id',
  `coupon_name` varchar(60) NOT NULL DEFAULT '' COMMENT '代金券名称',
  `game_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏真实id',
  `game_name` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `mold` tinyint(2) NOT NULL DEFAULT '0' COMMENT '代金券类型 ： 0 通用 1 游戏',
  `money` int(10) NOT NULL DEFAULT '0' COMMENT '优惠金额',
  `limit_money` int(10) NOT NULL DEFAULT '0' COMMENT '限制消费金额',
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态(0未使用 1 已使用)',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '使用时间',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `get_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '获取方式 0 领取 1 发放',
  `is_delete` tinyint(2) NOT NULL DEFAULT '0' COMMENT '删除状态 0 未删除 1 已删除',
  `limit` tinyint(2) NOT NULL DEFAULT '0' COMMENT '限制领取数量',
  `pid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推广员id',
  `deduct_amount` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '渠道代金券扣除平台币金额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代金券设置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_cp_settlement_period`
--

CREATE TABLE `tab_cp_settlement_period` (
  `id` int(11) NOT NULL,
  `cp_id` int(11) NOT NULL DEFAULT '0' COMMENT 'cp id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `order_num` varchar(60) NOT NULL COMMENT '结算单号',
  `total_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这段时期的实付流水',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `party_ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '甲方分成',
  `cp_ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'CP分成',
  `party_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '甲方分成金额(元)',
  `cp_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'CP分成金额(元)',
  `cp_pay_ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'CP通道费率',
  `cp_pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '通道费',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结算周期开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结算周期结束时间',
  `is_remit` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 0默认未打款 1已打款',
  `remit_time` int(11) NOT NULL DEFAULT '0' COMMENT '打款时间',
  `remit_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打款金额',
  `accounts_of_receive` varchar(255) NOT NULL COMMENT '收款账户',
  `name_of_receive` varchar(255) NOT NULL COMMENT '收款账户名',
  `type_of_receive` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '收款账户类型1 银行卡, 2支付宝',
  `operator` varchar(255) NOT NULL COMMENT '打款人,操作人',
  `receipt` varchar(255) NOT NULL COMMENT '回执单(图片链接)',
  `remark` varchar(255) NOT NULL COMMENT '备注信息',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间(结算时间)',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间(打款时间)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='CP结算记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_datareport_every_pid`
--

CREATE TABLE `tab_datareport_every_pid` (
  `id` int(11) UNSIGNED NOT NULL,
  `time` varchar(20) NOT NULL DEFAULT '' COMMENT '日期',
  `promote_id` int(11) NOT NULL DEFAULT '0',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏名称',
  `new_register_user` text NOT NULL COMMENT '新增注册用户',
  `new_register_device` text NOT NULL COMMENT '新增注册设备数',
  `new_register_device_user` text NOT NULL COMMENT '新增注册设备注册用户数',
  `active_user` text NOT NULL COMMENT '活跃人数',
  `active_user7` text NOT NULL COMMENT '七日活跃wau',
  `active_user30` text NOT NULL COMMENT '三十日活跃mau',
  `pay_user` text NOT NULL COMMENT '充值人数 ',
  `total_order` text NOT NULL COMMENT '订单数',
  `total_pay` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '总付费金额',
  `new_pay_user` text NOT NULL COMMENT '新增充值人数 ',
  `new_total_order` text NOT NULL COMMENT '新增充值订单',
  `new_total_pay` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '新增充值金额',
  `fire_device` text NOT NULL COMMENT '激活设备数',
  `active_device` text NOT NULL COMMENT '活跃设备数（激活设备+老设备次日登录）',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据报表基础数据 包含每个推广员每个游戏每天的数据';

-- --------------------------------------------------------

--
-- 表的结构 `tab_datareport_top_pid`
--

CREATE TABLE `tab_datareport_top_pid` (
  `id` int(11) UNSIGNED NOT NULL,
  `time` varchar(20) NOT NULL DEFAULT '' COMMENT '日期',
  `promote_id` int(11) NOT NULL DEFAULT '0',
  `promote_level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '渠道等级',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏名称',
  `new_register_user` text NOT NULL COMMENT '新增注册用户',
  `new_register_device` text NOT NULL COMMENT '新增注册设备数',
  `new_register_device_user` text NOT NULL COMMENT '新增注册设备注册用户数',
  `active_user` text NOT NULL COMMENT '活跃人数',
  `active_user7` text NOT NULL COMMENT '七日活跃wau',
  `active_user30` text NOT NULL COMMENT '三十日活跃mau',
  `pay_user` text NOT NULL COMMENT '充值人数 ',
  `total_order` text NOT NULL COMMENT '订单数',
  `total_pay` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '总付费金额',
  `new_pay_user` text NOT NULL COMMENT '新增充值人数 ',
  `new_total_order` text NOT NULL COMMENT '新增充值订单',
  `new_total_pay` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '新增充值金额',
  `fire_device` text NOT NULL COMMENT '激活设备数',
  `active_device` text NOT NULL COMMENT '活跃设备数（激活设备+老设备次日登录）',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据报表基础数据 包含上级推广员每个游戏每天的数据  不包含最低等级推广员';

-- --------------------------------------------------------

--
-- 表的结构 `tab_data_change_record`
--

CREATE TABLE `tab_data_change_record` (
  `id` int(10) UNSIGNED NOT NULL,
  `data_id` int(10) NOT NULL DEFAULT '0' COMMENT '数据id',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '数据类型 1 user 2promote 3busier 4spend 5 support',
  `notice_scrm_status` tinyint(2) NOT NULL COMMENT '通知scrm状态 0未通知 2通知中 1已通知',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_equipment_game`
--

CREATE TABLE `tab_equipment_game` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_num` varchar(255) NOT NULL DEFAULT '' COMMENT '设备号',
  `sdk_version` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1安卓 2苹果',
  `promote_id` int(11) DEFAULT '0' COMMENT '渠道id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `first_device` tinyint(1) DEFAULT '0' COMMENT '1设备第一次记录 2次日登录',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `device_name` varchar(60) NOT NULL DEFAULT '' COMMENT '设备名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏设备表\r\n和用户无关\r\n每日打开记录一次  每天不可重复记录\r\n设备号和游戏id不能为空';

-- --------------------------------------------------------

--
-- 表的结构 `tab_equipment_login`
--

CREATE TABLE `tab_equipment_login` (
  `id` int(11) NOT NULL COMMENT '登录记录表id',
  `time` varchar(20) NOT NULL DEFAULT '' COMMENT '时间',
  `equipment_num` varchar(100) NOT NULL DEFAULT '' COMMENT '用户id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `play_time` int(5) NOT NULL DEFAULT '0' COMMENT '设备在线时间',
  `login_count` int(3) NOT NULL DEFAULT '0' COMMENT '打开sdk次数',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_down_time` int(11) NOT NULL DEFAULT '0' COMMENT '上一次退出时间',
  `device_name` varchar(60) NOT NULL DEFAULT '' COMMENT '设备名称',
  `sdk_version` tinyint(1) NOT NULL DEFAULT '0' COMMENT '版本号 1 安卓 2 苹果',
  `first_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '首次登录时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备游戏在线统计';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game`
--

CREATE TABLE `tab_game` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '主键自增',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `short` varchar(20) NOT NULL DEFAULT '' COMMENT '游戏简写',
  `game_type_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '游戏类型id,更改为多选,最多可选三个',
  `game_type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏类型名称',
  `game_score` double(4,1) NOT NULL DEFAULT '0.0' COMMENT '游戏评分',
  `tag_name` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏标签',
  `features` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏简介',
  `introduction` varchar(1100) NOT NULL DEFAULT '' COMMENT '详细介绍',
  `recommend_level` double(3,0) NOT NULL DEFAULT '0' COMMENT '推荐指数',
  `apply_status` int(11) NOT NULL DEFAULT '1' COMMENT '审核状态 0未审核 1审核',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏图标',
  `cover` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏封面',
  `hot_cover` varchar(100) NOT NULL DEFAULT '' COMMENT '热门推荐图',
  `screenshot` varchar(1000) NOT NULL DEFAULT '' COMMENT '游戏截图',
  `groom` varchar(100) NOT NULL DEFAULT '' COMMENT '推荐图',
  `and_dow_address` varchar(100) NOT NULL DEFAULT '' COMMENT '安卓游戏下载地址',
  `ios_dow_plist` varchar(100) NOT NULL DEFAULT '' COMMENT '苹果商店下载链接',
  `ios_dow_address` varchar(100) NOT NULL DEFAULT '' COMMENT 'ios游戏下载地址',
  `add_game_address` varchar(255) NOT NULL DEFAULT '' COMMENT '外部链接游戏地址',
  `ios_game_address` varchar(255) NOT NULL DEFAULT '' COMMENT '外部链接游戏地址',
  `dow_num` int(10) NOT NULL DEFAULT '0' COMMENT '游戏下载数量',
  `recommend_status` varchar(10) NOT NULL DEFAULT '1' COMMENT '推荐状态(0:不推荐,1推荐 2热门 3最新)',
  `pay_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '充值状态(0:关闭,1:开启)',
  `dow_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '下载状态(0:关闭,1:开启)',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `marking` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏标示',
  `language` varchar(10) NOT NULL DEFAULT '' COMMENT '语言',
  `ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分成比例',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '注册单机',
  `game_appid` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏appid',
  `game_coin_name` varchar(10) NOT NULL DEFAULT '' COMMENT '游戏币名称',
  `game_coin_ration` varchar(10) NOT NULL DEFAULT '' COMMENT '游戏币比例',
  `category` tinyint(2) NOT NULL DEFAULT '0' COMMENT '开放类型',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '-1' COMMENT '区别版本   1安卓 2苹果 0 双版本',
  `game_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '游戏状态(0:关闭,1:开启)',
  `relation_game_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联游戏ID',
  `relation_game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '关联游戏名称',
  `game_size` varchar(10) NOT NULL DEFAULT '0MB' COMMENT '游戏大小',
  `material_url` varchar(255) NOT NULL DEFAULT '' COMMENT '素材包',
  `appstatus` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 ios  sdk wap支付   1ios sdk 苹果内购 ',
  `accredit_img` varchar(100) NOT NULL DEFAULT '0' COMMENT '授权图片',
  `back_describe` varchar(50) NOT NULL DEFAULT '' COMMENT '分享游戏标题',
  `dow_icon` varchar(100) NOT NULL DEFAULT '' COMMENT '分享游戏下载图标',
  `back_map` varchar(100) NOT NULL DEFAULT '0' COMMENT '分享背景图',
  `game_address_size` varchar(10) NOT NULL DEFAULT '0' COMMENT '第三方游戏大小',
  `down_port` tinyint(1) NOT NULL DEFAULT '1' COMMENT '下载端口 1：官方原包 2：第三方链接地址',
  `ccustom_service_qq` varchar(11) NOT NULL DEFAULT '' COMMENT '游戏客服qq',
  `is_force_update` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启强更 1开启  0关闭',
  `display_site` varchar(20) NOT NULL DEFAULT '1,2,3' COMMENT '控制游戏显示站点（1:PC官网，2:WAP，3:联运APP）',
  `dev_name` varchar(30) NOT NULL DEFAULT '' COMMENT '开发商名称',
  `promote_ids` text COMMENT '不可申请渠道',
  `third_party_url` varchar(800) NOT NULL DEFAULT '' COMMENT '第三方游戏地址',
  `screen_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '屏幕属性 1横屏 0竖屏',
  `weiduan_pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '微端APP支付开关 0关闭 1开启',
  `fullscreen` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'app内是否全屏 0不开启 1开启',
  `is_https` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是https游戏  1是 0 否',
  `video_cover` varchar(100) NOT NULL DEFAULT '' COMMENT '视频封面图',
  `video` text NOT NULL COMMENT '视频文件地址',
  `video_url` text NOT NULL COMMENT '视频第三方网址',
  `load_cover` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏加载图',
  `is_interflow` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否互通 0否 1是',
  `is_force_real` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否强制实名认证 1开启  0关闭',
  `first_support_num` int(10) NOT NULL DEFAULT '0' COMMENT '首次扶持额度',
  `following_support_rate` int(10) NOT NULL DEFAULT '0' COMMENT '后续扶持比例',
  `interface_id` int(6) NOT NULL DEFAULT '0' COMMENT '接口id',
  `cp_game_id` varchar(30) NOT NULL DEFAULT '' COMMENT '页游CP对接真实游戏id',
  `currency_name` varchar(10) NOT NULL DEFAULT '' COMMENT '游戏币名称',
  `currency_ratio` int(5) NOT NULL DEFAULT '0' COMMENT '游戏币比例',
  `cp_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '所属CP商id',
  `vip_table_pic` varchar(100) NOT NULL DEFAULT '' COMMENT 'VIP表图(仅是一张图)',
  `sdk_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启验证(0:关闭 1:开启)',
  `super_version` int(10) NOT NULL DEFAULT '0' COMMENT '超级签版本号',
  `super_remark` varchar(1000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '超级签更新说明',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `super_version_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '超级签版本名',
  `sdk_scheme` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '游戏scheme信息',
  `age_type` tinyint(11) NOT NULL DEFAULT '1' COMMENT '实名认证方式  1：平台   2：国家系统',
  `age_appid` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '国家实名认证系统APPID',
  `age_bizid` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '国家实名认证系统bizid',
  `age_key` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '国家实名认证系统secretKey',
  `apple_in_app_set` longtext CHARACTER SET utf8mb4 NOT NULL COMMENT '苹果内购充值设置',
  `sdk_area` tinyint(2) NOT NULL DEFAULT '0' COMMENT '运营地区 0:国内 1:海外',
  `test_game_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否是测试游戏 0 非测试游戏状态,1:测试游戏状态',
  `simulator_official_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '官方玩家模拟器登录(1:允许 0:不允许)',
  `simulator_channel_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '模拟器使用权限到渠道后台(1:开启 0:关闭)',
  `promote_ids2` text NOT NULL COMMENT '当前游戏仅在这些渠道内显示(存储渠道id)',
  `only_for_promote` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 通用, 1 渠道独占',
  `yidun_business_id` varchar(100) NOT NULL DEFAULT '' COMMENT '易盾配置上对应包名的business_id(一般安卓/iOS一致)',
  `platform_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方平台ID; 0：官方游戏  '
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_attr`
--

CREATE TABLE `tab_game_attr` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `is_control` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否是严控游戏  1是  0否',
  `promote_declare` varchar(255) NOT NULL DEFAULT '' COMMENT '推广说明',
  `cp_ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'cp分成比例',
  `cp_pay_ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'CP通道费率',
  `issue` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否支持分发  0不支持 1支持',
  `sue_pay_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分发支付模式  1：预付款，平台支付模式 0：分成模式，支付在分发平台',
  `support_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏下发扶持通知地址',
  `promote_level_limit` int(11) NOT NULL DEFAULT '0' COMMENT '渠道推广限制等级  0:不限制',
  `discount_show_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否显示折扣返利入口开关  1显示  0关闭',
  `coupon_show_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否显示代金券领取入口开关  1显示 0隐藏',
  `xg_kf_url` text CHARACTER SET utf8 NOT NULL COMMENT '溪谷客服系统的指定客服(客服链接)',
  `sdk_type` tinyint(2) NOT NULL DEFAULT '1' COMMENT 'sdk类型 1=旗舰 2=简化 3=海外',
  `bind_recharge_discount` decimal(10,2) NOT NULL DEFAULT '10.00' COMMENT '绑币充值折扣-首充',
  `bind_continue_recharge_discount` decimal(10,2) NOT NULL DEFAULT '10.00' COMMENT '绑币充值折扣-续充',
  `discount` decimal(10,2) NOT NULL DEFAULT '10.00' COMMENT '会长代充折扣-首充',
  `continue_discount` decimal(10,2) NOT NULL DEFAULT '10.00' COMMENT '会长代充折扣-续充',
  `support_introduction` varchar(1000) NOT NULL DEFAULT '' COMMENT '扶持规则',
  `ratio_begin_time` int(11) NOT NULL DEFAULT '0' COMMENT '分成比例生效时间',
  `money_begin_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册单价生效时间',
  `third_source_info` varchar(255) NOT NULL DEFAULT '' COMMENT '第三方游戏渠道打包信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_ban_set`
--

CREATE TABLE `tab_game_ban_set` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '游戏封禁设置表',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `ban_promote_ids` text NOT NULL COMMENT '渠道的ids,字符串',
  `ban_user_ids` text NOT NULL COMMENT '用户ids,字符串',
  `ban_devices` text NOT NULL COMMENT '设备信息,字符串',
  `ban_ips` text NOT NULL COMMENT 'IP地址,字符串',
  `ban_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '禁止开始时间',
  `ban_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '禁止结束时间 0为永久',
  `ban_types` varchar(50) NOT NULL DEFAULT '' COMMENT '禁止类型 1=登录;2=注册;3=充值;4=下载',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_change_name_log`
--

CREATE TABLE `tab_game_change_name_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `old_game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '原游戏名称',
  `new_game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '新游戏名称',
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作人',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏名称修改记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_collect`
--

CREATE TABLE `tab_game_collect` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` int(2) NOT NULL DEFAULT '1' COMMENT '1 收藏 2 取消收藏'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏收藏';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_comment`
--

CREATE TABLE `tab_game_comment` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(60) NOT NULL COMMENT '游戏名称',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户名',
  `content` varchar(500) NOT NULL DEFAULT '',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1审核成功 2隐藏',
  `comment_id` int(11) NOT NULL DEFAULT '0' COMMENT '上级评论id',
  `comment_account` varchar(60) NOT NULL DEFAULT '' COMMENT '评论账号',
  `top_id` int(11) NOT NULL DEFAULT '0' COMMENT '顶级评论id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏评论';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_comment_follow`
--

CREATE TABLE `tab_game_comment_follow` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL DEFAULT '0' COMMENT '评论id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `status` int(2) NOT NULL DEFAULT '1' COMMENT '状态 0未点赞 1点赞',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏评论点赞表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_config`
--

CREATE TABLE `tab_game_config` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '标识',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '标题',
  `config` varchar(1000) NOT NULL DEFAULT '' COMMENT '配置文件内容',
  `template` varchar(500) NOT NULL DEFAULT '' COMMENT '模板内容',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏模块扩展工具表';

--
-- 转存表中的数据 `tab_game_config`
--

INSERT INTO `tab_game_config` (`id`, `name`, `title`, `config`, `template`, `type`, `status`, `create_time`) VALUES
(1, 'comment_auto_audit', '评论自动审核', '', '', 0, 0, 1566808546);

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_cp`
--

CREATE TABLE `tab_game_cp` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `cp_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'cp名称(开发者名称)',
  `cp_attribute` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'cp属性',
  `cp_contact_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'cp联系人',
  `cp_mobile` varchar(255) NOT NULL DEFAULT '' COMMENT 'cp手机号',
  `cp_email` varchar(255) NOT NULL DEFAULT '' COMMENT 'cp邮箱',
  `cp_qq` varchar(255) NOT NULL DEFAULT '' COMMENT 'cpQQ号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `settlement_type` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结算类型0 未设置 1: 银行卡, 2: 支付宝',
  `bank_info` text NOT NULL COMMENT '银行卡信息 json',
  `alipay_info` text NOT NULL COMMENT '支付宝信息 json'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='cp商信息表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_down_record`
--

CREATE TABLE `tab_game_down_record` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `imei` varchar(200) NOT NULL DEFAULT '' COMMENT '设备号',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0网页 1安卓 2苹果',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT '下载ip',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '下载来源  1 pc下载 2wap  3app',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='游戏下载记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_follow`
--

CREATE TABLE `tab_game_follow` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `status` int(2) NOT NULL DEFAULT '1' COMMENT '状态 0未点赞 1点赞',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏点赞表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_giftbag`
--

CREATE TABLE `tab_game_giftbag` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `giftbag_name` varchar(30) NOT NULL DEFAULT '' COMMENT '礼包名称',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '礼包状态 1开启 0关闭',
  `novice` text NOT NULL COMMENT '激活码',
  `digest` varchar(300) NOT NULL DEFAULT '' COMMENT '礼包内容',
  `desribe` varchar(300) NOT NULL DEFAULT '' COMMENT '使用说明',
  `competence` varchar(100) NOT NULL DEFAULT '' COMMENT '兑换资格',
  `notice` varchar(255) NOT NULL DEFAULT '' COMMENT '注意事项',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `giftbag_version` varchar(10) NOT NULL DEFAULT '' COMMENT '运营平台 1and 2ios 3h5 4PC',
  `novice_num` int(11) NOT NULL DEFAULT '0' COMMENT '领取码数量',
  `remain_num` int(11) NOT NULL DEFAULT '0' COMMENT '剩余数量',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '优先级',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1普通码  2 统一码',
  `vip` tinyint(3) NOT NULL DEFAULT '0' COMMENT '领取VIP等级',
  `and_id` int(11) NOT NULL DEFAULT '0' COMMENT '安卓版本游戏ID',
  `ios_id` int(11) NOT NULL DEFAULT '0' COMMENT '苹果版本游戏id',
  `h5_id` int(11) NOT NULL DEFAULT '0' COMMENT 'H5游戏id',
  `pc_id` int(11) NOT NULL DEFAULT '0' COMMENT '页游游戏id',
  `third_gift_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方礼包ID  0官方',
  `platform_id` int(11) NOT NULL DEFAULT '0' COMMENT '简化版平台专属  0自身平台',
  `accumulate_recharge_limit` decimal(6,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '累充限制(累充达到才能领取此礼包)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='礼包管理';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_gift_record`
--

CREATE TABLE `tab_game_gift_record` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `gift_id` int(11) NOT NULL DEFAULT '0' COMMENT '礼包id',
  `gift_name` varchar(30) NOT NULL DEFAULT '' COMMENT '礼包名称',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态(0:未使用,1:已使用)',
  `novice` varchar(40) NOT NULL DEFAULT '' COMMENT '激活码',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `gift_version` varchar(10) NOT NULL DEFAULT '0' COMMENT '礼包平台1：安卓 2 ：苹果  3:h5',
  `delete_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '礼包删除状态 0 未删除  1已删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='礼包领取记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_interface`
--

CREATE TABLE `tab_game_interface` (
  `id` int(11) NOT NULL COMMENT '接口ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '接口名称',
  `tag` varchar(32) NOT NULL DEFAULT '' COMMENT '标签',
  `unid` varchar(32) NOT NULL DEFAULT '' COMMENT '接口标识(混服)',
  `login_url` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆地址',
  `pay_url` varchar(100) NOT NULL DEFAULT '' COMMENT '充值地址',
  `login_key` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆KEY',
  `pay_key` varchar(100) NOT NULL DEFAULT '' COMMENT '充值KEY',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `role_url` varchar(100) NOT NULL DEFAULT '' COMMENT '角色接口'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网页游戏接口表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_interflow`
--

CREATE TABLE `tab_game_interflow` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏id',
  `interflow_tag` varchar(40) NOT NULL DEFAULT '' COMMENT '游戏关联标识'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏互通配置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_ios_pay_to_download`
--

CREATE TABLE `tab_game_ios_pay_to_download` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `pay_download` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否开启付费下载0:未开启, 1:已开启',
  `pay_price` decimal(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '付费金额',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ios游戏超级签是否需要付费下载';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_ios_pay_to_download_order`
--

CREATE TABLE `tab_game_ios_pay_to_download_order` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `user_agent` varchar(512) NOT NULL DEFAULT '' COMMENT '浏览器标识',
  `user_agent_md5` varchar(100) NOT NULL DEFAULT '' COMMENT '浏览器标识md5',
  `pay_price` decimal(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '付费金额',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付方式 3:支付宝 4:微信',
  `pay_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '支付ip',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否付费成功 0:下单未付款, 1:付费成功',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ios游戏超级签游戏下载付费订单记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_ios_pay_to_download_record`
--

CREATE TABLE `tab_game_ios_pay_to_download_record` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `user_agent` varchar(512) NOT NULL DEFAULT '' COMMENT '浏览器标识',
  `user_agent_md5` varchar(100) NOT NULL DEFAULT '' COMMENT '浏览器标识md5',
  `pay_price` decimal(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '付费金额',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否付费成功 0:付费未成功, 1:付费成功',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ios游戏超级签游戏下载付费下载记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_server`
--

CREATE TABLE `tab_game_server` (
  `id` int(11) NOT NULL COMMENT '主键自增',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `server_num` varchar(30) NOT NULL DEFAULT '0' COMMENT '对接区服id',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '显示状态(0:否,1:是)',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `desride` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '1' COMMENT '运营平台 1：安卓 2：苹果',
  `third_server_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方游戏区服ID 0官方'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏区服表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_server_notice`
--

CREATE TABLE `tab_game_server_notice` (
  `id` int(11) NOT NULL COMMENT '主键自增',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '关注状态(0:否,1:是)',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏区服表开服通知表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_set`
--

CREATE TABLE `tab_game_set` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `login_notify_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏登陆通知地址',
  `pay_notify_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏支付通知地址',
  `game_role_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏角色获取地址',
  `game_gift_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏礼包领取地址',
  `game_server_url` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏区服获取地址',
  `game_key` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏key',
  `access_key` varchar(64) NOT NULL DEFAULT '' COMMENT '访问秘钥',
  `agent_id` varchar(32) NOT NULL DEFAULT '' COMMENT '代理id(合作方标示)',
  `wxlogin_appid` varchar(32) NOT NULL DEFAULT '' COMMENT '微信登录appid',
  `wxlogin_appsecret` varchar(64) NOT NULL DEFAULT '' COMMENT '微信登录appsecrte',
  `game_pay_appid` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏支付APPID',
  `apk_pck_name` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏包名',
  `apk_pck_sign` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏包签名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏设置表（游戏对接时调用）';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_share_record`
--

CREATE TABLE `tab_game_share_record` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '生成时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏分享记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_source`
--

CREATE TABLE `tab_game_source` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `file_name` varchar(100) NOT NULL DEFAULT '' COMMENT '文件名称',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏大小',
  `file_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '原包类型1 安卓2苹果',
  `plist_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'plist文件路径',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人名称',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `bao_name` varchar(30) NOT NULL DEFAULT '' COMMENT '包名',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `source_version` int(11) NOT NULL DEFAULT '0' COMMENT '原包版本号',
  `source_name` varchar(50) NOT NULL DEFAULT '' COMMENT '版本名',
  `sdk_version` tinyint(1) NOT NULL DEFAULT '0' COMMENT '运营版本 0 手游 3H5',
  `third_source_info` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '第三方渠道包打包信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏原包';

-- --------------------------------------------------------

--
-- 表的结构 `tab_game_type`
--

CREATE TABLE `tab_game_type` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `type_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏类型名称',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态(0:关闭,1:开启)',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人昵称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏类型表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_guess`
--

CREATE TABLE `tab_guess` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '编号',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '链接名称',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '链接地址',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `icon` varchar(100) NOT NULL DEFAULT '0' COMMENT '图标',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='猜你喜欢';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_game`
--

CREATE TABLE `tab_issue_game` (
  `id` int(10) NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '联运的游戏id',
  `game_name` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `game_type_id` varchar(50) DEFAULT '0' COMMENT '游戏类型id,更改为多选,最多可选三个',
  `game_type_name` varchar(100) DEFAULT '0' COMMENT '游戏类型',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1安卓 2苹果 3H5',
  `online_time` int(10) NOT NULL DEFAULT '0' COMMENT '上架时间',
  `short` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏简写',
  `sort` int(10) NOT NULL DEFAULT '0',
  `icon` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏图标',
  `cover` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏封面',
  `screenshot` varchar(1000) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏截图',
  `features` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏简介',
  `introduction` varchar(1100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '详细介绍',
  `screen_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '屏幕属性 1横屏 0竖屏',
  `material_url` varchar(255) NOT NULL DEFAULT '' COMMENT '素材包',
  `login_notify_url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏登陆通知地址',
  `pay_notify_url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏支付通知地址',
  `game_appid` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏appid',
  `game_key` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏key',
  `access_key` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '访问秘钥',
  `agent_id` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '代理id(合作方标示)',
  `td_ratio` int(3) NOT NULL DEFAULT '0' COMMENT '通道费',
  `ff_ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分发分成比例',
  `cp_ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'cp分成比例',
  `total_pay` float(15,2) NOT NULL DEFAULT '0.00',
  `total_reg` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0下架 1上架',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `dev_name` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '开发商名称',
  `game_pay_appid` varchar(255) NOT NULL DEFAULT '' COMMENT '使用微信支付时需要的appid',
  `interface_id` int(10) NOT NULL DEFAULT '0' COMMENT '页游接口id',
  `cp_game_id` varchar(30) NOT NULL DEFAULT '' COMMENT '页游CP对接真实游戏id',
  `currency_name` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏币名称',
  `currency_ratio` int(5) NOT NULL DEFAULT '0' COMMENT '游戏币比例'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分发游戏';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_game_apply`
--

CREATE TABLE `tab_issue_game_apply` (
  `id` int(10) NOT NULL COMMENT '自增主键',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台用户ID',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台用户id',
  `ratio` double(5,2) NOT NULL DEFAULT '0.00' COMMENT '分成比例 1-100',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '审核状态',
  `enable_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '操作状态 0未联运 1联运中',
  `dispose_id` int(10) NOT NULL COMMENT '操作人',
  `dispose_time` int(10) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `platform_config` text NOT NULL COMMENT '渠道配置参数',
  `service_config` text NOT NULL COMMENT '服务端参数',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '区别版本   1安卓 2苹果 3H5',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `pack_name` varchar(30) NOT NULL DEFAULT '' COMMENT '包名',
  `wx_appid` varchar(50) NOT NULL DEFAULT '' COMMENT '微信APPID',
  `qq_appid` varchar(50) NOT NULL DEFAULT '' COMMENT 'QQAPPID',
  `total_pay` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计充值',
  `total_register` int(10) NOT NULL DEFAULT '0' COMMENT '累计注册'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发平台游戏申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_game_pack_record`
--

CREATE TABLE `tab_issue_game_pack_record` (
  `id` int(10) NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台id',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台用户id',
  `pack_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '打包ip',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='打包记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_open_user`
--

CREATE TABLE `tab_issue_open_user` (
  `id` int(10) NOT NULL,
  `account` varchar(100) NOT NULL DEFAULT '' COMMENT '平台账号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '平台账号密码',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '平台名称',
  `balance` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '联运币',
  `company_name` varchar(50) NOT NULL DEFAULT '' COMMENT '公司名称',
  `linkman` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `job` varchar(50) NOT NULL DEFAULT '' COMMENT '职务',
  `mobile` varchar(20) NOT NULL DEFAULT '',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'qq',
  `wechat` varchar(20) NOT NULL DEFAULT '' COMMENT '微信',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '联系地址',
  `business_license_img` varchar(255) NOT NULL DEFAULT '' COMMENT '营业执照图片',
  `wenwangwen_img` varchar(255) NOT NULL DEFAULT '' COMMENT '文网文图片',
  `icp_img` varchar(255) NOT NULL DEFAULT '' COMMENT 'icp许可证',
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `auth_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '认证状态(0:未认证 1: 已认证 2: 待审核 3:已驳回)',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `last_login_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '',
  `account_type` varchar(20) NOT NULL DEFAULT '' COMMENT '结算账户类型',
  `bank_phone` varchar(50) NOT NULL DEFAULT '' COMMENT '结算手机号',
  `bank_card` varchar(50) NOT NULL DEFAULT '' COMMENT '结算卡号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `bank_account` varchar(50) NOT NULL DEFAULT '' COMMENT '银行持卡人姓名',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '市',
  `county` varchar(50) NOT NULL DEFAULT '' COMMENT '区/县',
  `account_openin` varchar(200) NOT NULL DEFAULT '' COMMENT '开户网点',
  `check_people` varchar(50) NOT NULL DEFAULT '' COMMENT '对账人',
  `check_people_qq` varchar(50) NOT NULL DEFAULT '' COMMENT '对账人QQ',
  `check_people_phone` varchar(50) NOT NULL DEFAULT '' COMMENT '对账人手机',
  `alipay_account` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝账户',
  `alipay_realname` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝真实姓名',
  `settle_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '结算模式 0 预付款 1平台结算'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分发平台登录用户';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_open_user_balance`
--

CREATE TABLE `tab_issue_open_user_balance` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID',
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取 平台订单)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `props_name` varchar(30) NOT NULL DEFAULT '' COMMENT '道具名称',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '3支付宝4微信',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `extra_param` varchar(255) DEFAULT '' COMMENT 'h5登录时透传给cp的参数 cp原样返回 sy待定',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付IP',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发用户联运币订单';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_open_user_login_record`
--

CREATE TABLE `tab_issue_open_user_login_record` (
  `id` int(10) UNSIGNED NOT NULL,
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '联运分发平台用户id',
  `login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '登录ip',
  `login_type` varchar(10) NOT NULL DEFAULT '' COMMENT '登录方式',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_open_user_platform`
--

CREATE TABLE `tab_issue_open_user_platform` (
  `id` int(10) NOT NULL COMMENT '自增主键',
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '账号',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台用户id',
  `total_pay` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '总流水',
  `total_register` int(10) NOT NULL DEFAULT '0' COMMENT '总注册数量',
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `forbid_game` mediumtext NOT NULL COMMENT '禁止申请ID ',
  `appkey` varchar(100) NOT NULL DEFAULT '' COMMENT 'appkey对接时加密需要',
  `platform_config_h5` varchar(500) NOT NULL DEFAULT '' COMMENT '渠道配置参数',
  `platform_config_sy` varchar(500) NOT NULL DEFAULT '' COMMENT '渠道配置参数',
  `platform_config_yy` varchar(500) DEFAULT '0.00' COMMENT '000',
  `controller_name_h5` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道配置参数',
  `controller_name_sy` varchar(50) NOT NULL DEFAULT '' COMMENT 'sy控制器名称',
  `controller_name_yy` varchar(50) NOT NULL DEFAULT '' COMMENT 'YY控制器名称',
  `pt_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0全平台 1h5 2手游',
  `website` varchar(200) NOT NULL DEFAULT '' COMMENT '网站地址',
  `service_config` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义配置参数',
  `min_balance` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '最低联运币限额',
  `sdk_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'SDK地址',
  `game_ids` varchar(800) NOT NULL DEFAULT '' COMMENT '禁止申请游戏id',
  `sdk_config_name` varchar(20) NOT NULL DEFAULT '' COMMENT 'SDK版本名称',
  `sdk_config_version` varchar(20) NOT NULL DEFAULT '' COMMENT 'SDK版本号',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '编辑时间',
  `order_notice_url_h5` varchar(255) NOT NULL DEFAULT '' COMMENT '创建订单通知地址',
  `pay_notice_url_h5` varchar(255) NOT NULL DEFAULT '' COMMENT '支付回调通知地址',
  `order_notice_url_sy` varchar(255) NOT NULL DEFAULT '' COMMENT '创建订单通知地址',
  `pay_notice_url_sy` varchar(255) NOT NULL DEFAULT '' COMMENT '支付回调通知地址',
  `order_notice_url_yy` varchar(255) NOT NULL DEFAULT '' COMMENT '创建订单通知地址',
  `pay_notice_url_yy` varchar(255) NOT NULL DEFAULT '' COMMENT '支付回调通知地址'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发平台表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_spend`
--

CREATE TABLE `tab_issue_spend` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID',
  `platform_openid` varchar(255) NOT NULL DEFAULT '' COMMENT '平台用户唯一标示',
  `user_account` varchar(30) NOT NULL DEFAULT '' COMMENT '分发用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` varchar(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(30) DEFAULT '' COMMENT '区服名称',
  `game_player_id` varchar(11) NOT NULL DEFAULT '0' COMMENT '玩家游戏内id',
  `game_player_name` varchar(100) DEFAULT '' COMMENT '游戏玩家昵称',
  `role_level` int(8) NOT NULL DEFAULT '0' COMMENT '角色等级',
  `platform_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属平台id',
  `platform_account` varchar(30) NOT NULL DEFAULT '' COMMENT '所属平台名称',
  `open_user_id` int(10) NOT NULL DEFAULT '0',
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取 平台订单)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `props_name` varchar(30) NOT NULL DEFAULT '' COMMENT '道具名称',
  `pay_code` varchar(50) NOT NULL DEFAULT '' COMMENT '充值档位id',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `dec_balance` decimal(10,2) DEFAULT '0.00' COMMENT '扣除联运币',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `pay_game_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '游戏支付通知状态 0 失败 1成功 ',
  `pay_ff_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '分发平台通知状态',
  `extra_param` varchar(255) DEFAULT '' COMMENT 'h5登录时透传给cp的参数 cp原样返回 sy待定',
  `extend` varchar(255) NOT NULL DEFAULT '' COMMENT '通知游戏方扩展(一般是游戏方透传的订单)',
  `spend_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付IP',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '1' COMMENT '区别sdk版本1安卓 2苹果  3H5',
  `callback_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '平台通知ip',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '分成比例',
  `ratio_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分成金额',
  `pay_way` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付类型 0 暂定  3支付宝 4微信',
  `is_check` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否参数结算 2不参与 1参与 0未参与'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发订单';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_user`
--

CREATE TABLE `tab_issue_user` (
  `id` int(10) NOT NULL COMMENT '主键自增',
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父级id',
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '登陆账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '第一次玩的游戏',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台id',
  `cumulative` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计充值',
  `lock_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '锁定状态',
  `register_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '注册ip',
  `last_login_time` int(10) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `last_login_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '登陆ip',
  `openid` varchar(255) NOT NULL DEFAULT '' COMMENT '平台用户标识',
  `birthday` varchar(20) NOT NULL DEFAULT '' COMMENT '出生日期',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台用户id',
  `equipment_num` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '首次登陆设备号',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发平台用户表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_user_day_login`
--

CREATE TABLE `tab_issue_user_day_login` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '平台id',
  `open_user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '平台用户id',
  `login_time` int(10) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `login_ip` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '登陆ip',
  `login_year` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '年',
  `login_month` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '月',
  `login_day` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '日',
  `equipment_num` varchar(100) NOT NULL DEFAULT '' COMMENT '设备号',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户每日登陆记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_user_login_record`
--

CREATE TABLE `tab_issue_user_login_record` (
  `id` int(10) NOT NULL COMMENT '自增主键',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `login_time` int(10) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '登陆ip',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '分发id',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '区别sdk版本1安卓 2苹果 ',
  `pt_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1h5 2手游',
  `equipment_num` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '设备号',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户登录记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_user_play`
--

CREATE TABLE `tab_issue_user_play` (
  `id` int(10) NOT NULL COMMENT '自增主键',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '区别sdk版本1安卓 2苹果 3 H5',
  `play_time` int(10) NOT NULL DEFAULT '0',
  `open_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '分发id',
  `login_extend` varchar(500) NOT NULL DEFAULT '' COMMENT '用户进入游戏时平台要求特殊处理的数据',
  `play_ip` varchar(30) NOT NULL DEFAULT '',
  `equipment_num` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '设备号',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '每次访问更新'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发平台玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_issue_user_play_role`
--

CREATE TABLE `tab_issue_user_play_role` (
  `id` int(10) NOT NULL COMMENT '自增主键',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `platform_openid` varchar(255) NOT NULL DEFAULT '' COMMENT '平台用户唯一标示',
  `user_account` varchar(30) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` varchar(30) NOT NULL DEFAULT '' COMMENT '区服id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `role_id` varchar(30) NOT NULL DEFAULT '' COMMENT '角色',
  `role_name` varchar(30) NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_level` int(4) NOT NULL DEFAULT '0' COMMENT '等级',
  `combat_number` varchar(30) NOT NULL DEFAULT '' COMMENT '战力值',
  `platform_id` int(10) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `open_user_id` int(10) NOT NULL DEFAULT '0',
  `platform_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `play_time` int(10) NOT NULL DEFAULT '0' COMMENT '游戏登陆时间',
  `play_ip` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏登陆IP',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '数据创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '数据更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分发平台玩家角色表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_kefuquestion`
--

CREATE TABLE `tab_kefuquestion` (
  `id` int(11) NOT NULL,
  `zititle` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `content` text,
  `sort` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `platform_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '平台类型 1：sdk   2：推广平台',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '问题类型'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='客服问题表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_kefuquestion_type`
--

CREATE TABLE `tab_kefuquestion_type` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类型名称',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `platform_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '平台类型 1：sdk   2：推广平台 3 : PC官网',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `admin_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='客服问题类型表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_links`
--

CREATE TABLE `tab_links` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `link_url` varchar(100) NOT NULL DEFAULT '' COMMENT '友链地址',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='友情链接表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_notice`
--

CREATE TABLE `tab_notice` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '编号',
  `game_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏编号',
  `game_name` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '展示开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '展示结束时间',
  `level` int(11) NOT NULL DEFAULT '0' COMMENT '优先级',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='通知公告';

-- --------------------------------------------------------

--
-- 表的结构 `tab_oa_studio`
--

CREATE TABLE `tab_oa_studio` (
  `id` int(10) UNSIGNED NOT NULL,
  `studio_name` varchar(100) NOT NULL DEFAULT '' COMMENT '公会名称',
  `appid` varchar(100) NOT NULL DEFAULT '' COMMENT '应用id',
  `domain` varchar(200) NOT NULL DEFAULT '' COMMENT '工作室OA域名',
  `api_key` varchar(100) NOT NULL DEFAULT '' COMMENT '请求接口key',
  `create_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1: 自主添加 2: 后台申请',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='OA公会列表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_platform`
--

CREATE TABLE `tab_platform` (
  `id` int(11) NOT NULL,
  `platform_name` varchar(50) NOT NULL DEFAULT '' COMMENT '系统名称',
  `platform_url` varchar(255) NOT NULL DEFAULT '' COMMENT '系统域名',
  `api_key` varchar(125) NOT NULL DEFAULT '' COMMENT '接口密钥',
  `pay_type` tinyint(11) NOT NULL DEFAULT '1' COMMENT '支付通道 1平台  2第三方',
  `status` tinyint(11) NOT NULL DEFAULT '1' COMMENT '系统状态  1开启  2关闭',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `op_nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '管理员账号',
  `marking` varchar(50) NOT NULL DEFAULT '' COMMENT '所属平台标识',
  `select_game_url` varchar(125) NOT NULL DEFAULT '' COMMENT '获取可申请游戏接口',
  `import_game_url` varchar(125) NOT NULL DEFAULT '' COMMENT '一键导入游戏接口',
  `import_server_url` varchar(125) NOT NULL DEFAULT '' COMMENT '导入区服接口',
  `import_gift_url` varchar(125) NOT NULL DEFAULT '' COMMENT '导入礼包接口',
  `import_source_url` varchar(125) NOT NULL DEFAULT '' COMMENT '导入原包接口',
  `import_spend_url` varchar(125) NOT NULL DEFAULT '' COMMENT '导入订单接口',
  `import_pay_url` varchar(125) NOT NULL DEFAULT '' COMMENT '补单接口',
  `cp_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应生成得CP id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简化版对接平台表(上级平台)';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote`
--

CREATE TABLE `tab_promote` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `second_pwd` varchar(100) NOT NULL DEFAULT '' COMMENT '二级密码',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `mobile_phone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `real_name` varchar(30) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行',
  `bank_card` varchar(20) NOT NULL DEFAULT '' COMMENT '银行卡',
  `bank_phone` varchar(11) NOT NULL DEFAULT '' COMMENT '银行预留手机号',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `balance_coin` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '平台币',
  `balance_profit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收益金额',
  `promote_type` int(2) NOT NULL DEFAULT '1' COMMENT '推广员类型',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父类ID',
  `parent_name` varchar(30) NOT NULL DEFAULT '' COMMENT '父类名称',
  `promote_level` tinyint(2) NOT NULL DEFAULT '1' COMMENT '推广员等级',
  `top_promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '顶级推广员id',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `mark1` varchar(255) NOT NULL DEFAULT '' COMMENT '基本信息备注',
  `bank_area` varchar(255) NOT NULL DEFAULT '' COMMENT '开户地点',
  `account_openin` varchar(100) NOT NULL DEFAULT '' COMMENT '开户网点',
  `bank_account` varchar(100) NOT NULL DEFAULT '' COMMENT '银行户名',
  `mark2` varchar(255) NOT NULL DEFAULT '' COMMENT '结算信息备注',
  `busier_id` int(10) NOT NULL DEFAULT '0' COMMENT '商务员id',
  `game_ids` varchar(800) NOT NULL DEFAULT '' COMMENT '不可推广游戏id',
  `alipay_account` varchar(30) NOT NULL DEFAULT '' COMMENT '支付宝账号',
  `pattern` tinyint(2) NOT NULL DEFAULT '0' COMMENT '合作模式 0 cps 1cpa',
  `register_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '注册类型 0个人 1公会 2公众号 3其它',
  `alipay_name` varchar(30) NOT NULL DEFAULT '' COMMENT '支付宝真实姓名',
  `settment_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '结算方式 0银行卡 1支付宝',
  `bt_welfare_register_auto` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道自助发放注册奖励,0:手动, 1:自助发放开启',
  `bt_welfare_recharge_auto` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道自助发放充值奖励,0:手动, 1:自助发放开启',
  `bt_welfare_total_auto` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道自助发放累充奖励,0:手动, 1:自助发放开启',
  `bt_welfare_month_auto` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道自助发放月卡奖励,0:手动, 1:自助发放开启',
  `bt_welfare_week_auto` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道自助发放周卡奖励,0:手动, 1:自助发放开启',
  `zfb` varchar(4000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '支付宝',
  `wxscan` varchar(4000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '微信扫码',
  `wxapp` varchar(4000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '微信APP',
  `is_custom_pay` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否自定义支付',
  `prepayment` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付通道预付款',
  `allow_check_subbox` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否允许渠道审核子渠道盒子,0:不允许, 1:允许',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `oa_studio_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'OA系统公会id',
  `game_ids2` text NOT NULL COMMENT '指定可推广游戏(存储游戏id)',
  `latest_session_id` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道最后一次登录记录的session_id',
  `settlement_day_period` int(8) UNSIGNED NOT NULL DEFAULT '7' COMMENT '按天的周期',
  `sms_notice_switch` tinyint(2) NOT NULL DEFAULT '0' COMMENT '渠道短信通知开关(发送数据)0=关闭 1=开启',
  `allow_simulator` tinyint(2) NOT NULL DEFAULT '0' COMMENT '允许全部用户模拟器登录(1:全部允许 0:关闭)',
  `cash_money` decimal(10,2) DEFAULT '0.00' COMMENT '押金'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推广员';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_agent`
--

CREATE TABLE `tab_promote_agent` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道帐号',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏名称',
  `game_discount` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '游戏统一折扣 首充',
  `game_continue_discount` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '游戏统一折扣-续充',
  `promote_discount` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '渠道代充折扣-(疑似作废)',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '代充状态 0关闭 1开启 (疑似作废)',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `promote_discount_first` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '渠道首充折扣',
  `promote_discount_continued` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '渠道续充折扣',
  `promote_first_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '渠道会长代充折扣-首充开关 1=开启 0 关闭',
  `promote_continue_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '渠道会长代充折扣-续充开关 1=开启 0 关闭'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会长代充折扣表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_app`
--

CREATE TABLE `tab_promote_app` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `app_id` int(11) NOT NULL DEFAULT '0' COMMENT 'APPID',
  `app_name` varchar(30) NOT NULL DEFAULT '' COMMENT 'APP名称',
  `app_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'APP版本 1:安卓 2：IOS',
  `apply_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `plist_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'plist文件地址',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '申请状态 0:审核中 1：通过 2：未通过',
  `enable_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '打包状态0未打包 1打包成功 2准备打包3打包中4打包失败',
  `dispose_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `dispose_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人',
  `dow_url` varchar(255) NOT NULL DEFAULT '' COMMENT '下载链接',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分包模式(苹果有效) 0原包上传 1超级签',
  `is_user_define` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自定义渠道包(0:默认 1:自定义)',
  `app_new_name` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '自定义app名称',
  `app_new_icon` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'APP新替换图标',
  `start_img1` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'APP启动图(上)',
  `start_img2` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'APP启动图(下)',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `super_url` varchar(255) DEFAULT '' COMMENT '超级签地址(ios专用)',
  `file_size2` varchar(30) NOT NULL DEFAULT '' COMMENT '文件大小(目前是自定义苹果包大小适用)',
  `ios_version` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'IOS版本号',
  `yidun_business_id` varchar(100) NOT NULL DEFAULT '' COMMENT '易盾配置上对应包名的business_id(iOS使用)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推广员app申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_apply`
--

CREATE TABLE `tab_promote_apply` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员ID',
  `apply_time` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '审核状态 0未审核 1已审核',
  `enable_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '渠道打包状态(仅用于渠道打包） -1 打包失败 0未打包  1打包成功 2准备打包 3打包中 ',
  `pack_url` varchar(500) NOT NULL DEFAULT '' COMMENT '游戏包地址',
  `plist_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'plist文件url',
  `dow_url` varchar(500) NOT NULL DEFAULT '' COMMENT '下载地址',
  `dispose_id` int(11) NOT NULL DEFAULT '1' COMMENT '操作人',
  `dispose_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '1' COMMENT '区别版本 1安卓 2苹果',
  `promote_ratio` double(5,2) NOT NULL DEFAULT '0.00' COMMENT '推广员分成比例',
  `promote_money` double(5,2) NOT NULL DEFAULT '0.00' COMMENT '注册单价',
  `pack_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打包方式 0未选择  1渠道打包  （2免分包  成功不存在失败） 3安卓批量打包',
  `pack_mark` varchar(100) NOT NULL DEFAULT '' COMMENT '打包结果说明，目前仅失败时会用到',
  `is_upload` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否上传云存储  0未 1已',
  `pack_id` int(11) NOT NULL DEFAULT '1' COMMENT '打包管理员',
  `pack_time` int(11) NOT NULL DEFAULT '0' COMMENT '打包时间',
  `and_url` varchar(100) NOT NULL DEFAULT '' COMMENT '安卓微端地址',
  `ios_url` varchar(100) NOT NULL DEFAULT '' COMMENT '苹果微端地址',
  `and_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '安卓打包状态  -1 打包失败 0未打包 1已打包 2准备打包 3打包中',
  `ios_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '苹果打包状态  -1 打包失败 0未打包 1已打包 2准备打包 3打包中',
  `and_upload` tinyint(1) NOT NULL DEFAULT '0' COMMENT '安卓是否上传云存储  0未 1已',
  `ios_upload` tinyint(1) NOT NULL DEFAULT '0' COMMENT '苹果是否上传云存储  0未 1已',
  `cps_alone_show` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'cps返利是否单独显示,0:不单独显示,1:单独显示',
  `add_cps_alone_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '将cps设成单独显示的时间(时间戳)',
  `cpa_alone_show` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'cpa返利是否单独显示,0:不单独显示,1:单独显示',
  `add_cpa_alone_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '将cps设成单独显示的时间(时间戳)',
  `is_h5_share_show` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '控制H5分享页面是否显示, 0 显示, 1隐藏, (默认0)',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `game_is_open` tinyint(2) NOT NULL DEFAULT '1' COMMENT '游戏是否正常开启 0=关闭 1=开启',
  `user_limit_discount` decimal(10,2) NOT NULL DEFAULT '10.00' COMMENT '玩家最低折扣,渠道在推广后台自定义首充续充、绑币充值折扣时不可低于该折扣'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_apply_upload`
--

CREATE TABLE `tab_promote_apply_upload` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `promote_apply_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道分包id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '上传状态 0未上传 1已上传 2上传中 3上传失败',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'H5微端包上传状态 1安卓 2苹果'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_bce_package`
--

CREATE TABLE `tab_promote_bce_package` (
  `id` int(11) NOT NULL,
  `requestid` varchar(100) NOT NULL DEFAULT '' COMMENT '请求id',
  `jobids` text NOT NULL COMMENT '打包回调',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '执行打包时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '打包结束时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打包回调结果',
  `apply_id` varchar(1000) NOT NULL DEFAULT '' COMMENT '申请id',
  `mark` varchar(200) NOT NULL DEFAULT '' COMMENT '回调结果'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='百度云打包记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_behavior`
--

CREATE TABLE `tab_promote_behavior` (
  `id` int(11) NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='推广员阅读公告行为记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_bind`
--

CREATE TABLE `tab_promote_bind` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '支付订单号 ',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员ID',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际金额',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 1 成功  0失败',
  `discount` double(5,2) NOT NULL DEFAULT '0.00' COMMENT '折扣比例',
  `pay_way` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付方式 2平台币  3:支付宝,4:微信',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '充值iP',
  `pay_id` int(11) NOT NULL DEFAULT '0' COMMENT '充值用户id',
  `pay_account` varchar(30) NOT NULL COMMENT '充值账号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会长代充记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_bind_update_record`
--

CREATE TABLE `tab_promote_bind_update_record` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '订单更改绑定表',
  `old_promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '原渠道id',
  `old_promote_account` varchar(50) NOT NULL DEFAULT '' COMMENT '原渠道账号',
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '现渠道id',
  `promote_account` varchar(50) NOT NULL DEFAULT '' COMMENT '现渠道账号',
  `pay_order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
  `create_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `create_user_account` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人账号',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态1=正常'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_business`
--

CREATE TABLE `tab_promote_business` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `mobile_phone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `real_name` varchar(10) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `qq` varchar(30) NOT NULL DEFAULT '' COMMENT 'qq',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `promote_ids` text NOT NULL COMMENT '推广员id集合',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `mark1` varchar(255) NOT NULL DEFAULT '' COMMENT '基本信息备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商务专员' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_coin`
--

CREATE TABLE `tab_promote_coin` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT 'type=2扣除时 为2级 发放时为1级',
  `promote_type` int(11) NOT NULL DEFAULT '0' COMMENT '作用渠道等级 1：一级 2：二级',
  `source_id` int(11) NOT NULL DEFAULT '0' COMMENT 'type=2扣除时 为1级 发放时为2级',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理后台操作人ID',
  `type` int(2) NOT NULL DEFAULT '0' COMMENT '1:发放 2:扣除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推广后台转移平台币';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_config`
--

CREATE TABLE `tab_promote_config` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '标识',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '标题',
  `config` varchar(1000) NOT NULL DEFAULT '' COMMENT '配置文件内容',
  `template` varchar(500) NOT NULL DEFAULT '' COMMENT '模板内容',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='扩展工具表';

--
-- 转存表中的数据 `tab_promote_config`
--

INSERT INTO `tab_promote_config` (`id`, `name`, `title`, `config`, `template`, `type`, `status`, `create_time`) VALUES
(1, 'promote_auto_audit', '推广员自动审核', '', '', 0, 0, 1566808546),
(2, 'promote_auto_audit_apply', '推广员游戏申请自动审核', '', '', 0, 0, 1566808546),
(3, 'promote_auto_audit_union', '推广员联盟申请自动审核', '', '', 0, 0, 1566808546),
(4, 'promote_auto_audit_app', '推广员app申请自动审核', '', '', 0, 0, 1566808546),
(5, 'promote_auto_audit_support', '推广员扶持申请自动审核', '', '', 0, 0, 1566808546);

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_deposit`
--

CREATE TABLE `tab_promote_deposit` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `pay_amount` double(11,2) NOT NULL DEFAULT '0.00' COMMENT '充值数额',
  `to_id` int(11) NOT NULL DEFAULT '0' COMMENT '被充值渠道ID',
  `pay_way` tinyint(2) NOT NULL DEFAULT '3' COMMENT '支付方式( 3:支付宝,4:微信）',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `order_number` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_order_number` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '支付订单号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1给自己充值 自主  2给子渠道充值  3 给其他与自己推广员无关人员充值',
  `spend_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '充值IP地址'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台币充值记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_level`
--

CREATE TABLE `tab_promote_level` (
  `id` int(11) NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `promote_level` int(11) NOT NULL DEFAULT '1' COMMENT '渠道等级 默认为1级',
  `sum_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '渠道累计充值',
  `cash_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已交押金'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_prepayment_deduct_record`
--

CREATE TABLE `tab_promote_prepayment_deduct_record` (
  `id` int(10) UNSIGNED NOT NULL,
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '推广员id (一级推广员)',
  `promote_account` varchar(255) NOT NULL DEFAULT '' COMMENT '推广员账号 (一级推广员)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '扣款记录关联订单号',
  `old_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣除前金额',
  `new_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣除后金额',
  `deduct_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣除金额',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态(1:正常)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推广员自定义支付预付款扣款记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_prepayment_recharge`
--

CREATE TABLE `tab_promote_prepayment_recharge` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '外部订单号(支付成功后回调获取)',
  `pay_order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(200) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `pay_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额 实付金额',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `pay_way` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付方式( 3:支付宝,4:微信）',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付ip',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推广员充值预付款订单记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_prepayment_send_record`
--

CREATE TABLE `tab_promote_prepayment_send_record` (
  `id` int(10) UNSIGNED NOT NULL,
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `send_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '发放数量',
  `new_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '发放后数量',
  `op_id` int(10) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人账号',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态(1:正常)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发放推广员预付款记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_settlement`
--

CREATE TABLE `tab_promote_settlement` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员ID',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `pattern` tinyint(2) NOT NULL DEFAULT '0' COMMENT '合作模式 0 cps 1cpa',
  `ratio` double(5,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT 'CPS分成比例(%)',
  `money` double(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT 'CPA注册单价(元)',
  `sum_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '结算金额即佣金',
  `sum_reg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '注册数量',
  `user_id` varchar(60) NOT NULL DEFAULT '' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '结算状态 0未结算  1已结算',
  `is_check` tinyint(2) NOT NULL DEFAULT '1' COMMENT '参与状态 1 ；参与 0 ：不参与',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '结算时间',
  `selle_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'CP结算 0未结算1 结算',
  `sub_status2` tinyint(2) NOT NULL DEFAULT '0' COMMENT '二级子渠道结算状态',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '上级渠道',
  `parent_name` varchar(60) NOT NULL DEFAULT '' COMMENT '上级推广员账号',
  `top_promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '顶级渠道',
  `register_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '注册方式',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付方式',
  `pay_order_number` varchar(34) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '结算时间',
  `ti_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '提现状态 0未1已',
  `role_name` varchar(30) NOT NULL DEFAULT '' COMMENT '角色名称',
  `ratio2` double(5,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '2级渠道CPS分成比例(%)',
  `money2` double(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '2级渠道CPA注册单价(元)',
  `sum_money2` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '2级渠道结算金额即佣金',
  `ratio3` double(5,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '3级渠道CPS分成比例(%)',
  `money3` double(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '3级渠道CPA注册单价(元)',
  `sum_money3` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '3级渠道结算金额即佣金',
  `sub_status3` tinyint(2) NOT NULL DEFAULT '0' COMMENT '三级子渠道结算状态',
  `update_time2` int(11) NOT NULL DEFAULT '0' COMMENT '二级结算时间',
  `update_time3` int(11) NOT NULL DEFAULT '0' COMMENT '三级结算时间',
  `period_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '所属哪个时间段的id号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推广员结算';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_settlement_period`
--

CREATE TABLE `tab_promote_settlement_period` (
  `id` int(11) NOT NULL,
  `period` varchar(255) NOT NULL DEFAULT '' COMMENT '周期',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_account` varchar(80) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `order_num` varchar(60) NOT NULL COMMENT '结算单号',
  `total_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这段时期的充值总金额',
  `plateform_earn` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这段时间平台分成金额',
  `promoter_earn` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这段时间渠道分成金额',
  `is_remit` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 0默认未打款 1已打款',
  `remit_time` int(11) NOT NULL DEFAULT '0' COMMENT '打款时间',
  `remit_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打款金额',
  `accounts_of_receive` varchar(255) NOT NULL COMMENT '收款账户',
  `name_of_receive` varchar(255) NOT NULL COMMENT '收款账户名',
  `type_of_receive` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '收款账户类型0 银行卡, 1支付宝',
  `operator` varchar(255) NOT NULL COMMENT '打款人,操作人',
  `receipt` varchar(255) NOT NULL COMMENT '回执单(图片链接)',
  `remark` varchar(255) NOT NULL COMMENT '备注信息',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间(结算时间)',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间(打款时间)',
  `period_start` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '周期开始时间',
  `period_end` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '周期结束时间',
  `total_cps` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这个周期内订单按照cps结算得到的佣金总和',
  `total_cpa` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '这个周期内订单按照cpa结算得到的佣金总和'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='按周期统计记录不同渠道的结算单,记录打款信息';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_settlement_time`
--

CREATE TABLE `tab_promote_settlement_time` (
  `id` int(11) NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_account` varchar(80) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `time_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '归档时间类型0: 按天, 1:按每月的几号',
  `day_period` int(8) UNSIGNED NOT NULL DEFAULT '7' COMMENT '按天的周期(每周的星期几也可以归到按照天 周期7天即可)',
  `date_period` int(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '按每月的几号',
  `next_count_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '下个结算时间点',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='根据渠道单独设置的结算周期生成结算时间表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_union`
--

CREATE TABLE `tab_promote_union` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `union_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员ID',
  `union_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广账号',
  `apply_domain_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '申请域名方式 0系统分配  1自己填写',
  `apply_time` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '审核状态 -1 已驳回 0待审核  1已通过',
  `dispose_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人',
  `dispose_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `domain_url` varchar(255) NOT NULL DEFAULT '' COMMENT '域名',
  `union_set` text NOT NULL,
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='联盟申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_union_article`
--

CREATE TABLE `tab_promote_union_article` (
  `id` int(10) UNSIGNED NOT NULL,
  `promote_id` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='联盟站文章表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_user_bind_discount`
--

CREATE TABLE `tab_promote_user_bind_discount` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_account` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '折扣类型  1全部玩家,2=部分玩家',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏名称',
  `first_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首冲折扣',
  `continue_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续充折扣',
  `first_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '首冲折扣状态',
  `continue_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '续冲状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='渠道自定义绑币充值折扣表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_user_bind_discount_game_user`
--

CREATE TABLE `tab_promote_user_bind_discount_game_user` (
  `id` int(11) NOT NULL,
  `user_bind_discount_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '折扣id',
  `game_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='渠道自定义绑币充值折扣设置部分玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_user_welfare`
--

CREATE TABLE `tab_promote_user_welfare` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_account` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '折扣类型  1全部玩家,2=部分玩家',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏名称',
  `first_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首冲折扣',
  `continue_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续充折扣',
  `first_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '首冲折扣状态',
  `continue_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '续冲状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='渠道自定义折扣表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_user_welfare_game_user`
--

CREATE TABLE `tab_promote_user_welfare_game_user` (
  `id` int(11) NOT NULL,
  `user_welfare_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '折扣id',
  `game_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='渠道自定义折扣设置部分玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_promote_withdraw`
--

CREATE TABLE `tab_promote_withdraw` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `widthdraw_number` varchar(100) NOT NULL DEFAULT '' COMMENT '自动打款订单号',
  `sum_money` double NOT NULL DEFAULT '0' COMMENT '结算金额',
  `fee` double NOT NULL DEFAULT '0' COMMENT '提现手续费',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `audit_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '提现状态( 0 申请中 1 已通过 2 已驳回 3已打款)',
  `withdraw_type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '打款方式(0；1-手动打款;2-自动打款)',
  `withdraw_way` tinyint(2) NOT NULL DEFAULT '1' COMMENT '打款途径(0;1-支付宝;2-微信 3-银行卡)',
  `tx_account` tinyint(2) NOT NULL DEFAULT '0' COMMENT '自动打款状态(0-未打款;1-成功;2-失败)',
  `respond` varchar(255) NOT NULL DEFAULT '' COMMENT '自动打款说明',
  `paid_time` int(11) NOT NULL DEFAULT '0' COMMENT '打款时间',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '提现类型 1：提现 2：兑换',
  `promote_level` tinyint(2) NOT NULL DEFAULT '1' COMMENT '提现类型 1：一级推广员 2：二级推广员',
  `payment_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打款金额',
  `payment_account` varchar(50) NOT NULL DEFAULT '' COMMENT '打款账户(支付宝账号/银行卡号)',
  `payment_name` varchar(50) NOT NULL DEFAULT '' COMMENT '打款姓名(支付宝账号/持卡人)',
  `payment_bank` varchar(100) NOT NULL DEFAULT '' COMMENT '打款银行(收款的银行)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '打款备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='提现表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_protocol`
--

CREATE TABLE `tab_protocol` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '编号',
  `language` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '语言版本(0:中文，1:英文，2:中文繁体)',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext NOT NULL COMMENT '内容',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '添加者'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `tab_protocol`
--

INSERT INTO `tab_protocol` (`id`, `language`, `title`, `content`, `create_time`, `update_time`, `status`, `user_id`) VALUES
(1, 0, '用户协议', '&lt;p&gt;用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议用户协议&lt;/p&gt;', 1618819720, 1618820011, 1, 0),
(2, 1, 'User Registration Agreement', '&lt;p&gt;User Registration Agreement&lt;/p&gt;&lt;p&gt;User Registration Agreement&lt;/p&gt;&lt;p&gt;User Registration Agreement&lt;/p&gt;&lt;p&gt;User Registration Agreement&lt;/p&gt;&lt;audio controls=&quot;controls&quot; style=&quot;display: none;&quot;&gt;&lt;/audio&gt;', 1618820946, 1618820946, 1, 0),
(3, 2, '用戶註冊協議', '用戶註冊協議&lt;audio controls=&quot;controls&quot; style=&quot;display: none;&quot;&gt;用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議用戶註冊協議&lt;/audio&gt;', 1618821017, 1618821017, 1, 0),
(4, 3, 'ユーザ登録プロトコル', '&lt;p&gt;ユーザ登録プロトコル&lt;/p&gt;&lt;p&gt;ユーザ登録プロトコル&lt;/p&gt;', 1618821083, 1618824143, 1, 0),
(5, 4, '사용자 등록 프로 토 콜', '&lt;p&gt;사용자 등록 프로 토 콜&lt;/p&gt;&lt;p&gt;사용자 등록 프로 토 콜&lt;/p&gt;&lt;p&gt;사용자 등록 프로 토 콜&lt;/p&gt;&lt;p&gt;사용자 등록 프로 토 콜&lt;/p&gt;&lt;audio controls=&quot;controls&quot; style=&quot;display: none;&quot;&gt;&lt;/audio&gt;', 1618880781, 1618880781, 1, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tab_safe_center`
--

CREATE TABLE `tab_safe_center` (
  `id` int(11) NOT NULL,
  `config` text NOT NULL COMMENT '配置信息',
  `ids` text NOT NULL COMMENT '渠道或管理员的id',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '重要信息备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-安全中心配置';

--
-- 转存表中的数据 `tab_safe_center`
--

INSERT INTO `tab_safe_center` (`id`, `config`, `ids`, `remark`, `create_time`, `update_time`) VALUES
(1, '', '', '管理员的ids', 1623920356, 1623920356),
(2, '', '', '渠道的ids', 1623920356, 1623920356),
(3, '', '', '管理员限制单端登录 管理员ids', 1623920356, 1623920356),
(4, '', '', '渠道显示单端登录 渠道的ids', 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tab_seo`
--

CREATE TABLE `tab_seo` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `name` varchar(35) NOT NULL DEFAULT '' COMMENT '标识',
  `title` varchar(25) NOT NULL DEFAULT '' COMMENT '标题',
  `seo_title` varchar(65) NOT NULL DEFAULT '' COMMENT '搜索优化标题',
  `seo_keyword` varchar(165) NOT NULL DEFAULT '' COMMENT '搜索优化关键字',
  `seo_description` varchar(255) NOT NULL DEFAULT '' COMMENT '搜索优化描述'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='seo设置表' ROW_FORMAT=DYNAMIC;

--
-- 转存表中的数据 `tab_seo`
--

INSERT INTO `tab_seo` (`id`, `name`, `title`, `seo_title`, `seo_keyword`, `seo_description`) VALUES
(1, 'media_index', '首页', '%webname% _好玩的手机游戏免费下载_最热门的手机游戏', '首页', '首页'),
(2, 'media_game_list', '游戏列表页', '%webname%  游戏中心_好玩的手机游戏免费下载_最热门的手机游戏', '游戏列表页', '游戏列表页'),
(3, 'media_game_detail', '游戏详情页', '%gamename% _%webname%_最新最热门的游戏免费下载', '游戏详情页', '游戏详情页'),
(13, 'channel_news_list', '资讯页', '%webname%_资讯中心_手机游戏推广联盟_手游推广员赚钱平台', '资讯列表', '资讯列表x'),
(4, 'media_gift_index', '礼包首页', '%webname%礼包中心 _好玩的手机游戏免费下载_最热门的手机游戏', '礼包首页', '礼包首页'),
(12, 'channel_game_list', '全部应用页', '%webname%_所有精品应用_手机游戏推广联盟_手游推广员赚钱平台', '全部应用', '全部应用'),
(6, 'media_gift_detail', '礼包详情页', '%gamename%%giftname%_%webname%', '礼包详情页', '礼包详情页'),
(11, 'channel_index', '首页', '%webname%_合作平台_手机游戏推广联盟_手游推广员赚钱平台', 'zxczxc', 'zxczxc'),
(7, 'media_news_list', '资讯列表页', '%webname% 资讯中心_好玩的手机游戏免费下载_最热门的手机游戏', '礼包列表页', '礼包列表页'),
(8, 'media_news_detail', '资讯详情页', '%newsname%_%webname% ', '资讯详情页', '资讯详情页'),
(9, 'media_recharge', '充值页', '%webname%充值中心_好玩的手机游戏免费下载_最热门的手机游戏', '充值', '充值'),
(10, 'media_service', '客服页', '%webname%客服中心_好玩的手机游戏免费下载_最热门的手机游戏', '客服', '客服'),
(14, 'channel_about', '关于我们页', '%webname%_手机游戏推广联盟_手游推广员赚钱平台', '资讯详情1111111', '资讯详情'),
(15, 'wap_index', '首页', '溪谷手游WAP站', '首页', '首页'),
(16, 'wap_game_list', '游戏列表页', '溪谷手游WAP站', '游戏列表页', '游戏列表页'),
(17, 'wap_game_detail', '游戏详情页', '%gamename%溪谷手游WAP站', '游戏详情', '游戏详情'),
(18, 'wap_gift_index', '礼包页', '溪谷手游WAP站', '礼包中心', '礼包中心'),
(20, 'wap_gift_detail', '礼包详情页', '溪谷手游WAP站', '礼包详情', '礼包详情\r\n'),
(21, 'wap_news_list', '资讯页', '溪谷手游WAP站', '资讯中心', '资讯中心'),
(24, 'wap_article_detail', '资讯详情页', '%newsname%_溪谷手游WAP站', '资讯详情', '资讯详情'),
(26, 'wap_recharge', '充值页', '溪谷手游WAP站', ' 游戏排行榜', ' 游戏排行榜'),
(27, 'media_open_game', '游戏落地页', '', '', ''),
(28, 'wap_open_game', '游戏落地页', '', '', '');

-- --------------------------------------------------------

--
-- 表的结构 `tab_sms_config`
--

CREATE TABLE `tab_sms_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `appid` varchar(50) NOT NULL DEFAULT '' COMMENT '应用id',
  `secret` varchar(200) NOT NULL DEFAULT '' COMMENT '应用标识',
  `captcha_tid` varchar(30) NOT NULL DEFAULT '0' COMMENT '模板id',
  `sell_tid` varchar(30) NOT NULL DEFAULT '0' COMMENT '出售小号模板id',
  `public_tid` varchar(30) NOT NULL DEFAULT '' COMMENT '签名名称',
  `unsubscribe_tid` varchar(30) NOT NULL DEFAULT '' COMMENT '账号注销模板id',
  `unsub_verify_tid` varchar(30) NOT NULL DEFAULT '' COMMENT '注销验证接口',
  `everyday_notice_tid` varchar(30) NOT NULL DEFAULT '' COMMENT '每日通知模板id',
  `client_send_max` tinyint(2) UNSIGNED NOT NULL DEFAULT '6' COMMENT '每日IP发送数量',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_stage_tid` varchar(255) NOT NULL DEFAULT '' COMMENT '玩家阶段提醒通知模板'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信配置表';

--
-- 转存表中的数据 `tab_sms_config`
--

INSERT INTO `tab_sms_config` (`id`, `appid`, `secret`, `captcha_tid`, `sell_tid`, `public_tid`, `unsubscribe_tid`, `unsub_verify_tid`, `everyday_notice_tid`, `client_send_max`, `status`, `create_time`, `update_time`, `user_stage_tid`) VALUES
(1, '', '', '99', '0', '0', '', '', '', 0, 1, 0, 1569046835, '');

-- --------------------------------------------------------

--
-- 表的结构 `tab_sms_log`
--

CREATE TABLE `tab_sms_log` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '主键',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '电话号码',
  `send_status` varchar(10) NOT NULL DEFAULT '0' COMMENT '短信发送状态',
  `send_time` varchar(15) NOT NULL DEFAULT '' COMMENT '发送时间',
  `smsId` varchar(40) NOT NULL DEFAULT '' COMMENT '发送短信唯一标识',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '记录时间',
  `pid` varchar(40) NOT NULL DEFAULT '' COMMENT '渠道id',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态',
  `ratio` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '比率',
  `create_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '发送ip'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend`
--

CREATE TABLE `tab_spend` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `game_player_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '玩家游戏内id',
  `game_player_name` varchar(100) NOT NULL DEFAULT '' COMMENT '游戏玩家昵称',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `props_name` varchar(30) NOT NULL DEFAULT '' COMMENT '道具名称',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `discount` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '折扣',
  `discount_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0:无折扣 1：首冲 2：续冲',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功 2苹果订单异常',
  `pay_game_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '游戏支付通知状态 0 失败 1成功 ',
  `extra_param` varchar(255) NOT NULL DEFAULT '' COMMENT '登录时透传给cp的参数 cp原样返回',
  `extend` varchar(255) NOT NULL DEFAULT '' COMMENT '通知游戏方扩展(一般是游戏方透传的订单)',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付方式1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付',
  `spend_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付IP',
  `is_check` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否需要补收益单  0 否  1是',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '1' COMMENT '区别sdk版本1安卓 2苹果 ',
  `receipt` varchar(1000) NOT NULL DEFAULT '' COMMENT '苹果服务器返回json',
  `role_level` int(8) NOT NULL DEFAULT '0' COMMENT '角色等级',
  `small_id` int(11) NOT NULL DEFAULT '0' COMMENT '小号id',
  `small_nickname` varchar(30) DEFAULT '' COMMENT '当前小号昵称',
  `coupon_record_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券记录id',
  `is_weiduan` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否微端支付  0否 1是',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `pay_promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '支付商户推广员id',
  `game_notify_info` varchar(1000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '游戏回调信息',
  `product_id` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '商品编号',
  `currency_code` varchar(10) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'CNY' COMMENT '货币来源类型',
  `us_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额(美元)',
  `currency_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额(当地金额)',
  `area` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型(0:sdk,1:海外)',
  `goods_reserve` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '游戏内额外角色信息',
  `auto_repair_times` tinyint(2) NOT NULL COMMENT '自动补单次数>5不执行',
  `platform_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方平台id 0本平台',
  `webplatform_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '简化版用户ID',
  `platform_pay_type` tinyint(11) NOT NULL DEFAULT '0' COMMENT '第三方平台支付方式  1第三方平台  2本平台',
  `cp_id` int(11) NOT NULL DEFAULT '0' COMMENT '0未给CP结算,其他数字为cp的id,代表已经给cp结算过了',
  `cp_settlement_period_id` int(11) NOT NULL DEFAULT '0' COMMENT '0未给CP结算,其他数字为cp结算单的id,代表已经给cp结算过了'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_balance`
--

CREATE TABLE `tab_spend_balance` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '外部订单号(支付成功后回调获取)',
  `pay_order_number` varchar(50) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员编号',
  `pay_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额 实付金额',
  `cost` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '真实金额',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `pay_way` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付方式 3:支付宝，4:微信',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付ip',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pay_id` int(11) NOT NULL DEFAULT '0',
  `pay_account` varchar(50) NOT NULL DEFAULT '',
  `small_id` int(11) NOT NULL DEFAULT '0' COMMENT '小号id',
  `small_nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '当前小号昵称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='平台币充值记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_bind`
--

CREATE TABLE `tab_spend_bind` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '支付订单号 ',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员ID',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际金额',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 1 成功  0失败',
  `discount` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '折扣比例',
  `discount_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '折扣类型0:游戏的配置折扣 1：首冲 2：续冲',
  `pay_way` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付方式  3:支付宝,4:微信',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '充值iP',
  `pay_id` int(11) NOT NULL DEFAULT '0' COMMENT '充值用户id',
  `pay_account` varchar(30) NOT NULL DEFAULT '' COMMENT '充值账号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绑币充值记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_bind_discount`
--

CREATE TABLE `tab_spend_bind_discount` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '绑币充值折扣表',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '折扣类型  1全站  2 官方  3渠道 4部分渠道 5部分玩家',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏名称',
  `first_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首冲折扣',
  `continue_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续充折扣',
  `first_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '首冲折扣状态',
  `continue_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '续冲状态',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='绑币充值折扣表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_bind_discount_game_user`
--

CREATE TABLE `tab_spend_bind_discount_game_user` (
  `id` int(11) NOT NULL COMMENT '绑币充值部分玩家关联表',
  `bind_discount_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑币充值折扣id',
  `game_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绑币充值折扣设置部分玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_bind_discount_promote`
--

CREATE TABLE `tab_spend_bind_discount_promote` (
  `id` int(11) NOT NULL COMMENT '绑币充值部分渠道关联表',
  `bind_discount_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑币充值折扣id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绑币充值折扣设置推广员表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_distinction`
--

CREATE TABLE `tab_spend_distinction` (
  `id` int(11) NOT NULL,
  `spend_id` int(11) NOT NULL DEFAULT '0' COMMENT 'spend表id',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `extend` varchar(32) NOT NULL DEFAULT '' COMMENT '通知游戏方扩展(一般是游戏方透传)',
  `last_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '最后金额金额',
  `receipt` varchar(255) NOT NULL DEFAULT '' COMMENT '苹果服务器返回json',
  `currency` varchar(20) NOT NULL DEFAULT '' COMMENT '币种',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='苹果异常订单';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_payconfig`
--

CREATE TABLE `tab_spend_payconfig` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '标识',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '标题',
  `config` varchar(500) NOT NULL DEFAULT '' COMMENT '配置文件内容',
  `template` varchar(500) NOT NULL DEFAULT '' COMMENT '模板内容',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='扩展工具表';

--
-- 转存表中的数据 `tab_spend_payconfig`
--

INSERT INTO `tab_spend_payconfig` (`id`, `name`, `title`, `config`, `template`, `type`, `status`, `create_time`) VALUES
(1, 'zfb', '支付宝', '', '', 0, 1, 1549936632),
(2, 'wxscan', '微信扫码', '', '', 0, 1, 1549936632),
(3, 'wxapp', '微信app', '', '', 0, 1, 1549936632),
(5, 'ptb_pay', '平台币', '', '', 0, 1, 1549936632),
(6, 'bind_pay', '绑币', '', '', 0, 1, 1549936632),
(7, 'zfb_tx', '支付宝(提现)', '', '', 0, 0, 1549936632);

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_promote_coin`
--

CREATE TABLE `tab_spend_promote_coin` (
  `id` int(11) UNSIGNED NOT NULL,
  `promote_id` int(255) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_type` tinyint(11) NOT NULL DEFAULT '0' COMMENT '渠道等级 1：一级 2：二级 3:三级',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1:发放 2:扣除',
  `source_id` int(11) NOT NULL DEFAULT '0' COMMENT '来源：0后台系统  1：收益兑换'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='后台发放 推广员';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_provide`
--

CREATE TABLE `tab_spend_provide` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '商户订单号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `cost` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否计算成本 0不计算 1计算',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广id',
  `amount` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '充值时间',
  `coin_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0平台币  1 绑定平台币',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1增加  2减少'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绑币发放记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_rebate`
--

CREATE TABLE `tab_spend_rebate` (
  `id` int(11) NOT NULL,
  `game_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(50) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '返利类型  1全站  2 官方  3渠道 4部分渠道 5部分玩家',
  `money` varchar(150) NOT NULL DEFAULT '' COMMENT '单笔金额',
  `ratio` varchar(150) NOT NULL DEFAULT '' COMMENT '返利比例',
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态(0关闭金额限制 1 开启金额限制)',
  `bind_status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑币消费返利状态 0:排除 1:包含',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '返利开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '返利结束时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='返利设置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_rebate_game_user`
--

CREATE TABLE `tab_spend_rebate_game_user` (
  `id` int(11) NOT NULL,
  `rebate_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '返利id',
  `game_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='返利设置部分玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_rebate_promote`
--

CREATE TABLE `tab_spend_rebate_promote` (
  `id` int(11) NOT NULL,
  `rebate_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '返利id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='返利设置推广员表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_rebate_record`
--

CREATE TABLE `tab_spend_rebate_record` (
  `id` int(11) NOT NULL,
  `pay_order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户名',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '返利比例',
  `ratio_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '返利绑定平台币',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员姓名',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='返利记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_welfare`
--

CREATE TABLE `tab_spend_welfare` (
  `id` int(11) UNSIGNED NOT NULL,
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '折扣类型  1全站  2 官方  3渠道 4部分渠道 5部分玩家',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '游戏名称',
  `first_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首冲折扣',
  `continue_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续充折扣',
  `first_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '首冲折扣状态',
  `continue_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '续冲状态',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='折扣表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_welfare_game_user`
--

CREATE TABLE `tab_spend_welfare_game_user` (
  `id` int(11) NOT NULL,
  `welfare_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '折扣id',
  `game_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='折扣设置部分玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_welfare_promote`
--

CREATE TABLE `tab_spend_welfare_promote` (
  `id` int(11) NOT NULL,
  `welfare_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '折扣id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='折扣设置推广员表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_spend_wxparam`
--

CREATE TABLE `tab_spend_wxparam` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `partner` varchar(150) NOT NULL DEFAULT '' COMMENT '商户号',
  `appid` varchar(150) NOT NULL DEFAULT '' COMMENT 'appid',
  `key` varchar(150) NOT NULL DEFAULT '' COMMENT 'api秘钥',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1开启  0关闭',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微端微信支付配置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_support`
--

CREATE TABLE `tab_support` (
  `id` int(10) UNSIGNED NOT NULL,
  `promote_id` int(10) NOT NULL DEFAULT '0' COMMENT '渠道id',
  `promote_account` varchar(255) NOT NULL DEFAULT '' COMMENT '渠道名称',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '用户密码',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(10) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(255) NOT NULL DEFAULT '' COMMENT '区服名称',
  `role_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名称',
  `apply_num` int(10) NOT NULL DEFAULT '0' COMMENT '申请数量',
  `support_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '扶持类型(0:首次申请, 1:后续申请)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '申请人备注',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `audit_time` int(10) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `usable_num` int(10) NOT NULL DEFAULT '0' COMMENT '可用额度',
  `send_num` int(10) NOT NULL DEFAULT '0' COMMENT '实际发放数量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '申请状态(0:未审核 1:已审核 2:已拒绝 3:已发放 -1:已删除)',
  `audit_idea` varchar(255) NOT NULL DEFAULT '' COMMENT '审核意见',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扶持申请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_task_trigger`
--

CREATE TABLE `tab_task_trigger` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '任务标题',
  `class_name` varchar(255) NOT NULL DEFAULT '' COMMENT '类名称(包含命名空间)',
  `function_name` varchar(255) NOT NULL DEFAULT '' COMMENT '方法名称',
  `param` text NOT NULL COMMENT '参数 json 格式保存',
  `remark` text NOT NULL COMMENT '备注',
  `error_num` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '执行失败次数',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '任务状态(0:待执行 1:已执行)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='待执行任务表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_tip`
--

CREATE TABLE `tab_tip` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '编号',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏编号',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `comment_id` int(11) NOT NULL DEFAULT '0' COMMENT '评论id',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '消息类型(新增字段),0:未指定, 1:代金券到期通知, 2:邀请奖励,注册奖励发放通知, 3:试玩任务通知, 4:开服提醒, 5:评论回复通知',
  `read_or_not` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否已读(新增字段),1:未读, 0:已读'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息中心';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user`
--

CREATE TABLE `tab_user` (
  `id` int(11) NOT NULL COMMENT '主键自增',
  `account` varchar(200) NOT NULL DEFAULT '' COMMENT '登陆账号',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆密码',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父类id',
  `parent_name` varchar(30) NOT NULL DEFAULT '' COMMENT '父类名称',
  `fgame_id` int(11) NOT NULL DEFAULT '0' COMMENT '第一次登陆的游戏id',
  `fgame_name` varchar(30) NOT NULL DEFAULT '' COMMENT '第一次登陆的游戏',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别(0 男 1 女)',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'qq',
  `phone` varchar(15) NOT NULL DEFAULT '' COMMENT '手机号码',
  `receive_address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货地址',
  `real_name` varchar(20) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `idcard` varchar(20) NOT NULL DEFAULT '' COMMENT '身份证',
  `vip_level` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'vip等级',
  `cumulative` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计游戏充值 所有支付方式 包括绑币',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `anti_addiction` tinyint(2) NOT NULL DEFAULT '0' COMMENT '防沉迷',
  `lock_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '锁定状态',
  `age_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '实名认证 0未审核 1未通过 2已成年 3未成年 4审核中',
  `register_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '注册来源 1sdk 2app 3PC 4wap',
  `register_type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '注册方式 0游客1账号 2 手机 3微信 4QQ 5百度 6微博 7邮箱 8苹果 9脸书',
  `register_time` varchar(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `register_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '注册ip',
  `login_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '登陆ip',
  `is_check` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否对账  1参与 2不参与',
  `settle_check` tinyint(2) NOT NULL DEFAULT '0' COMMENT '渠道结算 0未结算  1 已结算',
  `sub_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '子渠道结算状态(0未结算 1已结算)',
  `token` varchar(255) NOT NULL DEFAULT '' COMMENT 'token',
  `pkey` varchar(150) NOT NULL DEFAULT '' COMMENT '手机身份识别码设备码',
  `unionid` varchar(200) NOT NULL DEFAULT '' COMMENT '第三方唯一标识',
  `head_img` varchar(500) NOT NULL DEFAULT '' COMMENT '头像 链接',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `gold_coin` double(11,2) NOT NULL DEFAULT '0.00' COMMENT '金币',
  `alipay` varchar(20) NOT NULL DEFAULT '' COMMENT '绑定支付宝',
  `alipay_real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '支付宝实名码',
  `old_promote_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 原所属渠道ID',
  `third_authentication` tinyint(1) NOT NULL DEFAULT '0' COMMENT '第三方实名认证  1支付宝',
  `equipment_num` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '设备号',
  `is_bind` tinyint(2) NOT NULL DEFAULT '0' COMMENT '游客账号是否已修改账号密码  0 否 1是',
  `puid` int(11) NOT NULL DEFAULT '0' COMMENT '小号所属平台账号编号（0表示此账号不是小号）',
  `invitation_id` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人id',
  `is_platform` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否公用账户（0:不是，1：是）',
  `is_prompt` tinyint(1) NOT NULL DEFAULT '0' COMMENT '交易订单提示状态 0 提示 1 不提示',
  `is_sell_prompt` tinyint(1) NOT NULL DEFAULT '0' COMMENT '卖号提示 0 提示1不提示',
  `member_days` int(6) NOT NULL DEFAULT '0' COMMENT '会员购买时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '会员结束日期',
  `wx_nickname` varchar(60) NOT NULL DEFAULT '' COMMENT '微信昵称',
  `tplay_cash` decimal(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '试玩红包金额',
  `openid` varchar(200) NOT NULL DEFAULT '' COMMENT '微信openid参数',
  `pay_password` varchar(100) NOT NULL DEFAULT '' COMMENT '支付密码',
  `device_name` varchar(100) NOT NULL DEFAULT '' COMMENT '设备型号',
  `is_unsubscribe` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否注销 (0:未注销 1:已注销)',
  `wechat_cdkey` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '关注公众号获取的cdkey',
  `sso` tinyint(2) NOT NULL DEFAULT '0' COMMENT '单点登录开关(1:开启 0:关闭)',
  `is_simulator` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否可以模拟器登录(1:允许 0:禁止)',
  `is_batch_create` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否批量创建(1:批量创建 0:用户注册)',
  `user_score` decimal(8,1) NOT NULL DEFAULT '0.0' COMMENT '用户评分',
  `user_stage_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户所属阶段(阶段的id号)',
  `user_stage_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '用户阶段备注信息',
  `login_equipment_num` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '登录设备号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_age_record`
--

CREATE TABLE `tab_user_age_record` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '主键自增',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT 'game_id',
  `idcard` varchar(100) NOT NULL DEFAULT '' COMMENT '身份证号',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `pi` varchar(100) NOT NULL DEFAULT '' COMMENT '已通过认证玩家唯一标识',
  `age_status` tinyint(11) NOT NULL DEFAULT '0' COMMENT '实名认证状态 0未认证 1认证成功 2认证失败',
  `request_status` tinyint(11) NOT NULL DEFAULT '1' COMMENT '1认证成功 2等待认证中 3认证失败 ',
  `last_request_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次请求时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_auth_code`
--

CREATE TABLE `tab_user_auth_code` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(500) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_award`
--

CREATE TABLE `tab_user_award` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `award` int(11) NOT NULL DEFAULT '0' COMMENT '奖励金额或者代金券id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖品类型 1积分2平台币3代金券4商品5谢谢惠顾',
  `probability` int(11) NOT NULL DEFAULT '0' COMMENT '中奖基数',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '发放数量',
  `cover` varchar(100) NOT NULL DEFAULT '' COMMENT '封面图',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='抽奖奖品';

--
-- 转存表中的数据 `tab_user_award`
--

INSERT INTO `tab_user_award` (`id`, `name`, `award`, `type`, `probability`, `stock`, `number`, `cover`, `create_time`) VALUES
(1, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(2, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(3, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(4, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(5, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(6, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(7, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208),
(8, '谢谢惠顾', 0, 5, 0, 1, 0, '', 1596606208);

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_award_record`
--

CREATE TABLE `tab_user_award_record` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `award` int(11) NOT NULL DEFAULT '0' COMMENT '奖励金额或者代金券id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖品类型 1积分2平台币3代金券4商品5谢谢惠顾',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '消耗积分',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抽奖记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_balance_edit`
--

CREATE TABLE `tab_user_balance_edit` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `prev_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '修改前金额',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '修改后金额',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '货币类型',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='货币修改记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_batch_create_log`
--

CREATE TABLE `tab_user_batch_create_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `create_number` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '创建账号',
  `promote_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(255) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `game_id` int(10) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='批量创建用户账号记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_behavior`
--

CREATE TABLE `tab_user_behavior` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '-1取消收藏，1已收藏，-2不显示足迹，2显示足迹3浏览通知',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_cash_out`
--

CREATE TABLE `tab_user_cash_out` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '提现记录表',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '' COMMENT '用户账号',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `apply_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申请提现金额',
  `commission` varchar(50) NOT NULL DEFAULT '0' COMMENT '申请提现时的手续费(%)',
  `real_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态 0=未打款,1=成功打款,-1=打款失败,9=驳回',
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '提现的订单号',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `create_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '创建时ip',
  `payment_no` varchar(64) NOT NULL DEFAULT '' COMMENT '付款单号(付款成功后返回)',
  `payment_time` varchar(50) NOT NULL DEFAULT '' COMMENT '付款时间',
  `success_time` int(10) NOT NULL DEFAULT '0' COMMENT '成功到账或失败时间',
  `wx_openid` varchar(255) NOT NULL DEFAULT '' COMMENT '微信授权用户openid',
  `ali_account` varchar(255) NOT NULL DEFAULT '' COMMENT '支付宝账号',
  `account_name` varchar(255) NOT NULL DEFAULT '' COMMENT '打款的账号姓名(目前支付宝核验使用)',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '提现类型4=微信,3=支付宝',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '微信/支付宝转账返回的信息(主要记录失败原因)',
  `fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费金额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_config`
--

CREATE TABLE `tab_user_config` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '标识',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '标题',
  `config` text NOT NULL COMMENT '配置文件内容',
  `template` text NOT NULL COMMENT '模板内容',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='扩展工具表';

--
-- 转存表中的数据 `tab_user_config`
--

INSERT INTO `tab_user_config` (`id`, `name`, `title`, `config`, `template`, `type`, `status`, `create_time`) VALUES
(1, 'age', '', '', '', 0, 1, 1549936632),
(2, 'age_prevent', '', '', '', 0, 1, 1549936632),
(3, 'wechat', '', '', '', 0, 1, 1549936632),
(4, 'qq_login', '', '', '', 0, 1, 1549936632),
(5, 'weixin_login', '', '', '', 0, 1, 1549936632),
(6, 'app_qq_login', '', '', '', 0, 1, 1549936632),
(7, 'app_weixin_login', '', '', '', 0, 1, 1549936632);

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_day_login`
--

CREATE TABLE `tab_user_day_login` (
  `id` int(11) NOT NULL COMMENT '登录记录表id',
  `login_record_id` int(11) NOT NULL DEFAULT '0' COMMENT '登录记录id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `is_new` tinyint(1) NOT NULL DEFAULT '0' COMMENT '当game_id =0 is_new=1 时 为平台新用户 game_id<>0 is_new=1游戏新用户（即sdk注册）  is_new=0 老用户登录',
  `equipment_num` varchar(255) NOT NULL DEFAULT '' COMMENT '设备号',
  `sdk_version` tinyint(1) NOT NULL DEFAULT '0' COMMENT '设备系统 1安卓 2苹果',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日登录记录表  每个用户每个游戏  每日记录一次';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_deduct_bind`
--

CREATE TABLE `tab_user_deduct_bind` (
  `id` int(11) NOT NULL COMMENT '自增id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(20) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `amount` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '回收绑定平台币数量',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '执行人id',
  `op_account` varchar(20) NOT NULL DEFAULT '0' COMMENT '执行人账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='绑币回收记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_feedback`
--

CREATE TABLE `tab_user_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '提交用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(60) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ',
  `tel` varchar(32) NOT NULL DEFAULT '' COMMENT '联系电话',
  `report_type` varchar(500) NOT NULL DEFAULT '' COMMENT '举报类型',
  `remark` varchar(1000) NOT NULL DEFAULT '' COMMENT '备注',
  `images` varchar(1000) NOT NULL DEFAULT '' COMMENT '图片',
  `admin_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '后台备注',
  `mobile_type` varchar(255) NOT NULL DEFAULT '' COMMENT '手机型号',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='投诉反馈表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_game_login`
--

CREATE TABLE `tab_user_game_login` (
  `id` int(11) NOT NULL COMMENT '登录记录表id',
  `time` varchar(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `play_time` int(5) NOT NULL DEFAULT '0' COMMENT '游戏在线时间',
  `login_count` int(3) NOT NULL DEFAULT '0' COMMENT '打开sdk次数',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_down_time` int(11) NOT NULL DEFAULT '0' COMMENT '上一次退出时间',
  `effective_time` int(5) NOT NULL DEFAULT '0' COMMENT '防沉迷游戏时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户游戏在线统计';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_invitation`
--

CREATE TABLE `tab_user_invitation` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `invitation_id` int(11) NOT NULL DEFAULT '0' COMMENT '被邀请id',
  `invitation_account` varchar(30) NOT NULL DEFAULT '' COMMENT '被邀请人账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='邀请表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_invitation_record`
--

CREATE TABLE `tab_user_invitation_record` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1邀请注册奖励  2 邀请充值奖励  3 被邀请人奖励',
  `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '代金券id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `be_invite` int(11) NOT NULL DEFAULT '0' COMMENT '被邀请者id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='邀请奖励记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_item`
--

CREATE TABLE `tab_user_item` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `register_award` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `bind_phone` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `improve_address` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `bind_email` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `auth_idcard` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `play_game` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `first_pay` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `vip_upgrade` varchar(255) NOT NULL DEFAULT '' COMMENT ' 1未完成 2已完成 3已奖励',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `change_headimg` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `change_nickname` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `bind_qq` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励',
  `subscribe_wechat` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0任务关闭时完成不奖励 1未完成 2已完成 3已奖励'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户子表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_login_record`
--

CREATE TABLE `tab_user_login_record` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT ' 游戏名称',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `game_player_name` varchar(30) NOT NULL DEFAULT '' COMMENT '角色名称',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT '登陆时间',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '登陆ip',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型(1:游戏登陆,2:PC登陆,3wap登录)',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '0' COMMENT '区别sdk版本1安卓 2苹果 ',
  `status` int(3) NOT NULL DEFAULT '0' COMMENT '0未使用  1已使用',
  `puid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小号所属平台账号编号（0表示此账号不是小号）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='登录记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_member`
--

CREATE TABLE `tab_user_member` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户名',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '渠道所属账号名称',
  `pay_amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '充值数额',
  `pay_way` tinyint(2) NOT NULL DEFAULT '3' COMMENT '支付方式( 3:支付宝,4:微信）',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1成功',
  `order_number` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_order_number` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '支付订单号',
  `member_name` varchar(30) NOT NULL DEFAULT '' COMMENT '尊享卡名称',
  `days` int(5) NOT NULL DEFAULT '0' COMMENT '购买天数',
  `free_days` int(5) NOT NULL DEFAULT '0' COMMENT '赠送天数',
  `spend_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '充值IP地址',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '会员开始日期',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '会员结束日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='尊享卡购买记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_mend`
--

CREATE TABLE `tab_user_mend` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `promote_id_to` int(11) NOT NULL DEFAULT '0' COMMENT '修改后推广员id',
  `promote_account_to` varchar(30) NOT NULL DEFAULT '' COMMENT '修改后推广员账号',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `op_account` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人账号',
  `cut_time` int(10) NOT NULL DEFAULT '0' COMMENT '补单切割时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='补链表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_param`
--

CREATE TABLE `tab_user_param` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `openid` varchar(50) NOT NULL DEFAULT '' COMMENT 'QQ openid',
  `key` varchar(50) NOT NULL DEFAULT '' COMMENT '安全校验码',
  `callback` varchar(1000) NOT NULL DEFAULT '' COMMENT '回调地址/redirecturl',
  `wx_appid` varchar(30) NOT NULL DEFAULT '' COMMENT '微信appid',
  `appsecret` varchar(50) NOT NULL DEFAULT '' COMMENT '微信appsecret',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1开启 0关闭',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1qq 2 微信 3脸书',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方登录设置表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_play`
--

CREATE TABLE `tab_user_play` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `game_appid` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏appid',
  `bind_balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '绑定平台币',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `sdk_version` tinyint(2) NOT NULL DEFAULT '1' COMMENT '区别sdk版本1安卓 2苹果 ',
  `play_time` int(11) NOT NULL DEFAULT '0' COMMENT '进游戏时间',
  `play_ip` varchar(30) NOT NULL DEFAULT '' COMMENT '进游戏ip',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `is_small` tinyint(1) DEFAULT '0' COMMENT '是否是小号',
  `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除 0 否 1 是'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_play_info`
--

CREATE TABLE `tab_user_play_info` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` varchar(30) NOT NULL DEFAULT '' COMMENT '区服id',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `role_id` varchar(30) NOT NULL DEFAULT '' COMMENT '角色',
  `role_name` varchar(30) NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_level` int(8) NOT NULL DEFAULT '0' COMMENT '等级',
  `combat_number` varchar(30) NOT NULL DEFAULT '' COMMENT '战力值',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(30) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `play_time` int(11) NOT NULL DEFAULT '0' COMMENT '游戏登陆时间',
  `down_time` int(11) NOT NULL DEFAULT '0' COMMENT '游戏下线时间',
  `play_ip` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏登陆IP',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '数据更新时间',
  `puid` int(11) NOT NULL DEFAULT '0' COMMENT '大号id',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `player_reserve` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '角色信息额外参数',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '首次上传角色上传时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家角色表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_play_record`
--

CREATE TABLE `tab_user_play_record` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `create_time` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='页游玩家玩过区服记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_record`
--

CREATE TABLE `tab_user_point_record` (
  `id` int(11) NOT NULL,
  `type_id` tinyint(3) NOT NULL DEFAULT '0' COMMENT '积分类型',
  `type_name` varchar(60) NOT NULL DEFAULT '' COMMENT '注册类型',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '奖励积分或消费积分',
  `day` int(11) NOT NULL DEFAULT '0' COMMENT '签到时 连续签到天数',
  `vip` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'VIP等级   vip升级获取积分的记录',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分获取记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_shop`
--

CREATE TABLE `tab_user_point_shop` (
  `id` int(11) UNSIGNED NOT NULL,
  `good_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '单价',
  `limit` int(11) NOT NULL DEFAULT '1' COMMENT '限制领取次数',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `good_info` text NOT NULL COMMENT '商品信息',
  `instructions` varchar(1000) NOT NULL DEFAULT '' COMMENT '使用说明',
  `exchange_statement` varchar(1000) NOT NULL DEFAULT '' COMMENT '免责声明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1：开启 0：关闭',
  `thumbnail` varchar(100) NOT NULL DEFAULT '' COMMENT '封面',
  `type` tinyint(11) NOT NULL DEFAULT '1' COMMENT '商品类型 1:实物 2虚拟物品',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `vip_level` varchar(150) NOT NULL DEFAULT '' COMMENT 'VIP等级设置',
  `vip_discount` varchar(150) NOT NULL DEFAULT '' COMMENT 'vip折扣',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分商品';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_shop_record`
--

CREATE TABLE `tab_user_point_shop_record` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `good_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `good_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `good_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '商品类型 1实物 2虚拟',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '虚拟商品激活码',
  `pay_amount` int(11) NOT NULL DEFAULT '0' COMMENT '支付价格',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '购买数量',
  `receive_address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货地址',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '发货状态 0 未发货 1已发货',
  `vip` tinyint(3) NOT NULL DEFAULT '0' COMMENT '商品享受VIP等级',
  `discount` int(3) NOT NULL DEFAULT '100' COMMENT '折扣',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='兑换记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_type`
--

CREATE TABLE `tab_user_point_type` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '任务名称',
  `key` varchar(20) NOT NULL DEFAULT '' COMMENT '关键词',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '奖励积分数',
  `time_of_day` int(11) NOT NULL DEFAULT '0' COMMENT '递增积分',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '1：开启 0：关闭',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '任务类型 1新手任务  2日常任务 3游戏任务 4 推荐任务',
  `send_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '领取类型 0 主动领取  1 自动发放',
  `remark` varchar(150) NOT NULL DEFAULT '' COMMENT '备注说明',
  `description` varchar(100) NOT NULL DEFAULT '' COMMENT '任务说明',
  `birthday_point` tinyint(3) NOT NULL DEFAULT '0' COMMENT '生日充值倍数',
  `sort` bigint(10) NOT NULL DEFAULT '0' COMMENT '排序',
  `cycle` varchar(30) NOT NULL DEFAULT '' COMMENT '周期',
  `cdkey` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '关注微信公众号cdkey'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分任务类型';

--
-- 转存表中的数据 `tab_user_point_type`
--

INSERT INTO `tab_user_point_type` (`id`, `name`, `key`, `point`, `time_of_day`, `status`, `create_time`, `type`, `send_type`, `remark`, `description`, `birthday_point`, `sort`, `cycle`, `cdkey`) VALUES
(1, '注册奖励', 'register_award', 1, 0, 0, 1587707227, 1, 0, '新账号注册成功即可获得，每账号限1次', '完成注册，即可获得！', 0, 1, '一次性', ''),
(2, '绑定手机号码', 'bind_phone', 1, 0, 0, 1587707227, 1, 0, '每账号限1次，通过手机注册的默认绑定手机同样奖励', '绑定手机提升账号安全级别', 0, 2, '一次性', ''),
(3, '完善收货地址', 'improve_address', 1, 0, 0, 1587707227, 1, 0, '添加收货地址即可，每账号限1次', '登录用户中心完善收货地址即可！', 0, 3, '一次性', ''),
(4, '绑定邮箱', 'bind_email', 1, 0, 0, 1587707227, 1, 0, '每账号限1次，通过邮箱注册的默认绑定邮箱同样奖励', '登录用户中心绑定邮箱即可！', 0, 4, '一次性', ''),
(5, '实名认证', 'auth_idcard', 1, 0, 0, 1587707227, 1, 0, '每账号限1次', '登录用户中心实名认证即可！', 0, 5, '一次性', ''),
(6, '游戏体验', 'play_game', 1, 0, 0, 1587707227, 1, 0, '每账号限1次', '登录平台任一款游戏即可！', 0, 6, '一次性', ''),
(7, '首充奖励', 'first_pay', 1, 0, 0, 1587707227, 1, 0, '每账号限1次，首次在游戏内消费（不限制消费方式）', '首次充值游戏即可获得！', 0, 7, '一次性', ''),
(8, 'VIP升级奖励', 'vip_upgrade', 1, 0, 0, 1587707227, 1, 0, '累计游戏内付费金额达到VIP限额即可获得，每账号每等级限1次', '等级到Vip即可，充值更多惊喜！', 0, 8, '一次性', ''),
(9, '天天签到', 'sign_in', 1, 0, 0, 1587707227, 1, 1, '每账号每天限1次', '每日签到即可，连续签到更多惊喜！', 0, 9, '每天', ''),
(10, '充值送积分', 'pay_award', 1, 0, 0, 1587707227, 1, 1, '在游戏内消费时可获得同等积分值，无上限', '充值游戏得同等积分值！', 0, 10, '每天', ''),
(11, '每日登录游戏', 'game_login', 1, 0, 0, 1587707227, 1, 1, '每日登录平台任一游戏SDK即可，每账号每日限1次', '每日登录平台内任意一款游戏即可！', 0, 11, '无限周期', ''),
(12, '试玩有奖', 'try_game', 1, 0, 0, 1587707227, 1, 1, '每账号每任务限1次（不区分是否为多阶段）', '游戏指定区服达指定等级，审核通过后即可获得！', 0, 12, '无限周期', ''),
(14, '设置头像', 'change_headimg', 1, 0, 1, 1587707227, 1, 0, '每账号限1次', '更换头像，即可获得！', 0, 13, '一次性', ''),
(15, '设置昵称', 'change_nickname', 1, 0, 1, 1587707227, 1, 0, '每账号限1次', '设置您独一无二的昵称吧~', 0, 14, '一次性', ''),
(16, '绑定qq', 'bind_qq', 1, 0, 1, 1587707227, 1, 0, '每账号限1次', '方便第一时间处理游戏问题', 0, 15, '一次性', ''),
(17, '关注微信公众号', 'subscribe_wechat', 0, 0, 0, 1587707227, 1, 0, '每账号限1次', '掌握一手游戏资讯，不错过任何福利\r\n\r\n', 0, 16, '一次性', ''),
(18, '每日首充', 'first_pay_every_day', 0, 0, 0, 1587707227, 1, 1, '每账号每日限1次', '每日只要游戏首充，积分自动到账', 0, 17, '每天', ''),
(19, '微信朋友圈分享', 'wx_friends_share', 0, 0, 1, 1587707227, 2, 0, '每账号每日限1次', '分享到朋友圈，结伴游戏更畅快', 0, 18, '每天', ''),
(20, 'QQ空间分享', 'qq_zone_share', 0, 0, 1, 1587707227, 2, 0, '每账号每日限1次', '分享到QQ空间即可获得积分奖励', 0, 19, '每天', '');

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_use`
--

CREATE TABLE `tab_user_point_use` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL DEFAULT '0' COMMENT '子表id ',
  `type_id` tinyint(3) NOT NULL DEFAULT '0' COMMENT '积分类型',
  `type_name` varchar(60) NOT NULL DEFAULT '' COMMENT '注册类型',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '消费积分',
  `good_name` varchar(60) NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分消费记录';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_point_use_type`
--

CREATE TABLE `tab_user_point_use_type` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '任务名称',
  `key` varchar(20) NOT NULL DEFAULT '' COMMENT '关键词'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分使用类型';

--
-- 转存表中的数据 `tab_user_point_use_type`
--

INSERT INTO `tab_user_point_use_type` (`id`, `name`, `key`) VALUES
(1, '兑换商品', 'exchange'),
(2, '积分抽奖', 'draw');

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_stage`
--

CREATE TABLE `tab_user_stage` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '阶段名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '阶段描述',
  `other_setting` text NOT NULL COMMENT '累计消费;消费频次;活跃情况;等设置',
  `follow_remind` text NOT NULL COMMENT '跟进提醒设置',
  `only_for_sort` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '仅仅是一个排序,和进阶没有任何关系',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='阶段记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_third`
--

CREATE TABLE `tab_user_third` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(50) NOT NULL DEFAULT '' COMMENT '用户账号',
  `platform_id` int(1) NOT NULL DEFAULT '0' COMMENT '第三方平台ID',
  `third_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方平台用户id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_tplay`
--

CREATE TABLE `tab_user_tplay` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(60) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(60) NOT NULL DEFAULT '' COMMENT '区服名称',
  `quota` int(11) NOT NULL DEFAULT '0' COMMENT '名额',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态 1开启 0关闭',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取结束时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `time_out` int(11) NOT NULL DEFAULT '0' COMMENT '任务时限（单位：小时）',
  `award` varchar(255) NOT NULL DEFAULT '' COMMENT '奖励',
  `level` varchar(255) NOT NULL DEFAULT '' COMMENT '等级',
  `cash` varchar(255) NOT NULL DEFAULT '' COMMENT '现金奖励'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试玩任务表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_tplay_record`
--

CREATE TABLE `tab_user_tplay_record` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(60) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_id` int(11) NOT NULL DEFAULT '0' COMMENT '区服id',
  `server_name` varchar(60) NOT NULL DEFAULT '' COMMENT '区服名称',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态 0 进行中 1任务完成  2已超时',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取结束时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `award` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '奖励',
  `tplay_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务id',
  `level` int(11) NOT NULL DEFAULT '0' COMMENT '提交任务时角色的最高等级',
  `cash` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '奖励现金'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试玩任务记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_tplay_withdraw`
--

CREATE TABLE `tab_user_tplay_withdraw` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `pay_order_number` varchar(60) NOT NULL DEFAULT '' COMMENT '自动打款订单号',
  `fee` decimal(8,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '提现手续费',
  `money` decimal(8,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '提现方式 0兑换 1提现',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '打款途径(0兑换平台币;1-支付宝;2-微信)',
  `money_account` varchar(60) NOT NULL DEFAULT '' COMMENT '收款账号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打款状态 0失败 1成功 2自动打款失败',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '打款时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试玩红包提现表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_transaction`
--

CREATE TABLE `tab_user_transaction` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏密码',
  `phone` varchar(15) NOT NULL DEFAULT '' COMMENT '手机号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '交易标题',
  `screenshot` varchar(1000) NOT NULL DEFAULT '' COMMENT '图片',
  `dec` varchar(255) NOT NULL DEFAULT '' COMMENT '商品描述',
  `order_number` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `cumulative` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计游戏充值',
  `balance_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '平台币金额',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '交易状态 -1锁定 0审核 1出售中 2驳回 3已出售 4已下架',
  `reject` varchar(255) NOT NULL DEFAULT '' COMMENT '驳回原因',
  `lower_shelf` varchar(255) NOT NULL DEFAULT '' COMMENT '下架原因',
  `small_id` int(11) NOT NULL DEFAULT '0' COMMENT '小号id',
  `abate_money` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '还价价格',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除  0 未删除  1 已删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='小号交易表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_transaction_order`
--

CREATE TABLE `tab_user_transaction_order` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `game_name` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `server_name` varchar(30) NOT NULL DEFAULT '' COMMENT '区服名称',
  `password` varchar(30) NOT NULL DEFAULT '' COMMENT '游戏密码',
  `title` varchar(100) NOT NULL DEFAULT '',
  `screenshot` varchar(1000) NOT NULL DEFAULT '' COMMENT '图片',
  `dec` varchar(255) NOT NULL DEFAULT '',
  `cumulative` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计游戏充值',
  `order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '外部订单编号(支付成功后回调获取)',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `transaction_number` varchar(30) NOT NULL DEFAULT '' COMMENT '交易订单号',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `balance_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '平台币金额',
  `abate_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '还价价格',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态 0失败 1交易成功 2交易失败',
  `pay_way` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付方式1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付',
  `pay_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '支付IP',
  `sell_id` int(11) NOT NULL DEFAULT '0' COMMENT '出售人id',
  `sell_account` varchar(30) NOT NULL DEFAULT '' COMMENT '出售人账号',
  `small_id` int(11) NOT NULL DEFAULT '0' COMMENT '小号id',
  `phone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `transaction_id` int(11) NOT NULL DEFAULT '0' COMMENT '交易id',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除  0 未删除  1 已删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='交易订单表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_transaction_profit`
--

CREATE TABLE `tab_user_transaction_profit` (
  `id` int(11) NOT NULL COMMENT '自增id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '玩家id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `game_name` varchar(20) NOT NULL DEFAULT '' COMMENT '游戏名称',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '回收绑定平台币数量',
  `small_id` int(11) NOT NULL DEFAULT '0' COMMENT '执行人id',
  `small_account` varchar(20) NOT NULL DEFAULT '' COMMENT '执行人账号',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='出售小号收益';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_transaction_tip`
--

CREATE TABLE `tab_user_transaction_tip` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `transaction_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '修改价格',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '修改类型  1价格  2下架',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '当前状态 0未执行 1 已执行'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='改价或下架计划任务表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_user_unsubscribe`
--

CREATE TABLE `tab_user_unsubscribe` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(255) NOT NULL DEFAULT '',
  `user_account_alias` varchar(255) NOT NULL DEFAULT '' COMMENT '注销后账号',
  `apply_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '申请时间',
  `unsubscribe_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '注销时间',
  `err_num` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '失败次数',
  `exception_msg` longtext NOT NULL COMMENT '异常信息',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(10) NOT NULL DEFAULT '1' COMMENT '状态(1 : 待注销 2: 已注销 3: 注销中)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户注销记录表';

-- --------------------------------------------------------

--
-- 表的结构 `tab_warning`
--

CREATE TABLE `tab_warning` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '主键',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型 1余额到账异常 2余额变动异常 3大笔订单预警 4代充折扣异常 5账户修改异常 6后台发放异常',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_account` varchar(200) NOT NULL DEFAULT '' COMMENT '用户账号',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广员id',
  `promote_account` varchar(60) NOT NULL DEFAULT '' COMMENT '推广员账号',
  `target` tinyint(2) NOT NULL DEFAULT '1' COMMENT '充值对象 1绑币 2平台币 3游戏充值 4会长代充',
  `record_id` int(11) NOT NULL DEFAULT '0' COMMENT '记录id',
  `pay_order_number` varchar(100) NOT NULL DEFAULT '' COMMENT '订单号',
  `pay_amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `change_count` tinyint(3) NOT NULL DEFAULT '0' COMMENT '变动次数',
  `unusual_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '异常金额',
  `discount` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '代充折扣',
  `game_id` int(11) NOT NULL DEFAULT '0' COMMENT '游戏id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0未处理 1已处理 2已忽略',
  `op_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核日期',
  `op_id` int(3) NOT NULL DEFAULT '0' COMMENT '管理员id',
  `op_account` varchar(60) NOT NULL DEFAULT '' COMMENT '管理员账号',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `discount_first` decimal(5,2) DEFAULT '0.00' COMMENT '代充首充折扣',
  `discount_continued` decimal(5,2) DEFAULT '0.00' COMMENT '代充续充折扣'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='预警信息表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `tab_web_platform`
--

CREATE TABLE `tab_web_platform` (
  `id` int(11) NOT NULL,
  `platform_name` varchar(50) NOT NULL DEFAULT '' COMMENT '系统名称',
  `platform_url` varchar(255) NOT NULL DEFAULT '' COMMENT '系统域名',
  `promote_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道ID',
  `promote_account` varchar(125) NOT NULL DEFAULT '' COMMENT '渠道账号',
  `api_key` varchar(125) NOT NULL DEFAULT '' COMMENT '接口密钥',
  `pay_type` tinyint(11) NOT NULL DEFAULT '1' COMMENT '支付通道 1平台  2第三方',
  `status` tinyint(11) NOT NULL DEFAULT '1' COMMENT '系统状态  1开启  2关闭',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `op_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `op_nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '管理员账号',
  `my_url` varchar(255) NOT NULL DEFAULT '' COMMENT '我方平台域名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简化版对接站点表(下级站点)';

-- --------------------------------------------------------

--
-- 表的结构 `tab_web_platform_user`
--

CREATE TABLE `tab_web_platform_user` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_account` varchar(11) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `third_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '简化版平台用户id',
  `third_user_account` varchar(11) NOT NULL DEFAULT '' COMMENT '第三方玩家账号',
  `web_platform_id` int(11) NOT NULL DEFAULT '0' COMMENT '简化版平台ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `sys_admin_menu`
--
ALTER TABLE `sys_admin_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `controller` (`controller`);

--
-- 表的索引 `sys_asset`
--
ALTER TABLE `sys_asset`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_auth_access`
--
ALTER TABLE `sys_auth_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `rule_name` (`rule_name`) USING BTREE;

--
-- 表的索引 `sys_auth_rule`
--
ALTER TABLE `sys_auth_rule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`) USING BTREE,
  ADD KEY `module` (`app`,`status`,`type`);

--
-- 表的索引 `sys_comment`
--
ALTER TABLE `sys_comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id_status` (`table_name`,`object_id`,`status`),
  ADD KEY `object_id` (`object_id`) USING BTREE,
  ADD KEY `status` (`status`) USING BTREE,
  ADD KEY `parent_id` (`parent_id`) USING BTREE,
  ADD KEY `create_time` (`create_time`) USING BTREE;

--
-- 表的索引 `sys_hook`
--
ALTER TABLE `sys_hook`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_hook_plugin`
--
ALTER TABLE `sys_hook_plugin`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_nav`
--
ALTER TABLE `sys_nav`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_nav_menu`
--
ALTER TABLE `sys_nav_menu`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_option`
--
ALTER TABLE `sys_option`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `option_name` (`option_name`);

--
-- 表的索引 `sys_plugin`
--
ALTER TABLE `sys_plugin`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_portal_category`
--
ALTER TABLE `sys_portal_category`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_portal_category_post`
--
ALTER TABLE `sys_portal_category_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `term_taxonomy_id` (`category_id`);

--
-- 表的索引 `sys_portal_post`
--
ALTER TABLE `sys_portal_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_status_date` (`post_type`,`post_status`,`create_time`,`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `create_time` (`create_time`) USING BTREE;

--
-- 表的索引 `sys_portal_tag`
--
ALTER TABLE `sys_portal_tag`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_portal_tag_post`
--
ALTER TABLE `sys_portal_tag_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- 表的索引 `sys_recycle_bin`
--
ALTER TABLE `sys_recycle_bin`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_role`
--
ALTER TABLE `sys_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `status` (`status`);

--
-- 表的索引 `sys_role_user`
--
ALTER TABLE `sys_role_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `sys_route`
--
ALTER TABLE `sys_route`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_slide`
--
ALTER TABLE `sys_slide`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_slide_item`
--
ALTER TABLE `sys_slide_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slide_id` (`slide_id`);

--
-- 表的索引 `sys_theme`
--
ALTER TABLE `sys_theme`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_theme_file`
--
ALTER TABLE `sys_theme_file`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_third_party_user`
--
ALTER TABLE `sys_third_party_user`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_user`
--
ALTER TABLE `sys_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_login` (`user_login`),
  ADD KEY `user_nickname` (`user_nickname`);

--
-- 表的索引 `sys_user_action`
--
ALTER TABLE `sys_user_action`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_user_action_log`
--
ALTER TABLE `sys_user_action_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_object_action` (`user_id`,`object`,`action`),
  ADD KEY `user_object_action_ip` (`user_id`,`object`,`action`,`ip`);

--
-- 表的索引 `sys_user_favorite`
--
ALTER TABLE `sys_user_favorite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`user_id`);

--
-- 表的索引 `sys_user_score_log`
--
ALTER TABLE `sys_user_score_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_user_token`
--
ALTER TABLE `sys_user_token`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sys_verification_code`
--
ALTER TABLE `sys_verification_code`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_admin_action_log`
--
ALTER TABLE `tab_admin_action_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `action_time` (`action_time`);

--
-- 表的索引 `tab_admin_remind_msg`
--
ALTER TABLE `tab_admin_remind_msg`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_adv`
--
ALTER TABLE `tab_adv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`) USING BTREE,
  ADD KEY `pos_id` (`pos_id`) USING BTREE,
  ADD KEY `start_time` (`start_time`) USING BTREE,
  ADD KEY `end_time` (`end_time`) USING BTREE;

--
-- 表的索引 `tab_adv_pos`
--
ALTER TABLE `tab_adv_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- 表的索引 `tab_alipay_auth`
--
ALTER TABLE `tab_alipay_auth`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_app`
--
ALTER TABLE `tab_app`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_app_supersign_order`
--
ALTER TABLE `tab_app_supersign_order`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_prop`
--
ALTER TABLE `tab_bt_prop`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_welfare`
--
ALTER TABLE `tab_bt_welfare`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `times` (`start_time`,`end_time`);

--
-- 表的索引 `tab_bt_welfare_monthcard`
--
ALTER TABLE `tab_bt_welfare_monthcard`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_welfare_promote`
--
ALTER TABLE `tab_bt_welfare_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bt_welfare_id` (`bt_welfare_id`),
  ADD KEY `promote_id` (`promote_id`);

--
-- 表的索引 `tab_bt_welfare_recharge`
--
ALTER TABLE `tab_bt_welfare_recharge`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_welfare_register`
--
ALTER TABLE `tab_bt_welfare_register`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_welfare_total_recharge`
--
ALTER TABLE `tab_bt_welfare_total_recharge`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_bt_welfare_weekcard`
--
ALTER TABLE `tab_bt_welfare_weekcard`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_coupon`
--
ALTER TABLE `tab_coupon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`status`,`receive_start_time`,`receive_end_time`) USING BTREE;

--
-- 表的索引 `tab_coupon_promote`
--
ALTER TABLE `tab_coupon_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`) USING BTREE;

--
-- 表的索引 `tab_coupon_record`
--
ALTER TABLE `tab_coupon_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `search` (`user_account`,`coupon_name`,`game_id`,`status`,`get_way`);

--
-- 表的索引 `tab_cp_settlement_period`
--
ALTER TABLE `tab_cp_settlement_period`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_num` (`cp_id`,`order_num`,`is_remit`) USING BTREE;

--
-- 表的索引 `tab_datareport_every_pid`
--
ALTER TABLE `tab_datareport_every_pid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time` (`time`) USING BTREE,
  ADD KEY `p+t` (`promote_id`,`time`) USING BTREE,
  ADD KEY `promote_id` (`time`,`promote_id`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_datareport_top_pid`
--
ALTER TABLE `tab_datareport_top_pid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time` (`time`) USING BTREE,
  ADD KEY `p+t` (`promote_id`,`time`) USING BTREE,
  ADD KEY `promote_id` (`time`,`promote_id`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_data_change_record`
--
ALTER TABLE `tab_data_change_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `data_id` (`data_id`) USING BTREE,
  ADD KEY `data_id_2` (`data_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_equipment_game`
--
ALTER TABLE `tab_equipment_game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_num` (`equipment_num`(191)) USING BTREE,
  ADD KEY `first_device_register` (`first_device`) USING BTREE,
  ADD KEY `create_time` (`promote_id`,`game_id`,`create_time`) USING BTREE;

--
-- 表的索引 `tab_equipment_login`
--
ALTER TABLE `tab_equipment_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `s2` (`equipment_num`,`game_id`) USING BTREE,
  ADD KEY `s1` (`time`,`equipment_num`,`promote_id`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_game`
--
ALTER TABLE `tab_game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_name` (`game_name`),
  ADD KEY `game_appid` (`game_appid`),
  ADD KEY `recommend_status` (`recommend_status`) USING BTREE,
  ADD KEY `relation_game_id` (`relation_game_id`) USING BTREE,
  ADD KEY `game_type_id` (`game_type_id`) USING BTREE,
  ADD KEY `sdk_version` (`sdk_version`) USING BTREE;

--
-- 表的索引 `tab_game_attr`
--
ALTER TABLE `tab_game_attr`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`) USING BTREE;

--
-- 表的索引 `tab_game_ban_set`
--
ALTER TABLE `tab_game_ban_set`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_game_change_name_log`
--
ALTER TABLE `tab_game_change_name_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_game_collect`
--
ALTER TABLE `tab_game_collect`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_game_comment`
--
ALTER TABLE `tab_game_comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`status`) USING BTREE,
  ADD KEY `user_id` (`user_id`,`status`) USING BTREE,
  ADD KEY `top_id` (`top_id`,`status`) USING BTREE;

--
-- 表的索引 `tab_game_comment_follow`
--
ALTER TABLE `tab_game_comment_follow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comment_id` (`comment_id`,`user_id`) USING BTREE;

--
-- 表的索引 `tab_game_config`
--
ALTER TABLE `tab_game_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_game_cp`
--
ALTER TABLE `tab_game_cp`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_game_down_record`
--
ALTER TABLE `tab_game_down_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_game_follow`
--
ALTER TABLE `tab_game_follow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`user_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`,`status`) USING BTREE;

--
-- 表的索引 `tab_game_giftbag`
--
ALTER TABLE `tab_game_giftbag`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `create_time` (`create_time`) USING BTREE,
  ADD KEY `end_time` (`end_time`) USING BTREE,
  ADD KEY `start_time` (`start_time`) USING BTREE,
  ADD KEY `status` (`status`) USING BTREE;

--
-- 表的索引 `tab_game_gift_record`
--
ALTER TABLE `tab_game_gift_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_name` (`game_name`) USING BTREE,
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `gift_id` (`gift_id`) USING BTREE,
  ADD KEY `create_time` (`create_time`) USING BTREE,
  ADD KEY `user_id` (`user_id`,`create_time`) USING BTREE;

--
-- 表的索引 `tab_game_interface`
--
ALTER TABLE `tab_game_interface`
  ADD PRIMARY KEY (`id`),
  ADD KEY `标签` (`tag`);

--
-- 表的索引 `tab_game_interflow`
--
ALTER TABLE `tab_game_interflow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `interflow_tag` (`interflow_tag`);

--
-- 表的索引 `tab_game_ios_pay_to_download`
--
ALTER TABLE `tab_game_ios_pay_to_download`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`pay_download`) USING BTREE;

--
-- 表的索引 `tab_game_ios_pay_to_download_order`
--
ALTER TABLE `tab_game_ios_pay_to_download_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`user_agent`,`user_agent_md5`) USING BTREE;

--
-- 表的索引 `tab_game_ios_pay_to_download_record`
--
ALTER TABLE `tab_game_ios_pay_to_download_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`user_agent`,`user_agent_md5`) USING BTREE;

--
-- 表的索引 `tab_game_server`
--
ALTER TABLE `tab_game_server`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `show_status` (`status`) USING BTREE,
  ADD KEY `start_time` (`start_time`) USING BTREE,
  ADD KEY `create_time` (`create_time`) USING BTREE;

--
-- 表的索引 `tab_game_server_notice`
--
ALTER TABLE `tab_game_server_notice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`,`user_id`,`status`) USING BTREE,
  ADD KEY `search` (`server_id`,`status`);

--
-- 表的索引 `tab_game_set`
--
ALTER TABLE `tab_game_set`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `agent_id` (`agent_id`) USING BTREE,
  ADD KEY `game_pay_appid` (`game_pay_appid`) USING BTREE;

--
-- 表的索引 `tab_game_share_record`
--
ALTER TABLE `tab_game_share_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`game_id`,`user_id`,`create_time`) USING BTREE;

--
-- 表的索引 `tab_game_source`
--
ALTER TABLE `tab_game_source`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`) USING BTREE;

--
-- 表的索引 `tab_game_type`
--
ALTER TABLE `tab_game_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`) USING BTREE;

--
-- 表的索引 `tab_guess`
--
ALTER TABLE `tab_guess`
  ADD PRIMARY KEY (`id`),
  ADD KEY `title` (`title`),
  ADD KEY `url` (`url`);

--
-- 表的索引 `tab_issue_game`
--
ALTER TABLE `tab_issue_game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `game_type_id` (`game_type_id`),
  ADD KEY `game_name` (`game_name`),
  ADD KEY `game_key` (`game_key`(191));

--
-- 表的索引 `tab_issue_game_apply`
--
ALTER TABLE `tab_issue_game_apply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`platform_id`),
  ADD KEY `game_id_2` (`game_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- 表的索引 `tab_issue_game_pack_record`
--
ALTER TABLE `tab_issue_game_pack_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `platform_id` (`platform_id`,`open_user_id`);

--
-- 表的索引 `tab_issue_open_user`
--
ALTER TABLE `tab_issue_open_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`),
  ADD KEY `nickname` (`nickname`);

--
-- 表的索引 `tab_issue_open_user_balance`
--
ALTER TABLE `tab_issue_open_user_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `spend_search` (`user_id`,`pay_time`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `pay_time` (`pay_status`,`pay_time`) USING BTREE,
  ADD KEY `pay_status` (`pay_status`);

--
-- 表的索引 `tab_issue_open_user_login_record`
--
ALTER TABLE `tab_issue_open_user_login_record`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_issue_open_user_platform`
--
ALTER TABLE `tab_issue_open_user_platform`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`),
  ADD KEY `user_id` (`open_user_id`),
  ADD KEY `controller_name` (`controller_name_h5`);

--
-- 表的索引 `tab_issue_spend`
--
ALTER TABLE `tab_issue_spend`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `promote_id` (`platform_id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `spend_search` (`game_id`,`platform_id`,`user_id`,`pay_time`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `pay_time` (`pay_time`,`pay_status`) USING BTREE,
  ADD KEY `pay_status` (`pay_status`) USING BTREE;

--
-- 表的索引 `tab_issue_user`
--
ALTER TABLE `tab_issue_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deposite_search` (`lock_status`),
  ADD KEY `account` (`account`) USING BTREE,
  ADD KEY `openid` (`openid`) USING BTREE,
  ADD KEY `platform_id` (`platform_id`,`open_user_id`) USING BTREE,
  ADD KEY `create_time` (`create_time`) USING BTREE;

--
-- 表的索引 `tab_issue_user_day_login`
--
ALTER TABLE `tab_issue_user_day_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `year,month,day` (`login_year`,`login_month`,`login_day`),
  ADD KEY `game_id,user_id` (`game_id`,`user_id`),
  ADD KEY `login_time` (`login_time`),
  ADD KEY `open_user_id` (`open_user_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- 表的索引 `tab_issue_user_login_record`
--
ALTER TABLE `tab_issue_user_login_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_record` (`game_id`,`login_time`) USING BTREE,
  ADD KEY `login_time` (`login_time`),
  ADD KEY `p_date` (`platform_id`),
  ADD KEY `open_user_id` (`open_user_id`),
  ADD KEY `date_g_p` (`platform_id`,`game_id`,`open_user_id`) USING BTREE;

--
-- 表的索引 `tab_issue_user_play`
--
ALTER TABLE `tab_issue_user_play`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `platform_id` (`platform_id`),
  ADD KEY `user_id_2` (`user_id`,`game_id`,`platform_id`),
  ADD KEY `open_user_id` (`open_user_id`);

--
-- 表的索引 `tab_issue_user_play_role`
--
ALTER TABLE `tab_issue_user_play_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `promote_id` (`platform_id`),
  ADD KEY `search` (`user_id`,`game_id`,`server_id`,`server_name`,`role_id`) USING BTREE;

--
-- 表的索引 `tab_kefuquestion`
--
ALTER TABLE `tab_kefuquestion`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_kefuquestion_type`
--
ALTER TABLE `tab_kefuquestion_type`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_links`
--
ALTER TABLE `tab_links`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_notice`
--
ALTER TABLE `tab_notice`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_oa_studio`
--
ALTER TABLE `tab_oa_studio`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_platform`
--
ALTER TABLE `tab_platform`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote`
--
ALTER TABLE `tab_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`) USING BTREE,
  ADD KEY `parent_id` (`parent_id`);

--
-- 表的索引 `tab_promote_agent`
--
ALTER TABLE `tab_promote_agent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `promote_id` (`promote_id`) USING BTREE;

--
-- 表的索引 `tab_promote_app`
--
ALTER TABLE `tab_promote_app`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`);

--
-- 表的索引 `tab_promote_apply`
--
ALTER TABLE `tab_promote_apply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `apply_search` (`promote_id`,`game_id`) USING BTREE,
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_promote_apply_upload`
--
ALTER TABLE `tab_promote_apply_upload`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`status`),
  ADD KEY `apply_search` (`status`,`promote_apply_id`) USING BTREE,
  ADD KEY `game_id` (`promote_apply_id`);

--
-- 表的索引 `tab_promote_bce_package`
--
ALTER TABLE `tab_promote_bce_package`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_behavior`
--
ALTER TABLE `tab_promote_behavior`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`) USING BTREE;

--
-- 表的索引 `tab_promote_bind`
--
ALTER TABLE `tab_promote_bind`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_search` (`promote_id`,`user_id`,`game_id`,`pay_order_number`,`pay_time`,`pay_way`,`pay_status`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `user_id` (`user_id`,`pay_way`,`game_id`,`pay_time`) USING BTREE;

--
-- 表的索引 `tab_promote_bind_update_record`
--
ALTER TABLE `tab_promote_bind_update_record`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_business`
--
ALTER TABLE `tab_promote_business`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `account` (`account`) USING BTREE;

--
-- 表的索引 `tab_promote_coin`
--
ALTER TABLE `tab_promote_coin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `p_id` (`promote_id`),
  ADD KEY `sid` (`source_id`);

--
-- 表的索引 `tab_promote_config`
--
ALTER TABLE `tab_promote_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_deposit`
--
ALTER TABLE `tab_promote_deposit`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_level`
--
ALTER TABLE `tab_promote_level`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promote_id` (`promote_id`) USING BTREE,
  ADD KEY `search` (`promote_id`) USING BTREE;

--
-- 表的索引 `tab_promote_prepayment_deduct_record`
--
ALTER TABLE `tab_promote_prepayment_deduct_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`) USING BTREE,
  ADD KEY `pay_order_number` (`pay_order_number`);

--
-- 表的索引 `tab_promote_prepayment_recharge`
--
ALTER TABLE `tab_promote_prepayment_recharge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `pay_order_number` (`pay_order_number`) USING BTREE;

--
-- 表的索引 `tab_promote_prepayment_send_record`
--
ALTER TABLE `tab_promote_prepayment_send_record`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_settlement`
--
ALTER TABLE `tab_promote_settlement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `sousuo` (`user_account`,`pay_order_number`,`create_time`,`game_id`,`promote_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `tab_promote_settlement_period`
--
ALTER TABLE `tab_promote_settlement_period`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_num` (`order_num`,`is_remit`) USING BTREE;

--
-- 表的索引 `tab_promote_settlement_time`
--
ALTER TABLE `tab_promote_settlement_time`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`,`promote_account`,`next_count_time`) USING BTREE;

--
-- 表的索引 `tab_promote_union`
--
ALTER TABLE `tab_promote_union`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain_url` (`domain_url`),
  ADD KEY `union_id` (`union_id`);

--
-- 表的索引 `tab_promote_union_article`
--
ALTER TABLE `tab_promote_union_article`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_promote_user_bind_discount`
--
ALTER TABLE `tab_promote_user_bind_discount`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_promote_user_bind_discount_game_user`
--
ALTER TABLE `tab_promote_user_bind_discount_game_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_bind_discount_id` (`user_bind_discount_id`) USING BTREE;

--
-- 表的索引 `tab_promote_user_welfare`
--
ALTER TABLE `tab_promote_user_welfare`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_promote_user_welfare_game_user`
--
ALTER TABLE `tab_promote_user_welfare_game_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_welfare_id` (`user_welfare_id`) USING BTREE;

--
-- 表的索引 `tab_promote_withdraw`
--
ALTER TABLE `tab_promote_withdraw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `dingdanhao` (`widthdraw_number`) USING BTREE,
  ADD KEY `status` (`status`),
  ADD KEY `suoyou` (`widthdraw_number`,`promote_id`,`status`);

--
-- 表的索引 `tab_protocol`
--
ALTER TABLE `tab_protocol`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_safe_center`
--
ALTER TABLE `tab_safe_center`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_seo`
--
ALTER TABLE `tab_seo`
  ADD KEY `name` (`name`);

--
-- 表的索引 `tab_sms_config`
--
ALTER TABLE `tab_sms_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_sms_log`
--
ALTER TABLE `tab_sms_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_spend`
--
ALTER TABLE `tab_spend`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `spend_search` (`game_id`,`promote_id`,`user_id`,`pay_time`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `pay_time` (`pay_status`,`pay_time`) USING BTREE,
  ADD KEY `pay_status` (`pay_status`);

--
-- 表的索引 `tab_spend_balance`
--
ALTER TABLE `tab_spend_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `pay_order_number` (`pay_order_number`) USING BTREE;

--
-- 表的索引 `tab_spend_bind`
--
ALTER TABLE `tab_spend_bind`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_search` (`game_id`,`pay_order_number`,`promote_id`,`user_id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `user_id` (`user_id`,`pay_status`,`pay_time`) USING BTREE;

--
-- 表的索引 `tab_spend_bind_discount`
--
ALTER TABLE `tab_spend_bind_discount`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_spend_bind_discount_game_user`
--
ALTER TABLE `tab_spend_bind_discount_game_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bind_discount_id` (`bind_discount_id`) USING BTREE;

--
-- 表的索引 `tab_spend_bind_discount_promote`
--
ALTER TABLE `tab_spend_bind_discount_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bind_discount_id` (`bind_discount_id`) USING BTREE;

--
-- 表的索引 `tab_spend_distinction`
--
ALTER TABLE `tab_spend_distinction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spend_id` (`spend_id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_spend_payconfig`
--
ALTER TABLE `tab_spend_payconfig`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_spend_promote_coin`
--
ALTER TABLE `tab_spend_promote_coin`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_spend_provide`
--
ALTER TABLE `tab_spend_provide`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provide_search` (`game_id`,`user_account`,`create_time`),
  ADD KEY `user_account` (`user_account`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `create_time` (`create_time`);

--
-- 表的索引 `tab_spend_rebate`
--
ALTER TABLE `tab_spend_rebate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`status`) USING BTREE;

--
-- 表的索引 `tab_spend_rebate_game_user`
--
ALTER TABLE `tab_spend_rebate_game_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rebate_id` (`rebate_id`) USING BTREE;

--
-- 表的索引 `tab_spend_rebate_promote`
--
ALTER TABLE `tab_spend_rebate_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rebate_id` (`rebate_id`) USING BTREE;

--
-- 表的索引 `tab_spend_rebate_record`
--
ALTER TABLE `tab_spend_rebate_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `game_name` (`game_name`),
  ADD KEY `user_name` (`user_account`),
  ADD KEY `user_id` (`user_id`,`create_time`);

--
-- 表的索引 `tab_spend_welfare`
--
ALTER TABLE `tab_spend_welfare`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_spend_welfare_game_user`
--
ALTER TABLE `tab_spend_welfare_game_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `welfare_id` (`welfare_id`) USING BTREE;

--
-- 表的索引 `tab_spend_welfare_promote`
--
ALTER TABLE `tab_spend_welfare_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `welfare_id` (`welfare_id`) USING BTREE;

--
-- 表的索引 `tab_spend_wxparam`
--
ALTER TABLE `tab_spend_wxparam`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_support`
--
ALTER TABLE `tab_support`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_task_trigger`
--
ALTER TABLE `tab_task_trigger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `create_time` (`create_time`);

--
-- 表的索引 `tab_tip`
--
ALTER TABLE `tab_tip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `tab_user`
--
ALTER TABLE `tab_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account` (`account`) USING BTREE,
  ADD KEY `register_time` (`register_time`) USING BTREE,
  ADD KEY `promote_id` (`promote_id`) USING BTREE,
  ADD KEY `fgame_id` (`fgame_id`) USING BTREE,
  ADD KEY `deposite_search` (`promote_id`,`fgame_id`,`lock_status`,`register_way`,`register_time`) USING BTREE,
  ADD KEY `user_id` (`id`),
  ADD KEY `puid` (`puid`) USING BTREE;

--
-- 表的索引 `tab_user_age_record`
--
ALTER TABLE `tab_user_age_record`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_auth_code`
--
ALTER TABLE `tab_user_auth_code`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_award`
--
ALTER TABLE `tab_user_award`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_award_record`
--
ALTER TABLE `tab_user_award_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `search` (`user_account`,`name`,`create_time`);

--
-- 表的索引 `tab_user_balance_edit`
--
ALTER TABLE `tab_user_balance_edit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_user_batch_create_log`
--
ALTER TABLE `tab_user_batch_create_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_behavior`
--
ALTER TABLE `tab_user_behavior`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tab_user_cash_out`
--
ALTER TABLE `tab_user_cash_out`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `提现订单` (`order_no`);

--
-- 表的索引 `tab_user_config`
--
ALTER TABLE `tab_user_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_day_login`
--
ALTER TABLE `tab_user_day_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_time` (`login_time`) USING BTREE,
  ADD KEY `game_id` (`game_id`) USING BTREE,
  ADD KEY `promote_id` (`promote_id`) USING BTREE,
  ADD KEY `seach` (`game_id`,`promote_id`,`user_id`) USING BTREE,
  ADD KEY `seach1` (`user_id`,`login_time`,`game_id`);

--
-- 表的索引 `tab_user_deduct_bind`
--
ALTER TABLE `tab_user_deduct_bind`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`user_id`,`create_time`) USING BTREE;

--
-- 表的索引 `tab_user_feedback`
--
ALTER TABLE `tab_user_feedback`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_game_login`
--
ALTER TABLE `tab_user_game_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `s1` (`time`,`user_id`,`game_id`) USING BTREE,
  ADD KEY `s2` (`user_id`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_user_invitation`
--
ALTER TABLE `tab_user_invitation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`user_account`);

--
-- 表的索引 `tab_user_invitation_record`
--
ALTER TABLE `tab_user_invitation_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`user_account`),
  ADD KEY `user_id` (`user_id`,`type`) USING BTREE;

--
-- 表的索引 `tab_user_item`
--
ALTER TABLE `tab_user_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`) USING BTREE;

--
-- 表的索引 `tab_user_login_record`
--
ALTER TABLE `tab_user_login_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_record` (`game_id`,`user_account`,`login_time`) USING BTREE,
  ADD KEY `login_time` (`login_time`),
  ADD KEY `game_name` (`game_name`),
  ADD KEY `user_id` (`user_id`,`game_id`,`sdk_version`),
  ADD KEY `promote_id` (`promote_id`) USING BTREE,
  ADD KEY `search` (`user_id`,`login_time`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_user_member`
--
ALTER TABLE `tab_user_member`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`user_account`,`create_time`);

--
-- 表的索引 `tab_user_mend`
--
ALTER TABLE `tab_user_mend`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_time` (`user_account`,`create_time`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `promote_id` (`promote_id`);

--
-- 表的索引 `tab_user_param`
--
ALTER TABLE `tab_user_param`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_play`
--
ALTER TABLE `tab_user_play`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user-id` (`user_id`),
  ADD KEY `game-id` (`game_id`),
  ADD KEY `seach` (`game_id`,`promote_id`,`play_time`),
  ADD KEY `promote_id` (`user_id`,`game_id`) USING BTREE;

--
-- 表的索引 `tab_user_play_info`
--
ALTER TABLE `tab_user_play_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `promote_id` (`promote_id`),
  ADD KEY `search` (`user_id`,`game_id`,`server_id`,`server_name`,`role_id`) USING BTREE,
  ADD KEY `puid` (`puid`) USING BTREE;

--
-- 表的索引 `tab_user_play_record`
--
ALTER TABLE `tab_user_play_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user-id` (`user_id`,`game_id`,`server_id`) USING BTREE;

--
-- 表的索引 `tab_user_point_record`
--
ALTER TABLE `tab_user_point_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `search` (`type_id`,`user_account`),
  ADD KEY `search2` (`user_id`,`type_id`);

--
-- 表的索引 `tab_user_point_shop`
--
ALTER TABLE `tab_user_point_shop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `search` (`good_name`,`status`,`type`);

--
-- 表的索引 `tab_user_point_shop_record`
--
ALTER TABLE `tab_user_point_shop_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `sreach` (`user_account`,`good_name`,`create_time`),
  ADD KEY `good_id` (`good_id`),
  ADD KEY `search` (`user_id`,`good_id`);

--
-- 表的索引 `tab_user_point_type`
--
ALTER TABLE `tab_user_point_type`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_point_use`
--
ALTER TABLE `tab_user_point_use`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `search` (`type_id`,`user_account`),
  ADD KEY `search2` (`user_id`,`type_id`),
  ADD KEY `item_id` (`item_id`);

--
-- 表的索引 `tab_user_point_use_type`
--
ALTER TABLE `tab_user_point_use_type`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_stage`
--
ALTER TABLE `tab_user_stage`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_user_third`
--
ALTER TABLE `tab_user_third`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`user_account`) USING BTREE;

--
-- 表的索引 `tab_user_tplay`
--
ALTER TABLE `tab_user_tplay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `search` (`status`,`start_time`,`end_time`);

--
-- 表的索引 `tab_user_tplay_record`
--
ALTER TABLE `tab_user_tplay_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`user_id`,`game_id`,`tplay_id`) USING BTREE,
  ADD KEY `search` (`user_id`,`game_id`,`status`,`start_time`,`end_time`) USING BTREE;

--
-- 表的索引 `tab_user_tplay_withdraw`
--
ALTER TABLE `tab_user_tplay_withdraw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dingdanhao` (`pay_order_number`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `sreach` (`user_account`,`type`);

--
-- 表的索引 `tab_user_transaction`
--
ALTER TABLE `tab_user_transaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `search` (`user_account`,`game_id`,`create_time`,`status`);

--
-- 表的索引 `tab_user_transaction_order`
--
ALTER TABLE `tab_user_transaction_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_order_number` (`pay_order_number`),
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `spend_search` (`pay_order_number`,`game_id`,`pay_time`,`pay_status`) USING BTREE;

--
-- 表的索引 `tab_user_transaction_profit`
--
ALTER TABLE `tab_user_transaction_profit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`user_id`) USING BTREE;

--
-- 表的索引 `tab_user_transaction_tip`
--
ALTER TABLE `tab_user_transaction_tip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction` (`transaction_id`) USING BTREE;

--
-- 表的索引 `tab_user_unsubscribe`
--
ALTER TABLE `tab_user_unsubscribe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `unsubscribe_time` (`unsubscribe_time`);

--
-- 表的索引 `tab_warning`
--
ALTER TABLE `tab_warning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`target`) USING BTREE,
  ADD KEY `pos_id` (`type`) USING BTREE,
  ADD KEY `start_time` (`record_id`) USING BTREE,
  ADD KEY `end_time` (`pay_order_number`) USING BTREE;

--
-- 表的索引 `tab_web_platform`
--
ALTER TABLE `tab_web_platform`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tab_web_platform_user`
--
ALTER TABLE `tab_web_platform_user`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sys_admin_menu`
--
ALTER TABLE `sys_admin_menu`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=545;

--
-- 使用表AUTO_INCREMENT `sys_asset`
--
ALTER TABLE `sys_asset`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_auth_access`
--
ALTER TABLE `sys_auth_access`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_auth_rule`
--
ALTER TABLE `sys_auth_rule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则id,自增主键', AUTO_INCREMENT=537;

--
-- 使用表AUTO_INCREMENT `sys_comment`
--
ALTER TABLE `sys_comment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_hook`
--
ALTER TABLE `sys_hook`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- 使用表AUTO_INCREMENT `sys_hook_plugin`
--
ALTER TABLE `sys_hook_plugin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `sys_nav`
--
ALTER TABLE `sys_nav`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `sys_nav_menu`
--
ALTER TABLE `sys_nav_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sys_option`
--
ALTER TABLE `sys_option`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- 使用表AUTO_INCREMENT `sys_plugin`
--
ALTER TABLE `sys_plugin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增id', AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `sys_portal_category`
--
ALTER TABLE `sys_portal_category`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类id', AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `sys_portal_category_post`
--
ALTER TABLE `sys_portal_category_post`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `sys_portal_post`
--
ALTER TABLE `sys_portal_post`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用表AUTO_INCREMENT `sys_portal_tag`
--
ALTER TABLE `sys_portal_tag`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类id';

--
-- 使用表AUTO_INCREMENT `sys_portal_tag_post`
--
ALTER TABLE `sys_portal_tag_post`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_recycle_bin`
--
ALTER TABLE `sys_recycle_bin`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_role`
--
ALTER TABLE `sys_role`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sys_role_user`
--
ALTER TABLE `sys_role_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `sys_route`
--
ALTER TABLE `sys_route`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '路由id';

--
-- 使用表AUTO_INCREMENT `sys_slide`
--
ALTER TABLE `sys_slide`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_slide_item`
--
ALTER TABLE `sys_slide_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_theme`
--
ALTER TABLE `sys_theme`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sys_theme_file`
--
ALTER TABLE `sys_theme_file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- 使用表AUTO_INCREMENT `sys_third_party_user`
--
ALTER TABLE `sys_third_party_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_user`
--
ALTER TABLE `sys_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sys_user_action`
--
ALTER TABLE `sys_user_action`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_user_action_log`
--
ALTER TABLE `sys_user_action_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_user_favorite`
--
ALTER TABLE `sys_user_favorite`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_user_score_log`
--
ALTER TABLE `sys_user_score_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sys_user_token`
--
ALTER TABLE `sys_user_token`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sys_verification_code`
--
ALTER TABLE `sys_verification_code`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '表id';

--
-- 使用表AUTO_INCREMENT `tab_admin_action_log`
--
ALTER TABLE `tab_admin_action_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `tab_admin_remind_msg`
--
ALTER TABLE `tab_admin_remind_msg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_adv`
--
ALTER TABLE `tab_adv`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- 使用表AUTO_INCREMENT `tab_adv_pos`
--
ALTER TABLE `tab_adv_pos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键', AUTO_INCREMENT=36;

--
-- 使用表AUTO_INCREMENT `tab_alipay_auth`
--
ALTER TABLE `tab_alipay_auth`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号';

--
-- 使用表AUTO_INCREMENT `tab_app`
--
ALTER TABLE `tab_app`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_app_supersign_order`
--
ALTER TABLE `tab_app_supersign_order`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_bt_prop`
--
ALTER TABLE `tab_bt_prop`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare`
--
ALTER TABLE `tab_bt_welfare`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_monthcard`
--
ALTER TABLE `tab_bt_welfare_monthcard`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_promote`
--
ALTER TABLE `tab_bt_welfare_promote`
  MODIFY `id` bigint(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_recharge`
--
ALTER TABLE `tab_bt_welfare_recharge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_register`
--
ALTER TABLE `tab_bt_welfare_register`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_total_recharge`
--
ALTER TABLE `tab_bt_welfare_total_recharge`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_bt_welfare_weekcard`
--
ALTER TABLE `tab_bt_welfare_weekcard`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_coupon`
--
ALTER TABLE `tab_coupon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `tab_coupon_promote`
--
ALTER TABLE `tab_coupon_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_coupon_record`
--
ALTER TABLE `tab_coupon_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `tab_cp_settlement_period`
--
ALTER TABLE `tab_cp_settlement_period`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_datareport_every_pid`
--
ALTER TABLE `tab_datareport_every_pid`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_datareport_top_pid`
--
ALTER TABLE `tab_datareport_top_pid`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_data_change_record`
--
ALTER TABLE `tab_data_change_record`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_equipment_game`
--
ALTER TABLE `tab_equipment_game`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_equipment_login`
--
ALTER TABLE `tab_equipment_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '登录记录表id';

--
-- 使用表AUTO_INCREMENT `tab_game`
--
ALTER TABLE `tab_game`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键自增';

--
-- 使用表AUTO_INCREMENT `tab_game_attr`
--
ALTER TABLE `tab_game_attr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_ban_set`
--
ALTER TABLE `tab_game_ban_set`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '游戏封禁设置表';

--
-- 使用表AUTO_INCREMENT `tab_game_change_name_log`
--
ALTER TABLE `tab_game_change_name_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_collect`
--
ALTER TABLE `tab_game_collect`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_comment`
--
ALTER TABLE `tab_game_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_comment_follow`
--
ALTER TABLE `tab_game_comment_follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_config`
--
ALTER TABLE `tab_game_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_game_cp`
--
ALTER TABLE `tab_game_cp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_game_down_record`
--
ALTER TABLE `tab_game_down_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_game_follow`
--
ALTER TABLE `tab_game_follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_giftbag`
--
ALTER TABLE `tab_game_giftbag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_game_gift_record`
--
ALTER TABLE `tab_game_gift_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_game_interface`
--
ALTER TABLE `tab_game_interface`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '接口ID';

--
-- 使用表AUTO_INCREMENT `tab_game_interflow`
--
ALTER TABLE `tab_game_interflow`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_ios_pay_to_download`
--
ALTER TABLE `tab_game_ios_pay_to_download`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_ios_pay_to_download_order`
--
ALTER TABLE `tab_game_ios_pay_to_download_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_ios_pay_to_download_record`
--
ALTER TABLE `tab_game_ios_pay_to_download_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_server`
--
ALTER TABLE `tab_game_server`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键自增';

--
-- 使用表AUTO_INCREMENT `tab_game_server_notice`
--
ALTER TABLE `tab_game_server_notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键自增';

--
-- 使用表AUTO_INCREMENT `tab_game_share_record`
--
ALTER TABLE `tab_game_share_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_game_source`
--
ALTER TABLE `tab_game_source`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_game_type`
--
ALTER TABLE `tab_game_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_guess`
--
ALTER TABLE `tab_guess`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号';

--
-- 使用表AUTO_INCREMENT `tab_issue_game`
--
ALTER TABLE `tab_issue_game`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_issue_game_apply`
--
ALTER TABLE `tab_issue_game_apply`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_game_pack_record`
--
ALTER TABLE `tab_issue_game_pack_record`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_issue_open_user`
--
ALTER TABLE `tab_issue_open_user`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_issue_open_user_balance`
--
ALTER TABLE `tab_issue_open_user_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_open_user_login_record`
--
ALTER TABLE `tab_issue_open_user_login_record`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_issue_open_user_platform`
--
ALTER TABLE `tab_issue_open_user_platform`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_spend`
--
ALTER TABLE `tab_issue_spend`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_user`
--
ALTER TABLE `tab_issue_user`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键自增';

--
-- 使用表AUTO_INCREMENT `tab_issue_user_day_login`
--
ALTER TABLE `tab_issue_user_day_login`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_issue_user_login_record`
--
ALTER TABLE `tab_issue_user_login_record`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_user_play`
--
ALTER TABLE `tab_issue_user_play`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_issue_user_play_role`
--
ALTER TABLE `tab_issue_user_play_role`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_kefuquestion`
--
ALTER TABLE `tab_kefuquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_kefuquestion_type`
--
ALTER TABLE `tab_kefuquestion_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_links`
--
ALTER TABLE `tab_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_notice`
--
ALTER TABLE `tab_notice`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号';

--
-- 使用表AUTO_INCREMENT `tab_oa_studio`
--
ALTER TABLE `tab_oa_studio`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_platform`
--
ALTER TABLE `tab_platform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote`
--
ALTER TABLE `tab_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_agent`
--
ALTER TABLE `tab_promote_agent`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_app`
--
ALTER TABLE `tab_promote_app`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_promote_apply`
--
ALTER TABLE `tab_promote_apply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_apply_upload`
--
ALTER TABLE `tab_promote_apply_upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_bce_package`
--
ALTER TABLE `tab_promote_bce_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_behavior`
--
ALTER TABLE `tab_promote_behavior`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_bind`
--
ALTER TABLE `tab_promote_bind`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_promote_bind_update_record`
--
ALTER TABLE `tab_promote_bind_update_record`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订单更改绑定表';

--
-- 使用表AUTO_INCREMENT `tab_promote_business`
--
ALTER TABLE `tab_promote_business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_coin`
--
ALTER TABLE `tab_promote_coin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_config`
--
ALTER TABLE `tab_promote_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `tab_promote_deposit`
--
ALTER TABLE `tab_promote_deposit`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_level`
--
ALTER TABLE `tab_promote_level`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_prepayment_deduct_record`
--
ALTER TABLE `tab_promote_prepayment_deduct_record`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_prepayment_recharge`
--
ALTER TABLE `tab_promote_prepayment_recharge`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `tab_promote_prepayment_send_record`
--
ALTER TABLE `tab_promote_prepayment_send_record`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_settlement`
--
ALTER TABLE `tab_promote_settlement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_settlement_period`
--
ALTER TABLE `tab_promote_settlement_period`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_settlement_time`
--
ALTER TABLE `tab_promote_settlement_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_union`
--
ALTER TABLE `tab_promote_union`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_promote_union_article`
--
ALTER TABLE `tab_promote_union_article`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `tab_promote_user_bind_discount`
--
ALTER TABLE `tab_promote_user_bind_discount`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_user_bind_discount_game_user`
--
ALTER TABLE `tab_promote_user_bind_discount_game_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_user_welfare`
--
ALTER TABLE `tab_promote_user_welfare`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_user_welfare_game_user`
--
ALTER TABLE `tab_promote_user_welfare_game_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_promote_withdraw`
--
ALTER TABLE `tab_promote_withdraw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_protocol`
--
ALTER TABLE `tab_protocol`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号', AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `tab_safe_center`
--
ALTER TABLE `tab_safe_center`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `tab_sms_config`
--
ALTER TABLE `tab_sms_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tab_sms_log`
--
ALTER TABLE `tab_sms_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- 使用表AUTO_INCREMENT `tab_spend`
--
ALTER TABLE `tab_spend`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_spend_balance`
--
ALTER TABLE `tab_spend_balance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `tab_spend_bind`
--
ALTER TABLE `tab_spend_bind`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_spend_bind_discount`
--
ALTER TABLE `tab_spend_bind_discount`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '绑币充值折扣表';

--
-- 使用表AUTO_INCREMENT `tab_spend_bind_discount_game_user`
--
ALTER TABLE `tab_spend_bind_discount_game_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '绑币充值部分玩家关联表';

--
-- 使用表AUTO_INCREMENT `tab_spend_bind_discount_promote`
--
ALTER TABLE `tab_spend_bind_discount_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '绑币充值部分渠道关联表';

--
-- 使用表AUTO_INCREMENT `tab_spend_distinction`
--
ALTER TABLE `tab_spend_distinction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_payconfig`
--
ALTER TABLE `tab_spend_payconfig`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `tab_spend_promote_coin`
--
ALTER TABLE `tab_spend_promote_coin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_provide`
--
ALTER TABLE `tab_spend_provide`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_spend_rebate`
--
ALTER TABLE `tab_spend_rebate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_rebate_game_user`
--
ALTER TABLE `tab_spend_rebate_game_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `tab_spend_rebate_promote`
--
ALTER TABLE `tab_spend_rebate_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_rebate_record`
--
ALTER TABLE `tab_spend_rebate_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_welfare`
--
ALTER TABLE `tab_spend_welfare`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_welfare_game_user`
--
ALTER TABLE `tab_spend_welfare_game_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `tab_spend_welfare_promote`
--
ALTER TABLE `tab_spend_welfare_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_spend_wxparam`
--
ALTER TABLE `tab_spend_wxparam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_support`
--
ALTER TABLE `tab_support`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_task_trigger`
--
ALTER TABLE `tab_task_trigger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_tip`
--
ALTER TABLE `tab_tip`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号';

--
-- 使用表AUTO_INCREMENT `tab_user`
--
ALTER TABLE `tab_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键自增';

--
-- 使用表AUTO_INCREMENT `tab_user_age_record`
--
ALTER TABLE `tab_user_age_record`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键自增', AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `tab_user_auth_code`
--
ALTER TABLE `tab_user_auth_code`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- 使用表AUTO_INCREMENT `tab_user_award`
--
ALTER TABLE `tab_user_award`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `tab_user_award_record`
--
ALTER TABLE `tab_user_award_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_balance_edit`
--
ALTER TABLE `tab_user_balance_edit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_batch_create_log`
--
ALTER TABLE `tab_user_batch_create_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- 使用表AUTO_INCREMENT `tab_user_behavior`
--
ALTER TABLE `tab_user_behavior`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_cash_out`
--
ALTER TABLE `tab_user_cash_out`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '提现记录表';

--
-- 使用表AUTO_INCREMENT `tab_user_config`
--
ALTER TABLE `tab_user_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `tab_user_day_login`
--
ALTER TABLE `tab_user_day_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '登录记录表id';

--
-- 使用表AUTO_INCREMENT `tab_user_deduct_bind`
--
ALTER TABLE `tab_user_deduct_bind`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id';

--
-- 使用表AUTO_INCREMENT `tab_user_feedback`
--
ALTER TABLE `tab_user_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_game_login`
--
ALTER TABLE `tab_user_game_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '登录记录表id';

--
-- 使用表AUTO_INCREMENT `tab_user_invitation`
--
ALTER TABLE `tab_user_invitation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_invitation_record`
--
ALTER TABLE `tab_user_invitation_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_item`
--
ALTER TABLE `tab_user_item`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- 使用表AUTO_INCREMENT `tab_user_login_record`
--
ALTER TABLE `tab_user_login_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_member`
--
ALTER TABLE `tab_user_member`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_mend`
--
ALTER TABLE `tab_user_mend`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_param`
--
ALTER TABLE `tab_user_param`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_play`
--
ALTER TABLE `tab_user_play`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_play_info`
--
ALTER TABLE `tab_user_play_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_play_record`
--
ALTER TABLE `tab_user_play_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_point_record`
--
ALTER TABLE `tab_user_point_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- 使用表AUTO_INCREMENT `tab_user_point_shop`
--
ALTER TABLE `tab_user_point_shop`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `tab_user_point_shop_record`
--
ALTER TABLE `tab_user_point_shop_record`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `tab_user_point_type`
--
ALTER TABLE `tab_user_point_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 使用表AUTO_INCREMENT `tab_user_point_use`
--
ALTER TABLE `tab_user_point_use`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `tab_user_point_use_type`
--
ALTER TABLE `tab_user_point_use_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `tab_user_stage`
--
ALTER TABLE `tab_user_stage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_third`
--
ALTER TABLE `tab_user_third`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_tplay`
--
ALTER TABLE `tab_user_tplay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_tplay_record`
--
ALTER TABLE `tab_user_tplay_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_user_tplay_withdraw`
--
ALTER TABLE `tab_user_tplay_withdraw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_transaction`
--
ALTER TABLE `tab_user_transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_transaction_order`
--
ALTER TABLE `tab_user_transaction_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_transaction_profit`
--
ALTER TABLE `tab_user_transaction_profit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id';

--
-- 使用表AUTO_INCREMENT `tab_user_transaction_tip`
--
ALTER TABLE `tab_user_transaction_tip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键';

--
-- 使用表AUTO_INCREMENT `tab_user_unsubscribe`
--
ALTER TABLE `tab_user_unsubscribe`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_warning`
--
ALTER TABLE `tab_warning`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- 使用表AUTO_INCREMENT `tab_web_platform`
--
ALTER TABLE `tab_web_platform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tab_web_platform_user`
--
ALTER TABLE `tab_web_platform_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
