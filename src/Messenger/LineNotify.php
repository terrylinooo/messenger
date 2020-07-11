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

/**
 * LineNotify Messenger
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
class LineNotify implements MessengerInterface
{
    use MessengerTrait;

    /**
     * This access token is obtained by clicking `Generate token` button
     * at https://notify-bot.line.me/my/
     *
     * @var string
     */
    private $accessToken = '';

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
    public function send(string $message): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->provider());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
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

        $ret = $this->executeCurl($ch);

        if ($ret['success']) {

            $result = json_decode($ret['result'], true);

            if (200 !== $result['status']) {
                $this->resultData['success'] = false;
                $this->resultData['message'] = 'An error occurs when connecting Line Notify API. (' . $result['message'] . ')';
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
        return 'https://notify-api.line.me/api/notify';
    }
}