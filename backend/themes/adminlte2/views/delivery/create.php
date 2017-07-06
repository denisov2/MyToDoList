<?php

/* @var $this yii\web\View */
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Новый способ доставки';
?>
<div class="order-create">

    <?= $this->render($translation ? 'new_translate' : '_form', [
        'model' => $model,
    ]) ?>

</div>
