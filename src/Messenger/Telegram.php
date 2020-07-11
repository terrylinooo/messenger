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

use function curl_init;
use function curl_setopt;
use function json_decode;
use function http_build_query;

/**
 * Telegram Messenger
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class Telegram implements MessengerInterface
{
    use MessengerTrait;

    /**
     * API key.
     *
     * Add `BotFather` to start a conversation.
     * Type command `/newbot` to obtain your api key.
     *
     * @var string
     */
    private $apiKey;

    /**
     * Telegram channel name.
     *
     * For example, @your_channel_name, and remember, make your channel type public.
     * If you want to send message to your private channel, googling will find solutions.
     *
     * @var string
     */
    private $channel;

    /**
     * @param string $apiKey  Telegram bot access token provided by BotFather
     * @param string $channel Telegram channel name
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $apiKey, string $channel, int $timeout = 5)
    {
        $this->apiKey = $apiKey;
        $this->channel = $channel;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'text' => $message,
            'chat_id' => $this->channel,
        ]));

        $ret = $this->executeCurl($ch);

        if ($ret['success']) {
            $result = json_decode($ret['result'], true);

            if (! $result['ok']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurs when connecting Telegram API. (' . $result['description'] . ')';
                $this->resultData['result'] = $result;

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
        return 'https://api.telegram.org/bot' . $this->apiKey . '/SendMessage';
    }
}