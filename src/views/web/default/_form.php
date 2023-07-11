<?php

use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Panel;
use portalium\notification\Module;
use portalium\notification\models\Notification;

/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */
/** @var yii\widgets\ActiveForm $form */
?>


<?php $form = ActiveForm::begin(); ?>
<?php Panel::begin([
    'title' => Html::encode($this->title),
    'actions' => [
        'header' => [
        ],
        'footer' => [
            Html::submitButton(Module::t( 'Save'), ['class' => 'btn btn-success']),
        ]
    ],
]) ?>
<?= $form->field($model, 'type')->textInput() ?>
<?= $form->field($model, 'id_to')->dropDownList(Notification::getUserList())->label('User') ?>
<?= $form->field($model, 'text')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

<?php Panel::end() ?>
<?php ActiveForm::end(); ?>