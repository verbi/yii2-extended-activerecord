<?php
namespace verbi\yii2ExtendedActiveRecord\base;
use verbi\yii2ExtendedActiveRecord\behaviors\ModelFormBehavior;

/**
 * LoginForm is the model behind the login form.
 * 
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class Model extends \yii\base\Model
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(),[
            // get field names
            \verbi\yii2Helpers\behaviors\base\ComponentBehavior::className(),
            ModelFormBehavior::className(),
        ]);
    }
}