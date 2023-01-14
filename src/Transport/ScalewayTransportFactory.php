<?php

namespace Korridor\SymfonyScalewayTemMailer\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class ScalewayTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $transport = null;
        $scheme = $dsn->getScheme();
        $projectId = $this->getUser($dsn);
        $token = $this->getPassword($dsn);
        $region = $dsn->getOption('region', 'fr-par');

        if ('scaleway+api' === $scheme) {
            $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
            $port = $dsn->getPort();

            $transport = (new ScalewayApiTransport(
                $token,
                $region,
                $projectId,
                $this->client,
                $this->dispatcher,
                $this->logger
            ))
                ->setHost($host)
                ->setPort($port);
        }

        if ('scaleway+smtp' === $scheme || 'scaleway+smtps' === $scheme) {
            $transport = new ScalewaySmtpTransport($token, $region, $projectId, $this->dispatcher, $this->logger);
        }

        if (null !== $transport) {
            return $transport;
        }

        throw new UnsupportedSchemeException($dsn, 'scaleway', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['scaleway+api', 'scaleway+smtp', 'scaleway+smtps'];
    }
}
