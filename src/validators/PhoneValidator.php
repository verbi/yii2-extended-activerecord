<?php

namespace verbi\yii2ExtendedActiveRecord\validators;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use yii\validators\Validator;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

/**
 * Validates the given attribute value with the PhoneNumberUtil library.
 * 
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class PhoneValidator extends Validator {

    /**
     * @var mixed
     */
    public $region;

    /**
     * @inheritdoc
     */
    public function init() {
        if (!$this->message) {
            $this->message = \Yii::t('yii', '{attribute} is not a valid Phone number.');
        }
        parent::init();
    }

    /**
     * Validates the phone number. If the phone number has a valid region
     * or array of regions, it will validate against those regions. Otherwise,
     * it just checks the entire number.
     * 
     * @param mixed $value
     * @return array|null
     */
    protected function validateValue($value) {
        try {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneNumberUtil->parse($value, null);
            if (isset($this->region)) {
                if (!is_array($this->region)) {
                    $this->region = [$this->region];
                }
                foreach ($this->region as $region) {
                    if ($phoneNumberUtil->isValidNumberForRegion($phoneNumber, $region)) {
                        return;
                    }
                }
            } else {
                if ($phoneNumberUtil->isValidNumber($phoneNumber)) {
                    return;
                }
            }
        } catch (NumberParseException $e) {
            
        }
        return [$this->message, []];
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view) {
        $options = Json::htmlEncode([
                    'message' => \Yii::$app->getI18n()->format(
                            \Yii::t('app', $this->message), [
                        'attribute' => $model->getAttributeLabel($attribute)
                            ], \Yii::$app->language)
        ]);
        return new JsExpression('var options = '
                . $options
                . ', telInput = $("#'
                . Html::getInputId($model, $attribute)
                . '");'
                . 'if($.trim(telInput.val())){'
                . 'if(!telInput.intlTelInput("isValidNumber")){'
                . 'messages.push(options.message);'
                . '}'
                . '}');
    }

}
