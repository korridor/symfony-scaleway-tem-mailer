<?php

namespace Symfony\Component\Mailer\Bridge\Scaleway\Transport;

use Symfony\Component\Mime\Header\UnstructuredHeader;

final class MessageStreamHeader extends UnstructuredHeader
{
    public function __construct(string $value)
    {
        parent::__construct('X-PM-Message-Stream', $value);
    }
}
