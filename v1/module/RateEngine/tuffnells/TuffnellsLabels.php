<?php
namespace v1\module\RateEngine\tuffnells;
use v1\module\RateEngine\tuffnells\model\TuffnellsModel;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Exception;

class TuffnellsLabels extends \Icargo
{


    public function __construct($data = array())
    {
        parent::__construct(array(
            "email" => $data->email,
            "access_token" => $data->access_token
        ));
    }

    public function getInstance()
    {
        return new TuffnellsModel();
    }

    public function tuffnellLabelData($postData)
    {
        $tbname = DB_PREFIX . 'rateengine_labels';
        $postvalues = array(
            'credential_info' => json_encode($postData->credentials),
            'collection_info' => json_encode($postData->from),
            'delivery_info' => json_encode($postData->to),
            'package_info' => json_encode($postData->package),
            'extra_info' => json_encode($postData->extra),
            'insurance_info' => json_encode($postData->insurance),
            'constants_info' => json_encode($postData->constants),
            'billing_coounts' => json_encode($postData->billing_account),
            'dispatch_date' => $postData->ship_date,
            'currency' => $postData->currency,
            'carrier' => $postData->carrier,
            'service_type' => $postData->service,
            'labels' => isset($postData_options) ? $postData_options : "",
            'custom' => isset($postData->customs) ? $postData->customs : "",
            'account_number' => $postData->credentials->account_number,
            'reference_id' => $postData->extra->reference_id,
            'created_date' => date("Y-m-d H:i:s")
        );

        $column_names = array(
            'credential_info',
            'collection_info',
            'delivery_info',
            'package_info',
            'extra_info',
            'insurance_info',
            'constants_info',
            'billing_coounts',
            'dispatch_date',
            'currency',
            'carrier',
            'service_type',
            'labels',
            'custom',
            'account_number',
            'reference_id',
            'created_date'
        );

        $insertStmt = $this->db->insertIntoTable($postvalues, $column_names, $tbname);
        if ($insertStmt) {
            $responce = $this->genrateLabel($postData, $insertStmt);
            $responce['label_id'] = $insertStmt;
            return json_encode($responce);
        } else {
            return json_encode(array(
                'status' => 'false',
                'message' => 'OOPS!!! Something went wrong'
            ));
        }
    }

    public function genrateBarcodeNumber($data, $lastid){

        $postalcode = $data;
        $zipCode = $postalcode->to->zip;
        $postcode = $this->prep_postcode($zipCode);
        $explode = explode(' ', $postcode);
        $firstPcode = $explode[0];
        $scondCode = substr($explode[1], 0, 1);
        $pcode = $firstPcode.' '.$scondCode;
        //Depot Code
        $depotCode = $this->getInstance()->getDeliveryDepotCode($pcode);
        $deliveryDepoNumber = sprintf("%03d",($depotCode['delivery_depot_number']));
        $depPostCode = $depotCode['post_code'];
        $deliveryRound = $depotCode['delivery_round'];
        $delivery_depot_code = $depotCode['delivery_depot_code'];
        $post_code = $depotCode['post_code'];


        //Service Code
        $service_args = $this->tuffnelServiceType();
        $key = array_keys(array_combine(array_keys($service_args), array_column($service_args, 'desc')),$data->service);
        $keyval = $key[0];
        $serviceTypeCode = $service_args[$keyval]['service_type_code'];

        //====Sequence Number=====//
        $current_date = date("Y-m-d");
        $sequence_number = $this->getInstance()->genrateSequenceNumber($data->credentials->account_number, $current_date, $lastid);
        $formatted_sequence_number = sprintf("%04d", $sequence_number['account']);
        //======End Sequence Number=====//

        $account_number = substr($data->credentials->account_number, -7);
        $ship_date = strtotime($data->ship_date);
        $ship_month = date('m',$ship_date);
        $shipDate = date('d',$ship_date);
        //$sequence_number = mt_rand(001, 9999);
        $number_of_item = count($data->package);
        $padding_package_number = sprintf("%03d", $number_of_item);
        $barcode_number = $serviceTypeCode.$deliveryDepoNumber.$account_number.$ship_month.$shipDate.$formatted_sequence_number.$padding_package_number;
        $barnumber = preg_replace('/(?<=\d)\s+(?=\d)/', '', $barcode_number);

        return array('barcode' => $barnumber, 'service_code' => $serviceTypeCode, 'delivery_depot_number' => $deliveryDepoNumber,
            'depo_post_code' => $depPostCode, 'delivery_round' => $deliveryRound, 'post_code' => $post_code, 'delivery_depot_code' => $delivery_depot_code);

    }


    public function genrateLabel($data, $lastid){
        
        $html = "";
        $filepath = "";
        $loader = new Twig_Loader_Filesystem(__DIR__);
        $twig = new Twig_Environment($loader);

        $barCode = $this->genrateBarcodeNumber($data, $lastid);
        $bar_code = $barCode['barcode'];
        $serviceCode = $barCode['service_code'];
        $deliveryDepotCode = $barCode['delivery_depot_code'];
        $depoPostCode = $barCode['depo_post_code'];
        $delivery_round = $barCode['delivery_round'];
        $postCode = $barCode['post_code'];
        $deliveryDepotNumber = $barCode['delivery_depot_number'];


        $text = $bar_code;
        $size = "70";
        $orientation = "horizontal";
        $code_type = "code128";
        $print = true;
        $sizefactor = 1;
        $horizontal = uniqid().time().'h.png';
        $vertical = uniqid().time().'v.png';
        //Horizental
        $this->barcode($horizontal,$text,$size,$orientation,$code_type,$print,$sizefactor);
        //Vertical
        $this->barcode($vertical,$text, $size,'vertical',$code_type,false, $sizefactor);

        $specialInstruction = $data->extra->special_instruction;
        $collectionAddress = $data;
        $toName = $collectionAddress->from->name;
        $toCompany = $collectionAddress->from->company;
        $toPhone = $collectionAddress->from->phone;
        $toStreet1 = $collectionAddress->from->street1;
        $toStreet2 = $collectionAddress->from->street2;
        $toCity = $collectionAddress->from->city;
        $toZip = $collectionAddress->from->zip;
        $colAddress = $toName.'<br />'.$toCompany.'<br />'.$toPhone.'<br />'.$toStreet1.'<br />'.$toStreet2.'<br />'.$toCity.'<br />'.$toZip;

        $deliveryAddress = $data;
        $fromName = $deliveryAddress->to->name;
        $fromCompany = $deliveryAddress->to->company;
        $fromPhone = $deliveryAddress->to->phone;
        $fromStreet1 = $deliveryAddress->to->street1;
        $fromStreet2 = $deliveryAddress->to->street2;
        $fromCity = $deliveryAddress->to->city;
        $fromZip = $deliveryAddress->to->zip;
        $delAddress = $fromName.'<br />'.$fromCompany.'<br />'.$fromPhone.'<br />'.$fromStreet1.'<br />'.$fromStreet2.'<br />'.$fromCity.'<br />'.$fromZip;
        $totalPackage = count($data->package);
        $totalWeight = array_column($data->package, 'weight');

        $htmlParser = $twig->render('tuffnel_label_template.html', array('pageBreak'=>'Hello','labelData'=>$data->package,
                'depot_code' => $deliveryDepotCode, 'total_weight' => array_sum($totalWeight), 'total_package' => $totalPackage,
                'collection_address' => $colAddress, 'delivery_address' => $delAddress, 'special_instruction' => $specialInstruction,
                'vertical' => $vertical, 'horizontal' => $horizontal, 'post_code' => $depoPostCode,
                'delivery_depot_code' => $deliveryDepotCode, 'delivery_round' => $delivery_round,
                'delivery_depot_number' => $deliveryDepotNumber, 'dispatchDate' => $data->ship_date,
                'tel' => $fromPhone,'loadIdentity'=>$data->loadIdentity)
        );


        $dompdf = new Dompdf();
        $dompdf->setPaper('A4', 'potrait');
        $dompdf->loadHtml($htmlParser);
        $dompdf->render();
        $output = $dompdf->output();
        $directory = dirname(dirname(dirname(dirname(__FILE__))));
        $dir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
        $uid = uniqid();
        $mkdir = $dir.'/label/'.$uid.'/';
        mkdir($mkdir, 0777, true);
        file_put_contents($mkdir.$uid.'.pdf', $output);
        unlink($directory.DIRECTORY_SEPARATOR.$horizontal);
        unlink($directory.DIRECTORY_SEPARATOR.$vertical);
        $pdfUrl = PDFURL.'/'.$uid.'/'.$uid.'.pdf';
        $arrayval = array(
            "label" => array(
                "tracking_number" => $data->loadIdentity,
                "file_url" => $pdfUrl,
                "accountnumber" => $data->credentials->account_number,
                "accountstatus" => "nil",
                "accounttype" => "nil",
                "authenticationtoken" => $data->credentials->authentication_token,
                "authenticationtoken_created_at" => $data->credentials->authentication_token_created_at,
                "collectionjobnumber" => "PO433202977"
            )
        );
        return $arrayval;
    }


    public function paperManifestLabel($data){

        $checkAllMainfest = $this->getInstance()->paperManifestByDate($data->date);
        $twigLoader = new Twig_Loader_Filesystem(__DIR__);
        $twig = new Twig_Environment($twigLoader);
        $checkAllMainfest = $this->getInstance()->paperManifestByDate($data->date);
        $number_of_consignment = count($checkAllMainfest);
        $total_weight = array_column($checkAllMainfest, 'total_weight');
        $total_parcel = array_column($checkAllMainfest, 'total_parcel');

        $manifestHtmlParser = $twig->render('paper-manifest.html', array('dataargs' => $checkAllMainfest, 'number_of_consignment' => $number_of_consignment,
            'weight_sum' => array_sum($total_weight), 'number_of_parcel' => array_sum($total_parcel)));

        ob_start();
        $dompdf = new Dompdf();
        $options = new \Dompdf\Options();
        $dompdf->setOptions($options);
        $dompdf->setPaper('A4', 'potrait');
        $dompdf->loadHtml($manifestHtmlParser);
        $dompdf->render();
        $output = $dompdf->output();
        $directory = dirname(dirname(dirname(dirname(__FILE__))));
        $dir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
        $uid = 'PAPER_MANIFEST'.uniqid();
        $mkdir = $dir.'/label/'.$uid.'/';
        mkdir($mkdir, 0777, true);
        file_put_contents($mkdir.$uid.'.pdf', $output);

    }


    public function barcode($filepath = "", $text = "0", $size = "20", $orientation = "horizontal", $code_type = "code128", $print = false, $SizeFactor = 1)
    {
        $code_string = "";
        // Translate the $text into barcode the correct $code_type
        if (in_array(strtolower($code_type), array("code128", "code128b"))) {
            $chksum = 104;
            // Must not change order of array elements as the checksum depends on the array's key to validate final code
            $code_array = array(" " => "212222", "!" => "222122", "\"" => "222221", "#" => "121223", "$" => "121322", "%" => "131222", "&" => "122213", "'" => "122312", "(" => "132212", ")" => "221213", "*" => "221312", "+" => "231212", "," => "112232", "-" => "122132", "." => "122231", "/" => "113222", "0" => "123122", "1" => "123221", "2" => "223211", "3" => "221132", "4" => "221231", "5" => "213212", "6" => "223112", "7" => "312131", "8" => "311222", "9" => "321122", ":" => "321221", ";" => "312212", "<" => "322112", "=" => "322211", ">" => "212123", "?" => "212321", "@" => "232121", "A" => "111323", "B" => "131123", "C" => "131321", "D" => "112313", "E" => "132113", "F" => "132311", "G" => "211313", "H" => "231113", "I" => "231311", "J" => "112133", "K" => "112331", "L" => "132131", "M" => "113123", "N" => "113321", "O" => "133121", "P" => "313121", "Q" => "211331", "R" => "231131", "S" => "213113", "T" => "213311", "U" => "213131", "V" => "311123", "W" => "311321", "X" => "331121", "Y" => "312113", "Z" => "312311", "[" => "332111", "\\" => "314111", "]" => "221411", "^" => "431111", "_" => "111224", "\`" => "111422", "a" => "121124", "b" => "121421", "c" => "141122", "d" => "141221", "e" => "112214", "f" => "112412", "g" => "122114", "h" => "122411", "i" => "142112", "j" => "142211", "k" => "241211", "l" => "221114", "m" => "413111", "n" => "241112", "o" => "134111", "p" => "111242", "q" => "121142", "r" => "121241", "s" => "114212", "t" => "124112", "u" => "124211", "v" => "411212", "w" => "421112", "x" => "421211", "y" => "212141", "z" => "214121", "{" => "412121", "|" => "111143", "}" => "111341", "~" => "131141", "DEL" => "114113", "FNC 3" => "114311", "FNC 2" => "411113", "SHIFT" => "411311", "CODE C" => "113141", "FNC 4" => "114131", "CODE A" => "311141", "FNC 1" => "411131", "Start A" => "211412", "Start B" => "211214", "Start C" => "211232", "Stop" => "2331112");
            $code_keys = array_keys($code_array);
            $code_values = array_flip($code_keys);
            for ($X = 1; $X <= strlen($text); $X++) {
                $activeKey = substr($text, ($X - 1), 1);
                $code_string .= $code_array[$activeKey];
                $chksum = ($chksum + ($code_values[$activeKey] * $X));
            }
            $code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];

            $code_string = "211214" . $code_string . "2331112";
        } elseif (strtolower($code_type) == "code128a") {
            $chksum = 103;
            $text = strtoupper($text); // Code 128A doesn't support lower case
            // Must not change order of array elements as the checksum depends on the array's key to validate final code
            $code_array = array(" " => "212222", "!" => "222122", "\"" => "222221", "#" => "121223", "$" => "121322", "%" => "131222", "&" => "122213", "'" => "122312", "(" => "132212", ")" => "221213", "*" => "221312", "+" => "231212", "," => "112232", "-" => "122132", "." => "122231", "/" => "113222", "0" => "123122", "1" => "123221", "2" => "223211", "3" => "221132", "4" => "221231", "5" => "213212", "6" => "223112", "7" => "312131", "8" => "311222", "9" => "321122", ":" => "321221", ";" => "312212", "<" => "322112", "=" => "322211", ">" => "212123", "?" => "212321", "@" => "232121", "A" => "111323", "B" => "131123", "C" => "131321", "D" => "112313", "E" => "132113", "F" => "132311", "G" => "211313", "H" => "231113", "I" => "231311", "J" => "112133", "K" => "112331", "L" => "132131", "M" => "113123", "N" => "113321", "O" => "133121", "P" => "313121", "Q" => "211331", "R" => "231131", "S" => "213113", "T" => "213311", "U" => "213131", "V" => "311123", "W" => "311321", "X" => "331121", "Y" => "312113", "Z" => "312311", "[" => "332111", "\\" => "314111", "]" => "221411", "^" => "431111", "_" => "111224", "NUL" => "111422", "SOH" => "121124", "STX" => "121421", "ETX" => "141122", "EOT" => "141221", "ENQ" => "112214", "ACK" => "112412", "BEL" => "122114", "BS" => "122411", "HT" => "142112", "LF" => "142211", "VT" => "241211", "FF" => "221114", "CR" => "413111", "SO" => "241112", "SI" => "134111", "DLE" => "111242", "DC1" => "121142", "DC2" => "121241", "DC3" => "114212", "DC4" => "124112", "NAK" => "124211", "SYN" => "411212", "ETB" => "421112", "CAN" => "421211", "EM" => "212141", "SUB" => "214121", "ESC" => "412121", "FS" => "111143", "GS" => "111341", "RS" => "131141", "US" => "114113", "FNC 3" => "114311", "FNC 2" => "411113", "SHIFT" => "411311", "CODE C" => "113141", "CODE B" => "114131", "FNC 4" => "311141", "FNC 1" => "411131", "Start A" => "211412", "Start B" => "211214", "Start C" => "211232", "Stop" => "2331112");
            $code_keys = array_keys($code_array);
            $code_values = array_flip($code_keys);
            for ($X = 1; $X <= strlen($text); $X++) {
                $activeKey = substr($text, ($X - 1), 1);
                $code_string .= $code_array[$activeKey];
                $chksum = ($chksum + ($code_values[$activeKey] * $X));
            }
            $code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];

            $code_string = "211412" . $code_string . "2331112";
        } elseif (strtolower($code_type) == "code39") {
            $code_array = array("0" => "111221211", "1" => "211211112", "2" => "112211112", "3" => "212211111", "4" => "111221112", "5" => "211221111", "6" => "112221111", "7" => "111211212", "8" => "211211211", "9" => "112211211", "A" => "211112112", "B" => "112112112", "C" => "212112111", "D" => "111122112", "E" => "211122111", "F" => "112122111", "G" => "111112212", "H" => "211112211", "I" => "112112211", "J" => "111122211", "K" => "211111122", "L" => "112111122", "M" => "212111121", "N" => "111121122", "O" => "211121121", "P" => "112121121", "Q" => "111111222", "R" => "211111221", "S" => "112111221", "T" => "111121221", "U" => "221111112", "V" => "122111112", "W" => "222111111", "X" => "121121112", "Y" => "221121111", "Z" => "122121111", "-" => "121111212", "." => "221111211", " " => "122111211", "$" => "121212111", "/" => "121211121", "+" => "121112121", "%" => "111212121", "*" => "121121211");

            // Convert to uppercase
            $upper_text = strtoupper($text);

            for ($X = 1; $X <= strlen($upper_text); $X++) {
                $code_string .= $code_array[substr($upper_text, ($X - 1), 1)] . "1";
            }

            $code_string = "1211212111" . $code_string . "121121211";
        } elseif (strtolower($code_type) == "code25") {
            $code_array1 = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
            $code_array2 = array("3-1-1-1-3", "1-3-1-1-3", "3-3-1-1-1", "1-1-3-1-3", "3-1-3-1-1", "1-3-3-1-1", "1-1-1-3-3", "3-1-1-3-1", "1-3-1-3-1", "1-1-3-3-1");

            for ($X = 1; $X <= strlen($text); $X++) {
                for ($Y = 0; $Y < count($code_array1); $Y++) {
                    if (substr($text, ($X - 1), 1) == $code_array1[$Y])
                        $temp[$X] = $code_array2[$Y];
                }
            }

            for ($X = 1; $X <= strlen($text); $X += 2) {
                if (isset($temp[$X]) && isset($temp[($X + 1)])) {
                    $temp1 = explode("-", $temp[$X]);
                    $temp2 = explode("-", $temp[($X + 1)]);
                    for ($Y = 0; $Y < count($temp1); $Y++)
                        $code_string .= $temp1[$Y] . $temp2[$Y];
                }
            }

            $code_string = "1111" . $code_string . "311";
        } elseif (strtolower($code_type) == "codabar") {
            $code_array1 = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "-", "$", ":", "/", ".", "+", "A", "B", "C", "D");
            $code_array2 = array("1111221", "1112112", "2211111", "1121121", "2111121", "1211112", "1211211", "1221111", "2112111", "1111122", "1112211", "1122111", "2111212", "2121112", "2121211", "1121212", "1122121", "1212112", "1112122", "1112221");

            // Convert to uppercase
            $upper_text = strtoupper($text);

            for ($X = 1; $X <= strlen($upper_text); $X++) {
                for ($Y = 0; $Y < count($code_array1); $Y++) {
                    if (substr($upper_text, ($X - 1), 1) == $code_array1[$Y])
                        $code_string .= $code_array2[$Y] . "1";
                }
            }
            $code_string = "11221211" . $code_string . "1122121";
        }

        // Pad the edges of the barcode
        $code_length = 20;
        if ($print) {
            $text_height = 30;
        } else {
            $text_height = 0;
        }

        for ($i = 1; $i <= strlen($code_string); $i++) {
            $code_length = $code_length + (integer)(substr($code_string, ($i - 1), 1));
        }

        if (strtolower($orientation) == "horizontal") {
            $img_width = $code_length * $SizeFactor;
            $img_height = $size;
        } else {
            $img_width = $size;
            $img_height = $code_length * $SizeFactor;
        }

        $image = imagecreate($img_width, $img_height + $text_height);
        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $white);
        if ($print) {
            imagestring($image, 5, 31, $img_height, $text, $black);
        }

        $location = 10;
        for ($position = 1; $position <= strlen($code_string); $position++) {
            $cur_size = $location + (substr($code_string, ($position - 1), 1));
            if (strtolower($orientation) == "horizontal")
                imagefilledrectangle($image, $location * $SizeFactor, 0, $cur_size * $SizeFactor, $img_height, ($position % 2 == 0 ? $white : $black));
            else
                imagefilledrectangle($image, 0, $location * $SizeFactor, $img_width, $cur_size * $SizeFactor, ($position % 2 == 0 ? $white : $black));
            $location = $cur_size;
        }

        // Draw barcode to the screen or save in a file
        if ($filepath == "") {
            header('Content-type: image/png');
            imagepng($image);
            imagedestroy($image);
        } else {
            imagepng($image, $filepath);
            imagedestroy($image);
        }
    }

    public function tuffnelServiceType(){
        $args = array(

            'P1' => [
                'service'=>'P1',
                'desc'=>'Next Day',
                'surcharge'=>'',
                'service_type_code' => '01'
            ],
            'P2' => [
                'service' => 'P1',
                'desc' => 'Next day before noon',
                'surcharge' => 'BN',
                'service_type_code' => '01'
            ],
            'P3' => [
                'service' => 'PT',
                'desc' => 'Next day before 10.30',
                'surcharge' => '30',
                'service_type_code' => '01'
            ],
            'P4' => [
                'service' => 'P1',
                'desc' => 'Next day before 09.30',
                'surcharge' => '9T',
                'service_type_code' => '01'
            ],
            'P5' => [
                'service' => 'P1',
                'desc' => 'Saturday AM',
                'surcharge' => 'SM',
                'service_type_code' => '01'
            ],
            'P6' => [
                'service' => 'P1',
                'desc' => 'Saturday delivery',
                'surcharge' => 'SD',
                'service_type_code' => ''
            ],
            'P7' => [
                'service' => 'P2',
                'desc' => '2 day service',
                'surcharge' => '',
                'service_type_code' => '02'
            ],
            'P8' => [
                'service' => 'P3',
                'desc' => '3 day service',
                'surcharge' => '',
                'service_type_code' => '03'
            ],
            'P9' => [
                'service' => 'OF',
                'desc' => 'Next day offshore',
                'surcharge' => 'P1'
            ],
            'P10' => [
                'service' => 'OF',
                'desc' => '3 day offshore',
                'surcharge' => '9T',
                'service_type_code' => '03'
            ],
            'P11' => [
                'service' => 'DB',
                'desc' => 'Next day databag',
                'surcharge' => '',
                'service_type_code' => '04'
            ],
            'P12' => [
                'service' => 'DB',
                'desc' => 'Next day databag before noon',
                'surcharge' => 'BN',
                'service_type_code' => '04'
            ],
            'P13' => [
                'service' => 'DT',
                'desc' => 'Next day databad before 10:30',
                'surcharge' => '30',
                'service_type_code' => '04'
            ],
            'P14' => [
                'service' => 'DB',
                'desc' => 'Next day before 09.30',
                'surcharge' => '9T',
                'service_type_code' => '04'
            ],
            'P15' => [
                'service' => 'DB',
                'desc' => 'Saturday AM databag',
                'surcharge' => 'SM',
                'service_type_code' => '04'
            ],
            'P16' => [
                'service' => 'DB',
                'desc' => 'Saturday databag',
                'surcharge' => 'SD',
                'service_type_code' => ''
            ]
        );

        return $args;
    }

    public function prep_postcode($str){
        $str = strtoupper($str);
        $str = trim($str);
        if(substr($str, -4, 1) != ' ')
            $str = substr($str, 0, strlen($str) - 3) . " " . substr($str, -3);
        return $str;
    }

    public function is_postcode($postcode){
        $postcode = str_replace(' ','',$postcode);
        return
            preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/", $postcode)
            || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/", $postcode)
            || preg_match("/^GIR0[A-Z]{2}$/", $postcode);
    }

    public function getSequence($num) {
        return sprintf("%'.05d\n", $num);
    }


    public function ftp_file( $ftpservername, $ftpusername, $ftppassword, $ftpsourcefile, $ftpdirectory, $ftpdestinationfile )
    {
        $conn_id = ftp_connect($ftpservername);
        if ( $conn_id == false )
        {
            echo "FTP open connection failed to $ftpservername \n" ;
            return false;
        }
        $login_result = ftp_login($conn_id, $ftpusername, $ftppassword);
        if ((!$conn_id) || (!$login_result)) {
            echo "FTP connection has failed!\n";
            echo "Attempted to connect to " . $ftpservername . " for user " . $ftpusername . "\n";
            return false;
        } else {
            echo "Connected to " . $ftpservername . ", for user " . $ftpusername . "\n";
        }
        if ( strlen( $ftpdirectory ) > 0 )
        {
            if (ftp_chdir($conn_id, $ftpdirectory )) {
                echo "Current directory is now: " . ftp_pwd($conn_id) . "\n";
            } else {
                echo "Couldn't change directory on $ftpservername\n";
                return false;
            }
        }
        ftp_pasv ( $conn_id, true ) ;
        $upload = ftp_put( $conn_id, $ftpdestinationfile, $ftpsourcefile, FTP_ASCII );
        if (!$upload) {
            echo "$ftpservername: FTP upload has failed!\n";
            return false;
        } else {
            echo "Uploaded " . $ftpsourcefile . " to " . $ftpservername . " as " . $ftpdestinationfile . "\n";
        }
        ftp_close($conn_id);
        return true;
    }

} 