<?php
/**
 * Created by PhpStorm.
 * User: lulu
 * Date: 2019/6/24
 * Time: 15:44
 */
ini_set('date.timezone','Asia/Shanghai');
// 定义应用目录
defined('DS') or define('DS',DIRECTORY_SEPARATOR);
defined('ADMIN') or define('ADMIN', '/admin'); //C:\Lulu/admin
defined('HOME') or define('HOME', '/home'); //C:\Lulu/home
defined('LIB') or define('LIB', __DIR__); //C:\Lulu\framework\lib
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(dirname(__DIR__)))); //C:\Lulu
defined('EXTEND') or define('EXTEND', '/extends'); //C:\Lulu/extends
defined('VIEW') or define('VIEW', ROOT_PATH . '/view'); //C:\Lulu/view
defined('__UPLOADS__') or define('__UPLOADS__','/public/uploads'); //C:\Lulu/public/uploads
defined('__AIMAGES__') or define('__AIMAGES__','/public/admin/images');
defined('__AJS__') or define('__AJS__','/public/admin/js');//C:\Lulu/public/home/js
defined('__ACSS__') or define('__ACSS__','/public/admin/css');
defined('__ALIB__') or define('__ALIB__','/public/admin/lib');
defined('__AFONTS__') or define('__AFONTS__','/public/admin/fonts');
defined('__IMAGES__') or define('__IMAGES__','/public/home/images');
defined('__JS__') or define('__JS__','/public/home/js');
defined('__CSS__') or define('__CSS__','/public/home/css');
defined('__FONTS__') or define('__FONTS__','/public/home/fonts');

//
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
//
//die;


require_once(LIB.'/mysql.php');
require_once(LIB.'/func.php');
$_GET=_addslashes($_GET);
$_POST=_addslashes($_POST);
$_COOKIE=_addslashes($_COOKIE);

//var_dump($_SERVER['PHP_SELF']);
//echo '<br>';
//$a = explode('/',$_SERVER['PHP_SELF']);
//$func = $a[count($a)-1];
//var_dump($a);
//echo '<br>';
//var_dump($func);die;
//echo '<pre>';
//var_dump(getIp());
//var_dump(cPages(10,3));die;
//$sql = new Msql();
//$res = $sql::mGetRow('select * from category');
//$res1 = $sql::mGetAll('select * from category');
//echo '<pre>';
//var_dump($res);
//var_dump($res1);