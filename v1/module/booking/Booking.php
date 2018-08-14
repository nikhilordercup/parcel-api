<?php
class Booking extends Icargo
{
    private $_environment = array(
        "live" =>  array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImRldmVsb3BlcnNAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Njk5MzU0N30.cpm3XYPcLlwb0njGDIf8LGVYPJ2xJnS32y_DiBjSCGI",
            "access_url" => "http://occore.ordercup.com/api/v1/rate"
        ),
        "stagging" =>  array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6Im1hcmdlc2guc29uYXdhbmVAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Mzk2ODgxMX0.EJc4SVQXIwZibVuXFxkTo8UjKvH8S9gWyuFn9bsi63g",
            "access_url" => "http://occore.ordercup1.com/api/v1/rate"
        )
    );

    public

    function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data["email"], "access_token" => $data["access_token"]));
		$this->apiConn = "stagging";
		if(ENV=='live')
			$this->apiConn = "live";

        $this->authorization_token = $this->_environment[$this->apiConn]["authorization_token"];
        $this->access_url = $this->_environment[$this->apiConn]["access_url"];

        $this->modelObj = new Booking_Model_Booking();

        $this->postcodeObj = new Postcode();
    }

    /**
     * Start transaction
     */
    public

    function startTransaction() {
        $this->modelObj->startTransaction();
    }
    /**
     * Start transaction
     */
    public

    function commitTransaction() {
        $this->modelObj->commitTransaction();
    }
    /**
     * Start transaction
     */
    public

    function rollBackTransaction() {
        $this->modelObj->rollBackTransaction();
    }

    protected

    function _getDistanceMatrix($origin, $destinations, $timestamp){
        return (object)Library::_getInstance()->multiple_destinations_distance_and_duration(
            array(
                "origin" => $origin,
                "destinations" => $destinations,
                "departure_time" => $timestamp
            )
        );
    }

    protected

    function _checkCustomerAccountStatus($customer_id){
        $accountStatus = $this->modelObj->checkCustomerAccountStatus($customer_id);
        if(!$accountStatus){
            return array("status"=>"error", "message"=>"Customer account disabled.");
        }

        return array("status"=>"success", "message"=>"Customer account enabled.");
    }

    protected

    function _getCompanyCode($company_id){
        $this->modelObj->getCompanyCode($company_id);
    }

    protected

    function getCustomerWarehouseIdByCustomerId($company_id, $customer_id){
        $data = $this->modelObj->getCustomerWarehouseIdByCustomerId($company_id, $customer_id);
        return $data["warehouse_id"];
    }

    protected

    function _saveAddressData($data, $customer_id){
        $data = (object)$data;        
        $postcode = ($data->country->alpha3_code == 'GBR') ? ( $this->postcodeObj->validate($data->postcode) ) : true;
        if($postcode) {
			if(isset($data->address_type)){
				$address_type = ($data->address_type == 'No') ? 'Business' : 'Residential' ;
			}else{
				$address_type = "";
			}
			$addressType =
            $param["postcode"]      = $data->postcode;
            $param["address_line1"] = (isset($data->address_line1)) ? $data->address_line1 : "";
            $param["address_line2"] = (isset($data->address_line2)) ? $data->address_line2 : "";
            $param["city"]          = (isset($data->city)) ? $data->city : "";
            $param["state"]         = (isset($data->state)) ? $data->state : "";
            $param["country"]       = $data->country->short_name;
            $param["iso_code"]      = $data->country->alpha3_code;
            $param["first_name"]    = (isset($data->name)) ? $data->name : "";
            $param["last_name"]     = "";
            $param["contact_no"]    = (isset($data->phone)) ? $data->phone : "";
            $param["contact_email"] = (isset($data->email)) ? $data->email : "";
            $param["company_name"]  = "";

            $param["search_string"] = str_replace(' ','',implode('',$param));;

            $param["country_id"]    = $data->country->id;

            $param["latitude"]      = $data->geo_position->latitude;
            $param["longitude"]     = $data->geo_position->longitude;
            $param["is_default_address"] = "N";
            $param["customer_id"]   = $customer_id;
            $param["is_warehouse"]  = "N";
            $param["address_type"]  = $address_type;
            $param["billing_address"] = "N";
            $param["billing_address"] = "N";

            $addressVersion = $this->modelObj->getAddressBySearchStringAndCustomerId($customer_id, $param["search_string"]);

            if(!$addressVersion["version_id"]) {
                $param["version_id"] = "version_1";
				$address_id = $this->modelObj->saveAddress($param);
				return array("status"=>"success", "address_id"=>$address_id,"address_data"=>$param);
            }
            else{
                $version = explode("_", $addressVersion['version_id']);
                $param["version_id"] = "version_".($version[1]+1);
				return array("status"=>"success", "address_id"=>$addressVersion['address_id'],"address_data"=>$param);
            }
            //$address_id = $this->modelObj->saveAddress($param);
            //return array("status"=>"success", "address_id"=>$address_id,"address_data"=>$param);
        }else{
            return array("status"=>"error", "message"=>"Invalid postcode");
        }
    }

    protected

    function _saveShipment($param1, $param2, $parcel, $address_info, $warehouse_id, $company_id, $company_code, $service_date, $collection_end_at, $load_group_type_code, $job_type_code, $load_group_type_name, $shipment_service_type, $execution_order, $carrier_account_number=null, $is_internal=0){
        $param1 = (object)$param1;
        $param2 = (object)$param2;
        $addressInfo = (object)$address_info;
        $parcelInfo = (object)$parcel;

        $ticketNumber = $this->modelObj->generateTicketNo($company_id);


        if($ticketNumber){

            $data["notification_status"] = (isset($param2->notification)) ? $param2->notification : "";
            $data['shipment_address1'] = $addressInfo->address_line1;
            $data['shipment_address2'] = (isset($addressInfo->address_line2)) ? $addressInfo->address_line2 : "";
            $data['shipment_customer_city'] = (isset($addressInfo->city)) ? $addressInfo->city : "";
            $data['shipment_postcode'] = $addressInfo->postcode;
            $data['shipment_customer_country'] = $addressInfo->country;
            $data['shipment_instruction'] = (isset($param2->pickup_instruction)) ? $param2->pickup_instruction : "";
            $data['shipment_country_code'] = $param2->country->alpha3_code;
                                    
            //customer info
            $data['shipment_customer_name']    = (isset($param2->name)) ? $param2->name : "";
            $data['shipment_customer_email']   = (isset($param2->email)) ? $param2->email : "";
            $data['shipment_customer_phone']   = (isset($param2->phone)) ? $param2->phone : "";

            /*data not saved*/
            $data['shipment_total_weight']     = 0;//$param["weight"];
            $data['shipment_total_volume']     = 0;//$param["weight"];
            $data['shipment_statusName']       = "Un Attainded";
            $data['shipment_shouldBookIn']     = "false";
            $data['shipment_companyName']      = (isset($param2->company_name)) ? $param2->company_name : "";
            $data['distancemiles']             = "0.00";
            $data['estimatedtime']             = "00:00:00";
            /**/

            $data['shipment_highest_length']   = "0.00";//$param["length"];
            $data['shipment_highest_width']    = "0.00";//$param["width"];
            $data['shipment_highest_height']   = "0.00";//$param["height"];
            $data['shipment_highest_weight']   = "0.00";//$param["weight"];

            if(!isset($param["parcel_id"]))
                $param["parcel_id"] = 0;

            $data['shipment_required_service_starttime']       = date("H:i:s", strtotime($service_date));
            $data['shipment_required_service_endtime']         = date("H:i:s", strtotime($collection_end_at));

            $data['shipment_total_item']       = count((array)$parcelInfo);

            $data['instaDispatch_loadGroupTypeCode'] = strtoupper($load_group_type_code);
            $data['instaDispatch_docketNumber'] = $ticketNumber;
            $data['instaDispatch_loadIdentity'] = (isset($param2->load_identity)) ? $param2->load_identity : $ticketNumber;
            $data['instaDispatch_jobIdentity'] = $ticketNumber;
            $data['instaDispatch_objectIdentity'] = $ticketNumber;
            $data['instaDispatch_objectTypeName'] = "JobLoad";

            $data['instaDispatch_objectTypeId'] = 0;
            $data['instaDispatch_accountNumber'] = "";//$param["accountNumber"];
            $data['instaDispatch_businessName'] = $company_code;
            $data['instaDispatch_statusCode'] = "UNATTAINDED";
            $data['instaDispatch_jobTypeCode'] = $job_type_code;

            $data['instaDispatch_availabilityTypeCode'] = "UNKN";
            $data['instaDispatch_availabilityTypeName'] = "Unknown";
            $data['instaDispatch_loadGroupTypeId'] = 0;
            $data['instaDispatch_loadGroupTypeIcon'] = "";
            $data['instaDispatch_loadGroupTypeName'] = $load_group_type_name;
            $data['instaDispatch_customerReference'] = "";

            $data['shipment_isDutiable'] = "false";
            $data['error_flag'] = "0";

            $data['shipment_xml_reference'] = "";//$param["file_name"];

            $data['shipment_total_attempt'] = '0';
            $data['parent_id'] = (isset($param["parent_id"])) ? $param["parent_id"] : 0;

            $data['shipment_pod'] = '';
            $data['shipment_ticket'] = $ticketNumber;
            $data['shipment_required_service_date'] = date("Y-m-d", strtotime($service_date));
            $data['current_status'] = 'C';
            $data['is_shipment_routed'] = '0';
            $data['is_driver_assigned'] = '0';
            $data['dataof'] = $company_code;
            $data['waitAndReturn'] = "false";
            $data['company_id'] = $company_id;
            $data['warehouse_id'] = $warehouse_id;
            $data['address_id'] = 0;
            $data['shipment_service_type'] = $shipment_service_type;

            $data['shipment_latitude'] = $param2->geo_position->latitude;
            $data['shipment_longitude'] = $param2->geo_position->longitude;
            $data['shipment_latlong'] = $param2->geo_position->latitude.",".$param2->geo_position->longitude;
            $data['shipment_create_date'] = date("Y-m-d", strtotime('now'));
            $data['icargo_execution_order'] = $execution_order;
            $data['shipment_executionOrder'] = $execution_order;

            $data['customer_id'] = $param1->customer_id;

            /********Search string used for pickups (DHL, FEDEX etc) ***********/
            $sStr["postcode"]      = $addressInfo->postcode;
            $sStr["address_line1"] = $addressInfo->address_line1;
            $sStr["iso_code"]      = $param2->country->alpha3_code;            
            $data['search_string'] = str_replace(' ','',implode('',$sStr));             
            /********Search string used for pickups (DHL, FEDEX etc) ***********/
            
            $data['shipment_assigned_service_date'] = "1970-01-01" ;
            $data['shipment_assigned_service_time'] = "00:00:00" ;
            $data["booked_by"] =  $param1->booked_by;
            $data["user_id"] =  $param1->collection_user_id;

            $data["booking_ip"] = $_SERVER['REMOTE_ADDR'];

            $data["carrier_code"] = (isset($param2->carrier_code)) ? $param2->carrier_code : "";

            $data["carrier_account_number"] = ($carrier_account_number!=null) ? $carrier_account_number : "";

            $data["is_internal"] = $is_internal;
            
            $shipmentId = $this->modelObj->saveShipment($data);

            if($shipmentId){
                return array('status'=>"success",'message'=>'Shipment has been added successfully', "shipment_id"=>$shipmentId,"shipment_ticket"=>$ticketNumber);
            }else{
                return array('status'=>"error",'message'=>'Shipment has not been added successfully');
            }
        }else{
            return array('status'=>"error",'message'=>'Configuration not found');
        }
    }

    protected

    function _saveParcelOLD($shipment_id,$shipment_ticket,$warehouse_id,$company_id,$company_code,$parcel,$parcel_type){
        $parcel = (object)$parcel;
        $parcelTicketNumber = $this->modelObj->generateParcelTicketNumber($company_id);
        $parcelData = array();
        $parcelData['shipment_id'] = $shipment_id;

        $parcelData['instaDispatch_Identity'] = $shipment_ticket ;
        $parcelData['instaDispatch_pieceIdentity'] = $shipment_ticket;
        $parcelData['instaDispatch_jobIdentity'] = $shipment_ticket;
        $parcelData['instaDispatch_loadIdentity'] = $shipment_ticket;
        $parcelData['shipment_ticket'] = $shipment_ticket;

        $parcelData['package']       = $parcel->package_code;
        $parcelData['parcel_ticket'] = $parcelTicketNumber;
        $parcelData['parcel_weight'] = $parcel->weight;
        $parcelData['parcel_height'] = $parcel->height;
        $parcelData['parcel_length'] = $parcel->length;
        $parcelData['parcel_width']  = $parcel->width;
        //$parcelData["quantity"]      = $parcel->quantity;
        $parcelData['parcel_type']   = $parcel_type;//($valuedata['purposeTypeName'] == 'Collection') ? 'P' : 'D';

        $parcelData['dataof'] = $company_code;
        $parcelData['status'] = '1';
        /* add some new data for same day*/
        $parcelData['docketNumber'] = $shipment_ticket;
        $parcelData['customerReference'] = "";
        $parcelData['objectIdentity'] = $shipment_ticket;
        $parcelData['availabilityTypeId'] = 0;
        $parcelData['availabilityTypeCode'] = "UNKN";
        $parcelData['company_id'] = $company_id;
        $parcelData['warehouse_id'] = $warehouse_id;

        $parcel_id = $this->modelObj->saveParcel($parcelData);

        if($parcel_id){
            return array("status"=>"success", "parcel_id"=>$parcel_id);
        }else{
            return array("status"=>"error", "message"=>"Parcel not saved");
        }
    }

    protected

    function _saveParcel($shipment_id,$shipment_ticket,$warehouse_id,$company_id,$company_code,$parcel,$parcel_type,$loadidentity){
        $parcel = (object)$parcel;
        $parcelTicketNumber = $this->modelObj->generateParcelTicketNumber($company_id);
        $parcelData = array();
        $parcelData['shipment_id'] = $shipment_id;

        $parcelData['instaDispatch_Identity'] = $shipment_ticket ;
        $parcelData['instaDispatch_pieceIdentity'] = $shipment_ticket;
        $parcelData['instaDispatch_jobIdentity'] = $shipment_ticket;
        $parcelData['instaDispatch_loadIdentity'] = $loadidentity;
        $parcelData['shipment_ticket'] = $shipment_ticket;

        $parcelData['package']       = $parcel->package_code;
        $parcelData['parcel_ticket'] = $parcelTicketNumber;
        $parcelData['parcel_weight'] = $parcel->weight;
        $parcelData['parcel_height'] = $parcel->height;
        $parcelData['parcel_length'] = $parcel->length;
        $parcelData['parcel_width']  = $parcel->width;
        //$parcelData["quantity"]      = $parcel->quantity;
        $parcelData['parcel_type']   = $parcel_type;//($valuedata['purposeTypeName'] == 'Collection') ? 'P' : 'D';

        $parcelData['dataof'] = $company_code;
        $parcelData['status'] = '1';
        /* add some new data for same day*/
        $parcelData['docketNumber'] = $shipment_ticket;
        $parcelData['customerReference'] = "";
        $parcelData['objectIdentity'] = $shipment_ticket;
        $parcelData['availabilityTypeId'] = 0;
        $parcelData['availabilityTypeCode'] = "UNKN";
        $parcelData['company_id'] = $company_id;
        $parcelData['warehouse_id'] = $warehouse_id;

        $parcel_id = $this->modelObj->saveParcel($parcelData);

        if($parcel_id){
            return array("status"=>"success", "parcel_id"=>$parcel_id);
        }else{
            return array("status"=>"error", "message"=>"Parcel not saved");
        }
    }

    protected

    function _saveShipmentService($serviceOpted, $surcharges, $load_identity, $customer_id, $booking_status, $otherDetail){       
		
        $service_data = array();

        $price_version = $this->modelObj->findPriceNextVersionNo($load_identity);

        //save price breakdown
        $surchargeAndTaxValue = $this->_savePriceBreakdown($serviceOpted, $surcharges, $load_identity, $price_version);

        if($surchargeAndTaxValue["status"]=="success"){
            
            $service_data["label_tracking_number"] = 0;
            $service_data["label_files_png"] = 0;
            $service_data["label_file_pdf"] = 0;
            $service_data["is_label_printed"] = 0;
            
            $service_data["service_name"] = $serviceOpted->service_info->name;
            $service_data["rate_type"] = $serviceOpted->rate->rate_type;
            $service_data["currency"] = $serviceOpted->rate->currency;

            $service_data["courier_commission_type"] = $serviceOpted->rate->info->operator;
            $service_data["courier_commission"] = $serviceOpted->rate->info->ccf_value;
            $service_data["courier_commission_value"] = $serviceOpted->rate->info->price;

            $service_data["base_price"] = $serviceOpted->rate->info->original_price;
            $service_data["surcharges"] = $surchargeAndTaxValue["total_surcharge_value"];
            $service_data["taxes"] = $surchargeAndTaxValue["total_tax_value"];

            $service_data["total_price"] = $serviceOpted->rate->info->price_with_ccf;
            $service_data["grand_total"] = $service_data["total_price"] + $service_data["surcharges"] + $service_data["taxes"];
            $service_data["charge_from_base"] = (!empty($serviceOpted->charge_from_base)) ? $serviceOpted->charge_from_base : "0";

            $service_data["customer_id"] = $customer_id;
            $service_data["price_version"] = $price_version;
            $service_data["load_identity"] = $load_identity;

            $service_data["transit_distance"] = "00.00";
            $service_data["transit_time"] = "00.00";
            $service_data["transit_distance_text"] = "NA";

            $service_data["transit_time_text"] = "NA";
            $service_data["carrier"] = $serviceOpted->carrier_info->carrier_id;
            $service_data["isInvoiced"] = "NO";

            $service_data["invoice_reference"] = "";
            $service_data["service_request_string"] = $this->serviceRequestString;
            $service_data["service_response_string"] = $this->serviceResponseString;
            
            $service_data["is_insured"] = ($otherDetail['is_insured'] == true) ? 1 : 0;;
            $service_data["reason_for_export"] = $otherDetail['reason_for_export'];
            $service_data["tax_status"] = $otherDetail['tax_status'];
            $service_data["terms_of_trade"] =  $otherDetail['terms_of_trade'];

            $service_data["label_tracking_number"] = '0';
            $service_data["label_files_png"] = '';
            $service_data["label_file_pdf"] =  '';
            $service_data["label_json"] =  '';
            
            $service_data["status"] = $booking_status;
                                    
            $service_id = $this->modelObj->saveShipmentService($service_data);
            if($service_id>0){
                return array("status"=>"success", "message"=>"shipment service saved", "service_id"=>$service_id);
            }
            return array("status"=>"error", "message"=>"shipment service not saved");
        }
        return $surchargeAndTaxValue;
    }
    protected function _saveShipmentItems($item, $load_identity, $customer_id, $booking_status){
        
        $items_data = array();
        $date = date('Y-m-d H:i:s');            
        $items_data["load_identity"] = $load_identity;
        $items_data["item_description"] = $item->item_description;
        $items_data["item_quantity"] = $item->item_quantity;
        $items_data["country_of_origin"] = $item->country_of_origin->alpha2_code;
        $items_data["item_value"] = $item->item_value;
        $items_data["item_weight"] = $item->item_weight;
        $items_data["created"] = $date;
        $items_data["updated"] = $date;
        $items_data["status"] = $booking_status;            
        
        $item_id = $this->modelObj->saveItemService($items_data);
        if($item_id>0){
            return array("status"=>"success", "message"=>"shipment item saved", "item_id"=>$item_id);
        }        
        return array("status"=>"error", "message"=>"shipment item not saved");        
    }

    private

    function _savePriceBreakdownOLD($data, $surcharges, $load_identity, $price_version){
        $totalSurchargeValue = 0;
        $totalTaxValue = 0;

        $price_breakdown = array();

        $price_breakdown["load_identity"] = $load_identity;
        $price_breakdown["shipment_type"] = $data->rate->shipment_type;
        $price_breakdown["version"]       = $price_version;
        $price_breakdown["api_key"]       = "service";
        $price_breakdown["price_code"]    = $data->rate->info->courier_service_code;
        $price_breakdown["ccf_operator"]  = $data->rate->info->operator;
        $price_breakdown["ccf_value"]     = $data->rate->info->ccf_value;
        $price_breakdown["ccf_level"]     = $data->rate->info->level;
        $price_breakdown["baseprice"]     = $data->rate->info->original_price;
        $price_breakdown["ccf_price"]     = $data->rate->info->price;
        $price_breakdown["price"]         = $data->rate->info->price_with_ccf;
        $price_breakdown["service_id"]    = $data->rate->info->service_id;
        $price_breakdown["carrier_id"]    = $data->carrier_info->carrier_id;

        $status = $this->modelObj->saveShipmentPrice($price_breakdown);
        if($status>0){
            //save surcharges
            if(is_object($surcharges)){
                foreach($surcharges as $surcharge_code => $item){
                    $price_breakdown = array();

                    $price_breakdown["load_identity"] = $load_identity;
                    $price_breakdown["shipment_type"] = $data->rate->shipment_type;
                    $price_breakdown["version"]       = $price_version;
                    $price_breakdown["api_key"]       = "surcharges";
                    $price_breakdown["price_code"]    = $surcharge_code;
                    $price_breakdown["ccf_operator"]  = $item->operator;
                    $price_breakdown["ccf_value"]     = $item->surcharge_value;
                    $price_breakdown["ccf_level"]     = $item->level;
                    $price_breakdown["baseprice"]     = $item->original_price;
                    $price_breakdown["ccf_price"]     = $item->price;
                    $price_breakdown["price"]         = $item->price_with_ccf;
                    $price_breakdown["service_id"]    = $item->surcharge_id;
                    $price_breakdown["carrier_id"]    = $item->carrier_id;
                    $status = $this->modelObj->saveShipmentPrice($price_breakdown);
                    if(!$status){
                        return array("status"=>"error", "message"=>"shipment price breakdown not saved");
                    }
                    $totalSurchargeValue += $item->price_with_ccf;
                }
            }

            //save taxes
            if(isset($data->taxes)){
                $price_breakdown = array();
                $price_without_tax = $data->rate->info->original_price;
                foreach($data->taxes as $key => $item){
                    if($key=='total_tax'){
                        $price_breakdown["price_code"] = $key;
                        $price_breakdown["load_identity"] = $load_identity;
                        $price_breakdown["shipment_type"] = $data->rate->shipment_type;
                        $price_breakdown["version"] = $price_version;
                        $price_breakdown["api_key"] = "taxes";
                        $price_breakdown["inputjson"] = json_encode(array('originnal_tax_amt'=>$item));
                        $price_breakdown["carrier_id"] = $data->carrier_info->carrier_id;
                    }elseif($key=='tax_percentage'){
                        $price = number_format((($price_without_tax *$item)/100),2,'.','');
                        $price_breakdown["ccf_operator"] = "PERCENTAGE";
                        $price_breakdown["ccf_value"] = $item;
                        $price_breakdown["ccf_level"] = 0;
                        $price_breakdown["baseprice"] = $price_without_tax;
                        $price_breakdown["ccf_price"] = $price;
                        $price_breakdown["price"] = $price_breakdown["ccf_price"];
                    }else{
                        //
                    }
                }
                $status = $this->modelObj->saveShipmentPrice($price_breakdown);
                if(!$status){
                    return array("status"=>"error", "message"=>"shipment price breakdown not saved");
                }
                $totalTaxValue += $price_breakdown["price"];
            }
            return array("status"=>"success","total_surcharge_value"=>number_format($totalSurchargeValue, 2), "total_tax_value"=>number_format($totalTaxValue, 2));
        }


        return array("status"=>"error", "message"=>"shipment price breakdown not saved");
    }

    private

    function _savePriceBreakdown($data, $surcharges, $load_identity, $price_version){
        $totalSurchargeValue = 0;
        $totalTaxValue = 0;

        $price_breakdown = array();

        $price_breakdown["load_identity"] = $load_identity;
        $price_breakdown["shipment_type"] = $data->rate->shipment_type;
        $price_breakdown["version"]       = $price_version;
        $price_breakdown["api_key"]       = "service";
        $price_breakdown["price_code"]    = isset( $data->rate->info->courier_service_code ) ? $data->rate->info->courier_service_code : '';
        $price_breakdown["ccf_operator"]  = isset( $data->rate->info->operator ) ? $data->rate->info->operator : '';
        $price_breakdown["ccf_value"]     = $data->rate->info->ccf_value;
        $price_breakdown["ccf_level"]     = $data->rate->info->level;
        $price_breakdown["baseprice"]     = isset( $data->rate->info->original_price ) ? $data->rate->info->original_price : '0';
        $price_breakdown["ccf_price"]     = $data->rate->info->price;
        $price_breakdown["price"]         = isset( $data->rate->info->price_with_ccf ) ? $data->rate->info->price_with_ccf : '0';
        $price_breakdown["service_id"]    = $data->rate->info->service_id;
        $price_breakdown["carrier_id"]    = $data->carrier_info->carrier_id;

        $status = $this->modelObj->saveShipmentPrice($price_breakdown);
        if($status>0){
            //save surcharges
            $totalcarrierSurchage = 0;
            $totalcustomerSurchage = 0;
            if(is_object($surcharges)){
                foreach($surcharges as $surcharge_code => $item){
                    $totalcarrierSurchage += $item->original_price;
                    $totalcustomerSurchage += $item->price_with_ccf;
                    $price_breakdown = array();
                    $price_breakdown["load_identity"] = $load_identity;
                    $price_breakdown["shipment_type"] = $data->rate->shipment_type;
                    $price_breakdown["version"]       = $price_version;
                    $price_breakdown["api_key"]       = "surcharges";
                    $price_breakdown["price_code"]    = $surcharge_code;
                    $price_breakdown["ccf_operator"]  = $item->operator;
                    $price_breakdown["ccf_value"]     = $item->surcharge_value;
                    $price_breakdown["ccf_level"]     = $item->level;
                    $price_breakdown["baseprice"]     = $item->original_price;
                    $price_breakdown["ccf_price"]     = $item->price;
                    $price_breakdown["price"]         = $item->price_with_ccf;
                    $price_breakdown["service_id"]    = $item->surcharge_id;
                    $price_breakdown["carrier_id"]    = $item->carrier_id;
                    $status = $this->modelObj->saveShipmentPrice($price_breakdown);
                    if(!$status){
                        return array("status"=>"error", "message"=>"shipment price breakdown not saved");
                    }
                    $totalSurchargeValue += $item->price_with_ccf;
                }
            }
            //save taxes
            if(isset($data->taxes)){
                $price_breakdown = array();
                //$price_without_tax = $data->rate->info->original_price;

                $price_without_tax = ($totalcustomerSurchage  + $data->rate->info->price_with_ccf);
                $carriertotalpriceWithouttax = ($totalcarrierSurchage  + $data->rate->info->original_price);
                foreach($data->taxes as $key => $item){
                    if($key=='total_tax'){
                        $price_breakdown["price_code"] = $key;
                        $price_breakdown["load_identity"] = $load_identity;
                        $price_breakdown["shipment_type"] = $data->rate->shipment_type;
                        $price_breakdown["version"] = $price_version;
                        $price_breakdown["api_key"] = "taxes";
                        $price_breakdown["inputjson"] = json_encode(array('originnal_tax_amt'=>$item));
                        $price_breakdown["carrier_id"] = $data->carrier_info->carrier_id;
                    }elseif($key=='tax_percentage'){
                        $price = number_format((($price_without_tax *$item)/100),2,'.','');
                        $basetaxprice = number_format((($carriertotalpriceWithouttax *$item)/100),2,'.','');
                        $price_breakdown["ccf_operator"] = "PERCENTAGE";
                        $price_breakdown["ccf_value"] = $item;
                        $price_breakdown["ccf_level"] = 0;
                        $price_breakdown["baseprice"] = $basetaxprice;//$price_without_tax;
                        $price_breakdown["ccf_price"] = $price;
                        $price_breakdown["price"] = $price_breakdown["ccf_price"];
                    }else{
                        //
                    }
                }
                $status = $this->modelObj->saveShipmentPrice($price_breakdown);
                if(!$status){
                    return array("status"=>"error", "message"=>"shipment price breakdown not saved");
                }
                $totalTaxValue += $price_breakdown["price"];
            }
            return array("status"=>"success","total_surcharge_value"=>number_format($totalSurchargeValue, 2), "total_tax_value"=>number_format($totalTaxValue, 2));
        }
        return array("status"=>"error", "message"=>"shipment price breakdown not saved");
    }

    protected

    function _saveShipmentAttribute($param, $load_identity){
        if(isset($param->icon)){
            $_attribute["column_name"] = "icon";
            $_attribute["value"] = $param->icon;
            $_attribute["api_key"] = "icon";
            $_attribute["load_identity"] = $load_identity;
            $status = $this->modelObj->saveShipmentAttribute($_attribute);
            if($status==0){
                return array("status"=>"error", "message"=>"shipment attribute not saved");
            }
        }
        if(isset($param->dimensions)){
            foreach($param->dimensions as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "dimensions";
                $_attribute["load_identity"] = $load_identity;
                $status = $this->modelObj->saveShipmentAttribute($_attribute);
                if($status==0){
                    return array("status"=>"error", "message"=>"shipment attribute not saved");
                }
            }
        }
        if(isset($param->weight)){
            foreach($param->weight as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "weight";
                $_attribute["load_identity"] = $load_identity;
                $status = $this->modelObj->saveShipmentAttribute($_attribute);
                if($status==0){
                    return array("status"=>"error", "message"=>"shipment attribute not saved");
                }
            }
        }
        if(isset($param->time)){
            foreach($param->time as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = ($item!="") ? $item : 0;
                $_attribute["api_key"] = "time";
                $_attribute["load_identity"] = $load_identity;
                $status = $this->modelObj->saveShipmentAttribute($_attribute);
                if($status==0){
                    return array("status"=>"error", "message"=>"shipment attribute not saved");
                }
            }
        }

        return array("status"=>"success", "message"=>"shipment attribute saved");
    }

    protected

    function _saveShipmentCollection($data){

        $collection_data = array();

        $collection_data["carrier_code"]         = $data->carrier_code;
        //$collection_data["pickup_surcharge"]     = $data->pickup_surcharge;
        $collection_data["collection_date_time"] = $data->collection_date_time;
        $collection_data["is_regular_pickup"]    = $data->is_regular_pickup;
        $collection_data["pickup"]               = $data->pickup;
        $collection_data["service_id"]           = $data->service_id;

        $status = $this->modelObj->saveShipmentCollection($collection_data);
        if($status==0){
            return array("status"=>"error", "message"=>"shipment collection detail not saved");
        };
        return array("status"=>"success", "message"=>"shipment collection detail saved");
    }

    protected

    function _postRequest($data_string){
        //echo $data_string; die;
        
        //return '{"rate": {"UKMAIL":[{"D919022":[{"3":[{"rate":{"flow_type":"Domestic","price":"15.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.525,"tax_percentage":10.0}}]},{"9":[{"rate":{"flow_type":"Domestic","price":"13.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.325,"tax_percentage":10.0}}]},{"5":[{"rate":{"flow_type":"Domestic","price":"25.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":2.525,"tax_percentage":10.0}}]},{"2":[{"rate":{"flow_type":"Domestic","price":"9.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"4":[{"rate":{"flow_type":"Domestic","price":"10.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.025,"tax_percentage":10.0}}]},{"1":[{"rate":{"flow_type":"Domestic","price":"5.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"15:32:00:PM","last_pickup_time":"16:32:00:PM"},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":0.525,"tax_percentage":10.0}}]}]}],"PNP":[{"21232123":[{"one_hour":[{"rate":{"flow_type":"Domestic","price":4.38,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"1_hour_delivery","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:31:53"},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":0.876,"tax_percentage":20.0}}]},{"standard_same_day":[{"rate":{"flow_type":"Domestic","price":3.38,"rate_type":"Drop Rate","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"drop_service","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:32:41"},"service_times":{"last_booking_time":"16:05:00:PM","last_pickup_time":"17:00:00:PM"},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":0.676,"tax_percentage":20.0}}]},{"asap":[{"rate":{"flow_type":"Domestic","price":5.88,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":15,"unit":"MIN"},"category":"asap","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:26:50"},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":1.176,"tax_percentage":20.0}}]}]}]}, {"DHL": [{"420714888": [{"express_ww": [{"rate": {"weight_charge": 183.24,"fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0}}]}, {"express_domestic": [{"rate": {"weight_charge": 183.24,"fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0}}]},{"express_domestic_12": [{"rate": {"weight_charge": 189.24,"fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0}}]}]}]}}';
        
        //$server_output = '{"status":"success","message":"Rate found","data":{"UKMAIL":[{"D919022":[{"2":[{"rate":{"price":"9.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":null},"weight":{"weight":9999,"unit":null},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"2","name":"Testing 2"}}]},{"4":[{"rate":{"price":"10.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":1.025,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"4","name":"Testing 4"}}]},{"5":[{"rate":{"price":"25.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":2.525,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"5","name":"Testing 5"}}]},{"3":[{"rate":{"price":"15.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":1.525,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"3","name":"Testing 3"}}]},{"9":[{"rate":{"price":"13.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":1.325,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"9","name":"Testing 9"}}]},{"1":[{"rate":{"price":"5.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0},"taxes":{"total_tax":0.525,"tax_percentage":10},"carrier_info":{"carrier_id":"2","name":"ukmail","icon":"assets/images/carrier/dhl.png","description":"courier information goes here","code":"UKMAIL"},"service_info":{"code":"1","name":"Testing 1"}}]}]}]},"service_time":"11:20","service_date":"21/May/2018"}';
        //return json_decode($server_output);

        //return '{"rate":{"UKMAIL":[{"D919022":[{"2":[{"rate":{"price":"9.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"3":[{"rate":{"price":"15.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.525,"tax_percentage":10.0}}]},{"5":[{"rate":{"price":"25.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":2.525,"tax_percentage":10.0}}]},{"1":[{"rate":{"price":"5.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":0.525,"tax_percentage":10.0}}]},{"9":[{"rate":{"price":"13.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.325,"tax_percentage":10.0}}]},{"4":[{"rate":{"price":"10.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.025,"tax_percentage":10.0}}]}]}],"DHL": [{"420714888": [{"express_domestic": [{"rate": {"weight_charge": 183.24,"fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0}}]},{"express_domestic_12": [{"rate": {"weight_charge": 189.24,"fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0}}]}]}]}}';
              
        //$data_string = json_encode($data);

        //return '{"rate":{"UKMAIL":[{"D919022":[{"3":[{"rate":{"flow_type":"Domestic","price":"15.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.525,"tax_percentage":10.0}}]},{"9":[{"rate":{"flow_type":"Domestic","price":"13.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.325,"tax_percentage":10.0}}]},{"5":[{"rate":{"flow_type":"Domestic","price":"25.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":2.525,"tax_percentage":10.0}}]},{"2":[{"rate":{"flow_type":"Domestic","price":"9.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"4":[{"rate":{"flow_type":"Domestic","price":"10.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":1.025,"tax_percentage":10.0}}]},{"1":[{"rate":{"flow_type":"Domestic","price":"5.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"service_times":{"last_booking_time":"15:32:00:PM","last_pickup_time":"16:32:00:PM"},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0,"collection_surcharge":0},"taxes":{"total_tax":0.525,"tax_percentage":10.0}}]}]}],"PNP":[{"21232123":[{"one_hour":[{"rate":{"flow_type":"Domestic","price":4.38,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"1_hour_delivery","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:31:53"},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":0.876,"tax_percentage":20.0}}]},{"standard_same_day":[{"rate":{"flow_type":"Domestic","price":3.38,"rate_type":"Drop Rate","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"drop_service","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:32:41"},"service_times":{"last_booking_time":"16:05:00:PM","last_pickup_time":"17:00:00:PM"},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":0.676,"tax_percentage":20.0}}]},{"asap":[{"rate":{"flow_type":"Domestic","price":5.88,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":15,"unit":"MIN"},"category":"asap","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:26:50"},"service_times":{"last_booking_time":"","last_pickup_time":""},"surcharges":{"same_day_drop_surcharge":-2.0,"collection_surcharge":0},"taxes":{"total_tax":1.176,"tax_percentage":20.0}}]}]}]}}';

        $ch = curl_init($this->access_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: '.$this->authorization_token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);

        curl_close ($ch);
        return $server_output;
    }

    protected

    function _getParcelDimesionByShipmentId($shipment_id){
        $data = $this->modelObj->getParcelDimesionByShipmentId($shipment_id);

        $totalWeight = 0;
        $totalVolume = 0;
        $heighestLength = 0;
        $heighestWidth = 0;
        $heighestHeight = 0;
        $heighestWeight = 0;

        foreach($data as $item){
            $totalWeight += $item["parcel_weight"];

            if($item["parcel_length"]>$heighestLength)
                $heighestLength = $item["parcel_length"];

            if($item["parcel_width"]>$heighestWidth)
                $heighestWidth = $item["parcel_width"];

            if($item["parcel_height"]>$heighestHeight)
                $heighestHeight = $item["parcel_height"];

            if($item["parcel_weight"]>$heighestWeight)
                $heighestWeight = $item["parcel_weight"];

            $totalVolume += ($item["parcel_length"]*$item["parcel_width"]*$item["parcel_height"]);
        }
        return array("total_item"=>count($data),"total_volume"=>$totalVolume,"total_weight"=>$totalWeight, "heighest_length"=>$heighestLength, "heighest_width"=>$heighestWidth, "heighest_height"=>$heighestHeight, "heighest_weight"=>$heighestWeight);
    }

    protected

    function _saveShipmentDimension($param, $shipment_id){
        $data = array("shipment_highest_weight"=>$param["heighest_weight"] ,"shipment_highest_height"=>$param["heighest_height"] ,"shipment_total_item"=>$param["total_item"], "shipment_total_weight"=>$param["total_weight"], "shipment_total_volume"=>$param["total_volume"], "shipment_highest_length"=>$param["heighest_length"],"shipment_highest_width"=>$param["heighest_width"]);
        $this->modelObj->saveShipmentDimension($data, $shipment_id);
    }

    protected

    function getCustomerCarrierAccount($company_id, $customer_id, $collection_postcode, $collection_date){
        $carrierAccount = array();
        $carriers = $this->modelObj->getCompanyCarrier($company_id);

        //


        foreach($carriers as $carrier){
            array_push($carrierAccount, $carrier["account_id"]);
        }

        $carrierAccount = implode(',', $carrierAccount);

        $carrierLists = $this->modelObj->getCustomerCarrierAccountByAccountId($company_id, $customer_id, $carrierAccount);
        $lists = array();
        foreach($carrierLists as $carrierList){
            foreach($carriers as $key => $carrier) {
                if($carrierList["account_id"]==$carrier["account_id"]){
                    $carrier["account_number"] = $carrierList["account_number"];                    
                    array_push($lists, $carrier);
                }
            }
        }        
        $lists = Collection::_getInstance()->getCarrierAccountList($lists, array("zip"=>$collection_postcode),$customer_id,$company_id, $collection_date);   
        return $lists;
    }
	
    protected function _saveLabelInfoByLoadIdentity($labelArr,$loadIdentity){
            return $this->modelObj->saveLabelDataByLoadIdentity($labelArr,$loadIdentity);
    }

    protected function getCustomerInfo($user_id){
        return $this->modelObj->getCustomerInfo($user_id);
    }

    protected

    function getUserInfo($user_id){
        return $this->modelObj->getUserInfo($user_id);
    }
}
?>