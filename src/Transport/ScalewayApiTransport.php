<?php

namespace Korridor\SymfonyScalewayTemMailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ScalewayApiTransport extends AbstractApiTransport
{
    private const HOST = 'api.scaleway.com';

    private string $token;

    private string $region;

    private string $projectId;

    public function __construct(string $token, string $region, string $projectId, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->token = $token;
        $this->region = $region;
        $this->projectId = $projectId;
        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('scaleway+api://%s', $this->getEndpoint());
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $response = $this->client->request('POST', 'https://'.$this->getEndpoint().'/transactional-email/v1alpha1/regions/'.$this->getRegion().'/emails', [
            'headers' => [
                'Accept' => 'application/json',
                'X-Auth-Token' => $this->token,
            ],
            'json' => $this->getPayload($email, $envelope),
        ]);

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface) {
            throw new HttpTransportException('Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote Scaleway server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            throw new HttpTransportException('Unable to send an email: '.$result['message'].' Details: '.print_r($result, true), $response);
        }

        $sentMessage->setMessageId($result['emails'][0]['id']); // TODO: what is message_id

        return $response;
    }

    private function getPayload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'from' => $this->getAddressObject($envelope->getSender()),
            'to' => $this->getAddressesArray($this->getRecipients($email, $envelope)),
            'cc' => $this->getAddressesArray($email->getCc()),
            'bcc' => $this->getAddressesArray($email->getBcc()),
            'subject' => $email->getSubject(),
            'text' => $email->getTextBody(),
            'html' => $email->getHtmlBody(),
            'project_id' => $this->projectId,
            'attachments' => $this->getAttachments($email),
        ];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type', 'sender', 'reply-to'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            // TODO: ?
        }

        return $payload;
    }

    /**
     * @param  Address  $address
     *
     * @return object
     */
    private function getAddressObject(Address $address): object
    {
        return (object) [
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ];
    }

    /**
     * @param  Address[]  $addresses
     *
     * @return array
     */
    private function getAddressesArray(array $addresses): array
    {
        $data = [];
        foreach ($addresses as $address) {
            $data[] = $this->getAddressObject($address);
        }
        return $data;
    }

    private function getAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $att = [
                'name' => $filename,
                'type' => $headers->get('Content-Type')->getBody(),
                'content' => base64_encode($attachment->bodyToString()),
            ];

            $attachments[] = $att;
        }

        return $attachments;
    }

    /**
     * @return string|null
     */
    private function getEndpoint(): ?string
    {
        return ($this->host ?: self::HOST).($this->port ? ':'.$this->port : '');
    }

    /**
     * @return string
     */
    private function getRegion(): string
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
