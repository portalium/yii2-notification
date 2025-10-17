<?php

namespace portalium\notification\controllers\web;

use portalium\notification\models\Notification;
use portalium\notification\models\NotificationForm;
use portalium\notification\models\NotificationSearch;
use portalium\user\models\User;
use portalium\web\Controller;
use portalium\notification\Module;
use yii\filters\VerbFilter;
use Yii;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }
    public function actionIndex()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultIndex') && !\Yii::$app->user->can('notificationWebDefaultIndexOwn')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $searchModel = new NotificationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        if (!\Yii::$app->user->can('notificationWebDefaultIndex'))
            $dataProvider->query->andWhere(['id_to' => \Yii::$app->user->id]);

        $notifications = $dataProvider->getModels();
        //var_dump(count($notifications));
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'notifications' => $notifications,
        ]);
    }

    public function actionRead($id)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultRead', ['model' => Notification::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if ($model = Notification::findModel($id)) {
            $model->status = Notification::STATUS_READ;
            $model->save();
        }
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => true]);
        }
        return $this->redirect(['index']);
    }

    public function actionView($id)
    {
        $notification = Notification::findModel($id);
        if (!\Yii::$app->user->can('notificationWebDefaultView', ['model' => Notification::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        if ($notification->status == Notification::STATUS_UNREAD) {
            $notification->status = Notification::STATUS_READ;
            $notification->save();
        }
        return $this->render('view', [
            'model' => $notification,
        ]);
    }

    public function actionCreate()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultCreate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $notificationForm = new NotificationForm();
        $model = new Notification();
        if ($this->request->isPost) {
            if ($notificationForm->load($this->request->post())) {
                $users = $notificationForm->getUserList();
                if (empty($users)) {
                    $model->loadDefaultValues();
                    return $this->render('create', ['notificationForm' => $notificationForm]);
                }
                foreach ($users as $user) {
                    $model = new Notification();
                    $model->id_to = (int) $user['id_user'];
                    $model->text = $notificationForm->text;
                    $model->title = $notificationForm->title;
                    if ($model->save()) {
                        if ($notificationForm->send_email) {
                            $userEmail = User::findOne($model->id_to);
                            Yii::$app->notification->sendEmail($userEmail, $model->text, $model->title);
                        }
                    }
                }
            }
            return $this->redirect(['index']);
        } else {
            $model->loadDefaultValues();
        }
        return $this->render('create', [
            'notificationForm' => $notificationForm,
        ]);
    }

    public function actionUpdate($id)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultUpdate', ['model' => Notification::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = Notification::findModel($id);
        $notificationForm = new NotificationForm();
        $notificationForm->text = $model->text;
        $notificationForm->title = $model->title;

        if ($notificationForm->load($this->request->post())) {
            $model->title = $notificationForm->title;
            $model->text = $notificationForm->text;

            if ($model->save()) {
                Yii::$app->session->addFlash('success', Module::t('Notification has been updated'));
                return $this->redirect(['view', 'id' => $model->id_notification]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'notificationForm' => $notificationForm,
        ]);
    }

    public function actionDelete($id)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultDelete', ['model' => Notification::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if (Notification::findModel($id)->delete()) {
            Yii::$app->session->addFlash('info', Module::t('Notification has been deleted'));
        }
        return $this->redirect(['index']);
    }

    public function actionDeleteAll()
    {
        $deletedCount = 0;

        foreach (Notification::find()->all() as $notification)
            if (\Yii::$app->user->can('notificationWebDefaultDeleteAll') && $notification->delete())
                $deletedCount++;
        Yii::$app->session->setFlash(
            $deletedCount ? 'success' : 'error',
            Module::t(
                $deletedCount ? "{count} notifications have been deleted." : "No notifications found to delete or you do not have permission.",
                ['count' => $deletedCount]
            )
        );

        return $this->redirect(['index']);
    }

    public function actionShowNotificationType()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultTypeShow')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $out = [];
        if ($this->request->isPost) {
            $request = $this->request->post('depdrop_parents');

            $notificationType = $request[0] ?? null;

            if (!$notificationType) {
                return $this->asJson(['output' => [], 'selected' => '']);
            } else {
                switch ($notificationType) {
                    case Notification::NOTIFICATION_TYPE_USER:
                        $notifications = Notification::getUserListNotification();
                        foreach ($notifications as $notification) {
                            $out[] = ['id' => (string)$notification['id_user'], 'name' => $notification['username']];
                        }
                        break;
                    case Notification::NOTIFICATION_TYPE_ROLE:
                        $notifications = Notification::getRolesList();
                        foreach ($notifications as $key => $notification) {
                            $out[] = ['id' => (string)$notification->name, 'name' => $notification->name,];
                        }
                        break;
                    case Notification::NOTIFICATION_TYPE_GROUP:
                        $notifications = Notification::getGroupList();
                        foreach ($notifications as $notification) {
                            $out[] = ['id' => (string)$notification['id_group'], 'name' => $notification['name']];
                        }
                        break;
                }
                return json_encode(['output' => $out, 'selected' => '']);
            }
        }
    }

    public function actionResend()
    {
        if (!\Yii::$app->user->can('notificationWebDefaultResend')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $notificationId = \Yii::$app->request->post('id_notification');
        $channels = \Yii::$app->request->post('channels', []);

        if ($notificationId && !empty($channels)) {
            $notification = Notification::findModel($notificationId);
            // Logic to resend the notification
            if (in_array('email', $channels)) {
                $userEmail = User::findOne($notification->id_to);
                Yii::$app->notification->sendEmail($userEmail, $notification->text, $notification->title);
            }

            if (in_array('push', $channels)) {
                Yii::$app->notification->sendPushToUserByNotification($notification);
            }
        }
        Yii::warning("Resend action called for notification ID: " . $notificationId . " via channels: " . implode(", ", $channels));

        return $this->redirect(['index']);
    }
}
