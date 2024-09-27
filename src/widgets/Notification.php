<?php

namespace portalium\notification\widgets;

use portalium\menu\models\MenuItem;
use portalium\notification\models\Notification as NotificationModel;
use yii\base\Widget;
use portalium\notification\Module;
use Yii;
use yii\helpers\Html;
use portalium\theme\widgets\Nav;

class Notification extends Widget
{
    public $options;
    public $display;
    public $icon;
    public $placement;
    public $style;

    //initialize widget properties
    public function init()
    {
        if (!$this->icon) {
            $this->icon = Html::tag('i', '', ['class' => 'fa fa-bell', 'style' => 'margin-right: 5px;']);
        }
        $this->style = '{"icon":"","color":"","iconSize":"","display":"3","childDisplay":"1", "placement":"default"}';
        $this->style = json_decode($this->style, true);

        $this->options['class'] = 'placementWidget';
        if($this->placement == 'top-to-bottom'){
            $this->options['data-bs-placement'] = $this->placement; 
            $this->registerCss();

        }if($this->placement == 'side-by-side'){
            $this->options['data-bs-placement'] = $this->placement; 
            $this->registerCss();
        }

        parent::init();
    }

    //contain the code that generates the rendering result of the widget
    public function run()
    {
        if (\Yii::$app->user->can('notificationWebDefaultIndexOwn'))
           {
            $notifications = NotificationModel::getUnreadNotifications();
         }
        else {
            $notifications = [];
        }

        if (isset($this->options['class'])) {
            $this->options['class'] .= ' dropdown-menu notify-drop';
        } else {
            $this->options['class'] = 'dropdown-menu notify-drop';
        }
        $this->registerCss();

        $menuItems[] = [
            'label' => $this->generateLabel("Notification"),
            'display' => $this->display,
            'placement'=> $this->placement,
        ];

        return $this->render('notifications', [
            'notifications' => $notifications,
            'options' => $this->options,
            'label' => $this->generateLabel("Notification"),
            'placement'=>$this->placement,
            'display' => $this->display,
            'items' => $menuItems,
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
                    $label = $this->icon . Module::t($text);
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

    private function registerCss()
    {
        $css = <<<CSS
    .placementWidget[data-bs-placement="side-by-side"] {
    }
    .placementWidget[data-bs-placement="top-to-bottom"] li a i {
     display: block;
     flex-direction: column; 
     align-items: center;
    }
    CSS;
        $this->getView()->registerCss($css);
    }
}