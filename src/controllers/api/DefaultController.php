<?php

namespace portalium\notification\controllers\api;

use portalium\openapi\attributes as Doc;
use portalium\notification\Module;
use portalium\notification\models\NotificationForm;
use portalium\notification\models\NotificationSearch;
use portalium\rest\ActiveController as RestActiveController;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * Bildirimler. Kullanıcı yalnızca kendine gelenleri görür; Own izniyle güncellemede yalnızca
 * status (okundu) değişir. Toplu gönderim (send) izne bağlıdır, tüm kullanıcılara gönderim
 * ayrıca notificationApiDefaultSendAll ister.
 *
 * Bildirimin çalışma alanı yoktur (alıcıya, id_to'ya bağlıdır); bu yüzden erişimde
 * workspace katmanı değil, yalnızca global ve Own izinleri kullanılır.
 */
#[Doc\Tag('Notifications', 'In-app notifications of the signed-in user.')]
#[Doc\Operation(
    action: 'index',
    audience: Doc\Operation::PUBLIC,
    summary: 'List my notifications',
    description: 'Newest first. A user sees only the notifications sent to them; `notificationApiDefaultIndex` lists everyone\'s.',
)]
#[Doc\Operation(
    action: 'view',
    audience: Doc\Operation::PUBLIC,
    summary: 'View a notification',
    description: 'Only the receiver, or a user with `notificationApiDefaultView`, can view it.',
)]
#[Doc\Operation(
    action: 'update',
    audience: Doc\Operation::PUBLIC,
    summary: 'Mark a notification as read',
    description: 'Send `status: 1` to mark it as read, `0` to mark it unread. The receiver can change only `status`; changing other fields needs '
        . '`notificationApiDefaultUpdate`.',
)]
#[Doc\Body(action: 'update', fields: ['status' => ['type' => 'integer', 'enum' => [0, 1], 'description' => '1: read, 0: unread']], required: ['status'])]
#[Doc\Operation(
    action: 'delete',
    audience: Doc\Operation::PUBLIC,
    summary: 'Delete a notification',
    description: 'Only the receiver, or a user with `notificationApiDefaultDelete`, can delete it.',
)]
#[Doc\Operation(
    action: 'create',
    summary: 'Create a notification for one user',
    description: 'Writes a notification for `id_to`. To reach many users at once use the send endpoint.',
)]
#[Doc\Permission('notificationApiDefaultCreate', action: 'create')]
class DefaultController extends RestActiveController
{
    use \portalium\traits\RecordAccessTrait;

    public $modelClass = 'portalium\notification\models\Notification';

    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['send'] = ['POST'];
        return $verbs;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['dataFilter'] = [
            'class' => \yii\data\ActiveDataFilter::class,
            'searchModel' => NotificationSearch::class,
        ];
        $actions['index']['prepareSearchQuery'] = function ($query) {
            if (!Yii::$app->user->can('notificationApiDefaultIndex')) {
                $query->andWhere(['id_to' => Yii::$app->user->id]);
            }
            return $query->orderBy(['id_notification' => SORT_DESC]);
        };
        return $actions;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        switch ($action->id) {
            case 'index':
                $allowed = Yii::$app->user->can('notificationApiDefaultIndex')
                    || Yii::$app->user->can('notificationApiDefaultIndexOwn');
                break;
            case 'create':
                $allowed = Yii::$app->user->can('notificationApiDefaultCreate');
                break;
            case 'send':
                $allowed = Yii::$app->user->can('notificationApiDefaultSend')
                    || Yii::$app->user->can('notificationApiDefaultSendAll');
                break;
            default:
                $allowed = true;
                break;
        }
        if (!$allowed) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if ($action->id === 'update' && !Yii::$app->user->can('notificationApiDefaultUpdate')) {
            $this->stripBodyParams(['id_notification', 'id_to', 'title', 'text']);
        }
        return true;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        $map = [
            'view' => 'notificationApiDefaultView',
            'update' => 'notificationApiDefaultUpdate',
            'delete' => 'notificationApiDefaultDelete',
        ];
        if (!isset($map[$action])) {
            return;
        }
        if (
            $model === null || (!Yii::$app->user->can($map[$action])
            && !Yii::$app->user->can($map[$action] . 'Own', ['model' => $model]))
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
    }

    /**
     * Toplu gönderim. Gövde: title, text, notificationType (user|role|group|all ya da 1-4),
     * receiver_id (all dışında zorunlu: kullanıcı kimlikleri, rol adları ya da grup kimlikleri),
     * send_email, send_push.
     */
    #[Doc\Operation(
        summary: 'Send a notification to many users',
        description: 'Writes one notification per active user among the receivers and, if asked, also sends it by email and push. Returns 422 when no '
            . 'active user '
            . 'matches.',
    )]
    #[Doc\Body(fields: [
        'title' => 'string - At most 255 characters',
        'text' => 'string - At most 255 characters',
        'notificationType' => 'enum:user,role,group,all - Who receives it (1-4 are also accepted)',
        'receiver_id' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'User ids, role names or group ids; not used with `all`'],
        'send_email' => 'boolean - Also send by email',
        'send_push' => 'boolean - Also send a push notification to the users\' devices',
    ], required: ['title', 'text', 'notificationType'])]
    #[Doc\Response(200, 'Notifications created.', fields: [
        'count' => 'integer - Users notified',
        'email' => [
            'type' => 'object',
            'properties' => ['sent' => ['type' => 'integer'], 'failed' => ['type' => 'integer']],
            'description' => 'Only with send_email',
        ],
        'push' => ['type' => 'object', 'description' => 'Only with send_push: device totals or the error'],
    ])]
    #[Doc\Response(422, 'Invalid input, or no active user among the receivers.', ref: 'ValidationErrors')]
    #[Doc\Permission('notificationApiDefaultSend', note: 'notificationType user, role or group')]
    #[Doc\Permission('notificationApiDefaultSendAll', note: 'notificationType all')]
    public function actionSend()
    {
        $form = new NotificationForm();
        $form->load(Yii::$app->request->getBodyParams(), '');
        if (!$form->validate()) {
            return $form;
        }

        $permission = $form->isAll() ? 'notificationApiDefaultSendAll' : 'notificationApiDefaultSend';
        if (!Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $result = $form->send();
        return $result === false ? $form : $result;
    }
}
