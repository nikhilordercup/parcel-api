<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 16-01-2019
 * Time: 02:33 PM
 */

namespace v1\module\Utility;


class TextToImage
{
    /**
     * TextToImage constructor.
     * @param $text
     * @param $textColor
     * @param string $backgroundColor
     * @param $fontSize
     * @param $imgWidth
     * @param $imgHeight
     * @param $dir
     * @param $fileName
     */
    public function __construct($text, $textColor, $backgroundColor = '', $fontSize, $imgWidth, $imgHeight, $dir, $fileName)
    {
        /* settings */
        $font = './calibri.ttf';/*define font*/
        $textColor = $this->hexToRGB($textColor);

        $im = imagecreatetruecolor($imgWidth, $imgHeight);
        $textColor = imagecolorallocate($im, $textColor['r'], $textColor['g'], $textColor['b']);

        if ($backgroundColor == '') {/*select random color*/
            $colorCode = array('#56aad8', '#61c4a8', '#d3ab92');
            $backgroundColor = $this->hexToRGB($colorCode[rand(0, count($colorCode) - 1)]);
            $backgroundColor = imagecolorallocate($im, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b']);
        } else {/*select background color as provided*/
            $backgroundColor = $this->hexToRGB($backgroundColor);
            $backgroundColor = imagecolorallocate($im, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b']);
        }

        imagefill($im, 0, 0, $backgroundColor);
        list($x, $y) = $this->ImageTTFCenter($im, $text, $font, $fontSize);
        imagettftext($im, $fontSize, 0, $x, $y, $textColor, $font, $text);
        if (imagejpeg($im, $dir . $fileName, 90)) {
            imagedestroy($im);
        }
    }

    /*function to convert hex value to rgb array*/
    protected function hexToRGB($colour)
    {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6) {
            list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
        } elseif (strlen($colour) == 3) {
            list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
        } else {
            return false;
        }
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }

    /*function to get center position on image*/
    protected function ImageTTFCenter($image, $text, $font, $size, $angle = 8)
    {
        $xi = imagesx($image);
        $yi = imagesy($image);
        $box = imagettfbbox($size, $angle, $font, $text);
        $xr = abs(max($box[2], $box[4])) + 5;
        $yr = abs(max($box[5], $box[7]));
        $x = intval(($xi - $xr) / 2);
        $y = intval(($yi + $yr) / 2);
        return array($x, $y);
    }
}