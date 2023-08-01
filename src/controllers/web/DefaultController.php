<?php

namespace portalium\notification\controllers\web;

use portalium\notification\models\Notification;
use portalium\notification\models\Notification as notificationModel;
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
        $searchModel = new NotificationSearch;
        $dataProvider = $searchModel->search($this->request->queryParams);
        if(!\Yii::$app->user->can('notificationWebDefaultIndex'))
            $dataProvider->query->andWhere(['id_to'=>\Yii::$app->user->id]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        if (!\Yii::$app->user->can('notificationWebDefaultView' ,['model'=>notificationModel::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        return $this->render('view', [
                'model'=> notificationModel::findModel($id),
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
        if (!\Yii::$app->user->can('notificationWebDefaultUpdate' , ['model'=> notificationModel::findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = notificationModel::findModel($id);

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
        if(!\Yii::$app->user->can('notificationWebDefaultDelete' , ['model'=>notificationModel::findModel($id)]))
        {
            throw new \yii\web\ForbiddenHttpException( Module::t('You are not allowed to access this page.'));
        }

        if(notificationModel::findModel($id)->delete()){
            Yii::$app->session->addFlash('info', Module::t('Notification has been deleted'));
        }
        return $this->redirect(['index']);
    }
}
