<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2ExtendedActiveRecord\elasticsearch\ActiveRecord as ElasticSearchActiveRecord;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class SearchableModelBehavior extends Behavior {
    public $searchModel;
    
    public function search($data, $formName = null) {
        $model = $this->owner;
       
        if($this->owner->searchModel instanceof ElasticSearchActiveRecord) {
            $model = $this->owner->searchModel;
        }
        
        // search logic
        if($model instanceof ElasticSearchActiveRecord) {
            
        }
    }
}