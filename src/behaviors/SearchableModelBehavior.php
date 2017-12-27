<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class SearchableModelBehavior extends Behavior {

    public $attributes = null;
    protected $fields = [
        'q' => [
            'label' => 'Search Term',
            'options' => [
                'name' => 'q',
                'placeholder' => '',
            ],
        ],
    ];
    protected $values = [];
    protected $fieldSets = [];

    public function getAttributesForSearch() {
        if (isset($this->attributes)) {
            return $this->attributes;
        }
        return array_slice($this->owner->attributes(), 2);
    }

    public function search($data, $formName = null) {
        $this->owner->load($data, $formName);
        $this->values = $data;
        $modelClass = $this->owner->className();
        $query = $modelClass::find();
        foreach (array_keys($this->fields) as $name) {
            if (isset($this->values[$name])) {
                $query->andWhere('MATCH (' . implode(',', $this->owner->getAttributesForSearch()) . ') AGAINST (:' . $name . ' IN NATURAL LANGUAGE MODE)', [':' . $name => $this->values[$name]]);
            }
        }
        return $query;
    }

    public function getAttributesForSearchForm() {
        return array_map(function($field) {
            if (isset($field['options']) && isset($field['options']['name'])) {
                $field['options']['value'] = isset($this->values[$field['options']['name']]) ? $this->values[$field['options']['name']] : '';
            }
            return $field;
        }, $this->fields);
    }

    protected function generateWhereCondition($field, $query) {
        $tableSchema = $this->owner->getTableSchema();
    }

}
