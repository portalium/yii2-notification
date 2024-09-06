<?php

namespace portalium\notification\components;

use yii\base\Component;
use portalium\notification\models\Notification as NotificationModel;
use portalium\user\models\User;

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
}