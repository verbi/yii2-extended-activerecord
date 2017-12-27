<?php

namespace verbi\yii2ExtendedActiveRecord\base\behaviors;

use verbi\yii2Helpers\behaviors\base\AttributeBehavior;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
abstract class ActiveRecordInheritanceBehavior extends AttributeBehavior {

    protected $modelClass = null;
    protected $model;

    protected function getModel() {
        if (!$this->model) {
            $modelClass = $this->modelClass;
            if ($this->owner->getAttribute('id')) {
                $this->model = $modelClass::find(['id' => $this->owner->getAttribute('id')])->one();
            }
            if (!$this->model) {
                $this->model = new $modelClass();
            }
        }
        return $this->model;
    }

    public function _setInheritedAttributes($values, $safeonly = false) {
        $model = $this->getModel();
        if ($model) {
            $model->setAttributes($values, $safeonly);
        }
    }

    public function _getInheritedAttributeLabels() {
        $model = $this->getModel();
        if ($model) {
            return $model->attributeLabels();
        }
        return [];
    }

    public function _inheritedAttributes() {
        $model = $this->getModel();
        if ($model) {
            return $model->attributes();
        }
        return [];
    }

    public function _inheritedPrimaryKey() {
        $model = $this->getModel();
        if ($model) {
            return $model->getPrimaryKey(true);
        }
        return [];
    }

    public function _inheritedSave($runValidation = true, $attributeNames = null) {
        $model = $this->getModel();
        if ($model) {
            return $model->save($runValidation, $attributeNames);
        }
        return true;
    }

    public function _inheritedDelete() {
        $model = $this->getModel();
        if ($model) {
            return $model->delete();
        }
        return true;
    }

    public function _inheritedValidate($attributeNames = null, $clearErrors = true) {
        $model = $this->getModel();
        if ($model) {
            return $model->validate($attributeNames, $clearErrors);
        }
        return true;
    }

    public function _inheritedHasErrors($attribute = null) {
        $model = $this->getModel();
        if ($model) {
            return $model->hasErrors($attribute);
        }
        return false;
    }

    public function _inheritedGetErrors($attribute = null) {
        $model = $this->getModel();
        if ($model) {
            return $model->getErrors($attribute);
        }
        return [];
    }

    public function _inheritedRefresh() {
        $model = $this->getModel();
        if ($model) {
            return $model->refresh();
        }
        return true;
    }

    public function _inheritedGet($name) {
        $model = $this->getModel();
        if ($model) {
            return $model->__get($name);
        }
        return parent::__get($name);
    }

    public function _inheritedSet($name, $value) {
        $model = $this->getModel();
        if ($model) {
            return $model->__set($name, $value);
        }
        return;
    }

    public function _inheritedCall($name, $params) {
        $model = $this->getModel();
        if ($model) {
            return call_user_func_array([$model, $name], $params);
        }
        return parent::__call($name, $params);
    }

    public function _inheritedUnset($name) {
        $model = $this->getModel();
        if ($model) {
            return $model->__unset($name);
        }
    }

}
