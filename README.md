<div class="filament-hidden">

![Laravel Metrics Fathom](https://raw.githubusercontent.com/jeffersongoncalves/laravel-metrics-fathom/main/art/jeffersongoncalves-laravel-metrics-fathom.png)

</div>

# Laravel Metrics Fathom

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-metrics-fathom.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-metrics-fathom)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-metrics-fathom/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-metrics-fathom/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-metrics-fathom/phpstan.yml?branch=main&label=PHPStan&style=flat-square)](https://github.com/jeffersongoncalves/laravel-metrics-fathom/actions?query=workflow%3APHPStan+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-metrics-fathom.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-metrics-fathom)

Laravel package to interact with the [Fathom Analytics](https://usefathom.com) API. Fetch pageviews, visitors, events, milestones, and generate custom aggregation reports.

Settings are stored in the database via [spatie/laravel-settings](https://github.com/spatie/laravel-settings) — no config files needed.

## Installation

```bash
composer require jeffersongoncalves/laravel-metrics-fathom
```

Run migrations to create the settings:

```bash
php artisan migrate
```

## Configuration

After migration, the settings are seeded from environment variables:

```env
FATHOM_API_TOKEN=your-api-token
FATHOM_SITE_ID=your-site-id
FATHOM_BASE_URL=https://api.usefathom.com/v1
FATHOM_TIMEZONE=UTC
```

You can also update settings programmatically:

```php
use JeffersonGoncalves\MetricsFathom\Settings\FathomSettings;

$settings = app(FathomSettings::class);
$settings->api_token = 'new-token';
$settings->site_id = 'NEWSITE';
$settings->timezone = 'America/Sao_Paulo';
$settings->save();
```

## Usage

### Using the Facade

```php
use JeffersonGoncalves\MetricsFathom\Facades\Fathom;
```

### Account

```php
$account = Fathom::account();
```

### Sites

```php
// List sites
$result = Fathom::sites(limit: 20);
foreach ($result['data'] as $site) {
    echo $site->id . ' - ' . $site->name;
}

// Get a site
$site = Fathom::site('SITEID');

// Create a site
use JeffersonGoncalves\MetricsFathom\Enums\Sharing;
$site = Fathom::createSite('My Site', Sharing::Public);

// Update a site
$site = Fathom::updateSite('SITEID', name: 'New Name');

// Delete a site
Fathom::deleteSite('SITEID');
```

### Events

```php
// List events
$result = Fathom::events();

// Create an event
$event = Fathom::createEvent('signup');

// Get an event
$event = Fathom::event('EVENTID');

// Update an event
$event = Fathom::updateEvent('EVENTID', 'new-name');

// Delete an event
Fathom::deleteEvent('EVENTID');
```

### Milestones

```php
// List milestones
$result = Fathom::milestones();

// Create a milestone
$milestone = Fathom::createMilestone('v2.0 Launch', '2026-03-01');

// Update a milestone
$milestone = Fathom::updateMilestone('MSID', name: 'v2.1 Launch');

// Delete a milestone
Fathom::deleteMilestone('MSID');
```

### Current Visitors

```php
// Simple count
$visitors = Fathom::currentVisitors();
echo $visitors->total; // 42

// Detailed (top pages & referrers)
$visitors = Fathom::currentVisitors(detailed: true);
```

### Aggregations (Custom Reports)

Build flexible reports using the fluent query builder:

```php
use JeffersonGoncalves\MetricsFathom\Enums\Aggregate;
use JeffersonGoncalves\MetricsFathom\Enums\DateGrouping;
use JeffersonGoncalves\MetricsFathom\Enums\FieldGrouping;
use JeffersonGoncalves\MetricsFathom\Enums\FilterOperator;

// Pageviews per day for the last month
$query = Fathom::query()
    ->aggregate(Aggregate::Visits, Aggregate::Pageviews, Aggregate::BounceRate)
    ->groupByDate(DateGrouping::Day)
    ->from('2026-02-01 00:00:00')
    ->to('2026-02-28 23:59:59');

$result = Fathom::aggregate($query);

// Top pages by visits
$query = Fathom::query()
    ->aggregate(Aggregate::Visits)
    ->groupByField(FieldGrouping::Pathname)
    ->sortBy('visits', 'desc')
    ->limit(10);

$result = Fathom::aggregate($query);

// Visitors by country
$query = Fathom::query()
    ->aggregate(Aggregate::Uniques)
    ->groupByField(FieldGrouping::CountryCode)
    ->sortBy('uniques', 'desc');

$result = Fathom::aggregate($query);

// Filter by UTM source
$query = Fathom::query()
    ->aggregate(Aggregate::Visits, Aggregate::Uniques)
    ->where(FieldGrouping::UtmSource, FilterOperator::Is, 'twitter')
    ->from('2026-01-01 00:00:00');

$result = Fathom::aggregate($query);

// Event conversions
$query = Fathom::queryEvents()
    ->aggregate(Aggregate::Conversions, Aggregate::UniqueConversions)
    ->forSite('SITEID')
    ->forEvent('signup')
    ->groupByDate(DateGrouping::Month);

$result = Fathom::aggregate($query);
```

### Using DateTime Objects

```php
use Carbon\Carbon;

$query = Fathom::query()
    ->aggregate(Aggregate::Visits)
    ->between(Carbon::now()->subDays(30), Carbon::now());

$result = Fathom::aggregate($query);
```

## Available Enums

### Aggregate
`Visits`, `Uniques`, `Pageviews`, `AvgDuration`, `BounceRate`, `Conversions`, `UniqueConversions`, `Value`

### DateGrouping
`Hour`, `Day`, `Month`, `Year`

### FieldGrouping
`Hostname`, `Pathname`, `ReferrerHostname`, `Referrer`, `Browser`, `BrowserVersion`, `CountryCode`, `City`, `DeviceType`, `OperatingSystem`, `OperatingSystemVersion`, `UtmSource`, `UtmMedium`, `UtmCampaign`, `UtmContent`, `UtmTerm`

### FilterOperator
`Is`, `IsNot`, `IsLike`, `IsNotLike`, `Matching`, `NotMatching`

### Sharing
`None`, `Private`, `Public`

## API Rate Limits

- **Sites & Events**: 2,000 requests/hour
- **Aggregations & Current Visitors**: 10 requests/minute

## Testing

```bash
composer test
```

## Code Style

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
