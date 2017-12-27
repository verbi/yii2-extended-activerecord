<?php

namespace verbi\yii2ExtendedActiveRecord\base;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class DynamicSearchModel extends DynamicModel {

    public function __construct($modelClass, $config = []) {
        $model = new $modelClass;
        return parent::__construct($model->attributes(), $config);
    }

    public function rules() {
        return array_merge(parent::rules(), [[$this->attributes(), 'safe']]);
    }

}
