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

        // transmit them only as a <CRLF> sequence.
        $ln = "\r\n";

        // The default is iso-8859-1, but using UTF-8 instead.
        $charset = 'UTF-8';
        $type = $this->getContentType($message);
        $headers = 'MIME-Version: 1.0' . $ln;  
        $headers .= 'Content-type: ' . $type . '; charset=' . $charset . $ln;
        $headers .= 'X-Mailer: Shieldon Messenger' . $ln;

        // Let's talk to SMTP server.
        if ($smtp = @fsockopen($this->host, $this->port)) {

            // RFC 821 - 3.5
            // Open a transmission channel.
            fputs($smtp, 'EHLO ' . $_SERVER['HTTP_HOST'] . $ln);
            $talk['hello'] = fgets($smtp, 1024);

            fputs($smtp, 'AUTH LOGIN' . $ln);
            $talk['res'] = fgets($smtp, 1024);

            // Transmit the username to SMTP server.
            fputs($smtp, $this->user . $ln);
            $talk['user'] = fgets($smtp, 1024);

            // Transmit the password to SMTP server.
            fputs($smtp, $this->pass . $ln);
            $talk['pass'] = fgets($smtp, 256);

            fputs ($smtp, 'MAIL FROM: <' . $this->sender['email'] . '>' . $ln); 
            $talk['From'] = fgets($smtp, 1024);

            // Apply the recipient list.
            foreach ($toRecipients as $recipient) {
                fputs ($smtp, 'RCPT TO: <' . $recipient['email'] . '>' . $ln); 
                $talk['To'] = fgets($smtp, 1024);
            }

            foreach ($ccRecipients as $recipient) {
                fputs ($smtp, 'RCPT TO: <' . $recipient['email'] . '>' . $ln); 
                $talk['Cc'] = fgets($smtp, 1024);
            }

            foreach ($bccRecipients as $recipient) {
                fputs ($smtp, 'RCPT TO: <' . $recipient['email'] . '>' . $ln); 
                $talk['Bcc'] = fgets($smtp, 1024);
            }

            // Let's build DATA content.
            fputs($smtp, 'DATA' . $ln);
            $talk['data'] = fgets($smtp, 1024);

            $to = '';
            foreach ($toRecipients as $recipient) {
                $to .= 'To: <' . $recipient['email'] . '>' . $ln;
            }

            $cc = '';
            foreach ($ccRecipients as $recipient) {
                $cc .= 'Cc: <' . $recipient['email'] . '>' . $ln;
            }

            fputs($smtp, '
                From: <'   . $this->sender['email'] . '>' . $ln . '
                '          . $to                          . $ln . '
                '          . $cc                          . $ln . '
                '          . $headers                     . $ln . '
                Subject: ' . $this->subject               . $ln . '
                '          . $message                     . $ln . '
                .'         . $ln                          // terminated by `<CRLF>.<CRLF>`
            );
            $talk['send'] = fgets($smtp, 256);

            fputs($smtp, 'QUIT' . "\r\n"); 
            fclose($smtp); 
        }

        if (empty($talk)) {
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
}