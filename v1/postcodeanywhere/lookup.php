<?php

//Include Library
include('postcodeanywhere.php');

Class Address_Lookup{
    
    public
    
    function lookup($string)
    {
        //Set Licence and Account Code
        $postcodeObj = new Postcode();
        $string = $postcodeObj->validate($string);
		if($string!=''){
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

			$oPostcode->setPostcode($string);
			
			if (!$oPostcode->run()) {
				//Ensure there isn't any errors
				return array("status"=>"error", "message"=>$oPostcode->sErrorMessage);
			} else {
				//Output results
				return array("status"=>"success", "data"=>$oPostcode->aData);
			}
		}else{
			return array("status"=>"error", "message"=>"invalid postcode");
		}
        
    }
    
    public
    
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
    }
}
?>
