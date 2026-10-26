<?php

declare(strict_types=1);

/**
 * Allowlist for analytics dwell targets — keep in sync with src/data/analyticsInterestCatalog.ts
 *
 * @return list<string>
 */
function moun_analytics_section_ids(): array
{
    return [
        'landing',
        'overview',
        'whitepaper',
        'market',
        'features',
        'brand',
        'ecoOverview',
        'ecoBuild',
        'ecoCobra',
        'proOverview',
        'hobbyist',
        'hobbyistMfg',
        'mechanical',
        'electrical',
        'software',
        'manufacturing',
        'investorOverview',
        'investorsGuide',
        'curatorOverview',
    ];
}

/**
 * Interest image ids (format img:/path/to/file.png). Optional match: prefix for path families.
 *
 * @return list<array{id:string,match?:string}>
 */
function moun_analytics_interest_image_defs(): array
{
    return [
        ['id' => 'img:/brand/moun-logo-carbon-hero.png'],
        ['id' => 'img:/brand/moun-flagship-rig-hero.png'],
        ['id' => 'img:/market-research/moun-market-margins-hero.png'],
        ['id' => 'img:/market-research/moun-market-margin-analysis.png'],
        ['id' => 'img:/investors/moun-e-hero-product-desk-shelf.png'],
        ['id' => 'img:/investors/moun-e-sales-assembly-4panel.png'],
        ['id' => 'img:/investors/moun-e-sales-ecosystem-4panel.png'],
        ['id' => 'img:/investors/moun-e-sales-lifestyle-evaluation.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/01-phone-bench-usb-adapter.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/02-pillar-usb-low-view.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/03-angled-phone-bench-adapter.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/04-workplane-header-leds-tabs.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/05-exploded-assembly-red-bench.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/06-exploded-l-bench-usb.png'],
        ['id' => 'img:/hobbyist-eco/moun-e-cobra/07-assembly-unbox-power-storyboard.png'],
    ];
}

function moun_analytics_image_id_allowed(string $targetKey): bool
{
    foreach (moun_analytics_interest_image_defs() as $def) {
        $id = $def['id'];
        if (($def['match'] ?? '') === 'prefix') {
            if (str_starts_with($targetKey, $id)) {
                return true;
            }
        } elseif ($targetKey === $id) {
            return true;
        }
    }
    return false;
}

function moun_analytics_target_allowed(string $kind, string $targetKey, ?string $sectionContext): bool
{
    if ($kind === 'section') {
        return in_array($targetKey, moun_analytics_section_ids(), true);
    }
    if ($kind === 'image') {
        if ($sectionContext !== null && $sectionContext !== '' && !in_array($sectionContext, moun_analytics_section_ids(), true)) {
            return false;
        }
        return moun_analytics_image_id_allowed($targetKey);
    }
    return false;
}
