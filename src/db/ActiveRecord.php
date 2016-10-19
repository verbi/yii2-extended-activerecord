<?php

namespace verbi\yii2ExtendedActiveRecord\db;

use verbi\yii2ExtendedActiveRecord\behaviors\ModelFormBehavior;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use verbi\yii2Helpers\behaviors\base\ComponentBehavior;
use verbi\yii2ExtendedActiveRecord\base\ModelEvent;
use Yii;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveRecord extends \yii\db\ActiveRecord {

    const EVENT_BEFORE_SETATTRIBUTES = 'beforeSetAttributes';
    const EVENT_AFTER_SETATTRIBUTES = 'afterSetAttributes';
    const EVENT_BEFORE_RULES = 'beforeRules';
    const EVENT_AFTER_RULES = 'afterRules';

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return array_merge(parent::behaviors(), [
            ComponentBehavior::className(),
            // get field names
            ModelFormBehavior::className(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        if (!$this->beforeRules()) {
            return false;
        }
        $rules = parent::rules();
        return $rules;
    }

    
    protected function beforeRules() {
        
    }

    protected function afterRules($rules) {
        
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

    protected function beforeSetAttributes($attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => $attributes, 'safeOnly' => $safeOnly,];
        $this->trigger(self::EVENT_BEFORE_SETATTRIBUTES, $event);
        return $event->isValid;
    }

    protected function afterSetAttributes($attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => $attributes, 'safeOnly' => $safeOnly,];
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
        foreach ($pk as $key => $value) {
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
}
