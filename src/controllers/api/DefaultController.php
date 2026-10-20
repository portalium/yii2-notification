<?php

namespace portalium\notification\controllers\api;

use portalium\rest\ActiveController as RestActiveController;

class DefaultController extends RestActiveController
{
    public $modelClass = '';

    /**
     * No model is set, so the inherited CRUD actions are disabled (they would otherwise return 500).
     */
    public function actions()
    {
        return [];
    }
}
