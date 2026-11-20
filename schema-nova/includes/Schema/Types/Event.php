<?php
/**
 * Event markup from per-page overrides.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Event
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function maybe(Context $ctx): array
    {
        $o = $ctx->overrides;
        if (($o['schema_type'] ?? '') !== 'Event' && empty($o['event_name']) && empty($o['start_date'])) {
            return [];
        }
        if (empty($o['event_name']) && empty($o['start_date'])) {
            return [];
        }

        $locationName = (string) ($o['event_location'] ?? '');
        $locationUrl  = (string) ($o['event_location_url'] ?? '');
        $isOnline     = ! empty($o['event_online']);

        $location = $isOnline
            ? [
                '@type' => 'VirtualLocation',
                'url'   => $locationUrl !== '' ? $locationUrl : $ctx->url,
            ]
            : [
                '@type'   => 'Place',
                'name'    => $locationName !== '' ? $locationName : get_bloginfo('name'),
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressCountry'  => (string) $ctx->settings->get('organization.address.country', 'IR'),
                    'addressLocality' => (string) ($o['event_city'] ?? $ctx->settings->get('organization.address.locality', '')),
                    'streetAddress'   => (string) ($o['event_street'] ?? ''),
                ],
            ];

        $node = Node::make('Event', $ctx->entityId('event'), [
            'name'                => Text::clean((string) ($o['event_name'] ?? $ctx->title)),
            'description'         => Text::clean((string) ($o['event_description'] ?? $ctx->description), 500),
            'startDate'           => (string) ($o['start_date'] ?? ''),
            'endDate'             => (string) ($o['end_date'] ?? ''),
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $isOnline
                ? 'https://schema.org/OnlineEventAttendanceMode'
                : 'https://schema.org/OfflineEventAttendanceMode',
            'location'            => $location,
            'image'               => Images::urls($ctx->images),
            'organizer'           => Node::ref($ctx->orgId()),
            'offers'              => ! empty($o['price']) ? [
                '@type'         => 'Offer',
                'price'         => (string) $o['price'],
                'priceCurrency' => (string) ($o['currency'] ?? 'IRR'),
                'url'           => $ctx->url,
                'availability'  => 'https://schema.org/InStock',
            ] : '',
        ]);

        return [$node];
    }
}
