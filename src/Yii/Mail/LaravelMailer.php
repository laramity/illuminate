<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 29.04.2023 17:52
 */

namespace Yii2tech\Illuminate\Yii\Mail;

use Illuminate\Support\Facades\Mail;
use yii\mail\BaseMailer;
use Yii2tech\Illuminate\Mail\Message as LaravelMessage;

class LaravelMailer extends BaseMailer
{
    /** @var class-string */
    public $messageClass = Message::class;

    public function compose($view = null, array $params = []): Message
    {
        /** @var Message $message */
        $message = parent::compose($view, $params);

        return $message;
    }

    /**
     * @param Message $message
     */
    protected function sendMessage($message): bool
    {
        Mail::send(new LaravelMessage($message));

        $failures = Mail::failures();
        if (count($failures) > 0) {
            logger()->error('Error on send mail.', ['failures' => $failures, 'message' => $message->toString()]);

            return false;
        }

        return true;
    }
}
