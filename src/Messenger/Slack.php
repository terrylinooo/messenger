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

use Shieldon\Messenger\Messenger\MessengerInterface;
use Shieldon\Messenger\Messenger\MessengerTrait;

use RuntimeException;
use StdClass;

use function curl_init;
use function curl_setopt;
use function json_decode;

/**
 * Slack Messenger
 * 
 * Sending messages to your Slack channel by using webhook. It is the simplest way to achieve it.
 * 
 * @see https://api.slack.com/messaging/sending
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class Slack implements MessengerInterface
{
    use MessengerTrait;

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
     * @param string $accessToken Your Slack bot's access token.
     * @param string $channel     A channel of your Slack workspace.
     * @param int    $timeout     After n seconds the connection will be stopped.
     */
    public function __construct(string $accessToken, string $channel, int $timeout = 5)
    {
        $this->accessToken = $accessToken;
        $this->channel = $channel;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     * 
     * Send messages to your Slack channel via Slack API.
     * API Doc: https://api.slack.com/methods/chat.postMessage
     */
    public function send(string $message): bool
    {
        // Prepare data.
        $data = new stdClass();
        $data->channel = $this->channel;
        $data->text = $message;

        $message = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/json',
            'Authorization: ' . 'Bearer ' . $this->accessToken,
        ]);

        $ret = $this->executeCurl($ch);

        if ($ret['success']) {

            $result = json_decode($ret['result'], true);

            if (! isset($result['ok']) || ! $result['ok']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurrs when connecting Slack.';

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
     * For API only.
     *
     * @inheritDoc
     */
    public function provider(): string
    {
        return 'https://slack.com/api/chat.postMessage';
    }
}