<?php
/**
 * mysql操作函数
 * @author lulu
 * fetch/fetchAll参数：
 * PDO::FETCH_ASSOC:关联数组形式
 * PDO::FETCH_NUM:数字索引数组形式
 * PDO::FETCH_BOTH:两者数组形式都有，这是默认的
 * PDO::FETCH_OBJ:按照对象的形式，类似于以前的mysql_fetch_object()
 * PDO::FETCH_BOUND:以布尔值的形式返回结果，同时将获取的列值赋给bindParam()方法中指定的变量
 * PDO::FETCH_LAZY:以关联数组、数字索引数组和对象3种形式返回结果。
 **/

class Msql{
    private static $pdo;
    /**
     * 连接数据库
     * @return PDO
     */
    public static function mConnect(): PDO
    {
        //mConnect在同一个页面数据库只连接一次
        if (empty(self::$pdo)){
            require (LIB.'/config.php');
            //拼接数据库主机信息
            $dbh = $cfg['type'] . ':host=' . $cfg['host'] . ';' . 'dbname=' . $cfg['db'];
            try {
                //开始连接数据库
                $conn = new PDO($dbh, $cfg['user'], $cfg['password']);
                // 设置 PDO 错误模式为异常
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //echo '连接成功';
                //设置字符集
                $conn->query('set names ' . $cfg['charset']);
                self::$pdo = $conn;
            } catch (PDOException $e) {
                //连接失败错误提示
                writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($e->getMessage()));
                die('error:' . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    /**
     * 执行sql语句
     ** @param string $sql
     * @return PDOStatement 返回布尔型值，资源
     */
    public static function mQuery(string $sql):PDOStatement
    {
        $conn = self::mConnect();
        return $conn->query($sql);
    }

    /**
     * 执行sql语句
     ** @param string $sql
     * @return int 返回执行后受影响的行数
     */
    public static function sExec(string $sql):int
    {
        $conn = self::mConnect();
        return $conn->exec($sql);
    }

    /**
     * 调用select语句并返回一行
     * @param string $sql
     * @return mixed 查到返回一维数组，查不到返回false
     */
    public static function mGetRow(string $sql)
    {
        try{
            $myq = self::mQuery($sql);
            //同时获取关联数组和数字索引数组形式
            $res = $myq->fetch();
        }catch(PDOException $e){
            //连接失败错误提示
            writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($e->getMessage()));
            die('error:'.$e->getMessage());
        }
        return $res;
    }

    /**
     * 调用select语句并返回多行，适用于查多条数据
     * @param string $sql
     * @return mixed 查到返回二维数组,，查不到返回false
     */
    public static function mGetAll(string $sql)
    {
        try{
            $myq = self::mQuery($sql);
            $res = $myq->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            //连接失败错误提示
            writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($e->getMessage()));
            die('error:'.$e->getMessage());
        }
        return $res;
    }

    /**
     * 拼接sql语句，执行插入/修改
     * @param string $table 待插入的表
     * @param array $data 要插入或修改的数据
     * @param string $acr 插入还是修改，默认是insert操作
     * @param string $where 防止update语句忘记加where
     * @return int 受影响的行数
     */
    public static function mExec(string $table,array $data,string $act='insert',string $where='0'):int
    {
        if ($act=='insert') {
            $sql="INSERT INTO {$table}(";
            $sql.=implode(',', array_keys($data)).") values('";
            $sql.=implode("','", array_values($data))."')";
        }elseif ($act=='update'){
            $sql="UPDATE {$table} SET ";
            foreach ($data as $k => $v) {
                $sql.=$k."='".$v."',";
            }
            $sql=rtrim($sql,',');
            $sql.=' where '.$where;
        }else{
            throw new Exception('参数错误');
        }
        try{
            $conn = self::mConnect();
            $res = $conn->exec($sql);
        }catch (PDOException $e){
            writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($e->getMessage()));
            die('error:'.$e->getMessage());
        }
        return $res;
    }

    /**
     * 拼接sql语句，执行二维数组插入
     * @param string $table 待插入的表
     * @param array $data 要插入的二维数组
     * @param string $acr 默认insert操作
     * @param string $where 防止update语句忘记加where
     * @return int 受影响的行数
     */
    public static function m2Exec(string $table,array $data,string $act='insert',string $where='0'):int
    {
        if ($act=='insert') {
            $sql="INSERT INTO {$table}(";
            foreach ($data as $k => $v){
                if ($k == 0){
                    $sql.=implode(',', array_keys($v)).") values";
                }
                $sql.="('".implode("','", array_values($v))."'),";
            }
            $sql=rtrim($sql,',');
        }else{
            throw new Exception('参数错误');
        }
        try{
            $conn = self::mConnect();
            $res = $conn->exec($sql);
        }catch (PDOException $e){
            writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($e->getMessage()));
            die('error:'.$e->getMessage());
        }
        return $res;
    }

    /**
     * 返回最近一次插入产生的主键值
     * @return int
     */
    public static function getLastId():int
    {
        $conn = self::mConnect();
        return $conn->lastInsertId();
    }

    /**
     * SELECT 语句（或 SHOW语句等）查询结果集的记录数
     * @return int
     */
    public static function mCount():int
    {
        $res = self::mQuery('SELECT FOUND_ROWS()');
        return (int) $res->fetchColumn();
    }

    /**
     * MySQL 预处理插入
     * @param string $table 表名
     * @param array $data 插入的关联数组
     * @return int 自增id
     */
    public static function mInsert(string $table,array $data):int
    {
        $keys = array_keys($data);
        $fields = '`'.implode('`, `',$keys).'`';
        $placeholder = rtrim(str_repeat('?,',count($keys)),',');
        $conn = self::mConnect();
        // 预处理 SQL 并绑定参数
        try{
            $stmt = $conn->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholder})");
            $res = $stmt->execute(array_values($data));
            if($res){
                return self::getLastId();
            }else{
                writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($res));
                throw new Exception('执行失败');
            }
        }catch (PDOException $e){
            die('error:'.$e->getMessage());
        }
    }

    /**
     * MySQL 预处理关联数组查内容
     * @param string $table
     * @param array $data
     * @return array
     */
    public static function mSelect(string $table,array $data):array
    {
        $where = implode('',array_keys($data));
        $value = implode('',array_values($data));
        $conn = self::mConnect();
        $stmt = $conn->prepare("SELECT * FROM {$table} where {$where} = ?");
        $res = $stmt->execute(array($value));
        $data = array();
        if ($res) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[]=$row;
            }
        }else{
            writeLog(__FILE__.'>>>第'.__LINE__. "行\n".json_encode($res));
        }
        return $data;
    }

}