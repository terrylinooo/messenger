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
use function strpos;

/**
 * Slack Messenger
 * 
 * Sending messages to your Slack channel by using webhook. It is the simplest way to achieve it.
 * 
 * @see https://api.slack.com/messaging/webhooks
 *      
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class SlackWebhook implements MessengerInterface
{
    use MessengerTrait;

    /**
     * The webhook URL for your Slack channel.
     *
     * @var string
     */
    private $webhook = '';

    /**
     * @param string $webhook The webhook of your Slack workspace's channel.
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $webhook, int $timeout = 5)
    {
        $this->webhook = $webhook;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): bool
    {
        if (strpos($this->webhook, $this->provider()) === false) {
            $this->resultData['success'] = false;
            $this->resultData['message'] = 'Webhook URL is invalid.';
            $this->resultData['result'] = $this->webhook;
    
            if ($this->isDebug()) {
                throw new RuntimeException($this->resultData['message']);
            }

            return false;
        }

        // Prepare data.
        $data = new stdClass();
        $data->text = $message;

        $message = json_encode($data);

        // Start transmit data to Slack.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->webhook);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/json',
            'Content-Length: ' . strlen($message),
        ]);

        $ret = $this->executeCurl($ch);

        if ($ret['success']) {

            if ('ok' !== $ret['result']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurrs when connecting Slack via webhook.';

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
        return 'https://hooks.slack.com';
    }
}