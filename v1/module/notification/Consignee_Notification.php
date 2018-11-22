<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/03/18
 * Time: 10:39 AM
 */

class Consignee_Notification
{
    public static $obj = NULL;

    public

    static function _getInstance(){
        if(self::$obj==NULL){
            self::$obj = new Consignee_Notification();
        }
        return self::$obj;
    }

    private

    function __autoload($className, $type){
        $type = ucfirst(strtolower($type));
        require_once("$type/$className.php");
    }

    public

    function sendRouteStartNotification($param){
        $this->__autoload("Route_Start_Notification_Consignee", "sameday");
        $obj = new Route_Start_Notification_Consignee();
        $obj->send($param);
    }

    public

    function sendShipmentCollectionDeliverNotification($param){
        $this->__autoload("Collection_Delivery_Notification_Consignee", "sameday");
        $obj = new Collection_Delivery_Notification_Consignee();
        $obj->send($param);
    }

    public

    function sendSamedayBookingConfirmationNotification($param){
        $this->__autoload("Booking_Notification_Consignee", "sameday");
        $obj = new Booking_Notification_Consignee();
        $obj->send($param);
    }

    public

    function sendSamedayBookingConfirmationNotificationToCourier($param){
        $this->__autoload("Booking_Notification_Courier", "sameday");
        $obj = new Booking_Notification_Courier();
        $obj->send($param);
    }

    public

    function sendNextdayBookingConfirmationNotificationToCourier($param){
        $this->__autoload("Booking_Notification_Courier", "nextday");
        $obj = new Booking_Notification_Courier();
        $obj->send($param);
    }

    public

    function sendNextdayBookingConfirmationNotification($param){
        $this->__autoload("Booking_Notification_Consignee", "nextday");
        $obj = new Booking_Notification_Consignee();
        $obj->send($param);
    }

    public

    function sendNextdayQuotationEmailToConsignee($param){
        $this->__autoload("Quotation_Notification_Consignee", "nextday");
        $obj = new Quotation_Notification_Consignee($param);
        $obj->send($param);
    }
     /* New Template*/
    public

    function sendCustomerInvoiceNotification($param,$_sendpdftocustomer,$_sendtocustomemail,$physicalpath){
        $this->__autoload("Customer_Invoice", "invoice");
        $obj = new Customer_Invoice($param);
        $obj->send($param,$_sendpdftocustomer,$_sendtocustomemail,$physicalpath);
    }
    
    public
    function sendRecurringNotification($param){
        $this->__autoload("Recurring", "recurring");
        $obj = new Recurring($param);
        $obj->send($param);
    } 
   
}