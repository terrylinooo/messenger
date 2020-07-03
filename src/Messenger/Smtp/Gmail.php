<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shieldon\Messenger\Smtp;

use Shieldon\Messenger\Messenger\MessengerInterface;
use Shieldon\Messenger\Smtp;

/**
 * A very simple SMTP client for sending email via Gmail service.
 * 
 * Notice:
 * 
 * Google doesn't like people use their SMTP server to sending email by scripts, to
 * make sure it can work without problems, you have to set the settings right:
 * 
 * 1. Check your Google Accounts -> Access for less secure apps -> Turn on
 * 2. Use your host where you use to send email with your Google account and confirm that 
 *    you have trusted the device on.
 * 
 *    Good luck.
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.3.0
 */
class Gmail extends Smtp implements MessengerInterface
{
    /**
     * @param string $user    The username that you want to use to login to SMTP server.
     * @param string $pass    The password of that user.
     * @param mixed  $type    ssl|tls - SSL or TLS protocol.
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $user, string $pass, $type = 'ssl', int $timeout = 5)
    {
        if (is_numeric($type)) {
            $timeout = (int) $type;
            $type = 'ssl';
        }

        if ($type === 'tls') {
            $host = 'tls://smtp.gmail.com';
            $port = 587;
            $this->encryptionType = 'tls';
        }

        if ($type === 'ssl') {
            $host= 'ssl://smtp.gmail.com';
            $port = 465;
            $this->encryptionType = 'ssl';
        }

        if ($this->encryptionType !== '') {
            parent::__construct($user, $pass, $host, $port, $timeout);
        }
    }
}