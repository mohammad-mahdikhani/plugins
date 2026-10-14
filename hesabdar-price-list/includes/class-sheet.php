<?php
/**
 * Parsed spreadsheet / PDF table.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Sheet {
	/** @var string */
	public $name = '';

	/** @var string[] */
	public $headers = array();

	/** @var array<int, array<int, string>> 0-based data rows (without header). */
	public $rows = array();

	/** @var int 1-based source line of the header. */
	public $header_line = 1;

	/**
	 * @param string                         $name    Sheet name.
	 * @param string[]                       $headers Headers.
	 * @param array<int, array<int, string>> $rows    Data rows.
	 * @param int                            $header_line Header line.
	 */
	public function __construct( $name = '', $headers = array(), $rows = array(), $header_line = 1 ) {
		$this->name        = $name;
		$this->headers     = array_values( $headers );
		$this->rows        = array_values( $rows );
		$this->header_line = $header_line;
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	public function sample_rows( $limit = 8 ) {
		return array_slice( $this->rows, 0, $limit );
	}

	/**
	 * @return array{name:string,headers:string[],sample:array,row_count:int,header_line:int}
	 */
	public function to_preview() {
		return array(
			'name'        => $this->name,
			'headers'     => $this->headers,
			'sample'      => $this->sample_rows(),
			'row_count'   => count( $this->rows ),
			'header_line' => $this->header_line,
		);
	}
}

class HPL_Parsed_File {
	/** @var string */
	public $filename = '';

	/** @var string csv|xlsx|xls|pdf */
	public $type = '';

	/** @var HPL_Sheet[] */
	public $sheets = array();

	/** @var string */
	public $warning = '';

	/**
	 * @return HPL_Sheet|null
	 */
	public function sheet( $index ) {
		return isset( $this->sheets[ $index ] ) ? $this->sheets[ $index ] : null;
	}
}
