<?php

declare(strict_types=1);

namespace Korridor\SymfonyScalewayTemMailer\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewaySmtpTransport;
use Symfony\Component\Mime\Email;

class ScalewaySmtpTransportTest extends TestCase
{
    public function testCustomHeader(): void
    {
        // Arrange
        $email = new Email();
        $email->getHeaders()->addTextHeader('foo', 'bar');
        $transport = new ScalewaySmtpTransport('token', 'fr-par', 'project-id');
        $method = new \ReflectionMethod(ScalewaySmtpTransport::class, 'addScalewayHeaders');

        // Act
        $method->invoke($transport, $email);

        // Assert
        $this->assertCount(1, $email->getHeaders()->toArray());
        $this->assertSame('foo: bar', $email->getHeaders()->get('FOO')->toString());
    }

    public function testTagAndMetadataAndMessageStreamHeaders(): void
    {
        // Arrange
        $email = new Email();
        $email->getHeaders()->addTextHeader('foo', 'bar');
        $transport = new ScalewaySmtpTransport('token', 'fr-par', 'project-id');
        $method = new \ReflectionMethod(ScalewaySmtpTransport::class, 'addScalewayHeaders');

        // Act
        $method->invoke($transport, $email);

        // Assert
        $this->assertCount(1, $email->getHeaders()->toArray());
        $this->assertSame('foo: bar', $email->getHeaders()->get('FOO')->toString());
    }
}
