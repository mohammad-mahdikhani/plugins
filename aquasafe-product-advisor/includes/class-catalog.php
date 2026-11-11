<?php
class AS_Advisor_Catalog {

	const KIND_CATS = array(
		65 => 'water_device',
		16 => 'air_mix',
		40 => 'water_filter',
		57 => 'air_filter',
		47 => 'part',
		49 => 'tank',
		50 => 'pump',
		44 => 'cartridge',
		58 => 'air_part',
		62 => 'water_filter',
		60 => 'water_filter',
		48 => 'water_filter',
		59 => 'water_filter',
		61 => 'water_filter',
	);

	public static function all() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 400,
				'return' => 'objects',
			)
		);
		$out = array();
		foreach ( $products as $p ) {
			$out[] = self::normalize( $p );
		}
		return $out;
	}

	public static function models() {
		$list = array();
		foreach ( self::all() as $p ) {
			if ( in_array( $p['kind'], array( 'water_device', 'air_device' ), true ) ) {
				$list[] = array(
					'id'    => $p['id'],
					'name'  => $p['name'],
					'model' => $p['model'] ? $p['model'] : $p['name'],
					'kind'  => $p['kind'],
				);
			}
		}
		return $list;
	}

	public static function budgets() {
		$engine = new AS_Advisor_Engine();
		$all    = self::all();
		$map    = array(
			'water'        => array( 'water_device' ),
			'air'          => array( 'air_device' ),
			'filter'       => array( 'water_filter', 'air_filter', 'cartridge', 'pump', 'tank', 'part', 'air_part' ),
			'filter_water' => array( 'water_filter', 'cartridge', 'pump', 'tank', 'part' ),
			'filter_air'   => array( 'air_filter', 'air_part' ),
			'water_filter' => array( 'water_filter' ),
			'air_filter'   => array( 'air_filter' ),
			'other'        => array( 'part', 'tank', 'pump', 'cartridge', 'air_part' ),
			'tank'         => array( 'tank' ),
			'pump'         => array( 'pump' ),
			'cartridge'    => array( 'cartridge' ),
			'part'         => array( 'part' ),
			'air_part'     => array( 'air_part' ),
		);
		$out = array();
		foreach ( $map as $key => $kinds ) {
			$out[ $key ] = $engine->budget_pack( $all, $kinds );
		}
		return $out;
	}

	public static function other_kinds() {
		return array(
			array( 'id' => 'tank', 'label' => 'منبع آب تحت فشار' ),
			array( 'id' => 'pump', 'label' => 'پمپ تصفیه آب' ),
			array( 'id' => 'cartridge', 'label' => 'کارتریج فیلتر' ),
			array( 'id' => 'part', 'label' => 'قطعات دستگاه آب' ),
			array( 'id' => 'air_part', 'label' => 'قطعات دستگاه هوا' ),
		);
	}

	public static function normalize( WC_Product $p ) {
		$id     = $p->get_id();
		$meta   = self::product_meta( $id );
		$cat_ids = $p->get_category_ids();
		$kind    = ! empty( $meta['kind'] ) ? $meta['kind'] : self::infer_kind( $p->get_name(), $cat_ids );
		$img     = wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' );
		return array(
			'id'             => $id,
			'name'           => $p->get_name(),
			'permalink'      => get_permalink( $id ),
			'image'          => $img ? $img : '',
			'price'          => (int) $p->get_price(),
			'price_html'     => wp_strip_all_tags( $p->get_price_html() ),
			'stock_status'   => $p->get_stock_status(),
			'kind'           => $kind,
			'place'          => $meta['place'] ?? '',
			'need'           => $meta['need'] ?? '',
			'users_max'      => (int) ( $meta['users_max'] ?? 0 ),
			'install'        => $meta['install'] ?? '',
			'room_m2'        => (int) ( $meta['room_m2'] ?? 0 ),
			'noise'          => $meta['noise'] ?? '',
			'model'          => $meta['model'] ?? '',
			'replace_months' => (int) ( $meta['replace_months'] ?? 0 ),
			'filter_price'   => (int) ( $meta['filter_price'] ?? 0 ),
			'specs'          => array_filter(
				array(
					'قیمت' => wp_strip_all_tags( $p->get_price_html() ),
					'موجودی' => $p->is_in_stock() ? 'موجود' : 'ناموجود',
					'مدل'  => $meta['model'] ?? '',
				)
			),
			'add_to_cart'    => $p->is_purchasable() && $p->is_in_stock() ? $p->add_to_cart_url() : '',
			'sku'            => $p->get_sku(),
			'incomplete'     => self::is_incomplete( $kind, $meta ),
		);
	}

	public static function infer_kind( $name, array $cat_ids ) {
		if ( preg_match( '/دستگاه\s*تصفیه\s*هوا/u', $name ) ) {
			return 'air_device';
		}
		if ( preg_match( '/فیلتر.{0,12}هوا|هوا.{0,12}فیلتر|HEPA|VOC/u', $name ) ) {
			return 'air_filter';
		}
		foreach ( $cat_ids as $cid ) {
			if ( isset( self::KIND_CATS[ $cid ] ) && 'air_mix' !== self::KIND_CATS[ $cid ] ) {
				return self::KIND_CATS[ $cid ];
			}
		}
		if ( in_array( 16, $cat_ids, true ) ) {
			if ( preg_match( '/برد|موتور|کاور|پمپ|اتصال|آچار|سیم/u', $name ) ) {
				return 'air_part';
			}
			return 'part';
		}
		return 'other';
	}

	public static function product_meta( $id ) {
		$keys = array( 'kind', 'place', 'need', 'users_max', 'install', 'room_m2', 'noise', 'model', 'replace_months', 'filter_price' );
		$out  = array();
		foreach ( $keys as $k ) {
			$v = get_post_meta( $id, '_as_adv_' . $k, true );
			if ( '' !== $v && false !== $v ) {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	public static function is_incomplete( $kind, array $meta ) {
		if ( 'air_device' === $kind && empty( $meta['room_m2'] ) ) {
			return true;
		}
		if ( in_array( $kind, array( 'water_filter', 'air_filter' ), true ) && empty( $meta['model'] ) ) {
			return true;
		}
		return false;
	}

	public static function compat() {
		$rows = get_option( 'as_advisor_compat', array() );
		return is_array( $rows ) ? $rows : array();
	}
}
