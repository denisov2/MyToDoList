<?php

namespace api\controllers;

use common\models\Device;
use common\models\User;
use Yii;
use yii\base\Security;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use api\models\RegisterForm;
use api\models\LoginForm;

class SiteController extends Controller
{


    public function behaviors()
    {


        $behaviors = parent::behaviors();

        $behaviors['authenticator']['only'] = ['store-device-token', 'me'];
        $behaviors['authenticator']['authMethods'] = [
            HttpBasicAuth::className(),
            HttpBearerAuth::className(),
        ];
 
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['store-device-token', 'me'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }


    public function actionIndex()
    {
        return [
		
		'Aplication' => 'MyToDoList',
		'Api version' => '1.0', 
		'Company' => 'Witlex-компания, которая занимается разработкой приложений на Android. 
Мы разработали такие приложения как My to Do List и Swey.
Скачать в Google Store',



		
		];
    }
	
	 public function actionTest()
    {
        return [
		
		'Aplication' => 'MyToDoList',
		'Api version' => '0.9', 
		'Company' => 'Witlex-компания, которая занимается разработкой приложений на Android. 
Мы разработали такие приложения как My to Do List и Swey.
Скачать в Google Store',



		
		];
    }


    public function actionLogin()
    {
        $model = new LoginForm();
        $model->scenario = LoginForm::SCENARIO_LOGIN;
        $model->load(Yii::$app->request->bodyParams, '');
        if ($token = $model->auth()) {
            return $token;
        } else {
            return $model;
        }
    }

	public function actionMe()
    {
        return \Yii::$app->user->identity;
    }


    public function actionLoginGoogle()
    {
        $google_token = \Yii::$app->request->getBodyParam('google-token');
        $google_data = $this->loginViaGoogle($google_token);

        if (! $google_data['email']) {
            return [
                'success' => false,
                'message' => 'Не удалеось получить email пользователя '
            ];
        }


        $user = User::findOne(['email' => $google_data['email']]);

        if ($user) {

            // пользователь уже есть - логиним

            $model = new LoginForm();
            $model->scenario = LoginForm::SCENARIO_GOOGLE_LOGIN;
            $model->google_token = $google_token;
            $model->email = $user;

            if ($token = $model->auth()) {


                return [

                    'token' =>  $token->token,
                    'expired' => date(DATE_RFC3339, $token->expired_at),
                    'is_register' => false

                ];
            } else {
                return $model;
            }


        } else {

            // пользоветля нету - регистрируем
            $model = new RegisterForm();
            $model->email = $google_data['email'];
            $model->name = $google_data['name'];
            $model->password = \Yii::$app->security->generateRandomString(8);


            if ($model->validate()) {
                if ($user = $model->register()) {
                    if (Yii::$app->getUser()->login($user)) {
                        $token = $model->generateRegistrationToken($user);
                        return [

                            'token' =>  $token->token,
                            'expired' => date(DATE_RFC3339, $token->expired_at),
                            'is_register' => true

                        ];


                    }
                }
            }
            return $model;

            return ['result' => 'fail', 'error_message' => 'No registration data', 'model' => $model];


        }

    }

    public function loginViaGoogle($google_token)
    {


        // проверить $this->google_token и вернуть email
        $google_info_url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $google_token;


        // Get cURL resource
        $curl = curl_init();
// Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $google_info_url,
            CURLOPT_USERAGENT => 'Codular Sample cURL Request'
        ));
// Send the request & save response to $resp
        $data = json_decode(  curl_exec($curl) );
// Close request to clear up some resources
        curl_close($curl);






        return [
            'email' => $data->email,
            'name' => $data->name,
        ];
    }

    public function actionStoreDeviceToken()
    {
        $device_token = \Yii::$app->request->getBodyParam('device_token');
        $model = new Device;
        $model->token = $device_token;
        $model->user_id = \Yii::$app->user->id;

        if($model->validate()) {
            Device::deleteAll(['user_id' => \Yii::$app->user->id]);
            $model->save();
            return $model;
        }
        return $model;
    }


    public function actionRegister()
    {
        $model = new RegisterForm();
        if ($model->load(Yii::$app->request->bodyParams, '')) {
            if ($model->validate()) {
                if ($user = $model->register()) {
                    if (Yii::$app->getUser()->login($user)) {
                        $model->generateRegistrationToken($user);
                        return $user;
                    }
                }
            }
            return $model;
        }
        return ['result' => 'fail', 'error_message' => 'No registration data', 'model' => $model];


    }

    protected function verbs()
    {
        return [
            'login' => ['post'],
            'register' => ['post'],
            'store-device-token' => ['post'],
            'login-google' => ['post'],
        ];
    }
}
