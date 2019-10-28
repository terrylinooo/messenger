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
use stdClass;

use function curl_close;
use function curl_errno;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function json_encode;

/**
 * LineNotify Messenger
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class Sendgrid extends AbstractMailer implements MessengerInterface
{
    /**
     * This access token is obtained by clicking `Generate token` button
     * at https://notify-bot.line.me/my/
     *
     * @var string
     */
    private $apiKey = '';

    /**
     * The connection timeout when calling Telegram API.
     *
     * @var integer
     */
    private $timeout = 5;


    /**
     * @param string $apiKey Your SendGrid API key.
     */
    public function __construct(string $apiKey, int $timeout = 5)
    {
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message, array $logData = []): void
    {
        $message = $this->prepare($message, $logData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Length: ' . strlen($message)
        ]);

        $result = curl_exec($ch);

        if (! empty($result)) {
            $result = json_decode($result, true);

            if (! empty($result['errors'][0]['message'])) {
                throw new RuntimeException('An error occurred when accessing Sendgrid v3 API. (' . $result['errors'][0]['message'] . ')');
            }
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) || $httpcode !== 202) {
             throw new RuntimeException('An error occurred when accessing Sendgrid v3 API. (#' . $httpcode . ')');
        }

        curl_close($ch);
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
     * @return string JSON formatted string.
     */
    protected function prepare(string $message, array $logData = []): string
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
            $toRecipients[$i]['name'] = $recipient['name'];
            $toRecipients[$i]['email'] = $recipient['email'];
            $toRecipients[$i]['type'] = $recipient['type'];

            if ($recipient['type'] === 'cc') {
                $ccRecipients[$i]['name'] = $recipient['name'];
                $ccRecipients[$i]['email'] = $recipient['email'];
                $ccRecipients[$i]['type'] = $recipient['type'];
            }
            
            if ($recipient['type'] === 'bcc') {
                $bccRecipients[$i]['name'] = $recipient['name'];
                $bccRecipients[$i]['email'] = $recipient['email'];
                $bccRecipients[$i]['type'] = $recipient['type'];
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

        $data->subject = $this->subject;

        $data->content[0] = new stdClass();
        $data->content[0]->type = $this->getContentType($message);

        if (! empty($logData)) {

            if ($data->content[0]->type === 'text/plain') {
                $wrap = "\n";
            }

            if ($data->content[0]->type === 'text/html') {
                $wrap = "<br>";
            }

            $message .= $wrap;
            foreach ($logData as $key => $value) {
                $message .= $key . ': ' . $value . $wrap;
            }
            $message .= $wrap;
        }

        $data->content[0]->value = $message;

        return json_encode($data);
    }
}