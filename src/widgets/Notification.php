<?php

namespace portalium\notification\widgets;

use portalium\menu\models\MenuItem;
use portalium\notification\models\Notification as notificationModel;
use yii\base\Widget;
use portalium\notification\Module;
use portalium\theme\widgets\Nav;
use yii\helpers\Html;
use Yii;

class Notification extends Widget
{
    public $options;
    public $display;
    public $icon;

    //initialize widget properties
    public function init()
    {
//        if (count(NotificationModel::find()->where([ 'id_to'  => Yii::$app->user->id])->all()) > 0)
//        {
//            $this->icon = Html::beginTag('i', ['class' => "fa fa-bell", 'style' => 'margin-right: 5px;']).'<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
//            '.count(NotificationModel::find()->where(["id_to"  => Yii::$app->user->id])->all()).'
//            <span class="visually-hidden">unread messages</span>
//            </span>'.Html::endTag("i");
//        }
//        else
//        {
//            $this->icon = Html::tag('i', '', ['class' => 'fa fa-bell-slash', 'style' => 'margin-right: 5px;']);
//        }

        $this->display = MenuItem::TYPE_DISPLAY['icon'];
        parent::init();
    }

    //contain the code that generates the rendering result of the widget
    public function run()
    {
        $notificationItems = [];

        if(\Yii::$app->user->can('notificationWebDefaultIndex '))
        {
            $notifications = notificationModel::find()->all();
        }
        else
        {
            $notifications= NotificationModel::find()->where([ 'id_to'  => Yii::$app->user->id])->all();
        }

        foreach ($notifications as $value){
            $notificationItems[] = [
                'label' => Module::t($value->title),
                'url' => ['/notification/default/index'],
            ];
        }

        $menuItems[] = [
            //'label' => "Notifications",
            'label' => $this->generateLabel("Notification"),
            'items' => $notificationItems,
            'display' => $this->display,
        ];


        return $this->render('notifications', [
            'notifications' => $notifications,
            'options' => $this->options,
            'items' => $menuItems,
        ]);

//);
//        return Nav::widget([
//            'options' => $this->options,
//            'items' => $menuItems,
//        ]);

    }


    private function generateLabel($text)
    {
        $label = "";
        if(isset($this->display)){
            switch ($this->display) {
                case MenuItem::TYPE_DISPLAY['icon']:
                    $label = $this->icon;
                    break;
                case MenuItem::TYPE_DISPLAY['icon-text']:
                    $label = $this->icon . \portalium\workspace\Module::t($text);
                    break;
                case MenuItem::TYPE_DISPLAY['text']:
                    $label = Module::t($text);
                    break;
                default:
                    $label = $this->icon . Module::t($text);
                    break;
            }
        }else{
            $label = $this->icon . Module::t($text);
        }

        return $label;
    }
}