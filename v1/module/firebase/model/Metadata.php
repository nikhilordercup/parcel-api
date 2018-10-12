<?php
class Metadata implements Deserializable{
    public $createdAt = array();
    public $lastLoginAt;

    public function deserialize($input){
        return array(
            "createdAt" => $this->createdAt,
            "lastLoginAt" => $this->lastLoginAt
        );
    }

    public function __construct($input){
        return $this->deserialize($input);
    }
}
?>
