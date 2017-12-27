<?php

namespace verbi\yii2ExtendedActiveRecord\rbac;

use yii\rbac\Rule;
use yii\base\Model;

/**
 * Checks if owner_id matches user passed via params
 */
class ModelBasedRule extends Rule {

    public $name = 'modelBased';

    /**
     * @param string|int $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return bool a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params) {
        if (isset($params['model']) && $params['model'] instanceof Model) {
            return true;
        }
        return false;
    }

}
