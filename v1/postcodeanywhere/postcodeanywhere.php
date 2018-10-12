<?php

/**
 * @author Ben Squire
 * @copyright 2012
 * @name Postcode Anywhere PHP API
 * @example $sPostcodeanywhere->setFilter('OnlyResidential')->setLanguage('English')->addressFromPostcode('LE18 2RF');
 */
abstract class postcodeAnywhere {

	protected $aPossibleLanguages = array('english', 'welsh');
	protected $aPossibleFilters = array('None', 'OnlyResidential', 'OnlyCommercial');
	public $sLicenceKey = '';
	public $sAccountCode = '';
	public $sLanguage = 'English';
	public $sUsername = null;
	public $aData = array();
	public $iErrorID = null;
	public $sErrorMessage = null;
	public $sPostcode = '';


	protected $iTop;  //The maximum number of rows to return.
	protected $sOrderBy; //A list of columns to order the results by.
	protected $sFilter = 'None';  //A SQL-style WHERE filter to apply to the result. Name LIKE 'a%'
	protected $iPageNumber; //Integer	Returns the relevant page from the results, 1 being the first. Must be used in conjunction with $PageSize.5
	protected $iPageSize; //Selects the appropriate results per page size to use.
	protected $bUseHTTPs = false;
	protected $sUrl = null;
	protected $aUrl = array();

	public function __construct() {

	}

	protected abstract function run();

	/**
	 * Set the required language of the postcode request
	 *
	 * @param string $sLanguage The language we're searching in (Welsh or English)
	 */
	public function setLanguage($sLanguage) {
		$sLanguage = strtolower($sLanguage);

		if (!in_array($sLanguage, $this->aPossibleLanguages)) {
			throw new Exception('Invalid Requested Language');
		}

		$this->sLanguage = $sLanguage;
		return $this;
	}

	/**
	 * Set the username of the postcodeanywhere account
	 *
	 * @param string $sUsername The username of the account
     *
	 * @return postcodeanywhere
	 */
	public function setUsername($sUsername = null) {
		if (!is_string($sUsername) || strlen($sUsername) === 0) {
			throw new Exception('Invalid username, string or null');
		}

		$this->sUsername = $sUsername;
		return $this;
	}

	/**
	 * Set the filter for our returned results.
	 *
	 * @param string $sFilter The search filter to apply (Not fully implemented yet)
     *
	 * @return postcodeanywhere
	 */
	public function setFilter($sFilter = null) {
		if (!in_array($sFilter, $this->aPossibleFilters)) {
			throw new Exception('Invalid requestd filter');
		}

		$this->sFilter = $sFilter;
		return $this;
	}

	/**
	 * Sets the key for the request
	 *
	 * @param string $sKey The licence key of your account

	 * @return postcodeanywhere
	 */
	public function setLicenceKey($sKey) {
		if (strlen($sKey) === 0) {
			throw new Exception('Invalid api key');
		}

		$this->sLicenceKey = $sKey;
		return $this;
	}

	/**
	 * Set the postcodenywhere account code.
	 *
	 * @param string $sAccountCode The account code
     *
	 * @return postcodeanywhere
	 */
	public function setAccountCode($sAccountCode) {
		if (strlen($sAccountCode) === 0) {
			throw new Exception('Invalid Account Code');
		}

		$this->sAccountCode = $sAccountCode;
		return $this;
	}

	/**
	 * Set the house name and number we'll be searching on
	 *
	 * @param string $sPostcode The postcode to search for
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setPostcode($sPostcode = null,$regEx) {
        $oPostcode = new Pca_Postcode($regEx);
		if (!$oPostcode->isValid($sPostcode)) {
			throw new Exception('Invalid Postcode');
		}

		$this->sPostcode = $oPostcode->cleanPostcode($sPostcode);
		return $this;
	}

	/**
	 * Set the house name/number we'll be searching for
	 *
	 * @param string $sHouseNameNumber The house name/number to search for
     *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setHouseNameNumber($sHouseNameNumber) {
		if (strlen($sHouseNameNumber) === 0) {
			throw new Exception('Invalid House Name/Number');
		}

		$this->sHouseNameNumber = $sHouseNameNumber;
		return $this;
	}

	/**
	 * Sets the address ID we'll be looking up.
	 *
	 * @param int $iAddressID The address ID to look up
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setAddressID($iAddressID) {
		/* if (!is_int($iAddressID)) {
			throw new Exception('Invalid Address ID');
		}
		$this->iAddressID = (int) $iAddressID; */
		$this->iAddressID = $iAddressID;
		return $this;
	}

	/**
	 * Determines whether we access the service using SSL or not
	 *
	 * @param boolean $bUseHTTPs True or False
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setUseHTTPs($bUseHTTPs = false) {
		if (!is_bool($bUseHTTPs)) {
			throw new Exception('Invalid Use HTTPs value supplied');
		}

		$this->bUseHTTPs = (bool) $bUseHTTPs;
		return $this;
	}

	/**
	 * Sets the object error id and message
	 *
	 * @param string $sMessage
     *
	 * @return postcodeanywhere
	 */
	protected function setError($sMessage) {
		$this->sErrorMessage = $sMessage;
		return $this;
	}

	/**
	 * Sets the currently returned data
     *
	 * @param array $aData The data returned from PCA
     *
	 * @return postcodeanywhere
	 */
	protected function setData($aData) {
		$this->aData = $aData;
		return $this;
	}

	/**
	 * Get the current objects error information
	 *
	 * @return array
	 */
	public function getError() {
		return $this->sErrorMessage;
	}

	/**
	 * Returns the current array of postcode data.
	 *
	 * @return array
	 */
	public function getData() {
		return $this->aData;
	}

	/**
	 * Match the address ID with that stored in the object data variable
	 *
	 * @param int $iMatchID Compare the data array IDs against the required ID
     *
	 * @return mixed boolean|array
	 */
	public function matchAddressID($iMatchID) {
		foreach ($this->aData as $aAddressItem) {
			if ((isset($aAddressItem['id']) && $aAddressItem['id'] == $iMatchID)) {
				return $aAddressItem;
			}
		}

		return false;
	}

	/**
	 * Sends the GET request to PostcodeAnywhere
	 *
	 * @param string $sUrl The URL to retrieve the PCA results from
     *
	 * @return boolean
	 * @throws Exception
	 */
	protected function fetchXML($sUrl) {
		if (strlen($sUrl) === 0) {
			throw new Exception('Invalid URL');
		}

		libxml_clear_errors();
		libxml_use_internal_errors(true);

		$oXML = @simplexml_load_file($sUrl);
		$aErrors = libxml_get_errors();
		libxml_use_internal_errors(false);

		if (count($aErrors) > 0) {
			$aError = array_shift($aErrors);
			$this->sErrorMessage = $aError->message;
			return false;
		}

		return $oXML;
	}

	/**
	 * Build the URL to send the address request
	 *
	 * @return string
	 */
	protected function buildUrl() {
		$this->aUrl['Key'] = $this->sLicenceKey;
		$this->aUrl['UserName'] = $this->sUsername;
		$this->aUrl['PreferredLanguage'] = $this->sLanguage;

		//$this->aUrl['$Top']
		//$this->aUrl['$Orderby']
		//$this->aUrl['$Filter']
		//$this->aUrl['$PageNumber']
		//$this->aUrl['$PageSize']
		//Make the request to Postcode Anywhere and parse the XML returned
		$aUrl = [];
		foreach ($this->aUrl AS $sKey => $sValue) {
			$sValue = trim($sValue);

			if (strlen($sValue) > 0) {
				$aUrl[] = $sKey . '=' . urlencode($sValue);
			}
		}

		return 'http' . ($this->bUseHTTPs ? 's' : '') . '://' . $this->sUrl . '?' . implode('&', $aUrl);
	}

}

/**
 * Interactive Find v1.10
 * Lists address records matching the specified search term. This general search method can search by postcode, company or street.
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/Find/v1.1/default.aspx
 */
class interactiveFind extends postcodeanywhere {

	protected $sSearchTerm = null;
	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/Find/v1.10/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Sets the searchterm the interactive find will look for
	 *
	 * @param string $sSearchTerm The term to search on
	 *
	 * @return \interactiveFind
	 */
	public function setSearchTerm($sSearchTerm) {
		$this->sSearchTerm = $sSearchTerm;
		return $this;
	}

	/**
	 * Fetches an address based on a ad-hoc string (Free?)
	 * http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/Find/v1.1/default.aspx
	 *
	 * @return boolean
	 */
	public function run() {
		//Standard URL Parameters
		$this->aUrl = array();

		//Specific
		$this->aUrl['Filter'] = $this->sFilter;
		$this->aUrl['SearchTerm'] = $this->sSearchTerm;

		//Make the request
		$oXML = $this->fetchXML($this->buildUrl());

		if (!$oXML) {
			return false;
		}

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		$aData = array();
		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array('id' => (float) $item->attributes()->Id, 'street' => (string) $item->attributes()->StreetAddress, 'place' => (string) $item->attributes()->Place);
		}
		$this->setData($aData);
		return true;
	}

}

/**
 * Find an address using a UK postcode
 *
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/FindByPostcode/v1/default.aspx
 */
class interactiveFindByPostcode extends postcodeanywhere {

	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/FindByPostcode/v1.00/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Fetch possible address based on the postcode (Free?)
	 *
	 * @return boolean
	 */
	public function run() {
		if (strlen($this->sPostcode) === 0) {
			throw new Exception('Invalid Postcode.');
		}

		//Build URL
		$this->aUrl = array();
		$this->aUrl['Postcode'] = $this->sPostcode;
		//Make the request
		//Make the request to Postcode Anywhere and parse the XML returned
		$oXML = $this->fetchXML($this->buildUrl());
		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		$aData = array();
		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array('id' => (float) $item->attributes()->Id, 'street' => (string) $item->attributes()->StreetAddress, 'place' => (string) $item->attributes()->Place);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 * Interactive Retrieve By Id v1.30
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/RetrieveById/v1.3/default.aspx
 */
class interactiveRetrieveByID extends postcodeanywhere {

	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/RetrieveById/v1.20/xmla.ws';
	protected $iAddressID = null;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieves address information based on the postcodeanywhere id (Not Free)
	 *
	 * @return boolean
	 */
	public function run() {
		if (strlen($this->iAddressID) === 0) {
			throw new Exception('No Address ID set');
		}

		//Required.
		$this->aUrl = array();
		$this->aUrl['Id'] = (int) $this->iAddressID;
		$oXML = $this->fetchXML($this->buildUrl());

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array(
				'udprn' => (int) $item->attributes()->Udprn,
				'company' => (string) $item->attributes()->Company,
				'department' => (string) $item->attributes()->Department,
				'line1' => (string) $item->attributes()->Line1,
				'line2' => (string) $item->attributes()->Line2,
				'line3' => (string) $item->attributes()->Line3,
				'line4' => (string) $item->attributes()->Line4,
				'line5' => (string) $item->attributes()->Line5,
				'posttown' => (string) $item->attributes()->PostTown,
				'county' => (string) $item->attributes()->County,
				'postcode' => (string) $item->attributes()->Postcode,
				'mailsort' => (int) $item->attributes()->Mailsort,
				'barcode' => (string) $item->attributes()->Barcode,
				'type' => (string) $item->attributes()->Type,
				'delivery_point_suffix' => (string) $item->attributes()->DeliveryPointSuffix,
				'sub_building' => (string) $item->attributes()->SubBuilding,
				'building_name' => (string) $item->attributes()->BuildingName,
				'building_number' => (string) $item->attributes()->BuildingNumber,
				'primary_street' => (string) $item->attributes()->PrimaryStreet,
				'secondary_street' => (string) $item->attributes()->SecondaryStreet,
				'double_dependent_locality' => (string) $item->attributes()->DoubleDependentLocality,
				'dependent_locality' => (string) $item->attributes()->DependentLocality,
				'pobox' => (string) $item->attributes()->PoBox,
				'primary_street_name' => (string) $item->attributes()->PrimaryStreetName,
				'primary_street_type' => (string) $item->attributes()->PrimaryStreetType,
				'secondary_street_name' => (string) $item->attributes()->SecondaryStreetName,
				'secondary_street_type' => (string) $item->attributes()->SecondaryStreetType,
				'country_name' => (string) $item->attributes()->CountryName,
				'country_iso2' => (string) $item->attributes()->CountryISO2,
				'country_iso3' => (string) $item->attributes()->CountryISO3
			);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 *
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/RetrieveByAddress/v1.2/default.aspx
 * (Not free)
 */
class interactiveRetrieveByAddress extends postcodeanywhere {

	protected $sAddress = null;
	protected $sCompany = null;
	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/RetrieveByAddress/v1.20/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Sets the address of the address were looking for
	 *
	 * @param string $sAddress The address to search for
     *
	 * @throws Exception
	 */
	public function setAddress($sAddress = '') {
		if (strlen($sAddress) === 0) {
			throw new Exception('Invalid Address String');
		}

		$this->sAddress = $sAddress;
	}

	/**
	 * Sets the companny name of the address were looking for
	 *
	 * @param string $sCompany The company name to search for
     *
	 * @throws Exception
	 */
	public function setCompany($sCompany) {
		if (strlen($sCompany) === 0) {
			throw new Exception('Invalid Company String');
		}

		$this->sCompany = $sCompany;
	}


	/**
	 * Perform search for address using company name and/or address
	 *
	 * @return boolean
	 */
	public function run() {
		//Standard
		$this->aUrl = array();


		//Specific
		$this->aUrl['Address'] = $this->sAddress;
		$this->aUrl['Company'] = $this->sCompany;

		//Make the request
		$oXML = $this->fetchXML($this->buildUrl());

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array(
				'udprn' => (int) $item->attributes()->Udprn,
				'company' => (string) $item->attributes()->Company,
				'department' => (string) $item->attributes()->Department,
				'line1' => (string) $item->attributes()->Line1,
				'line2' => (string) $item->attributes()->Line2,
				'line3' => (string) $item->attributes()->Line3,
				'line4' => (string) $item->attributes()->Line4,
				'line5' => (string) $item->attributes()->Line5,
				'posttown' => (string) $item->attributes()->PostTown,
				'county' => (string) $item->attributes()->County,
				'postcode' => (string) $item->attributes()->Postcode,
				'mailsort' => (int) $item->attributes()->Mailsort,
				'barcode' => (string) $item->attributes()->Barcode,
				'type' => (string) $item->attributes()->Type,
				'delivery_point_suffix' => (string) $item->attributes()->DeliveryPointSuffix,
				'sub_building' => (string) $item->attributes()->SubBuilding,
				'building_name' => (string) $item->attributes()->BuildingName,
				'building_number' => (string) $item->attributes()->BuildingNumber,
				'primary_street' => (string) $item->attributes()->PrimaryStreet,
				'secondary_street' => (string) $item->attributes()->SecondaryStreet,
				'double_dependent_locality' => (string) $item->attributes()->DoubleDependentLocality,
				'dependent_locality' => (string) $item->attributes()->DependentLocality,
				'pobox' => (string) $item->attributes()->PoBox,
				'primary_street_name' => (string) $item->attributes()->PrimaryStreetName,
				'primary_street_type' => (string) $item->attributes()->PrimaryStreetType,
				'secondary_street_name' => (string) $item->attributes()->SecondaryStreetName,
				'country_name' => (string) $item->attributes()->CountryName,
				'confidence' => (string) $item->attributes()->Confidence
			);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 * Utility class for the validation and cleansing of Postcodes
 *
 */
class Pca_Postcode {

	//public $sPostCodeRegex = '/^([A-PR-UWYZ0-9][A-HK-Y0-9][AEHMNPRTVXY0-9]?[ABEHMNPRVWXY0-9]? {1,2}[0-9][ABD-HJLN-UW-Z]{2}|GIR 0AA)$/';
	public $sPostCodeRegex = null;

	public function __construct($regEx) {
		$this->sPostCodeRegex = $regEx;
	}

	/**
	 * Is the postcode valid?
	 *
	 * @param type $sPostcode The postcode (UK) to check
	 *
	 * @return boolean
	 */
	public function isValid($sPostcode = '') {
		//return (bool) preg_match($this->sPostCodeRegex, $this->cleanPostcode($sPostcode));
		$sPostcode = strtoupper(str_replace(' ', '', $sPostcode));
		return (bool) preg_match("$this->sPostCodeRegex","$sPostcode");
	}

	/**
	 * Clean a postcode string
	 *
	 * @param string $sPostcode The postcode to clean up
     *
	 * @return string
	 */
	public function cleanPostcode($sPostcode = '') {
		$sPostcode = strtoupper(str_replace(' ', '', $sPostcode));
		$sPostcode = wordwrap($sPostcode, strlen($sPostcode) - 3, ' ', true);
		return trim($sPostcode);
	}

}

/*
* find address for all countries
*/

class Capture_Interactive_Find_v1_00

{
    private $Key; //The key used to authenticate with the service.

    private $Text; //The search text to find. Ideally a postcode or the start of the address.

    private $Container; //A container for the search. This should only be another Id previously returned from this service when the Type of the result was not 'Address'.

    private $Origin; //A starting location for the search. This can be the name or ISO 2 or 3 character code of a country, WGS84 coordinates (comma separated) or IP address to search from.

    private $Countries; //A comma separated list of ISO 2 or 3 character country codes to limit the search within.

    private $Limit; //The maximum number of results to return.

    private $Language; //The preferred language for results. This should be a 2 or 4 character language code e.g. (en, fr, en-gb, en-us etc).

    private $Data; //Holds the results of the query

    function __construct($Key, $Text, $Container, $Origin, $Countries, $Limit, $Language)
    {
        $this->Key = $Key;
        $this->Text = $Text;
        $this->Container = $Container;
        $this->Origin = $Origin;
        $this->Countries = $Countries;
        $this->Limit = $Limit;
        $this->Language = $Language;
    }

    function MakeRequest()
    {
        $url = "https://api.addressy.com/Capture/Interactive/Find/v1.00/xmla.ws?";
        $url .= "&Key=" . urlencode($this->Key);
        $url .= "&Text=" . urlencode($this->Text);
        $url .= "&Container=" . urlencode($this->Container);
        $url .= "&Origin=" . urlencode($this->Origin);
        $url .= "&Countries=" . urlencode($this->Countries);
        $url .= "&Limit=" . urlencode($this->Limit);
        $url .= "&Language=" . urlencode($this->Language);
        //Make the request to Postcode Anywhere and parse the XML returned
        $file = simplexml_load_file($url);


        //Check for an error, if there is one then throw an exception

        if ($file->Columns->Column->attributes()->Name == "Error")
        {
            throw new Exception("[ID] " . $file->Rows->Row->attributes()->Error . " [DESCRIPTION] " . $file->Rows->Row->attributes()->Description . " [CAUSE] " . $file->Rows->Row->attributes()->Cause . " [RESOLUTION] " . $file->Rows->Row->attributes()->Resolution);

        }

        //Copy the data

        if ( !empty($file->Rows) )
        {
            foreach ($file->Rows->Row as $item)
            {
                $this->Data[] = array('Id'=>$item->attributes()->Id,'Type'=>$item->attributes()->Type,'Text'=>$item->attributes()->Text,'Highlight'=>$item->attributes()->Highlight,'Description'=>$item->attributes()->Description);
            }
        }
    }

    function HasData()
    {
        if ( !empty($this->Data) )
        {
            return $this->Data;
        }
        return false;
    }
}

/*
* find address by address id returned by pevious class
*/
class Capture_Interactive_Retrieve_v1_00
{
    private $Key; //The key to use to authenticate to the service.
    private $Id; //The Id from a Find method to retrieve the details for.
    private $Field1Format; //
    private $Field2Format; //
    private $Field3Format; //
    private $Field4Format; //
    private $Field5Format; //
    private $Field6Format; //
    private $Field7Format; //
    private $Field8Format; //
    private $Field9Format; //
    private $Field10Format; //
    private $Field11Format; //
    private $Field12Format; //
    private $Field13Format; //
    private $Field14Format; //
    private $Field15Format; //
    private $Field16Format; //
    private $Field17Format; //
    private $Field18Format; //
    private $Field19Format; //
    private $Field20Format; //
    private $Data; //Holds the results of the query


    function __construct($Key, $Id, $Field1Format, $Field2Format, $Field3Format, $Field4Format, $Field5Format, $Field6Format, $Field7Format, $Field8Format, $Field9Format, $Field10Format, $Field11Format, $Field12Format, $Field13Format, $Field14Format, $Field15Format, $Field16Format, $Field17Format, $Field18Format, $Field19Format, $Field20Format)
    {
        $this->Key = $Key;
        $this->Id = $Id;
        $this->Field1Format = $Field1Format;
        $this->Field2Format = $Field2Format;
        $this->Field3Format = $Field3Format;
        $this->Field4Format = $Field4Format;
        $this->Field5Format = $Field5Format;
        $this->Field6Format = $Field6Format;
        $this->Field7Format = $Field7Format;
        $this->Field8Format = $Field8Format;
        $this->Field9Format = $Field9Format;
        $this->Field10Format = $Field10Format;
        $this->Field11Format = $Field11Format;
        $this->Field12Format = $Field12Format;
        $this->Field13Format = $Field13Format;
        $this->Field14Format = $Field14Format;
        $this->Field15Format = $Field15Format;
        $this->Field16Format = $Field16Format;
        $this->Field17Format = $Field17Format;
        $this->Field18Format = $Field18Format;
        $this->Field19Format = $Field19Format;
        $this->Field20Format = $Field20Format;
    }

    function MakeRequest()
    {
        $url = "https://api.addressy.com/Capture/Interactive/Retrieve/v1.00/xmla.ws?";
        $url .= "&Key=" . urlencode($this->Key);
        $url .= "&Id=" . urlencode($this->Id);
        $url .= "&Field1Format=" . urlencode($this->Field1Format);
        $url .= "&Field2Format=" . urlencode($this->Field2Format);
        $url .= "&Field3Format=" . urlencode($this->Field3Format);
        $url .= "&Field4Format=" . urlencode($this->Field4Format);
        $url .= "&Field5Format=" . urlencode($this->Field5Format);
        $url .= "&Field6Format=" . urlencode($this->Field6Format);
        $url .= "&Field7Format=" . urlencode($this->Field7Format);
        $url .= "&Field8Format=" . urlencode($this->Field8Format);
        $url .= "&Field9Format=" . urlencode($this->Field9Format);
        $url .= "&Field10Format=" . urlencode($this->Field10Format);
        $url .= "&Field11Format=" . urlencode($this->Field11Format);
        $url .= "&Field12Format=" . urlencode($this->Field12Format);
        $url .= "&Field13Format=" . urlencode($this->Field13Format);
        $url .= "&Field14Format=" . urlencode($this->Field14Format);
        $url .= "&Field15Format=" . urlencode($this->Field15Format);
        $url .= "&Field16Format=" . urlencode($this->Field16Format);
        $url .= "&Field17Format=" . urlencode($this->Field17Format);
        $url .= "&Field18Format=" . urlencode($this->Field18Format);
        $url .= "&Field19Format=" . urlencode($this->Field19Format);
        $url .= "&Field20Format=" . urlencode($this->Field20Format);
        //Make the request to Postcode Anywhere and parse the XML returned
        $file = simplexml_load_file($url);
        //Check for an error, if there is one then throw an exception
        if ($file->Columns->Column->attributes()->Name == "Error")
        {
            throw new Exception("[ID] " . $file->Rows->Row->attributes()->Error . " [DESCRIPTION] " . $file->Rows->Row->attributes()->Description . " [CAUSE] " . $file->Rows->Row->attributes()->Cause . " [RESOLUTION] " . $file->Rows->Row->attributes()->Resolution);
        }

        //Copy the data
        if ( !empty($file->Rows) )
        {
            foreach ($file->Rows->Row as $item)

            {
                $this->Data[] = array('Id'=>$item->attributes()->Id,'DomesticId'=>$item->attributes()->DomesticId,'Language'=>$item->attributes()->Language,'LanguageAlternatives'=>$item->attributes()->LanguageAlternatives,'Department'=>$item->attributes()->Department,'Company'=>$item->attributes()->Company,'SubBuilding'=>$item->attributes()->SubBuilding,'BuildingNumber'=>$item->attributes()->BuildingNumber,'BuildingName'=>$item->attributes()->BuildingName,'SecondaryStreet'=>$item->attributes()->SecondaryStreet,'Street'=>$item->attributes()->Street,'Block'=>$item->attributes()->Block,'Neighbourhood'=>$item->attributes()->Neighbourhood,'District'=>$item->attributes()->District,'City'=>$item->attributes()->City,'Line1'=>$item->attributes()->Line1,'Line2'=>$item->attributes()->Line2,'Line3'=>$item->attributes()->Line3,'Line4'=>$item->attributes()->Line4,'Line5'=>$item->attributes()->Line5,'AdminAreaName'=>$item->attributes()->AdminAreaName,'AdminAreaCode'=>$item->attributes()->AdminAreaCode,'Province'=>$item->attributes()->Province,'ProvinceName'=>$item->attributes()->ProvinceName,'ProvinceCode'=>$item->attributes()->ProvinceCode,'PostalCode'=>$item->attributes()->PostalCode,'CountryName'=>$item->attributes()->CountryName,'CountryIso2'=>$item->attributes()->CountryIso2,'CountryIso3'=>$item->attributes()->CountryIso3,'CountryIsoNumber'=>$item->attributes()->CountryIsoNumber,'SortingNumber1'=>$item->attributes()->SortingNumber1,'SortingNumber2'=>$item->attributes()->SortingNumber2,'Barcode'=>$item->attributes()->Barcode,'POBoxNumber'=>$item->attributes()->POBoxNumber,'Label'=>$item->attributes()->Label,'Type'=>$item->attributes()->Type,'DataLevel'=>$item->attributes()->DataLevel,'Field1'=>$item->attributes()->Field1,'Field2'=>$item->attributes()->Field2,'Field3'=>$item->attributes()->Field3,'Field4'=>$item->attributes()->Field4,'Field5'=>$item->attributes()->Field5,'Field6'=>$item->attributes()->Field6,'Field7'=>$item->attributes()->Field7,'Field8'=>$item->attributes()->Field8,'Field9'=>$item->attributes()->Field9,'Field10'=>$item->attributes()->Field10,'Field11'=>$item->attributes()->Field11,'Field12'=>$item->attributes()->Field12,'Field13'=>$item->attributes()->Field13,'Field14'=>$item->attributes()->Field14,'Field15'=>$item->attributes()->Field15,'Field16'=>$item->attributes()->Field16,'Field17'=>$item->attributes()->Field17,'Field18'=>$item->attributes()->Field18,'Field19'=>$item->attributes()->Field19,'Field20'=>$item->attributes()->Field20);
            }
        }
    }

    function HasData()
    {
      if ( !empty($this->Data) )
        {
          return $this->Data;
        }
        return false;
    }
}

?>
