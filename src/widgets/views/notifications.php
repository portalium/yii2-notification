<?php

use portalium\notification\bundles\NotificationAsset;
use portalium\notification\Module;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $notifications portalium\notification\models\Notification[] */
/* @var $unread int */

NotificationAsset::register($this);

$bellLabel = $unread > 0
    ? Module::t('Notifications ({n} unread)', ['n' => $unread])
    : Module::t('Notifications');
// opens on the unread ones; with none left there is nothing to show there
$onlyUnread = $unread > 0;
$formatter = Yii::$app->formatter;
?>
<ul class="nav" id="notification">
    <li class="nav-item">
        <button type="button" class="nav-link notification-toggle" data-bs-toggle="offcanvas"
                data-bs-target="#notification-panel" aria-controls="notification-panel"
                aria-label="<?= Html::encode($bellLabel) ?>" title="<?= Html::encode($bellLabel) ?>">
            <span class="notification-bell">
                <i class="fa fa-bell" aria-hidden="true"></i>
                <?php if ($unread > 0) : ?>
                    <span class="badge rounded-pill bg-danger notification-count"><?= $unread ?></span>
                <?php endif; ?>
            </span>
        </button>
    </li>
</ul>

<div class="offcanvas offcanvas-end notification-panel<?= $onlyUnread ? ' only-unread' : '' ?>" tabindex="-1" id="notification-panel"
     aria-labelledby="notification-panel-title"
     data-read-url="<?= Html::encode(Url::to(['/notification/default/read-selected'])) ?>"
     data-read-all-url="<?= Html::encode(Url::to(['/notification/default/read-all'])) ?>"
     data-delete-url="<?= Html::encode(Url::to(['/notification/default/delete-selected'])) ?>"
     data-delete-confirm="<?= Html::encode(Module::t('Delete the selected notifications?')) ?>"
     data-delete-one-confirm="<?= Html::encode(Module::t('Delete this notification?')) ?>"
     data-select-label="<?= Html::encode(Module::t('Select all')) ?>"
     data-selected-label="<?= Html::encode(Module::t('{n} selected')) ?>"
     data-label="<?= Html::encode(Module::t('Notifications')) ?>"
     data-label-unread="<?= Html::encode(Module::t('Notifications ({n} unread)')) ?>">
    <div class="offcanvas-header notification-panel-header">
        <h2 class="offcanvas-title" id="notification-panel-title"><?= Module::t('Notifications') ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= Html::encode(Module::t('Close')) ?>"></button>
    </div>
    <div class="notification-tabs" role="tablist">
        <button type="button" class="notification-tab<?= $onlyUnread ? ' active' : '' ?>" role="tab" data-filter="unread"
                aria-selected="<?= $onlyUnread ? 'true' : 'false' ?>">
            <?= Module::t('Unread') ?> (<span class="notification-tab-count"><?= $unread ?></span>)
        </button>
        <button type="button" class="notification-tab<?= $onlyUnread ? '' : ' active' ?>" role="tab" data-filter="all"
                aria-selected="<?= $onlyUnread ? 'false' : 'true' ?>">
            <?= Module::t('All') ?>
        </button>
        <?= Html::a('<i class="fa fa-external-link" aria-hidden="true"></i>', ['/notification/default/index'], [
            'class' => 'notification-view-all',
            'title' => Module::t('View all'),
            'aria-label' => Module::t('View all'),
        ]) ?>
    </div>
    <div class="notification-select-bar">
        <label class="notification-select-all">
            <input class="form-check-input" type="checkbox" id="notification-select-all"<?= empty($notifications) ? ' disabled' : '' ?>>
            <span class="notification-select-label"><?= Module::t('Select all') ?></span>
        </label>
        <div class="notification-select-actions" hidden>
            <button type="button" class="btn btn-success notification-read-selected">
                <i class="fa fa-check" aria-hidden="true"></i> <?= Module::t('Mark as read') ?>
            </button>
            <button type="button" class="btn btn-danger notification-delete-selected">
                <i class="fa fa-trash-o" aria-hidden="true"></i> <?= Module::t('Delete') ?>
            </button>
        </div>
    </div>
    <div class="offcanvas-body notification-panel-body">
        <?php foreach ($notifications as $notification) : ?>
            <?php
            $id = (int) $notification->id_notification;
            $isUnread = (int) $notification->status === $notification::STATUS_UNREAD;
            $time = $notification->date_create ? strtotime($notification->date_create . ' UTC') : false;
            // the text may carry a link for the detail page; the row shows it as plain text
            $text = trim(html_entity_decode(strip_tags((string) $notification->text), ENT_QUOTES, 'UTF-8'));
            ?>
            <div class="notification-row<?= $isUnread ? ' unread' : '' ?>" data-key="<?= $id ?>">
                <input class="form-check-input notification-row-check" type="checkbox" value="<?= $id ?>"
                       aria-label="<?= Html::encode(Module::t('Select') . ': ' . $notification->title) ?>">
                <a class="notification-row-main" href="<?= Html::encode(Url::to(['/notification/default/view', 'id' => $id])) ?>">
                    <span class="notification-row-title"><?= Html::encode($notification->title) ?></span>
                    <?php if ($text !== '') : ?>
                        <span class="notification-row-text"><?= Html::encode($text) ?></span>
                    <?php endif; ?>
                </a>
                <div class="notification-row-side">
                    <?php if ($time) : ?>
                        <time class="notification-row-time" datetime="<?= Html::encode(gmdate('c', $time)) ?>"
                              title="<?= Html::encode($formatter->asDatetime($time, 'php:d.m.Y H:i')) ?>"><?= Html::encode($formatter->asRelativeTime($time)) ?></time>
                    <?php endif; ?>
                    <div class="notification-row-actions">
                        <?php if ($isUnread) : ?>
                            <button type="button" class="btn btn-success btn-sm notification-row-read" data-id="<?= $id ?>"
                                    title="<?= Html::encode(Module::t('Mark as read')) ?>" aria-label="<?= Html::encode(Module::t('Mark as read')) ?>">
                                <i class="fa fa-check" aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger btn-sm notification-row-delete" data-id="<?= $id ?>"
                                title="<?= Html::encode(Module::t('Delete')) ?>" aria-label="<?= Html::encode(Module::t('Delete')) ?>">
                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <p class="notification-empty"<?= empty($notifications) ? '' : ' hidden' ?>><?= Module::t('No notifications yet.') ?></p>
        <p class="notification-empty-unread" hidden><?= Module::t('No unread notifications.') ?></p>
    </div>
</div>
