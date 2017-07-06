<?php

/* @var $this yii\web\View */
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Способы доставки';
?>
<p>
    <?= Html::a('Добавить способ доставки', ['create'], ['class' => 'btn btn-success']) ?>
</p>
<?=GridView::widget([
    'summary'       => 'Показаны {begin}-{end} из {totalCount}',
    'emptyText'     => 'Нет ни одного способа доставки',
    'tableOptions'  => ['class' => 'table table-striped'],
    'dataProvider'  => $dataProvider,
    'columns' => [
        [
            'label' => 'Название',
            'attribute' => 'title',
        ],
        [
            'label' => 'Переводы',
            'value' => function($data){
                /**
                 * @var \common\models\DeliveryMethod $data
                 */

                $html = '';
                $translations = \common\models\DeliveryMethod::findAll(['original_id' => $data->id]);
                foreach ($translations as $item)
                {
                    /**
                     * @var \common\models\DeliveryMethod $item
                     */
                    $html .= Html::a($item->lang->name, Url::to(['/delivery/update', 'id' => $item->id]))."<br>";
                }
                if(count($translations) < \common\models\Languages::find()->count()-1)
                    $html .= Html::a('+ Добавить перевод', Url::to(['/delivery/create-translation', 'id' => $data->id]));
                return $html;
            },
            'format' => 'raw'
        ],
        [
            'label' => 'Примерные сроки (в днях)',
            'attribute' => 'time',
        ],
        [
            'label' => 'Стоимость',
            'attribute' => 'price',
            'value' => function($data){
                /**
                 * @var \common\models\DeliveryMethod $data
                 */

                return money_format("%i", $data->price). " UAH";
            }
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'header' => 'Действия',
            'template' => '{update} {delete}'
        ],
    ],
]); ?>