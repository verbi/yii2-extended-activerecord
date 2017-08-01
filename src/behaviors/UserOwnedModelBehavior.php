<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use Yii;
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
    public $attributeName = 'owner_id';
    
    public function events() {
        $ownerClass = $this->owner->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }

    public function afterGetFormAttributes(GeneralFunctionEvent $event) {
        if ($this->owner && isset($event->params['attributes']) && array_key_exists($this->attributeName, $event->params['attributes'])) {
            $params = $event->params;
            unset($params['attributes'][$this->attributeName]);
            $event->setparams($params);
            $event->setReturnValue($params['attributes']);
        }
    }

    public function afterCreateValidators(GeneralFunctionEvent $event) {
        if ($this->owner) {
            if (
                    !\Yii::$app->user->isGuest && isset($event->params['validators']) && $event->params['validators'] instanceof ArrayObject
            ) {
                if ($this->owner->hasAttribute($this->attributeName)) {
                    foreach ($event->params['validators'] as $validator) {
                        if (
                                $validator instanceof DefaultValueValidator && in_array($this->attributeName, $validator->attributes)) {
                            return;
                        }
                    }
                    $validator = Validator::createValidator(
                                    'default', $this->owner, [$this->attributeName], ['value' => \Yii::$app->user->identity->getId()]
                    );
                    $validators = $event->params['validators'];
                    $validators->prepend($validator);
                    $event->setReturnValue($validators);
                    return;
                }
            }
        }
    }
    
    public function addAuthRules($controller) {
        $auth = Yii::$app->authManager;
        $owner = $auth->createRole('owner');
        if($auth) {
            // add the rule
            $rule = new \verbi\yii2ExtendedActiveRecord\rbac\UserOwnedRule;
            $owner->ruleName = $rule->name;
            $auth->add($owner);
            // add the "updateOwnPost" permission and associate the rule with it.
            foreach(array_keys($controller->getActions()) as $actionId){
                $permission = $auth->createPermission($this->owner->className().'-'.$actionId.'-Own');
                $permission->description = $actionId . ' own ' . $this->owner->className();
                $permission->ruleName = $rule->name;
                $auth->add($permission);
//                $auth->addChild($permission, $updatePost);
            }
        }
    }
    
    public function isOwner($user) {
        return $this->owner->{$this->attributeName}==$user;
    }
}
