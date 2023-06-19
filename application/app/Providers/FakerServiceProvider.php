<?php

namespace App\Providers;

use App\Faker\EstonianPicFaker;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class FakerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(Generator::class, function (Generator $generator) {
            $generator->addProvider(new EstonianPicFaker($generator));

            return $generator;
        });
    }
}
