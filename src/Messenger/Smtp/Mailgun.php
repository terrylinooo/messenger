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
 * A very simple SMTP client for sending email via MailGun service.
 *
 * @author Terry L. <contact@terryl.in>
 * @since 1.3.3
 */
class Mailgun extends Smtp implements MessengerInterface
{
    /**
     * @param string $user    The username that you want to use to login to SMTP server.
     * @param string $pass    The password of that user.
     * @param int    $timeout After n seconds the connection will be stopped.
     */
    public function __construct(string $user, string $pass, int $timeout = 5)
    {
        parent::__construct($user, $pass, 'smtp.mailgun.org', 587, $timeout);
    }
}