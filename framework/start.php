<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */

//设置默认时区
ini_set('date.timezone','Asia/Shanghai');
// 定义全局目录
defined('DS') or define('DS',DIRECTORY_SEPARATOR);
defined('ROOT_PATH') or define('ROOT_PATH', realpath(dirname(__DIR__)));
defined('ADMIN') or define('ADMIN', ROOT_PATH.'/admin');
defined('HOME') or define('HOME', ROOT_PATH.'/home');
defined('LIB') or define('LIB', __DIR__.'/lib');
defined('EXTEND') or define('EXTEND', ROOT_PATH.'/extends');
defined('VIEW') or define('VIEW', ROOT_PATH . '/view');
defined('__UPLOADS__') or define('__UPLOADS__','/public/uploads');
defined('__AIMAGES__') or define('__AIMAGES__','/public/admin/images');
defined('__AJS__') or define('__AJS__','/public/admin/js');
defined('__ACSS__') or define('__ACSS__','/public/admin/css');
defined('__ALIB__') or define('__ALIB__','/public/admin/lib');
defined('__AFONTS__') or define('__AFONTS__','/public/admin/fonts');
defined('__IMAGES__') or define('__IMAGES__','/public/home/images');
defined('__JS__') or define('__JS__','/public/home/js');
defined('__CSS__') or define('__CSS__','/public/home/css');
defined('__FONTS__') or define('__FONTS__','/public/home/fonts');
//引入sql类库和公用方法
require_once(LIB.'/mysql.php');
require_once(LIB.'/config.php');
require_once(LIB.'/func.php');
//初步过滤提交数据
$_GET=_addslashes($_GET);
$_POST=_addslashes($_POST);
$_COOKIE=_addslashes($_COOKIE);

//设置默认分页大小
defined('PAGE_SIZE') or define('PAGE_SIZE',$cfg['page_size']);


//echo ADMIN.'<br>';
//echo HOME.'<br>';
//echo LIB.'<br>';
//echo ROOT_PATH.'<br>';
//echo EXTEND.'<br>';
//echo __UPLOADS__.'<br>';
//echo __AIMAGES__.'<br>';
//echo __AJS__.'<br>';
//echo __ACSS__.'<br>';
//echo __ALIB__.'<br>';
//echo __AFONTS__.'<br>';
//echo __IMAGES__.'<br>';
//echo __JS__.'<br>';
//echo __CSS__.'<br>';
//echo __FONTS__.'<br>';
//die;

