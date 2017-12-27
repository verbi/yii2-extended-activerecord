<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;

class PublishedModelBehavior  extends Behavior {
    use \verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait;
    
    public $rule = [
        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\PublishedRule',
    ];
    
    
    public function isPublished() {
//        die(print_r($this->owner->getAttributes(),true));
        return $this->owner->hasAttribute('published') ? $this->owner->getAttribute('published'):false;
    }
    
//    public function addAuthRules($controller, $parent = null) {die('test');
//        $auth = Yii::$app->authManager;
//        if($auth) {
//            $explodedClassName = explode('\\',$this->className());
//            $roleName = end($explodedClassName) . '-published';
//            if(!$owner = $auth->getRole($roleName)) {
//                $owner = $auth->createRole($roleName);
//                $newRule = new \verbi\yii2ExtendedActiveRecord\rbac\PublishedRule;
//                if(!$rule = $auth->getRule($newRule->name)) {
//                    // add the rule
//                    $rule = $newRule;
//                    $auth->add($rule);
//                }
//                
//                $owner->ruleName = $rule->name;
//                $auth->add($owner);
//            }
//            // add the "updateOwnPost" permission and associate the rule with it.
//            foreach(array_keys($controller->getActions()) as $actionId) {
//                $permissionName = $this->owner->className().'-'.$actionId.'-Own';
//                if(!$permission = $auth->getPermission($permissionName)) {
//                    $permission = $auth->createPermission($permissionName);
//                    $permission->description = $actionId . ' own ' . $this->owner->className();
//                    $permission->ruleName = $rule->name;
//                    $auth->add($permission);
//                }
//                if($parent) {
//                    $auth->addChild($permission, $parent);
//                }
//            }
//        }
//    }
    
}
