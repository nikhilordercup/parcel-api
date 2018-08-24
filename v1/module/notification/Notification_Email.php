<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/03/18
 * Time: 7:24 PM
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require '../vendor/autoload.php';

Class Notification_Email{

    public $mail = array();

    public

    function sendMail($param,$is_attachemt=null,$attachemt_physical_path=null){
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            //Server settings
            $mail->SMTPDebug = 0;                                  // Enable verbose debug output
            $mail->isSMTP();                                       // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';                        // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'tracking@instadispatch.com';       // SMTP username
            $mail->Password = 'T2N>Nn6B';                          // SMTP password
            $mail->SMTPSecure = 'tls';                             // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;                                     // TCP port to connect to

            //Recipients
            $mail->setFrom('nishant.v@perceptive-solutions.com', 'Icargo');

            foreach($param["recipient_name_and_email"] as $item){
                if(isset($item["name"]) and !empty($item["name"]) and isset($item["email"]) and !empty($item["email"])){
                    $mail->addAddress($item["email"], $item["name"]);
                }else{
                    $mail->addAddress($item["email"]);
                }
            }
            //$mail->addAddress($param["customer_email"], $param["customer_name"]);     // Add a recipient

            //$mail->addAddress('ellen@example.com');               // Name is optional
            //$mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');
            if($is_attachemt){
                $mail->addAttachment($attachemt_physical_path);         // Add attachments
            }
            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $param["subject_msg"];
            $mail->Body    = $param["template_msg"];
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            return array("status"=>true, "message"=>"email sent");
        } catch (Exception $e) {
            return array("status"=>false, "message"=>$mail->ErrorInfo);
        }
    }
}