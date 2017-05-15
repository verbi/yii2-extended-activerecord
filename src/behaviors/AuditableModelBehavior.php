<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class AuditableModelBehavior extends Behavior {

    private $attributes = [
        'created_on' => '',
        'created_by' => '',
        'updated_by' => '',
        'updated_on' => '',
    ];

    public function events() {
        $ownerClass = $this->owner->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }

    public function afterGetFormAttributes(GeneralFunctionEvent $event) {


        foreach (array_keys($this->attributes) as $attribute) {
            if ($this->owner && isset($event->params['attributes']) && array_key_exists($attribute, $event->params['attributes'])) {
                $params = $event->params;
                unset($params['attributes'][$attribute]);
                $event->setparams($params);
                $event->setReturnValue($params['attributes']);
            }
        }
    }

    public function afterCreateValidators(GeneralFunctionEvent $event) {
        if ($this->owner) {
            if (
                    !\Yii::$app->user->isGuest && isset($event->params['validators']) && $event->params['validators'] instanceof ArrayObject
            ) {
                
                array_walk($this->attributes, function($function, $attribute) {
                    
                    if ($this->owner->hasAttribute($attribute)) {
                        foreach ($event->params['validators'] as $validator) {
                            if ($validator instanceof DefaultValueValidator && in_array($attribute, $validator->attributes)) {
                                return;
                            }
                        }
                        $validator = Validator::createValidator(
                            'default', $this->owner, [$attribute], ['value' => \Yii::$app->user->identity->getId()]
                        );
                        $validators = $event->params['validators'];
                        $validators->prepend($validator);
                        $event->setReturnValue($validators);
                        return;
                    }
                    
                });
            }
        }
    }

}
