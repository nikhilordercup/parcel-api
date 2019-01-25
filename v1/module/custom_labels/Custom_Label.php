<?php
require_once "design1/design1.php";
class Custom_Label
{
    public

    function createLabel($load_identity,$carrier_code)
    {
        $config = array(
            'mode' => 'c',
			'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 5,
            //'margin_bottom' => 25,
            //'margin_header' => 16,
            //'margin_footer' => 13
			      'format' => 'A4'
			//'format' => array(101,152),
			//'orientation' => 'L'
        );

        $dirPath = "../label/$load_identity/$carrier_code";
        $path = "$dirPath/$load_identity.pdf";
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $obj = new Design1_Label();
        $labels = $obj->createLable($load_identity);
        $mpdf = new \Mpdf\Mpdf($config);
        $mpdf->SetDisplayMode('fullwidth');// fullwidth // real //default // fullpage
        $mpdf->list_indent_first_level = 0; // 1 or 0 - whether to indent the first level of a list

        // Load a stylesheet

        $stylesheet = file_get_contents('../v1/module/custom_labels/mpdfstyletables.css');
        $mpdf->WriteHTML($stylesheet, 1); // The parameter 1 tells that this is css/style only and no body/html/text
        foreach($labels as $label) {
            $mpdf->AddPage();
            $mpdf->WriteHTML($label, 2);
        }

        $mpdf->Output($path, "F");
        return $path;
    }
}
