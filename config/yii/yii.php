<?php
/**
 * Configuration for Yii2 to Laravel Migration Package.
 * @see https://github.com/yii2tech/illuminate
 */

return [
    /**
     * Configuration for Yii application middleware.
     *
     * @see \Yii2tech\Illuminate\Http\YiiApplicationMiddleware
     * @see \Illuminatech\ArrayFactory\FactoryContract
     */
    'middleware' => [
        'defaultEntryScript' => 'legacy/web/index.php',
        'cleanup' => true,
    ],

//    'bootstrap' => 'config/bootstrap.php',
//    'bootstrapAfterLoadYii' => false,

//    'container' => [
//        '__class' => Yii2tech\Illuminate\Yii\Di\Container::class,
//    ],
//
    'debug' => (bool) env('YII_COMMON_DEBUG', false),
//
//    'logger' => [
//        '__class' => Yii2tech\Illuminate\Yii\Log\Logger::class,
//    ],
];
