<?php

namespace portalium\notification;

class Module extends \portalium\base\Module
{
    public $apiRules = [
        [
            'class' => 'yii\rest\UrlRule',
            'controller' => [
                'notification/default',
            ]
        ],
    ];
    
	public static $tablePrefix = 'notification_';
    public static function moduleInit()
    {
        self::registerTranslation('notification','@portalium/notification/messages',[
            'notification' => 'notification.php',
        ]);
    }

    public static function t($message, array $params = [])
    {
        return parent::coreT('notification', $message, $params);
    }

    public function registerComponents()
    {
        return [
            'notification' => [
                'class' => 'portalium\notification\components\Notification',
            ]
        ];
    }

    public function getMenuItems(){
        $menuItems = [
            [
                [
                    'menu' => 'web',
                    'type' => 'widget',
                    'label' => 'portalium\notification\widgets\Notification',
                    'name' => 'notification',
                ]
            ],
        ];
        return $menuItems;
    }
}