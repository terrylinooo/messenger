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

use RuntimeException;

use function base64_encode;
use function fclose;
use function fgets;
use function fputs;
use function fsockopen;
use function restore_error_handler;
use function set_error_handler;
use function stream_socket_client;
use function stream_socket_enable_crypto;
use function wordwrap;

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
     * The encryption of connecting to SMTP server.
     * tls (STARTTLS), ssl (SMTPS), empty string (SMTP)
     *
     * @var string
     */
    protected $encryptionType = '';

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
    public function send(string $message): bool
    {
        $this->type = $this->getContentType($message);

        if ($this->type !== 'text/html') {
            $message = wordwrap($message, 70);
        }

        if (empty($this->sender['email'])) {
            $this->sender['email'] = $this->user;
        }

        $header = $this->getHeader();

        if (substr($this->host, 0, 3) == 'tls') {
            $this->host = str_replace('tls://', '', $this->host);
            $this->encryptionType = 'tls';
        }

        // Let's talk to SMTP server.
        if (function_exists('stream_socket_client')) {
            $this->smtp = stream_socket_client($this->host . ':' . $this->port, $errno, $errstr, $this->timeout);

        // `stream_socket_client` is missing? Let's try `fsockopen`;
        } elseif (function_exists('fsockopen')) {
            $this->smtp = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        // We cannot use SMTP because of the following reason...
        } else {
            $this->showError('PHP functons stream_socket_client and fsockopen are missing or disabled. One of them is required.');
            return false;
        }

        if ($this->smtp) {

            $result['connection'] = $this->talk($this->smtp, 220);

            // RFC 821 - 3.5
            // Open a transmission channel.
            $result['hello'] = $this->sendCmd('HELO ' . $_SERVER['SERVER_NAME'], 250);

            // Start TLS conntection.
            if ('tls' === $this->encryptionType) {

                $result['tls'] = $this->sendCmd('STARTTLS', 220);

                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
                }

                set_error_handler([$this, 'errorHandler']);
                $resultCrypto = stream_socket_enable_crypto($this->smtp, true, $cryptoMethod);
                restore_error_handler();

                if (! $resultCrypto) {
                    return false;
                }

                // Need to say hello again after sending STARTTLS command.
                $result['hello'] = $this->sendCmd('HELO ' . $_SERVER['SERVER_NAME'], 250);
            }

            // Start login process.
            $result['auth_type'] = $this->sendCmd('AUTH LOGIN', 334);

            // Transmit the username to SMTP server.
            $result['user'] = $this->sendCmd(base64_encode($this->user), 334);

            // Transmit the password to SMTP server.
            $result['pass'] = $this->sendCmd(base64_encode($this->pass), 235);
        
            //$result['auth_type'] = $this->sendCmd('AUTH PLAIN', 334);
            //$result['user_pass'] = $this->sendCmd(base64_encode("\0" . $this->user . "\0" . $this->pass), 235);

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

            // Sending process is complete.
            $result['quit'] =  $this->sendCmd('QUIT'); // 221

            fclose($this->smtp);
        }

        if (! $this->smtp) {
            $this->showError('An error occurs when connecting to ' . $this->host . '(#' . $errno . ' - ' . $errstr . ')');
        }

        // If there is no error, we assume the email that it is sent.
        $message = '';

        if ($this->success) {
            $message = 'Email is sent.';
        }

        $this->resultData = [
            'success' => $this->success,
            'message' => $message,
            'result'  => $result,
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
        $responseCode = 0;

        if (is_string($responseBody)) {
            $responseCode = (int) substr($responseBody, 0, 3);
        }

        if (! empty($responseBody) && $responseCode !== $answer) {
            if (0 !== $answer) {
                $this->success = false;
            }
        }

        if (! $responseBody || substr($responseBody, 3, 1) !== ' ') {
            $this->success = false;
        }

        return empty($responseBody) ? 'Unable to fetch expected response.' : $responseBody;
    }

    /**
     * Custom PHP error handler.
     *
     * @param int    $no      Error number.
     * @param string $message Error message.
     * @param string $file    Which file that error occurred in.
     * @param int    $line    The line number of the file that error occurred at.
     * 
     * @return void
     */
    protected function errorHandler(int $no, string $message, string $file = '', int $line = 0): void
    {
        $this->success = false;

        $this->resultData = [
            'success' => false,
            'message' => $message,
            'result'  => $no,
        ];

        if ($this->isDebug()) {
            throw new RuntimeException(
                'Connection failed. (' . $no . ':' . $message . ') (file: ' . $file . ' line: ' .  $line . ')'
            );
        }
    }

    /**
     * Simply use errorHandler.
     *
     * @param string $message Error message.
     * @param int    $no      Error number.
     *
     * @return void
     */
    protected function showError(string $message, int $no = 0): void
    {
        $this->errorHandler($no, $message);
    }
}