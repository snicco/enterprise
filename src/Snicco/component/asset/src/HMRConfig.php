<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Asset;

final class HMRConfig
{
    private string  $host;

    private string  $port;

    /**
     * @var 'http'|'https'|null
     */
    private ?string $scheme;

    /**
     * @param "http"|"https"|null $scheme
     */
    public function __construct(string $host, string $port, string $scheme = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->scheme = $scheme;
    }

    public static function fromDefaults(): self
    {
        return new self('localhost', '8080');
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): string
    {
        return $this->port;
    }

    /**
     * @return 'http'|'https'|null
     */
    public function scheme(): ?string
    {
        return $this->scheme;
    }
}
