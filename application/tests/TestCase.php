<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function assertResponseJsonDataIsEqualTo(array $expectedData, TestResponse $actualResponse): void
    {
        $actualResponse
            ->assertStatus(200)
            ->assertExactJson(['data' => $expectedData]);
    }

    public function assertArrayHasSpecifiedFragment(array $expectedFragment, array $actual): void
    {
        $actualFragment = Arr::only($actual, array_keys($expectedFragment));
        $this->assertEquals($expectedFragment, $actualFragment);
    }

    public function assertEqualAsJsonIgnoringOrderRecursively(array $expected, array $actual): void
    {
        $assertableJsonString = new AssertableJsonString($actual);
        $assertableJsonString->assertSimilar($expected);
    }
}
