<?php

declare(strict_types=1);

namespace Korridor\SymfonyScalewayTemMailer\Tests\Transport;

use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayApiTransport;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewaySmtpTransport;
use Korridor\SymfonyScalewayTemMailer\Transport\ScalewayTransportFactory;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Mailer\Test\TransportFactoryTestCase;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

class ScalewayTransportFactoryTest extends TransportFactoryTestCase
{
    public function getFactory(): TransportFactoryInterface
    {
        return new ScalewayTransportFactory(null, new MockHttpClient(), new NullLogger());
    }

    public static function supportsProvider(): iterable
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

    public static function createProvider(): iterable
    {
        $client = new MockHttpClient();
        $logger = new NullLogger();

        yield [
            new Dsn('scaleway+api', 'default', self::USER, self::PASSWORD),
            new ScalewayApiTransport(self::PASSWORD, 'fr-par', self::USER, $client, null, $logger),
        ];

        yield [
            new Dsn('scaleway+api', 'example.com', self::USER, self::PASSWORD, 8080),
            (new ScalewayApiTransport(self::PASSWORD, 'fr-par', self::USER, $client, null, $logger))
                ->setHost('example.com')
                ->setPort(8080),
        ];

        yield [
            new Dsn('scaleway+api', 'default', self::USER, self::PASSWORD, null, [
                'region' => 'nl-ams'
            ]),
            new ScalewayApiTransport(self::PASSWORD, 'nl-ams', self::USER, $client, null, $logger),
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', self::USER, self::PASSWORD),
            new ScalewaySmtpTransport(self::PASSWORD, 'fr-par', self::USER, null, $logger),
        ];

        yield [
            new Dsn('scaleway+smtp', 'default', self::USER, self::PASSWORD, null, [
                'region' => 'nl-ams'
            ]),
            new ScalewaySmtpTransport(self::PASSWORD, 'nl-ams', self::USER, null, $logger),
        ];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield [
            new Dsn('scaleway', 'default', self::USER, self::PASSWORD),
            'The "scaleway" scheme is not supported; supported schemes for mailer "scaleway" are:'.
            ' "scaleway+api", "scaleway+smtp", "scaleway+smtps".',
        ];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield [new Dsn('scaleway+api', 'default')];
    }
}
