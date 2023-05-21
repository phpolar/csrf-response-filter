<?php

declare(strict_types=1);

namespace Phpolar\CsrfResponseFilter\Http\Message;

use Generator;
use Phpolar\HttpMessageTestUtils\ResponseFactoryStub;
use Phpolar\HttpMessageTestUtils\StreamFactoryStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Stringable;

#[CoversClass(ResponseFilterPatternStrategy::class)]
final class ResponseFilterStrategyTest extends TestCase
{
    private StreamInterface $stream;

    public function tearDown(): void
    {
        if (isset($this->stream) === true) {
            $this->stream->close();
        }
    }

    public static function scenarios(): Generator
    {
        $givenTokenAsString = uniqid();
        $givenRequestId = uniqid();
        $givenToken = new class($givenTokenAsString) implements Stringable {
            public function __construct(private string $strVal)
            {
            }
            public function __toString(): string
            {
                return $this->strVal;
            }
        };
        yield [
            $givenTokenAsString,
            $givenRequestId,
            $givenToken,
        ];
    }

    #[Test]
    #[TestDox("Shall attach a request id to each form when a session is active")]
    #[DataProvider("scenarios")]
    public function forms(string $givenTokenAsString, string $givenRequestId, Stringable $givenToken)
    {
        $responseFactory = new ResponseFactoryStub();
        $streamFactory = new StreamFactoryStub("w+");
        $sut = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
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
        $responseWithFormKeys = $sut->algorithm($response->withBody($this->stream));
        $actual = $responseWithFormKeys->getBody()->getContents();
        $this->assertSame($expected, $actual);
    }

    #[Test]
    #[TestDox("Shall not attach a request id when there are no forms")]
    #[DataProvider("scenarios")]
    public function noop(string $givenTokenAsString, string $givenRequestId, Stringable $givenToken)
    {
        $responseFactory = new ResponseFactoryStub();
        $streamFactory = new StreamFactoryStub("w+");
        $sut = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
        $forms = <<<HTML
        <p></p>
        <p></p>
        <p></p>
        HTML;
        $expected = <<<HTML
        <p></p>
        <p></p>
        <p></p>
        HTML;
        $this->stream = $streamFactory->createStream($forms);
        $response = $responseFactory->createResponse();
        $responseWithFormKeys = $sut->algorithm($response->withBody($this->stream));
        $actual = $responseWithFormKeys->getBody()->getContents();
        $this->assertSame($expected, $actual);
    }

    #[Test]
    #[TestDox("Shall attach a request id to all links")]
    #[DataProvider("scenarios")]
    public function links(string $givenTokenAsString, string $givenRequestId, Stringable $givenToken)
    {
        $tokenForUri = urlencode($givenTokenAsString);
        $responseFactory = new ResponseFactoryStub();
        $streamFactory = new StreamFactoryStub("w+");
        $sut = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
        $links = <<<HTML
        <a href="http://somewhere.com?action=doSomething">some text</a>
        <a href="http://somewhere.com?action=doSomething">some text</a>
        <a href="http://somewhere.com?action=doSomething">some text</a>
        HTML;
        $expected = <<<HTML
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        HTML;
        $this->stream = $streamFactory->createStream($links);
        $response = $responseFactory->createResponse();
        $responseWithFormKeys = $sut->algorithm($response->withBody($this->stream));
        $actual = $responseWithFormKeys->getBody()->getContents();
        $this->assertSame($expected, $actual);
    }

    #[Test]
    #[TestDox("Shall attach a request id to all links and forms")]
    #[DataProvider("scenarios")]
    public function linksAndForms(string $givenTokenAsString, string $givenRequestId, Stringable $givenToken)
    {
        $tokenForUri = urlencode($givenTokenAsString);
        $responseFactory = new ResponseFactoryStub();
        $streamFactory = new StreamFactoryStub("w+");
        $sut = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
        $links = <<<HTML
        <a href="http://somewhere.com?action=doSomething">some text</a>
        <form action="somewhere" method="post"></form>
        <a href="http://somewhere.com?action=doSomething">some text</a>
        <form></form>
        <a href="http://somewhere.com?action=doSomething">some text</a>
        <form></form>
        HTML;
        $expected = <<<HTML
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        <form action="somewhere" method="post">
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        <form>
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        <a href="http://somewhere.com?{$givenRequestId}={$tokenForUri}&action=doSomething">some text</a>
        <form>
            <input type="hidden" name="{$givenRequestId}" value="{$givenTokenAsString}" />
        </form>
        HTML;
        $this->stream = $streamFactory->createStream($links);
        $response = $responseFactory->createResponse();
        $responseWithFormKeys = $sut->algorithm($response->withBody($this->stream));
        $actual = $responseWithFormKeys->getBody()->getContents();
        $this->assertSame($expected, $actual);
    }
}
