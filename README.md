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
+ [General Usage](#general-usage)
    + [Widgets](docs/widgets/widget.md)
    + [Component](#component)
  
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

![bell_icon](https://github.com/portalium/yii2-notification/assets/91452487/d76e7abd-0414-4e63-9007-0a8231eb296f)

<br>
By clicking any of them you will be redirected to that notification.

![notification_dropdown](https://github.com/portalium/yii2-notification/assets/91452487/260a7baf-82b0-426c-a2f6-ac9e772612e7)

<br>

### Create
For creating a notification you should have the related permission. If you have the permission you can start to create a notification
by clicking on the viewAll in the dropdown list of bell icon and then click on the plus(+) icon that is located on the corner side of your page.
The first field shows to whom the notification will be sent. After filling required fields, you can click on the save button.

![create](https://github.com/portalium/yii2-notification/assets/91452487/94d1fa18-b50e-4364-ba65-46ec61ea7ed3)

### Update
For updating a notification you need to click on the pencil icon on the right side your notification as follows,

![update_pen_icon](https://github.com/portalium/yii2-notification/assets/91452487/ff8a1276-10d0-4a6c-a7e5-9a152fd5c1e9)


### Delete
For deleting a notification, you should click on the trash icon which is located on the right side of your related notification row.
<br>
![delete_trash](https://github.com/portalium/yii2-notification/assets/91452487/69b02e06-0f47-4bc0-9455-7a7b9f72632d)
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
<br>
