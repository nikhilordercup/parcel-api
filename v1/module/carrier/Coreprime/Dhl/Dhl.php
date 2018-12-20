<?php
require_once dirname(dirname(__FILE__)) . "/Carrier_Coreprime_Request.php";
use Dompdf\Dompdf;

/* implements CarrierInterface */

final class Coreprime_Dhl extends Carrier {

    public $modelObj = null;

    public function __construct() {
        $this->modelObj = new Booking_Model_Booking();
		$this->libObj = new Library();
    }

    private function _getLabel($loadIdentity, $json_data) {
        $obj = new Carrier_Coreprime_Request();
        $label = $obj->_postRequest("label", $json_data);

        $labelArr = json_decode($label);
		//print_r($labelArr);die;
        if( isset($labelArr->label) ) {
            $pdf_base64 = $labelArr->label->base_encode;
            $labels = explode(",", $labelArr->label->file_url);
            $label_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/label/';
            $file_url = mkdir($label_path . $loadIdentity .'/dhl/', 0777, true);
            foreach ($labels as $dataFile) {
                //$dataFile = explode(".", $dataFile);
                $dataFile = $loadIdentity . '.pdf';
                //print_r($label_path);die;
                $file_name = $label_path . $loadIdentity .'/dhl/'. $dataFile;
                $data = base64_decode($pdf_base64);
                file_put_contents($file_name, $data);
                header('Content-Type: application/pdf');
            }
            $fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].LABEL_URL;

            unset($labelArr->label->base_encode);

            return array(
                    "status" => "success",
                    "message" => "label generated successfully",
                    "label_detail" => $labelArr,
                    "file_loc"=>$file_name,
                    "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $loadIdentity . '.pdf',
                    "label_tracking_number"=>$labelArr->label->tracking_number,
                    "label_files_png" => '',
                    "label_json" =>json_encode($labelArr)
            );

        } else {
            return array("status" => "error", "message" => $labelArr->error);
        }
    }

    public function getShipmentDataFromCarrier($loadIdentity, $rateDetail, $allData = array()) {
        $response = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);
        $paperLessTrade = false;
        foreach ($allData->delivery as $deliver) {
            $paperLessTrade = ($deliver->country->paperless_trade) ? true : false;
        }

        foreach ($shipmentInfo as $key => $data) {
            if ($data['shipment_service_type'] == 'P') {
                $response['from'] = array(
                    'name' => isset($data['shipment_customer_name']) ? $data['shipment_customer_name'] : '',
                    'company' => isset($data['shipment_companyName']) ? $data['shipment_companyName'] : '',
                    'phone' => isset($data['shipment_customer_phone']) ? $data['shipment_customer_phone'] : '',
                    'street1' => isset($data['shipment_address1']) ? $data['shipment_address1'] : '',
                    'street2' => isset($data['shipment_address2']) ? $data['shipment_address2'] : '',
                    'city' => isset($data['shipment_customer_city']) ? $data['shipment_customer_city'] : $data['shipment_customer_city'],
                    'state' => isset($data['shipment_county']) ? $data['shipment_county'] : '',
                    'zip' => isset($data['shipment_postcode']) ? $data['shipment_postcode'] : '',
                    'zip_plus4' => '',
                    'country' => isset($data['alpha2_code']) ? $data['alpha2_code'] : '',
                    'country_name' => isset($data['shipment_customer_country']) ? $data['shipment_customer_country'] : '',
                    'is_apo_fpo' => ''
                );
                $response['ship_date'] = $data['shipment_required_service_date'];
            } elseif ($data['shipment_service_type'] == 'D') {
                $response['carrier'] = $data['carrier_code'];
                $response['to'] = array(
                    'name' => isset($data['shipment_customer_name']) ? $data['shipment_customer_name'] : '',
                    'company' => isset($data['shipment_companyName']) ? $data['shipment_companyName'] : '',
                    'phone' => isset($data['shipment_customer_phone']) ? $data['shipment_customer_phone'] : '',
                    'street1' => isset($data['shipment_address1']) ? $data['shipment_address1'] : '',
                    'street2' => isset($data['shipment_address2']) ? $data['shipment_address2'] : '',
                    'city' => isset($data['shipment_customer_city']) ? $data['shipment_customer_city'] : $data['shipment_customer_city'],
                    'state' => isset($data['shipment_county']) ? $data['shipment_county'] : '',
                    'zip' => isset($data['shipment_postcode']) ? $data['shipment_postcode'] : '',
                    'zip_plus4' => '',
                    'country' => isset($data['alpha2_code']) ? $data['alpha2_code'] : '',
                    'country_name' => isset($data['shipment_customer_country']) ? $data['shipment_customer_country'] : '',
                    'email' => isset($data['shipment_customer_email']) ? $data['shipment_customer_email'] : '',
                    'is_apo_fpo' => '',
                    'is_residential' => ''
                );
                $carrierAccountNumber = $data['carrier_account_number'];
            }
        }

        $packageCode = '';
        $contents = array();
        foreach ($allData->parcel as $parcel) {
           $packageCode = $parcel->package_code;
           $contents[] = $parcel->name;
        }

        $response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity);
        $response['package'] = $this->getPackageInfo($loadIdentity);
        $serviceInfo = $this->getServiceInfo($loadIdentity);
        $response['currency'] = isset($serviceInfo['currency']) && !empty($serviceInfo['currency']) ? $serviceInfo['currency'] : 'GBP';
        $response['service'] = $serviceInfo['service_code'];
        $isDutiable = ( isset($allData->dutiable) && !empty($allData->dutiable) ) ?  "1" : "0";

        /*         * ********start of static data from requet json ************** */
        $response['extra'] = array(
            'reference_id' => "$loadIdentity",              // icargo order number  load identity
            'reference_id2' => '',                  // customer identification number
            'contents' => 'test description',       // contents of the parcel field, (qn for multiple ?)
            'terms_of_trade' => isset($allData->terms_of_trade) ? $allData->terms_of_trade : '',                 // ask arvind (this is only applicable for duitable shipment)
            'neutral_delivery' => "false",          // ?
            'paperless_trade' => "$paperLessTrade",           // flag that delivery country support paperless trade
            'inxpress' => '',                       // not in use
            'region_code' => 'EU',                  // ?
            'confirmation' => '',                   // ? delivery has been done or not, confirm with Akshar?
            'inbound' => 'false',                   // import shipment, if it is coming from another country
            'is_document' => isset($allData->is_document) && !empty($allData->is_document) ? "true" : "false",               // doc or non doc
            'customs_form_declared_value' => '',    // duitable related value
            'other_reseller_account' => '',         // not in use
            'gnd_payment_type' => '0',              // not in use
            'dutiable' => $isDutiable,              // true or false
            'residential_boolean' => '',            // yes or no (from customer)
            'itn' => '',                            // ?
            'auto_return' => '',                    // ?
            'return_service_id' => '',              // ?
            'return' => '',                         // ?
            'package_id' => '',                     // icargo order no
            'dry_ice_weight' => '',                 // specific value for content (ignore that)
            'dangerous_goods' => '',                // same as dry ice
            'order_number_barcode_format' => '',    // ?
            'order_number' => '',                   // ? (may be order number of icargo)
            'delivery_instruction' => '',           // print in form
            'home_delivery_premium_type' => '',     // ?
            'future_day_shipment' => '',            // ?
            'saturday_delivery' => '',              // not applicable
            'fedex_one_rate' => '',                 // works only for fedex
            'dry_ice' => '',                        // specific value for content (ignore that)
            'international' => '',                  // ?
            'image_type' => '',                     // ? can say label file type
            'print_to_screen' => 'false',           // no idea
            'mask_account_number' => '',            // not needed
            'intra_eu_shipping' => '',              // not needed
            'package_type' => "$packageCode",                   // package type ( Asked for multiple
            'payer_of_duties' => '',                // ask receiver will pay or sender will pay
            'dropoff_type' => '',                   // ?
            'thermal_image' => '',                  // ?
            'invoice_number' => ''                  // ?
        );

        $response['insurance'] = array('value' => ( isset($allData->is_insured) ? $allData->insurance_amount : 0 ) , 'currency' => $response['currency'], 'insurer' => '');

        if ($rateDetail) {
            $rateDetail = (array) $rateDetail;
            unset($rateDetail['price']);
            unset($rateDetail['info']);
            unset($rateDetail['currency']);
            unset($rateDetail['rate_type']);
        }

        $response['constants'] = $rateDetail;
        $response['label_options'] = array('format' => 'EPL2', 'size' => '', 'rotation' => '');
        $response['customs'] = '';
        $response['billing_account'] = array('payor_type' => '', 'billing_account' => '', 'billing_country_code' => '', 'billing_person_name' => '', 'billing_email' => '');
        $response['label'] = array();
        $response['method_type'] = 'post';

        $items = array();
        $totalValue = $totalWeight = 0;

        if(isset($allData->items)) {
            $key = 0;
            foreach ( $allData->items as $item ) {
                $items[$key]['item_description'] = $item->item_description;
                $items[$key]["item_quantity"] = $item->item_quantity;
                $items[$key]["country_of_origin"] = $item->country_of_origin->alpha2_code;
                $items[$key]["item_value"] = $item->item_value;
                $items[$key]["hs_code"] = '';
                $items[$key]["item_code"] = '';
                $items[$key]["item_weight"] = $item->item_weight;

                $totalValue = $totalValue + $item->item_value * $item->item_quantity;
                $totalWeight = $totalWeight + $item->item_weight * $item->item_quantity;
                $key++;
            }
        } else {
            $totalValue = ( isset($allData->is_insured) ? $allData->insurance_amount : 0 ) ;
        }

        $response['customs'] = array(
            'items' => $items,
            'declared_value' => "$totalValue",
            'total_weight' => $totalWeight,
            'terms_of_trade' => isset($allData->terms_of_trade) ? $allData->terms_of_trade : '',
            'contents' => ($contents) ? implode(', ', $contents) : ''
        );

        $response['extra']['contents'] = ($contents) ? implode(', ', $contents) : $response['extra']['contents'];
        $response['extra']['customs_form_declared_value'] = "$totalValue";

        /**********end of static data from requet json ************** */
        $response = $this->_getLabel($loadIdentity, json_encode($response));
        if( !$paperLessTrade && ($response['status'] != 'error') && $allData->dutiable ) {
            $customResp = $this->_getCustomInvoice($allData, $loadIdentity, $response);
			$response['invoice_created'] = $customResp['invoice_created'];
        } else {
            unset($response['label_detail']);
        }
        return $response;
    }


    private function _getCustomInvoice($allData, $loadIdentity, $labelDetail) {
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
        $wayBillNo = $label->license_plate_number[0];

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
        //echo $pdfHtml; die;
        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdfHtml);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        $dompdf->render();

        $label_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/label/';

        $invoiceName = $label_path . $loadIdentity . '/dhl/' .$loadIdentity.'-custom.pdf';

        file_put_contents($invoiceName, $dompdf->output());

        unset($dompdf);

        $labelFilePath = $labelDetail['file_loc'];
		
		//label and invoice files
		
		//$filenames = array($label_path . $loadIdentity . '/dhl/' . $loadIdentity.'.pdf',$invoiceName);
		//$outFile = uniqid().'.pdf'; 	
		
		/* if ($filenames) {
			try{
				$config = array('mode' => 'c','margin_left' => 15,'margin_right' => 0,'margin_top' => 5,'format' => 'A4','orientation' => 'L');
				$mpdf = new \Mpdf\Mpdf($config);
				$filesTotal = sizeof($filenames);
				$fileNumber = 1;
				$mpdf->SetImportUse();
				if (!file_exists($label_path.$loadIdentity.'/dhl/'.$outFile)) {
					$handle = fopen($label_path.$loadIdentity.'/dhl/'.$outFile, 'w');
					fclose($handle);
				}
				foreach ($filenames as $fileName) {
					if (file_exists($fileName)) {
						$pagesInFile = $mpdf->SetSourceFile($fileName);
						for ($i = 1; $i <= $pagesInFile; $i++) {
							$tplId = $mpdf->ImportPage($i);
							$mpdf->UseTemplate($tplId);
							if (($fileNumber < $filesTotal) || ($i != $pagesInFile)) {
								$mpdf->WriteHTML('<pagebreak />');
							}
						}
					}
					$fileNumber++;
				}	
				$mpdf->Output($label_path.$loadIdentity.'/dhl/'.$outFile);
			}catch(Exception $e){
                print_r($e);die;
            }
		
		} */

         /*$pdf = new ConcatPdf();
        $pdf->setFiles(array( $labelFilePath, $invoiceName));
        $pdf->concat();
        $pdf->Output( $label_path . $loadIdentity . '/dhl/' . $loadIdentity.'.pdf','F'); */
		
        $fileUrl = $this->libObj->get_api_url();

        return array("status" => "success", "message" => "label generated successfully", "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $loadIdentity.'.pdf','invoice_created'=>1);

    }

    public function getPackageInfo($loadIdentity) {
        $packageData = array();
        $packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
        foreach ($packageInfo as $data) {
            array_push($packageData, array("packaging_type" => $data["package"], "width" => $data["parcel_width"], "length" => $data["parcel_length"], "height" => $data["parcel_height"], "dimension_unit" => "CM", "weight" => $data["parcel_weight"], "weight_unit" => "KG"));
        }
        return $packageData;
    }

    public function getServiceInfo($loadIdentity) {
        $serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
        return $serviceInfo;
    }

    public function getCredentialInfo($carrierAccountNumber, $loadIdentity)
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
        $credentialInfo["latest_time"] = "17:00:00";
        $credentialInfo["earliest_time"]="14:00:00";
        $credentialInfo["carrier_account_type"] = array("1");

        return $credentialInfo;
    }


    private function validate($data) {
        $error = array();
        //call validation function from validation class
        if (!Dhl_Validation::_getInstance()->firstName('first_name')) {
            $error['first_name'] = Dhl_Validation::_getInstance()->errorMsg;
        }
        if (!Dhl_Validation::_getInstance()->lastName('last_name')) {
            $error['last_name'] = Dhl_Validation::_getInstance()->errorMsg;
        }
    }

}

?>
