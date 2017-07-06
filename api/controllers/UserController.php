<?php

namespace api\controllers;

use api\models\PostSearch;
use common\models\Post;
use common\rbac\Rbac;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class UserController extends ActiveController
{
    public $modelClass = 'common\models\User';




    /*

     public function checkAccess($action, $model = null, $params = [])
     {
         if (in_array($action, ['update', 'delete'])) {
             if (!Yii::$app->user->can(Rbac::MANAGE_POST, ['post' => $model])) {
                 throw  new ForbiddenHttpException('Forbidden.');
             }
         }
     }
 }
    */

}