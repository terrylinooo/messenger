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

use function curl_close;
use function curl_errno;
use function curl_exec;
use function curl_init;
use function curl_setopt;

/**
 * Send message through Mailgun API.
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.1.0
 */
class Mailgun extends AbstractMailer implements MessengerInterface
{
    /**
     * The API key that you have applied for from Mailgun.
     *
     * @var string
     */
    private $apiKey = '';

    /**
     * Domain that you are an authorized sender for.
     *
     * @var string
     */
    private $domain = '';

    /**
     * The connection timeout when calling Telegram API.
     *
     * @var integer
     */
    private $timeout = 5;

    /**
     * @param string $apiKey Your Mailgun API key.
     * @param string $domain Your domain.
     * @param int    $timeout     After n seconds the connection will be stopped.
     */
    public function __construct(string $apiKey, string $domain, int $timeout = 5)
    {
        $this->apiKey = $apiKey;
        $this->domain = $domain;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): void
    {
        $message = $this->prepare($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:'. $this->apiKey);

        $result = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) || $httpcode !== 200 || empty($result)) {
             throw new RuntimeException('An error occurred when accessing Mailgun v3 API. (#' . $httpcode . ')');
        }
        
        curl_close($ch);
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return 'https://api.mailgun.net/v2/' . $this->domain . '/messages';
    }

    /**
     * Prepare Maingun v2 data structure.
     * 
     * @param string Message body.
     *
     * @return array
     */
    protected function prepare(string $message): array
    {
        return [
            'from' => $this->sender['email'],
            'to' => $to,
            'subject' => $subject,
            'html' => $message,
            'text' => $plain
        ];
    }
}