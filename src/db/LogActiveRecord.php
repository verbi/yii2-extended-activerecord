<?php

namespace verbi\yii2ExtendedActiveRecord\db;

use Yii;
use verbi\yii2ExtendedActiveRecord\db\ActiveRecord;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class LogActiveRecord extends ActiveRecord {

    use \verbi\yii2Helpers\traits\ComponentTrait;

    /**
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null) {
        $this->log_datetime = date('Y-m-d H:i:s');
        $this->log_user = Yii::$app->user->id;
        $this->log_version = 1;
        if (!$this->log_action) {
            $this->log_action = 'update';
        }
        $model = $this
                ->find()
                ->where($this->getPrimaryKey(true))
                ->orderby('log_version DESC')
                ->one();
        if ($model) {
            $this->log_version = $model->log_version + 1;
            $this->log_action = 'insert';
        }
        return parent::save($runValidation, $attributeNames);
    }

}
