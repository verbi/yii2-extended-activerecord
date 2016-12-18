<?php

namespace verbi\yii2ExtendedActiveRecord\db;

use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\base\ArrayObject;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use verbi\yii2ExtendedActiveRecord\base\ModelEvent;
use Yii;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveRecord extends \yii\db\ActiveRecord {
    use \verbi\yii2ExtendedActiveRecord\traits\ActiveRecordTrait;
    use \verbi\yii2ExtendedActiveRecord\traits\ModelFormTrait;
    
    const EVENT_BEFORE_SETATTRIBUTES = 'beforeSetAttributes';
    const EVENT_AFTER_SETATTRIBUTES = 'afterSetAttributes';
    const EVENT_BEFORE_RULES = 'beforeRules';
    const EVENT_AFTER_RULES = 'afterRules';
    const EVENT_BEFORE_CREATE_VALIDATORS = 'beforeCreateValidators';
    const EVENT_AFTER_CREATE_VALIDATORS = 'afterCreateValidators';
    
    /**
     * @var array attribute values indexed by attribute names
     */
    protected $_attributes = [];
    
    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    protected $_oldAttributes;

    /**
     * @inheritdoc
     */
    public function rules() {
        if (!$this->beforeRules()) {
            return [];
        }
        $rules = parent::rules();
        $this->afterRules($rules);
        return $rules;
    }

    protected function beforeRules() {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_RULES, $event);
        return $event->isValid;
    }

    protected function afterRules(&$rules) {
        $event = new ModelEvent;
        $event->data = ['rules' => &$rules,];
        $this->trigger(self::EVENT_AFTER_RULES, $event);
        return $event->isValid;
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find() {
        return \Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public function findUnauthorized() {
        return parent::find();
    }

    public function getAccessRule($identity = null) {
        return array();
    }

    public function getAccessQuery($identity) {
        $query = (new \yii\db\Query())
                ->select('id')
                ->from($this->tableName())
                ->where($this->getAccessRule($identity));
        return $query;
    }

    public function checkAccess($identity) {
        if ($this->find()->Where($this->getAccessRule($identity))->andWhere($this->getPrimaryKey(true))->one()) {
            return true;
        }
        return false;
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

    /**
     * @inheritdoc
     */
    public function setAttributes($attributes, $safeOnly = true) {
        if (!$this->beforeSetAttributes($attributes, $safeOnly)) {
            return false;
        }
        $result = parent::setAttributes($attributes, $safeOnly);
        $this->afterSetAttributes($attributes, $safeOnly);
        return $result;
    }

    protected function beforeSetAttributes(&$attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => &$attributes, 'safeOnly' => $safeOnly,];
        $this->trigger(self::EVENT_BEFORE_SETATTRIBUTES, $event);
        return $event->isValid;
    }

    protected function afterSetAttributes(&$attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => &$attributes, 'safeOnly' => $safeOnly,];
        $this->trigger(self::EVENT_AFTER_SETATTRIBUTES, $event);
        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function isAttributeSafe($attribute) {

        if ($this->owner->isPrimaryKey([$attribute]))
            return false;
        return parent::isAttributeSafe($attribute);
    }

    public function __toString() {
        foreach ($this->attributes() as $name => $value) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $this->$name;
            }
        }
        /* @var $class \yii\db\ActiveRecord */
        $pk = $this->primaryKey();
        $str = '';
        foreach ($pk as $value) {
            $str .= ' ' . $this->$value;
        }
        return trim($str);
    }

    public function label() {
        return Inflector::camel2words(StringHelper::basename($this->className()));
    }

    protected function getCurrentUser($user = null) {
        if (!$user && !\Yii::$app->getUser()->isGuest) {
            $user = \Yii::$app->getUser()->getIdentity();
        }
        return $user;
    }
    
    public function createValidators()
    {
        $validators = new ArrayObject;
        if (!$this->beforeCreateValidators()) {
            return [];
        }
        $validators->exchangeArray((array) parent::createValidators());
        return $this->afterCreateValidators($validators);
    }
    
    protected function beforeCreateValidators() {
        $event = new GeneralFunctionEvent;
        $this->trigger(self::EVENT_BEFORE_CREATE_VALIDATORS, $event);
        return $event->isValid;
    }

    protected function afterCreateValidators(&$validators) {
        $event = new GeneralFunctionEvent;
        $event->params = ['validators' => &$validators,];
        $this->trigger(self::EVENT_AFTER_CREATE_VALIDATORS, $event);
        return $event->hasReturnValue()?$event->getReturnValue():$validators;
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
