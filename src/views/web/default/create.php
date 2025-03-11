<?php

use yii\helpers\Html;
use portalium\notification\Module;

/** @var yii\web\View $this */
/** @var portalium\notification\models\Notification $model */

$this->title = Module::t('Create Notification');
$this->params['breadcrumbs'][] = ['label' => Module::t('Notifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'notificationForm' => $notificationForm,
]) ?>

