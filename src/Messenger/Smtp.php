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
use RuntimeException;

use function fsockopen;
use function fputs;
use function fgets;
use function fclose;

/**
 * A very simple SMTP client.
 * 
 * This class is just for sending message simply. As a part of Shieldon Messenger, we can 
 * just keep it as simple as possible, so if you are looking for a completely featured SMTP 
 * class, using PHP Mailer (https://github.com/PHPMailer/PHPMailer) instead.
 * 
 * This blog post is also a good study: https://blog.mailtrap.io/cc-bcc-in-smtp/
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.1.0
 */
class Smtp extends AbstractMailer implements MessengerInterface
{
    /**
     * SMTP username.
     *
     * @var string
     */
    private $user = '';

    /**
     * SMTP password.
     *
     * @var string
     */
    private $pass = '';

    /**
     * The FQDN or IP address of the target SMTP server
     *
     * @var string
     */
    private $host = '';

    /**
     * The port of the target SMTP server.
     *
     * @var int
     */
    private $port = 25;

    /**
     * The connection result.
     *
     * @var array
     */
    protected $connection = [];

    /**
     * Socket resource instance.
     *
     * @var resource
     */
    private $smtp;

    /**
     * Debug mode.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * @param string $user The username that you want to use to login to SMTP server.
     * @param string $pass The password of that user,
     * @param string $host The FQDN or IP address of the target SMTP server
     * @param int    $port The port of the target SMTP server.
     */
    public function __construct(string $user, string $pass, string $host, int $port)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Connect to SMTP server and then send message.
     * 
     * @see https://tools.ietf.org/html/rfc821
     *      https://tools.ietf.org/html/rfc2821
     *
     * @inheritDoc
     *
     * @return void
     */
    function send(string $message): void
    {
        // Prepare the recipient data.
        $toRecipients = [];
        $ccRecipients = [];
        $bccRecipients = [];

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

        // Let's talk to SMTP server.
        if ($this->smtp = @fsockopen($this->host, $this->port, $errno, $errstr, 15)) {

            $talk['connection'] = $this->talk($this->smtp, 220);

            // RFC 821 - 3.5
            // Open a transmission channel.
    
            $talk['hello'] = $this->sendCmd('HELO ' . $_SERVER['SERVER_NAME'], 250);
            $talk['resource'] = $this->sendCmd('AUTH LOGIN', 334);

            // Transmit the username to SMTP server.
            $talk['user'] = $this->sendCmd(base64_encode($this->user), 334);

            // Transmit the password to SMTP server.
            $talk['pass'] = $this->sendCmd(base64_encode($this->pass), 235);

            $talk['from'] = $this->sendCmd('MAIL FROM: <' . $this->user . '>', 250);

            // Apply the recipient list.
            foreach ($toRecipients as $recipient) { 
                $talk['to'] = $this->sendCmd('RCPT TO: <' . $recipient['email'] . '>', 250);
            }

            foreach ($ccRecipients as $recipient) {
                $talk['cc'] =  $this->sendCmd('RCPT TO: <' . $recipient['email'] . '>', 250);
            }

            foreach ($bccRecipients as $recipient) {
                $talk['bcc'] =  $this->sendCmd('RCPT TO: <' . $recipient['email'] . '>', 250);
            }

            // Let's build DATA content.

            $talk['data'] =  $this->sendCmd('DATA', 354);

            // transmit them only as a <CRLF> sequence.
            $ln = "\r\n";

            $to = '';
            foreach ($toRecipients as $recipient) {
                $to .= 'To: <' . $recipient['email'] . '>' . $ln;
            }

            $cc = '';
            foreach ($ccRecipients as $recipient) {
                $cc .= 'Cc: <' . $recipient['email'] . '>' . $ln;
            }

            // The default is iso-8859-1, but using UTF-8 instead.
            $charset = 'utf-8';
            $type = $this->getContentType($message);

            $headers = '';
            $headers .= $to;
            $headers .= 'Subject: ' . $this->subject . $ln;
            $headers .= 'From: <' . $this->sender['email'] . '>' . $ln;
            $headers .= 'Reply-To: <' . $this->sender['email'] . '>' . $ln;
            $headers .= 'Return-Path: ' . $this->sender['email'] . $ln;
            $headers .= $cc;
            $headers .= 'X-Mailer: Shieldon Messenger' . $ln;
            $headers .= 'MIME-Version: 1.0' . $ln;  
            $headers .= 'Content-type: ' . $type . '; charset=' . $charset . $ln;

            $body = $headers . $message . $ln . '.' . $ln;

            $talk['send'] = $this->sendCmd($body, 250);

            $this->sendCmd('QUIT');
            fclose($this->smtp);

        } else {

            if ($this->debug) {
                throw new RuntimeException('Error occurred when connecting to ' . $this->host . ' (#' . $errno . ' - ' . $errstr . ')');
            }
        }

        if (empty($talk) && $this->debug) {
            throw new RuntimeException('PHP fsockopen() is not supported on your system.');
        }

        $this->connection = $talk;
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return '';
    }

    /**
     * Print the connection result. (for debugging purpose)
     *
     * @return string
     */
    public function printResult()
    {
        $data = '';

        foreach ($this->connection as $key => $value) {
            $data .= $key . ': ' . $value . "\n";
        }

        return $data;
    }

    /**
     * Debug mode.
     *
     * @return void
     */
    public function debugMode(bool $mode = false)
    {
        $this->debug = $mode;
    }

    /**
     * Send command to SMTP server.
     *
     * @param string $command
     * @param string $expect
     *
     * @return string
     */
    private function sendCmd(string $command, int $expect = 0)
    {
        fputs($this->smtp, $command . "\r\n");

        return $this->talk($this->smtp, $expect);
    }

    /**
     * Talk to SMTP server.
     *
     * @param mixed $socket Object or false.
     * @param int   $answer Expected answer.
     *
     * @return void
     */
    private function talk($socket, $answer)
    {
        $success = false;

        $responseBody = fgets($socket, 1024);

        if ($this->debug && substr($responseBody, 3, 1) !== ' ' && !$responseBody) {
            throw new RuntimeException('Unable to fetch expected response.');
        }
 
        if (! empty($responseBody) && substr($responseBody, 0, 3) === $answer) {
            $success = true;
        }

        if ($this->debug && ! $success) {
            throw new RuntimeException('Unable to send email.)');
        }

        return $responseBody ?? 'Failed.';
    }
}