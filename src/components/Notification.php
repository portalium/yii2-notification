<?php

namespace portalium\notification\components;

use Yii;
use yii\base\Component;
use portalium\user\models\User;
use portalium\notification\models\Notification as NotificationModel;

class Notification extends Component
{

    public function addNotification($id_to, $title, $text)
    { 
        if($id_to == null || User::findOne($id_to) == null)
            return;

        $model = new NotificationModel();
        $model->id_to = $id_to;
        $model->text = $text;
        $model->title = $title;
        $model->save();
    }

    public function sendEmail($user, $text, $title)
    {
        Yii::$app->site->mailer->setViewPath(Yii::getAlias('@portalium/notification/mail'));
        return Yii::$app
            ->site
            ->mailer
            ->compose(
                ['html' => 'notificationEmail-html', 'text' => 'notificationEmail-text'],
                ['user' => $user,'text'=> $text, 'title'=> $title]                 
                )
            ->setFrom([Yii::$app->setting->getValue('email::address') => Yii::$app->setting->getValue('email::displayname')])
            ->setTo($user->email)
            ->setSubject('Notification ' .  Yii::$app->setting->getValue('app::title'))
            ->send();
             
    }
}