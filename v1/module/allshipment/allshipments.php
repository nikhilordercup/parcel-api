<?php
class allShipments extends Icargo
{
    public $modelObj = null;

    public function __construct($param)
    {
        parent::__construct(array(
            "email" => $param->email,
            "access_token" => $param->access_token
        ));
        $this->modelObj = AllShipment_Model::getInstanse();
    }

    public function getallshipments($param)
    {
        $html  = '';
        $html2 = '';
        $html3 = '';

        //$html .= isset($param->data->account)?' AND CI.accountnumber = "'.$param->data->account.'" ':'';
        $html .= (isset($param->data->customer) && ($param->data->customer != '')) ? ' AND S.customer_id =  "' . $param->data->customer . '" ' : '';


        $html .= (isset($param->warehouse_id) && ($param->warehouse_id > 0) && ($param->warehouse_id != '')) ? ' AND S.warehouse_id = "' . $param->warehouse_id . '" ' : '';


        $html .= (isset($param->data->job_identity) && ($param->data->job_identity != '')) ? ' AND S.instaDispatch_loadIdentity = "' . $param->data->job_identity . '" ' : '';


        $html .= (isset($param->data->job_type) && ($param->data->job_type != '')) ? ' AND S.shipment_type = "' . $param->data->job_type . '" ' : '';


        $html .= (isset($param->data->booking_date) && ($param->data->booking_date != '')) ? ' AND S.booking_date =  "' . $param->data->booking_date . '" ' : '';

        if (isset($param->data->globalbookingdatefilter) && ($param->data->globalbookingdatefilter != '')) {
            $dates = explode('/', $param->data->globalbookingdatefilter);
        }

        $html .= (isset($param->data->globalbookingdatefilter) && ($param->data->globalbookingdatefilter != '')) ? 'AND (S.booking_date BETWEEN "' . $dates[0] . '" AND "' . $dates[1] . '")' : '';

        $html .= (isset($param->data->carrier) && ($param->data->carrier != '')) ? ' AND S.carrier = "' . $param->data->carrier . '" ' : '';

        $html .= (isset($param->data->booked_by) && ($param->data->booked_by != '')) ? ' AND S.booked_by = "' . $param->data->booked_by . '" ' : '';

        $html .= (isset($param->data->amount) && ($param->data->amount != '')) ? ' AND S.amount =  ' . $param->data->amount . ' ' : '';

        $html .= (isset($param->data->isInvoiced) && ($param->data->isInvoiced != '')) ? ' AND S.isInvoiced = "' . $param->data->isInvoiced . '" ' : '';


        $html .= (isset($param->data->service) && ($param->data->service != '')) ? ' AND S.service_name = "' . $param->data->service . '" ' : '';



        $html .= (isset($param->data->shipment_status) && ($param->data->shipment_status != 'select')) ? ' AND  S.tracking_code = "' . $param->data->shipment_status . '"' : '';

        $html2 .= (isset($param->data->postcode) && ($param->data->postcode != '')) ? ' AND S.shipment_postcode LIKE "%' . $param->data->postcode . '%"' : '';

        $html2 .= (isset($param->data->pickup_date) && ($param->data->pickup_date != '')) ? ' AND S.shipment_required_service_date =  "' . $param->data->pickup_date . '" AND S.shipment_service_type = "P"' : '';



        if (isset($param->data->globalcollectiondatefilter) && ($param->data->globalcollectiondatefilter != '')) {
            $dates2 = explode('/', $param->data->globalcollectiondatefilter);
        }

        $html2 .= (isset($param->data->globalcollectiondatefilter) && ($param->data->globalcollectiondatefilter != '')) ? 'AND (S.shipment_required_service_date BETWEEN "' . $dates2[0] . '" AND "' . $dates2[1] . '")' : '';


        if ($html2 != '' && $html == '') { // Only Serch Two's Data coming
            $identityarray     = array();
            $limitstr          = "LIMIT " . $param->datalimitpre . ", " . $param->datalimitpost . "";
            $shipmentsDataDrop = $this->modelObj->getAllShipmentsIdentity($html2, $limitstr);
            foreach ($shipmentsDataDrop as $data) {
                $identityarray[] = $data['instaDispatch_loadIdentity'];
            }
            $html3 .= " AND S.instaDispatch_loadIdentity  IN(" . '"' . implode('","', $identityarray) . '"' . ") ";

            $shipmentsData = $this->modelObj->getAllShipments($html3);
        }
        if ($html2 == '' && $html != '') { // Only Serch One's Data coming
            $identityarray = array();
            $limitstr      = "LIMIT " . $param->datalimitpre . ", " . $param->datalimitpost . "";
            $shipmentsData = $this->modelObj->getAllShipmentsDrop($param->warehouse_id, $param->company_id, $limitstr, $html);

            foreach ($shipmentsData as $data) {
                $identityarray[] = $data['instaDispatch_loadIdentity'];
            }
            $html3 .= " AND S.instaDispatch_loadIdentity  IN(" . '"' . implode('","', $identityarray) . '"' . ") ";
            $shipmentsData = $this->modelObj->getAllShipments($html3 . $html2);

        }
        if ($html2 != '' && $html != '') { // Both Serch's Data coming
            $seachoneBucketarray = array();
            $seachtwoBucketarray = array();
            $identityarray       = array();
            $identityarray2      = array();
            $limitstr            = "LIMIT " . $param->datalimitpre . ", " . $param->datalimitpost . "";
            $seachoneBucket      = $this->modelObj->getAllShipmentsDrop($param->warehouse_id, $param->company_id, "", $html);
            foreach ($seachoneBucket as $data) {
                $seachoneBucketarray[] = $data['instaDispatch_loadIdentity'];
            }
            $seachtwoBucket = $this->modelObj->getAllShipmentsIdentity($html2, "");
            foreach ($seachtwoBucket as $data) {
                $seachtwoBucketarray[] = $data['instaDispatch_loadIdentity'];
            }
            $commondata = array_intersect($seachoneBucketarray, $seachtwoBucketarray);
            $html31     = " AND S.instaDispatch_loadIdentity  IN(" . '"' . implode('","', $commondata) . '"' . ") ";

            $shipmentsDataDrop = $this->modelObj->getAllShipmentsDrop($param->warehouse_id, $param->company_id, $limitstr, $html31);
            foreach ($shipmentsDataDrop as $data) {
                $identityarray[] = $data['instaDispatch_loadIdentity'];
            }

            $html3 .= " AND S.instaDispatch_loadIdentity  IN(" . '"' . implode('","', $identityarray) . '"' . ") ";
            //$shipmentsData = $this->modelObj->getAllShipments($html3.$html2);
            $shipmentsData = $this->modelObj->getAllShipments($html3);
        }
        if ($html2 == '' && $html == '') { // Both Serch's Data not coming
            $limitstr      = "LIMIT " . $param->datalimitpre . ", " . $param->datalimitpost . "";
            $shipmentsData = $this->modelObj->getAllShipmentsDrop($param->warehouse_id, $param->company_id, $limitstr, $html);



            $identityarray = array();
            foreach ($shipmentsData as $data) {
                $identityarray[] = $data['instaDispatch_loadIdentity'];
            }
            $html3 .= " AND S.instaDispatch_loadIdentity  IN(" . '"' . implode('","', $identityarray) . '"' . ") ";
            $shipmentsData = $this->modelObj->getAllShipments($html3 . $html2);
        }

        $shipmentsPrepareData = $this->_prepareShipments($shipmentsData);
        return $shipmentsPrepareData;
    }

    private function _getCurrentTrackingStatusByLoadIdentity($load_identity){
        $currentTrackingStatus = $this->modelObj->getCurrentTrackingStatusByLoadIdentity($load_identity);
        return $currentTrackingStatus["code_translation"]; 
    }

    private function _prepareShipments($shipmentsData)
    {
        $dataArray  = array();
        $returndata = array();
        foreach ($shipmentsData as $key => $val) {
            $dataArray[$val['instaDispatch_loadIdentity']][strtoupper($val['instaDispatch_loadGroupTypeCode'])][$val['shipment_service_type']][] = $val;
        }
        if (count($dataArray) > 0) {
            foreach ($dataArray as $innerkey => $innerval) {
                $data                 = array();
                $data['job_identity'] = $innerkey;
                $data['shipment_status'] = $this->_getCurrentTrackingStatusByLoadIdentity($data['job_identity']);
                $data['job_type']     = key($innerval);
                $shipmentstatus       = array();
                $data['delivery']     = $innerkey;
                $jobIdentity          = $innerkey;
                if (key($innerval) == 'SAME') {
                    $data['action'] = 'sameday';
                    if (array_key_exists('P', $innerval['SAME'])) {
                        foreach ($innerval['SAME']['P'] as $pickupkey => $pickupData) {
                            $data['customer']           = $pickupData['shipment_customer_name'];
                            $data['account']            = $pickupData['shipment_customer_account'];
                            $data['service']            = $pickupData['shipment_service_name'];
                            $data['carrier']            = $pickupData['carrier'];
							$data['carrier_icon']       = $pickupData['carrier_icon'];
                            $data['amount']             = $pickupData['shipment_customer_price'];
                            $data['booked_by']          = $pickupData['booked_by'];
                            $data['isInvoiced']         = $pickupData['isInvoiced'];
                            $data['show']               = 'y';
                            $data['collectionpostcode'] = $pickupData['shipment_postcode'];
                            $data['collection']         = $pickupData['shipment_postcode'] . ', ' . $pickupData['shipment_customer_country'];
                            //$data['pickup_date']        = $pickupData['shipment_required_service_date'] . '  ' . $pickupData['shipment_required_service_starttime'];
                            $data['pickup_date']        = date("d/m/Y",strtotime($pickupData['shipment_required_service_date'])) . '  ' . $pickupData['shipment_required_service_starttime'];
							$data['create_date']        = date("Y-m-d",strtotime($pickupData['shipment_create_date']));
							$data['cancel_status']      = $pickupData['cancel_status'];
                            $shipmentstatus[]           = $pickupData['current_status'];
                        }
                    }
                    if (array_key_exists('D', $innerval['SAME'])) {
                        $temp = array();
                        foreach ($innerval['SAME']['D'] as $key => $row) {
                            $temp[$key] = $row['icargo_execution_order'];
                        }
                        array_multisort($temp, SORT_ASC, $innerval['SAME']['D']);
                        $lastDeliveryarray        = end($innerval['SAME']['D']);
                        $data['deliverypostcode'] = $lastDeliveryarray['shipment_postcode'];
                        $data['delivery']         = $lastDeliveryarray['shipment_postcode'] . ', ' . $lastDeliveryarray['shipment_customer_country'];
                        foreach ($innerval['SAME']['D'] as $deliverykey => $deliveryData) {
                            $shipmentstatus[] = $deliveryData['current_status'];
                        }
                    }
                    /*$arrd = array_unique($shipmentstatus);
                    if (count($arrd) > 1) {
                        $data['shipment_status'] = 'Not Completed';
                    } elseif (count($arrd) == 1) {
                        if ($arrd[0] == 'D') {
                            $data['shipment_status'] = 'Completed';
                        } else {
                            $data['shipment_status'] = 'Not Completed';
                        }
                    }*/
                    $returndata[] = $data;
                }
                if (key($innerval) == 'NEXT') {
                    $data['action'] = 'nextday';
                    if (array_key_exists('P', $innerval['NEXT'])) {
                        foreach ($innerval['NEXT']['P'] as $pickupkey => $pickupData) {
                            $labelArr = json_decode($pickupData['label_json']);

                            //print_r($labelArr); continue;
                            if( @is_object($labelArr) && @count($labelArr)>0 ) {
                                    $collectionReference = isset($labelArr->label->collectionjobnumber) ? $labelArr->label->collectionjobnumber : $labelArr->label->tracking_number;
                            } else {
                                    $collectionReference = "";
                            }

                            $data['customer']    = $pickupData['shipment_customer_name'];
                            $data['account']     = $pickupData['shipment_customer_account'];
                            $data['service']     = $pickupData['shipment_service_name'];
                            $data['carrier']	 = $pickupData['carrier'];
							$data['carrier_icon']= $pickupData['carrier_icon'];//http://localhost/projects/icargo/.$pickupData[carrier_icon];
                            $data['amount']      = $pickupData['shipment_customer_price'];
                            $data['booked_by']   = $pickupData['booked_by'];
                            $data['isInvoiced']  = $pickupData['isInvoiced'];
                            $data['collection']  = $pickupData['shipment_postcode'] . ', ' . $pickupData['shipment_customer_country'];
                            //$data['pickup_date'] = $pickupData['shipment_required_service_date'] . '  ' . $pickupData['shipment_required_service_starttime'];
                            $data['pickup_date'] = date("d/m/Y",strtotime($pickupData['shipment_required_service_date'])) . '  ' . $pickupData['shipment_required_service_starttime'];
							$data['create_date'] = date("Y-m-d",strtotime($pickupData['shipment_create_date']));
							$data['cancel_status'] = $pickupData['cancel_status'];
							$data['collection_reference'] = $collectionReference;
							
                            $shipmentstatus[]    = $pickupData['current_status'];
                        }
                    }
                    if (array_key_exists('D', $innerval['NEXT'])) {
                        krsort($innerval['NEXT']['D']);
                        $deliveryPostcode = array();
                        foreach ($innerval['NEXT']['D'] as $deliverykey => $deliveryData) {
                            $deliveryPostcode[$deliveryData['icargo_execution_order']] = $deliveryData['shipment_postcode'] . ', ' . $deliveryData['shipment_customer_country'];
                            $shipmentstatus[]                                          = $deliveryData['current_status'];
                        }
                        krsort($deliveryPostcode);
                        $data['delivery'] = end($deliveryPostcode);
                    }
                    /*$arrd = array_unique($shipmentstatus);
                    if (count($arrd) > 1) {
                        $data['shipment_status'] = 'Not Completed';
                    } elseif (count($arrd) == 1) {
                        if ($arrd[0] == 'D') {
                            $data['shipment_status'] = 'Completed';
                        } else {
                            $data['shipment_status'] = 'Not Completed';
                        }
                    }*/
                    $returndata[] = $data;
                }
            }
        }

        return $returndata;
    }

    public function getSameDayShipmentDetails($param)
    {
        $allInfo = $this->_getBasicInfoOfShipment($param['identity']);
        return array(
            'sameday' => array(
                'basicinfo' => $allInfo['basicInfo'],
                'priceinfo' => $allInfo['priceinfo'],
                'trackinginfo' => $allInfo['trackinginfo'],
                'podinfo' => $allInfo['podinfo']
            )
        );
    }

    public function getNextDayShipmentDetails($param)
    {
        $allInfo = $this->_getBasicInfoOfShipment($param['identity']);
        return array(
            'nextday' => array(
                'basicinfo' => $allInfo['basicInfo'],
                'priceinfo' => $allInfo['priceinfo'],
                'trackinginfo' => $allInfo['trackinginfo'],
                'podinfo' => $allInfo['podinfo'],
				'parcelInfo'=>$allInfo['parcelInfo']
            )
        );
    }


    private function _getBasicInfoOfShipment($identity)
    {
        $trackinginfo           = array();
        $shipmentsInfoData      = $this->modelObj->getShipmentsDetail($identity);
		$parcelInfo             = $this->modelObj->getAllParcelsByIdentity($identity);
        $priceversion           = $this->modelObj->getShipmentsPriceVersion($identity);
        $carrierPrice           = $this->modelObj->getShipmentsPriceDetailCarrier($identity, $shipmentsInfoData[0]['carrierid'], $shipmentsInfoData[0]['companyid'], $priceversion);
        $customerPrice          = $this->modelObj->getShipmentsPriceDetailCustomer($identity, $shipmentsInfoData[0]['carrierid'], $shipmentsInfoData[0]['companyid'], $priceversion);
        $shipmentsPriceInfoData = $this->ManagePriceData($carrierPrice, $customerPrice);
        $trackinginfo           = $this->getShipmentsTrackingDetails($identity);

        //$shipmentsPriceInfoData = $this->ManagePriceData($this->modelObj->getShipmentsPriceDetail($identity,$shipmentsInfoData[0]['carrierid'],$shipmentsInfoData[0]['companyid'],$priceversion));
        $basicInfo = array();
        if (count($shipmentsInfoData) > 0) {
            $basicInfo['totaldrop']            = count($shipmentsInfoData);
            $basicInfo['customer']             = $shipmentsInfoData[0]['customer'];
            $basicInfo['service']              = $shipmentsInfoData[0]['service'];
            $basicInfo['chargeableunit']       = $shipmentsInfoData[0]['chargeableunit'];
            $basicInfo['user']                 = $shipmentsInfoData[0]['user'];
            $basicInfo['carrier']              = $shipmentsInfoData[0]['carrier'];
            $basicInfo['carriername']          = $shipmentsInfoData[0]['carriername'];
            $basicInfo['reference']            = $shipmentsInfoData[0]['reference'];
            $basicInfo['carrierreference']     = $shipmentsInfoData[0]['carrierreference'];
            $basicInfo['carrierbillingacount'] = $shipmentsInfoData[0]['carrierbillingacount'];
            $basicInfo['chargeablevalue']      = $shipmentsInfoData[0]['chargeablevalue'];

            $basicInfo['customerbaseprice'] = $shipmentsInfoData[0]['customerbaseprice'];
            $basicInfo['customersurcharge'] = $shipmentsInfoData[0]['customersurcharge'];
            $basicInfo['customersubtotal']  = $shipmentsInfoData[0]['customersubtotal'];
            $basicInfo['customertax']       = $shipmentsInfoData[0]['customertax'];
            $basicInfo['customertotal']     = $shipmentsInfoData[0]['customertotalprice'];

            $basicInfo['carrierbaseprice'] = $shipmentsInfoData[0]['carrierbaseprice'];
            $basicInfo['carriersurcharge'] = $shipmentsInfoData[0]['carriersurcharge'];
            $basicInfo['carriersubtotal']  = $shipmentsInfoData[0]['carriersubtotal'];
            $basicInfo['carriertax']       = $shipmentsInfoData[0]['carriertax'];
            $basicInfo['carriertotal']     = $shipmentsInfoData[0]['carriertotalprice'];

            $basicInfo['customerinvoicereference'] = $shipmentsInfoData[0]['customerinvoicereference'];
            $basicInfo['bookingtype']              = $shipmentsInfoData[0]['bookingtype'];
            $basicInfo['customer_desc']            = $shipmentsInfoData[0]['customer_desc'];
            $basicInfo['customerreference']        = $shipmentsInfoData[0]['customerreference'];
            $basicInfo['waitandreturn']            = $shipmentsInfoData[0]['waitandreturn'];
            $basicInfo['waitandreturn']            = ($basicInfo['waitandreturn'] == 'false') ? 'NO' : 'YES';
            $basicInfo['transittime']              = $shipmentsInfoData[0]['transittime'];
            $basicInfo['bookingdate']              = date("Y/m/d", strtotime($shipmentsInfoData[0]['bookingdate']));
            $basicInfo['expecteddate']             = date("Y/m/d", strtotime($shipmentsInfoData[0]['expecteddate']));
            $basicInfo['expectedstarttime']        = $shipmentsInfoData[0]['expectedstarttime'];
            $basicInfo['expectedendtime']          = $shipmentsInfoData[0]['expectedendtime'];
            $basicInfo['isinsured']                = "N/A";
            $basicInfo['insurencevalue']           = "N/A";
            $basicInfo['handcost']                 = "N/A";
            $basicInfo['flowtype']                 = "Domestic";

            $shipmentsurchargeData   = $this->modelObj->getShipmentsurchargeData($identity);
            $basicInfo['chargedata'] = array();
            if (count($shipmentsurchargeData) > 0) {
                foreach ($shipmentsurchargeData as $key => $val) {
                    $basicInfo['chargedata'][$val['api_key']][] = array(
                        'price_code' => $val['price_code'],
                        'price' => $val['price']
                    );
                }
            }
            foreach ($shipmentsInfoData as $key => $val) {
                if ($val['shipment_type'] == 'P') {
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
                    $basicInfo['shipment_ticket'][]          = $val['shipment_ticket'];
                } else {
                    $data                             = array();
                    $data['deliverycustomername']     = $val['customername'];
                    $data['deliverycustomeraddress1'] = $val['address_line1'];
                    $data['deliverycustomeraddress2'] = $val['address_line2'];
                    $data['deliverycustomeremail']    = $val['customeremail'];
                    $data['deliverycustomerphone']    = $val['customerphone'];
                    $data['deliverycustomercountry']  = $val['country'];
                    $data['deliverycustomercity']     = $val['city'];
                    $data['deliverycustomerpostcode'] = $val['postcode'];
                    $data['deliverycustomerstate']    = $val['state'];
                    $basicInfo['shipment_ticket'][]   = $val['shipment_ticket'];
                    $basicInfo['deliveryaddress'][]   = $data;

                }
            }
            $getInvoiceDetails = $this->modelObj->getShipmentsInvoiceDetail($identity);
            if ($getInvoiceDetails != '') {
                $basicInfo['customerinvoicetype']      = $getInvoiceDetails['invoice_type'];
                $basicInfo['customerinvoicereference'] = $getInvoiceDetails['invoice_reference'];
                $basicInfo['customerinvoicetotal']     = $getInvoiceDetails['total'];
                $basicInfo['customerinvoiceraised_on'] = date("Y/m/d", strtotime($getInvoiceDetails['raised_on']));
                $basicInfo['customerinvoicedeu_date']  = date("Y/m/d", strtotime($getInvoiceDetails['deu_date']));
                $basicInfo['customerinvoicestatus']    = $getInvoiceDetails['invoice_status'];

            }





        }
        $podinfo = $this->getShipmentsPodDetails('"' . implode('","', $basicInfo['shipment_ticket']) . '"');
        return array(
            'basicInfo' => $basicInfo,
            'priceinfo' => $shipmentsPriceInfoData,
            'trackinginfo' => $trackinginfo,
            'podinfo' => $podinfo,
			'parcelInfo'=>$parcelInfo
        );
    }


    public function shipmentdetailsAction()
    {

        $ticketid                                 = $this->shipment_ticket;
        $podData                                  = array();
        $shipmentdetails                          = $this->modelObj->getShipmentStatusDetails('"' . $ticketid . '"');
        $parcelDetails                            = $this->modelObj->getAllParceldataByTicket($ticketid);
        $shipmentTrackingDetails                  = $this->shipmentTrackingDetails($shipmentdetails);
        $shipmentdetails['shipment_service_type'] = ($shipmentdetails['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery';

        $adressarr                                    = array();
        $adressarr[]                                  = ($shipmentdetails['shipment_customer_city'] != '') ? $shipmentdetails['shipment_customer_city'] : '';
        $adressarr[]                                  = ($shipmentdetails['shipment_customer_country'] != '') ? $shipmentdetails['shipment_customer_country'] : '';
        $shipmentdetails['shipment_customer_details'] = implode(',', array_filter($adressarr));

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
            $podData['delivery_contact_person'] = $contactName;
            $podData['delivery_comment']        = $commentData;
        }
        $returnData                              = array();
        $shipmentAdditionaldetails               = $this->modelObj->getShipmentAdditionalDetails($ticketid);
        $returnData['shipmentData']              = $shipmentdetails;
        $returnData['podData']                   = $podData;
        $returnData['shipmentAdditionaldetails'] = $shipmentAdditionaldetails;
        $returnData['parcelData']                = $parcelDetails;
        $returnData['trackingData']              = $shipmentTrackingDetails;
        $returnData['shipmentHistoryData']       = $shipmentHistory;
        $returnData['rejectHistoryData']         = $shipmentRejectHistory;
        $returnData['shipmentLifeCycle']         = $shipmentLifeCycle;
        $returnData['griddata']                  = $this->getshipmentdetailsjsonAction($ticketid);



        return $returnData;
    }
    public function shipmentTrackingDetails($getShipmentStatus)
    {
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


    public function getshipmentdetailsjsonAction($ticketid)
    {
        $shipmentdetails             = $this->modelObj->getShipmentStatusDetails('"' . $ticketid . '"');
        $refNo                       = $shipmentdetails['instaDispatch_jobIdentity'];
        $shipmentdetailsforReference = $this->modelObj->getShipmentDetailsByReference($refNo);
        $data                        = $innerdata = array();
        if (count($shipmentdetailsforReference) > 0) {
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
                'rows' => array()
            );


            return $showdata;
        }
    }
    public function getShipmentStatusHistory($shipmentHistoryid, $temparr = null)
    {
        $data                    = $this->modelObj->getShipmentStatusHistory($shipmentHistoryid);
        $history                 = array();
        $history['create_date']  = $data['create_date'];
        $history['driver_names'] = $data['name']; //$data['driver_unique_name'];

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
    public function getPriceDetails($data)
    {
        $returndata = array();
        if (!empty($data)) {
            $pricedetails = $breakdown = array();
            $pricedetails = $this->modelObj->getShipmentPriceDetails($data->shipid);
            $breakdown    = $this->modelObj->getShipmentPricebreakdownDetails($data->shipid);
            if (!empty($pricedetails) && !empty($breakdown)) {
                $returndata['pricedetails'] = $pricedetails;
                $returndata['breakdown']    = $breakdown;
                $returndata['status']       = 'true';
            } else {
                $returndata['pricedetails'] = 'Data not Found';
                $returndata['breakdown']    = 'Data not Found';
                $returndata['status']       = 'true';

            }
        }
        return $returndata;
    }

    public function ManagePriceData($carrierPrice, $customerPrice)
    {
        $carrierPriceData  = $this->ManagePriceDataCarrier($carrierPrice);
        $customerPriceData = $this->ManagePriceDataCustomer($customerPrice);
        foreach ($carrierPriceData as $key => $vals) {
            $carrierPriceData[$key]['customer'] = $customerPriceData[$key]['customer'];
        }
        return $carrierPriceData;
    }

    public function ManagePriceDataCarrier($data)
    {
        $return = array();
        if (count($data) > 0) {
            $return['service']['courier']    = array();
            $return['surcharges']['courier'] = array();
            $return['taxes']['courier']      = array(
                'baseprice' => 0
            );
            $return['subtotal']['courier']   = array();
            $carrierSurcharge                = array();
            foreach ($data as $key => $vel) {
                if ($vel['api_key'] == 'service') {
                    $return['service']['courier']['baseprice'] = $vel['baseprice'];
                    $return['service']['courier']['naration']  = ($vel['service_name'] == '') ? $vel['price_code'] : $vel['service_name'];
                    $return['service']['courier']['id']        = $vel['id'];
                } elseif ($vel['api_key'] == 'surcharges') {
                    $return['surcharges']['courier'][] = array(
                        'baseprice' => $vel['baseprice'],
                        'naration' => ($vel['surcharge_name'] == '') ? $vel['price_code'] : $vel['surcharge_name'],
                        'id' => $vel['id']
                    );
                    $carrierSurcharge[]                = $vel['baseprice'];
                } elseif ($vel['api_key'] == 'taxes') {
                    $return['taxes']['courier']['baseprice'] = $vel['baseprice'];
                    $return['taxes']['courier']['naration']  = 'Total Tax';
                    $return['taxes']['courier']['id']        = $vel['id'];
                }
            }
            $return['subtotal']['courier'] = array_sum($carrierSurcharge) + $return['service']['courier']['baseprice'];
            $return['total']['courier']    = $return['subtotal']['courier'] + $return['taxes']['courier']['baseprice'];
        }
        return $return;
    }
    public function ManagePriceDataCustomer($data)
    {
        $return = array();
        if (count($data) > 0) {
            $return['service']['customer']    = array();
            $return['surcharges']['customer'] = array();
            $return['taxes']['customer']      = array(
                'baseprice' => 0
            );
            $return['subtotal']['customer']   = array();
            $customerSurcharge[]              = array();
            foreach ($data as $key => $vel) {
                if ($vel['api_key'] == 'service') {
                    $return['service']['customer']['baseprice'] = $vel['price'];
                    $return['service']['customer']['naration']  = ($vel['company_service_name'] == '') ? $vel['service_name'] : $vel['company_service_name'];
                    $return['service']['customer']['naration']  = ($return['service']['customer']['naration'] == '') ? $vel['price_code'] : $return['service']['customer']['naration'];
                    $return['service']['customer']['id']        = $vel['id'];
                } elseif ($vel['api_key'] == 'surcharges') {
                    $surchargenaration                  = ($vel['company_surcharge_name'] == '') ? $vel['surcharge_name'] : $vel['company_surcharge_name'];
                    $surchargenaration                  = ($surchargenaration == '') ? $vel['price_code'] : $surchargenaration;
                    $return['surcharges']['customer'][] = array(
                        'baseprice' => $vel['price'],
                        'naration' => $surchargenaration,
                        'id' => $vel['id']
                    );
                    $customerSurcharge[]                = $vel['price'];
                } elseif ($vel['api_key'] == 'taxes') {
                    $return['taxes']['customer']['baseprice'] = $vel['price'];
                    $return['taxes']['customer']['naration']  = 'Total Tax';
                    $return['taxes']['customer']['id']        = $vel['id'];
                }
            }
            $return['subtotal']['customer'] = array_sum($customerSurcharge) + $return['service']['customer']['baseprice'];
            $return['total']['customer']    = $return['subtotal']['customer'] + $return['taxes']['customer']['baseprice'];

        }
        return $return;
    }


    public function updateCarrierPrice__old($param)
    {
        $param                 = json_decode(json_encode($param), 1);
        $getLastPriceVersion   = $this->modelObj->getShipmentPriceDetails($param['job_identity']);
        $priceVersion          = $getLastPriceVersion['price_version'];
        $getLastPriceBreakdown = $this->modelObj->getShipmentPricebreakdownDetailsWithVersion($param['job_identity'], $priceVersion);
        $isInvoiced            = $getLastPriceVersion['isInvoiced'];
        $customerId            = $getLastPriceVersion['customer_id'];
        $carrierId             = $getLastPriceVersion['carrier'];
        //$shipId    = $getLastPriceVersion['shipment_id'];
        $oldGrandTotal         = $getLastPriceVersion['grand_total'];
        $records               = array();

        foreach ($getLastPriceBreakdown as $key => $val) {
            if (array_key_exists($val['id'], $param['data'])) {
                if ($param['applypriceoncustomer'] == 'YES') {
                    $val['show_for']          = 'B';
                    $data                     = $this->calculateNewPrice($val, $param['data'][$val['id']]);
                    $val['ccf_price']         = $data['ccf_price'];
                    $val['baseprice']         = $data['baseprice'];
                    $val['price']             = $data['price'];
                    $val['version']           = $priceVersion + 1;
                    $val['apply_to_customer'] = 'YES';
                    $val['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $val['inputjson']         = json_encode($param);
                    unset($val['id']);
                    $records[] = $val;
                } elseif ($param['applypriceoncustomer'] == 'NO') {
                    $val['baseprice']         = $param['data'][$val['id']];
                    $val['version']           = $priceVersion + 1;
                    $val['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $val['inputjson']         = json_encode($param);
                    $val['apply_to_customer'] = 'NO';
                    unset($val['id']);
                    $records[] = $val;
                } else {
                    //
                }
            } else {
                $val['version'] = $priceVersion + 1;
                unset($val['id']);
                $val['version_reason'] = 'CARRIER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                $records[]             = $val;
            }
        }
        if (isset($param['data']['newsurcharges']) and count($param['data']['newsurcharges']) > 0) {
            foreach ($param['data']['newsurcharges'] as $key => $surchargeId) {
                $surcharge_code = $this->modelObj->getSurchargeCodeBySurchargeId($surchargeId, $param['company_id']);
                $price          = $param['data']['newsurchargesprice'][$key];
                if ($param['applypriceoncustomer'] == 'NO') {
                    $tempdata                      = array();
                    $tempdata['price_code']        = $surcharge_code;
                    $tempdata['price']             = $price;
                    $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                    //$tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                    $tempdata['shipment_type']     = '';
                    $tempdata['version']           = $priceVersion + 1;
                    $tempdata['api_key']           = 'surcharges';
                    $tempdata['ccf_operator']      = 'FLAT';
                    $tempdata['ccf_value']         = '0';
                    $tempdata['ccf_level']         = 'level 0';
                    $tempdata['baseprice']         = $price;
                    $tempdata['ccf_price']         = '0.00';
                    $tempdata['surcharge_id']      = $surchargeId;
                    $tempdata['service_id']        = '0';
                    $tempdata['apply_to_customer'] = 'NO';
                    $tempdata['show_for']          = 'CA';
                    $tempdata['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $tempdata['inputjson']         = json_encode($param);
                    $records[]                     = $tempdata;
                } elseif ($param['applypriceoncustomer'] == 'YES') {
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
                    } else {
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
                    $tempdata['price_code']        = $surcharge_ccf_price['company_surcharge_code'];
                    $tempdata['price']             = $surcharge_ccf_price['price'] + $price;
                    $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                    // $tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                    $tempdata['shipment_type']     = '';
                    $tempdata['version']           = $priceVersion + 1;
                    $tempdata['api_key']           = 'surcharges';
                    $tempdata['ccf_operator']      = $surcharge_ccf_price['operator'];
                    $tempdata['ccf_value']         = $surcharge_ccf_price['surcharge_value'];
                    $tempdata['ccf_level']         = $surcharge_ccf_price['level'];
                    $tempdata['baseprice']         = $price;
                    $tempdata['ccf_price']         = $surcharge_ccf_price['price'];
                    $tempdata['surcharge_id']      = $surcharge_ccf_price['surcharge_id'];
                    $tempdata['service_id']        = '0';
                    $tempdata['apply_to_customer'] = 'YES';
                    $tempdata['show_for']          = 'B';
                    $tempdata['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $tempdata['inputjson']         = json_encode($param);
                    $records[]                     = $tempdata;
                } else {
                    //
                }
            }
        }
        if ($param['applypriceoncustomer'] == 'YES') {
            $temp                                  = array();
            $temp['price_update_applyto_customer'] = 'YES';
            $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
            $temp['price_version']                 = $priceVersion + 1;
            $temp['surcharges']                    = 0;
            $temp['taxes']                         = 0;
            $temp['total_price']                   = 0;
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
                $adddata = $this->modelObj->addContent('shipment_price', $data);
            }
            if ($adddata) {
                $temp['grand_total'] = $temp['surcharges'] + $temp['total_price'] + $temp['taxes'];
                if ($isInvoiced == 'YES') {
                    if ($temp['grand_total'] != $oldGrandTotal) {
                        $voucherHistoryid                  = $this->modelObj->getVoucherHistory($param['job_identity']);
                        $voucherdata                       = array();
                        $voucherdata['voucher_type']       = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                        $voucherdata['voucher_reference']  = $this->modelObj->_generate_voucher_no($param['company_id']);
                        $voucherdata['amount']             = (($temp['grand_total'] - $oldGrandTotal) > 0) ? ($temp['grand_total'] - $oldGrandTotal) : ($oldGrandTotal - $temp['grand_total']);
                        //$voucherdata['shipment_id']  = $shipId;
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
                $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
                $temp['price_version']                 = $priceVersion + 1;
                $condition                             = "load_identity = '" . $param['job_identity'] . "'";
                $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
            }
        } elseif ($param['applypriceoncustomer'] == 'NO') {
            foreach ($records as $data) {
                $adddata = $this->modelObj->addContent('shipment_price', $data);
            }
            if ($adddata) {
                $temp                                  = array();
                $temp['price_update_applyto_customer'] = 'NO';
                $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
                $temp['price_version']                 = $priceVersion + 1;
                $condition                             = "load_identity = '" . $param['job_identity'] . "'";
                $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
            }
        } else {
        }
        if ($status) {
            return array(
                'status' => 'success',
                'message' => 'data updated successfully',
                'data' => array(
                    'identity' => $param['job_identity'],
                    'job_type' => $param['job_type']
                )
            );
        }
    }
    public function updateCustomerPrice__old($param)
    {
        $param                 = json_decode(json_encode($param), 1);
        $getLastPriceVersion   = $this->modelObj->getShipmentPriceDetails($param['job_identity']);
        $priceVersion          = $getLastPriceVersion['price_version'];
        $getLastPriceBreakdown = $this->modelObj->getShipmentPricebreakdownDetailsWithVersionOfCustomer($param['job_identity'], $priceVersion);
        $isInvoiced            = $getLastPriceVersion['isInvoiced'];
        $customerId            = $getLastPriceVersion['customer_id'];
        $carrierId             = $getLastPriceVersion['carrier'];
        //$shipId    = $getLastPriceVersion['shipment_id'];
        $oldGrandTotal         = $getLastPriceVersion['grand_total'];
        $records               = array();
        foreach ($getLastPriceBreakdown as $key => $val) {
            if (array_key_exists($val['id'], $param['data'])) {
                $val['show_for']       = 'B';
                $updatedPrice          = $param['data'][$val['id']];
                $val['ccf_value']      = ($updatedPrice - $val['price'] < 0) ? ($updatedPrice - $val['baseprice']) : ($updatedPrice - $val['baseprice']);
                $val['ccf_price']      = $val['ccf_value'];
                $val['price']          = $val['baseprice'] + $val['ccf_price'];
                $val['ccf_operator']   = 'FLAT';
                $val['ccf_level']      = 'level 0';
                $val['version']        = $priceVersion + 1;
                $val['version_reason'] = 'CUSTOMER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                unset($val['id']);
                $records[] = $val;
            } else {
                $val['version'] = $priceVersion + 1;
                unset($val['id']);
                $val['version_reason'] = 'CUSTOMER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                $records[]             = $val;
            }
        }
        if (isset($param['data']['newsurcharges']) and count($param['data']['newsurcharges']) > 0) {
            foreach ($param['data']['newsurcharges'] as $key => $surchargeId) {
                $surcharge_code                = $this->modelObj->getSurchargeCodeBySurchargeId($surchargeId, $param['company_id']);
                $price                         = $param['data']['newsurchargesprice'][$key];
                $tempdata                      = array();
                $tempdata['price_code']        = $surcharge_code;
                $tempdata['price']             = $price;
                $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                //$tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                $tempdata['shipment_type']     = '';
                $tempdata['version']           = $priceVersion + 1;
                $tempdata['api_key']           = 'surcharges';
                $tempdata['ccf_operator']      = 'FLAT';
                $tempdata['ccf_value']         = $price;
                $tempdata['ccf_level']         = 'level 0';
                $tempdata['baseprice']         = '0';
                $tempdata['ccf_price']         = $price;
                $tempdata['surcharge_id']      = $surchargeId;
                $tempdata['service_id']        = '0';
                $tempdata['apply_to_customer'] = 'NO';
                $tempdata['version_reason']    = 'CUSTOMER_PRICE_UPDATE';
                $tempdata['inputjson']         = json_encode($param);
                $tempdata['show_for']          = 'C';
                $records[]                     = $tempdata;

            }
        }
        $temp                = array();
        $temp['surcharges']  = 0;
        $temp['total_price'] = 0;
        $temp['taxes']       = 0;
        $taxPrice            = $this->getTaxPrice($records);
        foreach ($records as $data) {
            if ($data['api_key'] == 'service') {
                $temp['base_price']               = $data['baseprice'];
                $temp['courier_commission_value'] = $data['ccf_price'];
                $temp['courier_commission_type']  = $data['ccf_operator'];
                $temp['courier_commission']       = $data['ccf_value'];
                $temp['total_price']              = $data['price'];
            } elseif ($data['api_key'] == 'taxes') {
                $data['price']     = $taxPrice['tax_amt'];
                $data['baseprice'] = $taxPrice['base_price'];
                $data['ccf_price'] = $taxPrice['tax_amt'];
                $temp['taxes']     = $data['price'];
            } else {
                $temp['surcharges'] += $data['price'];
            }
            $adddata = $this->modelObj->addContent('shipment_price', $data);
        }
        if ($adddata) {
            $temp['grand_total'] = $temp['surcharges'] + $temp['total_price'] + $temp['taxes'];
            if ($isInvoiced == 'YES') {
                if ($temp['grand_total'] != $oldGrandTotal) {
                    $voucherHistoryid                  = $this->modelObj->getVoucherHistory($param['job_identity']);
                    $voucherdata                       = array();
                    $voucherdata['voucher_type']       = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                    $voucherdata['voucher_reference']  = $this->modelObj->_generate_voucher_no($param['company_id']);
                    $voucherdata['amount']             = (($temp['grand_total'] - $oldGrandTotal) > 0) ? ($temp['grand_total'] - $oldGrandTotal) : ($oldGrandTotal - $temp['grand_total']);
                    //$voucherdata['shipment_id']  = $shipId;
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
            $temp['version_reason']                = 'CUSTOMER_PRICE_UPDATE';
            $temp['price_version']                 = $priceVersion + 1;
            $condition                             = "load_identity = '" . $param['job_identity'] . "'";
            $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
        }
        if ($status) {
            return array(
                'status' => 'success',
                'message' => 'data updated successfully',
                'data' => array(
                    'identity' => $param['job_identity'],
                    'job_type' => $param['job_type']
                )
            );
        }
    }

    public function updateCarrierPrice($param)
    {
        $param                 = json_decode(json_encode($param), 1);
        $getLastPriceVersion   = $this->modelObj->getShipmentPriceDetails($param['job_identity']);
        $priceVersion          = $getLastPriceVersion['price_version'];
        $getLastPriceBreakdown = $this->modelObj->getShipmentPricebreakdownDetailsWithVersion($param['job_identity'], $priceVersion);
        $isInvoiced            = $getLastPriceVersion['isInvoiced'];
        $customerId            = $getLastPriceVersion['customer_id'];
        $carrierId             = $getLastPriceVersion['carrier'];
        //$shipId    = $getLastPriceVersion['shipment_id'];
        $oldGrandTotal         = $getLastPriceVersion['grand_total'];
        $records               = array();

        foreach ($getLastPriceBreakdown as $key => $val) {
            if (array_key_exists($val['id'], $param['data'])) {
                if ($param['applypriceoncustomer'] == 'YES') {
                    $val['show_for']          = 'B';
                    $data                     = $this->calculateNewPrice($val, $param['data'][$val['id']]);
                    $val['ccf_price']         = $data['ccf_price'];
                    $val['baseprice']         = $data['baseprice'];
                    $val['price']             = $data['price'];
                    $val['version']           = $priceVersion + 1;
                    $val['apply_to_customer'] = 'YES';
                    $val['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $val['inputjson']         = json_encode($param);
                    unset($val['id']);
                    $records[] = $val;
                } elseif ($param['applypriceoncustomer'] == 'NO') {
                    $val['baseprice']         = $param['data'][$val['id']];
                    $val['version']           = $priceVersion + 1;
                    $val['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $val['inputjson']         = json_encode($param);
                    $val['apply_to_customer'] = 'NO';
                    unset($val['id']);
                    $records[] = $val;
                } else {
                    //
                }
            } else {
                $val['version'] = $priceVersion + 1;
                unset($val['id']);
                $val['version_reason'] = 'CARRIER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                $records[]             = $val;
            }
        }
        if (isset($param['data']['newsurcharges']) and count($param['data']['newsurcharges']) > 0) {
            foreach ($param['data']['newsurcharges'] as $key => $surchargeId) {
                $surcharge_code = $this->modelObj->getSurchargeCodeBySurchargeId($surchargeId, $param['company_id']);
                $price          = $param['data']['newsurchargesprice'][$key];
                if ($param['applypriceoncustomer'] == 'NO') {
                    $tempdata                      = array();
                    $tempdata['price_code']        = $surcharge_code;
                    $tempdata['price']             = $price;
                    $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                    //$tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                    $tempdata['shipment_type']     = '';
                    $tempdata['version']           = $priceVersion + 1;
                    $tempdata['api_key']           = 'surcharges';
                    $tempdata['ccf_operator']      = 'FLAT';
                    $tempdata['ccf_value']         = '0';
                    $tempdata['ccf_level']         = 'level 0';
                    $tempdata['baseprice']         = $price;
                    $tempdata['ccf_price']         = '0.00';
                    $tempdata['surcharge_id']      = $surchargeId;
                    $tempdata['service_id']        = '0';
                    $tempdata['apply_to_customer'] = 'NO';
                    $tempdata['show_for']          = 'CA';
                    $tempdata['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $tempdata['inputjson']         = json_encode($param);
                    $records[]                     = $tempdata;
                } elseif ($param['applypriceoncustomer'] == 'YES') {
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
                    } else {
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
                    $tempdata['price_code']        = $surcharge_ccf_price['company_surcharge_code'];
                    $tempdata['price']             = $surcharge_ccf_price['price'] + $price;
                    $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                    // $tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                    $tempdata['shipment_type']     = '';
                    $tempdata['version']           = $priceVersion + 1;
                    $tempdata['api_key']           = 'surcharges';
                    $tempdata['ccf_operator']      = $surcharge_ccf_price['operator'];
                    $tempdata['ccf_value']         = $surcharge_ccf_price['surcharge_value'];
                    $tempdata['ccf_level']         = $surcharge_ccf_price['level'];
                    $tempdata['baseprice']         = $price;
                    $tempdata['ccf_price']         = $surcharge_ccf_price['price'];
                    $tempdata['surcharge_id']      = $surcharge_ccf_price['surcharge_id'];
                    $tempdata['service_id']        = '0';
                    $tempdata['apply_to_customer'] = 'YES';
                    $tempdata['show_for']          = 'B';
                    $tempdata['version_reason']    = 'CARRIER_PRICE_UPDATE';
                    $tempdata['inputjson']         = json_encode($param);
                    $records[]                     = $tempdata;
                } else {
                    //
                }
            }
        }

        if ($param['applypriceoncustomer'] == 'YES') {
            $temp                                  = array();
            $temp['price_update_applyto_customer'] = 'YES';
            $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
            $temp['price_version']                 = $priceVersion + 1;
            $temp['surcharges']                    = 0;
            $temp['taxes']                         = 0;
            $temp['total_price']                   = 0;
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
                $adddata = $this->modelObj->addContent('shipment_price', $data);
            }
            if ($adddata) {
                $temp['grand_total'] = $temp['surcharges'] + $temp['total_price'] + $temp['taxes'];
                if ($isInvoiced == 'YES') {
                    if ($temp['grand_total'] != $oldGrandTotal) {
                        $voucherHistoryid                  = $this->modelObj->getVoucherHistory($param['job_identity']);
                        $voucherdata                       = array();
                        $voucherdata['voucher_type']       = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                        $voucherdata['voucher_reference']  = $this->modelObj->_generate_voucher_no($param['company_id']);
                        $voucherdata['amount']             = (($temp['grand_total'] - $oldGrandTotal) > 0) ? ($temp['grand_total'] - $oldGrandTotal) : ($oldGrandTotal - $temp['grand_total']);
                        //$voucherdata['shipment_id']  = $shipId;
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
                $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
                $temp['price_version']                 = $priceVersion + 1;
                $condition                             = "load_identity = '" . $param['job_identity'] . "'";
                $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
            }
        } elseif ($param['applypriceoncustomer'] == 'NO') {
            $temp['surcharges']  = 0;
            $temp['total_price'] = 0;
            $temp['taxes']       = 0;
            $taxPrice            = $this->getTaxPrice($records);
            foreach ($records as $data) {
                if ($data['api_key'] == 'service') {
                    $temp['base_price']               = $data['baseprice'];
                    $temp['courier_commission_value'] = $data['ccf_price'];
                    $temp['courier_commission_type']  = $data['ccf_operator'];
                    $temp['courier_commission']       = $data['ccf_value'];
                    $temp['total_price']              = $data['price'];
                } elseif ($data['api_key'] == 'taxes') {
                    $data['price']     = $taxPrice['tax_amt'];
                    $data['baseprice'] = $taxPrice['base_price'];
                    $data['ccf_price'] = $taxPrice['tax_amt'];
                    $temp['taxes']     = $data['price'];
                } else {
                    $temp['surcharges'] += $data['price'];
                }
                $adddata = $this->modelObj->addContent('shipment_price', $data);
            }
            if ($adddata) {
                $temp                                  = array();
                $temp['price_update_applyto_customer'] = 'NO';
                $temp['version_reason']                = 'CARRIER_PRICE_UPDATE';
                $temp['price_version']                 = $priceVersion + 1;
                $condition                             = "load_identity = '" . $param['job_identity'] . "'";
                $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
            }
        } else {
        }
        if ($status) {
            return array(
                'status' => 'success',
                'message' => 'data updated successfully',
                'data' => array(
                    'identity' => $param['job_identity'],
                    'job_type' => $param['job_type']
                )
            );
        }
    }

    public function updateCustomerPrice($param)
    {
        $param                 = json_decode(json_encode($param), 1);
        $getLastPriceVersion   = $this->modelObj->getShipmentPriceDetails($param['job_identity']);
        $priceVersion          = $getLastPriceVersion['price_version'];
        $getLastPriceBreakdown = $this->modelObj->getShipmentPricebreakdownDetailsWithVersionOfCustomer($param['job_identity'], $priceVersion);
        $isInvoiced            = $getLastPriceVersion['isInvoiced'];
        $customerId            = $getLastPriceVersion['customer_id'];
        $carrierId             = isset($getLastPriceVersion['carrier']) ? $getLastPriceVersion['carrier'] : 0;
        //$shipId    = $getLastPriceVersion['shipment_id'];
        $oldGrandTotal         = $getLastPriceVersion['grand_total'];
        $records               = array();
        foreach ($getLastPriceBreakdown as $key => $val) {
            if (array_key_exists($val['id'], $param['data'])) {
                $val['show_for']       = 'B';
                $updatedPrice          = $param['data'][$val['id']];
                $val['ccf_value']      = ($updatedPrice - $val['price'] < 0) ? ($updatedPrice - $val['baseprice']) : ($updatedPrice - $val['baseprice']);
                $val['ccf_price']      = $val['ccf_value'];
                $val['price']          = $val['baseprice'] + $val['ccf_price'];
                $val['ccf_operator']   = 'FLAT';
                $val['ccf_level']      = 'level 0';
                $val['version']        = $priceVersion + 1;
                $val['version_reason'] = 'CUSTOMER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                unset($val['id']);
                $records[] = $val;
            } else {
                $val['version'] = $priceVersion + 1;
                unset($val['id']);
                $val['version_reason'] = 'CUSTOMER_PRICE_UPDATE';
                $val['inputjson']      = json_encode($param);
                $records[]             = $val;
            }
        }
        if (isset($param['data']['newsurcharges']) and count($param['data']['newsurcharges']) > 0) {
            foreach ($param['data']['newsurcharges'] as $key => $surchargeId) {
                $surcharge_code                = $this->modelObj->getSurchargeCodeBySurchargeId($surchargeId, $param['company_id']);
                $price                         = $param['data']['newsurchargesprice'][$key];
                $tempdata                      = array();
                $tempdata['price_code']        = $surcharge_code;
                $tempdata['price']             = $price;
                $tempdata['load_identity']     = $getLastPriceVersion['load_identity'];
                //$tempdata['shipment_id'] = $getLastPriceVersion['shipment_id'];
                $tempdata['shipment_type']     = '';
                $tempdata['version']           = $priceVersion + 1;
                $tempdata['api_key']           = 'surcharges';
                $tempdata['ccf_operator']      = 'FLAT';
                $tempdata['ccf_value']         = $price;
                $tempdata['ccf_level']         = 'level 0';
                $tempdata['baseprice']         = '0';
                $tempdata['ccf_price']         = $price;
                $tempdata['surcharge_id']      = $surchargeId;
                $tempdata['service_id']        = '0';
                $tempdata['apply_to_customer'] = 'YES';
                $tempdata['version_reason']    = 'CUSTOMER_PRICE_UPDATE';
                $tempdata['inputjson']         = json_encode($param);
                $tempdata['show_for']          = 'C';
                $records[]                     = $tempdata;

            }
        }
        $temp                = array();
        $temp['surcharges']  = 0;
        $temp['total_price'] = 0;
        $temp['taxes']       = 0;
        $taxPrice            = $this->getTaxPrice($records);

        foreach ($records as $data) {
            if ($data['api_key'] == 'service') {
                $temp['base_price']               = $data['baseprice'];
                $temp['courier_commission_value'] = $data['ccf_price'];
                $temp['courier_commission_type']  = $data['ccf_operator'];
                $temp['courier_commission']       = $data['ccf_value'];
                $temp['total_price']              = $data['price'];
            } elseif ($data['api_key'] == 'taxes') {
                $data['price']     = $taxPrice['tax_amt'];
                $data['baseprice'] = $taxPrice['base_price'];
                $data['ccf_price'] = $taxPrice['tax_amt'];
                $temp['taxes']     = $data['price'];
            } else {
                $temp['surcharges'] += $data['price'];
            }
            //print_r($data);die;
            $adddata = $this->modelObj->addContent('shipment_price', $data);
        }
        if ($adddata) {
            $temp['grand_total'] = $temp['surcharges'] + $temp['total_price'] + $temp['taxes'];
            if ($isInvoiced == 'YES') {
                if ($temp['grand_total'] != $oldGrandTotal) {
                    $voucherHistoryid                  = $this->modelObj->getVoucherHistory($param['job_identity']);
                    $voucherdata                       = array();
                    $voucherdata['voucher_type']       = (($temp['grand_total'] - $oldGrandTotal) > 0) ? 'DEBIT' : 'CREDIT';
                    $voucherdata['voucher_reference']  = $this->modelObj->_generate_voucher_no($param['company_id']);
                    $voucherdata['amount']             = (($temp['grand_total'] - $oldGrandTotal) > 0) ? ($temp['grand_total'] - $oldGrandTotal) : ($oldGrandTotal - $temp['grand_total']);
                    //$voucherdata['shipment_id']  = $shipId;
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
            $temp['version_reason']                = 'CUSTOMER_PRICE_UPDATE';
            $temp['price_version']                 = $priceVersion + 1;
            $condition                             = "load_identity = '" . $param['job_identity'] . "'";
            $status                                = $this->modelObj->editContent("shipment_service", $temp, $condition);
        }
        if ($status) {
            return array(
                'status' => 'success',
                'message' => 'data updated successfully',
                'data' => array(
                    'identity' => $param['job_identity'],
                    'job_type' => $param['job_type']
                )
            );
        }
    }

    public function getbookedCarrierSurcharge($param)
    {
        $returndata          = array();
        $getLastPriceVersion = $this->modelObj->getShipmentPriceDetails($param['job_identity']);
        $booked_carrier      = $getLastPriceVersion['carrier'];
        $company_id          = $param['company_id'];
        if ($booked_carrier != 0) {
            $returndata = $this->modelObj->getAllSurchargeOfCarrier($booked_carrier, $company_id);
        }
        return $returndata;
    }

    public function calculateNewPrice($dataSet, $basePrice)
    {
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

    private function _calculateSurcharge($price, $surcharge_value, $operator, $company_surcharge_code, $company_surcharge_name, $courier_surcharge_code, $courier_surcharge_name, $level, $surcharge_id)
    {
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

    public function getShipmentsTrackingDetails($identity)
    {
        $shipmentLifeCycle = $this->modelObj->getShipmentLifeCycleHistoryByIdentity($identity);
        foreach ($shipmentLifeCycle as $keys => $dataVal) {
            $shipmentLifeCycle[$keys]['shipment_service_type'] = ($dataVal['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery';
            $shipmentLifeCycle[$keys]['create_date']           = date('Y/m/d', strtotime($dataVal['create_date']));
            $shipmentLifeCycle[$keys]['is_custom_create']      = ($dataVal['is_custom_create'] == '0') ? 'false' : 'true';
        }
        return $shipmentLifeCycle;
    }
    public function getShipmentsPodDetails($tickets)
    {
        $shipmentPod = $this->modelObj->getShipmentPodByShipmentTicket($tickets);
        $retrundata  = array();
        foreach ($shipmentPod as $keys => $dataVal) {
            $temp              = array();
            $temp['date']      = date('Y/m/d', strtotime($dataVal['create_date']));
            $temp['time']      = date('H:m:s', strtotime($dataVal['create_date']));
            $temp['recipient'] = $dataVal['contact_person'];
            $temp['comment']   = $dataVal['comment'];
            $temp['download']  = ($dataVal['pod_name'] == 'signature') ? 'true' : 'false';
            $temp['action']    = $dataVal['value'];
            $temp['ticket']    = $dataVal['shipment_ticket'];
            $retrundata[]      = $temp;
        }
        return $retrundata;
    }
    public function allowedTrackingstatus()
    {
        $allowedTracking = $this->modelObj->allowedTracking();
        return $allowedTracking;
    }

    public function addCustomTracking($param)
    {
        $param   = json_decode(json_encode($param), 1);
        $records = array();
        if (is_array($param['data']) && count($param['data']) > 0) {
            $tempval                                = array();
            $tempval['shipment_ticket']             = $param['data']['reference'];
            $tempval['instaDispatch_loadIdentity']  = $param['job_identity'];
            $tempval['create_date']                 = date('Y-m-d', strtotime($param['data']['servicedate']));
            $tempval['create_time']                 = date('H:m:s', strtotime($param['data']['servicedate']));
            $tempval['actions']                     = $param['data']['events'];
            $tempval['internel_action_code']        = $param['data']['events'];
            $tempval['is_scaning']                  = 'NO';
            $tempval['parcel_ticket']               = 'NULL';
            $tempval['instaDispatch_pieceIdentity'] = 'NULL';
            $tempval['driver_id']                   = '0';
            $tempval['route_id']                    = '0';

            $tempval['status']           = '1';
            $tempval['company_id']       = $param['company_id'];
            $tempval['action_taken_by']  = 'controller';
            $tempval['is_custom_create'] = '1';
            $adddata                     = $this->modelObj->addContent('shipment_life_history', $tempval);
            if ($adddata) {
                return array(
                    'status' => 'success',
                    'message' => 'data updated successfully',
                    'data' => array(
                        'identity' => $param['job_identity']
                    )
                );
            }
        }
    }
    public function deleteCustomTracking($param)
    {
        $param = json_decode(json_encode($param), 1);
        if ($param['data']) {
            $status = $this->modelObj->deleteTracking($param['data']);
            if ($status) {
                return array(
                    'status' => 'success',
                    'message' => 'data deleted successfully',
                    'data' => array(
                        'identity' => $param['job_identity']
                    )
                );
            }
        }
    }

    public function addCustomPod($param)
    {
        $param   = json_decode(json_encode($param), 1);
        $records = array();
        if (is_array($param['data']) && count($param['data']) > 0) {
            $tempval                     = array();
            $tempval['shipment_ticket']  = $param['data']['reference'];
            $tempval['driver_id']        = '0';
            $tempval['type']             = 'text';
            $tempval['value']            = 'text';
            $tempval['pod_name']         = 'NULL';
            $tempval['comment']          = $param['data']['comment'];
            $tempval['contact_person']   = $param['data']['contact_person'];
            $tempval['status']           = '1';
            $tempval['create_date']      = date('Y-m-d H:m:s', strtotime($param['data']['servicedate']));
            $tempval['is_custom_create'] = '1';
            $adddata                     = $this->modelObj->addContent('shipments_pod', $tempval);
            if ($adddata) {
                return array(
                    'status' => 'success',
                    'message' => 'data added successfully',
                    'data' => array(
                        'identity' => $param['job_identity']
                    )
                );
            }
        }
    }

    public function cancelJob($param)
    {
        print_r($param);
        die;
    }

    public function shipmentdetailsAction____()
    {
        $podData           = array();
        $shipmentLifeCycle = $this->modelObj->getShipmentLifeCycleHistoryByIdentity($ticketid);
        if ($shipmentdetails['current_status'] == 'D') {
            $existingPodData = $this->modelObj->getExistingPodData($ticketid);
            $contactName     = $commentData = '';
            foreach ($existingPodData as $key => $pod) {
                $contactName = $pod['delivery_contact_person'];
                $commentData = $pod['delivery_comment'];
            }
            $podData['delivery_contact_person'] = $contactName;
            $podData['delivery_comment']        = $commentData;
        }
        $returnData                              = array();
        $shipmentAdditionaldetails               = $this->modelObj->getShipmentAdditionalDetails($ticketid);
        $returnData['shipmentData']              = $shipmentdetails;
        $returnData['podData']                   = $podData;
        $returnData['shipmentAdditionaldetails'] = $shipmentAdditionaldetails;
        $returnData['parcelData']                = $parcelDetails;
        $returnData['trackingData']              = $shipmentTrackingDetails;
        $returnData['shipmentHistoryData']       = $shipmentHistory;
        $returnData['rejectHistoryData']         = $shipmentRejectHistory;
        $returnData['shipmentLifeCycle']         = $shipmentLifeCycle;
        $returnData['griddata']                  = $this->getshipmentdetailsjsonAction($ticketid);



        return $returnData;
    }
    public function shipmentTrackingDetails____($getShipmentStatus)
    {
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

    public function getAllowedAllShipmentsStatus($param)
    {
        $data = $this->modelObj->getAllowedAllShipmentsStatus($param->company_id);
        return $data;
    }
    public function getAllowedAllServices($param)
    {
        $data = $this->modelObj->getAllowedAllServices($param->company_id);
        return $data;
    }

    public function getTaxPrice__old($records)
    {
        $temp['total_price'] = 0;
        $temp['surcharges']  = 0;
        $temp['taxes']       = 0;
        $isTax               = false;
        $returnTax           = array();
        if (count($records) > 0) {
            foreach ($records as $data) {
                if ($data['api_key'] == 'service') {
                    $temp['total_price'] = $data['price'];
                } elseif ($data['api_key'] == 'taxes') {
                    $isTax                  = true;
                    $temp['taxes']          = $data['price'];
                    $temp['taxes_operator'] = $data['ccf_operator'];
                    $temp['taxes_value']    = $data['ccf_value'];
                } else {
                    if ($data['apply_to_customer'] != 'NO') {
                        $temp['surcharges'] += $data['price'];
                    }

                }
            }
            if ($isTax) {
                $basePrice = $temp['total_price'] + $temp['surcharges'];
                if ($temp['taxes_operator'] == 'PERCENTAGE') {
                    $taxamt                  = number_format((($basePrice * $temp['taxes_value']) / 100), 2);
                    $returnTax['base_price'] = $basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                } elseif ($temp['taxes_operator'] == 'FLAT') {
                    $taxamt                  = $temp['taxes_value'];
                    $returnTax['base_price'] = $basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                } else {
                    $taxamt                  = 0;
                    $returnTax['base_price'] = $basePrice;
                    $returnTax['tax_amt']    = $taxamt;
                }
            }
        }
        return $returnTax;
    }

    public function getTaxPrice($records)
    {
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
    
     public function getAllCarrier($param){
         $data =  $this->modelObj->getAllCarrier($param->company_id);
          return $data; 
	}
    
	public function printLabelByLoadIdentity($param){
		/* $shipmentStatus = $this->modelObj->getStatusByLoadIdentity($param->load_identity);
		if($shipmentStatus!='cancel'){ */
			$carrierObj = new Carrier();
			if(is_array($param->load_identity)){
				$load_identity = implode("','",$param->load_identity);
				$shipmentStatus = $this->modelObj->getStatusByLoadIdentity($load_identity);
				foreach($shipmentStatus as $status){
					if($status['status']!='cancel'){
						$labelInfo = $carrierObj->getLabelByLoadIdentity($load_identity);
					}else{
						return array("status"=>"error","file_path"=>"","message"=>"One of selected shipment is cancelled, you cannot print label for that shipment");
					}
				}
			}else{
				$shipmentStatus = $this->modelObj->getStatusByLoadIdentity($param->load_identity);
				if($shipmentStatus!='cancel'){
					$labelInfo = $carrierObj->getLabelByLoadIdentity($param->load_identity);
				}else{
					return array("status"=>"error","file_path"=>"","One of selected shipment is cancelled, you cannot print label for that shipment");
				}
			}
			
			if(count($labelInfo)==1){
				if($labelInfo[0]['label_file_pdf']!=='')		
					return array("status"=>"success","file_path"=>$labelInfo[0]['label_file_pdf'],"message"=>"");
				else
					return array("status"=>"error","file_path"=>"","message"=>"label not found!");
			}elseif(count($labelInfo)>1){
				foreach($labelInfo as $data){
					if($data['label_file_pdf'] ==''){
						return array("status"=>"error","file_path"=>"","message"=>"label not found for all selected shipments!");
					}
				}
				$label_pdf = $carrierObj->mergePdf($labelInfo);
				return array("status"=>$label_pdf['status'],"file_path"=>$label_pdf['file_path'],"message"=>"");
				/* die;
				if($labelInfo[0]['label_file_pdf']!==''){
					$label_pdf = $carrierObj->mergePdf($labelInfo);
					return array("status"=>$label_pdf['status'],"file_path"=>$label_pdf['file_path'],"message"=>"");	
				}else{
					return array("status"=>"error","file_path"=>"","message"=>"label not found for all selected shipments!");
				} */
			}else{
				return array("status"=>"error","file_path"=>"","message"=>"label not found!");
			}
		/* }else{
			return array("status"=>"error","file_path"=>"","message"=>"This shipment is cancelled, you cannot print label for this shipment");
		} */
        
			
	}
	
	public function cancelShipmentByLoadIdentity($param){
            $carrierObj = new Carrier();
            $carrier_code = $param->carrier_code;
            if(strtolower($carrier_code) == 'dhl') {
                return $this->_updateShipmentCancel($param);    
            } else {                
                $cancelShipment = $carrierObj->cancelShipmentByLoadIdentity($param);
                $cancelShipment = json_decode($cancelShipment);
                if(isset($cancelShipment->void_consignment)){
                    return $this->_updateShipmentCancel($param);    
                }else{
                    return array("status"=>"error","message"=>$cancelShipment->error);
                }
            }
	}
        
        private function _updateShipmentCancel($param) {
            //update shipment status as cancel in shipment service table
            $updateStatus = $this->modelObj->editContent("shipment_service",array("status"=>"cancel"),"load_identity='".$param->load_identity."'");
            if($updateStatus)
                return array("status"=>"success","message"=>"Shipment cancelled successfully");
            else
                return array("status"=>"error","message"=>"Error while cancellation,please try again");
        }

}
?>
