<?php
class Notification_Index extends Icargo{

    public

    function __construct($data)
    {
        $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj = new Notification_Model_Index();

        $this->trigger_type = array("sms","email","webhook");
    }

    public

    function saveTemplate($param)
    {
        $messages = array();
        $message = "Requested %s Notification %s %s";
        $errorCounter = 0;

        $this->modelObj->startTransaction();

        foreach($this->trigger_type as $item){
            $param->trigger_type = $item;
            $template = $this->modelObj->getTemplate($param);

            $key = $item."Editor";
            $param->template = (!empty($param->$key)) ? $param->$key : "";
            if($template){
                //update template
                $status = $this->modelObj->updateTemplate($param);
                if($status){
                    array_push($messages, array("status"=>"success","message"=>sprintf($message, ucwords($item), "Updated","Successfully")));
                }else{
                    array_push($messages, array("status"=>"error","message"=>sprintf($message, ucwords($item), "Not","Updated")));
                    $errorCounter++;
                }
            }else{
                //save template
                $status = $this->modelObj->saveTemplate($param);
                if($status){
                    array_push($messages, array("status"=>"success","message"=>sprintf($message, ucwords($item), "Saved","Successfully")));
                }else{
                    array_push($messages, array("status"=>"error","message"=>sprintf($message, ucwords($item), "Not","Saved")));
                    $errorCounter++;
                }
            }
        }

        if($errorCounter==0){
            $this->modelObj->commitTransaction();
            return array("status"=>"success","message"=>"Requested Notification Updated Successfully");
        }else{
            $this->modelObj->rollBackTransaction();

            foreach($messages as $key =>$item)
                if($item["status"]=="success")
                    unset($messages[$key]);

            return array("status"=>"error","message"=>$messages);
        }
    }

    public

    function updateNotificationStatus($param){
        $message = "";
        if(empty($param->status))
            $param->status = 0;

        $template = $this->modelObj->getTemplate($param);
        if($template){
            $status = $this->modelObj->updateStatus($param);
            if($status){
                $message = "Requested Trigger Updated Successfully";
            }else{
                $message = "Requested Trigger Not Updated";
            }
        }else{
            $status = $this->modelObj->saveStatus($param);
            if($status){
                $message = "Requested Trigger Saved Successfully";
            }else{
                $message = "Requested Trigger Not Saved";
            }
        }
        if($status){
            return array("status"=>"success","message"=>$message);
        }else{
            return array("status"=>"error","message"=>$message);
        }
    }

    public

    function getNotificationStatus($param){
        if(isset($param->trigger_code)){
            $data = $this->modelObj->getNotificationStatusByTriggerCode($param);
        }else{
            $data = $this->modelObj->getNotificationStatus($param);
        }

        foreach($data as $key=>$item){
            $data[$key]["item_key"] = $item["trigger_code"].ucwords($item["trigger_type"]);
        }
        return array("status"=>"success","data"=>$data);
    }
}
?>