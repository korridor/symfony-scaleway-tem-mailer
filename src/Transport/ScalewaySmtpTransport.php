<?php

namespace Korridor\SymfonyScalewayTemMailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class ScalewaySmtpTransport extends EsmtpTransport
{
    private const HOSTNAME = 'smtp.tem.scw.cloud';

    private string $token;

    private string $region;

    private string $projectId;

    public function __construct(
        string $token,
        string $region,
        string $projectId,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->token = $token;
        $this->region = $region;
        $this->projectId = $projectId;
        parent::__construct(self::HOSTNAME, 587, false, $dispatcher, $logger);

        $this->setUsername($projectId);
        $this->setPassword($token);
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
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }
}
