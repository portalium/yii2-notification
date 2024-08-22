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
    public $placement;

    //initialize widget properties
    public function init()
    {
        if (!$this->icon) {
            $this->icon = Html::tag('i', '', ['class' => 'fa fa-bell', 'style' => 'margin-right: 5px;']);
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
         //}
        if (\Yii::$app->user->can('notificationWebDefaultIndexOwn'))
           {
            $notifications = NotificationModel::getUnreadNotifications();
            //var_dump(count($notifications)); 
         }
        else {
            $notifications = [];
        }

        if (isset($this->options['class'])) {
            $this->options['class'] .= ' dropdown-menu notify-drop';
        } else {
            $this->options['class'] = 'dropdown-menu notify-drop';
        }
        return $this->render('notifications', [
            'notifications' => $notifications,
            'options' => $this->options,
            'label' => $this->generateLabel("Notification")

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
