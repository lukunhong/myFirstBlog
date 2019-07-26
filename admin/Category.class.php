<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class Category{
    //基于单例的实例化
    static $db = null;

    /**
     * 栏目列表
     */
    public function catList(){
        $csql = 'SELECT COUNT(*) FROM category WHERE is_del=0';
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

        $sql = "SELECT id,cat_name,status,sort,num,add_time FROM category WHERE is_del=0 LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once(VIEW.'/admin/catlist.html');
    }

    /**
     * 新增栏目
     */
    public function catAdd(){
        if (empty($_POST)) {
            require_once(VIEW.'/admin/catadd.html');
        }else{
            $data = array();
            if (empty(trim($_POST['cat_name']))) {
                echo rDatas(false,'栏目名不能为空');exit();
            }
            if (empty($db)){
                $db = new Msql();
            }
            $esql = "SELECT id FROM category WHERE cat_name='{$_POST['cat_name']}'";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'栏目名已存在');exit();
            }else{
                $data['cat_name'] = $_POST['cat_name'];
                $data['sort'] = $_POST['sort'];
                $data['status'] = $_POST['status'];
                $data['add_time'] = time();
                $data['update_time'] = time();
                if ($db::mExec('category',$data,'insert')){
                    echo rDatas(true,'添加成功');exit();
                }else{
                    echo rDatas(false,'添加失败');exit();
                }
            }
        }
    }

    /**
     * 编辑栏目
     */
    public function catEdit(){
        if (empty($db)){
            $db = new Msql();
        }
        if (empty($_POST)) {
            if(isset($_GET['id'])) {
                if (!is_numeric($_GET['id'])){
                    echo rDatas(false,'id不合法');exit();
                }
                $id = $_GET['id'];
                $dsql = "SELECT * FROM category WHERE id={$id}";
                $data = $db::mGetRow($dsql);
                include_once(VIEW . '/admin/catedit.html');
            }else{
                include_once VIEW.'/admin/error.html';
            }
        }else{
            $udata = array();
            if(!isset($_POST['id'])){
                echo rDatas(false,'id非法');exit();
            }
            if (!is_numeric($_POST['id'])){
                echo rDatas(false,'id不合法');exit();
            }
            $id = $_POST['id'];
            if (!empty(trim($_POST['cat_name']))) {
                $udata['cat_name'] = trim($_POST['cat_name']);
                $esql = "SELECT id FROM category WHERE cat_name='{$udata['cat_name']}' AND id <> {$id}";
                $exist = $db::mGetRow($esql);
                if ($exist){
                    echo rDatas(false,'栏目名已存在');exit();
                }
            }
            if (!empty(trim($_POST['sort']))) {
                $udata['sort'] = $_POST['sort'];
            }

            $udata['status'] = $_POST['status'];
            $udata['update_time'] = time();
            $where = "id={$id}";
            if ($db::mExec('category',$udata,'update',$where)){
                echo rDatas(true,'编辑成功');exit();
            }else{
                echo rDatas(false,'编辑失败');exit();
            }
        }
    }

    /**
     * 软删除
     */
    public function softDel(){
        if (isset($_POST['id'])) {
            //软删除
            if (!is_numeric($_POST['id'])){
                echo rDatas(false,'id不合法');exit();
            }
            $id = $_POST['id'];
            $now = time();
            $dsql = "UPDATE category SET is_del=1,update_time={$now} WHERE id={$id}";
            if (empty($db)){
                $db = new Msql();
            }
            $res = $db::sExec($dsql);
            if ($res){
                echo rDatas(true,'删除成功');exit();
            }else{
                echo rDatas(false,'删除失败');exit();
            }
        }else{
            echo rDatas(false,'非法请求');exit();
        }
    }

    /**
     * 回收站
     */
    public function delList(){
        if (empty($db)){
            $db = new Msql();
        }
        $csql = 'SELECT COUNT(*) FROM category WHERE is_del=1';
        $cres = $db::mGetRow($csql);
        //分页类
        $num = $cres?$cres[0]:0; //总数
        $page_size = PAGE_SIZE;
        $page = (integer)($_GET['page'] ?? 1); //当前页
        $pages = cPages($num,$page_size,$page); //页码
        $sql = "SELECT id,cat_name,status,sort,num FROM category WHERE is_del=1 LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once(VIEW.'/admin/catdlist.html');
    }

    /**
     * 彻底删除、软删除恢复
     */
    public function reOrDel(){
        if (empty($db)){
            $db = new Msql();
        }
        if (!is_numeric($_POST['id'])){
            echo rDatas(false,'id不合法');exit();
        }
        $id = $_POST['id'];
        //彻底删除
        if (isset($_POST['del']) && isset($_POST['del'])==200){
            $info_sql = "SELECT num FROM category WHERE id={$id}";
            $info = $db::mGetRow($info_sql);
            if ($info['num'] > 0){
                echo rDatas(false,'该栏目下有文章，无法删除');exit();
            }else{
                $dsql = "DELETE FROM category WHERE id={$id}";
                $res = $db::sExec($dsql);
                if ($res){
                    echo rDatas(true,'已删除');exit();
                }else{
                    echo rDatas(false,'删除失败');exit();
                }
            }

        }else {
            //软删除恢复
            $now = time();
            $dsql = "UPDATE category SET is_del=0,update_time={$now} WHERE id={$id}";
            $res = $db::sExec($dsql);
            if ($res) {
                echo rDatas(true, '恢复成功');
                exit();
            } else {
                echo rDatas(false, '恢复失败');
                exit();
            }
        }
    }


}