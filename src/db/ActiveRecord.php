<?php

namespace verbi\yii2ExtendedActiveRecord\db;

use Yii;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveRecord extends \yii\db\ActiveRecord {
    use \verbi\yii2ExtendedActiveRecord\traits\ActiveRecordTrait;
    use \verbi\yii2ExtendedActiveRecord\traits\ModelFormTrait;
    
    protected $_activeQueryClass = '\\verbi\\yii2ExtendedActiveRecord\\db\\ActiveQuery';
    protected $_queryClass = '\\verbi\\yii2ExtendedActiveRecord\\db\\Query';

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }
    
    /**
     * @inheritdoc
     */
    public function findUnauthorized() {
        return parent::find();
    }
    
    public function getAccessQuery($identity) {
        $query = (new Query())
                ->select('id')
                ->from($this->tableName())
                ->where($this->getAccessRule($identity));
        return $query;
    }
    
    public function saveAll($models, $runValidation = true, $attributeNames = null) {
        $db = static::getDb();
        $sql = '';
        if ($this->isTransactional(self::OP_INSERT)) {
            $transaction = $db->beginTransaction();
        }
        $i = 0;
        foreach ($models as $model) {
            if ($model->getIsNewRecord()) {
                if ($runValidation && !$model->validate($attributeNames)) {
                    Yii::info('Model not inserted due to validation error.', __METHOD__);
                }
                if (!$model->beforeSave(true)) {
                    return false;
                }
                $values = $model->getDirtyAttributes($attributeNames);
                if (empty($values)) {
                    foreach ($model->getPrimaryKey(true) as $key => $value) {
                        $values[$key] = $value;
                    }
                }
                $command = $db->createCommand()->insert($model->tableName(), $values);

                $rawSql = $command->getRawSql();
                $sql.=$rawSql . ';';
            } else {
                if ($runValidation && !$model->validate($attributeNames)) {
                    Yii::info('Model not updated due to validation error.', __METHOD__);
                }
                if (!$model->beforeSave(true)) {
                    return false;
                }
                $values = $model->getDirtyAttributes($attributeNames);
                if (empty($values)) {
                    foreach ($model->getPrimaryKey(true) as $key => $value) {
                        $values[$key] = $value;
                    }
                }
                $command = $db->createCommand()->update($model->tableName(), $values);

                $rawSql = $command->getRawSql();
                $sql.=$rawSql . ';';
            }
            $i++;
            if ($i > 250) {
                $db->createCommand()->setSQL($sql)->execute();
                $sql = '';
                $i = 0;
            }
        }
        if ($i)
            $db->createCommand()->setSQL($sql)->execute();

        if ($this->isTransactional(self::OP_INSERT)) {
            try {
                $result = $this->insertInternal($attributes);
                if ($result === false) {
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
    }

    public function link($name, $model, $extraColumns = []) {
        try {
            return parent::link($name, $model, $extraColumns);
        }
        catch(\yii\db\IntegrityException $e) {
            $relation = $this->getRelation($name);
            if($relation->via !== null /*&& $this->isLinked($name,$model)*/) {
                if (is_array($relation->via)) {
                    /* @var $viaRelation ActiveQuery */
                    list($viaName, $viaRelation) = $relation->via;
                    $viaClass = $viaRelation->modelClass;
                } else {
                    $viaRelation = $relation->via;
                    $viaTable = reset($relation->via->from);
                }
                $columns = [];
                foreach ($viaRelation->link as $a => $b) {
                    $columns[$a] = $this->$b;
                }
                foreach ($relation->link as $a => $b) {
                    $columns[$b] = $model->$a;
                }
                foreach ($extraColumns as $k => $v) {
                    $columns[$k] = $v;
                }
                if (is_array($relation->via)) {
                    /* @var $viaClass ActiveRecordInterface */
                    /* @var $record ActiveRecordInterface */
                    $record = new $viaClass();
                    foreach ($columns as $column => $value) {
                        $record->$column = $value;
                    }
                    $record->update(false);
                } else {
                    $db = static::getDb();
                    $primaryKey = $db->getSchema()
                            ->getTableSchema($viaTable)
                            ->primaryKey;
                    $primaryKeyValues = [];
                    array_walk($primaryKey, function(&$var)
                            use (&$primaryKeyValues, &$columns) {
                        $primaryKeyValues[$var] = $columns[$var];
                            });
                    /* @var $viaTable string */
                    $db->createCommand()
                            ->update($viaTable, $columns, $primaryKeyValues)
                            ->execute();
                }
                return true;
            }
            throw $e;
        }
    }
}
