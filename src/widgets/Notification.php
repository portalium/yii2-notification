<?php

namespace portalium\notification\widgets;


use portalium\theme\widgets\Nav;
use portalium\notification\models\Notification as notificationModel;
use yii\base\Widget;
use portalium\notification\Module;
class Notification extends Widget
{
    public $options;
    public $display;

    //initialize widget properties
    public function init()
    {
        parent::init();

    }

    //contain the code that generates the rendering result of the widget
    public function run()
    {
        $notificationItems = [];
        $notifications = notificationModel::find()->all();
        foreach ($notifications as $value){
            $notificationItems[] = [
                'label' => Module::t($value->title),
                'url' => ['/notification/default/index'],
            ];
        }

        $menuItems[] = [
            'label' => "Notifications",
            'items' => $notificationItems,
            'display' => $this->display,
        ];

        return Nav::widget([
            'options' => $this->options,
            'items' => $menuItems,
        ]);
    }
}