<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Redis Mocking
|--------------------------------------------------------------------------
*/
uses()->beforeEach(function () {
    \Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturn(null)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('set')->andReturn(true)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('incr')->andReturn(1)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('decr')->andReturn(0)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('expire')->andReturn(true)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('zremrangebyscore')->andReturn(0)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('zcard')->andReturn(0)->byDefault();
    \Illuminate\Support\Facades\Redis::shouldReceive('del')->andReturn(1)->byDefault();
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
