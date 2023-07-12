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

    public function assertArrayHasSpecifiedFragmentIgnoringOrder(?array $expectedFragment, ?array $actual): void
    {
        $this->assertNotNull($expectedFragment);
        $this->assertNotNull($actual);

        $actualFragment = Arr::only($actual, array_keys($expectedFragment));
        $this->assertArraysEqualIgnoringOrder($expectedFragment, $actualFragment);
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
