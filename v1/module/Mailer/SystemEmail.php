<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 05-01-2019
 * Time: 09:03 AM
 */

namespace v1\module\Mailer;


class SystemEmail
{
    public function sendWelcomeEmail($name,$email){
        $mailer=new Email();
        $loader = new \Twig_Loader_Filesystem(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR);
        $twig = new \Twig_Environment($loader);
        $path = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'logo.png';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        $html=$twig->render('welcome.html',['logo'=>$base64,'name'=>$name]);
        $mailer->loadConfig('WELCOME')
            ->setMailContent('Welcome To Icargo',$html)
            ->send([$email=>$name]);
    }
    public function sendSignUpTrailEmail(){

    }
    public function sendTrialExpiryReminderEmail(){

    }
    public function sendPaymentFailEmail(){

    }
    public function sendPaymentSuccessEmail(){

    }
    public function sendBillingReminderEmail(){

    }
    public function sendEmailVerifyEmail(){

    }
}