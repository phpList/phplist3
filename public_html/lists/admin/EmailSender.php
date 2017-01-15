<?php
/**
 * A plugin should implement this interface if it wants to be responsible
 * for sending emails.
 */
interface EmailSender
{
    /**
     * Send an email.
     *
     * @param PHPlistMailer $phplistmailer mailer instance
     * @param string        $header        the message http headers
     * @param string        $body          the message body
     *
     * @return bool success/failure
     */
    public function send(PHPlistMailer $mailer, $header, $body);
}
