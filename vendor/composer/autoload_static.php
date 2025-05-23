<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit622828296935b387d3ebeaae5829b4fb
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit622828296935b387d3ebeaae5829b4fb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit622828296935b387d3ebeaae5829b4fb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit622828296935b387d3ebeaae5829b4fb::$classMap;

        }, null, ClassLoader::class);
    }
}
