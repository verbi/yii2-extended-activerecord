<?php
namespace verbi\yii2ExtendedActiveRecord\traits;

use Yii;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\base\ArrayObject;
use verbi\yii2Helpers\behaviors\base\AccessControl;
use yii\validators\DefaultValueValidator;
use yii\validators\Validator;

Trait UserOwnedModelTrait {
    public function events() {
        $ownerClass = $this->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }

    public function afterGetFormAttributes(GeneralFunctionEvent $event) {
        if (isset($event->params['attributes']) && array_key_exists('owner_id', $event->params['attributes'])) {
            $params = $event->params;
            unset($params['attributes']['owner_id']);
            $event->setparams($params);
            $event->setReturnValue($params['attributes']);
        }
    }

    public function afterCreateValidators(GeneralFunctionEvent $event) {

            if (
                    !\Yii::$app->user->isGuest && isset($event->params['validators']) && $event->params['validators'] instanceof ArrayObject
            ) {
                if ($this->hasAttribute('owner_id')) {
                    foreach ($event->params['validators'] as $validator) {
                        if (
                                $validator instanceof DefaultValueValidator && in_array('owner_id', $validator->attributes)) {
                            return;
                        }
                    }
                    $validator = Validator::createValidator(
                                    'default', $this, ['owner_id'], ['value' => \Yii::$app->user->identity->getId()]
                    );
                    $validators = $event->params['validators'];
                    $validators->prepend($validator);
                    $event->setReturnValue($validators);
                    return;
                }
            }
        
    }
    
    public function addAuthRules($controller) {
        $auth = Yii::$app->authManager;

        if($auth) {
            // add the rule
            $rule = new \verbi\yii2ExtendedActiveRecord\rbac\UserOwnedRule;
            $auth->add($rule);
            // add the "updateOwnPost" permission and associate the rule with it.
            foreach(array_keys($controller->getActions()) as $actionId){
                $permission = $auth->createPermission($this->className().'-'.$actionId.'-Own');
                $permission->description = 'Update own '.$this->className();
                $permission->ruleName = $rule->name;
                $auth->add($permission);
                
                
                $auth->addChild($permission, $updatePost);
            }
        }
    }
}