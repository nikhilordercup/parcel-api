<?php

error_reporting(E_ALL);

require_once 'vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;


$html='<!DOCTYPE html>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Commercial Invoice</title>
<style type="text/css">
    .sender-receiver {
        width:500px; 
        border-top-width: 0px;
        border-right-width: 0px;
        border-bottom-width: 0px;
        border-left-width: 0px;
        -webkit-border-horizontal-spacing: 0px;
        -webkit-border-vertical-spacing: 0px;
         font-family: arial;
        font-size: 14px;
    }
</style>
</head>
<body>
<table border="1" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td colspan="2" align="center" height="25" style="padding:5px;  font-family: arial; font-size:25px; font-weight:bold;">Commercial Invoice</td>
                    </tr>
                    <tr>
                        <td style="width:500px;">
                             <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px;
    border-right-width: 0px;
    border-bottom-width: 0px;
    border-left-width: 0px;
    -webkit-border-horizontal-spacing: 0px;
    -webkit-border-vertical-spacing: 0px;
     font-family: arial;
    font-size: 14px;">
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Sender:</th>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Perceptive Consulting</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Nikhil Kumar</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">6-12 Barkston Gardens</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Kensington</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">London</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;"></td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">SW5 0EN</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">United Kingdom</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Phone Number: 7595590074</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;"></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:500px; border:1px solid red;">
                             <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px;
    border-right-width: 0px;
    border-bottom-width: 0px;
    border-left-width: 0px;
    -webkit-border-horizontal-spacing: 0px;
    -webkit-border-vertical-spacing: 0px;
     font-family: arial;
    font-size: 14px;">
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;"> Recipient:</th>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Presidential Apartments Kensington</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Nikhil Kumar Kumar</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">H 160</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">noida sector 62</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">noida</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">UP</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">201301</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">India</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Phone Number: +447595590074</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px; height:25px;">Email: nikhil122@gmail.com</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;  font-family: arial;
    font-size: 14px;">
                    <tr>
                        <td style="width:500px;">
                            <table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial;
    font-size: 14px;">
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Invoice Date:</th>
                                    <td align="left" style="padding:2px; height:25px;"> 25 July 2018</td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">DHL Waybill Number: </th>
                                    <td align="left" style="padding:2px; height:25px;"> 2550909255 </td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Carrier: </th>
                                    <td align="left" style="padding:2px; height:25px;"> DHL</td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Type of Export:</th>
                                    <td align="left" style="padding:2px; height:25px;"> Permanent </td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Reason for Export: </th>
                                    <td align="left" style="padding:2px; height:25px;"> SALE </td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:500px;">
                            <table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial;
    font-size: 14px;">
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Invoice Number:</th>
                                    <td align="left" style="padding:2px; height:25px;">M00116</td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;"> Sender\'s Reference: </th>
                                    <td align="left" style="padding:2px; height:25px;"></td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Recipient\'s Reference: </th>
                                    <td align="left" style="padding:2px; height:25px;"></td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Type of Export:</th>
                                    <td align="left" style="padding:2px; height:25px;"> DAP - Delivered At Place </td>
                                </tr>
                                <tr>
                                    <th align="left" style="padding:2px; height:25px;">Tax Id/VAT/EIN#: </th>
                                    <td align="left" style="padding:2px; height:25px;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <th style="padding:2px; height:25px; text-align:left; font-family:arial; font-size:20px;" colspan="2">General Notes:</th>
        </tr>
        <tr>
            <td style="padding:2px; height:25px;" colspan="2"></td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px;
    border-right-width: 0px;
    border-bottom-width: 0px;
    border-left-width: 0px;
    -webkit-border-horizontal-spacing: 0px;
    -webkit-border-vertical-spacing: 0px;
     font-family: arial;
    font-size: 14px;">
                    <tr>
                        <th align="left" style="padding:2px; height:25px;"> Quantity</th>
                        <th align="left" style="padding:2px; height:25px;"> Country of Origin</th>
                        <th align="left" style="padding:2px; height:25px;"> Description of Contents</th>
                        <th align="left" style="padding:2px; height:25px;"> Harmonised Code </th>
                        <th align="left" style="padding:2px; height:25px;"> Unit Weight</th>
                        <th align="left" style="padding:2px; height:25px;"> Unit Value </th>
                        <th align="left" style="padding:2px; height:25px;"> SubTotal </th>
                    </tr>
                    <tr>
                        <td align="right" style="padding:2px; height:25px;"> 1</td>
                        <td align="left" style="padding:2px; height:25px;"> United Kingdom</td>
                        <td align="left" style="padding:2px; height:25px;"> Test Item description</td>
                        <td align="left" style="padding:2px; height:25px;"></td>
                        <td align="right" style="padding:2px; height:25px;">20.00 kgs</td>
                        <td align="right" style="padding:2px; height:25px;">50.00</td>
                        <td align="right" style="padding:2px; height:25px;">50.00</td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:2px; height:25px;"><strong>Total Net Weight:</strong></td>
                        <td align="right" style="padding:2px; height:25px;"> 20.00 kgs </td>
                        <td align="left" style="padding:2px; height:25px;"><strong>Total Declared Value:</strong> (GBP)</td>
                        <td colspan="4" align="right" style="padding:2px; height:25px;">50:00</td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:2px; height:25px;"><strong> Total Gross Weight:</strong></td>
                        <td align="right" style="padding:2px; height:25px;"> 20.00 kgs </td>
                        <td align="left" style="padding:2px; height:25px;"><strong>Freight & Insurance Charges:</strong> (GBP)</td>
                        <td colspan="4" align="right" style="padding:2px; height:25px;">00:00</td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:2px; height:25px;"><strong>Total Shipment Pieces:</strong></td>
                        <td align="right" style="padding:2px; height:25px;"> 1 </td>
                        <td align="left" style="padding:2px; height:25px;"><strong> Other Charges: </strong> (GBP)</td>
                        <td colspan="4" align="right" style="padding:2px; height:25px;">00:00</td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:2px; height:25px;"><strong>Currency Code:</strong></td>
                        <td align="left" style="padding:2px; height:25px;"> GBP </td>
                        <td align="left" style="padding:2px; height:25px;"><strong> Total Invoice Amount: </strong> (GBP)</td>
                        <td colspan="4" align="right" style="padding:2px; height:25px;">50:00</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0 0 0px; line-height:20px;"> These commodities, technology or software were exported from United States Of America in accordance with the Export Administration Regulations. Diversion contrary to United States Of America law is prohibited. </div>
    <div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0px; line-height:20px;"> I/We hereby certify that the information on this invoice is true and correct and that the contents of this shipment are as stated above. </div>
    <div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px;">
        <div style="width:50%; float:left;">
            <h4 style="float: left; font-size: 16px; width: 50px; ">Signature:</h4>            
            <p style="float: left; height: 2px; width: 200px; background-color: #000; margin-top: 35px; margin-left: 85px;"></p>            
        </div>
        <div style="width:25%; float:right;">
            <h4 style="float: left; font-size: 16px; width: 50px; ">Date:</h4>            
            <p style="float: right; height: 2px; width: 200px; background-color: #000; margin-top: 35px;"></p>            
        </div>
        <div style="clear: both;"></div>
        <div style="width:500px; margin-bottom:4px; float:left;">
            <span style="float: left; margin:0px; width: 20%; font-size: 16px; font-weight:bold;"> Name: </span>
            <span style="float: left; margin:0 10px 0 70px; width: 117px;">Nilesh Avhad</span>
        </div>
        <div style="clear: both;"></div>
        <div style="width:100%; margin-bottom:30px; float:left;">
            <p style="float:left; margin:0px; width: 117px; font-size: 16px; font-weight:bold;"> Title: </p>
            <p style="float: left; margin:0 0 0 70px; width: 117px;"> Sr S/W Engg</p>
        </div>

    </div>
</body>
</html>';


// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

$dompdf->render();
file_put_contents('doc1.pdf', $dompdf->output());

unset($dompdf);


    require_once('vendor/setasign/fpdf/fpdf.php');

    require_once('vendor/setasign/fpdi/src/autoload.php');

    require_once 'html_table.php';


    use setasign\Fpdi\Fpdi;


    class ConcatPdf extends Fpdi
    {

        public function concat($files)
        {
            foreach($files as $file) 
            {
                $pageCount = $this->setSourceFile($file);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) 
                {
                    $pageId = $this->ImportPage($pageNo);
                    $s = $this->getTemplatesize($pageId);
                    $this->AddPage($s['orientation'], $s);
                    $this->useImportedPage($pageId);
                }                                                
            }
            $this->Output();
        }
    }

    $pdf = new ConcatPdf();
    $pdf->concat(array( 'dhl-2550914542.pdf', 'doc1.pdf'));

?>

