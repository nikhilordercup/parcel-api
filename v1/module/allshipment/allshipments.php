<?php
class allShipments extends Icargo{
    public $modelObj = null;
    
   public function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = AllShipment_Model::getInstanse();
    }
    
   public function getallshipments($param){
       $html = '';
       $html2 = '';
       $html3 = '';
      
       //$html .= isset($param->data->account)?' AND CI.accountnumber = "'.$param->data->account.'" ':'';
       $html .= (isset($param->data->customer) 
                 && ($param->data->customer!=''))?' AND S.customer_id =  "'.$param->data->customer.'" ':'';
       
       
        $html .= (isset($param->warehouse_id) 
                 && ($param->warehouse_id!=''))?' AND S.warehouse_id = "'.$param->warehouse_id.'" ':'';
       
       
       $html .= (isset($param->data->job_identity) 
                 && ($param->data->job_identity!=''))
                ?' AND S.instaDispatch_loadIdentity = "'.$param->data->job_identity.'" ':'';
       
       
       $html .= (isset($param->data->job_type) 
                 && ($param->data->job_type!=''))?' AND S.shipment_type = "'.$param->data->job_type.'" ':'';
       
       
       $html .= (isset($param->data->booking_date) 
                 && ($param->data->booking_date!=''))?' AND S.booking_date =  "'.$param->data->booking_date.'" ':'';
       
      if(isset($param->data->globalbookingdatefilter) && ($param->data->globalbookingdatefilter !='')){
          $dates = explode('/',$param->data->globalbookingdatefilter);
      }
       
       $html .= (isset($param->data->globalbookingdatefilter) 
                 && ($param->data->globalbookingdatefilter!=''))?'AND (S.booking_date BETWEEN "'.$dates[0].'" AND "'.$dates[1].'")':''; 
       
       $html .= (isset($param->data->carrier) 
                 && ($param->data->carrier!=''))?' AND S.carrier = "'.$param->data->carrier.'" ':'';
       
       $html .= (isset($param->data->booked_by) 
                 && ($param->data->booked_by!=''))?' AND S.booked_by = "'.$param->data->booked_by.'" ':'';
       
       $html .= (isset($param->data->amount) 
                 && ($param->data->amount!=''))?' AND S.amount =  '.$param->data->amount.' ':'';
       
       $html .= (isset($param->data->isInvoiced) 
                 && ($param->data->isInvoiced!=''))?' AND S.isInvoiced = "'.$param->data->isInvoiced.'" ':'';
       
       
       $html .= (isset($param->data->service) 
                 && ($param->data->service!=''))?' AND S.service_name = "'.$param->data->service.'" ':'';
      
       
        
        $html2 .= (isset($param->data->shipment_status) 
                 && ($param->data->shipment_status!='select'))?' AND  S.current_status = "'.$param->data->shipment_status[0].'"':'';
       $html2 .= (isset($param->data->postcode) 
                 && ($param->data->postcode!=''))?' AND ADDR.postcode LIKE "%'.$param->data->postcode.'%"':'';
       
       $html2 .= (isset($param->data->pickup_date) 
                 && ($param->data->pickup_date!=''))?' AND S.shipment_required_service_date =  "'.$param->data->pickup_date.'" AND S.shipment_service_type = "P"':'';
     
   
       
       if(isset($param->data->globalcollectiondatefilter) && ($param->data->globalcollectiondatefilter !='')){
          $dates2 = explode('/',$param->data->globalcollectiondatefilter);
      }
       
       $html2 .= (isset($param->data->globalcollectiondatefilter) 
                 && ($param->data->globalcollectiondatefilter!=''))?'AND (S.shipment_required_service_date BETWEEN "'.$dates2[0].'" AND "'.$dates2[1].'")':''; 
       
       
       if($html2!='' && $html==''){  // Only Serch Two's Data coming
           $identityarray = array();
            $limitstr = "LIMIT ".$param->datalimitpre.", ".$param->datalimitpost."";  
           $shipmentsDataDrop = $this->modelObj->getAllShipmentsIdentity($html2,$limitstr);
           foreach($shipmentsDataDrop as $data){
             $identityarray[] = $data['instaDispatch_loadIdentity'];
           }
         $html3 .= " AND S.instaDispatch_loadIdentity  IN(" .'"'.implode('","',$identityarray) .'"'. ") "; 
          
         $shipmentsData = $this->modelObj->getAllShipments($html3);    
        }
       if($html2=='' && $html!=''){  // Only Serch One's Data coming
         $identityarray = array();
        $limitstr = "LIMIT ".$param->datalimitpre.", ".$param->datalimitpost."";  
        $shipmentsData = $this->modelObj->getAllShipmentsDrop($param->warehouse_id,$param->company_id,$limitstr,$html); 
       
         foreach($shipmentsData as $data){
           $identityarray[] = $data['instaDispatch_loadIdentity'];
         }
         $html3 .= " AND S.instaDispatch_loadIdentity  IN(" .'"'.implode('","',$identityarray) .'"'. ") "; 
         $shipmentsData = $this->modelObj->getAllShipments($html3.$html2); 
                                 
       }
       if($html2!='' && $html!=''){ // Both Serch's Data coming
         $seachoneBucketarray = array();$seachtwoBucketarray = array();
         $identityarray = array();$identityarray2 = array();
         $limitstr = "LIMIT ".$param->datalimitpre.", ".$param->datalimitpost."";   
         $seachoneBucket = $this->modelObj->getAllShipmentsDrop($param->warehouse_id,$param->company_id,"",$html); 
          foreach($seachoneBucket as $data){
              $seachoneBucketarray[] = $data['instaDispatch_loadIdentity'];
           }  
         $seachtwoBucket = $this->modelObj->getAllShipmentsIdentity($html2,"");   
          foreach($seachtwoBucket as $data){
              $seachtwoBucketarray[] = $data['instaDispatch_loadIdentity'];
           } 
         $commondata = array_intersect($seachoneBucketarray,$seachtwoBucketarray); 
        $html31 = " AND S.instaDispatch_loadIdentity  IN(" .'"'.implode('","',$commondata) .'"'. ") "; 
        
        $shipmentsDataDrop = $this->modelObj->getAllShipmentsDrop($param->warehouse_id,$param->company_id,$limitstr,$html31); 
        foreach($shipmentsDataDrop as $data){
              $identityarray[] = $data['instaDispatch_loadIdentity'];
        }   
           
        $html3 .= " AND S.instaDispatch_loadIdentity  IN(" .'"'.implode('","',$identityarray) .'"'. ") "; 
        $shipmentsData = $this->modelObj->getAllShipments($html3.$html2); 
       }
       if($html2=='' && $html==''){  // Both Serch's Data not coming
           $limitstr = "LIMIT ".$param->datalimitpre.", ".$param->datalimitpost.""; 
           $shipmentsData = $this->modelObj->getAllShipmentsDrop($param->warehouse_id,$param->company_id,$limitstr,$html); 
         
           
      
           $identityarray = array();
         foreach($shipmentsData as $data){
           $identityarray[] = $data['instaDispatch_loadIdentity'];
         }
         $html3 .= " AND S.instaDispatch_loadIdentity  IN(" .'"'.implode('","',$identityarray) .'"'. ") "; 
         $shipmentsData = $this->modelObj->getAllShipments($html3.$html2); 
       }
       
       $shipmentsPrepareData = $this->_prepareShipments($shipmentsData);
         return $shipmentsPrepareData;
     }
   
   private function _prepareShipments($shipmentsData){
    
        $dataArray   =     array();
        $returndata   =     array();  
        foreach($shipmentsData as $key=>$val){
          $dataArray[$val['instaDispatch_loadIdentity']][strtoupper($val['instaDispatch_loadGroupTypeCode'])][$val['shipment_service_type']][]   = $val;
         }
        if(count($dataArray)>0){
          foreach($dataArray as $innerkey=>$innerval){
           $data = array();
           $data['job_identity']         = $innerkey;
           $data['job_type']            = key($innerval);
           $shipmentstatus = array();
           $data['delivery']            = $innerkey;
           $jobIdentity = $innerkey; 
           if(key($innerval) == 'SAME'){ 
              $data['action']            = 'sameday';
             if(array_key_exists('P',$innerval['SAME'])){  
               foreach($innerval['SAME']['P'] as $pickupkey=>$pickupData){
                 $data['customer']            = $pickupData['shipment_customer_name'];
                 $data['account']             = $pickupData['shipment_customer_account'];
                 $data['service']             = $pickupData['shipment_service_name'];
                 $data['carrier']             = $pickupData['carrier'];
                 $data['amount']              = $pickupData['shipment_customer_price'];
                 $data['booked_by']           = $pickupData['booked_by'];
                 $data['isInvoiced']          = $pickupData['isInvoiced'];
                 $data['show']                = 'y';  
                 $data['collectionpostcode']  = $pickupData['shipment_postcode'];
                 $data['collection']          = $pickupData['shipment_postcode'].', '.$pickupData['shipment_customer_country'];
                 $data['pickup_date']         = $pickupData['shipment_required_service_date'].'  '.$pickupData['shipment_required_service_starttime']; 
                  $shipmentstatus[] =  $pickupData['current_status'];
              }  
            }       
             if(array_key_exists('D',$innerval['SAME'])){  
                $temp = array();
                foreach ($innerval['SAME']['D'] as $key => $row){
                   $temp[$key] = $row['icargo_execution_order'];
                }
                array_multisort($temp, SORT_ASC, $innerval['SAME']['D']);
                $lastDeliveryarray =  end($innerval['SAME']['D']);
                $data['deliverypostcode']  = $lastDeliveryarray['shipment_postcode'];
                $data['delivery']  = $lastDeliveryarray['shipment_postcode'].', '.$lastDeliveryarray['shipment_customer_country'];
                foreach($innerval['SAME']['D'] as $deliverykey=>$deliveryData){
                 $shipmentstatus[] =  $deliveryData['current_status'];
                }
            }
             $arrd = array_unique($shipmentstatus); 
             if(count($arrd)>1){
               $data['shipment_status']  = 'Not Completed'; 
             }elseif(count($arrd)==1){
               if($arrd[0]=='D'){
                  $data['shipment_status']     = 'Completed';  
               }else{
                  $data['shipment_status']     = 'Not Completed';  
               }  
             }   
             $returndata[] = $data;
           }
           if(key($innerval) == 'NEXT'){ 
             $data['action']            = 'nextday';
             if(array_key_exists('P',$innerval['NEXT'])){  
               foreach($innerval['NEXT']['P'] as $pickupkey=>$pickupData){
                 $data['customer']            = $pickupData['shipment_customer_name'];
                 $data['account']             = $pickupData['shipment_customer_account'];
                 $data['service']             = $pickupData['shipment_service_name'];
                 $data['carrier']             = $pickupData['carrier'];
                 $data['amount']              = $pickupData['shipment_customer_price'];
                 $data['booked_by']           = $pickupData['booked_by'];
                 $data['isInvoiced']          = $pickupData['isInvoiced'];
                 $data['collection']          = $pickupData['shipment_postcode'].', '.$pickupData['shipment_customer_country'];
                 $data['pickup_date']         = $pickupData['shipment_required_service_date'].'  '.$pickupData['shipment_required_service_starttime']; 
                  $shipmentstatus[] =  $pickupData['current_status'];
              }  
            }       
             if(array_key_exists('D',$innerval['NEXT'])){  
                krsort($innerval['NEXT']['D']);
                $deliveryPostcode = array();
                foreach($innerval['NEXT']['D'] as $deliverykey=>$deliveryData){
                 $deliveryPostcode[$deliveryData['icargo_execution_order']]  = $deliveryData['shipment_postcode'].', '.$deliveryData['shipment_customer_country'];
                  $shipmentstatus[] =  $deliveryData['current_status'];
                }
                krsort($deliveryPostcode);
                $data['delivery']  = end($deliveryPostcode); 
            }
             $arrd = array_unique($shipmentstatus); 
             if(count($arrd)>1){
               $data['shipment_status']     = 'Not Completed'; 
             }elseif(count($arrd)==1){
               if($arrd[0]=='D'){
                  $data['shipment_status']     = 'Completed';  
               }else{
                  $data['shipment_status']     = 'Not Completed';  
               }  
             }  
             $returndata[] = $data;
           } 
          }
        }
      
       return $returndata;
   }  
      
   public function getSameDayShipmentDetails($param){
       $basicInfo = $this->_getBasicInfoOfShipment($param['identity']); 
       return array('sameday'=>array('basicinfo'=>$basicInfo));
 }
    
   public function getNextDayShipmentDetails($param){
        print_r($param);die;
     }
      
  
   private function _getBasicInfoOfShipment($identity){
      $shipmentsInfoData = $this->modelObj->getShipmentsDetail($identity);
      $basicInfo =  array();
      if(count($shipmentsInfoData)>0)
      {
        $basicInfo['totaldrop']             = count($shipmentsInfoData); 
        $basicInfo['customer']              = $shipmentsInfoData[0]['customer'];
        $basicInfo['service']               = $shipmentsInfoData[0]['service'];
        $basicInfo['chargeableunit']        = $shipmentsInfoData[0]['chargeableunit'];
        $basicInfo['user']                  = $shipmentsInfoData[0]['user'];
        $basicInfo['carrier']               = $shipmentsInfoData[0]['carrier'];
        $basicInfo['reference']             = $shipmentsInfoData[0]['reference'];
        $basicInfo['carrierreference']      = $shipmentsInfoData[0]['carrierreference'];
        $basicInfo['carrierbillingacount']  = $shipmentsInfoData[0]['carrierbillingacount'];
        $basicInfo['chargeablevalue']       = $shipmentsInfoData[0]['chargeablevalue'];
          
        $basicInfo['customerbaseprice']       = $shipmentsInfoData[0]['customerbaseprice'];
        $basicInfo['customersurcharge']       = $shipmentsInfoData[0]['customersurcharge'];
        $basicInfo['customersubtotal']        = $shipmentsInfoData[0]['customersubtotal'];
        $basicInfo['customertax']             = $shipmentsInfoData[0]['customertax'];
        $basicInfo['customertotal']           = $shipmentsInfoData[0]['customertotalprice'];
        $basicInfo['carrierbaseprice']        = $shipmentsInfoData[0]['carrierbaseprice'];
        $basicInfo['carriersurcharge']        = $shipmentsInfoData[0]['carriersurcharge'];
        $basicInfo['carriersubtotal']         = $shipmentsInfoData[0]['carriersubtotal'];
        $basicInfo['carriertax']              = $shipmentsInfoData[0]['carriertax'];
        $basicInfo['carriertotal']            = $shipmentsInfoData[0]['carriertotalprice'];
        
        $basicInfo['customerinvoicereference']  = $shipmentsInfoData[0]['customerinvoicereference'];
        $basicInfo['bookingtype']               = $shipmentsInfoData[0]['bookingtype'];
        $basicInfo['customer_desc']             = $shipmentsInfoData[0]['customer_desc'];
        $basicInfo['customerreference']         = $shipmentsInfoData[0]['customerreference'];
        $basicInfo['waitandreturn']             = $shipmentsInfoData[0]['waitandreturn'];
        $basicInfo['waitandreturn']             = ($basicInfo['waitandreturn']=='false')?'NO':'YES';
        $basicInfo['transittime']               = $shipmentsInfoData[0]['transittime'];
        $basicInfo['bookingdate']               = date("Y/m/d",strtotime($shipmentsInfoData[0]['bookingdate']));
        $basicInfo['expecteddate']              = date("Y/m/d",strtotime($shipmentsInfoData[0]['expecteddate']));  
        $basicInfo['expectedstarttime']         = $shipmentsInfoData[0]['expectedstarttime'];
        $basicInfo['expectedendtime']           = $shipmentsInfoData[0]['expectedendtime'];
        $basicInfo['isinsured']                 = "N/A";    
        $basicInfo['insurencevalue']            = "N/A";
        $basicInfo['handcost']                  = "N/A"; 
        $basicInfo['flowtype']                  = "Domestic";   
        
          
          
       $shipmentsurchargeData = $this->modelObj->getShipmentsurchargeData('ICARGOS1773577');   
       $basicInfo['chargedata'] = array();
       if(count($shipmentsurchargeData)>0){
           foreach($shipmentsurchargeData as $key=>$val){
             $basicInfo['chargedata'][$val['api_key']][] = array('price_code'=>$val['price_code'],'price'=>$val['price']);  
        } 
       }   
        foreach($shipmentsInfoData as $key=>$val)
          {
             if($val['shipment_type']=='P'){
                $basicInfo['collectedby']                = $val['collectedby'];
                $basicInfo['collectioncustomername']     = $val['customername'];
                $basicInfo['collectioncustomeraddress1'] = $val['address_line1'];
                $basicInfo['collectioncustomeraddress2'] = $val['address_line2'];
                $basicInfo['collectioncustomeremail']    = $val['customeremail'];
                $basicInfo['collectioncustomerphone']    = $val['customerphone']; 
                $basicInfo['collectioncustomercountry']  = $val['country']; 
                $basicInfo['collectioncustomercity']     = $val['city']; 
                $basicInfo['collectioncustomerpostcode'] = $val['postcode']; 
                $basicInfo['collectioncustomerstate']    = $val['state'];
                $basicInfo['collectiondate']             = $val['expecteddate'];
              }else{
                $data = array();
                $data['deliverycustomername']     = $val['customername'];
                $data['deliverycustomeraddress1'] = $val['address_line1'];
                $data['deliverycustomeraddress2'] = $val['address_line2'];
                $data['deliverycustomeremail']    = $val['customeremail'];
                $data['deliverycustomerphone']    = $val['customerphone']; 
                $data['deliverycustomercountry']  = $val['country']; 
                $data['deliverycustomercity']     = $val['city']; 
                $data['deliverycustomerpostcode'] = $val['postcode']; 
                $data['deliverycustomerstate']    = $val['state']; 
                $basicInfo['deliveryaddress'][] = $data; 
                 
             }
          }
        $getInvoiceDetails =  $this->modelObj->getShipmentsInvoiceDetail($identity);
        if($getInvoiceDetails !=''){
         $basicInfo['customerinvoicetype']          = $getInvoiceDetails['invoice_type'];
         $basicInfo['customerinvoicereference']     = $getInvoiceDetails['invoice_reference'];  
         $basicInfo['customerinvoicetotal']         = $getInvoiceDetails['total'];
         $basicInfo['customerinvoiceraised_on']     = date("Y/m/d",strtotime($getInvoiceDetails['raised_on']));  
         $basicInfo['customerinvoicedeu_date']      = date("Y/m/d",strtotime($getInvoiceDetails['deu_date']));  
         $basicInfo['customerinvoicestatus']        = $getInvoiceDetails['invoice_status'];  
      
        }  
           
          
         
          
          
        } 
      return $basicInfo;
    }    
    
    
    public function shipmentdetailsAction(){
        
        $ticketid 			= $this->shipment_ticket;
        $podData = array();
        $shipmentdetails     = $this->modelObj->getShipmentStatusDetails('"'.$ticketid.'"');
        $parcelDetails                                = $this->modelObj->getAllParceldataByTicket($ticketid);
        $shipmentTrackingDetails                      = $this->shipmentTrackingDetails($shipmentdetails);
        $shipmentdetails['shipment_service_type']     = ($shipmentdetails['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery';
        
        $adressarr = array();
        $adressarr[] = ($shipmentdetails['shipment_customer_city'] !='')?$shipmentdetails['shipment_customer_city']:'';
        $adressarr[] = ($shipmentdetails['shipment_customer_country'] !='')?$shipmentdetails['shipment_customer_country']:'';
		$shipmentdetails['shipment_customer_details']	 = 	implode(',',array_filter($adressarr));
				
		$shipmentTrackingDetails['is_dassign_accept'] = ($shipmentTrackingDetails['divername'] != '') ? $shipmentTrackingDetails['is_dassign_accept'] : 'NA';
        $shipmentHistory                              = ($shipmentdetails['last_history_id'] != 0) ? $this->getShipmentStatusHistory($shipmentdetails['last_history_id']) : array();
        $shipmentRejectHistory                        = $this->modelObj->getAcceptRejectsShipmentStatusHistory($ticketid);
        $shipmentCurrentStatus                        = $this->modelObj->getShipmentCurrentStatusAndDriverId($ticketid);
        $shipmentLifeCycle                            = $this->modelObj->getShipmentLifeCycleHistory($ticketid);
        
        if ($shipmentdetails['current_status'] == 'D') {
            $existingPodData = $this->modelObj->getExistingPodData($ticketid);
            $contactName     = $commentData = '';
            foreach ($existingPodData as $key => $pod) {
                $contactName = $pod['delivery_contact_person'];
                $commentData = $pod['delivery_comment'];
           }
            $podData['delivery_contact_person'] =  $contactName;
            $podData['delivery_comment'] =  $commentData;
        }
        $returnData = array();
        $shipmentAdditionaldetails                  = $this->modelObj->getShipmentAdditionalDetails($ticketid);
        $returnData['shipmentData']                 = $shipmentdetails;
        $returnData['podData']                      = $podData;
        $returnData['shipmentAdditionaldetails']    = $shipmentAdditionaldetails;
        $returnData['parcelData']                   = $parcelDetails;
        $returnData['trackingData']                 = $shipmentTrackingDetails;
        $returnData['shipmentHistoryData']          = $shipmentHistory;
        $returnData['rejectHistoryData']            = $shipmentRejectHistory;
        $returnData['shipmentLifeCycle']            = $shipmentLifeCycle;
        $returnData['griddata']                     = $this->getshipmentdetailsjsonAction($ticketid); 
        
        
       
        return $returnData;
    }
    public function shipmentTrackingDetails($getShipmentStatus){
        $shipmentStatus                  = $getShipmentStatus['current_status'];
        $shipment_isRouted               = ($getShipmentStatus['is_shipment_routed'] == 1) ? 'Yes' : 'No';
        $shipment_isDAssign              = ($getShipmentStatus['is_driver_assigned'] == 1) ? 'Yes' : 'No';
        $shipment_isDAccept              = ($getShipmentStatus['is_driver_accept'] == 'Pending') ? 'Pending' : (($getShipmentStatus['is_driver_accept'] == 'YES') ? 'Accepted' : 'Rejected');
        $shipmentDriver                  = $getShipmentStatus['name'];
        $datastatus                      = array();
        $datastatus['is_routed']         = $shipment_isRouted;
        $datastatus['is_dassign']        = $shipment_isDAssign;
        $datastatus['is_dassign_accept'] = $shipment_isDAccept;
        $datastatus['divername']         = $shipmentDriver;
        switch ($shipmentStatus) {
            case 'C':
                $datastatus['ship_status'] = 'Unassigned Shipments';
                break;
            case 'O':
                if ($datastatus['is_dassign_accept'] == 'Rejected') {
                    $datastatus['ship_status'] = 'Rejected Shipments';
                } elseif ($datastatus['is_dassign_accept'] == 'Pending') {
                    $datastatus['ship_status'] = 'Assigned Shipments';
                } else {
                    $datastatus['ship_status'] = 'Operational Shipments';
                }
                break;
            case 'S':
                $datastatus['ship_status'] = 'Saved Shipments';
                break;
            case 'Dis':
                $datastatus['ship_status'] = 'Disputed Shipments';
                break;
            case 'Deleted':
                $datastatus['ship_status'] = 'Deleted Shipments';
                break;
            case 'D':
                $datastatus['ship_status'] = 'Delivered Shipments';
                break;
            case 'Ca':
                $datastatus['ship_status'] = 'Carded Shipments';
                break;
            case 'Rit':
                $datastatus['ship_status'] = 'Return Shipments';
                break;
        }
        return $datastatus;
    }
    public function getshipmentdetailsjsonAction($ticketid){
        $shipmentdetails             = $this->modelObj->getShipmentStatusDetails('"'.$ticketid.'"');
        $refNo                       = $shipmentdetails['instaDispatch_jobIdentity'];
        $shipmentdetailsforReference = $this->modelObj->getShipmentDetailsByReference($refNo);
        $data                        = $innerdata = array();
        if(count($shipmentdetailsforReference) > 0) {
            $count = 1;
            foreach ($shipmentdetailsforReference as $value) { 
                $address = '';
                $address .= ($value['shipment_address1'] != 'null') ? $value['shipment_address1'] . '<br/>' : '';
                $address .= ($value['shipment_address2'] != 'null') ? $value['shipment_address2'] . '<br/>' : '';
                $address .= ($value['shipment_address3'] != 'null') ? $value['shipment_address3'] . '<br/>' : '';
                if ($value['instaDispatch_loadGroupTypeCode'] == 'SAME') {
                    $typeCode = 'Same Day';
                } elseif ($value['instaDispatch_loadGroupTypeCode'] == 'NEXT') {
                    $typeCode = 'Next Day';
                } elseif ($value['instaDispatch_loadGroupTypeCode'] == 'PHONE') {
                    $typeCode = 'Phone';
                } else {
                    $typeCode = 'Regular';
                }
                $stage = '';
                switch ($value['current_status']) {
                    case 'C':
                        $stage = 'Unassigned Shipments';
                        break;
                    case 'O':
                        $stage = 'Operational Shipments';
                        break;
                    case 'S':
                        $stage = 'Saved Shipments';
                        break;
                    case 'Dis':
                        $stage = 'Disputed Shipments';
                        break;
                }
               
                $innerdata[] = array(
                    "sr" => $count,
                    "shipment_ticket" => $value['shipment_ticket'],
                    "shipment_consignment" => $value['instaDispatch_objectIdentity'],
                    "shipment_docket" => $value['instaDispatch_docketNumber'],
                    "shipment_ref" => $value['instaDispatch_jobIdentity'],
                    "shipment_service_type" => ($value['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery',
                    "shipment_create_date" => date("d-m-Y", strtotime($value['shipment_create_date'])),
                    "shipment_required_service_date" => date("d-m-Y", strtotime($value['shipment_required_service_date'])),
                    "shipment_required_service_time" => $value['shipment_required_service_starttime'] . ' - ' . $value['shipment_required_service_endtime'],
                    "shipment_total_weight" => $value['shipment_total_weight'],
                    "shipment_total_volume" => $value['shipment_total_volume'],
                    "shipment_customer_name" => $value['shipment_customer_name'],
                    "shipment_customer_email" => $value['shipment_customer_email'],
                    "shipment_customer_phone" => $value['shipment_customer_phone'],
                    "shipment_postcode" => $value['shipment_postcode'],
                    "shipment_current_stage" => $stage,
                    "shipment_total_attempt" => $value['shipment_total_attempt'],
                    "dataof" => $value['dataof'],
                    "shipment_address" => $address,
                    "shipment_inWarehouse" => $value['is_receivedinwarehouse'],
                    "shipment_type" => $typeCode,
                    "shipment_driverPickup" => $value['is_driverpickupfromwarehouse']
                );
                $count++;
            }
            $data['rows'] = $innerdata;
            return $data;
            
        } else {
            
            $showdata = array(
                'rows' => array());
            
            
            return $showdata;
        }
    }
    public function getShipmentStatusHistory($shipmentHistoryid, $temparr = null){
        $data                       = $this->modelObj->getShipmentStatusHistory($shipmentHistoryid);
        $history                    = array();
        $history['create_date']     = $data['create_date'];
        $history['driver_names']    = $data['name'];//$data['driver_unique_name'];

        $history['assigned_date']   = $data['last_assigned_service_date'];
        $history['assigned_time']   = $data['last_assigned_service_time'];
        $history['service_date']    = $data['actual_given_service_date'];
        $history['service_time']    = $data['actual_given_service_time'];
        $history['next_date']       = $data['next_schedule_date'];
        $history['next_time']       = $data['next_schedule_time'];
        $history['notes']           = $data['notes'];
        $history['driver_comment']  = $data['driver_comment'];
        $history['shipment_status'] = $data['shipment_status'];
        $temparr[]                  = $history;
        if ($data['last_shipment_history_id'] != 0) {
            return $this->getShipmentStatusHistory($data['last_shipment_history_id'], $temparr);
        }
        return $temparr;
    }
}
?>