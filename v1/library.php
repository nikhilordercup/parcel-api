<?php
    class Library{

        public static $_obj = NULL;

        public $google_api_key = "AIzaSyBhIrfuaPZmIGXV8KO5jCSE-Tpkr8J-7Z8";//"AIzaSyC7QAlFCWP5S4GZAaVQPEYVXkfHHsvgfw0";// "AIzaSyAr3FmCRdCkORfNYgz8fnxFKK7TcsEaLOU";

        public static function _getInstance(){
            if(self::$_obj==NULL){
                self::$_obj = new Library();
            }
            return self::$_obj;
        }

		public function get_file_content($param){
			$content = null;
			$fp = fopen($param['file'],'r') or die('unable to open file');
			while (($line = fgets($fp)) !== false) {
				$content .= $line;
			}
			fclose($fp);
			return $content;
		}

        public function put_file_content(){
			
		}
		
		public function generateCheckDigit($base_val){
			$result = "";
			$weight = array(
				2,	3,	4,
				5,	6,	7,
				2,	3,	4,
				5,	6,	7,
				2,	3,	4,
				5,	6,	7,
				2,	3,	4,
				5,	6,	7
			);
			/* For convenience, reverse the string and work left to right. */
			$reversed_base_val = strrev($base_val);
			for ($i = 0, $sum = 0; $i < strlen($reversed_base_val); $i++)
				{
				/* Calculate product and accumulate. */
				$sum+= (int)substr($reversed_base_val, $i, 1) * $weight[$i];
				}
	
			/* Determine check digit, and concatenate to base value. */
			$remainder = $sum % 11;
			switch ($remainder)
				{
			case 0:
				$result = 0;
				break;
	
			case 1:
				$result = 0;
				break;
	
			default:
				$check_digit = 11 - $remainder;
				$result = $check_digit;
				break;
				}
	
			return $result;
		}
		
		public function get_address_by_postcode($postcode,$lat=false,$long=false){//recommended by nikhil sir for same day booking
			$curl_handle=curl_init();
			curl_setopt($curl_handle, CURLOPT_URL,"https://api.postcodes.io/postcodes/$postcode");
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, 'cargo');
			$query = curl_exec($curl_handle);
			curl_close($curl_handle);
			$json = json_decode($query);
			if($json->status==200){
				return array("status"=>"success", "data"=>$json);
			}else{
				return array("status"=>"error", "data"=>$json);
			}
		}
		
		public function get_lat_long_by_address($address){
            $address = urlencode($address);
            $api_url = "http://maps.googleapis.com/maps/api/geocode/json?address=$address&sensor=false";
            $query   = $this->get_curlresponse($api_url);
            if($query!='Error'){
                $json = json_decode($query);
                if($json->status=="OK"){
                   $postcodeValid = false;
                    foreach($json->results[0]->address_components as $val){
                       if(in_array('postal_code',$val->types)){
                           $postdata = str_replace(' ','',$val->long_name);
                           $addressdatadata = str_replace(' ','',urldecode($address));
                           $postcodeValid = ($postdata==$addressdatadata)?true:false;
                       }
                   }
                if($postcodeValid) {
                     return array("latitude"=>$json->results[0]->geometry->location->lat, "longitude"=>$json->results[0]->geometry->location->lng, 'geo_location'=>$json,"status"=>"success") ;
                }else{
                      return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
                 }elseif($json->status=="OVER_QUERY_LIMIT"){
                   return $this->get_lat_long_by_postcode(urldecode($address));
                }
                 else{
                  return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
            }else{
                 return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
            }
		}
		
        public function get_lat_long_by_address_for_resolve_route($address){
            $address = urlencode($address);
            $api_url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$this->google_api_key";
            $query   = $this->get_curlresponse($api_url);
            if($query!='Error'){
                $json = json_decode($query); 
                if($json->status=="OK"){
                   $postcodeValid = false;
                    foreach($json->results[0]->address_components as $val){
                       if(in_array('country',$val->types)){ 
                           $countryCode = str_replace(' ','',$val->short_name);
                           $postcodeValid = ($countryCode =='GB')?true:false;
                       }
                   }
                if($postcodeValid) {
                     return array("latitude"=>$json->results[0]->geometry->location->lat, "longitude"=>$json->results[0]->geometry->location->lng, 'geo_location'=>$json,"status"=>"success") ;
                }else{
                      return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
                 }
                elseif($json->status=="OVER_QUERY_LIMIT"){
                   return $this->get_lat_long_by_postcode(urldecode($address));
                }
                else{
                  return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
            }else{
                 return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
            }
		}
        public  function get_curlresponse($url){
          try{
            $output = file_get_contents($url);
            return $output;

            /*$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
            //curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $output = curl_exec($ch);
            $request = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $error = curl_error($ch);
            curl_close($ch);
			
            return $output;*/
          }catch(Exception $e){
			  print_r($e);die;
             return 'Error';//array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
          }
      }
		
		public function get_lat_long_by_postcode($address){
			$address = urlencode($address);
            $api_url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$this->google_api_key";
            //$api_url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&sensor=false";   
            $query   = $this->get_curlresponse($api_url);

            //return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
            if($query!='Error'){
                $json = json_decode($query);
                if($json->status=="OK"){
                   $postcodeValid = false;
                   $latitude = '';$long = '' ;$geo_location = '';
                   foreach($json->results as $address_components){
                    foreach($address_components->address_components as $val){
                       if(in_array('postal_code',$val->types)){
                           $postdata = str_replace(' ','',$val->long_name);
                           $addressdatadata = str_replace(' ','',urldecode($address));
                           if($postdata==$addressdatadata){
                               $latitude = $address_components->geometry->location->lat;
                               $long     = $address_components->geometry->location->lng;
                               $postcodeValid = true;
                               break;
                           }
                       }
                    }
                   }
                if($postcodeValid) {
                     //return array("latitude"=>$json->results[0]->geometry->location->lat, "longitude"=>$json->results[0]->geometry->location->lng, 'geo_location'=>$json,"status"=>"success") ;
                     return array("latitude"=>$latitude, "longitude"=>$long, 'geo_location'=>$json,"status"=>"success");
                }else{
                      return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
                }elseif($json->status=="OVER_QUERY_LIMIT"){
                   return $this->get_lat_long_by_postcode(urldecode($address));
                }
                 else{
                  return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
            }else{
                 return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
            }
		}
        
        public function multiple_destinations_distance_and_duration($param){
			$direction = isset($param['order']) ? strtolower($param['order']) : 'asc';
			$mode = $param["mode"];
			$key = $this->google_api_key;

            $origin = urlencode($param['origin']);
            $destinations = urlencode(implode("|",$param['destinations']));
			$dateTimeZone = new DateTimeZone("Europe/London"); 
			$dateTime = new DateTime(date("Y-m-d H:i:s",$param["departure_time"]), $dateTimeZone);
			$timeOffset = $dateTimeZone->getOffset($dateTime); // New time since epoch according to timezone 
			$newTime = time() + $timeOffset; 
			$departure_time = strtotime(date("Y-m-d H:i:s",$newTime));
            //$departure_time = $param["departure_time"];

            $api_url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=$origin&destinations=$destinations&departure_time=$departure_time&traffic_model=best_guess&mode=$mode&key=$key";
            //$api_url = "http://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=$origin&destinations=$destinations&departure_time=$departure_time&traffic_model=best_guess&mode=$mode&sensor=false";
            try{
                $curl_handle=curl_init();
                curl_setopt($curl_handle, CURLOPT_URL,$api_url);
                curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 300);
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl_handle, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');

                curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);

                $query = curl_exec($curl_handle);
                curl_close($curl_handle);
                $json = json_decode($query);

                if($json->status=="OK"){
                    return array("data"=>$json,"status"=>"success");
                }elseif($json->status=="INVALID_REQUEST"){
                    return array("status"=>"error","message"=>$json->error_message);
                }else{
                    return array("status"=>"error","message"=>"Api returns bad response");
                }
            } catch(Exception $e){
                return array("status"=>"error","message"=>$e->getMessage());
            }
		}
        
        public function date_format($date)
        {
            return date("d-m-Y", strtotime($date));
        }
        
        public function time_format($time)
        {
            return date("h:i", strtotime($time));
        }
        
        public function base_url()
        {
            return sprintf(
                "%s://%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],"api"))//$_SERVER['REQUEST_URI']
            );
        }

        public function base_path(){
            return dirname(dirname(__FILE__));
        }

        public function get_tracking_url_by_env(){
		    //if(ENV=="dev"){
                return "http://tracking.app-tree.co.uk/";
            //}
        }

        public function get_api_url(){
            $url = $this->base_url();

            if(ENV=="dev"){
                $url = "$url/dev/";
            }elseif(ENV=="live"){
                $url = "$url/live/";
            }
            return $url;
        }
        
        public function get_address_by_latlong($lat,$long){
            $api_url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long=true";
            $query   = $this->get_curlresponse($api_url);
            if($query!='Error'){
                $json = json_decode($query);
                if($json->status=="OK"){
                    print_r($json);die;
                   $postcodeValid = false;
                    foreach($json->results[0]->address_components as $val){
                       if(in_array('postal_code',$val->types)){
                           $postdata = str_replace(' ','',$val->long_name);
                           $addressdatadata = str_replace(' ','',urldecode($address));
                           $postcodeValid = ($postdata==$addressdatadata)?true:false;
                       }
                   }
                if($postcodeValid) {
                     return array("latitude"=>$json->results[0]->geometry->location->lat, "longitude"=>$json->results[0]->geometry->location->lng, 'geo_location'=>$json,"status"=>"success") ;
                }else{
                      return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
                 }elseif($json->status=="OVER_QUERY_LIMIT"){
                   return $this->get_lat_long_by_postcode(urldecode($address));
                }
                 else{
                  return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
                 }
            }else{
                 return array("latitude"=>0.00, "longitude"=>0.00, 'geo_location'=>array(),"status"=>"error");
            }
		}

		public function saveImage($file_path, $file_name, $encoded_string){
            $path = $this->base_path().$file_path;
            $root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
            //echo "$root-$path";die;

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $data = $encoded_string;

            $type = array("png");
            return "abc.png";
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                    throw new \Exception('invalid image type');
                }

                $data = base64_decode($data);

                if ($data === false) {
                    throw new \Exception('base64_decode failed');
                }
            } else {
                throw new \Exception('did not match data URI with image data');
            }

            if(file_put_contents("$path/$file_name.{$type}", $data)){

                return $this->get_api_url()."$file_path$file_name.{$type}";
            }
            return false;
        }
   }
?>