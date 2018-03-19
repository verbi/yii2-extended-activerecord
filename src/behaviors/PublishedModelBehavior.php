<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;

class PublishedModelBehavior extends Behavior {

    use \verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait;

    public $rule = [
        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\PublishedRule',
    ];
    
    public $attributeName = 'published';
    
    public $defaultValue = true;
    
    public function events() {
        $ownerClass = $this->owner->className();
        return [
//            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }

//    public function afterGetFormAttributes(GeneralFunctionEvent $event) {
//        if ($this->owner && isset($event->params['attributes']) && array_key_exists($this->attributeName, $event->params['attributes'])) {
//            $params = $event->params;
//            $event->setparams($params);
//            $event->setReturnValue($params['attributes']);
//        }
//    }

    public function afterCreateValidators(GeneralFunctionEvent $event) {
        if ($this->owner) {
            if (
                    isset($event->params['validators']) && $event->params['validators'] instanceof ArrayObject
            ) {
                if ($this->owner->hasAttribute($this->attributeName)) {
                    $sw = true;
                    foreach ($event->params['validators'] as $validator) {
                        if ($validator instanceof DefaultValueValidator && in_array($this->attributeName, $validator->attributes)) {
                            $sw = false;
                        }
                    }
                    $validators = $event->params['validators'];
                    $validators->prepend(Validator::createValidator(
                                        'boolean', $this->owner, [$this->attributeName]
                        ));
                    if($sw) {
                        $validator = Validator::createValidator(
                                        'default', $this->owner, [$this->attributeName], ['value' => $this->defaultValue]
                        );
                        $validators->prepend($validator);
                    }
                    $event->setReturnValue($validators);
                    return;
                }
            }
        }
    }
    

    public function isPublished() {
        return $this->owner->hasAttribute($this->attributeName) ? $this->owner->getAttribute($this->attributeName) : false;
    }

}
