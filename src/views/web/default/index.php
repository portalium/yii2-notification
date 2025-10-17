<?php

use portalium\notification\models\Notification;
use portalium\notification\Module;
use portalium\theme\widgets\ActionColumn;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\GridView;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Panel;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var portalium\notification\models\NotificationSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Module::t('Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
/*
echo $this->render('@vendor/portalium/yii2-notification/src/widgets/views/notification', [
    'notifications' => $notifications
]); */
?>
<?php $form = ActiveForm::begin([
    'action' => ['default/delete-all'],
    'method' => 'post',
]);
?>

<?php
Panel::begin([
    'title' => Module::t('Notification'),
    'actions' => [
        'header' => [
            Html::submitButton(Module::t(''), [
                'class' => 'fa fa-trash btn btn-danger',
                'id' => 'delete-select',
                'title' => Module::t('Delete All'),
                'data' => [
                    'confirm' => Module::t('If you continue, all your data will be reset. Do you want to continue?'),
                    'method' => 'post',

                ]
            ]),
            Html::a(Module::t(''), ['create'], ['class' => 'fa fa-plus btn btn-success', 'title' => Module::t('Create')]),
        ]
    ]
]) ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => SerialColumn::class],

        'user.username',
        [
            'attribute' => 'text',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->text;
            }
        ],
        'title',
        [
            'attribute' => 'status',
            'value' => function ($model) {
                return Notification::getStatusList()[$model->status];
            },
            'filter' => Notification::getStatusList()

        ],
        [
            'class' => ActionColumn::class,
            'header' => Module::t('Actions'),
            'template' => '{view} {update} {delete} {resend}',
            'buttons' => [
                'urlCreator' => function ($action, Notification $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id_notification' => $model->id_notification]);
                },
                'resend' => function ($url, $model, $key) {
                    return Html::button('<i class="fa fa-paper-plane"></i>', [
                        'title' => Module::t('Resend'),
                        'class' => 'btn btn-success btn-xs open-resend-modal',
                        'style' => 'padding: 2px 9px 2px 9px; display: inline-block;',
                        'data-id' => $model->id_notification,
                    ]);
                },
            ]
        ],
    ],
]); ?>
<?php Panel::end();
ActiveForm::end();

Modal::begin([
    'id' => 'resend-modal',
    'title' => Module::t('Resend Notification'),
    'footer' => Html::submitButton(Module::t('Send'), [
        'class' => 'btn btn-success',
        'form' => 'resend-form',
    ]),
    'bodyOptions' => ['style' => 'padding:20px;'],
]);
?>

<?= Html::beginForm(['/notification/default/resend'], 'post', ['id' => 'resend-form']) ?>
    <?= Html::hiddenInput('id_notification', '', ['id' => 'resend-id']) ?>
    <?= Html::checkboxList('channels', [], [
        'on_site' => Module::t('On Site'),
        'email' => Module::t('Email'),
        'push' => Module::t('Push Notification'),
    ]) ?>
<?= Html::endForm() ?>

<?php Modal::end(); ?>

<?php

$this->registerJS(
    <<<JS
    $(document).on('click', '.open-resend-modal', function() {
        var id = $(this).data('id');
        $('#resend-id').val(id);
        $('#resend-modal').modal('show');
    });
JS
);
