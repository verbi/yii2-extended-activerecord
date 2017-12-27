<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\behaviors\base\filters\AccessRule;
use yii\base\Action;
use yii\web\User;
use yii\web\Request;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class AccessRuleModelBehavior extends Behavior {

    public static $EVENT_BEFORE_CHECK_MODEL_ACCESS = 'eventBeforeCheckModelAccess';
    public static $EVENT_AFTER_CHECK_MODEL_ACCESS = 'eventAfterCheckModelAccess';
    public $rule = [
        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule',
    ];

    public function events() {
        return array_merge(parent::events(), [
            AccessRule::$EVENT_MATCH_MODEL => [$this, 'eventMatchModel'],
        ]);
    }

    public function eventMatchModel(GeneralFunctionEvent $event) {
        $params = $event->params;
        $event->isValid = $this->modelAccessFilter($params['action'], $params['user'], $params['request']);
    }

    public function checkModelAccess($action, $user, $request) {
        return true;
    }

    public function modelAccessFilter(Action $action, User $user, Request $request) {
        $event = new GeneralFunctionEvent([
            'params' => [
                'action' => $action,
                'user' => $user,
                'request' => $request,
            ],
        ]);
        $this->owner->trigger(static::$EVENT_AFTER_CHECK_MODEL_ACCESS, $event);
        if ($event->isValid) {
            // Actually check the access
            $result = $this->owner->checkModelAccess($action, $user, $request);

            $event = new GeneralFunctionEvent([
                'params' => [
                    'action' => $action,
                    'user' => $user,
                    'request' => $request,
                    'result' => &$result,
                ],
            ]);
            $this->owner->trigger(static::$EVENT_AFTER_CHECK_MODEL_ACCESS, $event);
            if ($event->isValid) {
                if ($event->hasReturnValue()) {
                    $result = $event->getReturnValue();
                }
                return $result;
            }
        }
        return false;
    }

}
