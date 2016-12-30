<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\base\ArrayObject;
use yii\validators\DefaultValueValidator;
use yii\validators\Validator;
/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class UserOwnedModelBehavior extends Behavior {
    public function events() {
        $ownerClass = $this->owner->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }
    
    public function afterGetFormAttributes(GeneralFunctionEvent $event) {
        if($this->owner && isset($event->params['attributes']) && array_key_exists('owner_id',$event->params['attributes'])) {
            $params = $event->params;
            unset($params['attributes']['owner_id']);
            $event->setparams($params);
            $event->setReturnValue($params['attributes']);
        }
    }
    
    public function afterCreateValidators(GeneralFunctionEvent $event) {
        if($this->owner) {
            if(
                !\Yii::$app->user->isGuest
                && isset($event->params['validators']) 
                && $event->params['validators'] instanceof ArrayObject
            ) {
                if($this->owner->hasAttribute('owner_id')) {
                    foreach($event->params['validators'] as $validator) {
                        if(
                                $validator instanceof DefaultValueValidator
                                && in_array('owner_id',$validator->attributes)) {
                            return;
                        }
                    }
                $validator = Validator::createValidator(
                        'default',
                        $this->owner,
                        ['owner_id'],
                        ['value' => \Yii::$app->user->identity->getId()]
                    );
                $validators = $event->params['validators'];
                $validators->prepend($validator);
                $event->setReturnValue($validators);
                return;
                }
            }
        }
    }
}