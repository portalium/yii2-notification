<?php

use yii\db\Migration;

class m261029_000001_notification_api_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $permissionNames = [
            'notificationApiDefaultIndex',
            'notificationApiDefaultView',
            'notificationApiDefaultCreate',
            'notificationApiDefaultUpdate',
            'notificationApiDefaultDelete',
            'notificationApiDefaultSend',
            'notificationApiDefaultSendAll',
        ];
        foreach ($permissionNames as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = ucfirst(str_replace('notification', '', $permissionName));
            $auth->add($permission);
            $auth->addChild($admin, $permission);
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $permissionNames = [
            'notificationApiDefaultIndex',
            'notificationApiDefaultView',
            'notificationApiDefaultCreate',
            'notificationApiDefaultUpdate',
            'notificationApiDefaultDelete',
            'notificationApiDefaultSend',
            'notificationApiDefaultSendAll',
        ];
        foreach ($permissionNames as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null) {
                $auth->remove($permission);
            }
        }
    }
}
