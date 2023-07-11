<?php

namespace portalium\notification\bundles;
use yii\web\AssetBundle;

class notificationAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/portalium-notification/src/assets/';

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset'
    ];

    public $css = [
        'css/notification.css',
    ];

//
//    public $publishOptions = [
//        'forceCopy' => YII_DEBUG,
//    ];

    public function init()
    {
        parent::init();
    }
}

