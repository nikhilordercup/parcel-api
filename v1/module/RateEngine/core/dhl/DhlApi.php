<?php
namespace v1\module\RateEngine\core\dhl;


class DhlApi extends DhlApiBase
{    
    public static function initRoutes($app){               
        $app->post('/dhl-pickup', function() use ($app) {                          
            $dhlApiObj = new DhlApi();
            //////////// RAW DATA  START ////////////////////                                    
            $rawData = array();
            $rawData['credentials'] = array(
                'username' => 'kuberusinfos',
                'password' => 'GgfrBytVDz',
                'third_party_account_number' => '',
                'account_number' => '420714888',
                'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJlbWFpbCI6InNtYXJnZXNoQGdtYWlsLmNvbSIsImlzcyI6Ik9yZGVyQ3VwIG9yIGh0dHBzOi8vd3d3Lm9yZGVyY3VwLmNvbS8iLCJpYXQiOjE1MDI4MjQ3NTJ9.qGTEGgThFE4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0'
            );
            $rawData['carrier'] = 'dhl';
            $rawData['services'] = '';
            $rawData['address'] = array(
                'location_type' => 'B',
                'package_location' => 'Front Desk',
                'company' => 'Perceptive Consulting',
                'street1' => '6-12 Barkston Gardens',
                'street2' => 'Kensington',
                'city' => 'London',
                'state' => 'oxford',
                'country' => 'GB',
                'zip' => 'SW5 0EN'
            );
            $rawData['pickup_details'] = array(
                'pickup_date' => '2019-01-25',
                'ready_time' => '17:00',
                'close_time' => '19:00',
                'number_of_pieces' => '1',
                'instructions' => ' this is test',
                'type_codes' => 'A'
            );
            $rawData['pickup_contact'] = array(
                'name' => 'Nikhil Kumar',
                'phone' => '8470873414',
                'email' => 'rajesh.k@perceptive-solutions.com'
            );

            $rawData['confirmation_number'] = '';
            $rawData['method_type'] = 'post';
            $rawData['pickup'] = '';
             //////////// RAW DATA  END ////////////////////  
        
            $payload = $dhlApiObj->formatPickupData($rawData);
            $xmlRequest = $dhlApiObj->getPickupRequest($payload);            
            $resultData = $dhlApiObj->postDataToDhl($xmlRequest);
            $this->formatPickupResponseData($resultData);                                                                      
        });
        
        $app->post('/pickupcancel', function() use ($app) {             
            $r = json_decode($app->request->getBody());                                                 
            $dhlApiObj = new DhlApi();
            
            $rawData = array();
            $rawData['credentials'] = array(
                'username' => 'kuberusinfos',
                'password' => 'GgfrBytVDz',
                'third_party_account_number' => '',
                'account_number' => '420714888',
                'token' => '4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0'
            );
            $rawData['carrier'] = 'dhl';
            $rawData['ConfirmationNumber'] = 'PRG190204000141';
            $rawData['CountryCode'] = 'GB';
            $rawData['PickupDate'] = '2019-02-05';
            $rawData['CancelTime'] = date("H:i");
            $rawData['Reason'] = '002';
            $rawData['OriginSvcArea'] = '';
            $rawData['RegionCode'] = 'EU';
            $rawData['RequestorName'] = 'Kavita'; 
                        
            $payload = $dhlApiObj->formatPickupCancelationReq($rawData); 
            $xmlRequest = $dhlApiObj->getPickupCacelationRequest($payload);                      
            $resultData = $dhlApiObj->postDataToDhl($xmlRequest);
            $resultData = $dhlApiObj->formatPickupCancelationResponseData($resultData);             
            print_r($resultData);die;                                                           
        });
        
        
    }
    
    public function getPickupRequest($pickupData)
    {
        $xmlRequest = '<?xml version="1.0" encoding="UTF-8"?><req:BookPURequest xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com pickup-global-req.xsd" schemaVersion="3.0">
            <Request>
              <ServiceHeader>
                <MessageTime>'.$pickupData->MessageTime.'</MessageTime>
                <MessageReference>'.$pickupData->MessageReference.'</MessageReference>
                <SiteID>'.$pickupData->SiteID.'</SiteID>
                <Password>'.$pickupData->Password.'</Password>
              </ServiceHeader>
              <MetaData>
                <SoftwareName>XMLPI</SoftwareName>
                <SoftwareVersion>2.0</SoftwareVersion>
              </MetaData>
            </Request>
            <RegionCode>'.$pickupData->RegionCode.'</RegionCode>
            <Requestor>
              <AccountType>'.$pickupData->AccountType.'</AccountType>
              <AccountNumber>'.$pickupData->AccountNumber.'</AccountNumber>
              <RequestorContact>
                <PersonName></PersonName>
                <Phone></Phone>
              </RequestorContact>
              <CompanyName>'.$pickupData->CompanyName.'</CompanyName>
              <Address1>'.$pickupData->Address1.'</Address1>
              <City>'.$pickupData->City.'</City>
              <CountryCode>'.$pickupData->CountryCode.'</CountryCode>
            </Requestor>
            <Place>
              <LocationType>'.$pickupData->LocationType.'</LocationType>
              <CompanyName>'.$pickupData->CompanyName.'</CompanyName>
              <Address1>'.$pickupData->Address1.'</Address1>
              <Address2>'.$pickupData->Address2.'</Address2>
              <PackageLocation>'.$pickupData->PackageLocation.'</PackageLocation>
              <City>'.$pickupData->City.'</City>
              <DivisionName>'.$pickupData->DivisionName.'</DivisionName>
              <CountryCode>'.$pickupData->CountryCode.'</CountryCode>
              <PostalCode>'.$pickupData->PostalCode.'</PostalCode>
            </Place>
            <Pickup>
              <PickupDate>'.$pickupData->PickupDate.'</PickupDate>
              <PickupTypeCode>'.$pickupData->PickupTypeCode.'</PickupTypeCode>
              <ReadyByTime>'.$pickupData->ReadyByTime.'</ReadyByTime>
              <CloseTime>'.$pickupData->CloseTime.'</CloseTime>
              <Pieces>'.$pickupData->Pieces.'</Pieces>
              <RemotePickupFlag>'.$pickupData->RemotePickupFlag.'</RemotePickupFlag>
            </Pickup>
            <PickupContact>
              <PersonName>'.$pickupData->PersonName.'</PersonName>
              <Phone>'.$pickupData->Phone.'</Phone>
            </PickupContact>
          </req:BookPURequest>
          ';    // echo $xmlRequest;die;
        return $xmlRequest;
    }
    
    public function formatPickupData($rawData)
    {
        $payload = new \stdClass();
        $payload->MessageTime = date("Y-m-d").'T'.date("H:i:s");
        $payload->MessageReference = 'BookPickupGL_EU______________';
        $payload->SiteID = $rawData['credentials']['username'];
        $payload->Password = $rawData['credentials']['password'];
        $payload->RegionCode = 'EU';
        $payload->AccountType = 'D';
        $payload->AccountNumber = $rawData['credentials']['account_number'];
        $payload->PersonName = $rawData['pickup_contact']['name'];
        $payload->Phone = $rawData['pickup_contact']['name'];
        $payload->CompanyName = $rawData['address']['company'];
        $payload->Address1 = $rawData['address']['street1'];
        $payload->City = $rawData['address']['city'];
        $payload->CountryCode = $rawData['address']['country'];
        $payload->LocationType = $rawData['address']['location_type'];
        $payload->PostalCode = $rawData['address']['zip'];
        $payload->Address2 = $rawData['address']['street2']; 
        $payload->PackageLocation = $rawData['address']['package_location'];
        $payload->DivisionName = ''; 
        $payload->PickupDate = $rawData['pickup_details']['pickup_date'];
        $payload->PickupTypeCode = $rawData['pickup_details']['type_codes']; 
        $payload->ReadyByTime = $rawData['pickup_details']['ready_time'];
        $payload->CloseTime = $rawData['pickup_details']['close_time']; 
        $payload->Pieces = $rawData['pickup_details']['number_of_pieces'];
        $payload->RemotePickupFlag = 'N';
        return $payload;
    }
    
    public function formatPickupResponseData($rawResponseData)
    {                
        $finalResponse = new \stdClass();                        
        $rawResponseObj = json_decode($rawResponseData);         
        if(isset($rawResponseObj->Note) && $rawResponseObj->Note->ActionNote == 'Success')
        {
            $finalResponse->pickup = new \stdClass();
            $finalResponse->pickup->confirmation_number = $rawResponseObj->ConfirmationNumber;
            $finalResponse->pickup->charge = isset($rawResponseObj->PickupCharge) ? $rawResponseObj->PickupCharge : ''; 
            $finalResponse->pickup->currency_code = (isset($rawResponseObj->CurrencyCode)) ? $rawResponseObj->CurrencyCode : '';
            $finalResponse->pickup->origin_service_area = (isset($rawResponseObj->OriginSvcArea)) ?  $rawResponseObj->OriginSvcArea :'';
            $finalResponse->pickup->ready_time = isset($rawResponseObj->ReadyByTime) ? $rawResponseObj->ReadyByTime :'';
            $finalResponse->pickup->next_date =  isset($rawResponseObj->NextPickupDate) ? $rawResponseObj->NextPickupDate : '';
            $finalResponse->pickup->second_time = '';  
        }
        else 
        { 
            $finalResponse->timestamp = $rawResponseObj->Response->ServiceHeader->MessageTime;                        
            $finalResponse->error = $rawResponseObj->Response->Status->Condition->ConditionData;                        
        }                      
        return $finalResponse;                        
    } 
    
    public function getPickupCacelationRequest($reqData)
    {
        $reqData->MessageTime = date("Y-m-d").'T'.date("H:i:s");
        $reqData->RegionCode = 'EU';
        
        $pickupCancelationReq = '<?xml version="1.0" encoding="UTF-8"?>
        <req:CancelPURequest xmlns:req="http://www.dhl.com" 
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:schemaLocation="http://www.dhl.com cancel-pickup-global-req_EA.xsd" 
        schemaVersion="3.0">
         <Request>
            <ServiceHeader>
                <MessageTime>'.$reqData->MessageTime.'</MessageTime>
                <MessageReference>1111111111222222222211111111</MessageReference>
                <SiteID>'.$reqData->SiteID.'</SiteID>
                <Password>'.$reqData->Password.'</Password>
            </ServiceHeader> 
                     <MetaData>
                        <SoftwareName>XMLPI</SoftwareName>
                        <SoftwareVersion>2.0</SoftwareVersion>
                      </MetaData>     
         </Request>
         <RegionCode>'.$reqData->RegionCode.'</RegionCode>	
         <ConfirmationNumber>'.$reqData->ConfirmationNumber.'</ConfirmationNumber>
         <RequestorName>'.$reqData->RequestorName.'</RequestorName>
         <CountryCode>'.$reqData->CountryCode.'</CountryCode>
        <OriginSvcArea>111</OriginSvcArea>
         <Reason>'.$reqData->Reason.'</Reason>	
         <PickupDate>'.$reqData->PickupDate.'</PickupDate>
         <CancelTime>'.$reqData->CancelTime.'</CancelTime>
        </req:CancelPURequest>';                        
        return $pickupCancelationReq;        
    }
    
    public function formatPickupCancelationReq($rawData)
    {                                               
        $payload = new \stdClass();
        $payload->SiteID = $rawData['credentials']['username'];
        $payload->Password = $rawData['credentials']['password'];
        $payload->ConfirmationNumber = $rawData['ConfirmationNumber'];        
        $payload->PickupDate = $rawData['PickupDate'];
        $payload->CancelTime = $rawData['CancelTime'];
        $payload->RequestorName = $rawData['RequestorName'];
        $payload->CountryCode = $rawData['CountryCode'];        
        $payload->Reason = $rawData['Reason'];        
        return $payload;
    }
    
    public function formatPickupCancelationResponseData($rawResponseData)
    {                
        $finalResponse = new \stdClass();                                     
        if(isset($rawResponseData['Note']) && $rawResponseData['Note']['ActionNote'] == 'Success')
        {
            $finalResponse->status = 'Success';
            $finalResponse->confirmation_number = $rawResponseData['ConfirmationNumber'];            
        }
        else 
        { 
            $finalResponse->status = 'Error';                                          
            $finalResponse->error = $rawResponseData['Response']['Status']['Condition']['ConditionData'];                        
        }
        return $finalResponse;
    } 
    
}
