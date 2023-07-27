<?php

namespace portalium\notification\widgets;

use mysql_xdevapi\Warning;
use portalium\menu\models\MenuItem;
use portalium\notification\models\Notification as notificationModel;
use yii\base\Widget;
use portalium\notification\Module;
use Yii;

class Notification extends Widget
{
    public $options;
    public $display;
    public $icon;

    //initialize widget properties
    public function init()
    {
        $this->display = MenuItem::TYPE_DISPLAY['icon'];
        parent::init();
    }

    //contain the code that generates the rendering result of the widget
    public function run()
    {
        if(\Yii::$app->user->can('notificationWebDefaultIndex'))
        {
            $notifications = notificationModel::getAllNotifications();
        }
        else if(\Yii::$app->user->can('notificationWebDefaultIndexOwn'))
        {
            $notifications = notificationModel::getRelatedNotifications();
        }
        else{
            $notifications=[];
        }
        return $this->render('notifications', [
            'notifications' => $notifications,
            'options' => $this->options
        ]);
    }
}