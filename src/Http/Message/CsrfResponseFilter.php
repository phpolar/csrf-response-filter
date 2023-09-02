<?php

declare(strict_types=1);

namespace Phpolar\CsrfResponseFilter\Http\Message;

use PhpContrib\Http\Message\ResponseFilterInterface;
use PhpContrib\Http\Message\ResponseFilterStrategyInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Attaches a CSRF token to a response.
 */
final class CsrfResponseFilter implements ResponseFilterInterface
{
    public function __construct(private ResponseFilterStrategyInterface $filterStrategy)
    {
    }

    /**
     * Attach the token to the response.
     */
    public function filter(ResponseInterface $response): ResponseInterface
    {
        return $this->filterStrategy->algorithm($response);
    }
}
