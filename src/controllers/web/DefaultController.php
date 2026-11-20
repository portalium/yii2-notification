<?php

namespace portalium\notification\controllers\web;

use portalium\notification\models\Notification;
use portalium\notification\models\NotificationSearch;
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
                        'read' => ['POST'],
                        'read-all' => ['POST'],
                        'delete-selected' => ['POST'],
                        'read-selected' => ['POST'],
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

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionRead($id)
    {
        $saved = false;
        if ($model = Notification::findModel($id)) {
            if ((int) $model->id_to !== (int) Yii::$app->user->id && !Yii::$app->user->can('notificationWebDefaultUpdate')) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
            }
            $model->status = Notification::STATUS_READ;
            $saved = $model->save();
        }
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => $saved]);
        }
        return $this->redirect(['index']);
    }

    public function actionReadAll()
    {
        $count = Notification::markAllRead((int) Yii::$app->user->id);
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => true, 'count' => $count]);
        }
        return $this->redirect(['index']);
    }

    /**
     * Marks the checked notifications read (panel bar and the row's button); ids of other users are skipped.
     */
    public function actionReadSelected()
    {
        $marked = 0;
        foreach (Notification::find()->where(['id_notification' => $this->selectedIds()])->all() as $model) {
            if ((int) $model->id_to !== (int) Yii::$app->user->id && !Yii::$app->user->can('notificationWebDefaultUpdate')) {
                continue;
            }
            if ((int) $model->status === Notification::STATUS_UNREAD) {
                $model->status = Notification::STATUS_READ;
                if ($model->save(false, ['status'])) {
                    $marked++;
                }
            }
        }
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => true, 'count' => $marked, 'unread' => Notification::countUnread()]);
        }
        return $this->redirect(['index']);
    }

    /**
     * Deletes the checked notifications (panel and list); ids the user may not delete are skipped.
     */
    public function actionDeleteSelected()
    {
        $deleted = 0;
        foreach (Notification::find()->where(['id_notification' => $this->selectedIds()])->all() as $model) {
            if (Yii::$app->user->can('notificationWebDefaultDelete', ['model' => $model]) && $model->delete()) {
                $deleted++;
            }
        }
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => true, 'count' => $deleted, 'unread' => Notification::countUnread()]);
        }
        if ($deleted > 0) {
            Yii::$app->session->addFlash('info', Module::t('{n} notifications have been deleted', ['n' => $deleted]));
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
        $url = $notification->hasAttribute('url') ? $notification->url : null;
        if (Notification::isLocalUrl($url)) {
            return $this->redirect($url);
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

        $model = new Notification();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id_notification]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultUpdate', ['model' => Notification::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = Notification::findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', Module::t('Notification has been updated'));
            return $this->redirect(['view', 'id' => $model->id_notification]);
        }

        return $this->render('update', [
            'model' => $model,
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

    /**
     * Ids posted as selection[]; an empty selection matches no record.
     */
    private function selectedIds(): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->post('selection', []))));

        return $ids === [] ? [0] : $ids;
    }
}
