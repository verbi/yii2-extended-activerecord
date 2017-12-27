<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;

class PublishedModelBehavior extends Behavior {

    use \verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait;

    public $rule = [
        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\PublishedRule',
    ];

    public function isPublished() {
        return $this->owner->hasAttribute('published') ? $this->owner->getAttribute('published') : false;
    }

}
