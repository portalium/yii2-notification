<?php

namespace portalium\notification\widgets;

use portalium\menu\models\MenuItem;
use portalium\notification\models\Notification as NotificationModel;
use yii\base\Widget;
use portalium\notification\Module;
use Yii;
use yii\helpers\Html;

class Notification extends Widget
{
    public $options;
    public $display;
    public $icon;

    //initialize widget properties
    public function init()
    {
        if (!$this->icon) {
            $this->icon = Html::tag('i', '', ['class' => '', 'style' => 'margin-right: 5px;']);
        }
        //        $this->display = MenuItem::TYPE_DISPLAY['icon-text'];

        parent::init();
    }

    //contain the code that generates the rendering result of the widget
    public function run()
    {
        // if(\Yii::$app->user->can('notificationWebDefaultIndex'))
        // {
        // $notifications = NotificationModel::getAllNotifications();
        // }
        if (\Yii::$app->user->can('notificationWebDefaultIndexOwn')) {
            $notifications = NotificationModel::getRecentNotifications();
            $unread = NotificationModel::countUnread();
        } else {
            $notifications = [];
            $unread = 0;
        }

        return $this->render('notifications', [
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }

    private function generateLabel($text)
    {
        $label = "";
        if (isset($this->display)) {
            switch ($this->display) {
                case MenuItem::TYPE_DISPLAY['icon']:
                    $label = $this->icon;
                    break;
                case MenuItem::TYPE_DISPLAY['icon-text']:
                    $label = $this->icon . \portalium\site\Module::t($text);
                    break;
                case MenuItem::TYPE_DISPLAY['text']:
                    $label = Module::t($text);
                    break;
                default:
                    $label = $this->icon . Module::t($text);
                    break;
            }
        } else {
            $label = $this->icon . Module::t($text);
        }
        return $label;
    }
}
