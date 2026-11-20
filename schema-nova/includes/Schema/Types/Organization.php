<?php
/**
 * Organization / OnlineStore / LocalBusiness / NewsMediaOrganization.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;
use SchemaNova\Support\Url;

final class Organization
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $org  = $ctx->settings->get('organization', []);
        $org  = is_array($org) ? $org : [];
        $type = (string) ($org['type'] ?? 'Organization');
        $allowed = [
            'Organization', 'OnlineStore', 'OnlineBusiness', 'LocalBusiness',
            'NewsMediaOrganization', 'Store', 'Restaurant', 'Corporation',
            'EducationalOrganization', 'NGO', 'MedicalOrganization',
        ];
        if (! in_array($type, $allowed, true)) {
            $type = 'Organization';
        }

        $sameAs = $org['same_as'] ?? [];
        if (is_string($sameAs)) {
            $sameAs = preg_split('/\s+/', $sameAs) ?: [];
        }
        $sameAs = array_values(array_filter(array_map('trim', (array) $sameAs)));

        $address = $org['address'] ?? [];
        $address = is_array($address) ? $address : [];
        $postal  = [];
        if (array_filter($address)) {
            $postal = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $address['street'] ?? '',
                'addressLocality' => $address['locality'] ?? '',
                'addressRegion'   => $address['region'] ?? '',
                'postalCode'      => $address['postal'] ?? '',
                'addressCountry'  => $address['country'] ?? '',
            ];
        }

        $geo = [];
        $lat = (string) ($org['geo']['lat'] ?? '');
        $lng = (string) ($org['geo']['lng'] ?? '');
        if ($lat !== '' && $lng !== '') {
            $geo = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $lat,
                'longitude' => $lng,
            ];
        }

        $hours = [];
        foreach ((array) ($org['opening_hours'] ?? []) as $row) {
            if (! is_array($row) || empty($row['day'])) {
                continue;
            }
            $hours[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $row['day'],
                'opens'     => $row['opens'] ?? '09:00',
                'closes'    => $row['closes'] ?? '18:00',
            ];
        }

        $logo = Images::logo();
        $logoNode = $logo !== '' ? [
            '@type'      => 'ImageObject',
            '@id'        => Url::id($ctx->home, 'logo'),
            'url'        => $logo,
            'contentUrl' => $logo,
        ] : [];

        $data = [
            'name'          => Text::clean((string) ($org['name'] ?? get_bloginfo('name'))),
            'legalName'     => Text::clean((string) ($org['legal_name'] ?? '')),
            'alternateName' => Text::clean((string) ($org['alternate_name'] ?? '')),
            'description'   => Text::clean((string) ($org['description'] ?? ''), 500),
            'url'           => (string) ($org['url'] ?? $ctx->home),
            'logo'          => $logoNode,
            'image'         => $logo,
            'email'         => (string) ($org['email'] ?? ''),
            'telephone'     => (string) ($org['telephone'] ?? ''),
            'foundingDate'  => (string) ($org['founding_date'] ?? ''),
            'vatID'         => (string) ($org['vat_id'] ?? ''),
            'taxID'         => (string) ($org['tax_id'] ?? ''),
            'iso6523Code'   => (string) ($org['iso6523'] ?? ''),
            'naics'         => (string) ($org['naics'] ?? ''),
            'sameAs'        => $sameAs,
            'address'       => $postal,
            'geo'           => $geo,
            'openingHoursSpecification' => $hours,
            'priceRange'    => (string) ($org['price_range'] ?? ''),
            'areaServed'    => (string) ($org['area_served'] ?? ''),
            'contactPoint'  => array_filter([
                '@type'       => 'ContactPoint',
                'contactType' => (string) ($org['contact_type'] ?? 'customer service'),
                'email'       => (string) ($org['email'] ?? ''),
                'telephone'   => (string) ($org['telephone'] ?? ''),
                'areaServed'  => (string) ($org['area_served'] ?? ''),
                'availableLanguage' => ['fa', 'en'],
            ]),
        ];

        if (in_array($type, ['OnlineStore', 'OnlineBusiness', 'Store'], true)) {
            $days = (int) ($org['return_days'] ?? 0);
            if ($days > 0) {
                $data['hasMerchantReturnPolicy'] = [
                    '@type'                 => 'MerchantReturnPolicy',
                    'applicableCountry'     => (string) ($address['country'] ?? 'IR'),
                    'returnPolicyCountry'   => (string) ($address['country'] ?? 'IR'),
                    'returnPolicyCategory'  => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays'    => $days,
                    'returnMethod'          => 'https://schema.org/ReturnByMail',
                    'returnFees'            => (string) ($org['return_fees'] ?? 'https://schema.org/FreeReturn'),
                    'refundType'            => 'https://schema.org/FullRefund',
                ];
            }
            $rate = (string) ($org['shipping_rate'] ?? '');
            if ($rate !== '') {
                $data['hasShippingService'] = [
                    '@type'              => 'ShippingService',
                    'name'               => 'Standard shipping',
                    'fulfillmentType'    => 'FulfillmentTypeDelivery',
                    'shippingConditions' => [[
                        '@type'                => 'ShippingConditions',
                        'shippingDestination'  => [
                            '@type'          => 'DefinedRegion',
                            'addressCountry' => (string) ($address['country'] ?? 'IR'),
                        ],
                        'shippingRate'         => [
                            '@type'    => 'MonetaryAmount',
                            'value'    => $rate,
                            'currency' => (string) ($org['shipping_currency'] ?? 'IRR'),
                        ],
                    ]],
                ];
            }
        }

        return Node::make($type, $ctx->orgId(), $data);
    }
}
