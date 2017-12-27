<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use Yii;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\behaviors\base\Behavior;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class AuditableModelBehavior extends Behavior {

    private $attributes = [];

    public function init() {
        parent::init();
        $attributes = [
            'created_on' => function($obj) {
                return $obj->owner->getIsNewRecord ? date('Y-m-d H:i:s') : $obj->created_on;
            },
            'created_by' => function($obj) {
                return $obj->owner->getIsNewRecord ? Yii::$app->user->identity->getId() : $obj->created_by;
            },
            'updated_by' => function($obj) {
                return Yii::$app->user->identity->getId();
            },
            'updated_on' => function($obj) {
                return date('Y-m-d H:i:s');
            },
        ];
        $this->attributes = array_merge($attributes, $this->attributes);
    }

    public function events() {
        $ownerClass = $this->owner->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
            $ownerClass::EVENT_AFTER_UPDATE => 'afterSave',
            $ownerClass::EVENT_AFTER_INSERT => 'afterSave',
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
                array_walk($this->attributes, function($function, $attribute) use ($event) {
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

    public function afterSave($event) {
        
    }

}
