<?php
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Shieldon\Messenger;

use Shieldon\Messenger\Mailer\AbstractMailer;
use Shieldon\Messenger\Messenger\MessengerInterface;
use Shieldon\Messenger\Messenger\MessengerTrait;

use function mail;
use function wordwrap;
use function rtrim;

/**
 * Send message by PHP's bulit-in mail() function.
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.3.0
 */
class Mail extends AbstractMailer implements MessengerInterface
{
    use MessengerTrait;

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
    public function send(string $message): bool
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

        $this->success = @mail($recipientList, $this->subject, $message, $header);

        // If there is no error, we assume the email that it is sent.
        if ($this->success) {
            $message = 'Email is sent.';
        }

        $this->resultData = [
            'success' => $this->success,
            'message' => $message,
            'result'  => '',
        ];

        return $this->success;
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return '';
    }
}