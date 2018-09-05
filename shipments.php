<?php
$url = "http://localhost/icargor/icargo/track.php";

$data_string = '{"from":{"name":"Nishant","company":"1 Manor Farm Barns","street1":"1 Manor Farm Barns","street2":"Henton","city":"1 Manor Farm Barns","state":"Oxfordshire","zip":"OX39 4AE","country_name":"England","alpha3_code":"GBR"},"to":{"zip":"OX39 4AE","alpha3_code":"GBR","alpha2_code":"GB","country_name":"United Kingdom"},"ship_date":"2018-04-07","extra":{},"package":[{"packaging_type":"Parcels","width":"12","length":"12","height":"12","dimension_unit":"cm","weight":"12","weight_unit":"KG"}],"customer_id":"181","user_id":"335","booked_by":"10","carrier":"ukmail","currency":"GBP","endPointUrl":"getNextdayAvailableCarrier"}';

$data_string = '{"from":{"name":"Nishant","company":"1 Manor Farm Barns","street1":"1 Manor Farm Barns","street2":"Henton","city":"1 Manor Farm Barns","state":"Oxfordshire","zip":"OX39 4AE","alpha3_code":"GBR","country_name":"England"},"to":{"zip":"OX39 4PU","alpha3_code":"GBR","alpha2_code":"GB","country_name":"United Kingdom"},"ship_date":"2018-04-11","extra":{},"package":[{"packaging_type":"Parcels","width":"12","length":"12","height":"12","dimension_unit":"cm","weight":"12","weight_unit":"KG"}],"customer_id":"181","user_id":"335","booked_by":"10","carrier":"ukmail","currency":"GBP","email":"5ways@gmail.com","access_token":"MTU1NTc0MDMwOS01YWNiMzIyMzc2YjQzLTEw","company_id":"10","endPointUrl":"getNextdayAvailableCarrier"}';

$data_string = '{"parcel":{"0":{"package":"Parcels","qty":"1","weight":"12","length":"12","width":"12","height":"12"}},"customer":{"name":"Pooja Singh","email":"pooja1712@gmail.com","id":"181","users":[{"id":"181","name":"Pooja Singh","email":"pooja1712@gmail.com","is_default":0,"collection_address":{"user_id":"181","address_line1":"1 Manor Farm Barns","address_line2":"Henton","postcode":"OX39 4AE","city":"Chinnor","country":"England","latitude":"51.71613790","longitude":"-0.89684690","state":"Oxfordshire","company_name":"","name":null,"phone":null,"email":null}},{"id":"335","name":"Nishant","email":"nishant.verma121@ordercup.com","is_default":"1","collection_address":{"user_id":"335","address_line1":"1 Manor Farm Barns","address_line2":"Henton","postcode":"OX39 4AE","city":"Chinnor","country":"England","latitude":"51.71613790","longitude":"-0.89684690","state":"Oxfordshire","company_name":"","name":null,"phone":null,"email":null}},{"id":"336","name":"Test User","email":"testing110@gmail.com","is_default":"0","collection_address":null}],"default_user_id":"335"},"collection_postcode":"OX39 4AE","user":{"id":"335","name":"Nishant","email":"nishant.verma121@ordercup.com","is_default":"1","collection_address":{"user_id":"335","address_line1":"1 Manor Farm Barns","address_line2":"Henton","postcode":"OX39 4AE","city":"Chinnor","country":"England","latitude":"51.71613790","longitude":"-0.89684690","state":"Oxfordshire","company_name":"","name":null,"phone":null,"email":null}},"service_date":"2018-04-11","delivery_country":{"id":"235","short_name":"United Kingdom","alpha2_code":"GB","alpha3_code":"GBR","numeric_code":"826"},"delivery_postcode":"OX39 4PU","carrier_code":{"0":"ukmail"},"service_name":{"0":"Next working day"},"carrier_cost":{"0":"4.5"},"customer_cost":{"0":"4.5"},"collection":{"0":"1","postcode":"OX39 4AE","address_line1":"H-140","address_line2":"5th Floor","city":"Noida","county":"UP","country":"India","email":"nishant.v@perceptive-solutions.com","name":"Nishant 1","phone":"9999999999","instruction":"Pickup Instructions"},"service_code":"1","carrier_code_str":"ukmail","collection_str":"1","service_name_str":"Next working day","carrier_cost_str":"4.5","customer_cost_str":"4.5","delivery":{"postcode":"OX39 4PU","instruction":"Delivery Instructions","address_line1":"Auram Avenue","address_line2":"2nd Floor","city":"Pune","county":"Maharashtra","country":"India","email":"nishant.verma@ordercup.com","name":"Nishant sharma","phone":"8888888888"},"customer_reference":"Customer Reference","customer_description":"Customer Descripation","insurance_value":"13","insurance_opted":true,"insurance_currency":"GBP","special":{"1":true,"2":true,"3":true,"4":true,"5":true,"6":true,"7":true,"8":true},"email":"5ways@gmail.com","access_token":"MTU1NTc0MDMwOS01YWNiMzIyMzc2YjQzLTEw","company_id":"10","endPointUrl":"bookNextDayJob"}';

$data_string = '{"rererence":["ICARGOINV0000477"],"email":"meenakshi@perceptive-solutions.com","access_token":"MjA1OTM1NzgwNy01YjBlNTJiNTlhYzdlLTEyMA==","company_id":"10","endPointUrl":"createInvoicepdf"}';
$useragent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.10 (KHTML, like Gecko)
Chrome/8.0.552.224: Safari/534.10'; // notice this


$ch=curl_init($url);

curl_setopt_array($ch, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data_string,
    //CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HTTPHEADER => array('Content-Type:application/json', 'Content-Length: ' . strlen($data_string))
));

$result = curl_exec($ch);
curl_close($ch);

print_r($result);