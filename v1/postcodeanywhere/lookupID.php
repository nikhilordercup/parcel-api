<?php
/*
//Include Library
include('postcodeanywhere.php');

//Set Licence and Account Code
$oPostcode = new interactiveRetrieveByID();
$oPostcode->setLicenceKey('JJ36-KU94-DC22-FN63');
$oPostcode->setAccountCode('FIVEW11112');

//Set Language (Not needed, english is the default)
$oPostcode->setLanguage('English');

//Set the company were looking for and address
$oPostcode->setAddressID(6195115);

if (!$oPostcode->run()) {
	//Ensure there isn't any errors
	var_dump($oPostcode->sErrorMessage);
} else {
	//Output results
	var_dump($oPostcode->aData);
}
*/
?>

<?php
    
    //Include Library
    include('postcodeanywhere.php');
    
    Class Address_LookupID{
        
        public
        
        function lookup($string)
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

