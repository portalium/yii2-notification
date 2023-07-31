# <p align="center">Portalium-Notification</p>

# Table of Contents
+ [Introduction](#introduction)
+ [Requirements](#requirements)
+ [How does the Notification Module work?](#how-does-the-notification-module-work)
    + [Read](#read)
    + [Create](#create)
    + [Update](#update)
    + [Delete](#delete)
+ [How to use or develop](#how-to-use-or-develop)
## Introduction
This module created for sending and receiving notifications to specific users based on permissions
that is given to related user.

## Requirements
  PHP 7.3.31 or higher


## How does the Notification Module work?
Creating, Updating, Deleting and  Viewing the notification is done base on the permissions that is given to users.<br>
### Read
For reading or viewing your notifications you need to check the bell icon on the right corner of your login page.
If you have any new notification, a number which states the number of notifications will be appeared on top of bell icon.
<br>

![bell_icon.png](..%2F..%2F..%2F..%2F..%2F..%2Fbell_icon.png)
<br>
By clicking any of them you will be redirected to that notification.


![notification_dropdown.png](..%2F..%2F..%2F..%2F..%2F..%2Fnotification_dropdown.png)
<br>

### Create
For creating a notification you should have the related permission. If you have the permission you can start to create a notification
by clicking on the viewAll in the dropdown list of bell icon and then click on the plus(+) icon that is located on the corner side of your page.
The first field shows to whom the notification will be sent. After filling required fields, you can click on the save button.

![create.png](..%2F..%2F..%2F..%2F..%2F..%2Fcreate.png)


### Update
For updating a notification you need to click on the pencil icon on the right side your notification as follows,

![update_pen_icon.png](..%2F..%2F..%2F..%2F..%2F..%2Fupdate_pen_icon.png)


### Delete
For deleting a notification, you should click on the trash icon which is located on the right side of your related notification row.
<br>

![delete_trash.png](..%2F..%2F..%2F..%2F..%2F..%2Fdelete_trash.png)
<br>


## How to use or develop

You should add the following blocks of code into your composer.json file of your Portalium module.

to repositories part of your composer.json
<br>
`{
"type": "git",
"url": "https://github.com/portalium/yii2-notification.git"
}`
<br>
<br>
to require part of your composer.json
<br>
`"portalium/yii2-notification": "dev-develop",`

and in web\config\main.php path you should add the following block of code in module part
<br>
`{
"type": "git",
"url": "https://github.com/portalium/yii2-notification.git"
}`
<br><br>
and finally, you should run `composer update` command in the terminal of your php container in Docker.