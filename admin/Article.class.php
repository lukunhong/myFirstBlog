<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */

class Article{
    //基于单例的实例化
    static $db = null;

    /**
     * 文章列表
    */
    public function artList(){
        if (!empty($_POST)){
            $where = empty($_POST['title']) ? '' : " and a.title like '%".htmlspecialchars(trim($_POST['title']))."%'";
            $where .= empty($_POST['start']) ? '' : ' and a.add_time >= '.strtotime($_POST['start']);
            $where .= empty($_POST['end']) ? '' : ' and a.add_time <= '.strtotime($_POST['end']);
        }else{
            $where = '';
        }
        $csql = "SELECT COUNT(*) FROM article a WHERE a.is_del=0 {$where}";
        if (empty($db)){
            $db = new Msql();
        }
        $cres = $db::mGetRow($csql);
        //分页类
        $num = $cres?$cres[0]:0;
        $page_size = PAGE_SIZE;
        $page = (integer)($_GET['page'] ?? 1);
        $pages = cPages($num,$page_size,$page);
        $page = $page>$pages[1]?$pages[1]:$page;
        $sql = "SELECT a.id,title,thumb,a.add_time,comm,cat_name FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.is_del=0 {$where} LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once(VIEW.'/admin/artlist.html');
    }

    /**
     * 新增文章
    */
    public function artAdd(){
        if (empty($db)){
            $db = new Msql();
        }
        if (empty($_POST)) {
            $sql = "SELECT id,cat_name FROM category WHERE is_del=0";
            $categorys = $db::mGetAll($sql);
            require_once(VIEW.'/admin/art_add.html');
        }else{
            $data = array();
            if (empty(trim($_POST['title']))) {
                echo rDatas(false,'文章标题不能为空');exit();
            }else{
                $data['title'] = htmlspecialchars(trim($_POST['title']));
            }
            if (empty(trim($_POST['cat_id']))) {
                echo rDatas(false,'所属栏目不能为空');exit();
            }
            $esql = "SELECT id FROM article WHERE title='{$data['title']}'";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'该文章标题已存在');exit();
            }
            //处理图片
            if (!empty($_FILES['pic']['tmp_name'])){
                $allow_type=['image/jpeg','image/jpg','image/png','image/gif','image/bmp'];
                if (!in_array($_FILES['pic']['type'],$allow_type)){
                    echo rDatas(false,'图片格式非法');exit();
                }
                $tmp_name = $_FILES['pic']['tmp_name'];
                //截取后缀
                $suffix_arr = explode('.',$_FILES['pic']['name']);
                $suffix = $suffix_arr[count($suffix_arr)-1];
                //前缀
                $prefix=uniqid().mt_rand(10,99).'_'.date('Y-m-d',time());
                $name = $prefix.'.'.$suffix;
                //存放路径
                $savepath = ROOT_PATH.DS.'public'.DS.'uploads';
                //创建目录
                if(is_dir($savepath))
                {
                    $filepath = ROOT_PATH.DS.'public'.DS.'uploads'.DS;
                }else
                {
                    mkdir(ROOT_PATH.DS.'public'.DS.'uploads');
                    $filepath = ROOT_PATH.DS.'public'.DS.'uploads'.DS;
                }
                //上传图片
                if (move_uploaded_file($tmp_name,$filepath.$name)) {
                    //生成缩略图
                    $thumb = makeThumb($filepath.$name);
                    if ($thumb == 'false'){
                        //缩略图失败删除已上传
                        unlink($filepath.$name);
                        echo rDatas(false,'缩略图生成出错，请重试');exit();
                    }
                    $data['pic'] = $name;
                    $data['thumb'] = $thumb;
                }else{
                    //许可证上传失败则删除已传执照
                    echo rDatas(false,'图片上传出错，请刷新后重试');exit();
                }
            }
            //组装上传数据
            $data['cat_id'] = (integer)$_POST['cat_id'];
            $data['content'] = $_POST['content'];
            $data['add_time'] = time();
            //开启事务
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try{
                $csql = "UPDATE category SET num=num+1 WHERE id={$data['cat_id']}";
                if ($db::sExec($csql)){
                    if ($db::mExec('article',$data,'insert')){
                        $pdo->commit();
                        echo rDatas(true,'添加成功');exit();
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false,'添加失败');exit();
                    }
                }else{
                    echo rDatas(false,'文章数新增失败');exit();
                }
            }catch (PDOException $e){
                $pdo->rollBack();
                echo rDatas(false,$e->getMessage());exit();
            }
        }
    }

    /**
     * 编辑文章
     */
    public function artEdit(){
        if (empty($db)){
            $db = new Msql();
        }
        if (empty($_POST)) {
            if(isset($_GET['id'])) {
                $id = $_GET['id'] = (integer)$_GET['id'];
                $sql = "SELECT id,cat_name FROM category WHERE is_del=0";
                $categorys = $db::mGetAll($sql);
                $sql = "SELECT id,cat_id,title,content,pic,thumb FROM article WHERE id={$id}";
                $info = $db::mGetRow($sql);
                include_once(VIEW.'/admin/art_edit.html');
            }else{
                include_once VIEW.'/admin/error.html';
            }
        }else{
            $udata = array();
            if (!empty(trim($_POST['title']))) {
                $udata['title'] = htmlspecialchars($_POST['title']);
            }
            if (!empty(trim($_POST['cat_id']))) {
                $udata['cat_id'] = (integer)$_POST['cat_id'];
            }else{
                echo rDatas(false,'所属栏目不能为空');exit();
            }
            $esql = "SELECT id FROM article WHERE title='{$_POST['title']}' AND id <> {$_POST['id']}";
            $exist = $db::mGetRow($esql);
            if ($exist){
                echo rDatas(false,'该文章标题已存在');exit();
            }
            //存放路径
            $savepath = ROOT_PATH.DS.'public'.DS.'uploads';
            //创建目录
            if(is_dir($savepath))
            {
                $filepath = ROOT_PATH.DS.'public'.DS.'uploads'.DS;
            }else
            {
                mkdir(ROOT_PATH.DS.'public'.DS.'uploads');
                $filepath = ROOT_PATH.DS.'public'.DS.'uploads'.DS;
            }
            //处理图片
            if (!empty($_FILES['pic']['tmp_name'])){
                $allow_type=['image/jpeg','image/jpg','image/png','image/gif','image/bmp'];
                if (!in_array($_FILES['pic']['type'],$allow_type)){
                    echo rDatas(false,'图片格式非法');exit();
                }
                $tmp_name = $_FILES['pic']['tmp_name'];
                //截取后缀
                $suffix_arr = explode('.',$_FILES['pic']['name']);
                $suffix = $suffix_arr[count($suffix_arr)-1];
                //前缀
                $prefix=uniqid().mt_rand(10,99).'_'.date('Y-m-d',time());
                $name = $prefix.'.'.$suffix;
                //上传图片
                if (move_uploaded_file($tmp_name,$filepath.$name)) {
                    //生成缩略图
                    $thumb = makeThumb($filepath.$name);
                    if ($thumb == 'false'){
                        //缩略图失败删除已上传
                        unlink($filepath.$name);
                        echo rDatas(false,'缩略图生成出错，请重试');exit();
                    }
                    $udata['pic'] = $name;
                    $udata['thumb'] = $thumb;
                }else{
                    //许可证上传失败则删除已传执照
                    echo rDatas(false,'图片上传出错，请刷新后重试');exit();
                }
            }
            //组装上传数据
            $udata['content'] = $_POST['content'];
            $udata['update_time'] = time();
            //开启事务
            $pdo = $db::mConnect();
            $pdo->beginTransaction();
            try{
                $where = "id={$_POST['id']}";
                //变换栏目
                if ($_POST['old_cat_id'] != $_POST['cat_id']){
                    $csql1 = "UPDATE category SET num=num-1 WHERE id={$_POST['old_cat_id']}";
                    $csql2 = "UPDATE category SET num=num+1 WHERE id={$_POST['cat_id']}";
                    if ($db::sExec($csql1) && $db::sExec($csql2)){
                        if ($db::mExec('article',$udata,'update',$where)){
                            $pdo->commit();
                            if (!empty($_POST['old_pic']) && !empty($_FILES['pic']['tmp_name'])){
                                unlink($filepath.$_POST['old_pic']);
                            }
                            if (!empty($_POST['old_thumb']) && !empty($_FILES['pic']['tmp_name'])){
                                unlink($filepath.$_POST['old_thumb']);
                            }
                            echo rDatas(true,'保存成功');exit();
                        }else{
                            $pdo->rollBack();
                            echo rDatas(false,'保存失败');exit();
                        }
                    }else{
                        echo rDatas(false,'文章转移失败');exit();
                    }
                }else{
                    if ($db::mExec('article',$udata,'update',$where)){
                        $pdo->commit();
                        //删除旧图
                        if (!empty($_POST['old_pic']) && !empty($_FILES['pic']['tmp_name'])){
                            unlink($filepath.$_POST['old_pic']);
                        }
                        if (!empty($_POST['old_thumb']) && !empty($_FILES['pic']['tmp_name'])){
                            unlink($filepath.$_POST['old_thumb']);
                        }
                        echo rDatas(true,'保存成功');exit();
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false,'保存失败');exit();
                    }
                }
            }catch (PDOException $e){
                $pdo->rollBack();
                echo rDatas(false,$e->getMessage());exit();
            }
        }
    }

    /**
     * 软删除
     */
    public function softDel(){
        if (isset($_POST['del']) && $_POST['del'] == 200){
            if (isset($_POST['id'])) {
                //软删除
                if (!is_numeric($_POST['id'])){
                    echo rDatas(false,'id不合法');exit();
                }
                $id = $_POST['id'];
                $now = time();
                $dsql = "UPDATE article SET is_del=1,update_time={$now} WHERE id={$id}";
                if (empty($db)){
                    $db = new Msql();
                }
                $res = $db::sExec($dsql);
                if ($res){
                    echo rDatas(true,'删除成功');exit();
                }else{
                    echo rDatas(false,'删除失败');exit();
                }
            }
        }else{
            echo rDatas(false,'异常请求');exit();
        }
    }

    /**
     * 回收站
     */
    public function delList(){
        if (empty($db)){
            $db = new Msql();
        }
        $csql = 'SELECT COUNT(*) FROM article WHERE is_del=1';
        $cres = $db::mGetRow($csql);
        //分页类
        $num = $cres?$cres[0]:0;
        $page_size = PAGE_SIZE;
        $page = (integer)($_GET['page'] ?? 1);
        $pages = cPages($num,$page_size,$page);
        $sql = "SELECT a.id,a.title,a.thumb,a.add_time,a.comm,c.cat_name FROM article AS a LEFT JOIN category AS c ON a.cat_id=c.id WHERE a.is_del=1 LIMIT ".($page-1)*$page_size.",{$page_size}";
        $res = $db::mGetAll($sql);
        include_once(VIEW.'/admin/artdlist.html');
    }

    /**
     * 彻底删除、软删除恢复
     */
    public function reOrDel(){
        if (empty($db)){
            $db = new Msql();
        }
        if (isset($_POST['id'])){
            if (!is_numeric($_POST['id'])){
                echo rDatas(false,'id不合法');exit();
            }
            $id = $_POST['id'];
            //彻底删除
            if (isset($_POST['del']) && isset($_POST['del'])==200){
                //开启事务
                $pdo = $db::mConnect();
                $pdo->beginTransaction();
                try{
                    $psql = "SELECT cat_id,pic,thumb FROM article WHERE id={$id}";
                    $pinfo = $db::mGetRow($psql);
                    //文章数-1
                    $csql1 = "UPDATE category SET num=num-1 WHERE id={$pinfo['cat_id']}";
                    if ($db::sExec($csql1)){
                        $dcomsql = "DELETE FROM comment WHERE art_id={$id}";
                        $res = $db::sExec($dcomsql);
                        $dsql = "DELETE FROM article WHERE id={$id}";
                        $res = $db::sExec($dsql);
                        if ($res){
                            $pdo->commit();
                            //删图
                            if (!empty($pinfo['pic']) && !empty($pinfo['thumb'])){
                                $filepath = ROOT_PATH.DS.'public'.DS.'uploads'.DS;
                                unlink($filepath.$pinfo['pic']);
                                unlink($filepath.$pinfo['thumb']);
                            }
                            echo rDatas(true,'已删除');exit();
                        }else{
                            $pdo->rollBack();
                            echo rDatas(false,'删除失败');exit();
                        }
                    }else{
                        $pdo->rollBack();
                        echo rDatas(false,'文章数去除失败');exit();
                    }
                }catch (PDOException $e){
                    $pdo->rollBack();
                    echo rDatas(false,$e->getMessage());exit();
                }
            }else {
                //软删除恢复
                $now = time();
                $dsql = "UPDATE article SET is_del=0,update_time={$now} WHERE id={$id}";
                $res = $db::sExec($dsql);
                if ($res) {
                    echo rDatas(true, '恢复成功');
                    exit();
                } else {
                    echo rDatas(false, '恢复失败');
                    exit();
                }
            }
        }else{
            echo rDatas(false,'异常请求');exit();
        }
    }


}