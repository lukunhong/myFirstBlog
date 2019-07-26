<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */

class AdminRole{
    //基于单例的实例化
    static $db = null;

    /**
     * 角色列表
     */
    public function roleList()
    {
       if (!empty($_POST)){
           $csql = 'SELECT COUNT(*) FROM admin_role';
           if (empty($db)){
               $db = new Msql();
           }
           $cres = $db::mGetRow($csql);
           $num = $cres?$cres[0]:0;
           //使用layui表格
           $sql = "SELECT id,title,status,descr FROM admin_role LIMIT ".($_POST['page']-1)*$_POST['limit'].",{$_POST['limit']}";
           $res = $db::mGetAll($sql);
           $return['code'] = 0;
           $return['count'] = $num;
           $return['data'] = $res;
           $return['msg']    = "";
           echo json_encode($return);
           exit();
       }else {
           include_once(VIEW . '/admin/admin_role.html');
       }
    }

    /**
     * 添加角色
     */
    public function roleAdd()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $adata = array();
            $adata['title'] = htmlspecialchars(trim($_POST['title']));
            if (empty($adata['title'])) {echo rDatas(false, '角色名不能为空');exit();}
            $esql = "SELECT id FROM admin_role WHERE title='{$adata['title']}'";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'角色名已存在');exit();
            }
            $adata['descr'] = htmlspecialchars(trim($_POST['desc']));
            $ids = explode(',',$_POST['groups']);
            $adata['status'] = $_POST['status'];
            $adata['add_time'] = time();
            $adata['update_time'] = time();
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try {
                if ($db::mExec('admin_role', $adata)){
                    $r_id = $pdo->lastInsertId();
                    $rmdata = array();
                    foreach ($ids as $k =>$v){
                        $rmdata[$k]['r_id'] = $r_id;
                        $rmdata[$k]['m_id'] = $v;
                    }
                    if ($db::m2Exec('admin_role_menu',$rmdata)){
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
                    echo rDatas(false, '角色名添加失败');
                    exit();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo rDatas(false, $e->getMessage());
                exit();
            }

        }else{
            $csql = 'SELECT m.id,m.c_id,m.name,c.title FROM admin_menu m LEFT JOIN admin_cate c ON m.c_id=c.id';
            $rule_info = $db::mGetAll($csql);
            //二维数组归类
            $rule_arr = arrTree($rule_info,'c_id');
            include_once (VIEW.'/admin/admin_role_add.html');
        }
    }

    /**
     * 启用角色
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
            if ($db::mExec('admin_role',$udata,'update',$where)){
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
     * 修改角色
     */
    public function roleEdit()
    {
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $id = $_POST['id'];
            $udata = array();
            $udata['title'] = htmlspecialchars(trim($_POST['title']));
            if (empty($udata['title'])) {echo rDatas(false, '角色名不能为空');exit();}
            $esql = "SELECT id FROM admin_role WHERE title='{$udata['title']}' AND id <>{$id}";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'角色名已存在');exit();
            }
            $udata['descr'] = htmlspecialchars(trim($_POST['desc']));
            $ids = explode(',',$_POST['groups']);
            $udata['status'] = $_POST['status'];
            $udata['update_time'] = time();
            $where = "id={$_POST['id']}";
            $drmsql = "DELETE FROM admin_role_menu WHERE r_id={$id}";
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try {
                if ($db::mExec('admin_role', $udata,'update',$where)){
                    //删除旧中间表数据
                    if ($db::sExec($drmsql)) {
                        $rmdata = array();
                        foreach ($ids as $k => $v) {
                            $rmdata[$k]['r_id'] = $id;
                            $rmdata[$k]['m_id'] = $v;
                        }
                        if ($db::m2Exec('admin_role_menu', $rmdata)) {
                            $pdo->commit();
                            echo rDatas(true, '保存成功');
                            exit();
                        } else {
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
                    echo rDatas(false, '角色更新失败');
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
            $hsql = "SELECT * FROM admin_role WHERE id={$id}";
            $role_info = $db::mGetRow($hsql);
            $csql = 'SELECT m.id,m.c_id,m.name,c.title FROM admin_menu m LEFT JOIN admin_cate c ON m.c_id=c.id';
            $rule_info = $db::mGetAll($csql);
            $hsql = "SELECT * FROM admin_role_menu WHERE r_id={$id}";
            $has_permi = $db::mGetAll($hsql);
            //二维数组归类
            $rule_arr = arrTree($rule_info,'c_id');
            $has_arr = array();
            foreach ($has_permi as $k=>$v){
                $has_arr[$k] = $v['m_id'];
            }
            include_once (VIEW.'/admin/admin_role_edit.html');
        }
    }

    /**
     * 删除角色
     */
    public function roleDel(){
        if (isset($_POST['id'])) {
            if (empty($db)) {
                $db = new Msql();
            }
            $id = $_POST['id'];
            if (!is_numeric($id)) {
                echo rDatas(false, 'id不合法');
                exit();
            }
            //校验使用
            $esql = "SELECT * FROM admin_user_role WHERE r_id={$id}";
            if ($db::mGetRow($esql)){
                echo rDatas(false,'该角色正在使用中，无法删除');exit();
            }
            $dsql= "DELETE FROM admin_role WHERE id={$id}";
            $drmsql = "DELETE FROM admin_role_menu WHERE r_id={$id}";
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
        }else {
            echo rDatas(false, '非法请求');
            exit();
        }
    }

}