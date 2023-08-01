<?php

namespace portalium\site\components;

use yii\base\Component;
use portalium\user\models\User;

class Notification extends Component
{

    public function addNotification($id_to,$text,$title)
    { 
        $model = new Notification();
        $model->load();
        $model->save();
    }

    public function denemegetNotification($id_user)  
    {
        $notifications = Notification::findAll($id_user);
        return $notifications;
    }

    public function getNotification($id_user)  
    {
        $modelUser = User::findOne(['id_user' => $id_user]);  // modelUser değişkenine User tablosundan modeli bulup atadık

        $user_grupları = $modelUser->getGroups(); // user_grupları değişkenine dizi şeklinde 

        var_dump($user_grupları);
        exit;

        $bildirimler = Notification::find()->where(/*yazılacak*/)->all();  // tüm bildirimleri aldık


        foreach($bildirim as $notifications)
        {
            if($notifications->$id_to == $id_user && $notifications->$type == '1') 
            {
                return $notifications;
            }
            else if($notifications->$type == '2') //user'id o grupta var mı diye bak ve notificationu döndür
            {

            }

        }
    }

}
?>