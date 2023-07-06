<?php

namespace portalium\notification\controllers\web;

use portalium\menu\Module;
use portalium\notification\models\Notification;
use portalium\notification\models\NotificationSearch;
use portalium\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DefaultController implements the CRUD actions for Notification model.
 */
class DefaultController extends Controller
{
    /**
     * @inheritDoc
     */
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

    /**
     * Lists all Notification models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new NotificationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Notification model.
     * @param int $id_notification Id Notification
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id_notification)
    {
        //tüm bildirimleri görme yetkisi var mı kontrol
        if(\Yii::$app->user->can('notificationWebDefaultIndex '))
        {
            return $this->render('view', [
                'model' => $this->findModel($id_notification),
            ]);
        }

        //sadece kendi bildirimlerini görmeye yetkisi varsa
        elseif(\Yii::$app->user->can('notificationWebDefaultIndexOwn'))
        {
            //sisteme giriş yapan kullancının bildirimini notification değişkenine atadım
            $notification= Notification::find()->where([ 'id_to'  => Yii::$app->user->id,'id_notification'=>$id_notification ])->one();

            //eğer kendine ait bildirim varsa
            if($notification)
            {
                //bildirimi görüntüle
                return $this->render('view', [
                    'model'=>$notification,
                ]);
            }

        }

        //bildirim görüntüleme yetkisi yoksa
        else
        {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
    }

    /**
     * Creates a new Notification model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        //mesaj oluşturmaya yetkin var mı yok mu kontrol ediyor.
        if (!\Yii::$app->user->can('notificationWebDefaultCreate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Notification();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id_notification' => $model->id_notification]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Notification model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id_notification Id Notification
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */

    //hangi mesajı güncelleyeceksin id'si ile bul
    public function actionUpdate($id_notification)
    {
        //update işlemi için yetkin var mı yok mu kontrol ediliyor yoksa exception fırlatılıyor
        if (!\Yii::$app->user->can('notificationWebDefaultUpdate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        //hangi satırı güncelleyeceksen o satırı bul ve modele ata
        $model = $this->findModel($id_notification);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id_notification' => $model->id_notification]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Notification model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id_notification Id Notification
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id_notification)
    {

        //silme yetkin var mı kontrol ediliyor varsa istediğin kullanıcının bildirimini silebiilirsin
        if(\Yii::$app->user->can('notificationWebDefaultDelete'))
        {
            $this->findModel($id_notification)->delete();
            return $this->redirect(['index']);
        }
        //sadece kendi bildirimini silebilsin
        else if(\Yii::$app->user->can('notificationWebDefaultDeleteOwn'))
        {
            //sisteme giriş yapan kullancının bildirimlerini notifications değişkenine atadım
            $notification= Notification::find()->where([ 'id_to'  => Yii::$app->user->id , 'id_notification'=>$id_notification])->one();

            //eğer kendine ait bir bildirim varsa
            if($notification)
            {
               $notification->delete();
                return $this->redirect(['index']);
            }
        }
        //kullanıcı hiçbir izne sahip değilse exception yollanıyor
        else
        {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

    }

    /**
     * Finds the Notification model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id_notification Id Notification
     * @return Notification the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id_notification)
    {
        if (($model = Notification::findOne(['id_notification' => $id_notification])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
