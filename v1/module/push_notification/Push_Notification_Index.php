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

class Push_Notification_Index
{

    public function __construct($param){
        if(!isset($param["device_token_id"])){
            return "Device token missing";
        }

        else if(count($param["device_token_id"])==0){
            return "Device token missing";
        }

        $this->device_tokens = $param["device_token_id"];
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

        foreach($this->device_tokens as $device_token){
            $message->addRecipient(new Device($device_token));
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