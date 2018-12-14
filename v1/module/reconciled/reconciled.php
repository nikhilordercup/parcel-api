<?php  require __DIR__ . '/vendor/autoload.php';
class Module_Reconciled_Reconciled extends Icargo{
    public $reconciledModelObj = null;
    
    public $path = null;
    
    public $modelObj = null; 
    
    public $allShipObj = null;   
    public $carrierObj = null;   
    public $paramData = null; 
    public $fileUpload = null; 
    public $bufferAmt = null; 
   
        
    public  function __construct($data){
	    $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->reconciledModelObj   = Reconciled_Model::_getInstance();
        $this->modelObj  = AllShipment_Model::getInstanse();
	}
    
    public function setReconsiledData($param){
          $resultArray = array();
          $param->applyonaccount = (isset($param->applyonaccount))?$param->applyonaccount:false;
          if(count($param->data)>1){
          foreach($param->data as $key=>$valueData){
           if($key==0){ 
            $resultArray[] = array_keys((array)$valueData);
            $resultArray[] = array_values((array)$valueData);
           }else{
             $resultArray[] = array_values((array)$valueData);
           }
        }
          $param->selectedCarrier = json_decode($param->selectedCarrier);
          $uploadStatus = $this->createCsvFile($resultArray,$param->filename,$param->selectedCarrier->name,'request');
          if($uploadStatus['status']){
             $this->carrierObj =  call_user_func($param->selectedCarrier->name. '::getInstance');
             if(!$this->carrierObj->validHeader($resultArray[0])){
                 return array("status"=>"error", "message"=>"Please upload carrier (".$param->selectedCarrier->name.") csv "); 
             }else{ 
                 $this->paramData = $param; 
                 $this->fileUpload = $uploadStatus; 
                 shell_exec('php '.$this->startReconsiled()); 
                 return array("status"=>"success", "message"=>"Uploading csv has been done!!");
               }
            }
           else{
            return array("status"=>"error", "message"=>"Please upload csv with header and data");   
          }
       }
    }
    
    public function startReconsiled(){
         $param =  $this->paramData;
         $uploadStatus =  $this->fileUpload;
         $shipmentData = $this->carrierObj->getCsvData($param->data);
         $rowdata = array();
         $isEligibleCounter = 0;
         foreach($shipmentData as $shipkey=>$shipval){
            $jobstatus =   $this->checkCarrierPriceAndApply($shipkey,$shipval,$param->selectedCarrier->courier_id,$param->company_id,$param->user,$param->applyonaccount);
            $rowdata =  $rowdata + $jobstatus['data']['row'];
            if($jobstatus['isEligible'] == 'YES'){
              $isEligibleCounter++;
            }
         }
         if(count($rowdata)>0){
            $csvData     =  $this->carrierObj->getCarrierCsvData($uploadStatus['fullpath'],$rowdata);
            $responceCSV = $this->createCsvFile($csvData,$param->filename,$param->selectedCarrier->name,'response');
            $data = array();
            $data['total_requested_shipment'] = count($shipmentData);
            $data['total_eligible_shipment'] = $isEligibleCounter;
            $data['processed_date']     = date("Y-m-d");
            $data['apply_with_account'] = ($param->applyonaccount)?'YES':'NO';
            $data['requested_csv_path'] = $uploadStatus['file'];
            $data['responded_csv_path'] = $responceCSV['file'];
            $data['carrier'] = $param->selectedCarrier->courier_id;
            $data['company_id'] = $param->company_id;
            $data['status'] = '1';
            $this->reconciledModelObj->addContent('reconciled_reports', $data);
            //return array("status"=>"success", "message"=>"Uploading csv has been done!!");
        }
        
    }
    
    public function createCsvFile($data,$file,$carrier,$mode){
        try{
            $file = str_replace('.csv','__'.date('Ymd').rand(300,3000).'.csv',$file);
            $this->path  =  realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/reconciled/'.strtolower($carrier);
            if (!is_dir($this->path)) {
                mkdir($this->path,0777); 
                mkdir($this->path.'/request',0777); 
                mkdir($this->path.'/response',0777); 
            }
            $storagepathPdf = $this->path.'/'.$mode.'/';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.   $file.'"');
            $fp = fopen($storagepathPdf.$file, 'w');
            foreach ($data as $line ) {
                fputcsv($fp, $line);
            }
            fclose($fp); 
            return array('file'=>$file,'status'=>true,'fullpath'=>$storagepathPdf.$file);
        }catch(ERROR $e){
            return array('file'=>'','status'=>false);
        }
           
        
    } 
    
    public function checkCarrierPriceAndApply($trackingNumber,$serviceDetails,$carrier,$companyId,$user,$applyonaccount){ 
        $loadIdentity = $this->modelObj->getLoadIdentity($trackingNumber,$carrier,$companyId);
        $isEligibleForReconciled = 'NO';
        if(!is_array($loadIdentity)){
            $isEligibleForReconciled = 'NO';
            $return = array('row'=>array($serviceDetails['service']['rownumber']=>array(array("status"=>"error","message"=>"Shipment not found in our records for selected carrier","localprice"=>"","requestedprice"=>"","taxinfo"=>"","tracking"=>$trackingNumber))));
           
        }else{
            $isEligibleForReconciled = 'NO';
            if($loadIdentity['tracking_code'] === 'CANCELLED'){
                $return =  array('row'=>array($serviceDetails['service']['rownumber']=>array(array("status"=>"error","message"=>"Shipment are CANCELLED in our records for selected carrier","localprice"=>"","requestedprice"=>"","taxinfo"=>"","tracking"=>$trackingNumber))));
            }else{
                $this->bufferAmt       = $this->reconciledModelObj->getCompanyReconciledBuffer($companyId);
                $getLastPriceVersion   = $this->modelObj->getShipmentPriceDetails($loadIdentity['load_identity']);
                $priceVersion          = $getLastPriceVersion['price_version'];
                $getLastPriceBreakdown = $this->modelObj->getShipmentPricebreakdownDetailsReconciled($loadIdentity['load_identity'],$priceVersion);
                $data                  = $this->comparePrice($serviceDetails,$getLastPriceBreakdown);
                $eligibleForReconciled = $this->checkEligibleForReconciled($serviceDetails,$data,$trackingNumber);
                if(!empty($eligibleForReconciled)){
                     $isEligible            = $this->getEligibleForReconciled($eligibleForReconciled);
                     $isEligibleForReconciled = 'YES';
                     if($isEligible == 'YES' && $applyonaccount ){
                     $paramdata             = $this->prepareDataforReconciled($loadIdentity['load_identity'],$data,$carrier,$companyId,$user);
                     $consiledStatus        = $this->applyPriceChanges($getLastPriceBreakdown,$paramdata,$priceVersion,
                                                                $getLastPriceVersion,$getLastPriceVersion['customer_id'], $carrier);
                     } 
                     $return = $eligibleForReconciled;
                 }else{
                    $return = array('row'=>array($serviceDetails['service']['rownumber']=>array(array("status"=>"error","message"=>"no  mismatch found in our records for selected carrier","localprice"=>"","requestedprice"=>"","taxinfo"=>"","tracking"=>$trackingNumber))));
                 }
               }
        }
     return array('data'=>$return,'isEligible'=>$isEligibleForReconciled);   
    }
    
    
    public function getEligibleForReconciled($eligibleForReconciled){
        $temparray = array();
        foreach($eligibleForReconciled['row'] as $data){
           foreach($data as $inKey=>$innerData){ 
            if($inKey!=='taxinfo'){
                array_push($temparray,$innerData['status']);  
             }
           }
        }
        if(in_array('success',$temparray)){
            return 'YES';
        }else{
            return 'NO';
        }
    }
    
    public function checkEligibleForReconciled($serviceDetails,$data,$trackingNumber){ 
        $returnArray = array();
        $serviceDiff = bcsub($data['service']['reconciled']['nettcharge'], $data['service']['localdata']['baseprice'],2);
        $totalDiff = 0;
        if($serviceDiff >0 || $serviceDiff <0){
           $diff = ($data['service']['reconciled']['nettcharge'] - $data['service']['localdata']['baseprice']);
           $totalDiff += $diff;
            $returnArray['row'][$data['service']['reconciled']['rownumber']][] = array("status"=>"success", "message"=>"mismatch found in ".$data['service']['reconciled']['operation_code']." with $diff in service ","localprice"=>$data['service']['localdata']['baseprice'],"requestedprice"=>$data['service']['reconciled']['nettcharge'],"taxinfo"=>""  ,"tracking"=>$trackingNumber); 
        }
        if(is_array($data['surcharges']) && count($data['surcharges'])>0){
            foreach($data['surcharges'] as $nkey=>$nval){
               if(($nval['reconciled']['nettcharge'] != $nval['localdata']['baseprice'])){
                 $diff = ($nval['reconciled']['nettcharge'] - $nval['localdata']['baseprice']);
                 $totalDiff += $diff;
                 $returnArray['row'][$nval['reconciled']['rownumber']][] = array("status"=>"success", "message"=>"mismatch found in ".$nval['reconciled']['operation_code']." with $diff ","localprice"=>$nval['localdata']['baseprice'],"requestedprice"=>$nval['reconciled']['nettcharge'],"taxinfo"=>""  ,"tracking"=>$trackingNumber); 
             }
           }
        }
        if(is_array($data['newsurcharges']) && count($data['newsurcharges'])>0){
            foreach($data['newsurcharges'] as $keyNewSur => $valNewSur){
                $diff = ($data['newsurchargesprice'][$keyNewSur]);
                $totalDiff += $diff;
                $returnArray['row'][$data['newsurchargerow'][$keyNewSur]][] = array("status"=>"success", "message"=>"new surcharge found as ".$valNewSur." with difference $diff ","localprice"=>0,"requestedprice"=>$data['newsurchargesprice'][$keyNewSur],"taxinfo"=>"","tracking"=>$trackingNumber); 
           } 
        }
        if($data['tax']['reconciled'] != $data['tax']['localdata']['price']){
           $taxdiff = ($data['tax']['reconciled'] - $data['tax']['localdata']['price']);
           $totalDiff += $taxdiff;
            if(!empty($returnArray)){
               $returnArray['row'][$data['service']['reconciled']['rownumber']]['taxinfo'][] = "tax difference found with $taxdiff in tax head";
           }
        }
        if($totalDiff>0){
          $totalDiff =   bcsub($totalDiff,$this->bufferAmt,2);
          if($totalDiff >0) {
              return $returnArray;
          }else{
             return array(); 
          }   
         }else{
           $totalDiff = bcadd($totalDiff,$this->bufferAmt,2);
           if($totalDiff>0){
               return array();
           }else{
               return $returnArray;
           }
         }
      }
    public function applyPriceChanges($getLastPriceBreakdown,$param,$priceVersion,$getLastPriceVersion,$customerId, $carrierId){
        foreach ($getLastPriceBreakdown as $key => $val) {
            if (array_key_exists($val['id'], $param['data'])) {
                    $val['show_for']          = 'B';
                    $data                     = $this->calculateNewPrice($val, $param['data'][$val['id']]);
                    $val['ccf_price']         = $data['ccf_price'];
                    $val['baseprice']         = $data['baseprice'];
                    $val['price']             = $data['price'];
                    $val['version']           = $priceVersion + 1;
                    $val['apply_to_customer'] = 'YES';
                    $val['version_reason']    = 'RECONCILED';
                    $val['inputjson']         = json_encode($param);
                    unset($val['id']);
                    $records[] = $val;
              
            } else {
                $val['version'] = $priceVersion + 1;
                unset($val['id']);
                $val['version_reason'] = 'RECONCILED';
                $val['inputjson']      = json_encode($param);
                $records[]             = $val;
            }
        }
        if (isset($param['data']['newsurcharges']) and count($param['data']['newsurcharges']) > 0) {
            foreach ($param['data']['newsurcharges'] as $key => $surchargeId) {
                $surcharge_code = $this->modelObj->getSurchargeCodeBySurchargeId($surchargeId, $param['company_id']);
                $price          = $param['data']['newsurchargesprice'][$key];
                if ($param['applypriceoncustomer'] == 'YES') {
                    $surchargeCcf = $this->modelObj->getCcfOfCarrierSurcharge($surchargeId, $param['company_id'], $customerId, $carrierId);
                     if ($surchargeCcf) {
                        if (isset($surchargeCcf["customer_carrier_surcharge_ccf"]) and $surchargeCcf["customer_carrier_surcharge_ccf"] > 0 and $surchargeCcf["customer_carrier_surcharge_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_carrier_surcharge_ccf"], $surchargeCcf["customer_carrier_surcharge_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 1", $surchargeCcf["surcharge_id"]);
                        } elseif (isset($surchargeCcf["customer_carrier_surcharge"]) and $surchargeCcf["customer_carrier_surcharge"] > 0 and $surchargeCcf["customer_carrier_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_carrier_surcharge"], $surchargeCcf["customer_carrier_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 2", $surchargeCcf["surcharge_id"]);
                        } elseif (isset($surchargeCcf["customer_surcharge"]) and $surchargeCcf["customer_surcharge"] > 0 and $surchargeCcf["customer_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_surcharge"], $surchargeCcf["customer_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 3", $surchargeCcf["surcharge_id"]);
                        } elseif (isset($surchargeCcf["company_carrier_surcharge_ccf"]) and $surchargeCcf["company_carrier_surcharge_ccf"] > 0 and $surchargeCcf["company_carrier_surcharge_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["company_carrier_surcharge_ccf"], $surchargeCcf["company_carrier_surcharge_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 4", $surchargeCcf["surcharge_id"]);
                        } elseif (isset($surchargeCcf["company_carrier_ccf"]) and $surchargeCcf["company_carrier_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["company_carrier_ccf"], $surchargeCcf["company_carrier_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 5", $surchargeCcf["surcharge_id"]);
                        }
                    } 
                     else {
                        $customerCcf = $this->modelObj->getSurchargeOfCarrier($customerId, $param['company_id'], $carrierId);
                        if (isset($customerCcf["customer_surcharge_value"]) and $customerCcf["customer_surcharge_value"] > 0 and $customerCcf["company_ccf_operator_surcharge"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["customer_surcharge_value"], $customerCcf["company_ccf_operator_surcharge"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 2", $surchargeId);
                        } elseif (isset($customerCcf["customer_surcharge"]) and $customerCcf["customer_surcharge"] > 0 and $customerCcf["customer_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["customer_surcharge"], $customerCcf["customer_operator"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 3", $surchargeId);
                        } elseif (isset($customerCcf["company_carrier_ccf"]) and $customerCcf["company_carrier_operator"] != 'NONE') {
                            $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["company_carrier_ccf"], $customerCcf["company_carrier_operator"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 5", $surchargeId);
                        }
                    }
                        $tempdata                      = array();
                        $tempdata['price_code']        = $surcharge_ccf_price['surcharge_id'];//$surcharge_ccf_price['company_surcharge_code'];
                        $tempdata['price']             = $surcharge_ccf_price['price'] + $price;
                        $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                        $tempdata['shipment_type']     = '';
                        $tempdata['version']           = $priceVersion + 1;
                        $tempdata['api_key']           = 'surcharges';
                        $tempdata['ccf_operator']      = $surcharge_ccf_price['operator'];
                        $tempdata['ccf_value']         = $surcharge_ccf_price['surcharge_value'];
                        $tempdata['ccf_level']         = $surcharge_ccf_price['level'];
                        $tempdata['baseprice']         = $price;
                        $tempdata['ccf_price']         = $surcharge_ccf_price['price'];
                        $tempdata['surcharge_id']      = 0;
                        $tempdata['service_id']        = '0';
                        $tempdata['apply_to_customer'] = 'YES';
                        $tempdata['show_for']          = 'B';
                        $tempdata['version_reason']    = 'RECONCILED';
                        $tempdata['inputjson']         = json_encode($param);
                        $records[]                     = $tempdata;
                  } 
            }
        }
        
        $isInvoiced            = $getLastPriceVersion['isInvoiced'];
        $oldGrandTotal         = $getLastPriceVersion['grand_total'];
        if($param['applypriceoncustomer'] == 'YES') {
            $temp                                  = array();
            $temp['price_update_applyto_customer'] = 'YES';
            $temp['version_reason']                = 'RECONCILED';
            $temp['price_version']                 = $priceVersion + 1;
            $temp['surcharges']                    = 0;
            $temp['taxes']                         = 0;
            $temp['total_price']                   = 0;
            $newPriceComponent                     = array();
            $taxPrice                              = $this->getTaxPrice($records);
            foreach ($records as $key => $data) {
                if ($data['api_key'] == 'service') {
                    $temp['base_price']               = $data['baseprice'];
                    $temp['courier_commission_value'] = $data['ccf_price'];
                    $temp['total_price']              = $data['price'];
                } elseif ($data['api_key'] == 'taxes') {
                    $data['price']     = $taxPrice['tax_amt'];
                    $data['baseprice'] = $taxPrice['base_price'];
                    $data['ccf_price'] = $taxPrice['tax_amt'];
                    $temp['taxes']     = $data['price'];
                } else {
                    if ($data['apply_to_customer'] != 'NO') {
                        $temp['surcharges'] += $data['price'];
                    }

                }
                $newPriceComponent[] = $data;
                unset($data['reconciled_code']);
                $adddata = $this->modelObj->addContent('shipment_price', $data);
            }
            if ($adddata) {
                $temp['grand_total'] = $temp['surcharges'] + $temp['total_price'] + $temp['taxes'];
                if ($isInvoiced == 'YES') {
                    if ($temp['grand_total'] != $oldGrandTotal) {
                        $voucherHistoryid                  = $this->modelObj->getVoucherHistory($param['job_identity']);
                        $voucherdata                       = array();
                        $voucherdata                       = $this->getVoucherBreakDown($newPriceComponent,$getLastPriceBreakdown);
                        $voucherdata['voucher_type']       = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                        $voucherdata['voucher_reference']  = $this->modelObj->_generate_voucher_no($param['company_id']);
                        $voucherdata['total']             = ($temp['grand_total'] - $oldGrandTotal);
                        $voucherdata['shipment_reference'] = $param['job_identity'];
                        $voucherdata['create_date']        = date('Y-m-d');
                        $voucherdata['created_by']         = $param['user'];
                        $voucherdata['history_id']         = $voucherHistoryid;
                        $voucherdata['is_invoiced']        = 'NO';
                        $voucherdata['status']             = '1';
                        $voucherdata['company_id']         = $param['company_id'];
                        $voucherdata['customer_id']        = $customerId;
                        $voucherdata['invoice_reference']  = '';
                        $voucherdata['is_Paid']            = 'UNPAID';
                        $adddata                           = $this->modelObj->addContent('vouchers', $voucherdata);
                    }
                }
                
                $temp['price_update_applyto_customer'] = 'YES';
                $temp['version_reason']                = 'RECONCILED';
                $temp['price_version']                 = $priceVersion + 1;
                $condition                             = "load_identity = '" . $param['job_identity'] . "'";
                $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
		 if ($temp['grand_total'] != $oldGrandTotal) {
                   $dataarr = array();
                   $dataarr['payment_type']             = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                   $dataarr['amount']                   = (($temp['grand_total'] - $oldGrandTotal) > 0) ? ($temp['grand_total'] - $oldGrandTotal) :
                                                          ($oldGrandTotal - $temp['grand_total']);
                   $dataarr['customer_id']              = $customerId;
                   $dataarr['company_id']               = $param['company_id'];
                if ($isInvoiced == 'YES') {
                    $dataarr['payment_reference']        = $voucherdata['voucher_reference'];
                    $dataarr['payment_desc']             = 'Voucher Apply against '.$param['job_identity'];
                    $dataarr['payment_for']              = 'VOUCHER';
                }else{
                    $dataarr['payment_reference']        = $param['job_identity'];
                    $dataarr['payment_desc']             = 'UPADTE SHIPMENT PRICE';
                    $dataarr['payment_for']              = 'RECONCILED';
    		 }
                $accountUpdatestatus =  $this->manageAccount($dataarr);
                }
            }
        } 
        
        if ($status) {
            return array(
                'status' => 'success',
                'message' => 'data updated successfully',
                'data' => array(
                    'identity' => $param['job_identity']
                )
            );
        }
    }
    
    private function _calculateSurcharge($price, $surcharge_value, $operator, $company_surcharge_code, $company_surcharge_name, $courier_surcharge_code, $courier_surcharge_name, $level, $surcharge_id){
        if ($operator == "FLAT") {
            $price = $surcharge_value;
        } elseif ($operator == "PERCENTAGE") {
            $price = ($price * $surcharge_value / 100);
        } else {
            //
        }
        return array(
            "surcharge_value" => $surcharge_value,
            "operator" => $operator,
            "price" => $price,
            "company_surcharge_code" => $company_surcharge_code,
            "company_surcharge_name" => $company_surcharge_name,
            "courier_surcharge_code" => $courier_surcharge_code,
            "courier_surcharge_name" => $courier_surcharge_name,
            "level" => $level,
            'surcharge_id' => $surcharge_id
        );
    }
    
    public function calculateNewPrice($dataSet, $basePrice) {
        if ($dataSet['ccf_operator'] != '' && $dataSet['ccf_value'] != '' && $dataSet['ccf_operator'] != 'NONE') {
            if ($dataSet['ccf_operator'] == "FLAT") {
                $ccfprice = $dataSet['ccf_value'];
            } elseif ($dataSet['ccf_operator'] == "PERCENTAGE") {
                $ccfprice = ($basePrice * $dataSet['ccf_value'] / 100);
            } else {
            }
            $price = $basePrice + $ccfprice;
        } else {
            $ccfprice = $dataSet['ccf_price'];
            $price    = $basePrice + $ccfprice;
        }

        return array(
            "ccf_price" => $ccfprice,
            "baseprice" => $basePrice,
            "price" => $price
        );
    } 
    
    public function comparePrice($serviceDetails,$getLastPriceBreakdown){
        $compareArray = array();
        $compareArray['service']['reconciled'] = $serviceDetails['service'];
        $compareArray['surcharges'] = array();
        $compareArray['newsurcharges'] = array();
        $compareArray['newsurchargesprice'] = array();
        $compareArray['tax']['reconciled'] = $serviceDetails['tax'];
        $compareArray['tax']['localdata'] = 0;
        $newsurcharge = 0;
        $storedSurcharge = array();
         foreach($getLastPriceBreakdown as $localkey=>$localData){ 
                if($localData['api_key']=='service'){
                    $compareArray['service']['localdata'] = $localData;
                }elseif($localData['api_key']=='surcharges'){
                    $storedSurcharge[$localkey] = ($localData['reconciled_code']!='')?$localData['reconciled_code']:$localData['price_code'];
                }elseif($localData['api_key']=='taxes'){
                    $compareArray['tax']['localdata'] = $localData;
                }else{
                    //
                }
          }
        foreach($serviceDetails['surcharge'] as $surchargeval){
            $found = array_search($surchargeval['operation_code'],$storedSurcharge);
             if ($found !== false) {
                $compareArray['surcharges'][$surchargeval['operation_code']]['reconciled'] = $surchargeval;
                $compareArray['surcharges'][$surchargeval['operation_code']]['localdata'] = $getLastPriceBreakdown[$found]; 
             } else {
               $compareArray['newsurcharges'][$newsurcharge]      = $surchargeval['operation_code'];
                $compareArray['newsurchargesprice'][$newsurcharge] = $surchargeval['nettcharge'];
                $compareArray['newsurchargerow'][$newsurcharge] = $surchargeval['rownumber'];
                $newsurcharge++;
              }
         }
        return $compareArray;
    }
    
    public function prepareDataforReconciled($loadIdentity,$data,$carrier,$companyId,$user){ 
          $param = array();
          $param['job_identity'] = $loadIdentity;
          $param['user'] = $user;
          $param['applypriceoncustomer'] = 'YES';
          $param['company_id'] = $companyId;
          $param['data'][$data['service']['localdata']['id']] = $data['service']['reconciled']['nettcharge'];
          if(!empty($data['surcharges'])){
            foreach($data['surcharges'] as $surinnerkey =>$surinnerval){
              $param['data'][$surinnerval['localdata']['id']] = $surinnerval['reconciled']['nettcharge'];
            }
          }
          if(!empty($data['newsurcharges'])){
            $param['data']['newsurcharges'] = $data['newsurcharges'];
            $param['data']['newsurchargesprice'] = $data['newsurchargesprice'];
          }
          if(!empty($data['tax'])){ 
           $param['data'][$data['tax']['localdata']['id']] = $data['tax']['reconciled'];                     
          }
        return $param;
      }
    
    public function getTaxPrice($records){
        $temp['total_price']         = 0;
        $temp['carrier_total_price'] = 0;
        $temp['surcharges']          = 0;
        $temp['carrier_surcharges']  = 0;
        $temp['taxes']               = 0;
        $temp['carrier_taxes']       = 0;
        $isTax                       = false;
        $returnTax                   = array();
        if (count($records) > 0) {
            foreach ($records as $data) {
                if ($data['api_key'] == 'service') {
                    $temp['total_price']         = $data['price'];
                    $temp['carrier_total_price'] = $data['baseprice'];
                } elseif ($data['api_key'] == 'taxes') {
                    $isTax                  = true;
                    $temp['taxes']          = $data['price'];
                    $temp['carrier_taxes']  = $data['baseprice'];
                    $temp['taxes_operator'] = $data['ccf_operator'];
                    $temp['taxes_value']    = $data['ccf_value'];
                } else {
                    //if($data['apply_to_customer']!='NO'){
                    if ($data['show_for'] != 'CA') {
                        $temp['surcharges'] += $data['price'];
                    }
                    $temp['carrier_surcharges'] += $data['baseprice'];
                }
            }
            if ($isTax) {
                $basePrice         = $temp['total_price'] + $temp['surcharges'];
                $carrier_basePrice = $temp['carrier_total_price'] + $temp['carrier_surcharges'];
                if ($temp['taxes_operator'] == 'PERCENTAGE') {
                    $taxamt                  = number_format((($basePrice * $temp['taxes_value']) / 100), 2);
                    $carrier_taxamt          = number_format((($carrier_basePrice * $temp['taxes_value']) / 100), 2);
                    $returnTax['base_price'] = $carrier_taxamt; //$basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                } elseif ($temp['taxes_operator'] == 'FLAT') {
                    $taxamt                  = $temp['taxes_value'];
                    $returnTax['base_price'] = $carrier_basePrice; //$basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                } else {
                    $taxamt                  = 0;
                    $returnTax['base_price'] = $carrier_basePrice; //$basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                }
            }
        }
        return $returnTax;
    }
    
    public function manageAccount($creditbalanceData){
                $getCustomerdetails =  $this->modelObj->getCustomerInfo($creditbalanceData['customer_id']);
                $creditbalanceData['customer_type']        = $getCustomerdetails['customer_type'];
                $creditbalanceData['pre_balance']          = $getCustomerdetails["available_credit"];
                if($creditbalanceData['payment_type']=='CREDIT'){
                    $creditbalanceData['balance']              = $getCustomerdetails["available_credit"] + $creditbalanceData["amount"];
                }else{
                   $creditbalanceData['balance']              = $getCustomerdetails["available_credit"] - $creditbalanceData["amount"];
                }
                $creditbalanceData['create_date']          = date("Y-m-d");
                $addHistory = $this->modelObj->addContent('accountbalancehistory', $creditbalanceData);
                  if($addHistory>0){
                      $condition = "user_id = '".$creditbalanceData['customer_id']."'";
                      $updateStatus    = $this->modelObj->editContent("customer_info", array('available_credit'=>$creditbalanceData['balance']), $condition);
                      if($updateStatus){
                          return array("status"=>"success", "message"=>"Price Update save");
                      }
        }
    }
    
    public function getVoucherBreakDown($newPriceComponent,$getLastPriceBreakdown){
        $oldData = array('service'=>0,'surcharges'=>0,'fual_surcharge'=>0,'tax'=>0);
        $newData = array('service'=>0,'surcharges'=>0,'fual_surcharge'=>0,'tax'=>0);
        if(is_array($getLastPriceBreakdown)  and count($getLastPriceBreakdown)>0){
             foreach($getLastPriceBreakdown  as $key=>$data){
               if($data['api_key'] == 'service') {
                    $oldData['service']  = $data['price'];
                }elseif ($data['api_key'] == 'taxes') {
                    $oldData['tax']  = $data['price'];
                }else {
                    if ($data['price_code'] == 'fual_surcharge') {
                        $oldData['fual_surcharge']  += $data['price'];
                    }else{
                        $oldData['surcharges'] += $data['price'];
                    }
                }
            }
             foreach($newPriceComponent  as $key=>$data){
               //$newData['total'] += $data['price'];
               if($data['api_key'] == 'service') {
                    $newData['service']     = $data['price'];
                }elseif ($data['api_key'] == 'taxes') {
                    $newData['tax']         = $data['price'];
                }else {
                    if ($data['price_code'] == 'fual_surcharge') {
                        $newData['fual_surcharge']  += $data['price'];
                    }else{
                        $newData['surcharges'] += $data['price'];
                    }
                }
            }
        }
       return array(
                    'base_amount'=>number_format(($newData['service'] - $oldData['service']),2),
                    'surcharge_total'=>number_format(($newData['surcharges'] - $oldData['surcharges']),2),
                    'fual_surcharge'=>number_format(($newData['fual_surcharge'] - $oldData['fual_surcharge']),2),
                    'tax'=>number_format(($newData['tax'] - $oldData['tax']),2)
             );
        }
    
    public function getAllReconciled($param){
       $data = array();
       $data = $this->reconciledModelObj->getAllReconciled($param->company_id);
       if(count($data)>0){
           foreach($data as $key=>$datainner){
               $data[$key]['reqlink'] = str_replace(' ','',strtolower($datainner['name'])).'/request/'.$datainner['requested_csv_path']; 
               $data[$key]['reslink'] = str_replace(' ','',strtolower($datainner['name'])).'/response/'.$datainner['responded_csv_path']; 
           }
         }
      return $data;
    }
    
 }
?>
