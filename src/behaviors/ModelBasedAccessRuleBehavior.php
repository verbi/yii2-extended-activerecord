<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use Yii;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\behaviors\base\AccessControl;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\base\ArrayObject;
use yii\validators\DefaultValueValidator;
use yii\validators\Validator;


/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ModelBasedAccessRuleBehavior extends Behavior {
    
//    public $events = [
//        AccessControl::EVENT_AFTER_GENERATE_RULES => 'eventAfterGenerateRules',
//    ];
//    
    public function addAuthRules($controller) {
        $auth = Yii::$app->authManager;
        if($auth) {
            $explodedClassName = explode('\\',$this->owner->className());
            $roleName = end($explodedClassName) . '-model-based';
            if(!$owner = $auth->getRole($roleName)) {
                $owner = $auth->createRole($roleName);
                $newRule = new \verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule;
                if(!$rule = $auth->getRule($newRule->name)) {
                    // add the rule
                    $rule = $newRule;
                    $auth->add($rule);
                }
                
//                $auth->add($rule);
                $owner->ruleName = $rule->name;
                $auth->add($owner);
            }
            // add the "updateOwnPost" permission and associate the rule with it.
            foreach(array_keys($controller->getActions()) as $actionId) {
                $permissionName = $this->owner->className().'-'.$actionId.'-Model-Based';
                if(!$permission = $auth->getPermission($permissionName)) {
                    $permission = $auth->createPermission($permissionName);
                    $permission->description = $actionId . ' model based ' . $this->owner->className();
                    $permission->ruleName = $rule->name;
                    $auth->add($permission);
                }
//              $auth->addChild($permission, $updatePost);
            }
        }
    }
    
    public function getAccessRules($accessControl) {
        $rules = [];
        $explodedClassName = explode('\\',$this->owner->className());
        $roleName = end($explodedClassName) . '-model-based';
//        $this->owner->loadModel
        return [Yii::createObject(array_merge($accessControl->ruleConfig, [
                    'allow' => true,
                    'actions' => array_keys($accessControl->owner->getActions()),
//                    'roles' => [$roleName],
////                    'roles' => [$actionId . ' model based ' . $this->owner->className()],
//                    'roleParams' => function() use ($accessControl) {
//                        return ['model' => $accessControl->owner->loadModel($accessControl->owner->getPkFromRequest())];
//                    }
            ]))];
    }
    
    
    public function eventAfterGenerateRules(GeneralFunctionEvent $event) {
        die('event');
    }
}