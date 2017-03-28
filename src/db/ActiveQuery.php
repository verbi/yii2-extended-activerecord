<?php
namespace verbi\yii2ExtendedActiveRecord\db;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveQuery extends \yii\db\ActiveQuery  {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    
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
        if ($db === null) {
            $db = \Yii::$app->getDb();
        }
        return $db->cache(function ($db) {
            $rows = $this->andWhere($this->getAccessRule())->createCommand($db)->queryAll();
            return $this->populate($rows);
        });
    }
    
    /**
     * @inheritdoc
     * @param type $db
     * @return type
     */
//    public function one($db = null) {
//        if ($db === null) {
//            $db = \Yii::$app->getDb();
//        }
//        return $db->cache(function ($db) {
//            return parent::one($db);
//        });
//    }
}