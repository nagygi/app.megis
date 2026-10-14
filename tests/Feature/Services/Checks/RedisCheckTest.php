<?php

use App\Enums\CheckStatus;
use App\Services\Checks\RedisCheck;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;

it('passes without connecting when nothing uses Redis', function () {
    $result = app(RedisCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok)
        ->and($result->message)->toContain('Nincs használatban');
});

it('reports an error when Redis is in use but unreachable', function () {
    config(['session.driver' => 'redis']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('ping')->andThrow(new RuntimeException('Connection refused'));
    $redis = Mockery::mock(RedisManager::class);
    $redis->shouldReceive('connection')->andReturn($connection);

    $result = (new RedisCheck($redis))->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toContain('Connection refused');
});
