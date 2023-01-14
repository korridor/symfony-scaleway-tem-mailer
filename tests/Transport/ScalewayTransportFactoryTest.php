<?php

namespace Korridor\SymfonyScalewayTemMailer\Tests\Transport;

use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayApiTransport;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewaySmtpTransport;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayTransportFactory;
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
            new Dsn('scaleway+api', 'default', 'project-id', 'token', null, [
                'region' => 'fr-par'
            ]),
            true,
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', 'project-id', 'token', null, [
                'region' => 'fr-par'
            ]),
            true,
        ];

        yield [
            new Dsn('scaleway+smtps', 'default', 'project-id', 'token', null, [
                'region' => 'fr-par'
            ]),
            true,
        ];

        yield [
            new Dsn('scaleway+smtp', 'example.com', 'project-id', 'token', null, [
                'region' => 'fr-par'
            ]),
            true,
        ];
    }

    public function createProvider(): iterable
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        yield [
            new Dsn('scaleway+api', 'default', self::USER, self::PASSWORD),
            new ScalewayApiTransport(self::PASSWORD, 'fr-par', self::USER, $this->getClient(), $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+api', 'example.com', self::USER, self::PASSWORD, 8080),
            (new ScalewayApiTransport(self::PASSWORD, 'fr-par', self::USER, $this->getClient(), $dispatcher, $logger))
                ->setHost('example.com')
                ->setPort(8080),
        ];

        yield [
            new Dsn('scaleway+api', 'default', self::USER, self::PASSWORD, null, [
                'region' => 'nl-ams'
            ]),
            new ScalewayApiTransport(self::PASSWORD, 'nl-ams', self::USER, $this->getClient(), $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', self::USER, self::PASSWORD),
            new ScalewaySmtpTransport(self::PASSWORD, 'fr-par', self::USER, $dispatcher, $logger),
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', self::USER, self::PASSWORD, null, [
                'region' => 'nl-ams'
            ]),
            new ScalewaySmtpTransport(self::PASSWORD, 'nl-ams', self::USER, $dispatcher, $logger),
        ];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield [
            new Dsn('scaleway', 'default', self::USER, self::PASSWORD),
            'The "scaleway" scheme is not supported; supported schemes for mailer "scaleway" are:'.
            ' "scaleway+api", "scaleway+smtp", "scaleway+smtps".',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield [new Dsn('scaleway+api', 'default')];
    }
}
