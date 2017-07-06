<?php

namespace api\controllers;

use api\models\PostSearch;
use common\models\Device;
use common\models\Post;
use common\models\Share;
use common\models\Task;
use common\models\TaskList;
use common\models\User;
use common\rbac\Rbac;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class TaskController extends ActiveController
{
    public $modelClass = 'common\models\Task';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator']['only'] = ['create', 'update', 'delete', 'index', 'view', 'sync'];
        $behaviors['authenticator']['authMethods'] = [
            HttpBasicAuth::className(),
            HttpBearerAuth::className(),
        ];

        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['create', 'update', 'delete', 'index', 'view', 'sync'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);

        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        return $actions;
    }

    public function actionCreate()
    {
        $model = new Task();
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        $list = TaskList::findOne($model->list_id);
        $model->shared_user_id = \Yii::$app->user->id;

        $this->checkAccess('create', $model);

        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $push_result = $model->pushListWatchers();
            return $model;
            /*   return [
                'success' => true,
                //'model' => $model,
                'push_result' => $push_result,
                'info' => \Yii::$app->user->id == $list->user_id ? "В свой список {$model->list_id}" : "В чужой расшаренный $model->list_id",
            ];            */
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    public function actionDelete($id)
    {
        $model = Task::findOne($id);
        $original_shared_user_id = $model->shared_user_id; // для удаления
        $list = $model->list;


        if (!$model) throw new ServerErrorHttpException ('Failed to delete the object: list nor found. id=' . $id);

        $this->checkAccess('delete', $model);

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        $push_result = $model->pushListWatchers($list, $original_shared_user_id);


        return [
            'success' => true,
            'push_result' => $push_result
        ];



    }


    public function actionUpdate($id)
    {


        $model = Task::findOne($id);
        $this->checkAccess('update', $model);

        $model->load(\Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {

            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }




        $push_result = $model->pushListWatchers();

        return $model;

        /*
        return [
            'success' => true,
            'model' => $model,
            'push_result' => $push_result
        ];
        */


    }

    public function  actionSync()
    {

        $data = Yii::$app->getRequest()->getBodyParams();
        if (!is_array($data) || !count($data) > 0) {
            return ['success' => false, 'message' => 'Пустой спиок для синхронизации'];
        }

        $result_data = [];
        foreach ($data as $key => $object) {

            if (isset ($object['id'])) {
                // обновляем существующую
                $model = Task::findOne($object['id']);
                $model->load($object, '');
                if ($model->save()) {
                    $result_data[$key]['status'] = 'Updated';
                    $result_data[$key]['model'] = $model;
                } elseif (!$model->hasErrors()) {
                    throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
                }

            } else {
                // создаем
                $model = new Task();
                $model->load($object, '');
                if ($model->save()) {
                    $result_data[$key]['status'] = 'Created';
                    $result_data[$key]['model'] = $model;
                } elseif (!$model->hasErrors()) {
                    throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
                }

            }
        }


        return $result_data;

    }

    public function prepareDataProvider()
    {

        if (\Yii::$app->user->isGuest) throw new ForbiddenHttpException('Forbidden. Пользователь не авторизирован');

        $query = Task::find()->joinWith('list', 'task.list_id=list.id')->where(['list.user_id' => \Yii::$app->user->id]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 500,
            ],
        ]);
        return $dataProvider;
    }


    public function checkAccess($action, $model = null, $params = [])
    {
        /* @var $model Task */
        $list = TaskList::findOne($model->list_id);

        if (in_array($action, ['delete'])) {
            if (! (Yii::$app->user->can(Rbac::MANAGE_TASK, ['task' => $model]) || \Yii::$app->user->can(Rbac::MANAGE_SHARED_LIST, ['list' => $list])) ) {
                //var_dump(Yii::$app->user->can(Rbac::MANAGE_TASK, ['task' => $model])); die();
                throw  new ForbiddenHttpException('Нет прав удалять задачу ...');
            }
        }

        if (in_array($action, ['update'])) {
            if (! (Yii::$app->user->can(Rbac::MANAGE_TASK, ['task' => $model]) || \Yii::$app->user->can(Rbac::MANAGE_SHARED_LIST, ['list' => $list])) ) {
                //var_dump(Yii::$app->user->can(Rbac::MANAGE_TASK, ['task' => $model])); die();
                throw  new ForbiddenHttpException('Нет прав редактироват задачу ...');
            }
        }

        if ($action == 'view') {

            if (!(\Yii::$app->user->id == $list->user_id || \Yii::$app->user->can(Rbac::MANAGE_SHARED_LIST, ['list' => $list]) || \Yii::$app->user->can(Rbac::MANAGE_TASK, ['task' => $model]))) {

                throw  new ForbiddenHttpException('Нельзя просматривать чужую задачу ...');

            }
        }

        if (in_array($action, ['create'])) {

            // проверить в каком листе создается задача
            // можно создавать только в своем или расшаренном для тебя


            if (!(\Yii::$app->user->can(Rbac::MANAGE_SHARED_LIST, ['list' => $list]) || (\Yii::$app->user->can(Rbac::MANAGE_LIST, ['list' => $list])))) {
                throw  new ForbiddenHttpException('Forbidden ... Нельзя создавать задачу в чужом списке');
            }
        }

    }


}
