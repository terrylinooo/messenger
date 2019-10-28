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

/**
 * Messenger Interface
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
interface MessengerInterface
{
    /**
     * Send message to your Telegram channel.
     *
     * @param string $message The message body.
     * @param array  $logData Simple key-value data.
     * 
     * @return void
     */
    public function send(string $message, array $logData = []): void;

    /**
     * API URL from the third-party service provider.
     *
     * @return string
     */
    public function provider(): string;
}