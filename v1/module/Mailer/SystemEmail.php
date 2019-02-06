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

        $base64 = 'https://icargo-public.s3.eu-west-1.amazonaws.com/logo.png';
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