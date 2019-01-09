<?php 
//namespace DHL\DHL {
   class DHL extends Singleton{
    public $headerColumn = array();
    public function getName(){
        return 'I am DHL Class !!';
    }
    public function validHeader($header){
    $this->headerColumn = 
             array(
                    'invoiceno'=>str_replace(' ','',strtolower('Invoice No')),
                    'invoicedate'=>str_replace(' ','',strtolower('Invoice Date')),
                    'companyname'=>str_replace(' ','',strtolower('Company Name')),
                    'accountnumber'=>str_replace(' ','',strtolower('Account Number')),
                    'operation_code'=>str_replace(' ','',strtolower('DHL Product Description')),
                    'nettcharge'=>str_replace(' ','',strtolower('Nett Charge')),
                    'weight'=>str_replace(' ','',strtolower('Weight')),
                    'taxamount'=>str_replace(' ','',strtolower('Tax Amount')),
                    'identity'=>str_replace(' ','',strtolower('AWB Number')),
                    'shipperreference'=>str_replace(' ','',strtolower('Shipper Reference')),
                    'shipmentdate'=>str_replace(' ','',strtolower('Shipment Date'))
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
       $tempdata   =  array();
       $dataarray  =  array();
       foreach($data as $keyinner => $datainner){
           $datainner->rownumber = ($keyinner +1);
           $tempdata[$datainner->awbnumber][] = (array)$datainner;
       }
       $customHeader = array_flip($this->headerColumn);
       foreach($tempdata as $awbNumber => $tempinner){
            $dataarray[$awbNumber]['surcharge'] = array();
            $dataarray[$awbNumber]['tax']       = 0;
            foreach($tempinner as $innerkey => $innerdata){
             if($innerkey == 0){
                 $dataarray[$awbNumber]['tax'] += $innerdata['taxamount'];
                 $innerdata['nettcharge'] = ($innerdata['nettcharge'] - $innerdata['taxamount']);
                 foreach($innerdata as $key=>$val){
                     if(in_array($key,$this->headerColumn)){
                         $dataarray[$awbNumber]['service'][$customHeader[$key]] =  $val;
                     }
                 }   
                 $dataarray[$awbNumber]['service']['rownumber'] = $innerdata['rownumber'];
             }else{
                $surcharge = array();
                 $dataarray[$awbNumber]['tax'] += $innerdata['taxamount'];
                 $innerdata['nettcharge'] = ($innerdata['nettcharge'] - $innerdata['taxamount']);
                 foreach($innerdata as $key=> $val){
                     if(in_array($key,$this->headerColumn)){
                         $surcharge[$customHeader[$key]] =  $val;
                      }
                 }
                $surcharge['rownumber'] = $innerdata['rownumber'];
                array_push($dataarray[$awbNumber]['surcharge'],$surcharge);
             }        
          }
        }
       return $dataarray;    
     }
    public function getCarrierCsvData($path,$row){
        $csvFile = file($path);
        $data = [];
        foreach ($csvFile as $k=> $line) {
            $temp = str_getcsv($line);
            if($k ==0){
                array_push($temp,'Reconciled Eligible','Message','Local Price','Requested Price','Difference','Tax info');
            }else{
                if(isset($row[$k][0])){
                    array_push($temp,
                               isset($row[$k][0]['status'])?$row[$k][0]['status']:'',
                               isset($row[$k][0]['message'])?$row[$k][0]['message']:'',
                               isset($row[$k][0]['localprice'])?$row[$k][0]['localprice']:'',
                               isset($row[$k][0]['requestedprice'])?$row[$k][0]['requestedprice']:'',
                               ($row[$k][0]['requestedprice'] - $row[$k][0]['localprice']),
                               isset($row[$k][0]['taxinfo'])?$row[$k][0]['taxinfo']:'');
                }else{
                    array_push($temp,'','','','','','');
                }
            }
            $data[] = $temp;
        }
       return $data;
    }
   }        
  
//}

?>
