<?php

//Include Library
include('postcodeanywhere.php');

Class Address_Lookup{
    
    public
    
    function lookup($string,$countryCode='')
    {
        //Set Licence and Account Code
        $postcodeObj = new Postcode();
		if($countryCode=='')
			$countryCode = 'GB';//UK
        $string = $postcodeObj->validate($string,$countryCode);
        //if($string!=''){
		if(count($string)>0){
			$oPostcode = new interactiveFindByPostcode();//interactiveRetrieveByAddress();
        
			$oPostcode->setLicenceKey('JJ36-KU94-DC22-FN63');
			$oPostcode->setAccountCode('FIVEW11112');
			
			/*$oPostcode->setLicenceKey('YF51-PX61-JG65-FK14');
			$oPostcode->setAccountCode('KUBER11111');*/
			
			//Set Language (Not needed, english is the default)
			$oPostcode->setLanguage('English');
			//Set the company were looking for and address
			//$oPostcode->setCompany('Enevis Ltd');
			//$oPostcode->setAddress('Twistleton Court, DA1 2EN');
			//$oPostcode->setAddress($string);
			
			//$oPostcode->setPostcode($string);
			$output = $oPostcode->setPostcode($string[0],$string[1]);//postcode and country code in parameters 1,2
			/*****new parameters for updated api of postcodeanywhere*****/
			$Key = 'JJ36-KU94-DC22-FN63';
			$Text = $output->sPostcode;
			$Container = "";
			$Origin = "";
			$Countries = ($countryCode=='UK') ? "GB" : $countryCode;
			$Limit = "";
			$Language = 'English';
			$pa = new Capture_Interactive_Find_v1_00($Key, $Text, $Container, $Origin, $Countries, $Limit, $Language);
			$pa->MakeRequest();
			if ($pa->HasData())
			{
			$data = $pa->HasData();
			$aData = array();
			foreach ($data as $item)
				{
					$aData[] = array('id' => $item["Id"], 'street' => $item["Text"], 'place' => $item["Description"]);
					/* 
					echo $item["Id"] . "->Id<br>";
					echo $item["Type"] . "->Type<br>";
					echo $item["Text"] . "->Text<br>";
					echo $item["Highlight"] . "->Highlight<br>";
					echo $item["Description"] . "->Description<br>";
					echo "<br><br>########################################<br><br>"; */
				}
			return array("status"=>"success", "data"=>$aData);	
			}
			else {
				//Output results
				return array("status"=>"error", "message"=>"no address found");
			}
			/* die;
			if (!$oPostcode->run()) {
				//Ensure there isn't any errors
				return array("status"=>"error", "message"=>$oPostcode->sErrorMessage);
			} else {
				//Output results
				return array("status"=>"success", "data"=>$oPostcode->aData);
			} */
		}else{
			return array("status"=>"error", "message"=>"invalid postcode");
		}
        
    }
    
    /* public
    
    function lookupByID($string)
    {
        //Set Licence and Account Code
        $oPostcode = new interactiveRetrieveByID();
        
        $oPostcode->setLicenceKey('JJ36-KU94-DC22-FN63');
        $oPostcode->setAccountCode('FIVEW11112');
        //Set Language (Not needed, english is the default)
        $oPostcode->setLanguage('English');
        
        $oPostcode->setAddressID($string);
        if (!$oPostcode->run()) {
            //Ensure there isn't any errors
            return array("status"=>"error", "message"=>$oPostcode->sErrorMessage);
        } else {
            //Output results
            return array("status"=>"success", "data"=>$oPostcode->aData);
        }
    } */
	
	public
    
    function lookupByID($string)
    {
		$Key = 'JJ36-KU94-DC22-FN63';
		$addressId = $string;
		$Language = 'English';
		
		$pa = new Capture_Interactive_Retrieve_v1_00 ($Key,$addressId,"","","","","","","","","","","","","","","","","","","","");
        $pa->MakeRequest();
        if($pa->HasData()){
			$data = $pa->HasData();
			foreach ($data as $item)
			{
				$aData[] = array(
					'udprn' => (int) $item["Id"],
					'company' => (string) $item["Company"],
					'department' => (string) $item["Department"],
					'line1' => (string) $item["Line1"],
					'line2' => (string) $item["Line2"],
					'line3' => (string) $item["Line3"],
					'line4' => (string) $item["Line4"],
					'line5' => (string) $item["Line5"],
					'posttown' => (string) $item["Line5"],
					'county' => (string) $item["Province"],
					'postcode' => (string) $item["PostalCode"],
					'mailsort' => (int) $item["SortingNumber1"],
					'barcode' => (string) $item["Barcode"],
					'type' => (string) $item["Type"],
					'delivery_point_suffix' => (string) $item["Field7"],
					'sub_building' => (string) $item["SubBuilding"],
					'building_name' => (string) $item["BuildingName"],
					'building_number' => (string) $item["BuildingNumber"],
					'primary_street' => (string) $item["Street"],
					'secondary_street' => (string) $item["SecondaryStreet"],
					'double_dependent_locality' => (string) $item["Field6"],
					'dependent_locality' => (string) $item["Field5"],
					'pobox' => (string) $item["ProvinceCode"],
					'primary_street_name' => (string) $item["Field1"],
					'primary_street_type' => (string) $item["Field2"],
					'secondary_street_name' => (string) $item["Field3"],
					'secondary_street_type' => (string) $item["Field4"],
					'country_name' => (string) $item["CountryName"],
					'country_iso2' => (string) $item["CountryIso2"],
					'country_iso3' => (string) $item["CountryIso3"]
				);
			}
			return array("status"=>"success", "data"=>$aData);
		}else {
            //error message
            return array("status"=>"error", "message"=>"no data found");
        }
	} 
}
?>
