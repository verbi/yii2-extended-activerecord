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

    use \verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait;

    public $rule = [
        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\UserOwnedRule',
    ];
    
    public $attributeName = 'owner_id';

    public function events() {
        $ownerClass = $this->owner->className();
        return [
            $ownerClass::$EVENT_AFTER_GET_FORM_ATTRIBUTES => 'afterGetFormAttributes',
            $ownerClass::$EVENT_AFTER_CREATE_VALIDATORS => 'afterCreateValidators',
        ];
    }

    public function eventMatchModel(GeneralFunctionEvent $event) {
        if ($this->owner->isOwner($event->params['user']->getId())) {
            return true;
        }
        return false;
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
                                        'exist', $this->owner, [$this->attributeName], ['targetClass' => \Yii::$app->user->identityClass, 'targetAttribute' => [$this->attributeName => 'id'], 'skipOnError' => true]
                        ));
                    if($sw) {
                        $validator = Validator::createValidator(
                                        'default', $this->owner, [$this->attributeName], ['value' => \Yii::$app->user->identity?\Yii::$app->user->identity->getId():null]
                        );
                        
                        $validators->prepend($validator);
                    }
                    
                    $event->setReturnValue($validators);
                    return;
                }
            }
        }
    }

    public function addAuthRules($controller, $parent = null) {
        $auth = Yii::$app->authManager;
        if ($auth) {
            $explodedClassName = explode('\\', $this->className());
            $roleName = end($explodedClassName) . '-owner';
            if (!$owner = $auth->getRole($roleName)) {
                $owner = $auth->createRole($roleName);
                $newRule = new \verbi\yii2ExtendedActiveRecord\rbac\UserOwnedRule;
                if (!$rule = $auth->getRule($newRule->name)) {
                    // add the rule
                    $rule = $newRule;
                    $auth->add($rule);
                }

                $owner->ruleName = $rule->name;
                $auth->add($owner);
            }
            // add the "updateOwnPost" permission and associate the rule with it.
            foreach (array_keys($controller->getActions()) as $actionId) {
                $permissionName = $this->owner->className() . '-' . $actionId . '-Own';
                if (!$permission = $auth->getPermission($permissionName)) {
                    $permission = $auth->createPermission($permissionName);
                    $permission->description = $actionId . ' own ' . $this->owner->className();
                    $permission->ruleName = $rule->name;
                    $auth->add($permission);
                }
                if ($parent) {
                    $auth->addChild($permission, $parent);
                }
            }
        }
    }

    public function isOwner($user) {
        return $user !== null && $this->owner->{$this->attributeName} == $user;
    }
}
