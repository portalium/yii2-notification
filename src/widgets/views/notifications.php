<?php

use portalium\notification\bundles\NotificationAsset;
use portalium\notification\Module;
use yii\helpers\Html;

NotificationAsset::register($this);
?>
    <?php if (count($notifications) > 0) { ?>

    <ul class="card-box nav dropdown" id="notification">
        <li class="dropdown nav-item">
            <a class="dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" href="#" style="padding-left: 0px !important; padding-right: 0px !important; position: relative;">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger marginleft--10px">
                    <?= count($notifications) ?>
                    <span class="visually-hidden">unread messages</span>
                </span>
                <i class="fa fa-bell" style="padding-left: 20px; padding-right: 20px; margin-top: 3px; color: black !important;"></i>
                <span class="dropdown-toggle-icon"><i class="fa fa-caret-down"></i></span>
            </a>
            <?php echo Html::beginTag('ul', $options); ?>
            <div class="notification-heading">
                <span class="menu-title">Notifications</span>
            </div>
            <div class="drop-content">
                <div class="card" role="presentation">
                    <?php foreach ($notifications as $notification) { ?>
                        <div class="d-flex flex-row justify-content-between card-notification-item" data-key="<?= $notification->id_notification ?>">
                            <p class="card notification-content" role="presentation" style="padding: 8px 0px 8px 17px !important; background: transparent">
                                <span class="item-title"><?php echo Html::encode($notification->title); ?></span>
                            </p>
                            <div class="notification-action">
                                <a href="/notification/default/view?id=<?= $notification->id_notification ?>" class="btn btn-success btn-sm" style="padding: 5px !important;"><span class="fa fa-search"></span></a>
                                <span class="btn btn-danger btn-sm" style="padding: 5px !important;" onclick="readNotification(<?= $notification->id_notification ?>)"><span class="fa fa-trash"></span></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="notification-footer">
                <a href="/notification/default/index?" style="padding-left: 10px !important; width: 100%; display: flex; float: none; text-align: center; align-items: center;">
                    <span class="menu-title-footer">View all</span>
                </a>
            </div>
            <?php echo Html::endTag('ul'); ?>
        </li>
    </ul>
<?php } else { ?>
    <ul class="nav">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-bell-slash" style="padding-left: 5px; padding-right: 5px; margin-top: 3px; color: black;"></i>
            </a>
            <div class="dropdown-menu" aria-labelledby="notificationDropdown">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item" href="#">You have no new notifications.</a>
            </div>
        </li>
    </ul>

<?php } ?>

<?php
$js = <<<JS
    function readNotification(id) {
        $.ajax({
            url: '/notification/default/read?id=' + id,
            type: 'GET',
            success: function (data) {
                if (data.success) {
                    let notification = $('#notification');
                    let notificationItem = notification.find('[data-key=' + id + ']');
                    notificationItem.remove();
                    let notificationCount = notification.find('.badge');
                    let count = parseInt(notificationCount.text());
                    notificationCount.text(count - 1);
                    if (count - 1 === 0) {
                        notificationCount.remove();
                        $('#notification').html('<i class="fa fa-bell-slash" style="padding-left: 20px; padding-right: 20px; margin-top: 10px;"></i>');
                    }
                }
            }
        });
    }

    $(document).ready(function () {
        $('#notification ul').on('click', function (e) {
            e.stopPropagation();
        });

        $('.dropdown-toggle').on('click', function (e) {
            e.stopPropagation();
            $('#notification').toggleClass('show');
        });
    });

    $(document).on('click', function () {
        $('#notification').removeClass('show');
    });
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>