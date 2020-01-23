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

use Messenger\MessengerInterface;
use RuntimeException;
use StdClass;

use function curl_errno;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function json_decode;

/**
 * Slack Messenger
 * 
 * Sending messages to your Slack channel by using webhook. It is the simplest way to achieve it.
 * 
 * @see https://api.slack.com/messaging/sending
 *      https://api.slack.com/messaging/webhooks
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class Slack implements MessengerInterface
{
    /**
     * Your bot user's OAuth access token.
     *
     * @var string
     */
    private $accessToken = '';

    /**
     * The webhook URL for your Slack channel.
     *
     * @var string
     */
    private $webhook = '';

    /**
     * Your channel. (Not required for using webhook.)
     *
     * @var string
     */
    private $channel = '';

    /**
     * The connection timeout when calling Telegram API.
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * The type of way sending message to Slack.
     *
     * @var string
     */
    private $type = 'api';

    /**
     * @param string $accessToken The developer access token.
     * @param int    $timeout     After n seconds the connection will be stopped.
     */
    public function __construct(string $tokenOrWebhook, string $channel = '', int $timeout = 5)
    {
        if (strpos($tokenOrWebhook, 'https://hooks.slack.com') !== false) {
            $this->webhook = $tokenOrWebhook;
            $this->type = 'webhook';
        } else {
            $this->accessToken = $tokenOrWebhook;
            $this->type = 'api';
        }

        $this->channel = $channel;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): void
    {
        if ($this->type === 'api') {
            $this->sendByApi($message);
        }

        if ($this->type === 'webhook') {
            $this->sendByWebhook($message);
        }
    }

    /**
     * Send messages to your Slack channel via Slack API.
     * API Doc: https://api.slack.com/methods/chat.postMessage
     *
     * @param string $message
     *
     * @return void
     */
    private function sendByApi($message)
    {
        // Prepare data.
        $data = new stdClass();
        $data->channel = $this->channel;
        $data->text = $message;

        $message = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/json',
            'Authorization: ' . 'Bearer ' . $this->accessToken,
        ]);

        $result = curl_exec($ch);

        if (! curl_errno($ch)) {

            $resultData = json_decode($result, true);
           
            if (true !== $resultData['ok']) {
                throw new RuntimeException(
                    'An error occurrs when accessing Slack.' . "\n" .
                    $result
                );
           }
        }

        curl_close($ch);
    }

    /**
     * Send messages to your Slack channel via Slack webhook.
     *
     * @param string $message
     *
     * @return void
     */
    private function sendByWebhook($message)
    {
        // Prepare data.
        $data = new stdClass();
        $data->text = $message;

        $message = json_encode($data);

        // Start transmit data to Slack.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->webhook);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/json',
            'Content-Length: ' . strlen($message),
        ]);

        $result = curl_exec($ch);

        if (! curl_errno($ch)) {
           if ('ok' !== $result) {
                throw new RuntimeException(
                    'An error occurrs when accessing Slack.' . "\n" .
                    $result
                );
           }
        }

        curl_close($ch);
    }

    /**
     * For API only.
     *
     * @inheritDoc
     */
    public function provider(): string
    {
        return 'https://slack.com/api/chat.postMessage';
    }
}