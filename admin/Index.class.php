<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class Index{
    //基于单例的实例化
    static $db = null;

    /**
     * 首页
     */
    public function list()
    {
        if (empty($_POST)) {
            include_once(VIEW.'/admin/index.html');
        }else{
            include_once(VIEW.'/admin/error.html');
        }
    }

    /**
     *首页数据
     */
    public function welcome()
    {
        $host = $_SERVER['HTTP_HOST'];;
        $s = php_uname('s');//获取系统类型
        $sysos = $_SERVER["SERVER_SOFTWARE"];//获取php版本及运行环境
        $phpinfo = PHP_VERSION;//获取PHP信息
        include_once VIEW.'/admin/welcome.html';
    }


    public function info()
    {
        echo phpinfo();
    }

    public function test()
    {
        echo '<pre>';
        var_dump($_COOKIE);
        if (session_status() !==PHP_SESSION_ACTIVE) {
            session_start();
        }
        var_dump($_SESSION);
    }
}