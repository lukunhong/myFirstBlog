<?php
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
        require_once ROOT_PATH.'/extends/PHPMailer/PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer;
        //使用smtp鉴权方式发送邮件
        $mail->isSMTP();
        //smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = true;
        // qq 邮箱的 smtp服务器地址，这里当然也可以写其他的 smtp服务器地址
        $mail->Host = 'smtp.qq.com';
        //smtp登录的账号 这里填入字符串格式的qq号即可
        $mail->Username = '990527551@qq.com';
        // 这个就是之前得到的授权码，一共16位
        $mail->Password = 'mpxndcyibelzbbib';
        $mail->setFrom('990527551@qq.com', 'Lukunhong');
        // $to 为收件人的邮箱地址，如果想一次性发送向多个邮箱地址，则只需要将下面这个方法多次调用即可
        $mail->addAddress($email);
        // 该邮件的主题
        $mail->Subject = $subject;
        // 该邮件的正文内容
        $mail->Body = $message;
        // 使用 send() 方法发送邮件
        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return 'success';
        }
    }
}