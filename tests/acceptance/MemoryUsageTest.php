<?php

declare(strict_types=1);

namespace Phpolar\Phpolar;

use Generator;
use Phpolar\CsrfResponseFilter\Http\Message\CsrfResponseFilter;
use Phpolar\CsrfResponseFilter\Http\Message\ResponseFilterPatternStrategy;
use Phpolar\HttpMessageTestUtils\ResponseFactoryStub;
use Phpolar\HttpMessageTestUtils\StreamFactoryStub;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Stringable;

use const Phpolar\Tests\PROJECT_MEMORY_USAGE_THRESHOLD;

#[CoversNothing]
final class MemoryUsageTest extends TestCase
{
    public static function thresholds(): Generator
    {
        yield [(int) PROJECT_MEMORY_USAGE_THRESHOLD];
    }

    #[Test]
    #[TestDox("Memory usage for filtering a response shall be below \$threshold bytes")]
    #[DataProvider("thresholds")]
    public function shallBeBelowThreshold2(int $threshold)
    {
        $totalUsed = -memory_get_usage();
        $this->filterResponse();
        $totalUsed += memory_get_usage();
        $this->assertGreaterThan(0, $totalUsed);
        $this->assertLessThanOrEqual($threshold, $totalUsed);
    }

    private function filterResponse()
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
        $forms = <<<HTML
        <form action="somewhere" method="post"></form>
        <form></form>
        <form></form>
        HTML;
        $streamFactory = new StreamFactoryStub("w+");
        $responseFactory = new ResponseFactoryStub();
        $stream = $streamFactory->createStream($forms);
        $response = $responseFactory->createResponse();
        $strategy = new ResponseFilterPatternStrategy($givenToken, $streamFactory, $givenRequestId);
        $sut = new CsrfResponseFilter($strategy);
        $sut->filter($response->withBody($stream));
    }
}
