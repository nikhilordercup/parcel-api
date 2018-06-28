<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 1:28 PM
 */

class Credentials
{
    public $credentials = array(
        "dev"=>array(
            "firebase"=>array(
                "push_notification"=>array(
                    "server_key"=>"AAAAzqSBBiA:APA91bEiv_qrVt2LbguwfRLg0ORB49jhJMEZptJ-dYNxoZX-Dqxq7oQz2zQ1FbYTUaVT_lG0dh4o7DISloK_7cjkggrx1GQ-gfQ9ACXYtzWOWwRWOaGi-CO5dZ6ZfPCE21jm3CWD4Zz9cWfw3NKiVBh-YHCwtYKEKA"
                )
            )
        ),
        "live"=>array(
            "firebase"=>array(
                "push_notification"=>array(
                    "server_key"=>"AAAAzqSBBiA:APA91bEiv_qrVt2LbguwfRLg0ORB49jhJMEZptJ-dYNxoZX-Dqxq7oQz2zQ1FbYTUaVT_lG0dh4o7DISloK_7cjkggrx1GQ-gfQ9ACXYtzWOWwRWOaGi-CO5dZ6ZfPCE21jm3CWD4Zz9cWfw3NKiVBh-YHCwtYKEKA"
                )
            )
        )
    );

    public function getFcmServerKey()
    {
        return $this->credentials[ENV]["firebase"]["push_notification"]["server_key"];
    }
}