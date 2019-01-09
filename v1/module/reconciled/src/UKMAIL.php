<?php 
class UKMAIL extends Singleton{
    
    public $reconcileObj =  null;
    public function validHeader($header){
    $this->reconcileObj =   Reconciled_Model::_getInstance();
    $this->headerColumn = 
             array(
                    'invoiceno'=>str_replace(' ','',strtolower('Invoice')),
                    'invoicedate'=>str_replace(' ','',strtolower('Job Date')),
                    //'companyname'=>str_replace(' ','',strtolower('Company Name')),
                    'accountnumber'=>str_replace(' ','',strtolower('Customer')),
                    'operation_code'=>str_replace(' ','',strtolower('Service Desc')),
                    'nettcharge'=>str_replace(' ','',strtolower('Value')),
                    'weight'=>str_replace(' ','',strtolower('Weight')),
                    //'taxamount'=>str_replace(' ','',strtolower('VAT Rate')),
                    'identity'=>str_replace(' ','',strtolower('Consignment')),
                    //'shipperreference'=>str_replace(' ','',strtolower('Shipper Reference')),
                    'shipmentdate'=>str_replace(' ','',strtolower('Job Date'))
              );
        $flag = array();
        foreach($this->headerColumn as $key=>$vals){
            if(!in_array($vals,$header)){
                 $flag[] = false;
            }
          }
          if(count($flag)>0){
            return false;
          }else{
             return true; 
         }
     }
    public function getCsvData($data){
       return $formatedData = $this->formateddata((array)$data);
    }
    public function formateddata($data){
       array_pop($data);
       array_pop($data);
       $tempdata   =  array();
       $dataarray  =  array();
       $customSurcharge  =  array(
                           'bookin_surcharge' => 'bookincharge',
                           'iod_surcharge' => 'iodcharge',
                           'iow_surcharge' => 'iowcharge',
                           'long_length_surcharge' => 'longlengthcharge',
                           'heavy_weight_surcharge' => 'heavyweightsurcharge',
                           'insurance_surcharge' => 'insurancecharge'
       );
       $customHeader = array_flip($this->headerColumn);
       foreach($data as $key => $tempinner){
           $tempinner->rownumber = $key+1;
           if($tempinner->consignment!=''){
               $awbNumber = $tempinner->consignment;
               $fualSurcharge =  $this->reconcileObj->getFualSurchargeofUKMAIL($awbNumber);
               $dataarray[$awbNumber]['surcharge'] = array();
               $dataarray[$awbNumber]['tax'] = number_format((($tempinner->value*20)/100),2);
               $totalSurchargeCost = 0;
               foreach($tempinner as $key=>$val){
                if(in_array($key,$this->headerColumn)){
                    $dataarray[$awbNumber]['service'][$customHeader[$key]] =  $val;
                 }
                $dataarray[$awbNumber]['service']['rownumber'] =  $tempinner->rownumber;
                 $found = array_search($key,$customSurcharge);
                  if ($found !== false) {  
                    $totalSurchargeCost += $val;
                    if($val >0){
                      $dataarray[$awbNumber]['surcharge'][] = array('operation_code'=>$found,'nettcharge'=>$val,'rownumber'=>$tempinner->rownumber); 
                    }
                  }
                }
            $dataarray[$awbNumber]['service']['nettcharge'] = ($dataarray[$awbNumber]['service']['nettcharge'] - $totalSurchargeCost);
            $dataarray[$awbNumber]['surcharge'][] = array('operation_code'=>'fuel_surcharge','nettcharge'=>number_format((($tempinner->value*$fualSurcharge)/100),2),'rownumber'=>$tempinner->rownumber);  
           }
        }
       return $dataarray;    
     }
    public function getCarrierCsvData($path,$row){
        $csvFile = file($path);
         array_pop($csvFile);
         array_pop($csvFile);
         array_pop($csvFile);
         $data = [];
        foreach ($csvFile as $k=> $line) {
            $temp = str_getcsv($line);
            if($k ==0){
                array_push($temp,'Reconciled Eligible','Message');
            }else{
                $statusarray = array();
                $messagearray = array();
                foreach($row[$k] as $keyd => $vald){
                    if($keyd === 'taxinfo'){ 
                      array_push($messagearray,$row[$k]['taxinfo'][0]);
                    }else{
                     array_push($statusarray,$vald['status']);
                     array_push($messagearray,'('.$vald['message'].', Local Price '.$vald['localprice'].', Req Price '.$vald['requestedprice'].')');
                    }
                 }
                array_push($temp,(in_array('success',$statusarray))?'YES':'NO',implode(", ",$messagearray));
            }
            $data[] = $temp;
        }
       return $data;
    }
        
/*required Paased format data    
    [41607620000040] => Array
        (
            [surcharge] => Array
                (
                    [0] => Array
                        (
                            [operation_code] => fuel_surcharge
                            [nettcharge] => 0.00
                            [rownumber] => 4
                        )

                )
            [tax] => 0.94
            [service] => Array
                (
                    [accountnumber] => A906588
                    [rownumber] => 4
                    [identity] => 41607620000040
                    [invoiceno] => 6077682
                    [nettcharge] => 4.68
                    [shipmentdate] => 13/11/2018
                    [weight] => 16
                    [operation_code] => Next Day
                )
        )
    */
}
?>