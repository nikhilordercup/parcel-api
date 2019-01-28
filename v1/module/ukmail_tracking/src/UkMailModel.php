<?php
class UkMailModel extends Singleton
{                
    /**
     * key is tracking code that comes from API and corresponding shipment_code( from shipment_tracking_code) stored in shipment_tracking_table
     * @var type 
     */
     public static $consignmentStatus = array(
        '1' =>  'COLLECTION_AWAITED'
        ,'2' =>  'COLLECTED'
        ,'3' =>  'AT_DELIVERY_LOCATION'
        ,'4' =>  'OUTFORDELIVERY'
        ,'5' =>  'DELIVERYSUCCESS'
        ,'6' =>  'PART_DELIVERY' 
        ,'7' =>  'DELIVERYATTEMPTED'
        ,'8' =>  'DELAYED'
        ,'9' =>  'PLEASECALL'
        ,'10' =>  'DELIVERYREARRANGEDBYRECIPIENT'
    );
    
    public static $PodDeliveryTypeCode = array(
        'DT01' => 'signature',
        'DT02' => 'There was no answer at the address and the parcels have been left in the porch.',
        'DT03' => 'There was no answer at the address and the parcels have been left behind the gate.',
        'DT04' => 'There was no answer at the address and the parcels have been left in the shed.',
        'DT05' => 'There was no answer at the address and the parcels have been left in the garage.',
        'DT06' => 'There was no answer at the address and the parcels have been left with the porter/caretaker.',
        'DT07' => 'There was no answer at the address and the parcels have been left in the conservatory.',
        'DT09' => 'There was no answer at the address and the parcels have been left in another secure location. The secure location where the parcels have been left will be displayed in the comments field.',
        'DT10' => 'There was no answer at the address and the parcels have been left with a neighbour. The recipient name will be the name of the neighbour. The signature image will be the signature of the neighbour. The comments will contain the house number of the neighbour.',
        'DT11' => 'The recipient has missed a delivery attempt, has been left a card and has then chosen to have the parcels left in the safe place they have specified for the 2 nd delivery attempt. The signature image will be a scanned in copy of the card where the customer has given signed authority to leave in a safe place and specified the location of the safe place.',
        'DT12' => 'Unknown status',
        'DT13' => 'Leave your parcel in a safe place.'        
    );

    
    /**
     * This function update table carrier_user_token
     * @param type $username
     * @param type $authenticationKey
     */
    public function updateAuthToDb($username, $authenticationKey)
    {          
        $q1 = "SELECT `id`,`username`,`authentication_token`,`authentication_token_created_at`,`authentication_token_expire_at` 
               FROM `".DB_PREFIX."carrier_user_token` 
               WHERE `username` = '$username' AND `carrier` = 'UKMAIL'";
       
        $res = $this->db->getAllRecords($q1);                         
        if(count($res) > 0)
        {
            $q = "UPDATE `".DB_PREFIX."carrier_user_token` 
                   SET `authentication_token` = '$authenticationKey' 
                   ,`authentication_token_expire_at` =  DATE_ADD( NOW(), INTERVAL 24 HOUR )
                   WHERE  `username` =  '$username' ";              
            $this->db->updateData($q);
        }
        else
        {
            $q = "INSERT INTO ".DB_PREFIX."carrier_user_token(carrier,username,authentication_token,authentication_token_created_at,authentication_token_expire_at)
                   VALUES('UKMAIL','$username','$authenticationKey',NOW(),DATE_ADD( NOW(), INTERVAL 24 HOUR ))";            
            $this->db->executeQuery($q);                                                         
        }                                        
    }
    
    /**
     * This function save consignment tracking information into table ukmail_tracking and shipment_tracking
     * @param type $ConsignmentDetailInfo
     */
    public function saveTrackingInfo($ConsignmentDetailInfo)
    {                                       
        if($ConsignmentDetailInfo->ResultState == 'Successful')
        {        
            $resultState = $ConsignmentDetailInfo->ResultState;
            $errors = $ConsignmentDetailInfo->Errors = '';
            $consignmentNumber = $ConsignmentDetailInfo->ConsignmentNumber; 

            //Getting load_identity by consignment number                    
            $load_identity = $this->getLoadIdentityByConsignmentNo($consignmentNumber);
            
            //Getting shipment_ticket as delivery type and load_identity           
            $deliveryType = ($ConsignmentDetailInfo->StatusCode > 2) ? 'D':'P';            
            $shipment_ticket = $this->getShipmentTicket($deliveryType, $load_identity);
                        
            ///////////////////////////////////////////        
            $statusCode = $ConsignmentDetailInfo->StatusCode;
            $statusMessage = $ConsignmentDetailInfo->StatusMessage;
            $originalDelivery = $ConsignmentDetailInfo->OriginalDelivery;
            $expectedDelivery = $ConsignmentDetailInfo->ExpectedDelivery;
            $collectionDate = $ConsignmentDetailInfo->CollectionDate;
            $quantity = $ConsignmentDetailInfo->Quantity;
            $weight = $ConsignmentDetailInfo->Weight;
            $companyName = $ConsignmentDetailInfo->CompanyName;
            $postalTown = $ConsignmentDetailInfo->PostalTown;
            $foundConsignment = $ConsignmentDetailInfo->FoundConsignment;
            $international = $ConsignmentDetailInfo->International;
            $mail = $ConsignmentDetailInfo->Mail;
            $mailingID = $ConsignmentDetailInfo->MailingID;
            $swapOut = $ConsignmentDetailInfo->SwapOut;
            $returnConsignmentNumber = $ConsignmentDetailInfo->ReturnConsignmentNumber;
            $estimatedTimeOfArrivalStart = $ConsignmentDetailInfo->EstimatedTimeOfArrivalStart;
            $estimatedTimeOfArrivalEnd = $ConsignmentDetailInfo->EstimatedTimeOfArrivalEnd;
            ////////////////////////////////////
            $subEmail = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubEmail;
            $subInstructions = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubInstructions;
            $subName = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubName;
            $subPhone = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubPhone;
            $subRef1 = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubRef1;
            $subRef2 = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubRef2;
            $subSequence = $ConsignmentDetailInfo->ConsignmentSubs->GetConsignmentDetailsSub->SubSequence;
            ///////////////////////////////////

$podDescription = 'NA';
$podQuantity = 0;
$podSequence = 0;
$podTimeStamp = '0000-00-00T00:00:00+00:00';
$podRecipientName = 'NA';
$podDeliveryComments = 'NA';
$podDeliveryTypeCode = 'NA';
if(isset($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod))
{         
    if(is_array($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod))
    {
        $GetConsignmentDetailsPod = $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod;
        $GetConsignmentDetailsPodLastDetail = $GetConsignmentDetailsPod[count($GetConsignmentDetailsPod) -1];
                       
        $podDescription = ($GetConsignmentDetailsPodLastDetail->PodDescription != '') ? $GetConsignmentDetailsPodLastDetail->PodDescription : $podDescription;
        $podQuantity = ($GetConsignmentDetailsPodLastDetail->PodQuantity != '') ? $GetConsignmentDetailsPodLastDetail->PodQuantity : $podQuantity;
        $podSequence = ($GetConsignmentDetailsPodLastDetail->PodSequence != '') ? $GetConsignmentDetailsPodLastDetail->PodSequence : $podSequence;
        $podTimeStamp = ($GetConsignmentDetailsPodLastDetail->PodTimeStamp != '') ? $GetConsignmentDetailsPodLastDetail->PodTimeStamp:$podTimeStamp;
        $podRecipientName = ($GetConsignmentDetailsPodLastDetail->PodRecipientName != '') ? $GetConsignmentDetailsPodLastDetail->PodRecipientName: $podRecipientName;
        $podDeliveryComments = ($GetConsignmentDetailsPodLastDetail->PodDeliveryComments != '') ? $GetConsignmentDetailsPodLastDetail->PodDeliveryComments :'';
        $podDeliveryTypeCode = ($GetConsignmentDetailsPodLastDetail->PodDeliveryTypeCode != '') ? $GetConsignmentDetailsPodLastDetail->PodDeliveryTypeCode : $podDeliveryTypeCode;
    }
    else
    {
        $podDescription = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDescription != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDescription : $podDescription;
        $podQuantity = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodQuantity != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodQuantity : $podQuantity;
        $podSequence = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodSequence != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodSequence : $podSequence;
        $podTimeStamp = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodTimeStamp != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodTimeStamp:$podTimeStamp;
        $podRecipientName = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodRecipientName != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodRecipientName: $podRecipientName;
        $podDeliveryComments = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDeliveryComments != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDeliveryComments :$ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDescription;
        $podDeliveryTypeCode = ($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDeliveryTypeCode != '') ? $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod->PodDeliveryTypeCode : $podDeliveryTypeCode;
    }    
}
		
                    
            $createdOn = date('Y-m-d H:i:s');

            // Check if already status saved
            $q2 = "select id from ".DB_PREFIX."ukmail_tracking 
                   where load_identity = '$load_identity' and consignmentNumber = '$consignmentNumber'";
            $res2 = $this->db->getAllRecords($q2);
            if( count($res2) > 0 )
            {                       
                $sql = " UPDATE ".DB_PREFIX."ukmail_tracking 
                       SET `collectionDate` = '".$collectionDate."',`originalDelivery` =  '$originalDelivery'
                       ,`expectedDelivery` =  '$expectedDelivery',`estimatedTimeOfArrivalStart` =  '$estimatedTimeOfArrivalStart',`estimatedTimeOfArrivalEnd` =  '$estimatedTimeOfArrivalEnd'
                       ,`quantity` =  '$quantity',`weight` =  '$weight',`foundConsignment` =  '$foundConsignment',`companyName` =  '$companyName',`postalTown` =  '$postalTown'
                       ,`international` =  '$international',`mail` =  '$mail',`mailingID` =  '$mailingID',`swapOut` =  '$swapOut',`returnConsignmentNumber` =  '$returnConsignmentNumber'
                       ,`subEmail` =  '$subEmail',`subInstructions` =  '$subInstructions',`subName` =  '$subName',`subPhone` =  '$subPhone',`subRef1` =  '$subRef1'
                       ,`subRef2` =  '$subRef2',`subSequence` =  '$subSequence',`podDescription` =  '$podDescription',`podQuantity` =  '$podQuantity',`podSequence` =  '$podSequence'
                       ,`podTimeStamp` =  '$podTimeStamp',`podRecipientName` =  '$podRecipientName',`podDeliveryComments` =  '$podDeliveryComments',`podDeliveryTypeCode` =  '$podDeliveryTypeCode'
                       ,`statusCode` =  '$statusCode',`statusMessage` =  '$statusMessage',`resultState` =  '$resultState',`errors` =  '$errors',`createdOn` =  '$createdOn'                   
                       WHERE  `consignmentNumber` =  '$consignmentNumber' and `load_identity` = '$load_identity'"; 
                $lastInsertId = $this->db->updateData($sql);
            }
            else
            {
                $sql = "insert into ".DB_PREFIX."ukmail_tracking
                    (
                        consignmentNumber,load_identity,collectionDate,originalDelivery,expectedDelivery,estimatedTimeOfArrivalStart,estimatedTimeOfArrivalEnd,quantity
                        ,weight,foundConsignment,companyName,postalTown,international,mail,mailingID,swapOut,returnConsignmentNumber,subEmail,subInstructions,subName
                        ,subPhone,subRef1,subRef2,subSequence,podDescription,podQuantity,podSequence,podTimeStamp,podRecipientName,podDeliveryComments,podDeliveryTypeCode
                        ,statusCode,statusMessage,resultState,errors,createdOn
                    )
                    values( 
                        '$consignmentNumber','$load_identity','$collectionDate','$originalDelivery','$expectedDelivery','$estimatedTimeOfArrivalStart'
                        ,'$estimatedTimeOfArrivalEnd',$quantity,$weight,'$foundConsignment','$companyName','$postalTown','$international','$mail'
                        ,'$mailingID','$swapOut','$returnConsignmentNumber','$subEmail','$subInstructions','$subName','$subPhone','$subRef1','$subRef2'
                        ,$subSequence,'$podDescription',$podQuantity,$podSequence,'$podTimeStamp','$podRecipientName','$podDeliveryComments'
                        ,'$podDeliveryTypeCode','$statusCode','$statusMessage','$resultState','$errors','$createdOn'                                
                    )";
                    $lastInsertId = $this->db->executeQuery($sql);
            }

            // We are updating shipment_tracking table        
            if($lastInsertId > 0 )
            {
                $trackingId = '';
                $consignmentStatus = $ConsignmentDetailInfo->ConsignmentStatus->GetConsignmentDetailsStatus;
                
                
                $isMultipleStatus = FALSE;
                $isMultipleStatus = is_array($consignmentStatus); 

                $queryContinue = FALSE;
                if(count($consignmentStatus) > 0)
                { 
                    $cStatusQuery = "insert into ".DB_PREFIX."shipment_tracking(shipment_ticket,load_identity,code,create_date,carrier,status_detail,event_id,origin)values";
                    foreach($consignmentStatus as $conStatus)
                    {                         
                        if($isMultipleStatus)
                        {                            
                            $statusCode1 = self::$consignmentStatus[$conStatus->StatusCode];
                        }
                        else
                        {                           
                            $statusCode1 = self::$consignmentStatus[$consignmentStatus->StatusCode];
                        }
                                            
                        $q1 = "select id from ".DB_PREFIX."shipment_tracking 
                               where load_identity = '$load_identity'  and carrier = 'UKMAIL' and code = '$statusCode1'";
                        $res1 = $this->db->getOneRecord($q1);
                        $res1 = ($res1 != NULL) ? $res1 : array();
                        
                        if( count($res1) > 0 ){ $trackingId = $res1['id']; continue; }
                        
                        $queryContinue = TRUE;
                        $statusCode = ($isMultipleStatus) ? self::$consignmentStatus[$conStatus->StatusCode]: self::$consignmentStatus[$consignmentStatus->StatusCode]; 
                        $statusDescription = ($isMultipleStatus) ? $conStatus->StatusDescription : $consignmentStatus->StatusDescription;
                        $statusSequence = ($isMultipleStatus) ? $conStatus->StatusSequence : $consignmentStatus->StatusSequence;
                        $statusTimeStamp = ($isMultipleStatus) ? date("Y-m-d H:i:s", strtotime($conStatus->StatusTimeStamp)) : date("Y-m-d H:i:s", strtotime($consignmentStatus->StatusTimeStamp)); 
                        $cStatusQuery .= "('$shipment_ticket','$load_identity','$statusCode','$statusTimeStamp','UKMAIL','$statusDescription','$statusSequence','API')"; 
                        $cStatusQuery .= ",";                    
                    }
                    $cStatusQuery = rtrim($cStatusQuery,',');   
                    if($queryContinue)
                    {
                        $trackingId = $this->db->executeQuery($cStatusQuery);                        
                    }
                    
                    // Making new row for shipments_pod table
                    $podId = 0;
                    $query3 = "SELECT pod_id, shipment_ticket FROM ".DB_PREFIX."shipments_pod WHERE shipment_ticket = '$shipment_ticket' order by pod_id desc";
                    $res3 = $this->db->getOneRecord($query3);                         
                    $res3 = ($res3 != NULL) ? $res3 : array();
                    if(count($res3) > 0)
                    {
                        $podId = $res3['pod_id'];
                    }
                    else
                    {           
                         if(isset($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod))
                         {
                             
                             if(is_array($ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod))
                             {
                                 $GetConsignmentDetailsPod = $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod;
                                 $GetConsignmentDetailsPodLastDetail = $GetConsignmentDetailsPod[count($GetConsignmentDetailsPod) -1];
                                 
                                 $podObj = $GetConsignmentDetailsPodLastDetail;                                   
                                 $PodDeliveryType =  ($podObj->PodDeliveryTypeCode != '') ? self::$PodDeliveryTypeCode[$podObj->PodDeliveryTypeCode]:'';
                                 $PodDeliveryComments = ($podObj->PodDeliveryComments != '') ? $podObj->PodDeliveryComments : $podObj->PodDescription;
                        
                             }
                             else
                             {
                                 $podObj = $ConsignmentDetailInfo->ConsignmentPods->GetConsignmentDetailsPod;  
                                 //var_dump($podObj->PodDeliveryTypeCode);die;
                                 $PodDeliveryType =  ($podObj->PodDeliveryTypeCode != '') ? self::$PodDeliveryTypeCode[$podObj->PodDeliveryTypeCode]:'';
                                 $PodDeliveryComments = ($podObj->PodDeliveryComments != '') ? $podObj->PodDeliveryComments : $podObj->PodDescription;
                             }
		                
		                        
		                                                                                                              
		                $sql1 = "insert into ".DB_PREFIX."shipments_pod
		                (
		                    shipment_ticket,driver_id,type,value,pod_name,comment,contact_person,latitude,longitude,status,create_date,is_custom_create,tracking_id
		                )
		                values( 
		                    '$shipment_ticket','0','','','$PodDeliveryType','$PodDeliveryComments','$podObj->PodRecipientName','0','0','1','$createdOn'
		                    ,'0','$trackingId'       
		                )"; 
		                $podId = $this->db->executeQuery($sql1);
                       }            
                        
                    }
                    
                    // Making new row for tracking_pod table                                        
                    $query4 = "SELECT id,tracking_id,pod_id FROM ".DB_PREFIX."tracking_pod WHERE tracking_id = '$trackingId' and pod_id = '$podId'";
                    $res4 = $this->db->getOneRecord($query4);    
                    $res4 = ($res4 != NULL) ? $res3 : array();
                    if(count($res4) == 0)
                    {
                        $sql1 = "insert into ".DB_PREFIX."tracking_pod(tracking_id,pod_id)values( '$trackingId','$podId')";
                        $this->db->executeQuery($sql1);
                    }
                                                                                
                    // Updating shipment_service table column tracking_code by latest status
                    $lastConsignmentStatusInfo = ($isMultipleStatus) ? $consignmentStatus[count($consignmentStatus) - 1]:$consignmentStatus;
                    $lastConsignmentStatus = self::$consignmentStatus[$lastConsignmentStatusInfo->StatusCode];                    
                    $qToUShipSer = "UPDATE `".DB_PREFIX."shipment_service` 
                            SET `tracking_code` = '".$lastConsignmentStatus."'
                            WHERE  `load_identity` =  '$load_identity'";              
                    $this->db->updateData($qToUShipSer);                    
                }            
            }
        }        
    }
    
    /**
     * This function gives load_identity by consignment number
     * @param type $consignmentNumber
     * @return type
     */
    public function getLoadIdentityByConsignmentNo($consignmentNumber)
    {
        //Getting load_identity by consignment number
        $query1 = "SELECT `load_identity` FROM ".DB_PREFIX."shipment_service WHERE label_tracking_number = '$consignmentNumber'";
        $res = $this->db->getOneRecord($query1);         
        $load_identity = ($res['load_identity'] != '') ? $res['load_identity'] : $consignmentNumber;
        return $load_identity;
    }
    
    /**
     * This ticket give shipment_ticket on basis of delivery type and load identity
     * @param type $deliveryType
     * @param type $loadIdentity
     * @return type
     */
    public function getShipmentTicket($deliveryType, $loadIdentity)
    {
        $query2 = "SELECT `shipment_ticket` FROM ".DB_PREFIX."shipment WHERE shipment_service_type = '$deliveryType' and instaDispatch_loadIdentity = '$loadIdentity'";
        $res2 = $this->db->getOneRecord($query2);         
        $shipment_ticket = $res2['shipment_ticket'];
        return $shipment_ticket;
    }
   
    /**
     * This function gives array of shipments to be tracked
     * @return Array
     */
    public function getShipmentToTrack()
    {
        //$ukMailCarrierId = self::UKMAIL;
        $query1 = "SELECT ss.id,ss.load_identity,ss.carrier,ss.`status`,ss.accountkey,ss.parent_account_key
                   ,ss.label_tracking_number,ss.tracking_code,sh.company_id 
                   FROM ".DB_PREFIX."shipment_service as ss
                   
                   JOIN ".DB_PREFIX."shipment AS sh
                   ON ss.load_identity = sh.instaDispatch_loadIdentity
                   AND sh.shipment_service_type = 'P'

                   WHERE sh.carrier_code = 'ukmail' 
                   AND ss.accountkey != ''
                   AND ss.parent_account_key != ''
                   AND ss.`status` = 'success' 
                   AND ss.tracking_code != 'DELIVERYSUCCESS'                       
                   ";     
        $shipments = $this->db->getAllRecords($query1);         
        return $shipments;                    
    }
    
    /**
     * This function gives array of username and password for passed company and account
     * @param Integer $companyId
     * @param String $accoutNo
     * @return Array
     */
    public function getAccountCredential($companyId, $accoutNo)
    {
        $query1 = "select username,password from ".DB_PREFIX."courier_vs_company
                   where company_id = $companyId and account_number = '$accoutNo' 
                   ";        
        $credentials = $this->db->getAllRecords($query1);                         
        return (count($credentials) > 0) ? $credentials[0]:array();
    }
}
?>
