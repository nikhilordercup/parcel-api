<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit161dc0fc2b23db9659d5f752d7a86e0c
{
    public static $files = array (
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        '8dd32984d4cd58147cb41bf3844153c3' => __DIR__ . '/..' . '/chargebee/chargebee-php/lib/ChargeBee.php',
    );

    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'sngrl\\PhpFirebaseCloudMessaging\\' => 32,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'sngrl\\PhpFirebaseCloudMessaging\\' => 
        array (
            0 => __DIR__ . '/..' . '/sngrl/php-firebase-cloud-messaging/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit161dc0fc2b23db9659d5f752d7a86e0c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit161dc0fc2b23db9659d5f752d7a86e0c::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
