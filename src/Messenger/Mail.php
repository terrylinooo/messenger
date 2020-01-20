<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Messenger;

use Messenger\Mailer\AbstractMailer;
use Messenger\MessengerInterface;

use function mail;

/**
 * Send message by PHP's bulit-in mail() function.
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.1.0
 */
class Mail extends AbstractMailer implements MessengerInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Nonthing to do.
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): void
    {
        $contentType = $this->getContentType($message);

        if ($contentType !== 'text/html') {
            $message = wordwrap($message, 60);
        }

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: ' . $contentType . '; charset=UTF-8' . "\r\n";

        if (! empty($this->sender)) {
            $headers = 'From: ' . $this->sender['name'] . ' <' .  $this->sender['email'] . '>' . "\r\n";
        }
  
        $subject = $this->subject;

        foreach($this->recipients as $recipient) {
            mail($recipient, $subject, $message, $headers);
        }
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return '';
    }
}