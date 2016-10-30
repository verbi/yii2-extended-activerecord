<?php

namespace verbi\yii2ExtendedActiveRecord\db;

use Yii;
use Exception;
use verbi\yii2ExtendedActiveRecord\db\ActiveRecord;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class LoggedActiveRecord extends ActiveRecord {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    
    /**
     * Saves and logs.
     * 
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null) {
        $newRecord = $this->isNewRecord;
        $result = parent::save($runValidation, $attributeNames);
        $this->createLogRecord($newRecord ? 'insert' : 'update');
        return $result;
    }

    /**
     * Deletes and also logs.
     * 
     * @inheritdoc
     */
    public function delete() {
        $this->createLogRecord('delete');
        return parent::delete();
    }

    /**
     * Creates a log record, and saves it to an ActiveRecord.
     * 
     * @inheritdoc
     */
    public function createLogRecord($action) {
        try {
            $className = self::className();
            $logModel = new $className;
            $logModel->setAttributes($this->attributes);
            $logModel->log_action = $action;
            return $logModel->save();
        } catch (Exception $e) {
            Yii::$app->error($e->getMessage(), 'Log');
        }
        return false;
    }
}
