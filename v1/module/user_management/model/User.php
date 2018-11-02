<?php
require_once 'Deserializable.php';
require_once 'Metadata.php';

class User implements Deserializable{
    public $uid;
    public $email;
    public $emailVerified;
    public $displayName;
    public $photoUrl;
    public $phoneNumber;
    public $disabled;
    public $metadata;
    public $providerData;
    public $passwordHash;
    public $customAttributes;
    public $tokensValidAfterTime;

    public static $modelObj = null;

    public static function _getInstance(){
        if(self::$modelObj===null){
            self::$modelObj = new User();
        }
        return self::$modelObj;
    }

    public function deserialize($input){
        $this->uid = $input->uid;
        $this->email = $input->email;
        $this->emailVerified = $input->emailVerified;
        $this->displayName = $input->displayName;
        $this->photoUrl = $input->photoUrl;
        $this->phoneNumber = $input->phoneNumber;
        $this->disabled = $input->disabled;
        $this->passwordHash = $input->passwordHash;
        $this->metadata = new Metadata($input);
		return $this;
    }
}
?>
