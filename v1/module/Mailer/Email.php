<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-01-2019
 * Time: 11:40 AM
 */

namespace v1\module\Mailer;


use PHPMailer\PHPMailer\PHPMailer;
use v1\module\Database\Model\EmailConfigModel;

class Email extends PHPMailer
{

    /**
     * Email constructor.
     * @param $exception bool
     */
    public function __construct($exception = true)
    {
        parent::__construct($exception);
    }

    /**
     * @param $emailType
     * @param int $companyId
     * @return $this
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function loadConfig($emailType, $companyId = 0)
    {
        $config = EmailConfigModel::all()
            ->where('company_id', '=', $companyId)
            ->where('mail_type', '=', $emailType)
            ->where('status', '=', 'Active')
            ->first();
        if ($config->is_smtp == 'Yes') {
            $this->isSMTP();                                      // Set mailer to use SMTP
            $this->Host = $config->host;  // Specify main and backup SMTP servers
            $this->SMTPAuth = true;                               // Enable SMTP authentication
            $this->Username = $config->username;                 // SMTP username
            $this->Password = $config->password;                           // SMTP password
            $this->SMTPSecure = strtolower($config->ssl_type);                            // Enable TLS encryption, `ssl` also accepted
            $this->Port = $config->port;                                    // TCP port to connect to

        }else{
            $this->isMail();
        }
        $this->isHTML($config->mail_content_type == 'Html');
        $this->setFrom($config->from_email, $config->from_name);
        return $this;
    }
    public function setMailContent($subject,$message,$attachments=[]){
        $this->Subject=$subject;
        $this->Body=$message;
        $this->AltBody=strip_tags($message);
        foreach ($attachments as $attachment){
            $this->addAttachment($attachment);
        }
        return $this;
    }
    public function send($to=[],$bcc=[],$cc=[]){
        foreach ($to as $email=>$name){
            $this->addAddress($email,$name);
        }
        foreach ($bcc as $email=>$name){
            $this->addBCC($email,$name);
        }
        foreach ($cc as $email=>$name){
            $this->addCC($email,$name);
        }
        parent::send();
        return $this;
    }

}