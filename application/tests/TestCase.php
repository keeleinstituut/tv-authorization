<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function assertResponseJsonDataEqualsIgnoringOrder(array $expectedData, TestResponse $actualResponse): void
    {
        $this->assertArraysEqualIgnoringOrder($expectedData, $actualResponse->json('data'));
    }

    public function assertArrayHasSubsetIgnoringOrder(?array $expectedSubset, ?array $actual): void
    {
        $this->assertNotNull($expectedSubset);
        $this->assertNotNull($actual);

        $sortedDottedExpectedSubset = Arr::dot(Arr::sortRecursive($expectedSubset));
        $sortedDottedActualWholeArray = Arr::dot(Arr::sortRecursive($actual));
        $sortedDottedActualSubset = Arr::only($sortedDottedActualWholeArray, array_keys($sortedDottedExpectedSubset));

        $this->assertArraysEqualIgnoringOrder($sortedDottedExpectedSubset, $sortedDottedActualSubset);
    }

    public function assertArraysEqualIgnoringOrder(?array $expected, ?array $actual): void
    {
        $this->assertNotNull($expected);
        $this->assertNotNull($actual);

        $this->assertEquals(
            Arr::sortRecursive($expected),
            Arr::sortRecursive($actual)
        );
    }
}
