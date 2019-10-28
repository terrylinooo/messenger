<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Messenger\Mailer;

use Messenger\Mailer\MailerInterface;
use RuntimeException;

/**
 * Abstract Mailer
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
abstract class AbstractMailer implements MailerInterface
{
    /**
     * Sender.
     *
     * @var array
     */
    protected $sender = [];

    /**
     * Recipients.
     *
     * @var array
     */
    protected $recipients = [];

    /**
     * The subject of the email.
     *
     * @var string
     */
    protected $subject = 'Please use setSubject() to define the email subject!';

    /**
     * @inheritDoc
     */
    public function setSender(array $sender): void
    {
        if (empty($sender['email']) || ! filter_var($sender['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Sender’s email is invalid.');
        }

        $this->sender['email'] = $sender['email'];
        $this->sender['name']  = $this->getPrettyName($sender['email']);
        $this->sender['type']  = $sender['type'] ?? 'to';

        $this->sender = $sender;
    }

    /**
     * @inheritDoc
     */
    public function addSender(string $sender): void
    {
        if (! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Sender’s email is invalid.');
        }

        $this->sender['email'] = $sender;
        $this->sender['name']  = $this->getPrettyName($sender);
        $this->sender['type']  = $sender['type'] ?? 'to';
    }

    /**
     * @inheritDoc
     */
    public function setRecipients(array $recipients): void
    {
        foreach ($recipients as $i => $recipient) {
            if (empty($recipient['email']) || ! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Recipient’s email is invalid. (#' . $i . ')');
            }

            $this->recipients[$i]['email'] = $recipient['email'];
            $this->recipients[$i]['name']  = $this->getPrettyName($recipient['email']);
            $this->recipients[$i]['type']  = $recipient['type'] ?? 'to';
        }

        $this->recipients = $recipients;
    }

    /**
     * @inheritDoc
     */
    public function addRecipient(string $recipient): void
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Recipient’s email is invalid.');
        }

        array_push($this->recipients, [
            'email' => $recipient,
            'name'  => $this->getPrettyName($recipient),
            'type'  => $recipient['email'] ?? 'to',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * Check the content weather it is HTML or TEXT formatted.
     *
     * @param string $text The email content.
     *
     * @return string
     */
    protected function getContentType(string $text): string
    {
        return (substr(trim($text), 0, 1) === '<') 
            ? 'text/html'
            : 'text/plain';
    }

    /**
     * Get a better name from the email address, if you don't know the real name.
     *
     * @param string $email
     *
     * @return string
     */
    protected function getPrettyName($email): string
    {
        $name = explode('@', $email)[0];
        $name = preg_replace('/[0-9]+/', '', $name);
        $name = str_replace('.', ' ', $name);
        $name = ucwords($name);

        return $name;
    }
}