<?php

use yii\helpers\Html;
use portalium\notification\Module;

/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */
/** @var yii\widgets\ActiveForm $notificationForm */

$this->title = Module::t('Update Notification') . ": " . $model->title;
$this->params['breadcrumbs'][] = ['label' => Module::t('Notifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Module::t( 'Update');
?>

<?= $this->render('_form', [
    'model' => $model,
    'notificationForm' => $notificationForm
]) ?>
