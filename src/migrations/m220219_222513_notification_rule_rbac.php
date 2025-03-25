<?php
use portalium\notification\rbac\OwnRule;
use yii\db\Migration;
class m220219_222513_notification_rule_rbac extends Migration
{

    public function up()
    {
        $auth = Yii::$app->authManager;

        $rule = new OwnRule();
        $auth->add($rule);
        $role = \Yii::$app->setting->getValue('site::admin_role');
        $admin = (isset($role) && $role != '') ? $auth->getRole($role) : $auth->getRole('admin');

        $permissionsName = [
            'notificationWebDefaultIndexOwn',
            'notificationWebDefaultViewOwn',
            'notificationWebDefaultDeleteOwn',
            'notificationWebDefaultUpdateOwn',
            'notificationWebDefaultDeleteAllOwn'
        ];

        foreach ($permissionsName as $permissionName)
        {
            $permission = $auth->createPermission($permissionName);
            $permission->description = $permissionName;

            if($permissionName!=='notificationWebDefaultIndexOwn'){
                $permission->ruleName = $rule->name;
                $auth->add($permission);
                $auth->addChild($admin, $permission);

            $childPermission = $auth->getPermission(str_replace('Own', '', $permissionName));
            $auth->addChild($permission, $childPermission);
            }
            else{
                $auth->add($permission);
                $auth->addChild($admin, $permission);
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $auth->removeChild($auth->getPermission('notificationWebDefaultIndexOwn'), $auth->getPermission('notificationWebDefaultIndex'));
        $auth->remove($auth->getPermission('notificationWebDefaultIndexOwn'));
        $auth->remove($auth->getPermission('notificationWebDefaultViewOwn'));
        $auth->remove($auth->getPermission('notificationWebDefaultDeleteOwn'));
        $auth->remove($auth->getPermission('notificationWebDefaultUpdateOwn'));
        $auth->remove($auth->getPermission('notificationWebDefaultDeleteAllOwn'));
    }

}