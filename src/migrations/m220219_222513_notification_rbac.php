<?php

class m220219_222513_notification_rbac
{
    public function up(){

        $auth = Yii::$app->authManager;

        $role1 = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role1) && $role1 != '') ? $auth->getRole($role1) : $auth->getRole('admin');

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


        $notificationWebDefaultUpdate = $auth->createPermission('$notificationWebDefaultUpdate');
        $notificationWebDefaultUpdate->description = 'Notification Web Default update';
        $auth->add($notificationWebDefaultUpdate);
        $auth->addChild($admin, $notificationWebDefaultUpdate);


        $notificationWebDefaultIndexOwn = $auth->createPermission('$notificationWebDefaultIndexOwn');
        $notificationWebDefaultIndexOwn->description = 'Notification Web Default Index Own';
        $auth->add($notificationWebDefaultIndexOwn);
        $auth->addChild($admin, $notificationWebDefaultIndexOwn);


        $notificationWebDefaultDeleteOwn = $auth->createPermission('$notificationWebDefaultDeleteOwn');
        $notificationWebDefaultDeleteOwn->description = 'Notification Web Default Delete Own';
        $auth->add($notificationWebDefaultDeleteOwn);
        $auth->addChild($admin, $notificationWebDefaultDeleteOwn);
    }


    public function down(){

        $auth = Yii::$app->authManager;
        $auth->remove($auth->getPermission('notificationWebDefaultIndex'));
        $auth->remove($auth->getPermission('notificationWebDefaultCreate'));
        $auth->remove($auth->getPermission('notificationWebDefaultDelete'));
        $auth->remove($auth->getPermission('$notificationWebDefaultUpdate'));
        $auth->remove($auth->getPermission('$notificationWebDefaultIndexOwn'));
        $auth->remove($auth->getPermission('$notificationWebDefaultDeleteOwn'));

    }
}