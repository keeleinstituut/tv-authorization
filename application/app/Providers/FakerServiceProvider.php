<?php

namespace App\Providers;

use App\Faker\EstonianPicFaker;
use Faker\Generator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FakerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->extend(Generator::class, function (Generator $generator) {
            $generator->addProvider(new EstonianPicFaker($generator));

            return $generator;
        });
    }
}
