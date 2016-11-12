<?php

namespace verbi\yii2ExtendedActiveRecord\validators;
use Yii;

/*
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/Yii2-Helpers/
 * @license https://opensource.org/licenses/GPL-3.0
 */

class CountValidator extends Validator {

    /**
     * @var int The minimum number of elements in the collection
     */
    public $min = 0;
    
    /**
     * @var int The maximum number of elements in the collection
     */
    public $max;

    /**
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} should have at least {min} and at most {max} elements.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value) {
        if (sizeof($value) < $this->min || sizeof($value) > $this->max) {
            return [$this->message, [
                    'min' => $this->min,
                    'max' => $this->max,
            ]];
        }
        return null;
    }

}
