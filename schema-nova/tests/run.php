<?php
/**
 * Tiny assertion runner: php tests/run.php
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$failed = 0;
$passed = 0;

function expect($cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "  ok  {$msg}\n";
        return;
    }
    $failed++;
    echo "  FAIL  {$msg}\n";
}

echo "Text\n";
expect(\SchemaNova\Support\Text::clean('<b> سلام   دنیا </b>') === 'سلام دنیا', 'strips tags and collapses space');
expect(\SchemaNova\Support\Text::len('آزمایش') === 6, 'mb length');
expect(\SchemaNova\Support\Text::overlap('خرید لپ تاپ', 'راهنمای خرید لپ تاپ گیمینگ') >= 0.5, 'token overlap');

echo "Arr compact\n";
$c = \SchemaNova\Support\Arr::compact(['a' => 'x', 'b' => '', 'c' => null, 'd' => ['e' => '']]);
expect($c === ['a' => 'x'], 'drops empty nested values');

echo "Intent\n";
expect(\SchemaNova\Optimizer\Intent::classify('خرید گوشی سامسونگ') === \SchemaNova\Optimizer\Intent::TRANSACTIONAL, 'buy intent');
expect(\SchemaNova\Optimizer\Intent::classify('بهترین لپ تاپ ۲۰۲۵') === \SchemaNova\Optimizer\Intent::COMMERCIAL, 'commercial intent');
expect(\SchemaNova\Optimizer\Intent::classify('json ld چیست') === \SchemaNova\Optimizer\Intent::INFORMATIONAL, 'informational intent');
expect(\SchemaNova\Optimizer\Intent::classify('Demo Shop', 'Demo Shop') === \SchemaNova\Optimizer\Intent::NAVIGATIONAL, 'brand navigational');

echo "CTR model\n";
expect(\SchemaNova\Optimizer\CtrModel::expected(1) === 0.28, 'position 1 CTR');
expect(\SchemaNova\Optimizer\CtrModel::gap(0.05, 1) > 0.7, 'large gap at #1 with 5% CTR');
expect(\SchemaNova\Optimizer\CtrModel::gap(0.28, 1) === 0.0, 'no gap when matching expected');

echo "Title builder\n";
$proposal = \SchemaNova\Optimizer\TitleBuilder::propose(
    'مقاله قدیمی',
    'راهنمای کامل خرید لپ تاپ گیمینگ برای کار و بازی با بررسی قیمت و مدل‌ها',
    [[
        'query'       => 'خرید لپ تاپ گیمینگ',
        'impressions' => 800,
        'clicks'      => 12,
        'ctr'         => 0.015,
        'position'    => 4.2,
        'intent'      => 'transactional',
    ]],
    'Demo Shop',
    'fa-IR',
    true,
    ' | ',
    0.2
);
expect(is_array($proposal), 'proposal returned');
expect(isset($proposal['title']) && str_contains($proposal['title'], 'لپ تاپ'), 'title keeps query');
expect(isset($proposal['description']) && $proposal['description'] !== '', 'description filled');
expect(($proposal['intent'] ?? '') === 'transactional', 'intent preserved');

$skip = \SchemaNova\Optimizer\TitleBuilder::propose(
    'Something unrelated about cooking pasta',
    'pasta recipes tomato sauce italian cooking',
    [[
        'query'       => 'buy airplane tickets cheap',
        'impressions' => 900,
        'clicks'      => 2,
        'ctr'         => 0.002,
        'position'    => 8,
        'intent'      => 'transactional',
    ]],
    'Brand',
    'en',
    true,
    ' | ',
    0.4
);
expect($skip === null, 'rejects query that does not overlap page text');

echo "Normalizer\n";
$row = \SchemaNova\Connectors\Normalizer::row([
    'page' => 'https://example.com/a/',
    'query' => 'خرید گوشی',
    'clicks' => 10,
    'impressions' => 200,
    'ctr' => 5,
    'position' => 3.1,
], 'Demo');
expect(is_array($row), 'normalizes row');
expect($row['ctr'] === 0.05, 'percent CTR converted to ratio');
expect($row['intent'] === 'transactional', 'intent classified while normalizing');
expect(\SchemaNova\Connectors\Normalizer::path(['data' => ['rows' => [1]]], 'data.rows.0') === 1, 'dotted path');

echo "Validator\n";
$issues = \SchemaNova\Schema\Validator::check([
    '@graph' => [
        ['@type' => 'Article', 'headline' => 'Hi'],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem']]],
        ['@type' => 'Product', 'name' => 'Phone', 'image' => 'https://x/y.jpg', 'offers' => ['price' => '1']],
    ],
]);
$fields = array_column($issues, 'field');
expect(in_array('image', $fields, true), 'article missing image warned/errored');
expect(in_array('itemListElement', $fields, true), 'breadcrumb too short is error');
$score = \SchemaNova\Schema\Validator::score($issues);
expect($score < 100 && $score >= 0, 'score in range');

$clean = \SchemaNova\Schema\Validator::check([
    '@graph' => [[
        '@type' => 'WebSite',
        'name' => 'Demo',
        'url' => 'https://example.com/',
        'publisher' => ['@id' => '#org'],
        'inLanguage' => 'fa-IR',
        'potentialAction' => ['@type' => 'SearchAction'],
    ]],
]);
expect($clean === [], 'complete WebSite has no issues');

echo "Node dates\n";
$iso = \SchemaNova\Schema\Node::isoDate('2024-01-05 08:00:00', 'UTC');
expect(str_starts_with($iso, '2024-01-05T'), 'iso date');

echo "JSON encode\n";
$json = \SchemaNova\Schema\Graph::encode(['@context' => 'https://schema.org', '@graph' => [['@type' => 'Thing', 'name' => 'سلام']]]);
expect(str_contains($json, 'سلام'), 'unescaped unicode');
expect(! str_contains($json, '\/'), 'unescaped slashes');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
