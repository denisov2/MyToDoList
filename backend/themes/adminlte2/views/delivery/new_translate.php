<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\User;
use yii\helpers\ArrayHelper;
use common\models\Product;

/* @var $this yii\web\View */
/* @var $model common\models\Order */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="order-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'title')->textInput()->label('Название перевода') ?>
    <?= $form->field($model, 'lang_id')->dropDownList(ArrayHelper::map(\common\models\Languages::find()->where(['!=', 'id', \common\models\Languages::getDefault()->id])->all(),'id','name'))-> label('Язык'); ?>
    <?= $form->field($model, 'original_id')->dropDownList(ArrayHelper::map(\common\models\DeliveryMethod::find()->where(['lang_id' => \common\models\Languages::getDefault()->id])->all(),'id','title'), ['prompt' => 'Это оригинал'])-> label('Оригинал перевода') ; ?>

    <div class="form-group">
        <?= Html::submitButton('Добавить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
