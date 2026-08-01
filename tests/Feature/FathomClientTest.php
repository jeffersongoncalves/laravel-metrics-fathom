<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\MetricsFathom\Enums\Aggregate;
use JeffersonGoncalves\MetricsFathom\Exceptions\AuthenticationException;
use JeffersonGoncalves\MetricsFathom\Exceptions\RateLimitException;
use JeffersonGoncalves\MetricsFathom\Fathom;
use JeffersonGoncalves\MetricsFathom\FathomClient;

it('resolves fathom from container', function () {
    $fathom = app('fathom');

    expect($fathom)->toBeInstanceOf(Fathom::class);
});

it('throws exception when token is empty', function () {
    $client = new FathomClient(token: '', baseUrl: 'https://api.usefathom.com/v1');
    $client->get('account');
})->throws(AuthenticationException::class, 'Fathom API token is not configured');

it('throws exception on 401 response', function () {
    Http::fake([
        'api.usefathom.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $client = new FathomClient(token: 'invalid-token', baseUrl: 'https://api.usefathom.com/v1');
    $client->get('account');
})->throws(AuthenticationException::class);

it('throws exception on 429 response', function () {
    Http::fake([
        'api.usefathom.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
    ]);

    $client = new FathomClient(token: 'test-token', baseUrl: 'https://api.usefathom.com/v1');
    $client->get('account');
})->throws(RateLimitException::class);

it('fetches account info', function () {
    Http::fake([
        'api.usefathom.com/v1/account' => Http::response([
            'id' => '12345',
            'name' => 'Test Account',
            'email' => 'test@example.com',
        ]),
    ]);

    $fathom = app('fathom');
    $account = $fathom->account();

    expect($account)
        ->toHaveKey('id', '12345')
        ->toHaveKey('name', 'Test Account');
});

it('lists sites', function () {
    Http::fake([
        'api.usefathom.com/v1/sites*' => Http::response([
            'data' => [
                ['id' => 'SITE1', 'object' => 'My Site', 'sharing' => 'none'],
                ['id' => 'SITE2', 'object' => 'Other Site', 'sharing' => 'public'],
            ],
            'has_more' => false,
        ]),
    ]);

    $fathom = app('fathom');
    $result = $fathom->sites();

    expect($result['data'])->toHaveCount(2)
        ->and($result['data'][0]->id)->toBe('SITE1')
        ->and($result['data'][1]->id)->toBe('SITE2')
        ->and($result['has_more'])->toBeFalse();
});

it('fetches current visitors', function () {
    Http::fake([
        'api.usefathom.com/v1/current_visitors*' => Http::response([
            'total' => 42,
            'content' => [],
            'referrers' => [],
        ]),
    ]);

    $fathom = app('fathom');
    $visitors = $fathom->currentVisitors();

    expect($visitors->total)->toBe(42);
});

it('runs aggregation query', function () {
    Http::fake([
        'api.usefathom.com/v1/aggregations*' => Http::response([
            [
                'visits' => '1234',
                'pageviews' => '5678',
            ],
        ]),
    ]);

    $fathom = app('fathom');
    $query = $fathom->query()
        ->aggregate(
            Aggregate::Visits,
            Aggregate::Pageviews,
        )
        ->from('2026-01-01 00:00:00')
        ->to('2026-01-31 23:59:59');

    $result = $fathom->aggregate($query);

    expect($result[0])
        ->toHaveKey('visits', '1234')
        ->toHaveKey('pageviews', '5678');
});

it('creates a site', function () {
    Http::fake([
        'api.usefathom.com/v1/sites' => Http::response([
            'id' => 'NEWSITE',
            'object' => 'New Site',
            'sharing' => 'none',
        ]),
    ]);

    $fathom = app('fathom');
    $site = $fathom->createSite('New Site');

    expect($site->id)->toBe('NEWSITE')
        ->and($site->name)->toBe('New Site');
});

it('creates an event', function () {
    Http::fake([
        'api.usefathom.com/v1/sites/TESTSITE/events' => Http::response([
            'id' => 'EVT1',
            'object' => 'signup',
            'site_id' => 'TESTSITE',
        ]),
    ]);

    $fathom = app('fathom');
    $event = $fathom->createEvent('signup');

    expect($event->id)->toBe('EVT1')
        ->and($event->name)->toBe('signup');
});

it('lists milestones', function () {
    Http::fake([
        'api.usefathom.com/v1/sites/TESTSITE/milestones*' => Http::response([
            'data' => [
                ['id' => 'MS1', 'object' => 'Launch', 'site_id' => 'TESTSITE', 'milestone_date' => '2026-01-15'],
            ],
            'has_more' => false,
        ]),
    ]);

    $fathom = app('fathom');
    $result = $fathom->milestones();

    expect($result['data'])->toHaveCount(1)
        ->and($result['data'][0]->name)->toBe('Launch')
        ->and($result['data'][0]->milestoneDate)->toBe('2026-01-15');
});
