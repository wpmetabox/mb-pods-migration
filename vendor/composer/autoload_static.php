<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8ed120314d65f97ac2a801ac1b7a53e9
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Riimu\\Kit\\PHPEncoder\\' => 21,
        ),
        'M' => 
        array (
            'MetaBox\\Support\\' => 16,
            'MetaBox\\Pods\\' => 13,
            'MBBParser\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Riimu\\Kit\\PHPEncoder\\' => 
        array (
            0 => __DIR__ . '/..' . '/riimu/kit-phpencoder/src',
        ),
        'MetaBox\\Support\\' => 
        array (
            0 => __DIR__ . '/..' . '/meta-box/support',
        ),
        'MetaBox\\Pods\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'MBBParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/meta-box/mbb-parser/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8ed120314d65f97ac2a801ac1b7a53e9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8ed120314d65f97ac2a801ac1b7a53e9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8ed120314d65f97ac2a801ac1b7a53e9::$classMap;

        }, null, ClassLoader::class);
    }
}
