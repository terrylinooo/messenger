<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Messenger\Mailer;

/**
 * Mailer Interface
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
interface MailerInterface
{
    /**
     * Set the sender's information of the email that your want to send.
     *
     * @param array $sender [name, email]
     *
     * @return void
     */
    public function setSender(array $sender): void;

    /**
     * Add a sender's information by email only.
     *
     * @param string $email
     *
     * @return void
     */
    public function addSender(string $email): void;

    /**
     * Set the receivers' information of the email that you want to send.
     * You can set as many as you need.
     *
     * @param array $recipients [[name, email, type], [name, email, type], ...]
     *              type: to, cc, bcc
     * @return void
     */
    public function setRecipients(array $recipients): void;

    /**
     * Add a recipient's information by email only.
     *
     * @param string $email
     *
     * @return void
     */
    public function addRecipient(string $email): void;

    /**
     * Set The subject of the email that you want to send.
     *
     * @param string $subject The subject text.
     *
     * @return void
     */
    public function setSubject(string $subject): void;
}