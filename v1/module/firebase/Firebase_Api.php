<?php
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Firebase_Api{
    public static $_fbDatabase = NULL;
    public $firebase = NULL;
    public

    function __construct(){
        if(self::$_fbDatabase==null){
            $serviceAccount = ServiceAccount::fromJsonFile($this->_getFbCredential());
            $this->firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                // The following line is optional if the project id in your credentials file
                // is identical to the subdomain of your Firebase project. If you need it
                ->withDatabaseUri($this->_getFirebaseDb())
                ->create();
            self::$_fbDatabase = $this->firebase->getDatabase();
        }
        $this->database = self::$_fbDatabase;
    }

    private function _getFbCredential(){
        if(ENV=='live')
            return './credentials/idriver-production-270f0f61a989.json';
        else
            return './credentials/idriver-staging-5c94aa176af5.json';
    }

    private function _getFirebaseDb(){
        if(ENV=='live')
            return 'https://idriver-1476714038443.firebaseio.com/';//return 'https://idriver-production.firebaseio.com/';
        else
            return 'https://idriver-staging.firebaseio.com/';
    }

    public function getFirebase(){
      return $this->firebase;
    }

    public

    function save($url, $data){
        $newPostKey = $this->createNewPostKey($url);
        $data["postId"] = $newPostKey;
        $this->update("$url/$newPostKey", $data);
        return $newPostKey;
    }

    public

    function delete($url){
        $obj = $this->database
            ->getReference($url);
        return $obj->remove();
    }

    public

    function update($url, $data){
        return $this->database
                   ->getReference($url)
                   ->update($data);
    }

    public

    function getAppServiceMessage($url){
        return $this->database
                   ->getReference($url)
                   ->getSnapshot()
                   ->getValue();
    }

    public

    function createNewPostKey($url){
        $newPost = $this->database
            ->getReference($url)
            ->push('');
        $newPostKey = $newPost->getKey();
        return $newPostKey;
    }
}
?>
