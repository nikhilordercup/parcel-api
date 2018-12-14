<?php
class Courier_Signup{

    public static $modelObj = NULL;

    private $headerMsg = "Courier Signup";  

    private $recepientEmail = array(
        array(
            "name"=>"Nikhil",
            "email"=>"nikhil.kumar@ordercup.com"
        ),
        array(
            "name"=>"Deepak",
            "email"=>"deepak.sethi@perceptive-solutions.com"
        )
    );

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    public

    function send($param){
        $trigger_code = "courierSignup";

        $notificationData = $this->_getModelInstance()->getSignupNotification($trigger_code);

        $userInfo = $this->_getModelInstance()->findNewUserInfo($param);


        if(count($notificationData)>0){
            $subject_msg = $this->headerMsg;

            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    $template_msg = str_replace(array("__inquiry_name__","__inquiry_phone__","__inquiry_email__"), array($userInfo["name"], $userInfo["phone"], $userInfo["email"]), $item["template"]);

                    $emailObj = new Notification_Email();

                    $status = $emailObj->sendMail(array(
                        "recipient_name_and_email"=>$this->recepientEmail,
                        "template_msg"=>$template_msg,
                        "subject_msg"=>$subject_msg
                        )
                    );

                    $notificationHistory = array("trigger_code" => $trigger_code,"route_id"=>"0","shipment_ticket"=>"N/A","name"=>$this->recepientEmail[0]["name"],"email"=>$this->recepientEmail[0]["email"],"body"=>$template_msg,"subject"=>$subject_msg);
                    
                    if($status["status"]){
                        $notificationHistory["status"] = 1;
                    }else{
                        $notificationHistory["status"] = 0;
                    }
                    $status = $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
                }
            }
        }
    }
}
?>
