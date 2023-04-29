<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 29.04.2023 18:27
 */

namespace Yii2tech\Illuminate\Mail;

use Illuminate\Mail\Mailable;
use Yii2tech\Illuminate\Yii\Mail\Message as YiiMessage;

class Message extends Mailable
{
    private YiiMessage $yiiMessage;

    public function __construct(YiiMessage $yiiMessage)
    {
        $this->yiiMessage = $yiiMessage;
    }

    public function build(): self
    {
        $this->setAddressFromYii($this->yiiMessage->getFrom(true), 'from');
        $this->setAddressFromYii($this->yiiMessage->getTo(), 'to');
        $this->setAddressFromYii($this->yiiMessage->getCc(), 'cc');
        $this->setAddressFromYii($this->yiiMessage->getBcc(), 'bcc');
        $this->setAddressFromYii($this->yiiMessage->getReplyTo(true), 'replyTo');

        if (!empty($html = $this->yiiMessage->getHtmlBody())) {
            $this->html($html);
        }

        if (!empty($text = $this->yiiMessage->getTextBody())) {
            $this->viewData['plainText'] = $text;
            $this->text('illuminate::mail-plain-text');
        }

        if (!empty($subject = $this->yiiMessage->getSubject())) {
            $this->subject($subject);
        }

        if (count($attachments = $this->yiiMessage->getAttachments()) > 0) {
            foreach ($attachments as $attachment) {
                $options = [];
                if (isset($attachment['type']) && !empty($attachment['type'])) {
                    $options['mime'] = $attachment['type'];
                }

                $this->attachData(base64_decode($attachment['data']), $attachment['name'], $options);
            }
        }

        dump($this);

        return $this;
    }

    private function setAddressFromYii($yiiAddress, $property): void
    {
        if (empty($yiiAddress)) {
            return;
        }

        if (is_array($yiiAddress)) {
            foreach ($yiiAddress as $email => $name) {
                if (is_int($email)) {
                    $this->setAddress($name, null, $property);
                } else {
                    $this->setAddress($email, $name, $property);
                }
            }

            return;
        }

        $this->setAddress($yiiAddress, $property);
    }
}
