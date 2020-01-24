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
 * @since 1.3.0
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
        $this->type = $this->getContentType($message);

        if ($this->type !== 'text/html') {
            $message = wordwrap($message, 70);
        }

        $header = $this->getHeader();

        // Build the recipients' string, the formatting must comply with » RFC 2822
        // For example:
        // User <user@example.com>, Another User <anotheruser@example.com>
        $recipientList = '';

        foreach($this->recipients as $recipient) {
            $recipientList .= $recipient['name'] . ' <' . $recipient['email'] . '>, ';
        }

        $recipientList = rtrim($recipientList, ', ');

        @mail($recipientList, $this->subject, $message, $header);
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return '';
    }
}