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
        $html=$twig->render('welcome.html',['logo'=>$base64,'name'=>$name,'email'=>$email]);
        $mailer->loadConfig('WELCOME')
            ->setMailContent('Welcome To Icargo',$html)
            ->send([$email=>$name]);
    }
    public function sendSignUpTrailEmail($name,$email,$planInfo,$subscriptionInfo){
        $mailer=new Email();
        $loader = new \Twig_Loader_Filesystem(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR);
        $twig = new \Twig_Environment($loader);

        $base64 = 'https://icargo-public.s3.eu-west-1.amazonaws.com/logo.png';
        $html=$twig->render('subscription.html',['name'=>$name,'email'=>$email,
            'plan_name'=>$planInfo['plan_name'],'charge'=>$planInfo['price'],
            'start_date'=>date('Y-m-d'),'end_date'=>$subscriptionInfo->trial_end]);
        $mailer->loadConfig('SUBSCRIPTION')
            ->setMailContent('Trial Information',$html)
            ->send([$email=>$name]);
    }
    public function sendSetUpGuideEmail($name,$email){
        $mailer=new Email();
        $loader = new \Twig_Loader_Filesystem(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR);
        $twig = new \Twig_Environment($loader);

        $base64 = 'https://icargo-public.s3.eu-west-1.amazonaws.com/logo.png';
        $html=$twig->render('setup_assistance.html',['name'=>$name,'email'=>$email]);
        $mailer->loadConfig('SETUP_GUIDE')
            ->setMailContent('Setup Guide',$html)
            ->send([$email=>$name]);
    }
    public function sendSubscriptionCancelEmail($name,$email,$subId){
        $mailer=new Email();
        $loader = new \Twig_Loader_Filesystem(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR);
        $twig = new \Twig_Environment($loader);

        $base64 = 'https://icargo-public.s3.eu-west-1.amazonaws.com/logo.png';
        $html=$twig->render('subscription_cancel.html',['name'=>$name,'email'=>$email,
            'from'=>date('Y-m-d'),'subscription_id'=>$subId,'link'=>UI_BASE_URL]);
        $mailer->loadConfig('SUBSCRIPTION_CANCEL')
            ->setMailContent('Subscription Canceled',$html)
            ->send([$email=>$name]);
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