<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 29.04.2023 17:55
 */

namespace Yii2tech\Illuminate\Yii\Mail;

use Exception;
use InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\mail\BaseMessage;

class Message extends BaseMessage
{
    /**
     * Either a string with email address OR an array with 'name' and 'email' keys.
     * @var string|array
     */
    private $_from = null;

    /**
     * Either a stored array of recipients:
     * [
     *  'email' => 'name', 'email',
     * ]
     * @var array
     */
    private array $_to = [];

    /**
     * List of CC recipients
     * @var array
     */
    private array $_cc = [];

    /**
     * List of BCC recipients
     * @var array
     */
    private array $_bcc = [];

    /**
     * @var string|array Email address
     */
    private $_replyTo = [];

    private ?string $_subject = null;

    /**
     * Should be set if html or rfc822 are not set
     */
    private ?string $_text = null;

    /**
     * Should be set if text or rfc822 are not set
     */
    public ?string $_html = null;

    /**
     * Should be set if text or html are not set
     */
    private ?string $_rfc822 = null;

    /**
     * Attachments array:
     * [
     *  'type' => string, // MIME type
     *  'name' => string, // 255 bytes
     *  'data' => string, // base64 encoded
     * ]
     * @var array
     */
    private array $_attachments = [];

    /**
     * Inline (embed) images array:
     * [
     *  'type' => string, // MIME type
     *  'name' => string, // 255 bytes
     *  'data' => string, // base64 encoded
     * ]
     * @var array
     */
    private array $_images = [];

    /**
     * Returns the character set of this message.
     * @return string the character set of this message.
     */
    public function getCharset(): ?string
    {
        return null;
    }

    /**
     * @param string $charset character set name.
     * @return $this self reference.
     * @throws NotSupportedException
     */
    public function setCharset($charset): self
    {
        throw new NotSupportedException('Charset is not supported.');
    }

    /**
     * Returns the message sender.
     * @return string|array the sender
     */
    public function getFrom(bool $raw = false)
    {
        if (is_array($this->_from) && !$raw) {
            return $this->emailsToString($this->_from);
        }

        return $this->_from;
    }

    /**
     * Sets the message sender.
     * @param string|array $from sender email address.
     * You may pass an array of addresses if this message is from multiple people.
     * You may also specify sender name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setFrom($from): self
    {
        $this->_from = $from;

        return $this;
    }

    /**
     * Returns the message recipient(s).
     * @return array the message recipients or the recipients list id
     */
    public function getTo(): array
    {
        return $this->_to;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $to receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     * @throws Exception
     */
    public function setTo($to): self
    {
        if (is_string($to)) {
            $to = $to ? [$to] : [];
        }

        $this->_to = $this->extractUserData($to);

        return $this;
    }

    /**
     * Returns the reply-to address of this message.
     * @return string|array the reply-to address of this message.
     */
    public function getReplyTo(bool $raw = false)
    {
        if (is_array($this->_replyTo) && !$raw) {
            return $this->emailsToString($this->_replyTo);
        }

        return $this->_replyTo;
    }

    /**
     * Sets the reply-to address of this message.
     * @param string|array $replyTo the reply-to address.
     * You may pass an array of addresses if this message should be replied to multiple people.
     * You may also specify reply-to name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     */
    public function setReplyTo($replyTo): self
    {
        $this->_replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc(): array
    {
        return $this->_cc;
    }

    /**
     * Sets the Cc (additional copy receiver) addresses of this message.
     *
     * @param string|array $cc copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     * @throws Exception
     */
    public function setCc($cc): self
    {
        if (is_string($cc)) {
            $cc = $cc ? [$cc] : [];
        }

        $this->_cc = $this->extractUserData($cc);

        return $this;
    }

    /**
     * Returns the Bcc (hidden copy receiver) addresses of this message.
     * @return array the Bcc (hidden copy receiver) addresses of this message.
     */
    public function getBcc(): array
    {
        return $this->_bcc;
    }

    /**
     * Sets the Bcc (hidden copy receiver) addresses of this message.
     *
     * Both CC and BCC recipients require set 'header_to' field, it should be the email of the main recipient.
     *
     * @param string|array $bcc hidden copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return $this self reference.
     * @throws Exception
     */
    public function setBcc($bcc): self
    {
        if (is_string($bcc)) {
            $bcc = $bcc ? [$bcc] : [];
        }

        $this->_bcc = $this->extractUserData($bcc);

        return $this;
    }

    /**
     * Returns the message subject.
     * @return string the message subject
     */
    public function getSubject(): ?string
    {
        return $this->_subject;
    }

    /**
     * Sets the message subject.
     * @param string $subject message subject
     * @return $this self reference.
     */
    public function setSubject($subject): self
    {
        $this->_subject = $subject;

        return $this;
    }

    /**
     * Sets message plain text content.
     * @param string $text message plain text content.
     * @return $this self reference.
     */
    public function setTextBody($text): self
    {
        $this->_text = $text;

        return $this;
    }

    public function getTextBody(): string
    {
        return $this->_text;
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return $this self reference.
     */
    public function setHtmlBody($html): self
    {
        $this->_html = $html;

        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->_html;
    }

    /**
     * @return string
     */
    public function getRfc822(): string
    {
        return $this->_rfc822;
    }

    /**
     * @param string $rfc822
     * @return $this
     */
    public function setRfc822(string $rfc822): self
    {
        $this->_rfc822 = $rfc822;

        return $this;
    }

    /**
     * Attaches existing file to the email message.
     * @param string $fileName full file name
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     * @throws Exception
     */
    public function attach($fileName, array $options = []): self
    {
        if (!$fileName) {
            return $this;
        }

        $this->attachContent(file_get_contents($fileName), [
            'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
            'contentType' => ArrayHelper::getValue($options, 'contentType', FileHelper::getMimeType($fileName)),
        ]);

        return $this;
    }

    /**
     * Attach specified content as file for the email message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     * @throws Exception
     */
    public function attachContent($content, array $options = []): self
    {
        if (!$content) {
            return $this;
        }

        $this->_attachments[] = [
            'type' => ArrayHelper::getValue($options, 'contentType', $this->getBinaryMimeType($content)),
            'name' => ArrayHelper::getValue($options, 'fileName', ('file_' . count($this->_attachments))),
            'data' => base64_encode($content),
        ];

        return $this;
    }

    /**
     * Attach a file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $fileName file name.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file and will be used as a CID.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function embed($fileName, array $options = []): string
    {
        if (!$fileName) {
            return $this;
        }

        $mimeType = FileHelper::getMimeType($fileName);
        if (strpos($mimeType, 'image') === false) {
            throw new InvalidArgumentException("Only images can be embed. Given file $fileName is " . $mimeType);
        }

        return $this->embedContent(file_get_contents($fileName), [
            'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
            'contentType' => ArrayHelper::getValue($options, 'contentType', $mimeType),
        ]);
    }

    /**
     * Attach a content as file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file and will be used as a CID.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     * @throws Exception
     */
    public function embedContent($content, array $options = []): string
    {
        if (!$content) {
            return $this;
        }

        $mimeType = $this->getBinaryMimeType($content);
        if (strpos($mimeType, 'image') === false) {
            throw new InvalidArgumentException("Only images can be embed. Given content is " . $mimeType);
        }

        $cid = 'image_' . count($this->_images);

        $this->_images[] = [
            'type' => ArrayHelper::getValue($options, 'contentType', $mimeType),
            'name' => ArrayHelper::getValue($options, 'fileName', $cid),
            'data' => base64_encode($content),
        ];

        return $cid;
    }

    /**
     * Returns string representation of this message.
     * @return string the string representation of this message.
     */
    public function toString(): string
    {
        return $this->getSubject() . ' - Recipients:'
            . ' [TO] ' . implode('; ', $this->getTo())
            . ' [CC] ' . implode('; ', $this->getCc())
            . ' [BCC] ' . implode('; ', $this->getBcc());
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->_attachments;
    }

    /**
     * @return array
     */
    public function getImages(): array
    {
        return $this->_images;
    }

    /**
     * Converts emails array to the string: ['name' => 'email'] -> '"name" <email>'
     * @param array $emails
     * @return string
     */
    private function emailsToString(array $emails): string
    {
        $addresses = [];
        foreach ($emails as $email => $name) {
            $name = trim($name);

            if (is_int($email)) {
                $addresses[] = $name;
            } else {
                $email = trim($email);
                $addresses[] = "\"$name\" <$email>";
            }
        }

        return implode(',', $addresses);
    }

    /**
     * Returns the MIME type of the given binary data
     * @param $content
     * @return string the binary MIME type
     */
    private function getBinaryMimeType($content): string
    {
        $finfo = new \finfo(FILEINFO_MIME);

        return $finfo->buffer($content);
    }

    /**
     * Extracts user-specific data from given typical for setTo, setCc, setBcc methods array
     * Repeated in 'To', 'Cc', 'Bcc' addresses data will be overwritten in order of setters calls.
     * @param array $addresses
     * @return array list of addresses in canonical form
     * @throws Exception
     */
    private function extractUserData(array $addresses): array
    {
        $cleanAddresses = [];

        foreach ($addresses as $email => $name) {
            if (is_int($email)) {
                $cleanAddresses[] = $name;
            } elseif (is_array($name)) {
                $name = ArrayHelper::getValue($name, 'name');
                if ($name) {
                    $cleanAddresses[$email] = $name;
                } else {
                    $cleanAddresses[] = $email;
                }
            } else {
                $cleanAddresses[$email] = $name;
            }
        }

        return $cleanAddresses;
    }
}
