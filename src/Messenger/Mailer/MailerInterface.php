<?php declare(strict_types=1);
/*
 * This file is part of the Messenger package.
 *
 * (c) Terry L. <contact@terryl.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shieldon\Messenger\Mailer;

/**
 * Mailer Interface
 * 
 * @author Terry L. <contact@terryl.in>
 * @since 1.0.0
 */
interface MailerInterface
{
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
     * Set The subject of the email that you want to send.
     *
     * @param string $subject The subject text.
     *
     * @return void
     */
    public function setSubject(string $subject): void;

    /**
     * Add a recipient's information by email only.
     *
     * @param string $email
     * @param string $name  (option)
     * @param string $type  (option) to, cc, bcc
     *
     * @return void
     */
    public function addRecipient(string $email, string $name, string $type): void;

    /**
     * Add a recipient for carbon copy.
     *
     * @param string $email
     * @param string $name  (option)
     *
     * @return void
     */
    public function addCc(string $email, string $name): void;

    /**
     * Add a recipient for blind carbon copy.
     *
     * @param string $email
     * @param string $name  (option)
     *
     * @return void
     */
    public function addBcc(string $email, string $name): void;

    /**
     * Add a Reply-To email address.
     *
     * @param string $email
     * @param string $name  (option)
     *
     * @return void
     */
    public function addReplyTo(string $email, string $name): void;
}