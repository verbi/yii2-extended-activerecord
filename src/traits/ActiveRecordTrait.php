<?php
namespace verbi\yii2ExtendedActiveRecord\traits;

trait ActiveRecordTrait {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    
    protected function arrayFilterUniqueActiveRecord($array) {
        return array_filter($array, function( $value ) {
            static $idList = array();
            if($value instanceof \yii\db\ActiveRecordInterface && !$value->getIsNewRecord()) {
                $pk = $value->getPrimarykey();
                if(in_array($pk, $idList)) {
                    return false;
                }
                $idList[] = $pk;
            }
            return true;
        });
    }
}