<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Yii2tech\Illuminate\Yii\Web;

use Illuminate\Auth\AuthManager as IlluminateAuthManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use yii\db\BaseActiveRecord;
use yii\web\IdentityInterface as YiiIdentityInterface;

/**
 * User allows usage of the Laravel guard for authenticated user tracking.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'user' => Yii2tech\Illuminate\Yii\Web\User::class,
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * @see IlluminateAuthManager
 *
 * @property IlluminateAuthManager $illuminateAuthManager related Laravel auth manager.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class User extends \yii\web\User
{
    /**
     * @var string|null guard to be used while retrieving identity from Laravel auth manager.
     */
    public ?string $guard;

    /**
     * @var YiiIdentityInterface|bool user identity.
     */
    private YiiIdentityInterface|bool|null $_identity = false;

    /**
     * @var IlluminateAuthManager|null related Laravel auth manager.
     */
    private ?IlluminateAuthManager $_illuminateAuthManager = null;

    /**
     * {@inheritdoc}
     * @throws BindingResolutionException
     */
    public function getIdentity($autoRenew = true): ?YiiIdentityInterface
    {
        if ($this->_identity === false) {
            $identity = $this->getIlluminateAuthManager()->guard($this->guard)->user();
            if ($identity !== null) {
                $identity = $this->convertIlluminateIdentity($identity);
            }

            $this->_identity = $identity;
        }

        return $this->_identity;
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentity($identity): void
    {
        parent::setIdentity($identity);

        $this->_identity = $identity;
    }

    /**
     * @throws BindingResolutionException
     */
    public function getIlluminateAuthManager(): IlluminateAuthManager
    {
        if ($this->_illuminateAuthManager === null) {
            $this->_illuminateAuthManager = $this->defaultIlluminateAuthManager();
        }

        return $this->_illuminateAuthManager;
    }

    public function setIlluminateAuthManager(IlluminateAuthManager $authManager): self
    {
        $this->_illuminateAuthManager = $authManager;

        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    protected function defaultIlluminateAuthManager(): IlluminateAuthManager
    {
        return Container::getInstance()->make('auth');
    }

    /**
     * {@inheritdoc}
     * @throws BindingResolutionException
     */
    public function switchIdentity($identity, $duration = 0): void
    {
        $this->setIdentity($identity);

        if ($identity === null) {
            $this->getIlluminateAuthManager()->guard($this->guard)->logout();

            return;
        }

        if ($identity instanceof BaseActiveRecord) {
            $id = $identity->getPrimaryKey();
        } else {
            $id = $identity->getId();
        }

        $this->getIlluminateAuthManager()->guard($this->guard)->loginUsingId($id);
    }

    /**
     * Converts Laravel identity into Yii one.
     *
     * @param  Model|Authenticatable|array|mixed  $identity Laravel identity.
     * @return YiiIdentityInterface Yii compatible identity instance.
     */
    protected function convertIlluminateIdentity(mixed $identity): YiiIdentityInterface
    {
        if ($identity instanceof Model) {
            $id = $identity->getKey();
            $attributes = $identity->getAttributes();
        } elseif ($identity instanceof Authenticatable) {
            $id = $identity->getAuthIdentifier();
            $attributes = [];
        } elseif (is_array($identity) && isset($identity['id'])) {
            $id = $identity['id'];
            $attributes = $identity;
        } else {
            throw new RuntimeException('Unable to convert identity from "' . print_r($identity, true) . '"');
        }

        if (isset($attributes['yii_id'])) {
            $id = $attributes['yii_id'];
            unset($attributes);
        }

        $identityClass = $this->identityClass;
        if (!empty($attributes) && is_subclass_of($identityClass, BaseActiveRecord::class)) {
            /** @var YiiIdentityInterface $record */
            $record = new $identityClass();
            call_user_func([$identityClass, 'populateRecord'], $record, $attributes);

            return $record;
        }

        return call_user_func([$identityClass, 'findIdentity'], $id);
    }
}
