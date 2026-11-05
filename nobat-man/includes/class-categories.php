<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Categories {
	const OPTION = 'nobat_man_categories';

	public static function defaults() {
		return array(
			array(
				'id'          => 'cat_anxiety',
				'title'       => 'اضطراب و استرس',
				'description' => 'نگرانی، تنش و اختلال خواب',
				'questions'   => array(
					array(
						'id'    => 'q1',
						'label' => 'مدت زمان مشکل چقدر بوده است؟',
						'type'  => 'text',
					),
					array(
						'id'    => 'q2',
						'label' => 'توضیح کوتاه از وضعیت فعلی',
						'type'  => 'textarea',
					),
				),
			),
			array(
				'id'          => 'cat_family',
				'title'       => 'خانواده و زوج',
				'description' => 'ارتباط، تعارض و فرزندپروری',
				'questions'   => array(
					array(
						'id'    => 'q1',
						'label' => 'موضوع اصلی جلسه چیست؟',
						'type'  => 'text',
					),
				),
			),
		);
	}

	public static function all() {
		$items = get_option( self::OPTION, null );
		if ( ! is_array( $items ) ) {
			$items = self::defaults();
			update_option( self::OPTION, $items, false );
		}
		return array_values( $items );
	}

	public static function save( $items ) {
		$clean = array();
		foreach ( (array) $items as $item ) {
			$normalized = self::normalize( $item );
			if ( $normalized ) {
				$clean[] = $normalized;
			}
		}
		update_option( self::OPTION, $clean, false );
		return $clean;
	}

	public static function get( $id ) {
		foreach ( self::all() as $item ) {
			if ( isset( $item['id'] ) && $item['id'] === $id ) {
				return $item;
			}
		}
		return null;
	}

	public static function upsert( $data ) {
		$items = self::all();
		$item  = self::normalize( $data );
		if ( ! $item ) {
			return new WP_Error( 'invalid', 'عنوان دسته‌بندی الزامی است.' );
		}
		$found = false;
		foreach ( $items as $i => $row ) {
			if ( $row['id'] === $item['id'] ) {
				$items[ $i ] = $item;
				$found       = true;
				break;
			}
		}
		if ( ! $found ) {
			$items[] = $item;
		}
		self::save( $items );
		return $item;
	}

	public static function delete( $id ) {
		$items = array();
		foreach ( self::all() as $row ) {
			if ( $row['id'] !== $id ) {
				$items[] = $row;
			}
		}
		self::save( $items );
		return true;
	}

	public static function normalize( $data ) {
		$title = isset( $data['title'] ) ? sanitize_text_field( wp_unslash( $data['title'] ) ) : '';
		if ( $title === '' ) {
			return null;
		}
		$id = isset( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		if ( $id === '' ) {
			$id = 'cat_' . wp_generate_password( 8, false, false );
		}
		$questions = array();
		$raw_q     = isset( $data['questions'] ) ? $data['questions'] : array();
		if ( is_string( $raw_q ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $raw_q );
			$raw_q = array();
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( $line !== '' ) {
					$raw_q[] = array( 'label' => $line, 'type' => 'text' );
				}
			}
		}
		foreach ( (array) $raw_q as $q ) {
			$label = isset( $q['label'] ) ? sanitize_text_field( $q['label'] ) : '';
			if ( $label === '' ) {
				continue;
			}
			$qid = isset( $q['id'] ) ? sanitize_key( $q['id'] ) : '';
			if ( $qid === '' ) {
				$qid = 'q_' . wp_generate_password( 6, false, false );
			}
			$type = isset( $q['type'] ) && $q['type'] === 'textarea' ? 'textarea' : 'text';
			$questions[] = array(
				'id'    => $qid,
				'label' => $label,
				'type'  => $type,
			);
		}
		return array(
			'id'          => $id,
			'title'       => $title,
			'description' => isset( $data['description'] ) ? sanitize_textarea_field( wp_unslash( $data['description'] ) ) : '',
			'questions'   => $questions,
		);
	}
}
