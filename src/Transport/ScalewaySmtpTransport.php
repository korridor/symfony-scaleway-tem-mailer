<?php

namespace Symfony\Component\Mailer\Bridge\Scaleway\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Kevin Verschaeve
 */
class ScalewaySmtpTransport extends EsmtpTransport
{
    private $messageStream;

    private const HOSTNAME = 'smtp.tem.scw.cloud';

    public function __construct(string $id, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct(self::HOSTNAME, 587, false, $dispatcher, $logger);

        $this->setUsername($id);
        $this->setPassword($id);
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Message) {
            $this->addScalewayHeaders($message);
        }

        return parent::send($message, $envelope);
    }

    private function addScalewayHeaders(Message $message): void
    {
        $headers = $message->getHeaders();

        // TODO
    }

    /**
     * @return $this
     */
    public function setMessageStream(string $messageStream): static
    {
        $this->messageStream = $messageStream;

        return $this;
    }
}
