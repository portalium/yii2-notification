<?php

class m220219_222513_notification_rbac
{
    public function up(){

        $auth = Yii::$app->authManager;

        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');

        $notificationWebDefaultIndex = $auth->createPermission('notificationWebDefaultIndex');
        $notificationWebDefaultIndex->description = 'Notification Web Default Index';
        $auth->add($notificationWebDefaultIndex);
        $auth->addChild($admin, $notificationWebDefaultIndex);

        $notificationWebDefaultCreate = $auth->createPermission('notificationWebDefaultCreate');
        $notificationWebDefaultCreate->description = 'Notification Web Default Create';
        $auth->add($notificationWebDefaultCreate);
        $auth->addChild($admin, $notificationWebDefaultCreate);

        $notificationWebDefaultDelete = $auth->createPermission('notificationWebDefaultDelete');
        $notificationWebDefaultDelete->description = 'Notification Web Default Delete';
        $auth->add($notificationWebDefaultDelete);
        $auth->addChild($admin, $notificationWebDefaultDelete);

        $notificationWebDefaultUpdate = $auth->createPermission('notificationWebDefaultUpdate');
        $notificationWebDefaultUpdate->description = 'Notification Web Default update';
        $auth->add($notificationWebDefaultUpdate);
        $auth->addChild($admin, $notificationWebDefaultUpdate);

        $notificationWebDefaultView = $auth->createPermission('notificationWebDefaultView');
        $notificationWebDefaultView->description = 'Notification Web Default update';
        $auth->add($notificationWebDefaultView);
        $auth->addChild($admin, $notificationWebDefaultView);

        $notificationWebDefaultTypeShow = $auth->createPermission('notificationWebDefaultTypeShow');
        $notificationWebDefaultTypeShow->description = 'Notification Web Default Type show';
        $auth->add($notificationWebDefaultTypeShow);
        $auth->addChild($admin, $notificationWebDefaultTypeShow);
    }


    public function down(){

        $auth = Yii::$app->authManager;
        $auth->remove($auth->getPermission('notificationWebDefaultIndex'));
        $auth->remove($auth->getPermission('notificationWebDefaultCreate'));
        $auth->remove($auth->getPermission('notificationWebDefaultDelete'));
        $auth->remove($auth->getPermission('$notificationWebDefaultUpdate'));
        $auth->remove($auth->getPermission('notificationWebDefaultView'));
    }
}