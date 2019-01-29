<?php
final class Commonservices extends Booking
{   public $restModel = null;
    public function __construct(){
      $this->restModel         = new restservices_Model(); 
    }
    public function getMergeRecords($sameRecords,$nextRecords){
         $returnService = array();
        if($sameRecords['status']=='success' and $sameRecords['rate']['quotation_ref'] !=''){
            if(!key_exists('services',$sameRecords['rate'])){
               $sameRecords['rate']['services'] = array(); 
            }
            if(!empty($nextRecords) and $nextRecords['status']=='success' and  count($nextRecords['rate']['services'])>0){
                foreach($nextRecords['rate']['services'] as $value){
                    $sameRecords['rate']['services'][] = $value;
                }
            }
            $returnService = $sameRecords;
        }else{
             $returnService = $nextRecords;
        }
      return $returnService;
  
    }
    public function getRequestedQuotationInfo($records){
       if(!isset($records->quation_reference) || ($records->quation_reference=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'quation reference is missing'; 
                 $response["error_code"] = "ERROR00C43";
                 return $response;
        }elseif(!isset($records->service_code) || ($records->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code is missing';
                 $response["error_code"] = "ERROR00C44";
                 return $response;
        }elseif(!isset($records->act_number) || ($records->act_number=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'account number is missing';
                 $response["error_code"] = "ERROR00C544";
                 return $response;
        }elseif(!isset($records->carrier_code) || ($records->carrier_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'carrier code is missing';
                 $response["error_code"] = "ERROR00C644";
                 return $response;
        }else{
           $quotationData =  $this->restModel->getQuotationData($records->quation_reference);
             if($quotationData==''){
                 $response = array();
                 $response["status"]        = "fail";
                 $response["message"]       = 'quotation code is missmatch or expired';
                 $response["error_code"]    = "ERROR00C60";
                 return $response;
           }else{
            $serviceId = $this->restModel->getCustomerCarrierDataByServiceCodeAndAccontCarrier($records->customer_id,$records->service_code,$records->company_id,$records->act_number,$records->carrier_code);
            $preResponse     = json_decode($quotationData['response'],1);
            $preRequest      = json_decode($quotationData['request'],1);
            $preServiceKeys  = array_keys((array)$preResponse);
            if($records->customer_id != $preRequest['customer_id']){
                 $response = array();
                 $response["status"]        = "fail";
                 $response["message"]       = 'Token mismatched';
                 $response["error_code"]    = "ERROR00C151";
                 return $response;
            }elseif(!in_array($serviceId['service_id'],$preServiceKeys)){ 
                 $response = array();
                 $response["status"]        = "fail";
                 $response["message"]       = 'service code is missmatch with quotation';
                 $response["error_code"]    = "ERROR00C51";
                 return $response;
            }else{
                 $response = array();
                 $response["status"]         = "success";
                 $response["job_type"]       = $preResponse[$serviceId['service_id']]['job_type'];
                 $response["job_data"]       = $preResponse[$serviceId['service_id']];
                 $response["request_data"]   = $preRequest;
                 return $response;
             }
           }
        }
      }
    
    public function getSeviceInfo($records){
       if(!isset($records->service_code) || ($records->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code is missing';
                 $response["error_code"] = "ERROR00C144";
                 return $response;
        }elseif(!isset($records->act_number) || ($records->act_number=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'account number is missing';
                 $response["error_code"] = "ERROR00C514";
                 return $response;
        }elseif(!isset($records->carrier_code) || ($records->carrier_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'carrier code is missing';
                 $response["error_code"] = "ERROR00C614";
                 return $response;
        }else{
                 //$serviceId = $this->restModel->getCustomerCarrierDataByServiceCode($records->customer_id,$records->service_code,$records->company_id);
           $serviceId = $this->restModel->getCustomerCarrierDataByServiceCodeAndAccontCarrier($records->customer_id,$records->service_code,$records->company_id,$records->act_number,$records->carrier_code);
                  
           if(is_array($serviceId)){
                     $response = array();
                     $response["status"]         = "success";
                     $response["job_type"]       = $serviceId['service_type'];
                     $response["service_id"]       = $serviceId['service_id'];
                     return $response;
                 }else{
                     $response = array();
                     $response["status"] = "fail";
                     $response["message"] = 'service code is not match';
                     $response["error_code"] = "ERROR00C124";
                     return $response;
                 }
               
                 
        }
      }
    
}
?>