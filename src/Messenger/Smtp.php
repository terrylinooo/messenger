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
 * @since 1.3.0
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
    private $resultData = [];

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
     * The connection timeout when calling SMTP server.
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * @param string $user    The username that you want to use to login to SMTP server.
     * @param string $pass    The password of that user,
     * @param string $host    The FQDN or IP address of the target SMTP server
     * @param int    $port    The port of the target SMTP server.
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $user, string $pass, string $host, int $port, int $timeout = 5)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
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
    public function send(string $message): void
    {
        $this->type = $this->getContentType($message);

        if ($this->type !== 'text/html') {
            $message = wordwrap($message, 70);
        }

        $header = $this->getHeader();

        // Let's talk to SMTP server.
        if ($this->smtp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
            $talk['connection'] = $this->talk($this->smtp, 220);

            // RFC 821 - 3.5
            // Open a transmission channel.
            $talk['hello'] = $this->sendCmd('HELO ' . $_SERVER['SERVER_NAME'], 250);

            // Start login process.
            $talk['resource'] = $this->sendCmd('AUTH LOGIN', 334);

            // Transmit the username to SMTP server.
            $talk['user'] = $this->sendCmd(base64_encode($this->user), 334);

            // Transmit the password to SMTP server.
            $talk['pass'] = $this->sendCmd(base64_encode($this->pass), 235);

            // Specify this email is sent by whom.
            $talk['from'] = $this->sendCmd('MAIL FROM: <' . $this->sender['email'] . '>', 250);

            // Apply the recipient list.
            foreach($this->recipients as $i => $recipient) {

                if ($recipient['type'] === 'to') {
                    $toRecipients[$i] = $recipient['email'];
                }

                if ($recipient['type'] === 'cc') {
                    $ccRecipients[$i] = $recipient['email'];
                }
                
                if ($recipient['type'] === 'bcc') {
                    $bccRecipients[$i] = $recipient['email'];
                }
            }

            if (! empty($toRecipients)) {
                foreach ($toRecipients as $recipient) { 
                    $talk['to'] = $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            if (! empty($ccRecipients)) {
                foreach ($ccRecipients as $recipient) {
                    $talk['cc'] =  $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            if (! empty($bccRecipients)) {
                foreach ($bccRecipients as $recipient) {
                    $talk['bcc'] =  $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            // Let's build DATA content.
            $talk['data'] =  $this->sendCmd('DATA', 354);

            // Send email.
            $talk['send'] = $this->sendCmd($header . $message . "\r\n.\r\n", 250);

            $this->sendCmd('QUIT');
            fclose($this->smtp);
        }

        if ($this->debug) {
            
            if (! $this->smtp) {
                throw new RuntimeException(
                    'An error occurs when connecting to ' . $this->host .
                    '(#' . $errno . ' - ' . $errstr . ')'
                );
            }
    
            if (empty($talk)) {
                throw new RuntimeException(
                    'Your system does not support PHP fsockopen() function.'
                );
            }
        }

        $this->resultData = $talk;
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

        foreach ($this->resultData as $key => $value) {
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
     * @return string
     */
    private function talk($socket, $answer): string
    {
        $success = false;

        $responseBody = fgets($socket, 1024);

        if (! empty($responseBody) && substr($responseBody, 0, 3) === $answer) {
            $success = true;
        }

        if ($this->debug) {

            if (! $responseBody || substr($responseBody, 3, 1) !== ' ') {
                throw new RuntimeException('Unable to fetch expected response.');
            }

            if (! $success) {
                throw new RuntimeException('Unable to send email.)');
            }
        }

        return empty($responseBody) ? 'Failed.' : $responseBody;
    }
}