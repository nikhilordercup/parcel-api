<?php
/**
 * User: Mandeep Singh Nain
 * Date: 03/01/2018
 * Time: 05:28 PM
 */

class Payment
{
    private $_db,$_paymentConfig;

    /**
     * Payment constructor.
     */
    public function __construct()
    {
        $this->_db=new DbHandler();
        $this->_paymentConfig=$this->fetchPaymentConfig();
    }

    /**
     * Fetch All drivers related to current login user
     */
    public function fetchDrivers($companyId){
            $driverQuery="SELECT * FROM ".DB_PREFIX."users WHERE user_level=4 AND parent_id=".$companyId;
            $drivers=$this->_db->getAllRecords($driverQuery);

            foreach ($drivers as $driver){
                if(count($this->_paymentConfig)){
                    $conf='PPD';
                    switch ($conf){
                        case 'PPD':
                            $this->calculatePayableDelivery();
                            break;
                        case 'PPA':
                            $this->calculatePayableAttempts();
                            break;
                        case 'PFT':
                            $this->calculatePayableTime();
                            break;
                        case 'PFD':
                            $this->calculatePayableDistance();
                            break;
                        default:
                            break;
                    }
                }
            }
    }

    /**
     * Fetch all unpaid jobs for any particular driver
     */
    public function fetchDriverJobs($driverId){
        $subQuery="SELECT SD.shipment_id FROM payment_details AS SD LEFT JOIN payments 
            AS P on SD.payment_id=P.payment_id WHERE P.driver_id=".$driverId;

        $query="SELECT * FROM shipments WHERE shipment_id NOT IN (".$subQuery.") 
        AND assigned_driver=".$driverId;
        $jobs=$this->_db->getAllRecords($query);
        foreach ($jobs as $job){

        }
    }

    /**
     * Fetch payment configuration for current company.
     */
    public function fetchPaymentConfig($companyId){
            $configQuery="SELECT * FROM payment_config WHERE company_id=".$companyId;
            return $this->_db->getAllRecords($configQuery);
    }

    /**
     * Fetch Payment History for current company
     */
    public function fetchAllPayments($companyId){
        $Query="SELECT * FROM  payments WHERE company_id=".$companyId;
        return $this->_db->getAllRecords($Query);
    }

    /**
     * Add new payment and it's details to database.
     */
    public function addPayment($paymentInfo){
        $insertQuery="";
        $this->_db->executeQuery($insertQuery);
        $paymentId="";
        $this->addPaymentDetails($paymentId,[]);
    }

    /**
     * @param $paymentId
     * @param $details
     * Add Payment Details after inserting payment
     */
    public function addPaymentDetails($paymentId,$details){
        $insertQuery="";
        $this->_db->executeQuery($insertQuery);
    }

    /**
     * @param $paymentId
     * @param $amount
     * @param $comment
     * Add Payment Dispute in payment table
     */
    public function addPaymentDispute($paymentId,$amount,$comment){
        $updatePaymentQuery="";
        $updatePaymentDetailQuery="";
        $this->_db->executeQuery($updatePaymentQuery);
        $this->_db->executeQuery($updatePaymentDetailQuery);
    }

    /**
     * @param $paymentId
     * @param $amount
     * @param $comment
     * Update dispute details like status, comment or disputed amount
     */
    public function editPaymentDispute($paymentId,$amount,$comment){
        $updatePaymentQuery="";
        $updatePaymentDetailQuery="";
        $this->_db->executeQuery($updatePaymentQuery);
        $this->_db->executeQuery($updatePaymentDetailQuery);
    }

    /**
     * @param $paymentId
     * @param $status
     * Change dispute status when admin click on resolve button.
     */
    public function changeDisputeStatus($paymentId,$status){
        $statusChangeQuery="";
        $this->_db->executeQuery($statusChangeQuery);
    }

    /**
     * Calculate driver total applicable travel time for payment when Pay For Time configuration is enabled
     */
    public function calculatePayableTime(){
        $jobs=$this->fetchDriverJobs();
        foreach ($jobs as $job){

        }
    }

    /**
     * Calculate drive applicable traver distance for payment when Pay For Distance configuration is enabled
     */
    public function calculatePayableDistance(){
        $jobs=$this->fetchDriverJobs();
        foreach ($jobs as $job){

        }
    }

    /**
     * Count number of successful deliveries for payment when Pay Per Delivery configuration is enabled.
     */
    public function calculatePayableDelivery(){
        $jobs=$this->fetchDriverJobs();
        foreach ($jobs as $job){

        }
    }

    /**
     * Count number of attempts for deliveries for payment when Pay Per Attempt is enabled.
     */
    public function calculatePayableAttempts(){
        $jobs=$this->fetchDriverJobs();
        foreach ($jobs as $job){

        }
        $deliveryAttempts=$this->fetchDeliveryAttempts();
        foreach ($deliveryAttempts as $attempt){

        }
    }

    /**
     * Create Payment details pdf for driver payment
     */
    public function generatePaymentPdf(){

    }

    /**
     * Create Payment Report for given time period. Like one month, 3 month, half year or year
     */
    public function generatePaymentReportPdf(){

    }

    /**
     * @param $driverId
     * @return array
     */
    public function fetchDeliveryAttempts($driverId){
        $attemptQuery="";
        return $this->_db->getAllRecords($attemptQuery);
    }

}