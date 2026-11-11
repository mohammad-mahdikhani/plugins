<?php
require_once dirname( __DIR__ ) . '/includes/class-engine.php';

function as_assert( $ok, $msg ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: $msg\n" );
		exit( 1 );
	}
	echo "ok: $msg\n";
}

function item( $over ) {
	$base = array(
		'id'           => 1,
		'name'         => 'نمونه',
		'permalink'    => 'https://example.test/p',
		'image'        => '',
		'price'        => 400000000,
		'price_html'   => '400',
		'stock_status' => 'instock',
		'kind'         => 'water_device',
		'place'        => 'home',
		'need'         => 'general',
		'users_max'    => 6,
		'install'      => 'under_sink',
		'room_m2'      => 0,
		'noise'        => '',
		'model'        => '',
		'specs'        => array(),
		'add_to_cart'  => '',
		'replace_months' => 0,
		'filter_price' => 0,
	);
	return array_merge( $base, $over );
}

$e = new AS_Advisor_Engine();
$catalog = array(
	item( array( 'id' => 10, 'name' => 'دستگاه خانگی A', 'price' => 320000000 ) ),
	item( array( 'id' => 11, 'name' => 'دستگاه خانگی B', 'price' => 700000000 ) ),
	item( array( 'id' => 12, 'name' => 'دستگاه نایاب', 'price' => 200000000, 'stock_status' => 'outofstock' ) ),
	item( array( 'id' => 20, 'name' => 'دستگاه هوا 3500', 'kind' => 'air_device', 'price' => 992000000, 'room_m2' => 40, 'noise' => 'normal' ) ),
	item( array( 'id' => 21, 'name' => 'هوا کوچک', 'kind' => 'air_device', 'price' => 400000000, 'room_m2' => 12 ) ),
	item( array( 'id' => 30, 'name' => 'فیلتر 3500', 'kind' => 'air_filter', 'price' => 14000000, 'model' => 'AJ3500' ) ),
	item( array( 'id' => 31, 'name' => 'فیلتر دیگر', 'kind' => 'air_filter', 'price' => 20000000 ) ),
);

$r = $e->recommend( array( 'path' => 'water', 'place' => 'home', 'budget' => 'low' ), $catalog );
as_assert( 1 === count( $r['items'] ) && 10 === $r['items'][0]['id'], 'water budget low excludes expensive and OOS' );

$r = $e->recommend( array( 'path' => 'water', 'budget' => 'low' ), $catalog );
as_assert( empty( array_filter( $r['items'], function ( $i ) { return 12 === $i['id']; } ) ), 'out of stock never recommended' );

$r = $e->recommend( array( 'path' => 'water', 'budget' => 'low' ), array( item( array( 'id' => 99, 'price' => 900000000 ) ) ) );
as_assert( 0 === count( $r['items'] ) && $r['empty_reason'], 'empty when all over budget, no filler' );

$r = $e->recommend( array( 'path' => 'air', 'area' => 'l' ), $catalog );
as_assert( 0 === count( $r['items'] ), 'air large room not filled with undersized units' );

$r = $e->recommend( array( 'path' => 'air', 'area' => 'm' ), $catalog );
as_assert( 1 === count( $r['items'] ) && 20 === $r['items'][0]['id'], 'air medium uses unit with enough room_m2' );

$compat = array(
	array( 'device_id' => 20, 'part_id' => 30, 'device_model' => 'AJ3500', 'status' => 'confirmed' ),
);
$r = $e->recommend(
	array( 'path' => 'filter', 'device_type' => 'air', 'model' => 'AJ3500', 'part_type' => 'filter' ),
	$catalog,
	$compat
);
as_assert( 1 === count( $r['items'] ) && 30 === $r['items'][0]['id'], 'filter uses registered compatibility' );
as_assert( 'confirmed' === $r['items'][0]['compat'], 'confirmed compat marked' );

$r = $e->recommend(
	array( 'path' => 'filter', 'device_type' => 'air', 'model' => 'UnknownX', 'part_type' => 'filter' ),
	$catalog,
	$compat
);
as_assert( 0 === count( $r['items'] ) && ! empty( $r['need_support'] ), 'unknown model without relation is not claimed compatible' );

$r = $e->recommend(
	array( 'path' => 'filter', 'device_type' => 'air', 'model_unknown' => true, 'part_type' => 'filter' ),
	$catalog,
	array()
);
as_assert( ! empty( $r['need_support'] ), 'unknown model asks for support' );
as_assert( isset( $r['items'][0]['compat'] ) && 'unknown' === $r['items'][0]['compat'], 'does not mark unknown as confirmed' );

$r = $e->recommend(
	array( 'path' => 'filter', 'device_type' => 'air', 'device_id' => 20, 'part_type' => 'filter' ),
	$catalog,
	$compat
);
as_assert( 1 === count( $r['items'] ) && 30 === $r['items'][0]['id'], 'device_id maps to registered part' );

$office_only = array( item( array( 'id' => 88, 'place' => 'office', 'price' => 300000000 ) ) );
$r = $e->recommend( array( 'path' => 'water', 'place' => 'home', 'budget' => 'low' ), $office_only );
as_assert( 0 === count( $r['items'] ), 'does not fill home path with office-only device' );

echo "ALL_PASS\n";

