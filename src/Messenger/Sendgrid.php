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
use stdClass;

use function curl_init;
use function curl_setopt;
use function json_encode;
use function strlen;

/**
 * Send message through Sendgrid API.
 * 
 * @see https://sendgrid.com/docs/API_Reference/api_v3.html
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class Sendgrid extends AbstractMailer implements MessengerInterface
{
    use MessengerTrait;

    /**
     * The API key that you have applied for from Sendgrid.
     *
     * @var string
     */
    private $apiKey = '';

    /**
     * @param string $apiKey  Your SendGrid API key.
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $apiKey, int $timeout = 5)
    {
        $this->apiKey = $apiKey;
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Length: ' . strlen($message)
        ]);

        $ret = $this->executeCurl($ch, true);

        if ($ret['success']) {

            $result = json_decode($ret['result'], true);

            if (! empty($result['errors'][0]['message'])) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = $result['errors'][0]['message'];

                if ($this->isDebug()) {
                    throw new RuntimeException($this->resultData['message']);
                }

                return false;
            }
 
            if (202 !== $ret['httpcode']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurs when connecting SendGrid v3 API. (#' . $ret['httpcode'] . ')';

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
        return 'https://api.sendgrid.com/v3/mail/send';
    }

    /**
     * Prepare Sendgrid v3 data structure.
     * 
     * @param string Message body.
     *
     * @return string JSON formatted string.
     */
    protected function prepare(string $message): string
    {
        $data = new stdClass();

        // Sender.
        $data->from = new stdClass();
        $data->from->email = $this->sender['email'];
        $data->from->name = $this->sender['name'];

        $toRecipients = [];  // primary recipients.
        $ccRecipients = [];  // cc: carbon copy
        $bccRecipients = []; // bcc: blind carbon copy

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

        $data->personalizations[0] = new stdClass();

        if (! empty($toRecipients)) {
            $data->personalizations[0]->to = $toRecipients;
        }

        if (! empty($ccRecipients)) {
            $data->personalizations[0]->cc = $ccRecipients;
        }

        if (! empty($bccRecipients)) {
            $data->personalizations[0]->bcc = $bccRecipients;
        }

        if (! empty($this->replyTo)) {
            $data->reply_to = new stdClass();
            $data->reply_to->email = $this->sender['email'];
            $data->reply_to->name = $this->sender['name'];
        }

        $data->subject = $this->subject;

        $data->content[0] = new stdClass();
        $data->content[0]->type = $this->getContentType($message);
        $data->content[0]->value = $message;

        return json_encode($data);
    }
}