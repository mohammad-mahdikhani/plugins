<?php
/**
 * Stylish 3D maintenance page.
 *
 * @package NahalMaintenance
 * @var array<string, string> $data
 */

if ( ! isset( $data ) || ! is_array( $data ) ) {
	$data = array();
}
$data = NM_Page::normalize( $data );

$esc = static function ( $value ) {
	$value = (string) $value;
	if ( function_exists( 'esc_html' ) ) {
		return esc_html( $value );
	}
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
};
$esc_attr = static function ( $value ) {
	$value = (string) $value;
	if ( function_exists( 'esc_attr' ) ) {
		return esc_attr( $value );
	}
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
};
$esc_url = static function ( $value ) {
	$value = (string) $value;
	if ( function_exists( 'esc_url' ) ) {
		return esc_url( $value );
	}
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
};

$title   = $data['title'] !== '' ? $data['title'] : 'در حال تعمیر';
$brand   = $data['brand'];
$kicker  = $data['kicker'];
$message = $data['message'];
$eta     = $data['eta_text'];
$phone   = $data['phone'];
$address = $data['address'];
$logo    = $data['logo_url'];
$phone_href = $phone !== '' ? 'tel:' . preg_replace( '/[^\d+]/', '', $phone ) : '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="google" content="notranslate">
	<meta name="theme-color" content="#06110c">
	<title><?php echo $esc( $title ); ?><?php echo $brand !== '' ? ' | ' . $esc( $brand ) : ''; ?></title>
	<link rel="stylesheet" href="<?php echo $esc_url( $data['css_href'] ); ?>">
</head>
<body class="nm-body">
	<div class="nm-aurora" aria-hidden="true"></div>
	<div class="nm-dust" aria-hidden="true"></div>

	<main class="nm-shell">
		<section class="nm-stage" aria-hidden="true">
			<div class="nm-world" id="nm-world">
				<div class="nm-floor"></div>
				<div class="nm-ring nm-ring-a"></div>
				<div class="nm-ring nm-ring-b"></div>
				<div class="nm-sculpture">
					<?php echo NM_Page::cube( 'nm-cube-core' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo NM_Page::cube( 'nm-cube-a' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo NM_Page::cube( 'nm-cube-b' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo NM_Page::cube( 'nm-cube-c' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo NM_Page::cube( 'nm-cube-d' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="nm-crystal">
						<span class="nm-pyr nm-pyr-top"></span>
						<span class="nm-pyr nm-pyr-bot"></span>
					</div>
				</div>
			</div>
		</section>

		<section class="nm-copy">
			<div class="nm-card" id="nm-card" dir="rtl">
				<?php if ( $logo !== '' ) : ?>
					<img class="nm-logo" src="<?php echo $esc_url( $logo ); ?>" alt="<?php echo $esc_attr( $brand ); ?>">
				<?php else : ?>
					<div class="nm-mark" aria-hidden="true">
						<span></span><span></span><span></span>
					</div>
				<?php endif; ?>

				<?php if ( $kicker !== '' ) : ?>
					<p class="nm-kicker"><?php echo $esc( $kicker ); ?></p>
				<?php endif; ?>

				<h1 class="nm-title"><?php echo $esc( $title ); ?></h1>

				<?php if ( $message !== '' ) : ?>
					<p class="nm-message"><?php echo $esc( $message ); ?></p>
				<?php endif; ?>

				<?php if ( $eta !== '' ) : ?>
					<p class="nm-eta"><?php echo $esc( $eta ); ?></p>
				<?php endif; ?>

				<?php if ( $phone !== '' || $address !== '' ) : ?>
					<div class="nm-meta">
						<?php if ( $phone !== '' ) : ?>
							<a class="nm-phone" href="<?php echo $esc_url( $phone_href ); ?>" dir="ltr"><?php echo $esc( $phone ); ?></a>
						<?php endif; ?>
						<?php if ( $address !== '' ) : ?>
							<p class="nm-address"><?php echo $esc( $address ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $brand !== '' ) : ?>
					<p class="nm-brand"><?php echo $esc( $brand ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	</main>

	<script src="<?php echo $esc_url( $data['js_src'] ); ?>"></script>
</body>
</html>
