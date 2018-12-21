<?php
class Booking extends Icargo
{
    public

    function __construct($data){
        $this->coreprimeObj = new Module_Coreprime_Api((object)array("email" => $data["email"], "access_token" => $data["access_token"]));

        $this->modelObj = new Booking_Model_Booking();

        $this->postcodeObj = new Postcode();

		$this->db = new DbHandler();
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

    function _saveAddressData($data, $customer_id,$address_op=""){
		$commonObj = new Common();
        $data = (object)$data;
        $postcode = ($data->country->alpha3_code == 'GBR') ? ( $this->postcodeObj->validate($data->postcode) ) : true;
        if($postcode) {
			if(isset($data->address_type)){
				$address_type = ($data->address_type == 'No') ? 'Business' : 'Residential' ;
			}else{
				$address_type = "";
			}
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
            $param["phone"]    	    = (isset($data->phone)) ? $data->phone : "";
            $param["name"]          = (isset($data->name)) ? $data->name : "";
            $param["email"]         = (isset($data->email)) ? $data->email : "";
            $param["contact_email"] = (isset($data->email)) ? $data->email : "";
            $param["company_name"]  = (isset($data->company_name)) ? $data->company_name : "";

			$addressData = array("company_name"=>$param["company_name"],"address_1"=>$param['address_line1'],"address_2"=>$param['address_line2'],"name"=>$param['first_name'],"city"=>$param['city'],"state"=>$param['state'],"company_id"=>$param['company_name'],"country"=>$param['country'],"email"=>$param['contact_email'],"postcode"=>$param['postcode']);

            $param["search_string"] = $commonObj->getAddressBookSearchString((object)$addressData);//str_replace(' ','',implode('',$param));;

            $param["country_id"]    = $data->country->id;

            $param["latitude"]      = isset($data->geo_position) ? $data->geo_position->latitude : 0.00000000;
            $param["longitude"]     = isset($data->geo_position) ? $data->geo_position->longitude : 0.00000000;
            $param["is_default_address"] = "N";
            $param["customer_id"]   = $customer_id;
            $param["is_warehouse"]  = "N";
            $param["address_type"]  = $address_type;
            $param["billing_address"] = "N";
            $param["billing_address"] = "N";

            $addressVersion = $this->modelObj->getAddressBySearchStringAndCustomerId($customer_id, $param["search_string"]);

			if(isset($data->address_origin) and $data->address_origin=='api'){
				$param["version_id"] = "version_1";
				$address_id = $this->modelObj->saveAddress($param);
				return array("status"=>"success", "address_id"=>$address_id,"address_data"=>$param);

			}else{
				if(!$addressVersion["address_id"]){
					if(($address_op == null) OR ($address_op == "add")){
						$param["version_id"] = "version_1";
						$address_id = $this->modelObj->saveAddress($param);
					}else{
						$address_id = (isset($data->address_list->id)) ? $data->address_list->id : 0;
						$update = $this->db->update("address_book",$param,"id='$address_id'");
					}
					return array("status"=>"success", "address_id"=>$address_id,"address_data"=>$param);
				}else{
					$version = explode("_", $addressVersion['version_id']);
					$param["version_id"] = "version_".($version[1]+1);
					return array("status"=>"success", "address_id"=>$addressVersion['address_id'],"address_data"=>$param);
				}
			}
        }else{
            return array("status"=>"error", "message"=>"Invalid postcode");
        }
    }

    protected

    function _saveShipment($param1, $param2, $parcel, $address_info, $warehouse_id, $company_id, $company_code, $service_date, $collection_end_at, $load_group_type_code, $job_type_code, $load_group_type_name, $shipment_service_type, $execution_order, $carrier_account_number=null, $is_internal=0, $shipment_instruction=""){
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

            $data['shipment_xml_reference'] = "";

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

            $data['shipment_latitude'] = isset($data->geo_position) ? $data->geo_position->latitude : 0.00000000;
            $data['shipment_longitude'] = isset($data->geo_position) ? $data->geo_position->longitude : 0.00000000;
            $data['shipment_latlong'] = isset($data->geo_position) ? $param2->geo_position->latitude.",".$param2->geo_position->longitude : "0.00000000,0.00000000";
            $data['shipment_create_date'] = date("Y-m-d", strtotime('now'));
            $data['icargo_execution_order'] = $execution_order;
            $data['shipment_executionOrder'] = $execution_order;

            $data['customer_id'] = $param1->customer_id;

	          $data['search_string'] = "";

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

            $data["shipment_instruction"] = (isset($param2->pickup_instruction)) ? $param2->pickup_instruction :"";//$shipment_instruction;

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

        $parcelData['package_name']  = $parcel->name;
		$parcelData['package']       = $parcel->package_code;
        $parcelData['parcel_ticket'] = $parcelTicketNumber;
        $parcelData['parcel_weight'] = round($parcel->weight/$parcel->quantity,2);
		$parcelData['total_weight'] =  $parcel->weight;
        $parcelData['parcel_height'] = $parcel->height;
        $parcelData['parcel_length'] = $parcel->length;
        $parcelData['parcel_width']  = $parcel->width;
        //$parcelData["quantity"]      = $parcel->quantity;
        $parcelData['parcel_type']   = $parcel_type;//($valuedata['purposeTypeName'] == 'Collection') ? 'P' : 'D';
		$parcelData['is_document']   = isset($parcel->is_document) ? 'Y' : 'N';

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

    function _saveInfoReceived($load_identity){
        return $this->modelObj->saveInfoReceived($load_identity);
    }

    protected

    function _saveShipmentService($serviceOpted, $surcharges, $load_identity, $customer_id, $booking_status, $otherDetail,$serviceId, $cust_ref1, $cust_ref2,$ismanualbooking,$manualbookingreference){

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
            $service_data["accountkey"] = isset( $serviceOpted->rate->act_number) ? $serviceOpted->rate->act_number : '';
			$service_data["parent_account_key"] = isset( $serviceOpted->rate->act_number) ? $serviceOpted->rate->act_number : '';
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
            //$service_data["carrier"] = $serviceOpted->carrier_info->carrier_id;
            $service_data["carrier"] = $serviceOpted->carrier_info->account_id;
            $service_data["isInvoiced"] = "NO";

            $service_data["invoice_reference"] = "";
            $service_data["service_request_string"] = $this->serviceRequestString;
            $service_data["service_response_string"] = $this->serviceResponseString;

            $customerData = $this->getBookedShipmentsCustomerInfo($customer_id);
	        $service_data['customer_type'] = $customerData['customer_type'];

	        $service_data["is_insured"] = ($otherDetail['is_insured'] == true) ? 1 : 0;;
            $service_data["reason_for_export"] = $otherDetail['reason_for_export'];
            $service_data["tax_status"] = $otherDetail['tax_status'];
            $service_data["terms_of_trade"] =  $otherDetail['terms_of_trade'];

            $service_data["label_tracking_number"] = '0';
            $service_data["label_files_png"] = '';
            $service_data["label_file_pdf"] =  '';
            $service_data["label_json"] =  '';

            $service_data["status"] = $booking_status;
            $service_data["customer_reference1"] = $cust_ref1;
            $service_data["customer_reference2"] = $cust_ref2;
            $service_data["is_manualbooking"] = $ismanualbooking;
            $service_data["manualbooking_ref"] = $manualbookingreference;
            $service_data["booked_service_id"] = $serviceOpted->rate->info->service_id;

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
        //$price_breakdown["carrier_id"]    = $data->carrier_info->carrier_id;
        $price_breakdown["carrier_id"]    = $data->carrier_info->account_id;
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
                        //$price_breakdown["carrier_id"] = $data->carrier_info->carrier_id;
                        $price_breakdown["carrier_id"]    = $data->carrier_info->account_id;
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

    function _postRequest($data){
        return $this->coreprimeObj->_postRequest($data);
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

    protected

    function _getCustomerAccountBalence($customer_id,$bookShipPrice){
        $available_credit = $this->modelObj->getCustomerAccountBalence($customer_id);
        if(($available_credit["available_credit"] <= 0  ) || ($available_credit["available_credit"] < $bookShipPrice) ){
            return array("status"=>"error", "message"=>"you don't have sufficient balance,your current balance is ".$available_credit["available_credit"]." .","available_credit"=>$available_credit['available_credit']);
        }
        return array("status"=>"success", "message"=>"sufficient balance.","available_credit"=>$available_credit['available_credit']);
    }


    protected

    function _manageAccounts($priceServiceid,$load_identity, $customer_id,$company_id){
          $priceData = $this->modelObj->getBookedShipmentsPrice($priceServiceid,$customer_id);
          if(isset($priceData["grand_total"])){
                 $creditbalanceData = array();
                 $creditbalanceData['customer_id']          = $customer_id;
                 $creditbalanceData['customer_type']        = $priceData['customer_type'];
                 $creditbalanceData['company_id']           = $company_id;
                 $creditbalanceData['payment_type']         = 'DEBIT';
                 $creditbalanceData['pre_balance']          = $priceData["available_credit"];
                 $creditbalanceData['amount']               = $priceData["grand_total"];
                 $creditbalanceData['balance']              = $priceData["available_credit"] - $priceData["grand_total"];
                 $creditbalanceData['create_date']          = date("Y-m-d");
                 $creditbalanceData['payment_reference']    = $load_identity;
                 $creditbalanceData['payment_desc']         = 'BOOK A SHIPMENT';
                 $creditbalanceData['payment_for']          = 'BOOKSHIP';
                 $addHistory = $this->modelObj->saveAccountHistory($creditbalanceData);
                 if($addHistory>0){
                      $condition = "user_id = '".$customer_id."'";
                      $updateStatus = $this->modelObj->editAccountBalance(array('available_credit'=>$creditbalanceData['balance']),$condition);
                      if($updateStatus){
                          return array("status"=>"success", "message"=>"Price Update save");
                      }
                  }
        }
        return array("status"=>"error", "message"=>"shipment service not saved");
    }

     protected

    function _getCarrierCode($carrier_id){
       return $this->modelObj->getCarrierCode($carrier_id);
    }
    public function getBookedShipmentsCustomerInfo($customerId){
       return $this->modelObj->getBookedShipmentsCustomerInfo($customerId);
    }
    public function _isInternalCarrier($carrier_code){
       return $this->modelObj->isInternalCarrier($carrier_code);
    }
    
}
?>