<?php

declare(strict_types=1);

namespace Phpolar\CsrfResponseFilter\Http\Message;

use PhpContrib\Http\Message\ResponseFilterStrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Stringable;

/**
 * Attaches the request id to forms and links using a pattern match and replace algorithm
 */
final class ResponseFilterPatternStrategy implements ResponseFilterStrategyInterface
{
    public function __construct(
        private Stringable $token,
        private StreamFactoryInterface $streamFactory,
        private string $requestId,
    ) {
    }

    public function algorithm(
        ResponseInterface $response,
    ): ResponseInterface {
        $contents = $response->getBody()->getContents();
        $writeStream = $this->streamFactory->createStream();
        $result = preg_replace(
            [
                "/<form(.*?)>(.*?)<\/form>/s",
                "/<a href=(.*?)\?(.*?)>(.*?)<\/a>/s",
            ],
            [
                sprintf(
                    "<form$1>$2%s    <input type=\"hidden\" name=\"%s\" value=\"%s\" />%s</form>",
                    PHP_EOL,
                    $this->requestId,
                    (string) $this->token,
                    PHP_EOL,
                ),
                sprintf(
                    "<a href=$1?%s=%s&$2>$3</a>",
                    $this->requestId,
                    urlencode(
                        (string) $this->token,
                    ),
                )
            ],
            $contents,
        );
        $writeStream->write($result ?? "");
        $writeStream->rewind();
        return $response->withBody($writeStream);
    }
}
