PhpTiCons
===============
Generate Titanium icons and splash screens  
Based on [Fokke Zandbergen TiCons project](https://github.com/FokkeZB/TiCons)  

Installation:
-------------
The library is PSR-0 compliant and the simplest way to install it is via composer, simply add:

    {
        "require": {
            "ba/PhpTIcons": "dev-master"
        }
    }

into your composer.json, then run 'composer install' or 'composer update' as required.

Example:
--------
This example demonstrates the generation of IOS and Android icons and splash screens based on a  
1012x1014 png icon file and a 2208x2208 png splash screen.

    <?php
        use Econcepto\PHPTi\Medias\PhpTIcons;

        include('../../vendor/autoload.php');

        $generator = new PhpTIcons();
        $generator
            ->setPlatforms([PhpTIcons::PLATFORM_IPHONE, PhpTIcons::PLATFORM_IPAD, PhpTIcons::PLATFORM_ANDROID])
            ->setOutputDir(__DIR__.'/my_titanium_project');
            
        $generator->icons('my_base_icon.png');
        $generator->splash('my_base_splash.png');