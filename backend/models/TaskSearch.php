<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Task;

/**
 * TaskSearch represents the model behind the search form of `common\models\Task`.
 */
class TaskSearch extends Task
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'local_id', 'time', 'is_date_set', 'is_time_set', 'is_notification_enabled', 'repeats', 'list_id', 'state', 'task_type'], 'integer'],
            [['title', 'title_lower', 'priority', 'notes'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Task::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'local_id' => $this->local_id,
            'time' => $this->time,
            'is_date_set' => $this->is_date_set,
            'is_time_set' => $this->is_time_set,
            'is_notification_enabled' => $this->is_notification_enabled,
            'repeats' => $this->repeats,
            'list_id' => $this->list_id,
            'state' => $this->state,
            'task_type' => $this->task_type,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'title_lower', $this->title_lower])
            ->andFilterWhere(['like', 'priority', $this->priority])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        return $dataProvider;
    }
}
