<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd586e1af5293c4db34b6b440eade6f0c
{
    public static $prefixLengthsPsr4 = array (
        'c' => 
        array (
            'codexoft\\MpesaSdk\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'codexoft\\MpesaSdk\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitd586e1af5293c4db34b6b440eade6f0c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd586e1af5293c4db34b6b440eade6f0c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd586e1af5293c4db34b6b440eade6f0c::$classMap;

        }, null, ClassLoader::class);
    }
}
