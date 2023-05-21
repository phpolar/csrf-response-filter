<?php

declare(strict_types=1);

namespace Phpolar\CsrfResponseFilter\Http\Message;

use Phpolar\HttpMessageTestUtils\ResponseFactoryStub;
use Phpolar\HttpMessageTestUtils\StreamFactoryStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Stringable;

#[CoversClass(CsrfResponseFilter::class)]
final class CsrfResponseFilterTest extends TestCase
{
    private StreamInterface $stream;

    public function tearDown(): void
    {
        if (isset($this->stream) === true) {
            $this->stream->close();
        }
    }

    #[TestDox("Shall attach a CSRF token to a form")]
    public function testa()
    {
        $givenTokenAsString = uniqid();
        $givenRequestId = uniqid();
        $givenToken = new class ($givenTokenAsString) implements Stringable {
            public function __construct(private string $strVal)
            {
            }
            public function __toString(): string
            {
                return $this->strVal;
            }
        };

        $streamFactory = new StreamFactoryStub("w+");
        $responseFactory = new ResponseFactoryStub();
        $strategy = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
        $sut = new CsrfResponseFilter($strategy);
        $forms = <<<HTML
        <form action="somewhere" method="post"></form>
        <form></form>
        <form></form>
        HTML;
        $expected = <<<HTML
        <form action="somewhere" method="post">
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        <form>
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        <form>
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        HTML;
        $this->stream = $streamFactory->createStream($forms);
        $response = $responseFactory->createResponse();
        $filteredResponse = $sut->filter($response->withBody($this->stream));
        $actual = $filteredResponse->getBody()->getContents();
        $this->assertSame($expected, $actual);
    }
}
