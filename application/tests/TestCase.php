<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function assertResponseJsonDataEqualsIgnoringOrder(array $expectedData, TestResponse $actualResponse): void
    {
        $this->assertEqualAsJsonIgnoringOrder($expectedData, $actualResponse->json('data'));
    }

    public function assertArrayHasSpecifiedFragmentIgnoringOrder(array $expectedFragment, array $actual): void
    {
        $actualFragment = Arr::only($actual, array_keys($expectedFragment));

        $this->assertEquals(
            Arr::sortRecursive($expectedFragment),
            Arr::sortRecursive($actualFragment)
        );
    }

    public function assertEqualAsJsonIgnoringOrder(array $expected, array $actual): void
    {
        $assertableJsonString = new AssertableJsonString($actual);
        $assertableJsonString->assertSimilar($expected);
    }
}
