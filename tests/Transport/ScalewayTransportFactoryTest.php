<?php

namespace Symfony\Component\Mailer\Bridge\Scaleway\Tests\Transport;

use Symfony\Component\Mailer\Bridge\Scaleway\Transport\ScalewayApiTransport;
use Symfony\Component\Mailer\Bridge\Scaleway\Transport\ScalewaySmtpTransport;
use Symfony\Component\Mailer\Bridge\Scaleway\Transport\ScalewayTransportFactory;
use Symfony\Component\Mailer\Test\TransportFactoryTestCase;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

class ScalewayTransportFactoryTest extends TransportFactoryTestCase
{
    public function getFactory(): TransportFactoryInterface
    {
        return new ScalewayTransportFactory($this->getDispatcher(), $this->getClient(), $this->getLogger());
    }

    public function supportsProvider(): iterable
    {
        yield [
            new Dsn('scaleway+api', 'default'),
            true,
        ];

        yield [
            new Dsn('scaleway', 'default'),
            true,
        ];

        yield [
            new Dsn('scaleway+smtp', 'default'),
            true,
        ];

        yield [
            new Dsn('scaleway+smtps', 'default'),
            true,
        ];

        yield [
            new Dsn('scaleway+smtp', 'example.com'),
            true,
        ];
    }

    public function createProvider(): iterable
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        yield [
            new Dsn('scaleway+api', 'default', self::USER),
            new ScalewayApiTransport(self::USER, 'fr-par', $this->getClient(), $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+api', 'example.com', self::USER, '', 8080),
            (new ScalewayApiTransport(self::USER, 'fr-par', $this->getClient(), $dispatcher, $logger))->setHost('example.com')->setPort(8080),
        ];

        yield [
            new Dsn('scaleway+api', 'example.com', self::USER, '', 8080, ['message_stream' => 'broadcasts']),
            (new ScalewayApiTransport(self::USER, 'fr-par', $this->getClient(), $dispatcher, $logger))->setHost('example.com')->setPort(8080)->setMessageStream('broadcasts'),
        ];

        yield [
            new Dsn('scaleway', 'default', self::USER),
            new ScalewaySmtpTransport(self::USER, $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', self::USER),
            new ScalewaySmtpTransport(self::USER, $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+smtps', 'default', self::USER),
            new ScalewaySmtpTransport(self::USER, $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+smtps', 'default', self::USER, null, null, ['message_stream' => 'broadcasts']),
            (new ScalewaySmtpTransport(self::USER, $dispatcher, $logger))->setMessageStream('broadcasts'),
        ];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield [
            new Dsn('scaleway+foo', 'default', self::USER),
            'The "scaleway+foo" scheme is not supported; supported schemes for mailer "scaleway" are: "scaleway", "scaleway+api", "scaleway+smtp", "scaleway+smtps".',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield [new Dsn('scaleway+api', 'default')];
    }
}
