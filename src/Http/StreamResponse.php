<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 03.05.2023 12:27
 */

namespace Yii2tech\Illuminate\Http;

use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamResponse extends StreamedResponse
{
    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct($callback, $status, $headers);
    }
}
