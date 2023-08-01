<?php

namespace portalium\notification\bundles;
use yii\web\AssetBundle;

class NotificationAsset extends AssetBundle
{
    public $sourcePath = '@vendor/portalium/yii2-notification/src/assets/';

    public $depends = [];

    public $css = [
        'css/notification.css',
    ];

    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    public function init()
    {
        parent::init();
    }
}

