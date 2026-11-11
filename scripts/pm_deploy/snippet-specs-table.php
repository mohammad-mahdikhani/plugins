<?php
/**
 * Softer product specification table.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-specs-table">'
			. '#tab-additional_information>h2{margin:0 0 .85rem!important;font-size:1.12rem!important;font-weight:800!important;color:#1e4339!important;line-height:1.4!important;border:0!important}'
			. 'table.woocommerce-product-attributes.shop_attributes{width:100%!important;margin:0!important;border:0!important;border-collapse:separate!important;border-spacing:0 .4rem!important;background:transparent!important}'
			. 'table.woocommerce-product-attributes.shop_attributes th,table.woocommerce-product-attributes.shop_attributes td{border:0!important;padding:.85rem 1rem .85rem 1.15rem!important;background:#e8f6f1!important;font-style:normal!important;vertical-align:middle!important;line-height:1.55!important}'
			. 'table.woocommerce-product-attributes.shop_attributes tr:nth-child(even) th,table.woocommerce-product-attributes.shop_attributes tr:nth-child(even) td{background:#f7fbf9!important}'
			. 'table.woocommerce-product-attributes.shop_attributes th{width:42%!important;color:#1e4339!important;font-weight:800!important;text-align:start!important;border-start-start-radius:14px!important;border-end-start-radius:14px!important;position:relative!important;padding-inline-start:1.35rem!important}'
			. 'table.woocommerce-product-attributes.shop_attributes td{color:#2a5f52!important;font-weight:600!important;text-align:start!important;border-start-end-radius:14px!important;border-end-end-radius:14px!important}'
			. 'table.woocommerce-product-attributes.shop_attributes td p{margin:0!important}'
			. 'table.woocommerce-product-attributes.shop_attributes th::before{content:"";position:absolute;inset-inline-start:.55rem;top:.7rem;bottom:.7rem;width:3px;border-radius:999px;background:#4aa88e}'
			. '</style>';
	},
	30
);
