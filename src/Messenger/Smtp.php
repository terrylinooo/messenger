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
    use MessengerTrait;

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
     * Socket resource instance.
     *
     * @var resource
     */
    private $smtp;

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

        if (empty($this->sender['email'])) {
            $this->sender['email'] = $this->user;
        }

        $header = $this->getHeader();

        // Let's talk to SMTP server.
        if ($this->smtp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
            $result['connection'] = $this->talk($this->smtp, 220);

            // RFC 821 - 3.5
            // Open a transmission channel.
            $result['hello'] = $this->sendCmd('HELO ' . $_SERVER['SERVER_NAME'], 250);

            // Start login process.
            $result['auth_type'] = $this->sendCmd('AUTH LOGIN', 334);

            // Transmit the username to SMTP server.
            $result['user'] = $this->sendCmd(base64_encode($this->user), 334);

            // Transmit the password to SMTP server.
            $result['pass'] = $this->sendCmd(base64_encode($this->pass), 235);

            // Specify this email is sent by whom.
            $result['from'] = $this->sendCmd('MAIL FROM: <' . $this->sender['email'] . '>', 250);

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
                    $result['to'] = $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            if (! empty($ccRecipients)) {
                foreach ($ccRecipients as $recipient) {
                    $result['cc'] =  $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            if (! empty($bccRecipients)) {
                foreach ($bccRecipients as $recipient) {
                    $result['bcc'] =  $this->sendCmd('RCPT TO: <' . $recipient . '>', 250);
                }
            }

            // Let's build DATA content.
            $result['data'] =  $this->sendCmd('DATA', 354);

            // Send email.
            $result['send'] = $this->sendCmd($header . $message . "\r\n.\r\n", 250);

            $this->sendCmd('QUIT');
            fclose($this->smtp);
        }


        $message = '';

        if (! $this->smtp) {
            $this->success = false;
            $message = 'An error occurs when connecting to ' . $this->host . '(#' . $errno . ' - ' . $errstr . ')';
            $result = [];

            if ($this->isDebug()) {
                throw new RuntimeException($message);
            }
        }

        if (empty($talk)) {
            $this->success = false;
            $message = 'Your system does not support PHP fsockopen() function.';
            $result = [];

            if ($this->isDebug()) {
                throw new RuntimeException($message);
            }
        }

        $this->resultData = [
            'success' => $this->success,
            'message' => $message,
            'result'  => $result,
        ];
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return '';
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
        $responseBody = fgets($socket, 1024);

        if (! empty($responseBody) && substr($responseBody, 0, 3) !== $answer) {
            $this->success = false;
        }

        if (! $responseBody || substr($responseBody, 3, 1) !== ' ') {
            $this->success = false;
        }

        return empty($responseBody) ? 'Unable to fetch expected response.' : $responseBody;
    }
}