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

/**
 * LineNotify Messenger
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class LineNotify implements MessengerInterface
{
    /**
     * This access token is obtained by clicking `Generate token` button
     * at https://notify-bot.line.me/my/
     *
     * @var string
     */
    private $accessToken = '';

    /**
     * The connection timeout when calling Telegram API.
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * @param string $accessToken The developer access token.
     * @param int    $timeout     After n seconds the connection will be stopped.
     */
    public function __construct(string $accessToken, int $timeout = 5)
    {
        $this->accessToken = $accessToken;
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "message=$message");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: '  . 'application/x-www-form-urlencoded',
            'Authorization: ' . 'Bearer ' . $this->accessToken,
        ]);

        $result = curl_exec($ch);

        if (! curl_errno($ch)) {
            $result = json_decode($result, true);

            if (200 !== $result['status']) {
                throw new RuntimeException('An error occurred when accessing Line Notify API. (' . $result['message'] . ')');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function provider(): string
    {
        return 'https://notify-api.line.me/api/notify';
    }
}