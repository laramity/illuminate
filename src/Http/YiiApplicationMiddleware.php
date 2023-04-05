<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Yii2tech\Illuminate\Http;

use Closure;
use Illuminate\Contracts\Foundation\Application as IlluminateApplication;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Yii;
use yii\base\ExitException as YiiExitException;
use yii\web\HttpException as YiiHttpException;
use Yii2tech\Illuminate\YiiApplicationHelper;

/**
 * YiiApplicationMiddleware is a middleware, which processing Yii web application.
 *
 * Kernel configuration example:
 *
 * ```php
 * namespace App\Http;
 *
 * use Illuminate\Foundation\Http\Kernel as HttpKernel;
 *
 * class Kernel extends HttpKernel
 * {
 *     protected $middleware = [
 *         \App\Http\Middleware\CheckForMaintenanceMode::class,
 *         \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
 *         \App\Http\Middleware\TrimStrings::class,
 *         \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
 *         // ...
 *         \Yii2tech\Illuminate\Http\YiiApplicationMiddleware::class,
 *     ];
 *     // ...
 * }
 * ```
 *
 * Route configuration example:
 *
 * ```php
 * Route::any('{fallbackPlaceholder}', function () {
 *     abort(404);
 * })
 *     ->middleware(Yii2tech\Illuminate\Http\YiiApplicationMiddleware::class)
 *     ->where('fallbackPlaceholder', '.*')
 *     ->fallback();
 * ```
 *
 * Each middleware instance is automatically configured from the configuration key 'yii.yii' using [array factory](https://github.com/illuminatech/array-factory).
 *
 * @see \Illuminatech\ArrayFactory\FactoryContract
 * @see \Yii2tech\Illuminate\Yii\Web\Response
 * @see DummyResponse
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class YiiApplicationMiddleware
{
    /**
     * @var string|null default path to Yii application entry script relative to the project base path.
     * This value will be used only in case entry script is not specified as a middleware parameter.
     */
    public ?string $defaultEntryScript = 'legacy/web/index.php';

    /**
     * @var bool whether to perform cleanup of Yii application.
     */
    public bool $cleanup = true;

    /**
     * @var IlluminateApplication Laravel application instance.
     */
    protected IlluminateApplication $app;

    protected YiiApplicationHelper $yiiHelper;

    /**
     * Constructor.
     *
     * @param IlluminateApplication $app Laravel application instance.
     */
    public function __construct(IlluminateApplication $app)
    {
        $this->app = $app;

        $this->yiiHelper = new YiiApplicationHelper($this->app);

        $this->yiiHelper->getFactory()->configure($this, $this->app->get('config')->get('yii.yii.middleware', []));
    }

    /**
     * Handle an incoming request, attempting to resolve it via Yii web application.
     *
     * @param  \Illuminate\Http\Request  $request request to be processed.
     * @param  \Closure  $next  next pipeline request handler.
     * @param  string|null  $entryScript  path to Yii application entry script relative to the project base path.
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $entryScript = null)
    {
        $this->yiiHelper->bootstrapYii();

        try {
            return $this->runYii($entryScript);
        } catch (YiiHttpException $e) {
            $this->cleanup();

            if ($e->statusCode == 404) {
                // If Yii indicates page does not exist - pass its resolving to Laravel
                return $next($request);
            }

            throw new HttpException($e->statusCode, $e->getMessage(), $e, [], $e->getCode());
        } catch (YiiExitException $e) {
            // In case Yii requests application termination - request is considered as handled
            return $this->createResponse();
        }
    }

    /**
     * Runs Yii application from the given entry PHP script.
     *
     * @param  string|null  $entryScript path to Yii application entry script relative to the project base path.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse HTTP response instance.
     */
    protected function runYii(?string $entryScript = null)
    {
        if ($entryScript === null) {
            $entryScript = $this->defaultEntryScript;
        }

        $entryScript = $this->app->make('path.base') . DIRECTORY_SEPARATOR . $entryScript;

        require $entryScript;

        return $this->createResponse();
    }

    /**
     * Performs clean up after running Yii application in case {@see $cleanup} is enabled.
     */
    protected function cleanup()
    {
        if ($this->cleanup) {
            $this->yiiHelper->terminateYii();
        }
    }

    /**
     * Creates HTTP response for this middleware.
     * In case Yii application uses response, which allows its conversion into Laravel one, such conversion will be perfromed.
     * Otherwise a dummy response will be generated.
     * This method performs automatic clean up.
     *
     * @see \Yii2tech\Illuminate\Yii\Web\Response
     * @see DummyResponse
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse HTTP response instance.
     */
    protected function createResponse()
    {
        if (headers_sent()) {
            $this->cleanup();

            return new DummyResponse();
        }

        $yiiResponse = Yii::$app ? Yii::$app->get('response') : null;

        $this->cleanup();

        if ($yiiResponse instanceof \Yii2tech\Illuminate\Yii\Web\Response) {
            return $yiiResponse->getIlluminateResponse(true);
        }

        return new DummyResponse();
    }
}
