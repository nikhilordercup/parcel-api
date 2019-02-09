<?php
require_once "Business_Days_Calculator.php";

class Carrier_Business_Day extends Business_Days_Calculator{
    public static $businessObj = null;
    private $_calculator = NULL;

    public function __construct(){
        if(self::$businessObj===null){
            self::$businessObj = new Business_Days_Calculator();
        }
        $this->_calculator = self::$businessObj;
    }

    public function _findBusinessDay( $startDate,  $businessDays = 0,  $carrierCode){
        $holidays = array(); //[new DateTime("2014-06-01"), new DateTime("2014-06-02")]
        $this->_calculator->setParam($startDate, $holidays, [Business_Days_Calculator::SATURDAY, Business_Days_Calculator::SUNDAY]);
        $this->_calculator->addBusinessDays($businessDays);
        return $this->_calculator->getDate();
    }
}
/*$calculator = new Business_Days_Calculator(
    new DateTime(), // Today
    [new DateTime("2014-06-01"), new DateTime("2014-06-02")], // holiday
    [BusinessDaysCalculator::SATURDAY, BusinessDaysCalculator::FRIDAY] // Non business day
);

$calculator->addBusinessDays(3); // Add three business days

var_dump($calculator->getDate());
die;*/
