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

use function curl_errno;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function json_decode;
use function json_encode;

/**
 * RocketChat Messenger
 * 
 * reference: https://rocket.chat/docs/developer-guides/rest-api/chat/postmessage/
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.3.0
 */
class RocketChat implements MessengerInterface
{
    use MessengerTrait;

    /**
     * The user's authorized token.
     *
     * @var string
     */
    private $accessToken = '';

    /**
     * The user's Id.
     *
     * @var string
     */
    private $userId = '';

    /**
     * The base URL of your your RocketChat.
     *
     * @var string
     */
    private $serverUrl = 'http://localhost:3000';

    /**
     * The channel name with the prefix in front of it.
     *
     * @var string
     */
    private $channel = '#general';

    /**
     * @param string $accessToken The user's authorized token.
     * @param string $userId      The user's Id.
     * @param string $serverUrl   The base URL of your your RocketChat.
     * @param string $channel     The channel name with the prefix in front of it.
     * @param int    $timeout     After n seconds the connection will be stopped.
     */
    public function __construct(string $accessToken, string $userId, string $serverUrl, string $channel = '#general', int $timeout = 5)
    {
        $this->accessToken = $accessToken;
        $this->userId = $userId;
        $this->serverUrl = $serverUrl;
        $this->general = $channel;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function send(string $message): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->prepare($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/json',
            'X-User-Id: ' . $this->userId,
            'X-Auth-Token: ' . $this->accessToken,
        ]);

        $result = curl_exec($ch);

        if (! curl_errno($ch)) {
            $result = json_decode($result, true);

            if ($result['success'] !== true) {
                throw new RuntimeException('An error occurs when accessing RocketChat API. (' . $result['message']['msg'] . ')');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return $this->serverUrl . '/api/v1/chat.postMessage';
    }

    /**
     * Prepare RocketChat data structure.
     * 
     * @param string Message body.
     *
     * @return string JSON formatted string.
     */
    protected function prepare(string $message): string
    {
        return json_encode([
            'text' => $message,
            'channel' => $this->channel,
        ]);
    }
}