<?php

namespace verbi\yii2ExtendedActiveRecord\rbac;

use yii\rbac\Rule;
use yii\base\Model;

/**
 * Checks if owner_id matches user passed via params
 */
class PublishedRule extends Rule {

    public $name = 'isPublished';

    /**
     * @param string|int $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return bool a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params) {
        return isset($params['model']) && $params['model'] instanceof Model && $params['model']->hasMethod('isPublished') ? $params['model']->isPublished() : false;
    }

}
