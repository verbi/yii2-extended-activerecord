<?php

namespace verbi\yii2ExtendedActiveRecord\behaviors;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2DynamicForms\components\Form;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ModelFormBehavior extends Behavior {

    public $attributeFormInputTypes = [];
    public $formInputs = [
        'boolean' => Form::INPUT_CHECKBOX,
        'ntext' => Form::INPUT_TEXTAREA,
        'password' => Form::INPUT_PASSWORD,
        'multiselect' => Form::INPUT_MULTISELECT,
        'fileInput' => Form::INPUT_FILE,
        'dropdownList' => Form::INPUT_DROPDOWN_LIST,
        'widget' => Form::INPUT_WIDGET,
        'datetime' => [
            'type' => Form::INPUT_WIDGET,
        ],
    ];
    public $formInputOptions = [
        'datetime' => ['widgetClass' => '\kartik\widgets\DatePicker',],
        'email' => [],
        'url' => [],
    ];

    public function getFormAttributes() {
        return $this->owner->getAttributes();
    }

    public function getAttributesForForm() {
        $attributes = array();
        foreach ($this->owner->getFormAttributes() as $attribute => $value) {
            if ($this->owner->isAttributeSafe($attribute)) {
                $array = [];
                $input = $this->owner->getAttributeFormInput($attribute);
                if ($input) {
                    if (is_string($input)) {
                        $array['type'] = $input;
                    } elseif (is_array($input)) {
                        $array = $input;
                    }
                }
                if ((!isset($array['type']) || $array['type'] != 'widget') && (!isset($array['options']) || !isset($array['options']['placeholder']))
                ) {
                    $array['options']['placeholder'] = $this->owner->getAttributeLabel($attribute);
                }
                $attributes[$attribute] = $array;
            }
        }
        return $attributes;
    }

    public function getAttributeFormInputs() {
        return $this->owner->attributeFormInputTypes;
    }

    public function getAttributeFormInput($name) {
        $attributeFormInputs = $this->owner->getAttributeFormInputs();
        if (isset($attributeFormInputs[$name])) {
            return $attributeFormInputs[$name];
        }

        return $this->owner->generateActiveField($name);
    }

    public function getTableSchema() {
        return false;
    }

    public function generateActiveField($attribute) {
        $column = $this->owner->getAttributeColumn($attribute);
        if ($column && isset($this->owner->formInputs[$this->owner->generateColumnFormat($column)])) {
            return $this->owner->formInputs[$this->owner->generateColumnFormat($column)];
        } elseif ($column) {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = Form::INPUT_PASSWORD;
            } else {
                $input = Form::INPUT_TEXT;
            }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                $dropDownOptions = [];
                foreach ($column->enumValues as $enumValue) {
                    $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                }
                return Form::INPUT_DROPDOWN_LIST;
                return "\$form->field(\$model, '$attribute')->dropDownList("
                        . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)) . ", ['prompt' => ''])";
            } elseif ($column->phpType !== 'string' || $column->size === null) {
                return $input;
            } else {
                return $input;
            }
        }
    }

    /**
     * Generates column format
     * @param \yii\db\ColumnSchema $column
     * @return string
     */
    public function generateColumnFormat($column) {
        if (!$column) {
            return 'text';
        }
        if ($column->phpType === 'boolean') {
            return 'boolean';
        } elseif ($column->type === 'text') {
            return 'ntext';
        } elseif (
                $column->type == 'datetime' || ( (stripos($column->name, 'time') !== false || stripos($column->name, 'day') !== false) && $column->phpType === 'integer' )
        ) {
            return 'datetime';
        } elseif ($column->type == 'date') {
            return 'date';
        } elseif (stripos($column->name, 'email') !== false) {
            return 'email';
        } elseif (stripos($column->name, 'url') !== false) {
            return 'url';
        } else {
            return 'text';
        }
    }

    public function getAttributeColumn($attribute) {
        $tableSchema = $this->owner->getTableSchema();
        if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
            return null;
        }
        return $tableSchema->columns[$attribute];
    }

    public function getAttributeFormat($attribute) {
        $column = $this->owner->getAttributeColumn($attribute);
        //if(!$column)
        if (true or \Yii::$app->formatter instanceof verbi\yii2Helpers\i18n\Formatter) {
            foreach ($this->owner->getActiveValidators($attribute) as $validator) {
                if ($validator instanceof \verbi\yii2ExtendedActiveRecord\validators\PhoneValidator) {
                    if (!\Yii::$app->formatter->hasMethod('asPhone')) {
                        \Yii::$app->formatter->attachBehavior('phoneFormatter', '\verbi\yii2Helpers\behaviors\formatter\PhoneFormatterBehavior');
                    }
                    return 'phone';
                }
            }
        }
        return $this->owner->generateColumnFormat($column);
    }

}
