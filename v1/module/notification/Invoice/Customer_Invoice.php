<?php
/**
 * Created by PhpStorm.
 * User: roopesh
 * Date: 12/07/18
 * Time: 4:24 PM
 */

class Customer_Invoice
{
    public static $modelObj = NULL;

    //private $headerMsg = "icargo invoice #2132323232";

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    public

    function send($param,$_sendpdftocustomer,$_sendtocustomemail,$physicalpath){

        // need to work on $is_attachments
        $trigger_code = "invoiceCustomer";
        $custom_trigger_code = "invoiceCustomCustomer";
        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);
        if(count($notificationData)>0){
           $libraryObj = new Library();
            if(count($param['invoiceId'])>0){
               $allInvoices = [];
                foreach($param['invoiceId'] as $invoiceId){
                    $invoiceData    = $this->_getModelInstance()->getInvoiceData($invoiceId);
                    $template_msg   = $this->getTemplate($notificationData,$invoiceData);
                    $subject_msg    = "Your Courier Delivery Invoice #".$invoiceData['invoice_reference'];
                    $allInvoices[]  = $invoiceData['invoice_reference'].'.pdf';
                    if($_sendpdftocustomer){
                      $pdfPath = $physicalpath.$invoiceData['invoice_reference'].'.pdf';
                      $mail_status    = $this->sendMail($invoiceData,$template_msg,$subject_msg,true,$pdfPath);
                      $save_status    = $this->savSendingMailHistory($trigger_code,$invoiceData,$template_msg,$subject_msg,$mail_status);
                     }
                   }

                 if($_sendtocustomemail){
                      $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $custom_trigger_code);
                      $template_msg   = $this->getTemplate($notificationData,$invoiceData);
                      $subject_msg    = "instaDispatch invoice(s)";
                      $zipname        =  date('dmYHms').'.zip';
                      $this->createZip($allInvoices,$physicalpath,$zipname);
                      $mail_status    = $this->sendMail(array('customer_name'=>'custom name','customer_email'=>$_sendtocustomemail,'invoice_reference'=>'MULTIPLE'),$template_msg,$subject_msg,true,$physicalpath.$zipname);
                      $save_status    = $this->savSendingMailHistory($custom_trigger_code,array('customer_name'=>'custom name','customer_email'=>$_sendtocustomemail,'invoice_reference'=>'MULTIPLE'),$template_msg,$subject_msg,$mail_status);
              }

            }
         }

    }

public

  function getTemplate($notificationData,$invoiceData){
     $template_msg = "";
        foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    $template_msg = str_replace(array("__customer_name__","__company_name__","__from_date__","__to_date__"), array($invoiceData["customer_name"], $invoiceData["company_name"], $invoiceData["from"], $invoiceData["to"]), $item["template"]);
                }
            }
      return $template_msg;
    }


public

function sendMail($mail_info,$template_msg,$subject_msg,$is_attachemt,$physicalpath){
    $emailObj = new Notification_Email();
    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$mail_info["customer_name"],"email"=>$mail_info["customer_email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg),$is_attachemt,$physicalpath);

return $status;
}

public

function savSendingMailHistory($trigger_code,$mail_info,$template_msg,$subject_msg,$mail_status){
    $notificationHistory = array(
        "trigger_code" => $trigger_code,
        "route_id"=>0,
        "shipment_ticket"=>$mail_info["invoice_reference"],
        "name"=>$mail_info["customer_name"],
        "email"=>$mail_info["customer_email"],
        "body"=>$template_msg,
        "subject"=>$subject_msg
    );
    if($mail_status["status"]){
        $notificationHistory["status"] = 1;
    }else{
        $notificationHistory["status"] = 0;
    }
  $status = $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
  return $status;
 }

public

function createZip($files,$physicalpath,$zipname){
        $zip = new ZipArchive;
        if(count($files)>0){
            if($zip->open($physicalpath.$zipname, ZipArchive::CREATE) === TRUE)
                {
                  foreach($files as $file){
                      $zip->addFile($physicalpath.$file,'invoices/'.$file);
                   }
                  $zip->close();
                }
         }
   return true;
    }
}
