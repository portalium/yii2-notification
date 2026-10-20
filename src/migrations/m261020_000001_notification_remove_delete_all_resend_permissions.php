<?php

use yii\db\Migration;

class m261020_000001_notification_remove_delete_all_resend_permissions extends Migration
{
    public function up()
    {
        $auth = \Yii::$app->authManager;
        $deleteAll = $auth->getPermission('notificationWebDefaultDeleteAll');
        if ($deleteAll !== null) {
            $auth->remove($deleteAll);
        }
        $resend = $auth->getPermission('notificationWebDefaultResend');
        if ($resend !== null) {
            $auth->remove($resend);
        }
    }

    public function down()
    {
        $auth = \Yii::$app->authManager;
        $admin = $auth->getRole('admin');

        $deleteAll = $auth->createPermission('notificationWebDefaultDeleteAll');
        $deleteAll->description = 'Notification Web Default Delete All';
        $auth->add($deleteAll);
        if ($admin !== null) {
            $auth->addChild($admin, $deleteAll);
        }

        $resend = $auth->createPermission('notificationWebDefaultResend');
        $resend->description = 'Notification Web Default Resend';
        $auth->add($resend);
        if ($admin !== null) {
            $auth->addChild($admin, $resend);
        }
    }
}
