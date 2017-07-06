<?php

/* @var $this yii\web\View */
use yii\grid\GridView;
$this->title = 'История платежей';
?>

<?=GridView::widget([
    'summary'       => 'Показаны {begin}-{end} из {totalCount}',
    'emptyText'     => 'Нет ни одного платежа',
    'tableOptions'  => ['class' => 'table table-striped'],
    'dataProvider'  => $dataProvider,
    'columns' => [
        [
            'label' => 'ID заказа',
            'attribute' => 'order_id',
        ],
        [
            'label' => 'Статус',
            'attribute' => 'status',
        ],
        [
            'label' => 'Описание',
            'attribute' => 'description',
        ],
        [
            'label' => 'Сумма',
            'attribute' => 'amount',
        ],
        [
            'label' => 'Дата',
            'attribute' => 'updated_at',
            'value' => function ($data) {
                return date("d.m.Y", $data->created_at);
            },
        ],
    ],
]); ?>