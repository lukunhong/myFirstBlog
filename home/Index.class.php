<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class Index{
    //基于单例的实例化
    static $db = null;

    public function __construct()
    {
        if (session_status() !==PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * 首页
     */
    public function list()
    {
        if (empty($db)){
            $db = new Msql();
        }
        //是否登录
        if (accHomeLogin()) {
            $flag = $_SESSION['home_name'];
        }else{
            $flag = '请登录';
        }
        //获取栏目
        $sql = "SELECT id,cat_name FROM category WHERE status=1 ORDER BY sort ASC";
        $cat_res = $db::mGetAll($sql);
        //获取最近文章
        $sql = "SELECT c.cat_name,c.id AS cat_id,a.id,a.title,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time,a.content
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.is_del=0 ORDER BY a.add_time DESC LIMIT 0,3";
        $article_res = $db::mGetAll($sql);
        foreach($article_res as &$v){
            $v['content'] = uEditorToText($v['content']);
        }
        unset($v);
        include_once (VIEW.'/home/index.html');
    }

    /**
     * 栏目列表
     */
    public function category()
    {
        if (isset($_GET['cat_id'])){
            if (empty($db)){
                $db = new Msql();
            }
            //是否登录
            if (accHomeLogin()) {
                $flag = $_SESSION['home_name'];
            }else{
                $flag = '请登录';
            }
            if (!is_numeric($_GET['cat_id'])){
                echo 'id不合法';die;
            }
            //当前栏目id
            $cat_id = (integer)$_GET['cat_id'];
            //获取栏目
            $sql = "SELECT id,cat_name FROM category WHERE status=1 ORDER BY sort ASC";
            $cat_res = $db::mGetAll($sql);
            //获取最近文章
            $sql = "SELECT c.cat_name,c.id AS cat_id,a.id,a.title,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time,a.content
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.is_del=0 ORDER BY a.add_time DESC LIMIT 0,3";
            $article_res = $db::mGetAll($sql);
            foreach($article_res as &$v){
                $v['content'] = uEditorToText($v['content']);
            }
            unset($v);
            //分页类
            $csql = "SELECT COUNT(*) FROM article WHERE cat_id={$cat_id} AND is_del=0";
            $cres = $db::mGetRow($csql);
            $num = $cres?$cres[0]:0;
            $page_size = 5;
            if (isset($_GET['page'])){
                if (is_numeric($_GET['page'])){
                    $page = (integer)$_GET['page'];
                }else{
                    echo rDatas(false,'参数不合法');exit();
                }
            }else{
                $page = 1;
            }
            $pages = cPages($num,$page_size,$page);
            $page = $page>$pages[1]?$pages[1]:$page;

            //获取栏目文章
            $sql = "SELECT c.cat_name,c.id AS cat_id,a.id,a.title,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time,a.content 
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.cat_id={$cat_id} AND a.is_del=0 LIMIT ".($page-1)*$page_size.",{$page_size}";
            $art_list = $db::mGetAll($sql);
            foreach($art_list as &$v){
                $v['content'] = uEditorToText($v['content']);
            }
            unset($v);
            include_once (VIEW.'/home/category.html');
        }else{
            include_once (VIEW.'/home/404.html');
        }
    }

    /**
     * 文章详情
     */
    public function article()
    {
        if (isset($_GET['art_id'])){
            if (empty($db)){
                $db = new Msql();
            }
            //获取栏目
            $sql = "SELECT id,cat_name FROM category WHERE status=1 ORDER BY sort ASC";
            $cat_res = $db::mGetAll($sql);
            //是否登录
            if (accHomeLogin()) {
                $flag = $_SESSION['home_name'];
            }else{
                $flag = '请登录';
            }
            if (!is_numeric($_GET['art_id'])){
                echo 'id不合法';die;
            }
            //当前文章id
            $art_id = $_GET['art_id'];
            //相关文章
            $sql = "SELECT c.cat_name,a.id,a.title,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time,a.content
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.cat_id=(SELECT cat_id FROM article WHERE id={$art_id}) 
ORDER BY a.add_time DESC LIMIT 0,3";
            $article_res = $db::mGetAll($sql);
            foreach($article_res as &$v){
                $v['content'] = uEditorToText($v['content']);
            }
            unset($v);
            //文章详情
            $sql = "SELECT c.cat_name,a.id,a.title,a.content,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time 
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.id={$art_id} ORDER BY a.add_time DESC LIMIT 0,5";
            $art_info = $db::mGetRow($sql);
            //评论列表
            $sql = "SELECT nick,content,ip_address,DATE_FORMAT(FROM_UNIXTIME(pub_time),'%Y-%m-%d %H:%i:%s') as pub_time FROM comment WHERE art_id={$art_id} ORDER BY pub_time DESC";
            $comm_info = $db::mGetAll($sql);
            //置换表情
            foreach($comm_info as &$v){
                $v['content'] = decodeEmoji($v['content']);
            }
            unset($v);
            include_once (VIEW.'/home/article.html');
        }else{
            include_once (VIEW.'/home/404.html');
        }
    }

    /**
     * ajax无刷新获取评论
     */
    public function getComm(){
        if (isset($_POST['art_id'])){
            //评论列表
            if (empty($db)){
                $db = new Msql();
            }
            $art_id = (integer)$_POST['art_id'];
            $sql = "SELECT nick,content,ip_address,DATE_FORMAT(FROM_UNIXTIME(pub_time),'%Y-%m-%d %H:%i:%s') as pub_time FROM comment WHERE art_id={$art_id} ORDER BY pub_time DESC";
            $comm_info = $db::mGetAll($sql);
            //置换表情
            foreach($comm_info as &$v){
                $v['content'] = decodeEmoji($v['content']);
            }
            unset($v);
            echo rDatas(true,$comm_info);exit();
        }else{
            echo rDatas(false,'请求异常');exit();
        }
    }

    /**
     * ajax搜索
     */
    public function search()
    {
        if (isset($_POST['keyword'])){
            if (empty($db)){
                $db = new Msql();
            }
            $title = trim($_POST['keyword']);
            if (empty($title)){
                echo rDatas(false,'请输入搜索内容');exit();
            }
            if (!empty($title)) {
                $sql = "SELECT c.cat_name,a.id,a.title,a.pic,a.comm,DATE_FORMAT(FROM_UNIXTIME(a.add_time),'%Y-%m-%d %H:%i:%s') as pub_time,a.content
FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.title like '%{$title}%' ORDER BY a.add_time DESC LIMIT 0,5";
                $seaech_res = $db::mGetAll($sql);
                foreach ($seaech_res as &$v) {
                    $v['content'] = uEditorToText($v['content']);
                }
                unset($v);
            }else{
                $seaech_res = [];
            }
            echo rDatas(true,$seaech_res);exit();
        }else{
            echo rDatas(false,'非法访问');exit();
        }
    }

    /**
     * 登录
     */
    public function login()
    {
        if (!empty($_POST)){
            if (empty($db)){
                $db = new Msql();
            }
            if (session_status() !==PHP_SESSION_ACTIVE) {
                session_start();
            }
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $verify_code = trim($_POST['verify_code']);
            if (isset($_COOKIE['home_verify_code'])) {
                if (strtolower($verify_code) != $_COOKIE['home_verify_code']) {
                    echo rDatas(false, '验证码错误');exit();
                }
            }else{
                echo rDatas(false, '验证码已过期');exit();
            }
            $sql = "SELECT * FROM home_user WHERE account='{$username}'";
            $res = $db::mGetRow($sql);
            if ($res!=false){
                $cfg=require(LIB.'/config.php');
                $salt=$cfg['salt'];
                $password .= $salt;
                if (dPassword($password,$res['password'])){
                    if ($res['status'] == 1) {
                        $_SESSION['home_name'] = $res['account'];
                        $_SESSION['home_uid'] = $res['id'];
                        $_SESSION['home_ucode'] = cPassword($res['id']);
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
            echo rDatas(false,'异常访问');exit();
        }
    }

    /**
     * 退出登录（预留）
     * lulu.com/index.php/home/index/loginOut
     */
    public function loginOut()
    {
        unset($_SESSION['home_name']);
        unset($_SESSION['home_uid']);
        unset($_SESSION['home_ucode']);
        header('location:/index.php/home/index/list');
    }

    /**
     * 找回密码
     */
    public function resetPsd()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            if (!empty($_POST['verify_code'])){
                $userCode = strtolower($_POST['verify_code']);
                if (!isset($_COOKIE['home_email_verify_code'])){
                    echo rDatas(false,'验证码已过期');exit();
                }else{
                    $verify_arr = array();
                    parse_str($_COOKIE['home_email_verify_code'],$verify_arr);
                    $email = $verify_arr['email'];
                    $code = $verify_arr['code'];
                }
                //邮箱验证码判断
                if ($userCode != $code) {
                    echo rDatas(false,'验证码错误');exit();
                }
                setcookie('home_email_verify_code',null,0);
                $data = array();
                $data['password'] = cPassword($_POST['password']);
                $data['ip'] = getIp();
                $data['update_time'] = time();
                $where = "email='{$email}'";
                if ($db::mExec('home_user',$data,'update',$where)){
                    echo rDatas(true, '修改成功');exit();
                }else{
                    echo rDatas(false,'修改失败');exit();
                }
            }else{
                echo rDatas(false,'请输入邮箱验证码');exit();
            }
        }else{
            //获取栏目
            $sql = "SELECT id,cat_name FROM category WHERE status=1 ORDER BY sort ASC";
            $cat_res = $db::mGetAll($sql);
            include_once (VIEW.'/home/reset_psd.html');
        }
    }

    /**
     * 注册
     */
    public function registerNew()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            if (!empty($_POST['verify_code'])){
                $userCode = strtolower($_POST['verify_code']);
                $phone = trim($_POST['phone']);
                if (!isset($_SESSION['home_vphone']) || !isset($_SESSION['home_vcode'])){
                    echo rDatas(false,'请发送验证码进行验证');exit();
                }
                //短信验证码判断
                if ($_SESSION['home_vcode']['expire'] < time()) {
                    unset($_SESSION['home_vcode']);
                    unset($_SESSION['home_vphone']);
                    echo rDatas(false,'验证码已过期');exit();
                }else{
                    if ($userCode != $_SESSION['home_vcode']['data'] || $phone != $_SESSION['home_vphone']['data']) {
                        echo rDatas(false,'验证码错误');exit();
                    }
                }
                $data = array();
                $data['account'] = htmlspecialchars(trim($_POST['account']));
                $data['nick'] = htmlspecialchars(trim($_POST['nick']));
                if (mb_strlen($data['account'], 'UTF8')>45){
                    echo rDatas(false,'账号过长,请重输');exit();
                }
                if (mb_strlen($data['nick'], 'UTF8')>45){
                    echo rDatas(false,'昵称过长,请重输');exit();
                }
                $esql = "SELECT id FROM home_user WHERE account='{$data['account']}'";
                if ($db::mGetRow($esql)){
                    echo rDatas(false,'该账号已存在');exit();
                }
                $esql = "SELECT id FROM home_user WHERE nick='{$data['nick']}'";
                if ($db::mGetRow($esql)){
                    echo rDatas(false,'该昵称已存在');exit();
                }
                $data['email'] = trim($_POST['email']);
                $where = array();
                $where['email'] = $data['email'];
                //预处理查询
                $emailRes = $db::mSelect('home_user',$where);
                if ($emailRes){
                    echo rDatas(false,'该邮箱已经注册!');exit();
                }
                $data['phone'] = $phone;
                $data['password'] = cPassword($_POST['password']);
                $data['ip'] = getIp();
                $data['add_time'] = time();
                $data['login_time'] = time();
                $data['update_time'] = time();
                //预处理插入
                if ($db::mInsert('home_user',$data)){
                    echo rDatas(true, '注册成功');exit();
                }else{
                    echo rDatas(false,'注册失败');exit();
                }
            }else{
                echo rDatas(false,'请输入短信验证码');exit();
            }
        }else{
            //获取栏目
            $sql = "SELECT id,cat_name FROM category WHERE status=1 ORDER BY sort ASC";
            $cat_res = $db::mGetAll($sql);
            include_once (VIEW.'/home/register.html');
        }
    }

    /**
     * 获取验证码
     */
    public function vCode()
    {
        $code = randStr(4,false);
        //大小写不铭感
        $code = strtolower($code);
        //存验证码
        setcookie('home_verify_code', $code, time() + 1800, '/');
        verifyCode($code);
    }

    /**
     * 获取短信验证码
     */
    public function getVerifyCode()
    {
        if (isset($_POST['phone'])){
            $phone = trim($_POST['phone']);
        }else{
            echo rDatas(false,'非法请求');exit();
        }
        if (empty($db)){
            $db = new Msql();
        }
        $where = array();
        $where['phone'] = $phone;
        //预处理查询
        $emailRes = $db::mSelect('home_user',$where);
        if ($emailRes){
            echo rDatas(false,'该手机号已经注册！');exit();
        }
        $code = (string)mt_rand(1000, 9999);
        require_once ROOT_PATH . '/api/sendMsg.class.php';
        $sendObj = new sendMsg();
        $res = $sendObj->sendSms($phone,$code);
        if ($res['code'] == 200){
            $session_data = array();
            $session_data['data'] = $phone;
            $session_data['expire'] = time() + 120;
            $_SESSION['home_vphone'] = $session_data;
            $session_data['data'] = $code;
            $_SESSION['home_vcode'] = $session_data;
            write_log('注册短信验证码',json_encode($_SESSION['home_vphone']));
            echo rDatas(true,'发送成功');exit();
        }
        echo rDatas(false,'发送失败:'.$res['data']);exit();
    }

    /**
    /*发送邮件验证码
     */
    public function getMailCode()
    {
        if (isset($_POST['email'])) {
            $code = randStr(4,false);
            //大小写不铭感
            $code = strtolower($code);
            // 发送邮件
            $email = $_POST['email'];
            if (empty($db)){
                $db = new Msql();
            }
            $where = array();
            $where['email'] = $email;
            //预处理查询
            $emailRes = $db::mSelect('home_user',$where);
            if (!$emailRes){
                echo rDatas(false,'邮箱不存在!');exit();
            }
            $subject = '该验证码仅用于密码的找回';
            $message = "您的验证码为：{$code}，该验证码 2 分钟内有效，若非本人操作，请勿泄漏于他人.(From:lulu)";
            require_once ROOT_PATH . '/api/sendMail.class.php';
            $sendObj = new sendMail();
            $ret = $sendObj->send($email,$subject,$message);
            if($ret != 'success') {
                echo rDatas(false,'发送失败: '.$ret);exit();
            } else {
                $data = array();
                $data['code'] = $code;
                $data['email'] = $email;
                setcookie('home_email_verify_code', http_build_query($data), time() + 120, '/');
                echo rDatas(true,array('data'=>$emailRes[0]['account'],'msg'=>'发送成功'));exit();
            }
        } else {
            echo rDatas(false,'非法请求');exit();
        }
    }

    /**
     * 发布评论
     */
    public function sendComm()
    {
        if (!empty($_POST)){
            if (empty($db)){
                $db = new Msql();
            }
            if (session_status() !==PHP_SESSION_ACTIVE) {
                session_start();
            }
            $data = array();
            $data['content'] = htmlspecialchars(trim($_POST['content']));
            if (mb_strlen($data['content'])>240){
                echo rDatas(false,'评论内容过长');exit();
            }
            $data['art_id'] = (integer)$_POST['art_id'];
            $data['user_id'] = $_SESSION['home_uid'];//id
            //一分钟之内不能重复评论
            $sql = "SELECT MAX(pub_time) AS pub_time FROM comment WHERE user_id='{$data['user_id']}'";
            $res = $db::mGetRow($sql);
            if ($res!=false){
                if (time()-$res['pub_time']<60){
                    echo rDatas(false,'一分钟之内不能重复评论');exit();
                }
            }
            $data['ip'] = getIp();
            $data['ip_address'] = getIpAddress();
            $data['pub_time'] = time();
            $sql = "SELECT nick FROM home_user WHERE id='{$data['user_id']}'";
            $res = $db::mGetRow($sql);
            if ($res!=false){
                $data['nick'] = $res['nick'];
                if ($db::mExec('comment',$data)){
                    echo rDatas(true,'评论成功');exit();
                }else{
                    echo rDatas(false,'评论失败');exit();
                }
            }else{
                unset($_SESSION['home_name']);
                unset($_SESSION['home_uid']);
                unset($_SESSION['home_ucode']);
                echo rDatas(false,'登录信息出错，请刷新重新登录');exit();
            }
        }else{
            echo rDatas(false,'异常访问');exit();
        }
    }

    /**
     * IE显示升级
     */
    public static function ieBrowser()
    {
        include_once (VIEW.'/home/upgrade-browser.html');
    }

}