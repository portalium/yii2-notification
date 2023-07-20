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

        $notificationWebDefaultIndexOwn = $auth->createPermission('notificationWebDefaultIndexOwn');
        $notificationWebDefaultIndexOwn->description = 'Notification Web Default Index Own';
        $auth->add($notificationWebDefaultIndexOwn);
        $auth->addChild($admin, $notificationWebDefaultIndexOwn);
        $notificationWebDefaultIndexOwn->ruleName = $rule->name;
        $notificationWebDefaultIndex = $auth->getPermission('$notificationWebDefaultIndex');
        $auth->addChild($notificationWebDefaultIndexOwn, $notificationWebDefaultIndex);


        $notificationWebDefaultViewOwn=$auth->createPermission('notificationWebDefaultViewOwn');
        $notificationWebDefaultViewOwn->description='Notification Web Default View Own';
        $auth->add($notificationWebDefaultIndexOwn);
        $auth->addChild($admin, $notificationWebDefaultViewOwn);
        $notificationWebDefaultViewOwn->ruleName = $rule->name;
        $notificationWebDefaultIndex = $auth->getPermission('$notificationWebDefaultIndex');
        $auth->addChild($notificationWebDefaultIndexOwn, $notificationWebDefaultIndex);


        $notificationWebDefaultUpdateOwn=$auth->createPermission('notificationWebDefaultUpdateOwn');
        $notificationWebDefaultUpdateOwn->description='Notification Web Default Update Own';
        $auth->add($notificationWebDefaultUpdateOwn);
        $auth->addChild($admin, $notificationWebDefaultUpdateOwn);
        $notificationWebDefaultUpdateOwn->ruleName = $rule->name;
        $notificationWebDefaultUpdate = $auth->getPermission('$notificationWebDefaultUpdate');
        $auth->addChild($notificationWebDefaultUpdateOwn, $notificationWebDefaultUpdate);


        $notificationWebDefaultDeleteOwn=$auth->createPermission('notificationWebDefaultDeleteOwn');
        $notificationWebDefaultDeleteOwn->description='Notification Web Default Delete Own';
        $auth->add($notificationWebDefaultDeleteOwn);
        $auth->addChild($admin, $notificationWebDefaultDeleteOwn);
        $notificationWebDefaultDeleteOwn->ruleName = $rule->name;
        $notificationWebDefaultDelete = $auth->getPermission('$notificationWebDefaultDelete');
        $auth->addChild($notificationWebDefaultDeleteOwn, $notificationWebDefaultDelete);
    }

}