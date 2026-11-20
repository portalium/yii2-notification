<?php

use portalium\notification\rbac\OwnRule;
use yii\db\Migration;

class m261029_000002_notification_api_own_rbac extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;
        $rule = $auth->getRule('notificationOwnRule');
        if ($rule === null) {
            $rule = new OwnRule();
            $auth->add($rule);
        }
        $role = Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');
        $user = $auth->getRole('user');

        $permissionsName = [
            'notificationApiDefaultViewOwn',
            'notificationApiDefaultUpdateOwn',
            'notificationApiDefaultDeleteOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $permission->ruleName = $rule->name;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
            if ($user !== null) {
                $auth->addChild($user, $permission);
            }
            $childPermission = $auth->getPermission(str_replace('Own', '', $permissionName));
            $auth->addChild($permission, $childPermission);
        }

        $permissionsName = [
            'notificationApiDefaultIndexOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            if ($auth->getPermission($permissionName) !== null) {
                continue;
            }
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;
            $auth->add($permission);
            $auth->addChild($admin, $permission);
            if ($user !== null) {
                $auth->addChild($user, $permission);
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $permissionsName = [
            'notificationApiDefaultViewOwn',
            'notificationApiDefaultUpdateOwn',
            'notificationApiDefaultDeleteOwn',
            'notificationApiDefaultIndexOwn',
        ];
        foreach ($permissionsName as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null) {
                $auth->remove($permission);
            }
        }
    }
}
