<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 06.08.2023 22:55
 */

namespace Yii2tech\Illuminate\Yii\Web;

use BadMethodCallException;
use yii\base\UnknownPropertyException;
use yii\web\IdentityInterface;

class ArrayIdentity implements IdentityInterface
{
    public function __construct(protected array $attributes = [])
    {
    }

    public static function findIdentity($id)
    {
        throw new BadMethodCallException("This identity doesn't support `findIdentity`");
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new BadMethodCallException("This identity doesn't support `findIdentityByAccessToken`");
    }

    public function getId()
    {
        if (isset($this->attributes['yii_id'])) {
            return $this->attributes['yii_id'];
        }

        return $this->attributes['id'];
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }

    public function __get(string $name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if (isset($this->attributes['extra']['legacyData'][$name])) {
            return $this->attributes['extra']['legacyData'][$name];
        }

        throw new UnknownPropertyException("Property `$name` doesn't exist in ArrayIdentity");
    }
}
