<?php
/**
 * Stop horizontal scroll from Elementor 100vw / stretched sections.
 *
 * @package NahalFontDisplaySwap
 */

class NFDS_Overflow {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'print_js' ), 99 );
	}

	/**
	 * @return string
	 */
	public static function css() {
		return
			'html{max-width:100%;overflow-x:hidden;overflow-x:clip;}'
			. 'body{max-width:100%;width:auto;overflow-x:hidden;}'
			. 'img,video,svg,canvas,iframe,embed,object{max-width:100%;}'
			. '.elementor-section.elementor-section-stretched,'
			. '.elementor-section.elementor-section-full_width,'
			. '.e-con.e-con-full,'
			. '.e-con.e-parent{'
			. 'max-width:100% !important;'
			. '}'
			. '.elementor-section.elementor-section-stretched{'
			. 'width:100% !important;'
			. 'left:0 !important;'
			. 'right:0 !important;'
			. 'transform:none !important;'
			. 'margin-left:0 !important;'
			. 'margin-right:0 !important;'
			. '}'
			. 'body.rtl .elementor-section.elementor-section-stretched{'
			. 'left:0 !important;'
			. 'right:0 !important;'
			. '}';
	}

	public static function print_css() {
		echo '<style id="nfds-no-hscroll">' . self::css() . '</style>' . "\n";
	}

	public static function print_js() {
		echo '<script id="nfds-no-hscroll-js">';
		echo '(function(){function n(){var d=document.documentElement;if(!d||!document.body)return;if(d.scrollWidth>d.clientWidth+2){d.style.setProperty("overflow-x","hidden","important");document.body.style.setProperty("overflow-x","hidden","important");document.body.style.setProperty("max-width","100%","important");}}n();if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",n);window.addEventListener("load",n);window.addEventListener("resize",n);}());';
		echo '</script>' . "\n";
	}
}
