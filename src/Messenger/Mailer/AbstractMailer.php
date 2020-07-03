<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shieldon\Messenger\Mailer;

use Shieldon\Messenger\Mailer\MailerInterface;
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
     * A reply-to email address.
     *
     * @var string
     */
    protected $replyTo = '';

    /**
     * The subject of the email.
     *
     * @var string
     */
    protected $subject = '';

    /**
     * Content type.
     */
    protected $type = 'text/plain';

    /**
     * The charset of the email content.
     * Most mailers set iso-8859-1 as default, but we use UTF-8 instead.
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * Confirm that every step works as expected, if not, set this variable as false.
     * 
     * @var bool
     */
    protected $success = true;

    /**
     * @inheritDoc
     */
    public function addSender(string $sender, string $name = ''): void
    {
        if (! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Sender’s email is invalid.');
        }

        $this->sender['email'] = $sender;
        $this->sender['name']  = empty($name) ? $this->getPrettyName($sender) : $name;
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
            $this->recipients[$i]['type']  = empty($recipient['type']) ? 'to' : $recipient['type'];
        }

        $this->recipients = $recipients;
    }

    /**
     * @inheritDoc
     */
    public function addRecipient(string $email, string $name = '', string $type = ''): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Recipient\'s email is invalid.');
        }

        array_push($this->recipients, [
            'email' => $email,
            'name'  => empty($name) ? $this->getPrettyName($email) : $name,
            'type'  => empty($type) ? 'to' : $type
        ]);
    }

    /**
     * An a primary recipient.
     *
     * @param string $email
     * @param string $name
     *
     * @return void
     */
    public function addTo(string $email, string $name = ''): void
    {
        $this->addRecipient($email, $name, 'to');
    }

    /**
     * An a cc recipient.
     *
     * @param string $email
     * @param string $name
     *
     * @return void
     */
    public function addCc(string $email, string $name = ''): void
    {
        $this->addRecipient($email, $name, 'cc');
    }

    /**
     * An a bcc recipient.
     *
     * @param string $email
     * @param string $name
     *
     * @return void
     */
    public function addBcc(string $email, string $name = ''): void
    {
        $this->addRecipient($email, $name, 'bcc');
    }

    /**
     * An a reply-to email address.
     *
     * @param string $email
     * @param string $name
     *
     * @return void
     */
    public function addReplyTo(string $email, string $name = ''): void
    {
        $this->replyTo = [
            'email' => $email,
            'name'  => $name
        ];
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
        $name = str_replace(['"', "'", '<', '>', '/', '\\'], '', $name);

        return $name;
    }

    protected function getHeader(): string
    {
        // Prepare the recipient data.
        $toRecipients = [];
        $ccRecipients = [];
        $bccRecipients = [];

        if (empty($this->sender['email'])) {
            throw new RuntimeException('The sender\'s email is required');
        }

        if (empty($this->sender['name'])) {
            $this->sender['name'] = $this->getPrettyName($this->sender['email']);
        }

        if (empty($this->replyTo)) {
            $this->replyTo = $this->sender;
        }

        foreach($this->recipients as $i => $recipient) {
            if ($recipient['type'] === 'cc') {
                $ccRecipients[$i]['name'] = $recipient['name'];
                $ccRecipients[$i]['email'] = $recipient['email'];

            } else if ($recipient['type'] === 'bcc') {
                $bccRecipients[$i]['name'] = $recipient['name'];
                $bccRecipients[$i]['email'] = $recipient['email'];

            } else {
                $toRecipients[$i]['name'] = $recipient['name'];
                $toRecipients[$i]['email'] = $recipient['email'];
            }
        }
  
        $ln = "\r\n";

        $to = '';
        if (! empty($toRecipients)) {
            $to = 'To: ';
            foreach ($toRecipients as $recipient) {
                $to .= '"' . $recipient['name'] . '" <' . $recipient['email'] . '>, ';
            }
            $to = rtrim($to, ', ') . $ln;
        }

        $cc = '';
        if (! empty($ccRecipients)) {
            $cc = 'Cc: ';
            foreach ($ccRecipients as $recipient) {
                $cc .= '"' . $recipient['name'] . '" <' . $recipient['email'] . '>, ';
            }
            $cc = rtrim($cc, ', ') . $ln;
        }

        $bcc = '';
        if (! empty($bccRecipients)) {
            $bcc = 'Bcc: ';
            foreach ($bccRecipients as $recipient) {
                $bcc .= '"' . $recipient['name'] . '" <' . $recipient['email'] . '>, ';
            }
            $bcc = rtrim($bcc, ', ') . $ln;
        }

        // Build the header of the email.
        $header = '';
        $header .= 'From: "' . $this->sender['name'] . '" <' . $this->sender['email'] . '>' . $ln;
        $header .= $to;
        $header .= $cc;
        // $header .= $bcc; // Bcc should not be printed in header information.
        $header .= 'Subject: ' . $this->subject . $ln;
        $header .= 'Reply-To: "' . $this->replyTo['name'] . '" <' . $this->replyTo['email'] . '>' . $ln;
        $header .= 'Return-Path: ' . $this->sender['email'] . $ln;
        $header .= 'X-Mailer: shieldon/messenger' . $ln;
        $header .= 'MIME-Version: 1.0' . $ln;  
        $header .= 'Content-type: ' . $this->type . '; charset=' . $this->charset . $ln;

        return $header;
    }
}