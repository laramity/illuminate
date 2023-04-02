<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 02.04.2023 21:06
 */

namespace Yii2tech\Illuminate\Http;

use Illuminate\Http\Response;

class EmptyResponse extends Response
{
    public function sendContent()
    {
        return $this;
    }
}
