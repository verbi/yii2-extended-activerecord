<?php

namespace nodge\eauth;

use Yii;
use yii\base\BootstrapInterface;

/**
 * This is the bootstrap class for the yii2-extended-activerecord extension.
 * 
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class Bootstrap implements BootstrapInterface {

    /**
     * @inheritdoc
     */
    public function bootstrap($app) {
        Yii::setAlias('@yii2ExtendedActiveRecord', __DIR__);
    }

}
