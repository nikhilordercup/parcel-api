<?php
/**
 * Created by PhpStorm.
 * User: roopesh
 * Date: 12/07/18
 * Time: 4:24 PM
 */


class Recurring
{
    public static $modelObj = NULL;

    private $headerMsg = "Icargo Recurring Fail Job Notification";

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    public

    function send($param){
        $trigger_code = "recurring";
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param['rowdata']['company_id'], $trigger_code);
        if(count($notificationData)>0){
           $companyInfo = $this->_getModelInstance()->getUserInfo($param['rowdata']['company_id']);
           $subject_msg = $this->headerMsg;
           $companyadminEmail = 'roopesh.madhesia@ordercup.com';//$companyInfo['email'];
           $companyName = $companyInfo['name'];  
           foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){ 
                   switch($param['rowdata']['recurring_type']){
                    case 'DAILY':
                      $errorExecution = $param['rowdata']['recurring_time'];       
                    break;
                    case 'WEEKLY':
                      $errorExecution = $param['rowdata']['recurring_day'].', '.$param['rowdata']['recurring_time'];         
                    break;
                    case 'MONTHLY':
                      $errorExecution = $param['rowdata']['recurring_month_date'].', '.$param['rowdata']['recurring_time'];        
                    break;
                    case 'ONCE':
                      $errorExecution = $param['rowdata']['recurring_date'].', '.$param['rowdata']['recurring_time'];        
                    break;
                   } 
                   $template_msg = str_replace(array(
                        "__company_name__",
                        "__job_reference__",
                        "__job_type__",
                        "__recurring_type__",
                        "__recurring_execution__",
                        "__Error_message__"
                    ), 
                    array(
                        $companyName,
                        $param['rowdata']['load_identity'],
                        ($param['rowdata']['load_type'] =='SAME')?'SAME DAY':'NEXT DAY',
                        $param['rowdata']['recurring_type'],
                        $errorExecution,
                        $param['returnData']['message']
                    ), $item["template"]);
                    $emailObj = new Notification_Email();
                    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$companyName,"email"=>$companyadminEmail)),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));
                    $notificationHistory = array("trigger_code" => $trigger_code,"route_id"=>0,"shipment_ticket"=>$param['rowdata']['load_identity'],"name"=>$companyName,"email"=>$companyadminEmail,"body"=>$template_msg,"subject"=>$subject_msg);
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