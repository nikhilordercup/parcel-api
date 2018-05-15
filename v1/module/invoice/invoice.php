<?php
class Invoice extends Icargo{
    public $modelObj = null;
    
   public function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = AllInvoice_Model::getInstanse();
    }
    
   public function getallinvoice($param){
         $invoiceData = $this->modelObj->getAllInvice($param->warehouse_id,$param->company_id);
         return $invoiceData;
   }
  public function createInvoice($param){
      $_start_date          = $param->start_date;
      $_end_date            = $param->end_date;
      $_sendpdftocustomer   = $param->sendpdftocustomer;
      $_attachvoucher       = $param->attachvoucher;
      $_sendtomail          = $param->sendtomail;
      $_sendtocustomemail   = $param->sendtomailofemail;
      $_email               = $param->email;    
      $_company_id          = $param->company_id;
      $customerfilter = isset($param->customer)?"AND A.customer_id = '".$param->customer."'":" ";
      $invoicedDocketData = $this->modelObj->getAllInvoicedDocket($_company_id,$_start_date,$_end_date,$customerfilter);
      $tempdatap = array();
      foreach($invoicedDocketData as $key=>$val){
       $tempdatap[$val['customer_id']]['shipments'][] = $val;
          $tempdatap[$val['customer_id']]['total']['totalshipment'][] = 1;
          $tempdatap[$val['customer_id']]['total']['totalitems'][]= $val['items'];
      }
      $invoicedDocketData = $tempdatap;
      $invoicecount = 0;
      $invoiceRef = array();
     
      //if(isset($_attachvoucher)){
          
      //}
      
      foreach($invoicedDocketData as $key=>$val){
         $invoicedata =  array();
         $invoicedata['customer_id']        = $key;
         $invoicedata['company_id']         = $_company_id;
         $invoicedata['invoice_reference']  = $this->modelObj->_generate_invoice_no($_company_id);
         $invoicedata['raised_on']          = date('Y-m-d');
         $invoicedata['deu_date']           = $this->getDueDateOfInvoice($key,date('Y-m-d'));
         $invoicedata['from']               = $_start_date;
         $invoicedata['to']                 = $_end_date;
         $invoicedata['voucer']             = 0;
         $invoicedata['tot_shipmets']       = array_sum($val['total']['totalshipment']);
         $invoicedata['tot_item']           = array_sum($val['total']['totalitems']);
         $invoicedata['status']             = 1;
         $invoicedata['invoice_status']     = 'UNPAID';
         $invoiceId = $this->modelObj->addContent('invoices',$invoicedata);
         $invoiceRef[] = $invoicedata['invoice_reference'];
         $invoicecount++;
         $invoicePriceData = array('base_amount'=>0,'surcharge_total'=>0,'fual_surcharge'=>0,'tax'=>0,'total_ammount'=>0);
          if($invoiceId){
             foreach($val['shipments'] as $innerkey=>$innerval){ 
                 $docketdata = array();
                 $docketdata['invoice_id'] = $invoiceId;
                 $docketdata['invoice_reference'] = $invoicedata['invoice_reference'];
                 $docketdata['company_id'] = $_company_id;
                 $docketdata['reference_id'] = $innerval['reference_id'];
                 $docketdata['reference'] = $innerval['reference'];
                 $docketdata['invoice_type'] = 'SHIPMENT';
                 $docketdata['create_date'] = date("Y-m-d");
                 $docketdata['items'] = $innerval['items'];
                 $docketdata['chargable_unit']  = $innerval['rate_type'];
                 $docketdata['chargable_value'] = $innerval['chargable_value'];
                 $docketdata['service_name'] = $innerval['service_name'];
                 $docketdata['customer_booking_reference'] = $innerval['customer_booking_reference'];
                 $docketdata['base_amount'] = $innerval['base_amount'];
                 $docketdata['tax'] = $innerval['tax'];
                 $docketdata['fual_surcharge'] = isset($innerval['fual_surcharge'])?$innerval['fual_surcharge']:0;   
                 $docketdata['surcharge_total'] = ($innerval['surcharge_total']-$docketdata['fual_surcharge']);
                 $docketdata['total']    = ($docketdata['base_amount']+$docketdata['surcharge_total']+
                                            $docketdata['fual_surcharge']+$docketdata['tax']);
                 
                 $getDocketOriginandEndPoint = $this->getOriginandDestination($docketdata['reference']); 
                
          
                 if(!empty($getDocketOriginandEndPoint)){
                  $docketdata['origin'] = isset($getDocketOriginandEndPoint['collection'])?$getDocketOriginandEndPoint['collection']:'';
                  $docketdata['destination'] = isset($getDocketOriginandEndPoint['delivery'])?$getDocketOriginandEndPoint['delivery']:'';
                  $docketdata['collection_date'] = ($getDocketOriginandEndPoint['collection_date']=='0000-00-00')?'1970-01-01':$getDocketOriginandEndPoint['collection_date'];
                 }else{
                  $docketdata['origin'] = '';
                  $docketdata['destination'] = '';
                  $docketdata['collection_date'] = '1970-01-01';
                 }
                
                $invoicedocket = $this->modelObj->addContent('invoice_vs_docket',$docketdata);
                if($invoicedocket){
                  $updatestatus = $this->modelObj->editContent('shipment_service',array('isInvoiced'=>'YES','invoice_reference'=>$invoicedata['invoice_reference'])," shipment_id = '".$innerval['reference_id']."'");   
                }
                 $invoicePriceData['base_amount'] += $docketdata['base_amount'];
                 $invoicePriceData['surcharge_total'] += $docketdata['surcharge_total'];
                 $invoicePriceData['fual_surcharge'] += $docketdata['fual_surcharge'];
                 $invoicePriceData['tax'] += $docketdata['tax'];
                 $invoicePriceData['total_ammount'] += $docketdata['total'];
                 $updatestatus = $this->modelObj->editContent('invoices',$invoicePriceData," id = '".$invoiceId."'");  
             }
         }
     }
      
    
      
    if(isset($_sendpdftocustomer)){
              
      }
      
    if(isset($_sendtomail) && isset($_sendtocustomemail) && $_sendtocustomemail !=''){
          
      }
     return array('status'=>true,'message'=>'total '.$invoicecount.' invoice created');
   }
    
   public function getDueDateOfInvoice($customerId,$date){
      $invoicecycle =  $this->modelObj->getCustomerInvoiceCycle($customerId);
       return date('Y-m-d', strtotime($date. ' + '+$invoicecycle+' days'));
   }  
    
    
    
   private function getOriginandDestination($jobId){
        $shipmentsData = $this->modelObj->getjobDetails($jobId);
     
        $dataArray   =     array();
        $data   =     array(); 
        foreach($shipmentsData as $key=>$val){                                
          $val['instaDispatch_loadGroupTypeCode']  = strtoupper($val['instaDispatch_loadGroupTypeCode']);    
          $dataArray[$val['instaDispatch_loadIdentity']][strtoupper($val['instaDispatch_loadGroupTypeCode'])][$val['shipment_service_type']][]   = $val;
         }
        
        if(count($dataArray)>0){
          foreach($dataArray as $innerkey=>$innerval){
            if(key($innerval) == 'SAME'){
              if(array_key_exists('P',$innerval['SAME'])){  
               foreach($innerval['SAME']['P'] as $pickupkey=>$pickupData){
                 $data['collection'] = $pickupData['shipment_postcode'].' '.$pickupData['shipment_customer_country'];
                 $data['collection_date'] = $pickupData['shipment_required_service_date'];  
                   
              }  
            }       
              if(array_key_exists('D',$innerval['SAME'])){  
                $temp = array();
                foreach ($innerval['SAME']['D'] as $key => $row){
                   $temp[$key] = $row['icargo_execution_order'];
                }
                array_multisort($temp, SORT_ASC, $innerval['SAME']['D']);
                $lastDeliveryarray =  end($innerval['SAME']['D']);
                $data['delivery']  = $lastDeliveryarray['shipment_postcode'].' '.$lastDeliveryarray['shipment_customer_country'];
            }
            }
            if(key($innerval) == 'NEXT'){ 
             if(array_key_exists('P',$innerval['NEXT'])){  
               foreach($innerval['NEXT']['P'] as $pickupkey=>$pickupData){
                  $data['collection']          = $pickupData['shipment_postcode'].' '.$pickupData['shipment_customer_country'];
                    $data['collection_date'] = $pickupData['shipment_required_service_date'];  
              }  
            }       
             if(array_key_exists('D',$innerval['NEXT'])){  
                krsort($innerval['NEXT']['D']);
                $deliveryPostcode = array();
                foreach($innerval['NEXT']['D'] as $deliverykey=>$deliveryData){
                 $deliveryPostcode[$deliveryData['icargo_execution_order']]  = $deliveryData['shipment_postcode'].' '.$deliveryData['shipment_customer_country'];
                  $shipmentstatus[] =  $deliveryData['current_status'];
                }
                krsort($deliveryPostcode);
                $data['delivery']  = end($deliveryPostcode); 
            }
           } 
          }
        } 
       return $data;
   }  
    
   public function cancelInvoices($param){
       $_company_id = $param->company_id;
       $_invoice_ticket = $param->invoice_ticket;
       $invoice_array = explode(',',$_invoice_ticket);
       $_company_id = $param->company_id;
       $totalInvoice = array();
       if(count($invoice_array)>0){
           foreach($invoice_array as $key=>$vals){ 
            $updatestatus = $this->modelObj->editContent('shipment_service',
                                                         array('isInvoiced'=>'NO','invoice_reference'=>'')," invoice_reference = '".$vals."'");  
            if($updatestatus){
            $updateinvoicestatus = $this->modelObj->editContent('invoices',
                                                         array('invoice_status'=>'CANCEL'),
                                                         " invoice_reference = '".$vals."'");
            $totalInvoice[] = $vals;
            }      
          }
       }
       if(count($totalInvoice)>0){
        return array('status'=>true,'message'=>join(',',$totalInvoice).' canceled','reference'=>join(',',$totalInvoice));
    }else{
        return array('status'=>false,'message'=>'your request are not processed');
     }
    }
   public function createInvoicepdf($invoiceRef){
       // $physicalPath   = realpath(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/assets/template/';
        //$headerContent  = file_get_contents($physicalPath.'invoice-header.html');
        //$footerContent  = file_get_contents($physicalPath.'invoice-footer.html');
        //$bodyContent    = file_get_contents($physicalPath.'invoice-body.html');
        $pdfdata = array();
        foreach($invoiceRef->rererence as $val){ 
          $pdfdata[$val]['invoiceshipdata'] = $this->modelObj->getAllInviceShip($val);
          //$pdfdata[$val]['invoicecustomer'] = $this->modelObj->getAllInviceCustomerDetails($val);
         $customerdata =  $this->modelObj->getAllInviceCustomerDetails($val);  
           foreach($customerdata as $keys=>$vals){
             if($vals==""){ $customerdata[$keys]= "N/A";} 
            }   
           $pdfdata[$val]['invoicecustomer'] =  $customerdata;
          //$pdfdata[$val]['templateheader']  = $headerContent;
          //$pdfdata[$val]['templatefooter']  = $footerContent;
          //$pdfdata[$val]['templatebody']    = $bodyContent;
      }  
        return $pdfdata;
    } 
  public function saveInvoicepdf($paramdata){ 
  $physicalPath   = realpath(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/upload/invoices/';
  
       print_r($paramdata);die;
   $data = substr($paramdata['data'], strpos($_POST['data'], ",") + 1);   
     
     

$decodedData = base64_decode($paramdata->data);
// print out the raw data, 
echo ($decodedData);
$filename = $physicalPath.$paramdata->fileName;
// write the data out to the file
$fp = fopen($filename, 'wb');
fwrite($fp, $decodedData);
fclose($fp);
  }
    
 }
?>