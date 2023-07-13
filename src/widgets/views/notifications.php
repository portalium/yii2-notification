<?php
use portalium\notification\bundles\NotificationAsset;
use portalium\notification\Module;
$this->title = Module::t('Notification');
NotificationAsset::register($this);
?>

<a href="#" class="dropdown-toggle" style="background-color: #212529" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
    <i class="fa fa-bell"></i>
</a>
<ul class="dropdown-menu notify-drop">

    <li>
        <div class="notification-heading">
            <h4 class="menu-title">Notifications</h4>
        </div>
    </li>
    <hr class="dropdown-divider">

    <div class="drop-content">
        <div class="notifications-wrapper">
            <div class="notification-item">
                <h4 class="item-title">Evaluation Deadline 1 · day ago</h4>
                <p class="item-info">Marketing 101, Video Assignment</p>
            </div>

            <div class="notification-item">
                <h4 class="item-title">Evaluation Deadline 1 · day ago</h4>
                <p class="item-info">Marketing 101, Video Assignment</p>
            </div>
        </div>
    </div>

    <hr class="dropdown-divider">
    <li>
        <div class="notification-footer">
            <h4 class="menu-title">View all</h4>
        </div>
    </li>
</ul>

