<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use Yii;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\base\ArrayObject;
use verbi\yii2ExtendedActiveRecord\validators\SetValueValidator;
use yii\validators\Validator;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class AuditableModelBehavior extends Behavior {

    private $attributes;

    public function init() {
        parent::init();
        
        
        if (!is_array($this->attributes)) {
            $identity = Yii::$app->user->identity;
            $this->attributes = [
                'created_on' => [
                    'validators' => [
                        [
                            SetValueValidator::className(),
                            'onlyIfNewRecord' => false,
                            'value' => Yii::$app->formatter->asDatetime(time()),
                        ],
                        [
                            'datetime',
                        ],
                    ],
                ],
                'created_by' => [
                    'validators' => [
                        [
                            SetValueValidator::className(),
                            'value' => $identity?$identity->getId():null,
                        ],
                        [
                            'exist',
                            'skipOnError' => true,
                            'targetClass' => Yii::$app->user->identityClass,
                            'targetAttribute' => [
                                'created_by' => 'id',
                            ],
                        ],
                    ],
                ],
                'updated_by' => [
                    'validators' => [
                        [
                            SetValueValidator::className(),
                            'onlyIfNewRecord' => false,
                            'value' => $identity?$identity->getId():null,
                        ],
                        [
                            'exist',
                            'skipOnError' => true,
                            'targetClass' => Yii::$app->user->identityClass,
                            'targetAttribute' => [
                                'updated_by' => 'id',
                            ],
                        ],
                    ],
                ],
                'updated_on' => [
                    'validators' => [
                        [
                            SetValueValidator::className(),
                            'onlyIfNewRecord' => false,
                            'value' =>  Yii::$app->formatter->asDatetime(time()),
                        ],
                        [
                            'datetime',
                        ],
                    ],
                ],
            ];
        }
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
            if (isset($event->params['validators']) && $event->params['validators'] instanceof ArrayObject) {
                array_walk($this->attributes, function($settings, $attribute) use ($event) {
                    if ($this->owner->hasAttribute($attribute)) {
                        $validators = $event->params['validators'];
                        if (isset($settings['validators']) && is_array($settings['validators'])) {
                            foreach ($settings['validators'] as $validator) {

                                $validatorName = array_shift($validator);

                                $validators->append(
                                        Validator::createValidator(
                                                $validatorName, $this->owner, [$attribute], $validator
                                        )
                                );
                            }
                        }

                        $event->setReturnValue($validators);
                    }
                });
            }
        }
    }

    public function afterSave($event) {
        
    }

}
