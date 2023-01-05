<?php

namespace Symfony\Component\Mailer\Bridge\Scaleway\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Scaleway\Transport\MessageStreamHeader;
use Symfony\Component\Mailer\Bridge\Scaleway\Transport\ScalewayApiTransport;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
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
                new ScalewayApiTransport('KEY', 'par-fr'),
                'scaleway+api://api.scaleway.com',
            ],
            [
                (new ScalewayApiTransport('KEY', 'par-fr'))->setHost('example.com'),
                'scaleway+api://example.com',
            ],
            [
                (new ScalewayApiTransport('KEY', 'par-fr'))->setHost('example.com')->setPort(99),
                'scaleway+api://example.com:99',
            ],
        ];
    }

    public function testCustomHeader(): void
    {
        // Arrange
        $email = new Email();
        $email->getHeaders()->addTextHeader('foo', 'bar');
        $envelope = new Envelope(new Address('alice@system.com'), [new Address('bob@system.com')]);
        $transport = new ScalewayApiTransport('ACCESS_KEY', 'par-fr');
        $method = new \ReflectionMethod(ScalewayApiTransport::class, 'getPayload');

        // Act
        $payload = $method->invoke($transport, $email, $envelope);

        // Assert
        $this->assertArrayHasKey('Headers', $payload);
        $this->assertCount(1, $payload['Headers']);
        $this->assertEquals(['Name' => 'foo', 'Value' => 'bar'], $payload['Headers'][0]);
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

            return new MockResponse(json_encode(['MessageID' => 'foobar']), [
                'http_code' => 200,
            ]);
        });

        $transport = new ScalewayApiTransport('KEY', 'par-fr', $client);

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
        $this->assertSame('foobar', $message->getMessageId());
    }

    public function testSendThrowsForErrorResponse(): void
    {
        // Arrange
        $client = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            return new MockResponse(json_encode(['Message' => 'i\'m a teapot', 'ErrorCode' => 418]), [
                'http_code' => 418,
                'response_headers' => [
                    'content-type' => 'application/json',
                ],
            ]);
        });
        $transport = new ScalewayApiTransport('KEY', 'par-fr', $client);
        $transport->setPort(8984);

        $mail = new Email();
        $mail->subject('Hello!')
            ->to(new Address('saif.gmati@symfony.com', 'Saif Eddin'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->text('Hello There!');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send an email: i\'m a teapot (code 418).');
        $transport->send($mail);
    }

    public function testTagAndMetadataAndMessageStreamHeaders(): void
    {
        // Arrange
        $email = new Email();
        $email->getHeaders()->add(new TagHeader('password-reset'));
        $email->getHeaders()->add(new MetadataHeader('Color', 'blue'));
        $email->getHeaders()->add(new MetadataHeader('Client-ID', '12345'));
        $email->getHeaders()->add(new MessageStreamHeader('broadcasts'));
        $envelope = new Envelope(new Address('alice@system.com'), [new Address('bob@system.com')]);

        $transport = new ScalewayApiTransport('ACCESS_KEY', 'par-fr');
        $method = new \ReflectionMethod(ScalewayApiTransport::class, 'getPayload');

        // Act
        $payload = $method->invoke($transport, $email, $envelope);

        // Assert
        $this->assertArrayNotHasKey('Headers', $payload);
        $this->assertArrayHasKey('Tag', $payload);
        $this->assertArrayHasKey('Metadata', $payload);
        $this->assertArrayHasKey('MessageStream', $payload);

        $this->assertSame('password-reset', $payload['Tag']);
        $this->assertSame(['Color' => 'blue', 'Client-ID' => '12345'], $payload['Metadata']);
        $this->assertSame('broadcasts', $payload['MessageStream']);
    }

    public function testMultipleTagsAreNotAllowed(): void
    {
        $email = new Email();
        $email->getHeaders()->add(new TagHeader('tag1'));
        $email->getHeaders()->add(new TagHeader('tag2'));
        $envelope = new Envelope(new Address('alice@system.com'), [new Address('bob@system.com')]);

        $transport = new ScalewayApiTransport('ACCESS_KEY', 'par-fr');
        $method = new \ReflectionMethod(ScalewayApiTransport::class, 'getPayload');

        $this->expectException(TransportException::class);

        $method->invoke($transport, $email, $envelope);
    }
}
