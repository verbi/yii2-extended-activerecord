<?php
namespace verbi\yii2ExtendedActiveRecord\db;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveQuery extends \yii\db\ActiveQuery  {
    
    /**
     * @inheritdoc
     */
    public function behaviors() {
        return array_merge(parent::behaviors(), [
            \verbi\yii2Helpers\behaviors\base\ComponentBehavior::className(),
        ]);
    }
    
    /**
     * @inheritdoc
     */
    public function getAccessRule() {
        $class = $this->modelClass;
        $model= new $class;
        return $model->getAccessRule();
    }
    
    /**
     * @inheritdoc
     */
    public function getAccessQuery() {
        $modelClass=$this->modelClass;
        $query=(new \yii\db\Query())->select('id')->from($modelClass::tableName())->where($this->getAccessRule());
        return $query;
    }
    
    /**
     * @inheritdoc
     */
    public function all($db = null)
    {
        $rows = $this->andWhere($this->getAccessRule())->createCommand($db)->queryAll();
        return $this->populate($rows);
    }
}