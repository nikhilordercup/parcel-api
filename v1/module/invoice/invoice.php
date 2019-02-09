<?php
class Invoice extends Icargo{
public $modelObj = null;
public $pdf = null;
public function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = AllInvoice_Model::getInstanse();
    }
public function getallinvoice($param){
	if(isset($param->customer_id))
		$invoiceData = $this->modelObj->getAllInvoiceByCustomerId($param->warehouse_id,$param->company_id,$param->customer_id);
	else
		$invoiceData = $this->modelObj->getAllInvice($param->warehouse_id,$param->company_id);
	for ($i =0; $i< count($invoiceData); $i++ ){
		$invoiceData[$i]['download_path']= Library::_getInstance()->get_api_url().'assets/outputpdf/'.$invoiceData[$i]['incoice_pdf'];
		}
	return $invoiceData;
   }
public function createInvoice($param){
      $_start_date          = (isset($param->start_date) and ($param->start_date))?$param->start_date:'1970-01-01';
      $_end_date            = (isset($param->end_date) and ($param->end_date))?$param->end_date:'1970-01-01';
      $_sendpdftocustomer   = (isset($param->sendpdftocustomer) and ($param->sendpdftocustomer))?$param->sendpdftocustomer:false;
      $_attachvoucher       = (isset($param->attachvoucher) and ($param->attachvoucher))?true:false;
      $_sendtomail          = (isset($param->sendtomail) and ($param->sendtomail))?$param->sendtomail:false;
      $_sendtocustomemail   = (isset($param->sendtomailofemail) and ($param->sendtomailofemail))?$param->sendtomailofemail:'';
      $_start_date          = (isset($param->addolduninvoiced) and ($param->addolduninvoiced))?'1970-01-01':$_start_date;// intialized start date  for add previous booking
      $_email               = $param->email;
      $_company_id          = $param->company_id;
      $customerfilter = isset($param->customer)?"AND A.customer_id = '".$param->customer."'":" ";
      $_start_date          = date('Y-m-d',strtotime($_start_date));
      $_end_date            = date('Y-m-d',strtotime($_end_date));
      $invoicedDocketData = $this->modelObj->getAllInvoicedDocket($_company_id,$_start_date,$_end_date,$customerfilter);

      $tempdatap = array();
      $getAllVoucher = array();
      foreach($invoicedDocketData as $key=>$val){
          $tempdatap[$val['customer_id']]['shipments'][] = $val;
          $tempdatap[$val['customer_id']]['total']['totalshipment'][] = 1;
          $tempdatap[$val['customer_id']]['total']['totalitems'][]= $val['items'];
      }

      if($_attachvoucher){
        $getAllVoucher = $this->modelObj->getAllVoucher($_company_id,$customerfilter);
      }
      if(count($getAllVoucher)>0){
      foreach($getAllVoucher as $key=>$val){
         $tempdatap[$val['customer_id']]['vouchers'][] = $val;
         $tempdatap[$val['customer_id']]['total']['totalshipment'][] = 1;
         $tempdatap[$val['customer_id']]['total']['totalitems'][]= $val['items'];
       }
     }

      $invoicedDocketData = $tempdatap;
      $invoicecount = 0;
      $invoiceRef = array();
      $allInvoiceRef = array();
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
         $allInvoiceRef[] = $invoicedata['invoice_reference'];
         $invoiceRef[] = $invoiceId;
         $invoicecount++;
         $invoicePriceData = array('base_amount'=>0,'surcharge_total'=>0,'fual_surcharge'=>0,'tax'=>0,'total_ammount'=>0);
          if($invoiceId){
            if(isset($val['shipments']) and count($val['shipments'])>0){
              foreach($val['shipments'] as $innerkey=>$innerval){
                 $docketdata = array();
                 $docketdata['invoice_id'] = $invoiceId;
                 $docketdata['invoice_reference'] = $invoicedata['invoice_reference'];
                 $docketdata['company_id'] = $_company_id;
                 $docketdata['reference_id'] = $innerval['reference_id'];
                 $docketdata['reference'] = $innerval['reference'];
                 $docketdata['invoice_type'] = 'SHIPMENT';
                 $docketdata['reference1'] = isset($innerval['reference1'])?$innerval['reference1']:'';
                 $docketdata['reference2'] = isset($innerval['reference2'])?$innerval['reference2']:'';
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
                  $updatestatus = $this->modelObj->editContent('shipment_service',array('isInvoiced'=>'YES','invoice_reference'=>$invoicedata['invoice_reference'])," load_identity = '".$docketdata['reference']."'");
                }
                 $invoicePriceData['base_amount'] += $docketdata['base_amount'];
                 $invoicePriceData['surcharge_total'] += $docketdata['surcharge_total'];
                 $invoicePriceData['fual_surcharge'] += $docketdata['fual_surcharge'];
                 $invoicePriceData['tax'] += $docketdata['tax'];
                 $invoicePriceData['total_ammount'] += $docketdata['total'];
                 $updatestatus = $this->modelObj->editContent('invoices',$invoicePriceData," id = '".$invoiceId."'");
             }
            }
            $voucherAllPrices = array();
            if(isset($val['vouchers']) and count($val['vouchers'])>0){
             foreach($val['vouchers'] as $innerkey=>$innerval){
                 $docketdata = array();

                 $docketdata['invoice_id'] = $invoiceId;
                 $docketdata['invoice_reference'] = $invoicedata['invoice_reference'];
                 $docketdata['company_id'] = $_company_id;
                 $docketdata['reference_id'] = $innerval['reference_id'];
                 $docketdata['reference']   = $innerval['shipment_reference'];
                 $docketdata['invoice_type'] = ($innerval['voucher_type']=='DEBIT')?'DEBITNOTE':'CREDITNOTE';
                 $docketdata['create_date'] = date("Y-m-d");
                 $docketdata['reference1'] = isset($innerval['reference1'])?$innerval['reference1']:'';
                 $docketdata['reference2'] = isset($innerval['reference2'])?$innerval['reference2']:'';
                 $docketdata['items'] = $innerval['items'];
                 $docketdata['chargable_unit']  = $innerval['rate_type'];
                 $docketdata['chargable_value'] = $innerval['chargable_value'];
                 $docketdata['service_name'] = $innerval['service_name'];
                 $docketdata['customer_booking_reference'] = $innerval['voucher_reference'];
                 $docketdata['base_amount'] = $innerval['base_amount'];
                 $docketdata['tax'] = $innerval['tax'];
                 $docketdata['fual_surcharge'] = isset($innerval['fual_surcharge'])?$innerval['fual_surcharge']:0;
                 //$docketdata['surcharge_total'] = ($innerval['surcharge_total']-$docketdata['fual_surcharge']);
                 $docketdata['surcharge_total'] = ($innerval['surcharge_total']);
                 $docketdata['total']    = ($docketdata['base_amount']+$docketdata['surcharge_total']+
                                            $docketdata['fual_surcharge']+$docketdata['tax']);

                 $getDocketOriginandEndPoint = $this->getOriginandDestination($innerval['shipment_reference']);
                 if(!empty($getDocketOriginandEndPoint)){
                  $docketdata['origin'] = isset($getDocketOriginandEndPoint['collection'])?$getDocketOriginandEndPoint['collection']:'';
                  $docketdata['destination'] = isset($getDocketOriginandEndPoint['delivery'])?$getDocketOriginandEndPoint['delivery']:'';
                  $docketdata['collection_date'] = ($getDocketOriginandEndPoint['collection_date']=='0000-00-00')?'1970-01-01':$getDocketOriginandEndPoint['collection_date'];
                 }else{
                  $docketdata['origin'] = '';
                  $docketdata['destination'] = '';
                  $docketdata['collection_date'] = '1970-01-01';
                 }

                $invoicevoucher = $this->modelObj->addContent('invoice_vs_docket',$docketdata);
                if($invoicevoucher){
                  $updatestatus = $this->modelObj->editContent('vouchers',array('is_invoiced'=>'YES','invoice_reference'=>$invoicedata['invoice_reference'])," voucher_reference = '".$innerval['voucher_reference']."'");
                }

                 $voucherAllPrices['base_amount']['credit'][] = ($docketdata['base_amount']<0)?$docketdata['base_amount']:0;
                 $voucherAllPrices['base_amount']['debit'][] =  ($docketdata['base_amount']>0)?$docketdata['base_amount']:0;
                 $voucherAllPrices['surcharge_total']['credit'][] = ($docketdata['surcharge_total']<0)?$docketdata['surcharge_total']:0;
                 $voucherAllPrices['surcharge_total']['debit'][] = ($docketdata['surcharge_total']>0)?$docketdata['surcharge_total']:0;
                 $voucherAllPrices['fual_surcharge']['credit'][] = ($docketdata['fual_surcharge']<0)?$docketdata['fual_surcharge']:0;
                 $voucherAllPrices['fual_surcharge']['debit'][] = ($docketdata['fual_surcharge']>0)?$docketdata['fual_surcharge']:0;
                 $voucherAllPrices['tax']['credit'][] = ($docketdata['tax']<0)?$docketdata['tax']:0;
                 $voucherAllPrices['tax']['debit'][] = ($docketdata['tax']>0)?$docketdata['tax']:0;
                 $voucherAllPrices['total_ammount']['credit'][] = ($docketdata['total']<0)?$docketdata['total']:0;
                 $voucherAllPrices['total_ammount']['debit'][] =   ($docketdata['total']>0)?$docketdata['total']:0;
             }
             if(count($voucherAllPrices)>0){
                 $getInvoiceData = $this->modelObj->getInvoiceData($invoiceId);
                 $totalAmt = array();
                 $totalAmt['voucer'] = array_sum($voucherAllPrices['total_ammount']['credit']) + array_sum($voucherAllPrices['total_ammount']['debit']);
                 $totalAmt['total_ammount'] = $getInvoiceData['total_ammount'] + $totalAmt['voucer'];
                 $totalAmt['voucher_data'] = json_encode($voucherAllPrices);
                 $updatestatus = $this->modelObj->editContent('invoices',$totalAmt," id = '".$invoiceId."'");
             }
            }
         }
      }
       $logo    = $this->modelObj->getCompanyLogo($_company_id);
       $createAndSavePdf = $this->createAllInvoicePdf($allInvoiceRef,$logo['logo'],'');
       $physicalpath = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/outputpdf/';
       if(is_array($createAndSavePdf) and count($createAndSavePdf)>0){
           if((isset($_sendtomail) && isset($_sendtocustomemail) && $_sendtocustomemail !='') || isset($_sendpdftocustomer)){
                    Consignee_Notification::_getInstance()->sendCustomerInvoiceNotification(
                    array('company_id'=>$_company_id,'invoiceId'=>$invoiceRef),$_sendpdftocustomer,$_sendtocustomemail,$physicalpath);
            }
       }
     return array('status'=>true,'message'=>'total '.$invoicecount.' invoice created');
   }
public function getDueDateOfInvoice($customerId,$date){
      $invoicecycle =  $this->modelObj->getCustomerInvoiceCycle($customerId);
       return date('Y-m-d', strtotime($date. ' + '.$invoicecycle.' days'));
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
            $updatestatusVoucher = $this->modelObj->editContent('vouchers',
                                                         array('is_invoiced'=>'NO','invoice_reference'=>'')," invoice_reference = '".$vals."'");
            if($updatestatus){
            $updateinvoicestatus = $this->modelObj->editContent('invoices',
                                                         array('invoice_status'=>'CANCEL'),
                                                         " invoice_reference = '".$vals."'");
            $totalInvoice[] = $vals;
            }
          }
        $logo              = $this->modelObj->getCompanyLogo($param->company_id);
        $createAndSavePdf = $this->createAllInvoicePdf($invoice_array,$logo['logo'],'CANCEL');
       }
       if(count($totalInvoice)>0){
        return array('status'=>true,'message'=>join(',',$totalInvoice).' canceled','reference'=>join(',',$totalInvoice));
    }else{
        return array('status'=>false,'message'=>'your request are not processed');
     }
    }
public function createAllInvoicePdf($invoiceRef,$imageName,$watermark){
         $physicalPath   = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/template/';
         $storagepathPdf = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/outputpdf/';
         $headerContent  = file_get_contents($physicalPath.'invoice-header.html');
         $footerTemplate  = file_get_contents($physicalPath.'invoice-footer.html');
         $bodyContent    = file_get_contents($physicalPath.'invoice-body.html');
         $htmlTemplate   = $headerContent.$bodyContent;
         $pdfRefData = array();
         if(count($invoiceRef)>0){
            foreach($invoiceRef as $ref){
             $pdfdata = $this->modelObj->getAllInviceShip($ref);
             $customerdata =  $this->modelObj->getAllInviceCustomerDetails($ref);
              foreach($customerdata as $keys=>$vals){
               if($vals==""){ $customerdata[$keys]= "N/A";}
              }
             $img_file = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/logo/'.$imageName;
             $imgData = base64_encode(file_get_contents($img_file));
             $src = 'data:'.mime_content_type($img_file).';charset=binary;base64,'.$imgData;
             $html    = $this->prepareHtml($htmlTemplate,$customerdata,$src);
             $html    = $this->getBodyDataHTML($html,$pdfdata);
             $footerContent  = $this->prepareFooterHtml($footerTemplate,$customerdata,$src);
             ob_clean();

             $config = array(
                 'mode' => 'c',
                 'margin_left' => 32,
                 'margin_right' => 25,
                 'margin_top' => 27,
                 'margin_bottom' => 25,
                 'margin_header' => 16,
                 'margin_footer' => 13,
                 'format' => 'A4-L'
             );

             //$pdf = new mPDF('c','A4-L');

             $pdf = new Mpdf\Mpdf($config);


             $pdf->showImageErrors = true;
             $pdf->SetHTMLHeader('<div class="container"> <h3>TAX INVOICE</h3></div>');
             $pdf->SetDisplayMode('fullpage');
             if($watermark!=''){
                 $pdf->SetWatermarkText($watermark);
                 $pdf->watermarkTextAlpha = 0.081;
                 $pdf->watermark_font = 'DejaVuSansCondensed';
                 $pdf->showWatermarkText = true;
             }
             $pdf->writeHTML($html);
             $pdf->SetHTMLHeader('');
             $pdf->SetHTMLFooter('<table width="100%"><tr>
                                     <td width="33%">'.$ref.'</td>
                                    <td width="33%" align="center">{DATE j-m-Y}</td>
                                    <td width="33%" style="text-align: right;">{PAGENO} of {nbpg}</td></tr>
                                </table>');
             $pdf->AddPage();
             $pdf->writeHTML($footerContent);
             $filename = $storagepathPdf.$ref.'.pdf';
             $pdf->Output($filename,'F');
             $pdfRefData[$ref] = $ref.'.pdf';
             $updatestatus = $this->modelObj->editContent('invoices',array('incoice_pdf'=>$ref.'.pdf')," invoice_reference = '".$ref."'");
             }
          }
        return $pdfRefData;
    }
public function prepareHtml($html,$customerdata,$src){
    $return =   str_replace(
         array('__company_logo__','__customername__','__customeraddress1__','__customeraddress2__','__customercity__','__customercountry__','__customerstate__',
              '__customerpostcode__','__company_name__','__customeraccount__','__company_address1__','__company_address2__','__company_postcode__','__company_county__',
              '__company_city__','__customerinvoiceref__','__customerinvoicedate__','__customerinvoiceduedate__','__customeraccount__'),

         array($src,$customerdata['customername'],$customerdata['customeraddress1'],$customerdata['customeraddress2'],$customerdata['customercity'],$customerdata['customercountry'],
               $customerdata['customerstate'],$customerdata['customerpostcode'],$customerdata['company_name'],$customerdata['customeraccount'],$customerdata['company_address1'],
               $customerdata['company_address2'],$customerdata['company_postcode'],$customerdata['company_county'],$customerdata['company_city'],$customerdata['customerinvoiceref'],
               $customerdata['customerinvoicedate'],$customerdata['customerinvoiceduedate'],$customerdata['customeraccount']),
         $html);
    return $return;
}
public function prepareFooterHtml($footerContent,$customerdata,$src)  {
      $return =   str_replace(
         array('__company_logo__','__baseprice__','__surcharge__','__fualsurcharge__','__tax__','__voucher__','__total__'),
         array($src,$customerdata['baseprice'],$customerdata['surcharge'],$customerdata['fualsurcharge'],$customerdata['tax'],$customerdata['voucher'],$customerdata['total']),
         $footerContent);
       return $return;

 }
public function getBodyDataHTML($html,$pdfdata){

    $columnName = '<tr><th>Consignment</th>
                  <th>Type</th>
                  <th>Collection</th>
                  <th>Origin</th>
                  <th>Destination</th>
                  <th>Items</th>
                  <th>Charge unit</th>
                  <th>Service</th>
                  <th>Reference</th>
                  <th>Freight</th>
                  <th>Surcharges</th>
                  <th>Fuel</th>
                  <th>VAT</th>
                  <th>Ref 1</th>
                  <th>Ref 2</th>
                  <th>Total</th></tr>';


    $htmlData = '';
    if(count($pdfdata)>0){
        foreach($pdfdata as $val){
            $htmlData.= '<tr><td>'.$val['reference'].'</td>
                              <td>'.$val['invoice_type'].'</td>
                              <td>'.$val['collection_date'].'</td>
                              <td>'.$val['origin'].'</td>
                              <td>'.$val['destination'].'</td>
                              <td>'.$val['items'].'</td>
                              <td>'.$val['chargable_value'].'</td>
                              <td>'.$val['service_name'].'</td>
                              <td>'.$val['customer_booking_reference'].'</td>
                              <td>'.$val['base_amount'].'</td>
                              <td>'.$val['surcharge_total'].'</td>
                              <td>'.$val['fual_surcharge'].'</td>
                              <td>'.$val['tax'].'</td>
                              <td>'.$val['reference1'].'</td>
                              <td>'.$val['reference2'].'</td>
                              <td>'.$val['total'].'</td>
                          </tr>';
        }
    }
    $return =   str_replace(
         array('__header_column__','__body_data__'),array($columnName,$htmlData),$html);
    return $return;
   }
public function payinvoices($param){
    $this->db->startTransaction();
    $checkInvoiceStatus =  $this->modelObj->getInvoiceStatus($param->invoice_reference);
    $invoiceStatus      = $checkInvoiceStatus['invoice_status'];
    if(($invoiceStatus != 'PAID') && ($invoiceStatus != 'CANCEL')){
    $data = array();
    $data['invoice_reference']      = $param->invoice_reference;
    $data['invoice_amt']            = $param->total_amount;
    $data['paid_amt']               = $param->payamount;
    $data['paydate']                = $param->paydate;
    $data['customer_account']       = $param->shipment_customer_account;
    $data['last_invoice_status']    = $param->status;
    $data['paymode']                = $param->paymode;
    $data['payment_reference']      = isset($param->payment_reference)?$param->payment_reference:'';
    $status                         = $this->modelObj->addContent('invoice_payment',$data);
    if($status){
         $updatestatus = $this->modelObj->editContent('invoices',
            array('invoice_status'=>'PAID')," invoice_reference = '".$param->invoice_reference."' AND invoice_status != 'CANCEL'");
         if($updatestatus){
              $datastatus =  $this->_manageAccounts($param->customer_id,$param->company_id,$param->payamount,$param->invoice_reference,'PAYINVOICE','INVOICE PAYMENT');
              if($datastatus['status']=='success'){
                  $this->db->commitTransaction();
                   return array('status'=>true,'message'=>$param->invoice_reference.' Paid','reference'=>$param->invoice_reference, 'available_credit' => $datastatus['available_credit']);
              }else{
                  $this->db->rollBackTransaction();
                  return array('status'=>"false",'message'=>'fail','code'=>'SERR11');
              }
         }else{
             $this->db->rollBackTransaction();
             return array('status'=>"false",'message'=>'fail','code'=>'SERR12');
         }
    }
    else{
        $this->db->rollBackTransaction();
        return array('status'=>"false",'message'=>'fail','code'=>'SERR13');
     }
  }else{
        $this->db->rollBackTransaction();
        return array('status'=>"false",'message'=>'already paid','code'=>'SERR14');
    }
}
public function _manageAccounts($customer_id,$company_id,$amount,$invoiceRef,$payfor,$paydesc){
     $customerData = $this->modelObj->getCustomerAccount($customer_id);
     $creditbalanceData = array();
     $creditbalanceData['customer_id']          = $customer_id;
     $creditbalanceData['customer_type']        = $customerData['customer_type'];
     $creditbalanceData['company_id']           = $company_id;
     $creditbalanceData['payment_type']         = 'CREDIT';
     $creditbalanceData['pre_balance']          = $customerData["available_credit"];
     $creditbalanceData['amount']               = $amount;
     $creditbalanceData['balance']              = $customerData["available_credit"] + $amount;
     $creditbalanceData['create_date']          = date("Y-m-d");
     $creditbalanceData['payment_reference']    = $invoiceRef;
     $creditbalanceData['payment_desc']         = $paydesc;
     $creditbalanceData['payment_provider']     = $paydesc;
     $creditbalanceData['payment_for']          = $payfor;
     $addHistory                                = $this->modelObj->addContent('accountbalancehistory',$creditbalanceData);
     if($addHistory){
         $condition     = "user_id = '".$customer_id."'";
         $updatestatus  = $this->modelObj->editContent('customer_info',array('available_credit'=>$creditbalanceData['balance']),$condition);
         if($updatestatus){
              return array("status"=>"success", "message"=>"Invoice paid", 'available_credit'=>$creditbalanceData['balance']);
          }
      }
      return array("status"=>"error", "message"=>"payment not saved");
    }
public function loadPostpaidCustomer($company_id){
    $return = array();
    $shipmentsData = $this->modelObj->getPostpaidCustomer($company_id);
    return $shipmentsData;
   }
public function loadPrepaidCustomer($company_id){
    $return = array();
    $shipmentsData = $this->modelObj->getPrepaidCustomer($company_id);
    return $shipmentsData;
   }
public function prepaidrecharge($param){
      $this->db->startTransaction();
      $param->payment_reference = isset($param->payment_reference)?$param->payment_reference:'000000';
      $datastatus =  $this->_manageAccounts($param->customer,$param->company_id,$param->payamount,$param->payment_reference,'RECHARGE',$param->paymode);
      if($datastatus['status']=='success'){
          $this->db->commitTransaction();
           return array('status'=>true,'message'=>"Account recharged successfully.", 'available_credit'=>$datastatus['available_credit']);
      }else{
          $this->db->rollBackTransaction();
          return array('status'=>"false",'message'=>'fail','code'=>'SERR11');
      }
   }
public function checkInvoiceNumber($param){
        $shipmentsData = $this->modelObj->checkInvoiceNumberUnpaid($param);
        return array('status' => ($shipmentsData) ? 'success' : 'error', 'data'=>$shipmentsData);
        return $shipmentsData;
   }
public function loadAllCustomers($company_id){
    $return = array();
    $shipmentsData = $this->modelObj->getAllCustomers($company_id);
    return $shipmentsData;
   }    
    
    
}
?>