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

use function curl_init;
use function curl_setopt;

/**
 * Send message through Mailgun API.
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.3.0
 */
class Mailgun extends AbstractMailer implements MessengerInterface
{
    use MessengerTrait;

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
     * @param string $apiKey  Your Mailgun API key.
     * @param string $domain  Your domain.
     * @param int    $timeout After n seconds the connection will be stopped.
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
    public function send(string $message): bool
    {
        $message = $this->prepare($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($message));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:'. $this->apiKey);

        $ret = $this->executeCurl($ch);

        if ($ret['success']) {
 
            if (200 !== $ret['httpcode']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurs when accessing MailGun v3 API. (#' . $ret['httpcode'] . ')';

                if ($this->isDebug()) {
                    throw new RuntimeException($this->resultData['message']);
                }

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return 'https://api.mailgun.net/v3/' . $this->domain . '/messages';
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
        $type = $this->getContentType($message);
        $data = [];

        foreach($this->recipients as $i => $recipient) {

            if ($recipient['type'] === 'cc') {
                $ccRecipients[$i] = $recipient['email'];

            } else if ($recipient['type'] === 'bcc') {
                $bccRecipients[$i] = $recipient['email'];

            } else {
                $toRecipients[$i] = $recipient['email'];
            }
        }

        if (! empty($toRecipients)) {
            $data['to'] = $toRecipients;
        }

        if (! empty($ccRecipients)) {
            $data['cc'] = $ccRecipients;
        }

        if (! empty($bccRecipients)) {
            $data['bcc'] = $bccRecipients;
        }

        // Mailgun does not allow sending email with cc or bcc only.
        if ((isset($bccRecipients) || isset($ccRecipients)) && empty($toRecipients)) {
            $data['to'] = $this->sender['email'];
        }

        $data['from'] = $this->sender['email'];
        $data['subject'] = $this->subject;
        $data['text'] = $message;

        if ('text/html' === $type) {
            $data['html'] = $message;
            unset($data['text']);
        }

        return $data;
    }
}