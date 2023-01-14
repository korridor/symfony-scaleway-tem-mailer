<?php

namespace Korridor\SymfonyScalewayTemMailer\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayApiTransport;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ScalewayApiTransportTest extends TestCase
{
    /**
     * @dataProvider getTransportData
     */
    public function testToString(ScalewayApiTransport $transport, string $expected): void
    {
        $this->assertSame($expected, (string) $transport);
    }

    public function getTransportData(): array
    {
        return [
            [
                new ScalewayApiTransport('token', 'par-fr', 'project-id'),
                'scaleway+api://api.scaleway.com',
            ],
            [
                (new ScalewayApiTransport('token', 'par-fr', 'project-id'))->setHost('example.com'),
                'scaleway+api://example.com',
            ],
            [
                (new ScalewayApiTransport('token', 'par-fr', 'project-id'))->setHost('example.com')->setPort(99),
                'scaleway+api://example.com:99',
            ],
        ];
    }

    public function testSend(): void
    {
        // Arrange
        $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.scaleway.com/transactional-email/v1alpha1/regions/par-fr/emails', $url);

            $body = json_decode($options['body'], true);
            $this->assertSame(['email' => 'fabpot@symfony.com', 'name' => 'Fabien'], $body['from']);
            $this->assertSame([['email' => 'saif.gmati@symfony.com', 'name' => 'Saif Eddin']], $body['to']);
            $this->assertSame([['email' => 'other.person@symfony.com', 'name' => 'Other Person']], $body['cc']);
            $this->assertSame([['email' => 'another.person@symfony.com', 'name' => 'Another Person']], $body['bcc']);
            $this->assertSame('Hello!', $body['subject']);
            $this->assertSame('Hello There!', $body['text']);

            return new MockResponse(json_encode([
                'emails' => [
                    (object) [
                        'id' => 'email-1',
                        'message_id' => 'some-uuid',
                    ],
                    (object) [
                        'id' => 'email-2',
                        'message_id' => 'some-uuid',
                    ]
                ]
            ]), [
                'http_code' => 200,
            ]);
        });

        $transport = new ScalewayApiTransport('token', 'par-fr', 'project-id', $client);

        $mail = new Email();
        $mail->subject('Hello!')
            ->to(new Address('saif.gmati@symfony.com', 'Saif Eddin'))
            ->cc(new Address('other.person@symfony.com', 'Other Person'))
            ->bcc(new Address('another.person@symfony.com', 'Another Person'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->text('Hello There!');

        // Act
        $message = $transport->send($mail);

        // Assert
        $this->assertSame('some-uuid', $message->getMessageId());
    }

    public function testSendThrowsForErrorResponse(): void
    {
        // Arrange
        $client = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            return new MockResponse(json_encode([
                'message' => 'i\'m a teapot',
            ]), [
                'http_code' => 418,
                'response_headers' => [
                    'content-type' => 'application/json',
                ],
            ]);
        });
        $transport = new ScalewayApiTransport('token', 'par-fr', 'project-id', $client);
        $transport->setPort(8984);

        $mail = new Email();
        $mail->subject('Hello!')
            ->to(new Address('saif.gmati@symfony.com', 'Saif Eddin'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->text('Hello There!');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send an email: i\'m a teapot');
        $transport->send($mail);
    }
}
