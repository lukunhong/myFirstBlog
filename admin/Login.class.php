<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class Login{
    public function index(){
        if (!empty($_POST)){
            if (empty($db)){
                $db = new Msql();
            }
            if (session_status() !==PHP_SESSION_ACTIVE) {
                session_start();
            }
//            //设置session过期时间,结合accLogin的$lifetime=60;session_set_cookie_params($lifetime);使用
//            session_regenerate_id(true);
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $verify_code = trim($_POST['verify_code']);
            if (isset($_COOKIE['verify_code'])) {
                if (strtolower($verify_code) != $_COOKIE['verify_code']) {
                    echo rDatas(false, '验证码错误');exit();
                }
            }else{
                echo rDatas(false, '验证码已过期');exit();
            }
            $sql = "SELECT * FROM admin_user WHERE account='{$username}'";
            $res = $db::mGetRow($sql);
            if ($res!=false){
                $cfg=require_once(LIB.'/config.php');
                $salt=$cfg['salt'];
                $password .= $salt;
                if (dPassword($password,$res['password'])){
                    if ($res['status'] == 1) {
//                        setcookie('uid', $res['id'], time() + 600, '/');
//                        setcookie('ucode', cPassword($res['id']), time() + 600, '/');

                        $_SESSION['admin_name'] = $res['account'];
                        $_SESSION['ses_id'] = session_id();
                        $_SESSION['admin_uid'] = $res['id'];
                        $_SESSION['admin_ucode'] = cPassword($res['id']);
                        echo rDatas(true,'登录成功');exit();
                    }else{
                        echo rDatas(false,'该账户已停用');exit();
                    }
                }else{
                    echo rDatas(false,'密码错误');exit();
                }
            }else{
                echo rDatas(false,'账号不存在');exit();
            }
        }else{
            include_once (VIEW.'/admin/login.html');
        }
    }


    /**
     * 退出登录
     */
    public function loginOut(){
//        setcookie('uid',null,0);
        unset($_SESSION['admin_name']);
        unset($_SESSION['ses_id']);
        unset($_SESSION['admin_uid']);
        unset($_SESSION['admin_ucode']);
        header('location:/index.php/admin/login/index');
    }

    /**
     * 获取验证码
     */
    public function vCode(){
        $code = randStr(4,false);
        //大小写不铭感
        $code = strtolower($code);
        //存验证码
        setcookie('verify_code', $code, time() + 1800, '/');
        verifyCode($code);
    }
}