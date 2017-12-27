<?php

namespace verbi\yii2ExtendedActiveRecord\traits;

use yii\base\UnknownPropertyException;
use yii\base\UnknownMethodException;
use yii\db\Exception as DbException;
use Yii;
use Exception;

/**
 * Trait to simulate inheritance between two ActiveRecordInterface classes.
 * Child classes which use the trait must also implement the
 * ActiveRecordInheritanceInterface.
 * 
 * @see ActiveRecordInheritanceInterface
 * 
 * When this trait is linked, the call stack will follow the following logic:
 * * The default call stack is preserved. The method, property or attribute is
 * looked up first.
 * * Afterward it will do the same call stack for the "parent" ActiveRecord.
 * * These steps are repeated for every "parent" ActiveRecord, until there's
 * no "parents" left
 * * The last ActiveRecord just follows the Default stack
 * 
 * 
 * i.e.:
 * ```php
 * namespace my\name\space;
 * use verbi\yii2ExtendedActiveRecord\traits\ActiveRecordInheritanceTrait;
 * 
 * class User extends ActiveRecord {
 * }
 * 
 * class Admin extends ActiveRecord {
 *     use ActiveRecordInheritanceTrait;
 * 
 *     public static function extendsFrom() {
 *         return User::className();
 *     }
 * }
 * ```
 * 
 * The primary key of the child ActiveRecord is used as foreign key of the
 * parent model.
 * 
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
trait ActiveRecordInheritanceTrait {

    /**
     * @inheritdoc
     */
    public function __get($name) {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedGet')) {
                    try {
                        return $behavior->_inheritedGet($name);
                    } catch (Exception $exception) {
                        
                    }
                }
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value) {
        $result = false;
        try {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedSet')) {
                    try {
                        $behavior->_inheritedSet($name, $value);
                        $result = true;
                    } catch (Exception $exception) {
                        
                    }
                }
            }

            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            if (!$result) {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true) {
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_setInheritedAttributes')) {
                $behavior->_setInheritedAttributes($values, $safeOnly);
            }
        }
        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @inheritdoc
     */
    public function __unset($name) {
        try {
            if (parent::__get($name) !== null) {
                parent::__unset($name);
            }
        } catch (UnknownPropertyException $e) {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedUnset')) {
                    try {
                        return $behavior->_inheritedUnset($name);
                    } catch (UnknownMethodException $exception) {
                        
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params) {
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $e) {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedCall')) {
                    try {
                        return $behavior->_inheritedCall($name, $params);
                    } catch (UnknownMethodException $exception) {
                        
                    }
                }
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        $attributeLabels = parent::attributeLabels();
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_getInheritedAttributeLabels')) {
                $attributeLabels = array_merge($behavior->_getInheritedAttributeLabels(), $attributeLabels);
            }
        }
        return $attributeLabels;
    }

    /**
     * Returns attribute values.
     * 
     * @param array $names list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes listed in [[attributes()]] will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * @param array $except list of attributes whose value should NOT be returned.
     * @return array attribute values (name => value).
     */
    public function getAttributes($names = null, $except = array()) {


        if ($names === null) {
            $names = $this->attributes();
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedAttributes')) {
                    $names = array_merge($behavior->_inheritedAttributes(), $names);
                }
            }
        }
        return parent::getAttributes($names, $except);
    }

    /**
     * Saves the parent model and the current model.
     * 
     * @return boolean
     * @throws \Exception
     */
    public function save($runValidation = true, $attributeNames = null) {
        if ($runValidation === true && $this->validate($attributeNames) === false) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }
        $trans = static::getDb()->beginTransaction();
        try {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedSave')) {
                    if ($behavior->_inheritedSave(false, $attributeNames) === false) {
                        throw new DbException('Unable to save parent model');
                    }
                    if ($behavior->hasMethod('_inheritedPrimaryKey')) {
                        $this->setAttributes($behavior->_inheritedPrimaryKey());
                    }
                    if (parent::save(false, $attributeNames) === false) {
                        throw new DbException('Unable to save current model');
                    }
                }
            }

            $trans->commit();
            return true;
        } catch (Exception $e) {
            $trans->rollback();
            throw $e;
        }
    }

    /**
     * Deletes the table row corresponding to this active record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns false, it will skip the
     *    rest of the steps;
     * 2. delete the record from the database;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return integer|false the number of rows deleted, or false if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     * being deleted is outdated.
     * @throws \Exception in case delete failed.
     */
    public function delete() {
        $trans = static::getDb()->beginTransaction();
        try {
            $result = parent::delete();
            if ($result === false) {
                throw new DbException('Unable to delete current model');
            }
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedDelete')) {
                    if ($behavior->_inheritedDelete() === false) {
                        throw new DbException('Unable to delete parent model');
                    }
                }
            }
            $trans->commit();
            return $result;
        } catch (Exception $e) {
            $trans->rollback();
            throw $e;
        }
    }

    /**
     * Validates the parent and the current model.
     * 
     * @return boolean
     * @throws Exception
     */
    public function validate($attributeNames = null, $clearErrors = true) {
        $r = parent::validate($attributeNames === null ?
                                array_diff($this->attributes(), $this->getInheritedPrimaryKeys()) :
                                $attributeNames, $clearErrors);
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_inheritedValidate')) {
                $r = $behavior->_inheritedValidate($attributeNames, $clearErrors) && $r;
            }
        }

        return $r;
    }

    public function getInheritedPrimaryKeys() {
        $pk = [];
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_inheritedPrimaryKey')) {
                $pk = array_merge(array_keys($behavior->_inheritedPrimaryKey()), $pk);
            }
        }
        return $pk;
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * 
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null) {
        $hasErrors = parent::hasErrors($attribute);
        if (!$hasErrors) {
            foreach ($this->getBehaviors() as $behavior) {
                if ($behavior->hasMethod('_inheritedHasErrors')) {
                    if ($behavior->_inheritedHasErrors($attribute)) {
                        return true;
                    }
                }
            }
        }
        return $hasErrors;
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * 
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ~~~
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ~~~
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null) {
        $errors = parent::getErrors($attribute);
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_inheritedGetErrors')) {
                $errors = array_merge($behavior->_inheritedGetErrors($attribute), $errors);
            }
        }
        return $errors;
    }

    /**
     * Returns the first error of every attribute in the model.
     * 
     * @return array the first errors. The array keys are the attribute names, and the array
     * values are the corresponding error messages. An empty array will be returned if there is no error.
     * @see getErrors()
     * @see getFirstError()
     */
    public function getFirstErrors() {
        $errs = $this->getErrors();
        if (empty($errs)) {
            return [];
        } else {
            $errors = [];
            foreach ($errs as $name => $es) {
                if (!empty($es)) {
                    $errors[$name] = reset($es);
                }
            }
            return $errors;
        }
    }

    /**
     * Returns the first error of the specified attribute.
     * 
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     */
    public function getFirstError($attribute) {
        $errors = $this->getErrors($attribute);
        return count($errors) ? $errors[0] : null;
    }

    /**
     * @inheritdoc
     */
    public function refresh() {
        $r = parent::refresh();
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior->hasMethod('_inheritedRefresh')) {
                if (!$behavior->_inheritedRefresh()) {
                    $r = false;
                }
            }
        }
        return $r;
    }

}
