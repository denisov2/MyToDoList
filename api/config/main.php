<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php')
// require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'baseUrl' => '/api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'application/xml' => 'yii\web\XmlParser',

            ],
        ],
        'response' => [
            'formatters' => [
                'json' => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG,
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            //'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                'auth' => 'site/login',
                'login' => 'site/login',
                'me' => 'site/me',
                'login-google' => 'site/login-google',
                'register' => 'site/register',
                'store-device-token' => 'site/store-device-token',

                'GET profile' => 'profile/index',
                'GET profiles' => 'profile/index',
                'PUT,PATCH profile' => 'profile/update',
                'PUT,PATCH profiles' => 'profile/update',

                ['class' => 'yii\rest\UrlRule', 'controller' => 'post'],


                [
                    'class' => \yii\rest\UrlRule::className(),
                    'controller' => 'task',

                    'extraPatterns' => [


                        'POST sync' => 'sync',
                    ],

                ],

                [
                    'class' => yii\rest\UrlRule::className(),
                    'controller' => ['lists' => 'task-list'],

                    'tokens' => [

                        '{id}' => '<id:\\d[\\d,]*>',
                        //'{user_id}' => '<user_id:\\d[\\d,]*>',
                        //'{email}' => '<email:\\w[\\w,]*>',
                    ],


                    'extraPatterns' => [

                        'GET {id}/tasks' => 'tasks',
                        //'DELETE {id}' => 'delete',
                        //'POST {id}/share-list/{user_id}' => 'share-list',
                        'POST {id}/share-list' => 'share-list',
                        'GET shared' => 'shared',
                        'GET push' => 'push',
                        'POST sync' => 'sync',
                    ],
                ],


                ['class' => 'yii\rest\UrlRule', 'controller' => 'user'],


            ],
        ],
    ],
    'params' => $params,
];
