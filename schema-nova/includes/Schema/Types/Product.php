<?php
/**
 * Product snippets + merchant listing fields (Google Search Central).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Product
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        if (function_exists('wc_get_product') && $ctx->postId) {
            $wc = wc_get_product($ctx->postId);
            if ($wc) {
                return self::fromWoo($ctx, $wc);
            }
        }

        return self::fromOverrides($ctx);
    }

    /**
     * @param mixed $product WC_Product
     * @return array<string, mixed>
     */
    private static function fromWoo(Context $ctx, $product): array
    {
        $images = [];
        $imageId = (int) $product->get_image_id();
        if ($imageId) {
            $src = wp_get_attachment_image_url($imageId, 'full');
            if ($src) {
                $images[] = $src;
            }
        }
        foreach ($product->get_gallery_image_ids() as $gid) {
            $src = wp_get_attachment_image_url((int) $gid, 'full');
            if ($src) {
                $images[] = $src;
            }
        }
        if ($images === []) {
            $images = Images::urls($ctx->images);
        }

        $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'IRR';
        $price    = $product->get_price();
        $avail    = $product->is_in_stock()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        if ($product->is_on_backorder()) {
            $avail = 'https://schema.org/BackOrder';
        }

        $brandName = '';
        if ($product->get_attribute('brand')) {
            $brandName = wp_strip_all_tags((string) $product->get_attribute('brand'));
        } elseif ($product->get_attribute('pa_brand')) {
            $brandName = wp_strip_all_tags((string) $product->get_attribute('pa_brand'));
        }

        $gtin = (string) $product->get_meta('_gtin', true);
        if ($gtin === '') {
            $gtin = (string) $product->get_global_unique_id();
        }

        $offer = [
            '@type'           => 'Offer',
            'url'             => get_permalink($ctx->postId),
            'priceCurrency'   => $currency,
            'price'           => $price !== '' ? (string) $price : '',
            'availability'    => $avail,
            'itemCondition'   => 'https://schema.org/NewCondition',
            'seller'          => Node::ref($ctx->orgId()),
            'priceValidUntil' => gmdate('Y-12-31'),
        ];

        $org = $ctx->settings->get('organization', []);
        if (is_array($org) && (int) ($org['return_days'] ?? 0) > 0) {
            $offer['hasMerchantReturnPolicy'] = [
                '@id' => $ctx->orgId() . '/return-policy',
            ];
        }

        $shippingRate = (string) ($org['shipping_rate'] ?? '');
        if ($shippingRate !== '') {
            $offer['shippingDetails'] = [
                '@type'               => 'OfferShippingDetails',
                'shippingRate'        => [
                    '@type'    => 'MonetaryAmount',
                    'value'    => $shippingRate,
                    'currency' => (string) ($org['shipping_currency'] ?? $currency),
                ],
                'shippingDestination' => [
                    '@type'          => 'DefinedRegion',
                    'addressCountry' => (string) ($org['address']['country'] ?? 'IR'),
                ],
                'deliveryTime'        => [
                    '@type'        => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 1,
                        'maxValue' => 3,
                        'unitCode'=> 'DAY',
                    ],
                    'transitTime'  => [
                        '@type'    => 'QuantitativeValue',
                        'minValue' => 1,
                        'maxValue' => 7,
                        'unitCode'=> 'DAY',
                    ],
                ],
            ];
        }

        $rating = self::rating($ctx->postId);

        return Node::make('Product', $ctx->entityId('product'), [
            'name'            => Text::clean($product->get_name()),
            'description'     => Text::clean($product->get_short_description() ?: $product->get_description(), 5000),
            'sku'             => (string) $product->get_sku(),
            'mpn'             => (string) $product->get_meta('_mpn', true),
            'gtin'            => $gtin,
            'image'           => $images,
            'brand'           => $brandName !== '' ? ['@type' => 'Brand', 'name' => $brandName] : '',
            'category'        => self::wooCategory($ctx->postId),
            'offers'          => $offer,
            'aggregateRating' => $rating,
            'mainEntityOfPage'=> Node::ref($ctx->pageId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromOverrides(Context $ctx): array
    {
        $o = $ctx->overrides;
        $name = (string) ($o['product_name'] ?? $ctx->title);
        $price = (string) ($o['price'] ?? '');
        $currency = (string) ($o['currency'] ?? 'IRR');
        $sku = (string) ($o['sku'] ?? '');
        $brand = (string) ($o['brand'] ?? '');
        $avail = (string) ($o['availability'] ?? 'https://schema.org/InStock');
        $images = Images::urls($ctx->images);

        $offer = [];
        if ($price !== '') {
            $offer = [
                '@type'         => 'Offer',
                'url'           => $ctx->url,
                'price'         => $price,
                'priceCurrency' => $currency,
                'availability'  => $avail,
                'seller'        => Node::ref($ctx->orgId()),
            ];
        }

        return Node::make('Product', $ctx->entityId('product'), [
            'name'            => Text::clean($name),
            'description'     => $ctx->description,
            'sku'             => $sku,
            'image'           => $images,
            'brand'           => $brand !== '' ? ['@type' => 'Brand', 'name' => $brand] : '',
            'offers'          => $offer,
            'mainEntityOfPage'=> Node::ref($ctx->pageId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rating(int $postId): array
    {
        if (! function_exists('wc_get_product')) {
            return [];
        }
        $count = (int) get_post_meta($postId, '_wc_review_count', true);
        $avg   = (float) get_post_meta($postId, '_wc_average_rating', true);
        if ($count < 1 || $avg <= 0) {
            return [];
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => $avg,
            'reviewCount' => $count,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }

    private static function wooCategory(int $postId): string
    {
        $terms = get_the_terms($postId, 'product_cat');
        if (! is_array($terms) || $terms === [] || is_wp_error($terms)) {
            return '';
        }

        return $terms[0]->name;
    }
}
