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
    public $allowedTags = ['strong', 'b', 'i', 'em', 'a' => ['href'], 'u','ul', 'li', 'p','s'];
    
    /**
     * @var bool Whether to purify the input.
     */
    public $purify = true;
    
    /**
     * This will validate and clean up(purify) HTML
     */
    public function validateAttribute($model, $attribute)
    {
        if(is_array($this->allowedTags)) {
             $tags = [];
                    foreach($this->allowedTags as $key => $value) {
                        $tag = $value;
                        if(is_array($value)) {
                            $tag=$key;
                        }
                        $tags[] = $tag;
                    }
            $model->$attribute = strip_tags($model->$attribute,$tags && sizeof($tags)?'<'.implode('><',$tags).'>':'');
        }
        
        if($this->purify) {
            $allowedTags = $this->allowedTags;
            $model->$attribute = HtmlPurifier::process($model->$attribute, function($config) use ($allowedTags) {
                
                if(is_array($allowedTags) && sizeof($allowedTags)) {
                    $tags = [];
                    foreach($allowedTags as $key => $value) {
                        $tag = $value;
                        if(is_array($value)) {
                            $tag=$key;
                            if(sizeof($value)) {
                                $tag .= '['.implode(',',$value).']';
                            }
                        }
                        $tags[] = $tag;
                    }
                    $config->set('HTML.Allowed', implode(',', $tags));
                }
            });
        }
    }
}