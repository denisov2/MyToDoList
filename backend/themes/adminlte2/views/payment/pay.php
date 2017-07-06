<?php

/* @var $this yii\web\View */
use yii\grid\GridView;
$this->title = 'Оплата заказов пользователей';
?>

<?=GridView::widget([
    'summary'       => 'Показаны {begin}-{end} из {totalCount}',
    'emptyText'     => 'Нет ни одного неоплаченного заказа',
    'tableOptions'  => ['class' => 'table table-striped'],
    'dataProvider'  => $dataProvider,
    'columns' => [
        [
            'label' => 'ID заказа',
            'attribute' => 'id',
        ],
        [
            'label' => 'Товар',
            'value' => function ($data) {
                return $data->product->getShortTitle();
            },
        ],
        [
            'label' => 'Договор',
            'value' => function ($data) {
                /**
                 * @var \common\models\Order $data
                 */
                return $data->contract->signature ? 'Подписан' : 'Не подписан';
            },
        ],
        [
            'label' => 'Сумма',
            'attribute' => 'total_price',
        ],
        [
            'label' => 'Оплатить',
            'format' => 'raw',
            'value' => function ($data) {
                /**
                 * @var \common\models\Order $data
                 */
                if($data->isFilled() && $data->contract->signature)
                {
                    $payment = new LiqPay(Yii::$app->params['LiqPay']['PublicKey'], Yii::$app->params['LiqPay']['PrivateKey']);
                    return $payment->cnb_form([
                        'version'           => '3',
                        'action'            => 'pay',
                        'phone'             => $data->user->profile->phone,
                        'amount'            => $data->total_price,
                        'currency'          => 'UAH',
                        'description'       => Yii::t('common', 'Оплата АДМИНИСТРАТОРОМ заказа #{0} ({1})', [$data->id, $data->product->getShortTitle()]),
                        'order_id'          => $data->id,
                        'server_url'        => 'http://swable.org/ru/payment/callback',
                        'product_url'       => Yii::$app->urlManagerFrontEnd->createAbsoluteUrl(['product/view', 'slug'=>$data->product->slug]),
                        'product_name'      => $data->product->title,
                        'customer'          => $data->user->id,
                        'sender_first_name' => $data->user->profile->name,
                        'language'          => Yii::$app->language,
                        'sandbox'           => Yii::$app->params['LiqPay']['sandbox']
                    ]);
                }
                return;
            },
        ],
    ],
]); ?>