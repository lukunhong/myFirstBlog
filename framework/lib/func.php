<?php
/**
 * Created by PhpStorm.
 * User: lulu
 * Date: 2019/6/24
 * Time: 16:12
 */
//开启严格模式
declare(strict_types=1);

/**
 * 百度富文本剔除节点样式
 * @param string $str
 * @param int $len 截取长度
 * @return string
 */
function uEditorToText(string $str,int $len=200):string
{
    $str = htmlspecialchars_decode($str);//把一些预定义的 HTML 实体转换为字符
    $str = str_replace("&nbsp;","",$str);//将空格替换成空
    $str = strip_tags($str);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
    $str = mb_substr($str, 0, $len,"utf-8");//返回字符串中的前100字符串长度的字符
    return $str;
}

/**
 * 检查段落中表情字符，并转成实体表情图
 * @param string $str  继续#emoji:5_img#明白了#emoji:29_img#
 * @return string
 */
function decodeEmoji(string $str):string
{
    $img_path = '<img src="/public/home/js/jquery-emoji/dist/img/qq/';
    $img_suf = '.gif" alt="[表情]">';
    $str = str_replace("#emoji:",$img_path,$str);
    $str = str_replace("_img#",$img_suf,$str);
    return $str;
}

/**
 * 获取用户真实 IP
 */
function getIp():string
{
    static $realip;
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

/**
 * 利用淘宝的ip地址库获获取ip地址
 */
function getIpAddress():string
{
    $opts = array(
        'http'=>array(
            'method'=>"GET",
            'timeout'=>5,)
    );
    $context = stream_context_create($opts);
    $ipmac=getIp();
    if(strpos($ipmac,"127.0.0.") === true)return '';
    $url_ip='http://ip.taobao.com/service/getIpInfo.php?ip='.$ipmac;
    $str = @file_get_contents($url_ip, false, $context);
    if(!$str) return "";
    $json=json_decode($str,true);
    if($json['code']==0){
        $ipcity= $json['data']['region'].$json['data']['city'];
        $ip= $ipcity.','.$ipmac;
    }else{
        $ip="";
    }
    return $ip;
}

/**
 * * sql注入防范：转义字符串，对post,get,cookie数组进行转义
 * @param array $arr
 * @return array
 */
function _addslashes(array $arr):array
{
    foreach ($arr as $k => $v) {
        if (is_string($v)) {
            $arr[$k]=addslashes($v);
        }elseif (is_array($v)) {
            $arr[$k]=_addslashes($v);
        }
    }
    return $arr;
}

/**
 * 计算分页代码
 * @param int $num 总文章数
 * * @param int $page_size 每页显示文章数
 * * @param int $curr 当前显示页码数
 * @param array $pages 返回一个页码数和最大页码数的二维数组
 */
function cPages(int $num,int $page_size=5,int $curr=1):array
{
    if ($num == 0){
        return array(array(),1);
    }
    $max=ceil($num/$page_size);
    $left=$curr-2;
    $left=max($left,1);$right=$left+4;
    $right=min($right,$max);
    $left=$right-4;
    $left=max($left,1);
    $pages = array();
    for($i=$left;$i<=$right;$i++){
        $_GET['page']=$i;
        $pages[0][$i]=http_build_query($_GET);
//        $pages[$i]=$i;
    }
    $pages[1] = $max;
    return $pages;
}

/**
 * 处理返回信息
 * @param bool $bol 是否成功
 * @param string|array $message 提示信息或数据
 * @return string json
 */
function rDatas(bool $bol, $message):string
{
    $datas = array();
    $datas['code'] = $bol ? 200 : 500;
    $datas['data'] = $message;
    return json_encode($datas);
}

/**
 * md5 加密用户名+盐
 * @param string $name 用户名
 * @return string str 返回加密后的字符*/
function cName(string $name):string
{
    require(LIB.'/config.php');
    $salt=$cfg['salt'];
    return md5($name.$salt);
}

/**
 * php7加盐加密
 * 请注意，随时间推移，默认算法可能会有变化，所以需要储存的空间能够超过 60 字（建议255字）
 * @param string $password
 * @return string 加密后的字符串，注：同个字符串每次加密结果不一样
 */
function cPassword(string $password):string
{
    require (LIB.'/config.php');
    $salt=$cfg['salt'];
    $password .= $salt;
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * php7验证加盐加密
 * @param string $password 用户的密码
 * @param string $hash 一个由 password_hash() 创建的散列值。
 * @return bool
 */
function dPassword( string $password , string $hash ):bool
{
    require(LIB.'/config.php');
    $salt=$cfg['salt'];
    $password .= $salt;
    return password_verify($password,$hash);
}

/**
 * 检测后台用户是否登陆
 */
function accLogin():bool
{
    //设置session过期时间
//    $lifetime=60;
//    session_set_cookie_params($lifetime);
    if (session_status() !==PHP_SESSION_ACTIVE) {
        session_start();
    }
//    if (isset($_COOKIE['uid']) && isset($_COOKIE['ucode'])) {
//        return dPassword($_COOKIE['uid'],$_COOKIE['ucode']);
//    }else{
//        return false;
//    }
    if (isset($_SESSION['ses_id']) && $_SESSION['ses_id'] == session_id()) {
        if (isset($_SESSION['admin_uid']) && isset($_SESSION['admin_ucode'])) {
            return dPassword($_SESSION['admin_uid'], $_SESSION['admin_ucode']);
        } else {
            return false;
        }
    }else{
        return false;
    }
}

/**
 * 检测前端用户是否登陆
 */
function accHomeLogin():bool
{
    if (session_status() !==PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (isset($_SESSION['home_uid']) && isset($_SESSION['home_ucode'])) {
        return dPassword($_SESSION['home_uid'], $_SESSION['home_ucode']);
    } else {
        return false;
    }
}

/**
 * 获取权限组
 * @return string
 */
function getGroups():string
{
//    var_dump($_SERVER['PHP_SELF']);///index.php/admin/login/index"
    if (accLogin()) {
        $u_id = $_SESSION['admin_uid'];
        $sql = "SELECT id,title,status FROM admin_role LEFT JOIN admin_user_role ON r_id=id WHERE u_id={$u_id}";
        if (empty($db)) {
            $db = new Msql();
        }
        $res = $db::mGetRow($sql);
        if ($res) {
            $role_arr = array();
            $role_arr['code'] = 200;
            $role_arr['r_id'] = $res['id'];
            $role_arr['r_name'] = $res['title'];
            if ($res['status'] == 1) {
                return json_encode($role_arr);
            } else {
                return rDatas(false, '该用户组已停用');
            }
        } else {
            return rDatas(false, '角色不存在');
        }
    }else{
        header('location:/index.php/admin/login/index');
    }
}

/**
 * 判断权限
 */
function accessAuth():string
{
    global $my_module;
    global $my_controller;
    global $my_func;
    $cru_url = strtolower($my_module.'/'.$my_controller.'/'.$my_func);
    if ($cru_url == 'admin/login/vcode'){return rDatas(true,'允许访问');}
    $allow_arr = array('admin/index/list','admin/index/welcome','admin/index/info','admin/index/test','admin/login/index','admin/login/loginout');
    if (accLogin()){
        if (in_array($cru_url,$allow_arr)){
            return rDatas(true,'允许访问');
        }
        $u_id = $_SESSION['admin_uid'];
        if (empty($db)) {
            $db = new Msql();
        }
        $sql = "SELECT m.url FROM admin_menu AS m LEFT JOIN admin_role_menu AS rm ON m.id=rm.m_id LEFT JOIN admin_user_role AS ur ON ur.r_id=rm.r_id WHERE ur.u_id={$u_id}";
        $res = $db::mGetAll($sql);
        if ($res) {
            foreach ($res as &$v){
                if ($cru_url == strtolower($v['url'])){
                    return rDatas(true,'允许访问');
                }
            }
            return rDatas(false, '无权限');
        } else {
            return rDatas(false, '非法访问');
        }
    }else{
        if (!in_array($cru_url,array('admin/login/index','admin/login/loginout'))){
            return rDatas(false, '请登录');
        }else{
            return rDatas(true,'允许访问');
        }
    }
}

/**
 * 生成指定长度的随机文件名
 * @param int $length 长度
 *@return string 随机字符串
 */
function randStr(int $length=6,bool $need=true):string
{
    if ($need==false){
        $name='';
    }else{
        $name=uniqid();
    }
    $name .= substr(str_shuffle('abcdefghijglmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789'),0,$length);
    return $name;
}

/**
 * 获取上传文件后缀名
 * @param string $filename 传入的文件名
 * @return string
 */
function getExt(string $filename):string
{
    return strrchr($filename, '.');
}

/**
 * 按日期递归创建文件目录
 * @param string $path 路径
 * @return mixed 成功返回$path 失败返回false
 */
function createDir():string
{
    $path='/upload/'.date('Y/m/d');
    $abs=ROOT_PATH.$path;
    if (is_dir($abs) || mkdir($abs,0777,true)) {
        return $path;
    }else{
        return 'false';
    }
}

/**
 * 等比例生成缩略图 比例不合适两端留白
 * @param string $ori 原图全路径
 * @param int $sw 缩略图的宽
 * @param int $sh 缩略图的高
 * @return string $path 缩略图
 */
function makeThumb(string $opic,int $sw=50,int $sh=50):string
{
    $tname = 'thumb_'.randStr().'.png';
    $opath=dirname($opic).DS.$tname;//缩略图绝对路径
    if (!list($bw,$bh,$type)=getimagesize($opic)) {
        return 'false';
    }
    /**
     * 用数组装载文件类型 1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8
     * = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
    */
    $map=array(
        1=>'imagecreatefromgif',
        2=>'imagecreatefromjpeg',
        3=>'imagecreatefrompng',
    );
    //如果传来的图片不在$map里面，无法处理，返回false
    if (!isset($map[$type])) {
        return 'false';
    }
    //创建原始大画布
    $func=$map[$type];
    $big=$func($opic);//创建小画布
    $small=imagecreatetruecolor($sw, $sh);
    $white=imagecolorallocate($small, 255, 255, 255);
    imagefill($small, 0, 0, $white);
    //计算缩略比
    $rate=min($sw/$bw,$sh/$bh);
    $rw=$bw*$rate;
    $rh=$bh*$rate;
    /**
     * dst_image目标图象连接资源。src_image源图象连接资源。dst_x目标 X 坐标点。dst_y目标 Y 坐标点。src_x源的
     * X 坐标点。src_y源的 Y 坐标点。dst_w目标宽度。dst_h目标高度。src_w源图象的宽度。src_h源图象的高度。
    */
    imagecopyresampled($small, $big, (int)(($sw-$rw)/2), (int)(($sh-$rh)/2), 0, 0, (int)$rw, (int)$rh, $bw, $bh);
    imagepng($small,$opath);
    imagedestroy($small);
    imagedestroy($big);
    return $tname;
}

/**
 * 生成验证码图片
 * @param string $code 验证码
 */
function verifyCode(string $code='666'):bool
{
    //1 创建画布
    $img = imagecreatetruecolor(90, 50);
    //2 创建颜色
    $red = imagecolorallocate($img, 255, 0, 0);
    $gray = imagecolorallocate($img, 200, 200, 200);
    //3 填充颜色
    imagefill($img, 0, 0, $gray);
    //4 水平的画一行字符串 参数: 画布,字体(1-5),str的x轴开始处,str的y轴开始处,str,字符串颜色
//    imagestring ($img , 5 , 10 , 10 , randStr(4,false) , $red);
    //参数：（画布，大小（单位是磅），斜度，文字开始X轴，文字开始Y轴，字体颜色，字体（直接选择本机字体），要画 的文字）
    $font = ROOT_PATH.'/public/admin/fonts/HYShangWeiShouShuW.ttf';
    imagefttext($img, 24, 5, 10, 39, $red, $font, $code);
    $y1=mt_rand(1,50);
    $y2=mt_rand(1,50);
    $x1 = mt_rand(1,90);
    $x2 = mt_rand(1,90);
    $color=mt_rand(1,255);
    //设置线条粗细
    imagesetthickness($img,rand(2,3));
    //划线条，参数:画布,距左上角长度，距离左上角高度，左起点，上起点，颜色
    imageline($img,$x1,$y1,$x2,$y2,$color);
    imageline($img,rand(1,50),rand(1,90),rand(1,50),rand(1,90),$color);
    imageline($img,rand(1,50),rand(1,90),rand(1,50),rand(1,90),$color);
    //5 保存图片 //通知浏览器 接下来输出的是png图片
    header('Content-type:image/png');
    //不加第二个参数 浏览器会将图片的二进制信息输出在浏览器上,它会按照文字来理解这个图片
    imagepng($img);
    // 6 销毁画布
    return imagedestroy($img);
}

/**
 * 文本日志记录
 * @param string $content 内容
 * @param string $filename 文件名
 * @param bool $debug  是否记录日志开关
 */
function writeLog(string $content, string $filename = 'log.log', bool $debug = true):bool
{
    if ($debug) {
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/log/' . $filename;
        $fp = fopen($filename, "a");
        flock($fp, LOCK_EX) ;
        fwrite($fp, "\n执行日期：" . date('Y-m-d H:i:s') . "\n" . $content . "\n");
        flock($fp, LOCK_UN);
        return fclose($fp);
    }
}

/**
 * mysql记录日志
 * @param  string $title   [description]
 * @param  string $content [description]
 * @return bool          [description]
 */
function write_log(string $title,string $content):bool
{
    if (empty($db)){
        $db = new Msql();
    }
    $year = date('Y',time());
    $link =$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $log_time = date("Y-m-d H:i:s",time());
    $ip = getIp();
    $table = 'lulu_'.$year;
    $sql = "CREATE TABLE IF NOT EXISTS `".$table."`(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `time` datetime NOT NULL,
  `link` text NOT NULL,
  `ip` varchar(20) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'ip地址',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;";
    $db::sExec($sql);
    $sql = "insert into `".$table."` values(null,'$title','$content','$log_time','$link','$ip')";
    $res = $db::sExec($sql);
    if($res){
        return true;
    }else{
        return false;
    }
}

/**
 * 异步curl
 * @param string $url
 * @param array $data
 * @param int $timeout 必须设置CUROPT_TIMEOUT为1,CURLOPT_TIMEOUT 默认为0，意思是永远不会断开链接。所以不设置的话，可能因为链接太慢，会把 HTTP 资源用完。
 * @param bool $isProxy
 * @return bool|mixed
 */
function curlData(string $url, array $data=null, int $timeout=1, bool $isProxy=false):string
{
    $curl = curl_init();
    if($isProxy){   //是否设置代理
        $proxy = "127.0.0.1";   //代理IP
        $proxyport = "8080";   //代理端口
        curl_setopt($curl, CURLOPT_PROXY, $proxy.":".$proxyport);
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if(!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache",
                "content-type: application/json",)
        );
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    if ($timeout > 0) { //超时时间秒
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    }
    $output = curl_exec($curl);
    $error = curl_errno($curl);
    curl_close($curl);
    if($error){
        return $error;
    }
    return $output;
}

/**
 * 二维数组归类
 * @param array $arr
 * @param string $pid 父级ID的字段名
 * @return array
 */
function arrTree(array $arr, string $pid='pid'):array
{
    $tem_arr = array();
    foreach ($arr as $k=> $v){
        //出栈
        $temp = $v;
        $tem_arr[$temp[$pid]] = isset($tem_arr[$temp[$pid]])?$tem_arr[$temp[$pid]]:[];
        //入栈
        array_push($tem_arr[$temp[$pid]],$temp);
    }
    return $tem_arr;
}

/**
 * 子元素计数器
 * @param array $array
 * @param string $pid
 * @return array
 */
function arr_children_count(array $array, string $pid):array
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }
    return $counter;
}

