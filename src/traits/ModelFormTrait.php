<?php
namespace verbi\yii2ExtendedActiveRecord\traits;

use verbi\yii2DynamicForms\components\Form;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use yii\web\JsExpression;

trait ModelFormTrait {
    public static $builtInFormFields = [
        'richText' => [
            'type' => Form::INPUT_WIDGET,
            'widgetClass' => '\dosamigos\ckeditor\CKEditor',
        ]
    ];
    
    public static $builtInFormFieldsValidatorMapping = [
        'richText' => 'richText',
    ];
    
    public static $EVENT_BEFORE_GET_FORM_ATTRIBUTES = 'beforeGetFormAttributes';
    public static $EVENT_AFTER_GET_FORM_ATTRIBUTES = 'afterGetFormAttributes';
    public $attributeFormInputTypes = [];
    public $formInputs = [
        'text' => Form::INPUT_TEXT,
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
        if (!$this->beforeGetFormAttributes()) {
            return [];
        }
        $attributes = $this->getAttributes();
        return $this->afterGetFormAttributes($attributes);
    }

    protected function beforeGetFormAttributes() {
        $event = new GeneralFunctionEvent;
        $this->trigger(self::$EVENT_BEFORE_GET_FORM_ATTRIBUTES, $event);
        return $event->isValid;
    }

    protected function afterGetFormAttributes(&$attributes) {
        $event = new GeneralFunctionEvent;
        $event->params = ['attributes' => &$attributes,];
        $this->trigger(self::$EVENT_AFTER_GET_FORM_ATTRIBUTES, $event);
        return $event->hasReturnValue() ? $event->getReturnValue() : $attributes;
    }

    public function getAttributesForForm() {
        $attributes = array();
        foreach ($this->getFormAttributes() as $attribute => $value) {
            if ($this->isAttributeSafe($attribute)) {
                $array = [];
                $input = $this->getAttributeFormInput($attribute);
                if ($input) {
                    if (is_string($input)) {
                        $array['type'] = $input;
                    } elseif (is_array($input)) {
                        $array = $input;
                    }
                }
                if ((!isset($array['type']) || $array['type'] != 'widget') && (!isset($array['options']) || !isset($array['options']['placeholder']))
                ) {
                    $array['options']['placeholder'] = $this->getAttributeLabel($attribute);
                }
                $attributes[$attribute] = $array;
            }
        }
        return $attributes;
    }

    public function getAttributeFormInputs() {
        return $this->attributeFormInputTypes;
    }

    public function getAttributeFormInput($name) {
        $attributeFormInputs = $this->getAttributeFormInputs();
        if (isset($attributeFormInputs[$name])) {
            return $attributeFormInputs[$name];
        }

        return $this->generateActiveField($name);
    }

    public function generateActiveField($attribute) {
        if($this->hasMethod('hasValidator')) {
            foreach(static::$builtInFormFieldsValidatorMapping as $validator => $field) {
                if($this->hasValidator($validator, $attribute)) {
                    if(is_string($field) && isset(static::$builtInFormFields[$field])) {
                        return static::$builtInFormFields[$field];
                    }
                    return $field;
                }
            }
        }
        $column = $this->getAttributeColumn($attribute);
        if ($column && isset($this->formInputs[$this->generateColumnFormat($column)])) {
            return $this->formInputs[$this->generateColumnFormat($column)];
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
        } elseif($this->hasMethod('getRelation')) {
            $relation = $this->getRelation($attribute, false);
            if ($relation instanceof \yii\db\ActiveQueryInterface) {
                $input = [
                    'type' => Form::INPUT_WIDGET,
                    'widgetClass' => \verbi\yii2Helpers\widgets\Select::classname(),
                    'options' => [
                        'options' => [
                            'multiple' => true,
                        ],
                        'pluginOptions' => [
                            'ajax' => [
                                'url' => \yii\helpers\Url::to([
                                    'suggestions',
                                    'relation' => $attribute,
                                ]),
                                'dataType' => 'json',
                                'data' => new JsExpression('function(params) { return {"q":params.term }}'),
                                'processResults' => new JsExpression('function (data, params) {'
                                    . 'var items = new Array();'
                                    . 'for (var i = 0; data.length > i; i++) {'
                                        . 'items.push( {id:data[i].id,text:data[i].text});'
                                    . '}'
                                    . 'return {'
                                        . 'results: items'
                                    . '};'
                                . '}'),
                            ],
                            'tags' => false,
                            'maximumInputLength' => 20,
                            'maximumSelectionLength' => 10,
                        ],
                    ],
                ];
                return $input;
            }
        }
        return $this->formInputs[$this->generateColumnFormat($column)];
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
        $tableSchema = false;
        if($this->hasMethod('getTableSchema')){
            $tableSchema = $this->getTableSchema();
        }
        if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
            return null;
        }
        return $tableSchema->columns[$attribute];
    }

    public function getAttributeFormat($attribute) {
        $column = $this->getAttributeColumn($attribute);
        //if(!$column)
        if (true or \Yii::$app->formatter instanceof verbi\yii2Helpers\i18n\Formatter) {
            foreach ($this->getActiveValidators($attribute) as $validator) {
                if ($validator instanceof \verbi\yii2ExtendedActiveRecord\validators\PhoneValidator) {
                    if (!\Yii::$app->formatter->hasMethod('asPhone')) {
                        \Yii::$app->formatter->attachBehavior('phoneFormatter', '\verbi\yii2Helpers\behaviors\formatter\PhoneFormatterBehavior');
                    }
                    return 'phone';
                }
            }
        }
        return $this->generateColumnFormat($column);
    }
}
