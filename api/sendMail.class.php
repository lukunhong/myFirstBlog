<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
/**
 * Created by PhpStorm.
 * User: lulu
 */
class sendMail
{

    /**
     * 发送邮件
     * @param string $email
     * @param string $subject
     * @param string $message
     * @return string
     */
    public function send(string $email,string $subject,string $message):string
    {
        // 这个PHPMailer 就是之前从 Github上下载下来的那个项目
//        require_once ROOT_PATH.'/extends/PHPMailer/PHPMailer/PHPMailerAutoload.php';
//        $mail = new PHPMailer;
//        //使用smtp鉴权方式发送邮件
//        $mail->isSMTP();
//        //smtp需要鉴权 这个必须是true
//        $mail->SMTPAuth = true;
//        // qq 邮箱的 smtp服务器地址，这里当然也可以写其他的 smtp服务器地址
//        $mail->Host = 'smtp.qq.com';
//        //smtp登录的账号 这里填入字符串格式的qq号即可
//        $mail->Username = '990527551@qq.com';
//        // 这个就是之前得到的授权码，一共16位
//        $mail->Password = '';
//        $mail->setFrom('990527551@qq.com', 'Lukunhong');
//        // $to 为收件人的邮箱地址，如果想一次性发送向多个邮箱地址，则只需要将下面这个方法多次调用即可
//        $mail->addAddress($email);
//        // 该邮件的主题
//        $mail->Subject = $subject;
//        // 该邮件的正文内容
//        $mail->Body = $message;
//        // 使用 send() 方法发送邮件
//        if(!$mail->send()) {
//            return $mail->ErrorInfo;
//        } else {
//            return 'success';
//        }
        require_once ROOT_PATH.'/extends/PHPMailer-master/src/Exception.php';
        require_once ROOT_PATH.'/extends/PHPMailer-master/src/PHPMailer.php';
        require_once ROOT_PATH.'/extends/PHPMailer-master/src/SMTP.php';

        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.qq.com';                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = '990527551@qq.com';                // SMTP 用户名  即邮箱的用户名
            $mail->Password = '';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom('990527551@qq.com', 'Lukunhong');  //发件人
            $mail->addAddress($email);  // 收件人
            //$mail->addAddress('ellen@example.com');  // 可添加多个收件人
            $mail->addReplyTo('990527551@qq.com', 'info'); //回复的时候回复给哪个邮箱 建议和发件人一致
            //$mail->addCC('cc@example.com');                    //抄送
            //$mail->addBCC('bcc@example.com');                    //密送
            //发送附件
            // $mail->addAttachment('../xy.zip');         // 添加附件
            // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名
            //Content
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = $subject ;
            $mail->Body    = "<h1>{$message}</h1>" . date('Y-m-d H:i:s');
            $mail->AltBody = $message;//如果邮件客户端不支持HTML则显示此内容
            if(!$mail->send()) {
                return $mail->ErrorInfo;
            } else {
                return 'success';
            }
        } catch (Exception $e) {
            return '邮件发送失败: '.$mail->ErrorInfo;
        }
    }
}