<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class AdminUser{

    public function __construct()
    {
        if (session_status() !==PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * 管理员列表
     */
    public function adminList()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $sql = "SELECT u.id,u.account,u.phone,u.email,u.status,FROM_UNIXTIME(u.add_time) as add_time,r.title FROM admin_user AS u LEFT JOIN admin_user_role ON u.id=u_id LEFT JOIN admin_role AS r ON r.id=r_id LIMIT ".($_POST['page']-1)*$_POST['limit'].",{$_POST['limit']}";
            $res = $db::mGetAll($sql);
            $csql = 'SELECT COUNT(*) FROM admin_user';
            $cres = $db::mGetRow($csql);
            $num = $cres?$cres[0]:0;
            $data = array();
            $data['code'] = 0;
            $data['data'] = $res;
            $data['count'] = $num;
            echo json_encode($data);exit();
        }else{
            include_once (VIEW.'/admin/admin_list.html');
        }
    }

    /**
     * 新增管理员
     */
    public function adminAdd()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            if (!empty($_POST['verify_code'])){
                $userCode = $_POST['verify_code'];
                $phone = $_POST['phone'];
                if (!isset($_SESSION['verify_code']) || !isset($_SESSION['verify_phone'])){
                    echo rDatas(false,'请发送验证码进行验证');exit();
                }
                if ($_SESSION['verify_code']['expire'] < time()) {
                    unset($_SESSION['verify_code']);
                    unset($_SESSION['verify_phone']);
                    echo rDatas(false,'验证码已过期');exit();
                }else{
                    if ($userCode != $_SESSION['verify_code']['data'] || $phone != $_SESSION['verify_phone']['data']) {
                        echo rDatas(false,'验证码错误');exit();
                    }
                }
                $r_id = $_POST['r_id'];
                $data = array();
                $data['account'] = htmlspecialchars(trim($_POST['account']));
                $esql = "SELECT id FROM admin_user WHERE account='{$data['account']}'";
                if ($db::mGetRow($esql)){
                    echo rDatas(false,'该账号已存在');exit();
                }
                $data['phone'] = $phone;
                $data['email'] = $_POST['email'];
                $data['password'] = cPassword($_POST['password']);
                $data['status'] = $_POST['status'];
                $data['add_time'] = time();
                $data['update_time'] = time();
                $pdo = $db::mConnect();
                $pdo->beginTransaction();
                try{
                    if ($db::mExec('admin_user',$data)){
                        $u_id = $pdo->lastInsertId();
                        $rmdata = array();
                        $rmdata['u_id'] = $u_id;
                        $rmdata['r_id'] = $r_id;
                        if ($db::mExec('admin_user_role',$rmdata)){
                            $pdo->commit();
                            echo rDatas(true, '添加成功');
                            exit();
                        }else{
                            $pdo->rollBack();
                            echo rDatas(false, '添加失败');
                            exit();
                        }
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false,'添加失败');exit();
                    }
                }catch (PDOException $e){
                    $pdo->rollBack();
                    echo rDatas(false,$e->getMessage());exit();
                }
            }else{
                echo rDatas(false,'请输入短信验证码');exit();
            }
        }else{
            $rsql = "SELECT id,title FROM admin_role WHERE status=1";
            $role_arr = $db::mGetAll($rsql);
            include_once (VIEW.'/admin/admin_add.html');
        }
    }

    /**
     * 编辑管理员
     */
    public function adminEdit()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $id = $_POST['id'];
            $udata['phone'] = $_POST['phone'];
            $r_id = $_POST['r_id'];
            $udata['account'] = htmlspecialchars(trim($_POST['account']));
            $esql = "SELECT id FROM admin_user WHERE account='{$udata['account']}' AND id <>{$id}";
            if ($db::mGetRow($esql)){
                echo rDatas(false,'该账号已存在');exit();
            }
            $udata['email'] = $_POST['email'];
            $udata['status'] = $_POST['status'];
            $udata['update_time'] = time();
            //输入密码则修改密码
            if (!empty($_POST['password'])){
                $udata['password'] = cPassword($_POST['password']);
            }
            $where = "id={$id}";
            $drsql = "DELETE FROM admin_user_role WHERE u_id={$id}";
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try {
                if ($db::mExec('admin_user', $udata,'update',$where)){
                    //删除旧中间表数据
                    if ($db::sExec($drsql)) {
                        $rmdata = array();
                        $rmdata['u_id'] = $id;
                        $rmdata['r_id'] = $r_id;
                        if ($db::mExec('admin_user_role',$rmdata)){
                            $pdo->commit();
                            echo rDatas(true, '保存成功');
                            exit();
                        }else{
                            $pdo->rollBack();
                            echo rDatas(false, '保存失败');
                            exit();
                        }
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false, '中间表处理失败');
                        exit();
                    }
                }else{
                    $pdo->rollBack();
                    echo rDatas(false, '主表更新失败');
                    exit();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo rDatas(false, $e->getMessage());
                exit();
            }
        }else{
            $id = $_GET['id'];
            if (!is_numeric($id)) {
                echo rDatas(false, 'id不合法');
                exit();
            }
            $sql = "SELECT u.id,u.account,u.phone,u.email,u.status,FROM_UNIXTIME(u.add_time) as add_time,r.title,r_id FROM admin_user AS u LEFT JOIN admin_user_role ON u.id=u_id LEFT JOIN admin_role AS r ON r.id=r_id WHERE u.id={$id}";
            $res = $db::mGetRow($sql);
            $rsql = "SELECT id,title FROM admin_role WHERE status=1";
            $role_arr = $db::mGetAll($rsql);
            include_once (VIEW.'/admin/admin_edit.html');
        }
    }

    /**
     * 删除管理员
     */
    public function adminDel(){
        if (isset($_POST['id'])) {
            if (empty($db)) {
                $db = new Msql();
            }
            $id = $_POST['id'];
            if (!is_numeric($id)) {
                echo rDatas(false, 'id不合法');
                exit();
            }
            $dsql= "DELETE FROM admin_user WHERE id={$id}";
            $drmsql = "DELETE FROM admin_user_role WHERE u_id={$id}";
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try{
                if ($db::sExec($dsql)) {
                    if ($db::sExec($drmsql)){
                        $pdo->commit();
                        echo rDatas(true, '删除成功');
                        exit();
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false, '删除失败,失败原因：中间表删除失败');
                        exit();
                    }
                } else {
                    $pdo->rollBack();
                    echo rDatas(false, '删除失败,失败原因：主表删除失败');
                    exit();
                }
            }catch (PDOException $e){
                $pdo->rollBack();
                echo rDatas(false, $e->getMessage());
                exit();
            }
        }else{
            echo rDatas(false,'非法请求');exit();
        }
    }

    /**
     * 启用管理员
     */
    public function isOpen()
    {
        if (!empty($_POST)){
            if (!is_numeric($_POST['id'])){
                echo rDatas(false,'id不合法');exit();
            }
            if (empty($db)){
                $db = new Msql();
            }
            $udata['status'] = $_POST['status'];
            $udata['update_time'] = time();
            $where = "id={$_POST['id']}";
            if ($db::mExec('admin_user',$udata,'update',$where)){
                echo rDatas(true,'操作成功');exit();
            }else{
                echo rDatas(false,'操作失败');exit();
            }
        }else{
            echo rDatas(false, '非法请求');
            exit();
        }
    }

    /**
     * 获取验证码
     */
    public function getVerifyCode()
    {
        if (isset($_POST['phone'])){
            $phone = $_POST['phone'];
        }else{
            echo rDatas(false,'非法请求');exit();
        }
        $code = (string)mt_rand(1000, 9999);
        require_once ROOT_PATH . '/api/sendMsg.class.php';
        $sendObj = new sendMsg();
        $res = $sendObj->sendSms($phone,$code);
        if ($res['code'] == 200){
            $session_data = array();
            $session_data['data'] = $phone;
            $session_data['expire'] = time() + 120;
            $_SESSION['verify_phone'] = $session_data;
            $session_data['data'] = $code;
            $_SESSION['verify_code'] = $session_data;
            echo rDatas(true,'发送成功');exit();
        }
        echo rDatas(false,'发送失败:'.$res['data']);exit();
    }

    /**
    /*发送邮件
     */
    public function sendMail() {
        if (isset($_REQUEST['email'])) { // 如果接收到邮箱参数则发送邮件
            // 发送邮件
            $email = $_REQUEST['email'];
            $subject = $_REQUEST['subject'] ;
            $message = $_REQUEST['message'] ;
            require_once ROOT_PATH . '/api/sendMail.class.php';
            $sendObj = new sendMail();
            $ret = $sendObj->send($email,$subject,$message);
            if($ret != 'success') {
                echo rDatas(false,'发送失败: '.$ret);exit();
            } else {
                echo rDatas(true,'发送成功');exit();
            }
        } else {
            echo rDatas(false,'非法请求');exit();
        }
    }
}