<?php

namespace verbi\yii2ExtendedActiveRecord\elasticsearch;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ActiveRecord extends \yii\elasticsearch\ActiveRecord {

    use \verbi\yii2ExtendedActiveRecord\traits\ActiveRecordTrait;

use \verbi\yii2ExtendedActiveRecord\traits\ModelFormTrait;

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find() {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public function findUnauthorized() {
        return parent::find();
    }

    public function getAccessQuery($identity) {
        $query = (new Query())
                ->select('id')
                ->from($this->tableName())
                ->where($this->getAccessRule($identity));
        return $query;
    }

}
