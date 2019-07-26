<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
require ('start.php');

// 路由解析
//$sel = $_SERVER['PHP_SELF'];
$sel = $_SERVER['REQUEST_URI'];
//获取模块参数
//$url_str = substr($sel,strripos($sel,"index.php")+10);
$url_str = str_replace('/index.php','',$sel);
//校验模块参数
if (!$url_str){
    echo '未知入口';die;
}
if ($sel=='/'){
    echo "<script>window.location.href='/home/index/list';</script>";exit();
}

$url_arr = explode('/',$url_str);

//允许访问模块
$allow_entrance = ['admin','home'];
//校验访问文件和方法
if (count($url_arr)<3){
    if (count($url_arr)<2){
        echo '缺失控制器';die;
    }
    echo '缺失方法';die;
}
if (!in_array($url_arr[1],$allow_entrance)){
    echo '未知模块入口';die;
}
//组装模块-类文件-方法
$my_module = explode('.',$url_arr[1])[0];
$my_controller = explode('.',$url_arr[2])[0];
//去除参数
$my_func = explode('.',explode('?',$url_arr[3])[0])[0];
//后台权限验证
if ($my_module == 'admin') {
    $res = accessAuth();
    $res = json_decode($res, true);
    if ($res['code'] != 200) {
        if ($res['data'] != '请登录') {
            echo "<script>alert('{$res['data']}');</script>";
            die;
        } else {
            echo "<script>window.location.href='/index.php/admin/login/index';</script>";exit();
        }
    }
}
//拼接文件目录
$goPath = strtolower($my_module).'/'.ucwords($my_controller).'.class.php';
//控制器文件判断
if (!is_file($goPath)){
    echo '未知入口1';return;
}
//引入控制器
require ($goPath);
$aclass = new $my_controller();
//类方法验证
if (!method_exists($aclass,$my_func)){
    echo '错误的访问1';return;
}
//var_dump($aclass->$my_func());die;
//渲染页面
$aclass->$my_func();

//var_dump($module);
//var_dump($controller);
//var_dump($func);
//var_dump(strtoupper($module));