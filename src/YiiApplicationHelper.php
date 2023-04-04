<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 03.04.2023 17:06
 */

namespace Yii2tech\Illuminate;

use Illuminate\Contracts\Foundation\Application as IlluminateApplication;
use Illuminatech\ArrayFactory\FactoryContract;
use Yii;

class YiiApplicationHelper
{
    /**
     * @var string|null path to bootstrap file, which should be included before or after defining constants and including `Yii.php`.
     */
    public ?string $bootstrap;

    /**
     * @var array|null array configuration for Yii DI container to be applied during Yii bootstrap.
     * If not set - container will not be explicitly setup.
     */
    public ?array $container;

    /**
     * @var array|null array configuration for Yii logger to be applied during Yii bootstrap.
     * If not set - logger will not be explicitly setup.
     */
    public ?array $logger;

    /**
     * @var bool include bootstrap file after defining constants and including 'Yii.php'
     */
    public bool $bootstrapAfterLoadYii = false;

    public bool $debug = false;

    /**
     * @var IlluminateApplication Laravel application instance.
     */
    protected IlluminateApplication $app;

    /**
     * Constructor.
     *
     * @param IlluminateApplication $app Laravel application instance.
     */
    public function __construct(IlluminateApplication $app)
    {
        $this->app = $app;

        $config = $this->app->get('config')->get('yii.yii', []);
        if (isset($config['middleware'])) {
            unset($config['middleware']);
        }

        $this->getFactory()->configure($this, $config);
    }

    /**
     * Makes preparations for Yii application run.
     */
    public function bootstrapYii()
    {
        if ($this->bootstrap && !$this->bootstrapAfterLoadYii) {
            require $this->app->make('path.base') . DIRECTORY_SEPARATOR . $this->bootstrap;
        }

        defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

        defined('YII_DEBUG') or define('YII_DEBUG', $this->debug);

        if (! defined('YII_ENV')) {
            $environment = $this->app->get('config')->get('app.env', 'production');
            switch ($environment) {
                case 'production':
                    $environment = 'prod';
                    break;
                case 'local':
                case 'development':
                    $environment = 'dev';
                    break;
                case 'testing':
                    $environment = 'test';
                    break;
            }

            define('YII_ENV', $environment);
        }

        if (!class_exists('Yii')) {
            require $this->app->make('path.base') . '/vendor/yiisoft/yii2/Yii.php';
        }

        if ($this->bootstrap && $this->bootstrapAfterLoadYii) {
            require $this->app->make('path.base') . DIRECTORY_SEPARATOR . $this->bootstrap;
        }

        if ($this->container) {
            Yii::$container = $this->getFactory()->make($this->container);
        }

        if ($this->logger) {
            Yii::setLogger($this->getFactory()->make($this->logger));
        }
    }

    /**
     * Preforms clean up after running Yii application.
     */
    public function terminateYii()
    {
        Yii::$classMap = [];
        Yii::$aliases = [];

        Yii::setLogger(null);
        Yii::$app = null;
        Yii::$container = null;
    }

    /**
     * Returns related array factory for components creation and configuration.
     *
     * @return FactoryContract array factory instance.
     */
    public function getFactory(): FactoryContract
    {
        return $this->app->make(FactoryContract::class);
    }
}
