<?php

use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Panel;
use portalium\notification\Module;
use portalium\notification\models\Notification;
use portalium\notification\models\NotificationForm;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */
/** @var yii\widgets\ActiveForm $notificationForm */
?>


<?php $form = ActiveForm::begin(); ?>
<?php Panel::begin([
    'title' => Html::encode($this->title),
    'actions' => [
        'header' => [
        ],
        'footer' => [
            Html::submitButton(Module::t('Save'), ['class' => 'btn btn-success']),
        ]
    ],
]) ?>



<?php


echo $form->field($notificationForm, 'notificationType', ['options' => ['id' => 'module-list-div']])->dropDownList(Notification::getNotificationType(), ['id' => 'module-list', 'prompt' => Module::t('Select Notification Type')]);


echo $form->field($notificationForm, 'receiver_id', ['options' => ['id' => 'recipients-list-div']])->widget(DepDrop::classname(), [
    'options' => ['id' => 'recipients-list',
        'multiple' =>true],
    'type' => DepDrop::TYPE_SELECT2,
    'pluginOptions' => [
        'depends' => ['module-list'],
        'placeholder' => Module::t('Select...'),
        'url' => Url::to(['/notification/default/show-notification-type']),
        'paramsBase' => [
            Yii::$app->request->csrfParam => Yii::$app->request->csrfToken,
        ]
    ]
]);
?>
<?= $form->field($notificationForm, 'title')->textInput(['maxlength' => true]) ?>
<?= $form->field($notificationForm, 'text')->textInput(['maxlength' => true]) ?>

<?= $form->field($notificationForm, 'send_email')->checkbox(['label' => Module::t('Send as Email')]) ?>


<?php Panel::end() ?>
<?php ActiveForm::end(); ?>

<?php
$this->registerJs('
    $(document).ajaxSend(function(event, jqxhr, settings) {
        if (settings.type == "POST") {
            settings.data = settings.data + "&' . Yii::$app->request->csrfParam . '=' . Yii::$app->request->csrfToken . '";
        }
    });
');
?>