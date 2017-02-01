<?php
namespace verbi\yii2ExtendedActiveRecord\traits;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\base\ArrayObject;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use verbi\yii2ExtendedActiveRecord\base\ModelEvent;
use Yii;

trait ActiveRecordTrait {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    
    public static $EVENT_BEFORE_SETATTRIBUTES = 'beforeSetAttributes';
    public static $EVENT_AFTER_SETATTRIBUTES = 'afterSetAttributes';
    public static $EVENT_BEFORE_RULES = 'beforeRules';
    public static $EVENT_AFTER_RULES = 'afterRules';
    public static $EVENT_BEFORE_CREATE_VALIDATORS = 'beforeCreateValidators';
    public static $EVENT_AFTER_CREATE_VALIDATORS = 'afterCreateValidators';
    
    /**
     * @var array attribute values indexed by attribute names
     */
    protected $_attributes = [];
    
    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    protected $_oldAttributes;

    /**
     * @inheritdoc
     */
    public function rules() {
        if (!$this->beforeRules()) {
            return [];
        }
        $rules = parent::rules();
        $this->afterRules($rules);
        return $rules;
    }

    protected function beforeRules() {
        $event = new ModelEvent;
        $this->trigger(self::$EVENT_BEFORE_RULES, $event);
        return $event->isValid;
    }

    protected function afterRules(&$rules) {
        $event = new ModelEvent;
        $event->data = ['rules' => &$rules,];
        $this->trigger(self::$EVENT_AFTER_RULES, $event);
        return $event->isValid;
    }
    
    public function getAccessRule($identity = null) {
        return array();
    }

    public function checkAccess($identity) {
        if ($this->find()->Where($this->getAccessRule($identity))->andWhere($this->getPrimaryKey(true))->one()) {
            return true;
        }
        return false;
    }
    
    
    
    
    
    
    
    
    
    
    /**
     * @inheritdoc
     */
    public function setAttributes($attributes, $safeOnly = true) {
        if (!$this->beforeSetAttributes($attributes, $safeOnly)) {
            return false;
        }
        $result = parent::setAttributes($attributes, $safeOnly);
        $this->afterSetAttributes($attributes, $safeOnly);
        return $result;
    }

    protected function beforeSetAttributes(&$attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => &$attributes, 'safeOnly' => $safeOnly,];
        $this->trigger(self::$EVENT_BEFORE_SETATTRIBUTES, $event);
        return $event->isValid;
    }

    protected function afterSetAttributes(&$attributes, $safeOnly = true) {
        $event = new ModelEvent;
        $event->data = ['attributes' => &$attributes, 'safeOnly' => $safeOnly,];
        $this->trigger(self::$EVENT_AFTER_SETATTRIBUTES, $event);
        return $event->isValid;
    }
    
    /**
     * @inheritdoc
     */
    public function isAttributeSafe($attribute) {
        if ($this->owner->isPrimaryKey([$attribute]))
            return false;
        return parent::isAttributeSafe($attribute);
    }

    public function __toString() {
        foreach ($this->attributes() as $name => $value) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $this->$name;
            }
        }
        /* @var $class \yii\db\ActiveRecord */
        $pk = $this->primaryKey();
        $str = '';
        foreach ($pk as $value) {
            $str .= ' ' . $this->$value;
        }
        return trim($str);
    }

    public function label() {
        return Inflector::camel2words(StringHelper::basename($this->className()));
    }

    protected function getCurrentUser($user = null) {
        if (!$user && !\Yii::$app->getUser()->isGuest) {
            $user = \Yii::$app->getUser()->getIdentity();
        }
        return $user;
    }
    
    public function createValidators()
    {
        $validators = new ArrayObject;
        if (!$this->beforeCreateValidators()) {
            return [];
        }
        $validators->exchangeArray((array) parent::createValidators());
        return $this->afterCreateValidators($validators);
    }
    
    protected function beforeCreateValidators() {
        $event = new GeneralFunctionEvent;
        $this->trigger(self::$EVENT_BEFORE_CREATE_VALIDATORS, $event);
        return $event->isValid;
    }

    protected function afterCreateValidators(&$validators) {
        $event = new GeneralFunctionEvent;
        $event->params = ['validators' => &$validators,];
        $this->trigger(self::$EVENT_AFTER_CREATE_VALIDATORS, $event);
        return $event->hasReturnValue()?$event->getReturnValue():$validators;
    }
    
    public function hasValidator(String $name, String $attribute) {
        if(isset(Validator::$builtInValidators[$name])) {
            $builtInValidator = Validator::$builtInValidators[$name];
            if(is_string($builtInValidator)){
                $name = $builtInValidator;
            }
            elseif(is_array($builtInValidator) && isset($builtInValidator['type'])) {
                $name = $builtInValidator['class'];
            }
        }
        foreach($this->getActiveValidators($attribute) as $validator) {
            if($validator instanceof $name) {
                return true;
            }
        }
        return false;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
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