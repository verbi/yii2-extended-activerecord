<?php

namespace verbi\yii2ExtendedActiveRecord\validators;

class SetValueValidator extends Validator {

    public $value;
    
    /**
     * @var bool This property makes sure that dirty attributes are skipped.
     */
    public $skipChanged = true;
    
    public $onlyIfNewRecord = true;
    
    /**
     * @var bool this property is overwritten to be false so that this validator will
     * be applied when the value being validated is empty.
     */
    public $skipOnEmpty = false;
    
    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        if ((!$this->onlyIfNewRecord || $model->getIsNewRecord())
                && (!$this->skipChanged || !$model->isAttributeChanged($attribute))) {
            if ($this->value instanceof \Closure) {
                $model->$attribute = call_user_func($this->value, $model, $attribute);
            } else {
                $model->$attribute = $this->value;
            }
        }
    }
}