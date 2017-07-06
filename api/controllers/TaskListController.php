<?php

namespace api\controllers;

use common\models\Device;
use common\models\Share;
use common\models\Task;
use common\models\TaskList;
use common\models\User;
use common\rbac\Rbac;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

use common\components\FirebaseNotifications;

class TaskListController extends ActiveController
{
    public $modelClass = 'common\models\TaskList';

    const ACTION_PUSH_TYPE_SHARE = 1;
    const ACTION_PUSH_TYPE_DELETE = 2;
    //const ACTION_PUSH_TYPE_UNSHARE = 3;
    const ACTION_PUSH_TYPE_CHANGE = 4;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator']['only'] = ['create', 'update', 'delete', 'index', 'share-list', 'shared', 'view', 'sync'];
        $behaviors['authenticator']['authMethods'] = [
            HttpBasicAuth::className(),
            HttpBearerAuth::className(),
        ];

        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['create', 'update', 'delete', 'index', 'share-list', 'shared', 'view', 'sync'],
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
        unset($actions['delete']);
        unset($actions['update']);


        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];


        return $actions;

    }

    public function actionPush()
    {
        return $this->push("Вася", "Васин список", 'cIpggJ6wdbQ:APA91bFB4DsNGoDWn3o6VD3grzk_ONX7Pi2IN1mZiPUr2lGnG-5OK_9SjXjDIUxfOHn2lgGZarEYRTSeLIYE1UyxbVMH4SbGMFOFXNYM3_fHrolvADA4Ke7JFXHUeA7N6oVIEprcVj3G', 1);
    }


    public function push($from_user, $list_name, $action_type, $token)
    {
        $firebase = new FirebaseNotifications();

        $data = [
            'from_user' => $from_user,
            'list_name' => $list_name,
            'action_type' => $action_type,

        ];
        return $firebase->sendNotification($token, $data);

    }

    public function actionDelete($id)
    {
        $model = TaskList::findOne($id);
        if (!$model) throw new ServerErrorHttpException ('Failed to delete the object: list nor found. id=' . $id);
        $list_name = $model->name;
        $this->checkAccess('delete', $model);

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        // Список успешно удален, раасылаем пуши если он есть на кого то расшарен
        $shares = Share::findAll(['list_id' => $model->id]);
        $push_result = [];

        if ($shares) {
            foreach ($shares as $key => $share) {

                $user = User::findOne($share->user_id);
                $device = Device::find()->where(['user_id' => $user->id])->orderBy('id DESC ')->one();
                if ($device) {
                    $push_result[$key] = $this->push($user->name, $list_name, $this::ACTION_PUSH_TYPE_DELETE, $device->token);
                    \Yii::trace("Шлем пуш удаление собственно списка {$user->name} | {$user->email}| {$user->id}" . var_export($push_result ), __METHOD__);
                }
                else {

                    $push_result[$key] = "Девайс для юзера с id={$share->user_id} не найден";
                    \Yii::trace("У юзера нет девайся {$user->name} | {$user->email}| {$user->id}" . var_export($push_result ), __METHOD__);
                }
                /*
                 \Yii::trace("Шлем пуш {$user->name} | {$user->email}| {$user->id}" . var_export($push_result ), __METHOD__);
                 * */

                $share->delete();
            }
        }
        return [
            'result' => true,
            'status' => 204,
            'push_result' => $push_result,
        ];
    }

    public function actionUpdate($id) {
        /* @var $model ActiveRecord */
        $model = TaskList::findOne($id);
        $list_name = $model->name;

        $cur_user = User::findOne(\Yii::$app->user->id);

        $this->checkAccess('delete', $model);

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        // Список успешно удален, раасылаем пуши если он есть на кого то расшарен
        $shares = Share::findAll(['list_id' => $model->id]);
        $push_result = [];



        if ($shares) {
            foreach ($shares as $key => $share) {

                $user = User::findOne($share->user_id);
                $device = Device::find()->where(['user_id' => $user->id])->orderBy('id DESC ')->one();
                if ($device)
                    $push_result[$key] = $this->push($cur_user->name, $list_name, $this::ACTION_PUSH_TYPE_CHANGE, $device->token);
                else $push_result[$key] = "Девайс для юзера с id={$share->user_id} не найден";

                $share->delete();
            }
        }

        return $model;
    }


    public function actionCreate()
    {
        $model = new TaskList();
        $model->user_id = \Yii::$app->user->id;


        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute(['view', 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
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
                $model = TaskList::findOne($object['id']);
                $model->load($object, '');

                if ($model->save()) {
                    $result_data[$key]['status'] = 'Updated';
                    $result_data[$key]['model'] = $model;
                } elseif (!$model->hasErrors()) {
                    throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
                }

            } else {
                // создаем
                $model = new TaskList();
                $model->load($object, '');
                $model->user_id = \Yii::$app->user->id;
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


    public function actionTasks($id)
    {
        $tasks = Task::findAll(['list_id' => $id]);

        if ($tasks) return $tasks;
        else throw new NotFoundHttpException("Не найдено задач для списка с id=" . $id);

    }


    public function actionShared()
    {

        $shares = Share::find()->where(['user_id' => \Yii::$app->user->id])->select('list_id')->asArray()->all();

        if ($shares) {


            $ids = [];
            foreach ($shares as $share) {
                $ids[] = $share['list_id'];
            }

            $lists = TaskList::findAll($ids);

            return $lists;
        } else
            return ['success' => false, 'message' => "Не найдено "];
    }


    public function actionShareList($id)
    {


        $email = \Yii::$app->request->getBodyParam('email');
        $user = User::findOne(['email' => $email]);
        if (!$user) throw new NotFoundHttpException("User with email $email NOT FOUND");
        if ($user->id == \Yii::$app->user->id) throw new ServerErrorHttpException("You couldn't share list to yourself");

        $share = Share::findOne(['list_id' => $id, 'user_id' => $user->id]);
        if (!$share) {
            $share = new Share();
            $share->list_id = $id;
            $share->user_id = $user->id;
        }
        $list = TaskList::findOne($id);
        if ($share->validate() && $share->save(false)) {

            $device = Device::findOne(['user_id' => $user->id]);

            if($device) {
                $push_result = $this->push($user->name, $list->name, $this::ACTION_PUSH_TYPE_SHARE, $device->token);
                \Yii::trace("Шлем пуш {$user->name} | {$user->email}| {$user->id}" . var_export($push_result ), __METHOD__);
            }
            else {
                $push_result = "Push sending fail. No device fonud user_id = {$user->id} , email={$user->email}";
                \Yii::warning("Девайс для юзера не найден {$user->name} | {$user->email}| {$user->id}" . var_export($push_result), __METHOD__);
            }

            return [
                'success' => true,
                'message' => "Список \"{$list->name}\" (id = {$id}) расшарен на пользоателя {$user->email} (id={$user->id}) ",
                //'push_result' => $push_result,
                'current_user' => \Yii::$app->user->id,
            ];
        } else
            return $share;
    }

    public function prepareDataProvider()
    {

        if (\Yii::$app->user->isGuest) throw new ForbiddenHttpException('Forbidden. Пользователь не авторизирован');
        $query = TaskList::find()->andWhere(['user_id' => \Yii::$app->user->getId()]);
        if (!$query) throw new NotFoundHttpException("Не найдено задач для списка с id=" . \Yii::$app->user->getId());

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

        if (in_array($action, ['update', 'delete', 'view'])) {
            if (!Yii::$app->user->can(Rbac::MANAGE_LIST, ['list' => $model])) {
                throw  new ForbiddenHttpException('Forbidden.');
            }
        }

    }


}
