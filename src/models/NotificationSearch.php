<?php

namespace portalium\notification\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use portalium\notification\models\Notification;

class NotificationSearch extends Notification
{
    public function rules()
    {
        return [
            [['id_notification', 'id_to'], 'integer'],
            [['text', 'title'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Notification::find();

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
            'id_notification' => $this->id_notification,
            'id_to' => $this->id_to,
        ]);

        $query->andFilterWhere(['like', 'text', $this->text])
            ->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
