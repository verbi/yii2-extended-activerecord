<?php
namespace verbi\yii2ExtendedActiveRecord\validators;
use yii\helpers\HtmlPurifier;
/*
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/Yii2-Helpers/
 * @license https://opensource.org/licenses/GPL-3.0
*/
class RichTextValidator extends Validator
{
    /**
     * @var Array the allowed tags 
     */
    public $allowedTags;
    
    /**
     * @var bool Whether to purify the input.
     */
    public $purify = true;
    
    /**
     * This will validate and clean up(purify) HTML
     */
    public function validateAttribute($model, $attribute)
    {
        if($this->allowedTags) {
            $model->$attribute = strip_tags($model->$attribute,$this->allowedTags);
        }
        
        if($this->purify) {
            $model->$attribute = HtmlPurifier::process($model->$attribute);
        }
    }
}