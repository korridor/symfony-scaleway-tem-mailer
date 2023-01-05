<?php

namespace Symfony\Component\Mailer\Bridge\Scaleway\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * @author Konstantin Myakshin <molodchick@gmail.com>
 */
final class ScalewayTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $transport = null;
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);

        if ('scaleway+api' === $scheme) {
            $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
            $port = $dsn->getPort();
            $region = 'fr-par'; // TODO

            $transport = (new ScalewayApiTransport($user, $region, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port);
        }

        if ('scaleway+smtp' === $scheme || 'scaleway+smtps' === $scheme || 'scaleway' === $scheme) {
            $transport = new ScalewaySmtpTransport($user, $this->dispatcher, $this->logger);
        }

        if (null !== $transport) {
            $messageStream = $dsn->getOption('message_stream');

            if (null !== $messageStream) {
                $transport->setMessageStream($messageStream);
            }

            return $transport;
        }

        throw new UnsupportedSchemeException($dsn, 'scaleway', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['scaleway', 'scaleway+api', 'scaleway+smtp', 'scaleway+smtps'];
    }
}
