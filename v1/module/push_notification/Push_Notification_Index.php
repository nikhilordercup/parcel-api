<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 1:20 PM
 */

/*FCM integration*/
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;

require_once 'model/Push_Notification_Model_Index.php';

class Push_Notification_Index
{

    public function __construct($param)
    {
        if(!isset($param["user_id"])){
            return "User id not found";
        }

        $this->user_id = $param["user_id"];

        $this->modelObj = new Push_Notification_Model_Index();

    }

    private function _getDeviceTokens()
    {
        return $this->modelObj->getDeviceTokenByUserId(implode(",", $this->user_id));
    }

    public function sendRouteAssignNotification()
    {
        $credentials = new Credentials();

        $server_key = $credentials->getFcmServerKey();
        $client = new Client();

        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $notification = new Notification();

        $notification->setTitle('iDriver')->setBody('New route has been assigned')->setSound(true);

        $message = new Message();
        $message->setPriority('high');

        $deviceTokens = $this->_getDeviceTokens();

        foreach($deviceTokens as $device_token){
            $message->addRecipient(new Device($device_token["device_token_id"]));
        }

        $message
            ->setNotification($notification)
            ->setData(['key' => 'value'])
        ;

        $response = $client->send($message);
        //var_dump($response->getStatusCode());
        //var_dump($response->getBody()->getContents());
    }
}