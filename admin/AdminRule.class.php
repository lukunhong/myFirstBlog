<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class AdminRule{
    //基于单例的实例化
    static $db = null;

    /**
     * 权限列表
     */
    public function ruleList(){
        $csql = 'SELECT COUNT(*) FROM admin_menu';
        if (empty($db)){
            $db = new Msql();
        }
        $cres = $db::mGetRow($csql);
        $num = $cres?$cres[0]:0;
        //分页类
        $page_size = PAGE_SIZE;
        $page = (integer)($_GET['page'] ?? 1);
        $pages = cPages($num,$page_size,$page);
        $sql = "SELECT m.id,m.name,m.url,c.title FROM admin_menu m LEFT JOIN admin_cate c ON m.c_id=c.id LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once (VIEW.'/admin/admin_rule.html');
    }

    /**
     * 新增权限
     */
    public function ruleAdd(){
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $adata = array();
            $adata['name'] = htmlspecialchars(trim($_POST['name']));
            $esql = "SELECT id FROM admin_menu WHERE name='{$adata['name']}'";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'权限名称已存在');exit();
            }else{
                $adata['c_id'] = $_POST['c_id'];
                $adata['url'] = trim($_POST['url']);
                if ($db::mExec('admin_menu', $adata)){
                    echo rDatas(true,'保存成功');exit();
                }else{
                    echo rDatas(false,'保存失败');exit();
                }
            }
        }else{
            $csql = 'SELECT * FROM admin_cate';
            $admin_cate = $db::mGetAll($csql);;
            include_once (VIEW.'/admin/admin_rule_add.html');
        }
    }

    /**
     * 编辑权限
     */
    public function ruleEdit(){
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $adata = array();
            $adata['name'] = htmlspecialchars(trim($_POST['name']));
            $esql = "SELECT id FROM admin_menu WHERE name='{$adata['name']}' AND id <> {$_POST['id']}";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'权限名称已存在');exit();
            }else{
                $where = "id={$_POST['id']}";
                $adata['c_id'] = $_POST['c_id'];
                $adata['url'] = trim($_POST['url']);
                if ($db::mExec('admin_menu', $adata,'update',$where)){
                    echo rDatas(true,'保存成功');exit();
                }else{
                    echo rDatas(false,'保存失败');exit();
                }
            }
        }else{
            $id = $_GET['id'];
            $csql = 'SELECT * FROM admin_cate';
            $admin_cate = $db::mGetAll($csql);;
            $dsql = "SELECT * FROM admin_menu WHERE id={$id}";
            $data = $db::mGetRow($dsql);
            include_once (VIEW.'/admin/admin_rule_edit.html');
        }
    }

    /**
     * 删除权限
     */
    public function ruleDel()
    {
        if (isset($_POST['id'])) {
            if (!is_numeric($_POST['id'])) {
                echo rDatas(false, 'id不合法');
                exit();
            }
            $id = $_POST['id'];
            if (empty($db)) {
                $db = new Msql();
            }
            //校验菜单
            $dsql = "DELETE FROM admin_menu WHERE id={$id}";
            $drmsql = "DELETE FROM admin_role_menu WHERE m_id={$id}";
            //开启事务
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try {
                if ($db::sExec($dsql)) {
                    if ($db::sExec($drmsql)){
                        $pdo->commit();
                        echo rDatas(true, '删除成功');
                        exit();
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false, '删除失败，失败原因：中间表无法删除');
                        exit();
                    }
                } else {
                    $pdo->rollBack();
                    echo rDatas(false, '删除失败，失败原因：主表无法删除');
                    exit();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo rDatas(false, $e->getMessage());
                exit();
            }
        } else {
            echo rDatas(false, '非法请求');
            exit();
        }
    }
}