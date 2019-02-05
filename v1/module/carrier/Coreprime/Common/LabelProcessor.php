<?php
/**
 * Created by Mandeep Singh Nain.
 * User: Mandeep
 * Date: 18-12-2018
 * Time: 11:10 AM
 */

namespace v1\module\carrier\Coreprime\Common;
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'coreprime' . DIRECTORY_SEPARATOR . 'api.php';
use Dompdf\Dompdf;

class LabelProcessor
{
    public $modelObj = null;

    public function __construct(\Carrier $carrier)
    {
        $this->modelObj = $carrier->modelObj;
        $this->libObj = new \Library();
    }

    private function _getLabel($loadIdentity, $json_data,$child_account_data)
    {
        $json_data = json_decode($json_data);
        $app = new \Slim\Slim();
        $obj = new \Module_Coreprime_Api($json_data);
        $label = $obj->_postRequest($json_data);
        $labelArr = is_string($label) ? json_decode($label, true) : $label;        
        $labelArr = $labelArr['label'];  
		if($labelArr['status']=='error'){
			return array("status"=>$labelArr['status'],"message"=>$labelArr['message']);
		}
        if ($labelArr['tracking_number'] != "") {
            $labelArr['status'] = "success";
            $labelArr['file_path'] = $labelArr['file_url'];
            $labelArr['label_tracking_number'] = $labelArr['tracking_number'];
            $labelArr['label_json'] = $labelArr['label_json'];
            $labelArr['file_loc'] = isset($labelArr['file_url']) ? $labelArr['file_url'] : "";
            $labelArr['label_files_png'] = isset($labelArr['label_files_png']) ?  $labelArr['label_files_png'] : "";
            $labelArr['label_detail'] = new \stdClass();
            $labelArr['label_detail']->label = (object)$labelArr;
            if($json_data->carrier=='Tuffnells'){
				$labelArr['label_json'] = '';
			}elseif($json_data->carrier=='UKMAIL'){
				$labelArr['child_account_data'] = $child_account_data;
			}
        } 
        return $labelArr;
    }
    
    public function getShipmentDataFromCarrier($loadIdentity, $rateDetail = array(), $allData = array())
    {       
        $collection = (array)$allData->collection; 
        $delivery = (array)$allData->delivery;     
        if(count($collection) > 0)
        {
            foreach($collection as $collectionTmp)
            {
               $collection = $collectionTmp;
            }
        }
        
        if(count($delivery) > 0)
        {
            foreach($delivery as $deliveryTmp)
            {
               $delivery = $deliveryTmp;
            }
        }
                       
        $response = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);
        
        $paperLessTrade = false;
        foreach ($allData->delivery as $deliver) {
            $paperLessTrade = ($deliver->country->paperless_trade) ? true : false;
        }
        
        
        foreach ($shipmentInfo as $key => $data) {
            if ($data['shipment_service_type'] == 'P') {
                $response['from'] = array(
                    "name" => $data["shipment_customer_name"],
                    "company" => $data["shipment_companyName"],
                    "phone" => $data["shipment_customer_phone"],
                    "street1" => $data["shipment_address1"],
                    "street2" => $data["shipment_address2"],
                    "city" => $data["shipment_customer_city"],
                    "state" => (isset($data["shipment_county"]) ? $data["shipment_county"]: $collection->state),
                    "zip" => $data["shipment_postcode"],
                    "country" => $data["shipment_country_code"],
                    "country_name" => $data["shipment_customer_country"],                    
                    "is_apo_fpo" => "",
                    "email" => (isset($collection->email) && $collection->email != '') ? $collection->email : '',
                    "is_res" => (isset($collection->address_type) && $collection->address_type == 'Residential') ? TRUE : FALSE
                );
                $response['ship_date'] = $data['shipment_required_service_date'];

            } elseif ($data['shipment_service_type'] == 'D') {
                $response['carrier'] = $data['carrier_code'];

                $response['to'] = array(
                    "name" => $data["shipment_customer_name"],
                    "company" => $data["shipment_companyName"],
                    "phone" => $data["shipment_customer_phone"],
                    "street1" => $data["shipment_address1"],
                    "street2" => $data["shipment_address2"],
                    "city" => $data["shipment_customer_city"],
                    "state" => (isset($data["shipment_county"]) ? $data["shipment_county"] : $delivery->state),
                    "zip" => $data["shipment_postcode"],
                    "zip_plus4" => "",
                    "country" => $data["shipment_country_code"],
                    "country_name" => $data["shipment_customer_country"],
                    "email" => $data["shipment_customer_email"],
                    "is_apo_fpo" => "",
                    "is_res" => (isset($delivery->address_type) && $delivery->address_type == 'Residential') ? TRUE : FALSE
                );

                $carrierAccountNumber = $data["carrier_account_number"];
                //$response['ship_date'] = $data['shipment_required_service_date'];
            }
        }                
        $response['package'] = $this->getPackageInfo($loadIdentity);
        $serviceInfo = $this->getServiceInfo($loadIdentity);
        $delivery_instruction = $this->modelObj->getDeliveryInstructionByLoadIdentity($loadIdentity);
		$pickup_instruction   = $this->modelObj->getPickupInstructionByLoadIdentity($loadIdentity);
        $response['currency'] = $serviceInfo['currency'];
        $response['service'] = $serviceInfo['service_code'];
        $response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity,$allData);

        /*start of binding child account data*/
        if (isset($response['credentials']['is_child_account']) && $response['credentials']['is_child_account'] == 'yes') {
            $child_account_data = array("is_child_account" => $response['credentials']['is_child_account'],
                "parent_account_number" => $response['credentials']['parent_account_number'],
                "child_account_number" => $response['credentials']['credentials']['account_number']);
        } else {
            $child_account_data = array();
        }
        /*end of binding child account data*/

        //$response['credentials'] = $response['credentials']['credentials'];

        $response['extra'] = array(
            "service_key" => $serviceInfo['service_code'],
            "long_length" => $allData->LongLength,
            "bookin" => "",
            'paperless_trade' => "$paperLessTrade", 
            "exchange_on_delivery" => "",
            "reference_id" => "",
            "region_code" => "",
            "confirmation" => "",
            "is_document" => "",
            "auto_return" => "",
            "return_service_id" => "",
            "special_instruction" => $delivery_instruction['shipment_instruction'],
			"pickup_instruction" => $pickup_instruction['shipment_instruction'],
            "custom_desciption" => $serviceInfo['customer_reference1'],
            "custom_desciption2" => $serviceInfo['customer_reference2'],
            "custom_desciption3" => "",
            "customs_form_declared_value" => "",
            "document_only" => "",
            "no_dangerous_goods" => "",
            "in_free_circulation_eu" => "",
            "extended_cover_required" => (isset($allData->is_insured) && $allData->is_insured!='') ? $allData->insurance_amount : "",
            "invoice_type" => ""
        );

        $response['currency'] = isset($serviceInfo['currency']) && !empty($serviceInfo['currency']) ? $serviceInfo['currency'] : 'GBP';
        $response['insurance'] = array('value' => (isset($allData->is_insured) ? $allData->insurance_amount : 0), 'currency' => $response['currency'], 'insurer' => '');
        $response['constants'] = array(
            "shipping_charge" => "",
            "weight_charge" => "",
            "fuel_surcharge" => "",
            "remote_area_delivery" => "",
            "insurance_charge" => "",
            "over_sized_charge" => "",
            "over_weight_charge" => "",
            "discounted_rate" => ""
        );
        $response['label_options'] = "";
        $response['customs'] = "";
        $response['billing_account'] = array(
            "payor_type" => "",
            "billing_account" => "",
            "billing_country_code" => "",
            "billing_person_name" => "",
            "billing_email" => ""
        );
        $response['label'] = array();
        $response['providerInfo'] = $allData->providerInfo;
        $response['method_type'] = "post";
		$response['access_token'] = $allData->access_token;
		$response['email'] = $allData->email;
		$response['company_id'] = $allData->company_id;
		$response['customer_id'] = $allData->customer_id;
		$response['collection_user_id'] = $allData->collection_user_id;
		$response['parcel_total_weight'] = $allData->parcel_total_weight;
        
        
        $parcelQuantity = 1;
        $tempParcel = (array)$allData->parcel;
        if(count($tempParcel) > 0)
        {
            foreach($tempParcel as $parcel)
            {
                $parcelQuantity = $parcel->quantity;
            }
        }
        $response['pickup_detail'] = array(
            'pickup_date'=>(isset($allData->pickup_date)) ? $allData->pickup_date : '',
            'earliest_pickup_time'=> (isset($allData->earliest_pickup_time)) ? $allData->earliest_pickup_time : '00:00',
            'latest_pickup_time'=> (isset($allData->latest_pickup_time)) ? $allData->latest_pickup_time : '00:00',
            'pickup_instruction'=>(isset($collection->pickup_instruction)) ? $collection->pickup_instruction : '',
            'package_quantity'=>$parcelQuantity,
            'package_location'=> (isset($allData->package_location) && $allData->package_location != '') ? $allData->package_location : 'Front Desk',
            'collectionjobnumber'=> (isset($allData->collectionjobnumber) && $allData->collectionjobnumber != '') ? $allData->collectionjobnumber : ''
        );
                                        
        $response =  $this->_getLabel($loadIdentity, json_encode($response), $child_account_data);
        if($allData->service_opted->collected_by[0]->carrier_code == 'DHL')
        {
            if( !$paperLessTrade && ($response['status'] != 'error') && $allData->dutiable ) {
            $customResp = $this->_getCustomInvoice($allData, $loadIdentity, $response);
			$response['invoice_created'] = $customResp['invoice_created'];
            } else {
                unset($response['label_detail']);
            }
        }     
        return $response;        
    }

    private function _getCustomInvoice($allData, $loadIdentity, $labelDetail) 
    {                        
        $label = $labelDetail['label_detail']->label;

        $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<title>Commercial Invoice - '.$loadIdentity.'</title><style type="text/css"> .sender-receiver { width:500px; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px; }</style></head><body>';
        $html .= '<table border="1" cellpadding="0" cellspacing="0" style="margin:0 auto;"><tr><td><table cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
        $html .= '<tr><td colspan="2" align="center" height="25" style="padding:5px;  font-family: arial; font-size:25px; font-weight:bold;">Commercial Invoice</td></tr><tr><td style="width:500px;">';
        $html .= '<table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';

        $collection = $delivery = '';
        $tortalW = $totalPrice = $totalQ = 0;
        $collectionDate = date('d M Y', strtotime($allData->collection_date));
        $shipItems = $allData->items;
        $currencyCode = $allData->service_opted->rate->currency;
        $carrier = $allData->service_opted->collected_by[0]->carrier_code;
        $wayBillNo = (isset($label->license_plate_number)) ? $label->license_plate_number[0]:'';

        foreach ($allData->collection as $coll) {
            $collection = $coll;
        }
        foreach ($allData->delivery as $coll) {
            $delivery = $coll;
        }

        $sender  = '<tr><th align="left" style="padding:2px; height:25px;">Sender:</th></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->company_name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->address_line1.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->address_line2.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->city.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->state.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->postcode.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->country->short_name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.(isset($collection->email) ? $collection->email:'').'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">Phone Number: '.$collection->phone.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;"></td></tr></table></td>';

        $receiver = '<td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';
        $receiver .= '<tr><th align="left" style="padding:2px; height:25px;"> Recipient:</th></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.(isset($delivery->company_name) ? 'testdeliver@gmail.com':'').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->name.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->address_line1.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->address_line2.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->city.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.( isset($delivery->state) ? $delivery->state : '').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->postcode.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->country->short_name.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.(isset($delivery->email) ? $delivery->email:'').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">Phone Number: '.$delivery->phone.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;"></td></table></td></tr></table></td></tr>';

        $invoice = '<tr><td><table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;  font-family: arial; font-size: 14px;">';
        $invoice .= '<tr><td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial;font-size: 14px;">';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Invoice Date:</th><td align="left" style="padding:2px; height:25px;"> '.$collectionDate.'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">DHL Waybill Number: </th><td align="left" style="padding:2px; height:25px;"> '.$wayBillNo.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Carrier: </th><td align="left" style="padding:2px; height:25px;"> '.$carrier.'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Type of Export:</th><td align="left" style="padding:2px; height:25px;"> '.$allData->reason_for_export.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Reason for Export: </th><td align="left" style="padding:2px; height:25px;"> '.$allData->reason_for_export.' </td></tr>';
        $invoice .= '</table></td><td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial; font-size: 14px;">';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Invoice Number:</th><td align="left" style="padding:2px; height:25px;"></td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Sender\'s Reference: </th><td align="left" style="padding:2px; height:25px;">'.( isset($collection->recipient_ref) ? $collection->recipient_ref : '' ).'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Recipient\'s Reference: </th><td align="left" style="padding:2px; height:25px;">'.( isset($delivery->sender_ref) ? $delivery->sender_ref : '' ).'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Type of Export:</th><td align="left" style="padding:2px; height:25px;"> '.$allData->terms_of_trade.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Tax Id/VAT/EIN#: </th><td align="left" style="padding:2px; height:25px;">'.$allData->tax_status.'</td></tr>';
        $invoice .= '</table></td></tr></table></td></tr>';

        $gNotes = '<tr><th style="padding:2px; height:25px; text-align:left; font-family:arial; font-size:20px;" colspan="2">General Notes:</th></tr><tr><td style="padding:2px; height:25px;" colspan="2"></td></tr><tr><td>';

        $items = '<table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';
        $items .= '<tr><th align="left" style="padding:2px; height:25px;"> Quantity</th><th align="left" style="padding:2px; height:25px;"> Country of Origin</th><th align="left" style="padding:2px; height:25px;"> Description of Contents</th><th align="left" style="padding:2px; height:25px;"> Harmonised Code </th>';
        $items .= '<th align="left" style="padding:2px; height:25px;"> Unit Weight</th><th align="left" style="padding:2px; height:25px;"> Unit Value </th><th align="left" style="padding:2px; height:25px;"> SubTotal </th></tr>';

        foreach ($shipItems as $item) {
            //Loop start
            $items .= '<tr><td align="right" style="padding:2px; height:25px;">'.$item->item_quantity.'</td><td align="left" style="padding:2px; height:25px;">'.$item->country_of_origin->short_name.'</td>';
            $items .= '<td align="left" style="padding:2px; height:25px;">'.$item->item_description.'</td><td align="left" style="padding:2px; height:25px;"></td>';
            $items .= '<td align="right" style="padding:2px; height:25px;">'.$item->item_weight.' kgs</td><td align="right" style="padding:2px; height:25px;">'.$item->item_value.'</td>';
            $items .= '<td align="right" style="padding:2px; height:25px;">'.($item->item_quantity * $item->item_value).'</td></tr>';
            //Loop end here
            $tortalW += $item->item_quantity * $item->item_weight;
            $totalPrice += $item->item_quantity * $item->item_value;
            $totalQ += $item->item_quantity;

        }

        $otherChanrges = ( $label->fuel_surcharge +$label->remote_area_delivery + $label->over_sized_charge + $label->over_weight_charge );

        $items .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Total Net Weight:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$tortalW.' kgs </td>';
        $items .= '<td align="left" style="padding:2px; height:25px;"><strong>Total Declared Value:</strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$totalPrice.'</td></tr>';
        $items .= '<tr><td align="left" style="padding:2px; height:25px;"><strong> Total Gross Weight:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$tortalW.' kgs </td>';
        //"total_cost": 32.64, "weight_charge": 32.64, "fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0,"discounted_rate": 32.64,"product_content_code": "WPX","license_plate_number": ["JD011000000002811859"],"chargeable_weight": "0.5","service_area_code": "LON",
        $charges = '<td align="left" style="padding:2px; height:25px;"><strong>Freight & Insurance Charges:</strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$label->insurance_charge.'</td></tr>';
        $charges .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Total Shipment Pieces:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$totalQ.' </td>';
        $charges .= '<td align="left" style="padding:2px; height:25px;"><strong> Other Charges: </strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$otherChanrges.'</td></tr>';
        $charges .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Currency Code:</strong></td><td align="left" style="padding:2px; height:25px;"> '.$currencyCode.' </td>';
        $charges .= '<td align="left" style="padding:2px; height:25px;"><strong> Total Invoice Amount: </strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$label->total_cost.'</td></tr></table></td></tr></table>';

        $div = '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0 0 0px; line-height:20px;"> These commodities, technology or software were exported from United States Of America in accordance with the Export Administration Regulations. Diversion contrary to United States Of America law is prohibited. </div>';
        $div .= '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0px; line-height:20px;"> I/We hereby certify that the information on this invoice is true and correct and that the contents of this shipment are as stated above. </div>';
        $div .= '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px;"><div style="width:50%; float:left;"><h4 style="float: left; font-size: 16px; width: 50px; ">Signature:</h4>';
        $div .= '<p style="float: left; height: 2px; width: 200px; background-color: #000; margin-top: 35px; margin-left: 85px;"></p></div>';
        $div .= '<div style="width:25%; float:right;">;<h4 style="float: left; font-size: 16px; width: 50px; ">Date:</h4><p style="float: right; height: 2px; width: 200px; background-color: #000; margin-top: 35px;"></p></div>';
        $div .= '<div style="clear: both;"></div><div style="width:500px; margin-bottom:4px; float:left;"><span style="float: left; margin:0px; width: 20%; font-size: 16px; font-weight:bold;"> Name: </span>';
        $div .= '<span style="float: left; margin:0 10px 0 70px; width: 117px;">'.$delivery->name.'</span></div><div style="clear: both;"></div>';
        $div .= '<div style="width:100%; margin-bottom:30px; float:left;"><p style="float:left; margin:0px; width: 117px; font-size: 16px; font-weight:bold;"> Title: </p><p style="float: left; margin:0 0 0 70px; width: 117px;"> Sr S/W Engg</p></div></div></body></html>';

        $pdfHtml = $html . $sender . $receiver . $invoice . $gNotes . $items . $charges . $div;        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdfHtml);       
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();               
        $label_path = dirname(dirname((dirname(dirname(dirname(dirname(__FILE__))))))) . '/label/';        
        $invoiceName = $label_path . $loadIdentity . '/'. strtolower($carrier).'/' .$loadIdentity.'-custom.pdf';
        file_put_contents($invoiceName, $dompdf->output());
        unset($dompdf);
        $labelFilePath = $labelDetail['file_loc'];						
        $fileUrl = $this->libObj->get_api_url();
        return array("status" => "success", "message" => "label generated successfully", "file_path" => $fileUrl . "/label/" . $loadIdentity . '/'.strtolower($carrier).'/' . $loadIdentity.'.pdf','invoice_created'=>1);
    }

    public function getPackageInfo($loadIdentity)
    {
        $packageData = array();
        $packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
        foreach ($packageInfo as $data) {
            array_push($packageData, array("packaging_type" => $data["package"], "width" => $data["parcel_width"], "length" => $data["parcel_length"], "height" => $data["parcel_height"], "dimension_unit" => "CM", "weight" => $data["parcel_weight"], "weight_unit" => "KG"));
        }
        return $packageData;
    }

    public function getServiceInfo($loadIdentity)
    {
        $serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
        return $serviceInfo;
    }

    public function getCredentialInfo($carrierAccountNumber, $loadIdentity,$allData)
    {
        $credentialData = array();
        $credentialData = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);

        $credentialInfo["username"] = $credentialData["username"];
        $credentialInfo["password"] = $credentialData["password"];
        $credentialInfo["authentication_token"] = $credentialData["authentication_token"];
        $credentialInfo["authentication_token_created_at"] = $credentialData["authentication_token_created_at"];
        $credentialInfo["token"] = $credentialData["token"];
        $credentialInfo["account_number"] = $carrierAccountNumber;
        $credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = isset($allData->pickup_latest_time) ? $allData->pickup_latest_time : "";
        $credentialInfo["earliest_time"] = isset($allData->pickup_earliest_time) ? $allData->pickup_earliest_time : "";
		$credentialInfo["requested_collection_date"] = isset($allData->pickup_latest_time) ? $allData->pickup_latest_time : "";
		$credentialInfo["pickup_date"] = isset($allData->pickup_date) ? $allData->pickup_date : "";
		$credentialInfo["earliest_pickup_time"] = isset($allData->earliest_pickup_time) ? $allData->earliest_pickup_time : "";
		$credentialInfo["latest_pickup_time"] = isset($allData->latest_pickup_time) ? $allData->latest_pickup_time : "";
		$credentialInfo["collectionjobnumber"] = isset($allData->collectionjobnumber) ? $allData->collectionjobnumber : "";
        $credentialInfo["carrier_account_type"] = array("1");

        return $credentialInfo;
    }


    private function validate($data)
    {
        $error = array();
    }
}
