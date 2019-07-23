<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */

class adminCate{
    //基于单例的实例化
    static $db = null;

    /**
     * 权限分类列表
     */
    public function cateList(){
        $csql = 'SELECT COUNT(*) FROM admin_cate';
        if (empty($db)){
            $db = new Msql();
        }
        $cres = $db::mGetRow($csql);
        $num = $cres?$cres[0]:0;
        //分页类
        $page_size = PAGE_SIZE;
        $page = (integer)($_GET['page'] ?? 1);
        $pages = cPages($num,$page_size,$page);
        $page = $page>$pages[1]?$pages[1]:$page;
        $sql = "SELECT * FROM admin_cate LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once(VIEW . '/admin/admin_cate.html');
    }

    /**
     * 新增分类
     */
    public function cateAdd(){
        if (!empty($_POST)){
            $title = htmlspecialchars(trim($_POST['title']));
            if (empty($db)){
                $db = new Msql();
            }
            $esql = "SELECT id FROM admin_cate WHERE title='{$title}'";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'分类已存在');exit();
            }else{
                $sql = "INSERT INTO admin_cate(title) VALUES ('{$title}')";
                $res = $db::sExec($sql);
                if ($res){
                    echo rDatas(true,'添加成功');exit();
                }else{
                    echo rDatas(false,'添加失败');exit();
                }
            }
        }else{
            include_once (VIEW.'/admin/admin_cate_add.html');
        }
    }

    /**
     * 编辑分类
     */
    public function cateEdit(){
        if (empty($db)){
            $db = new Msql();
        }
        if (!empty($_POST)){
            $udata = array();
            $id = $_POST['id'];
            if (!empty(trim($_POST['title']))) {
                $title = htmlspecialchars(trim($_POST['title']));
                $udata['title'] = $title;
                $esql = "SELECT id FROM admin_cate WHERE title='{$title}' AND id <> {$id}";
                $exist = $db::mGetRow($esql);
                if ($exist){
                    echo rDatas(false,'分类名已存在');exit();
                }
                $where = "id={$id}";
                if ($db::mExec('admin_cate',$udata,'update',$where)){
                    echo rDatas(true,'编辑成功');exit();
                }else{
                    echo rDatas(false,'编辑失败');exit();
                }
            }else{
                echo rDatas(true,'保存成功');exit();
            }
        }else{
            if(isset($_GET['id'])) {
                if (!is_numeric($_GET['id'])){
                    echo rDatas(false,'id不合法');exit();
                }
                $id = $_GET['id'];
                $dsql = "SELECT * FROM admin_cate WHERE id={$id}";
                $data = $db::mGetRow($dsql);
                include_once (VIEW.'/admin/admin_cate_edit.html');
            }else{
                include_once VIEW.'/admin/error.html';
            }
        }
    }

    /**
     * 删除分类
     */
    public function cateDel(){
        if (isset($_POST['id'])) {
            if (!is_numeric($_POST['id'])){
                echo rDatas(false,'id不合法');exit();
            }
            $id = $_POST['id'];
            $dsql = "SELECT * FROM admin_cate_menu WHERE c_id={$id}";
            $dmsql = "SELECT * FROM admin_menu WHERE c_id={$id}";
            if (empty($db)){
                $db = new Msql();
            }
            $res = $db::mGetRow($dsql);
            $mres = $db::mGetRow($dmsql);
            //校验菜单
            if ($res == 0 && $mres == 0){
                $dsql = "DELETE FROM admin_cate WHERE id={$id}";
                if ($db::sExec($dsql)) {
                    echo rDatas(true, '删除成功');
                    exit();
                }else{
                    echo rDatas(false,'删除失败');exit();
                }
            }else{
                echo rDatas(false,'该分类下有菜单，无法删除');exit();
            }
        }else{
            echo rDatas(false,'非法请求');exit();
        }
    }




}