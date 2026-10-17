<?php
/**
 * Unread counts, issue grouping, weekly/monthly digests.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Stats {

	/**
	 * Stable key so the same broken asset/page groups together.
	 *
	 * @param array<string, mixed> $data Normalized or public payload.
	 * @return string
	 */
	public static function issue_key( array $data ) {
		$urls = array();
		if ( ! empty( $data['failed'] ) && is_array( $data['failed'] ) ) {
			foreach ( $data['failed'] as $row ) {
				if ( ! is_array( $row ) || empty( $row['url'] ) ) {
					continue;
				}
				if ( ! empty( $row['payment'] ) || ( class_exists( 'NPBR_Sanitizer', false ) && NPBR_Sanitizer::is_payment_asset( $row['url'] ) ) ) {
					$urls[] = preg_replace( '/[?#].*$/', '', (string) $row['url'] );
				}
			}
		}
		$urls = array_values( array_unique( $urls ) );
		sort( $urls );
		if ( $urls ) {
			return 'asset:' . implode( '|', array_slice( $urls, 0, 3 ) );
		}
		$page = isset( $data['pageUrl'] ) ? preg_replace( '/[?#].*$/', '', (string) $data['pageUrl'] ) : '';
		$note = isset( $data['note'] ) ? trim( (string) $data['note'] ) : '';
		$note = preg_replace( '/\s+/u', ' ', $note );
		if ( function_exists( 'mb_substr' ) ) {
			$note = mb_substr( (string) $note, 0, 80 );
		} else {
			$note = substr( (string) $note, 0, 80 );
		}
		return 'page:' . $page . '|' . $note;
	}

	/**
	 * @param array<string, mixed> $data Payload.
	 * @return string
	 */
	public static function issue_label( array $data ) {
		if ( class_exists( 'NPBR_Report', false ) ) {
			$vitals = NPBR_Report::vital_failures( $data );
			if ( $vitals && ! empty( $vitals[0]['title'] ) ) {
				$label = (string) $vitals[0]['title'];
				if ( ! empty( $vitals[0]['url'] ) ) {
					$label .= ' — ' . $vitals[0]['url'];
				}
				return $label;
			}
		}
		if ( ! empty( $data['summary'] ) ) {
			return (string) $data['summary'];
		}
		$note = isset( $data['note'] ) ? trim( (string) $data['note'] ) : '';
		if ( $note ) {
			if ( function_exists( 'mb_substr' ) ) {
				return mb_substr( $note, 0, 120 );
			}
			return substr( $note, 0, 120 );
		}
		return isset( $data['pageUrl'] ) ? (string) $data['pageUrl'] : 'خطای خرید';
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Report rows.
	 * @return array<string, mixed>
	 */
	public static function summarize( array $rows ) {
		$issues = array();
		$open   = 0;
		$res    = 0;
		$auto   = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$status = isset( $row['status'] ) ? (string) $row['status'] : 'open';
			if ( 'resolved' === $status ) {
				$res++;
			} else {
				$open++;
			}
			if ( ! empty( $row['auto'] ) ) {
				$auto++;
			}
			$key = isset( $row['issueKey'] ) ? (string) $row['issueKey'] : self::issue_key( $row );
			if ( ! isset( $issues[ $key ] ) ) {
				$issues[ $key ] = array(
					'key'      => $key,
					'label'    => isset( $row['issueLabel'] ) && $row['issueLabel'] ? $row['issueLabel'] : self::issue_label( $row ),
					'count'    => 0,
					'open'     => 0,
					'resolved' => 0,
					'pageUrl'  => isset( $row['pageUrl'] ) ? (string) $row['pageUrl'] : '',
					'lastDate' => isset( $row['date'] ) ? (string) $row['date'] : '',
				);
			}
			$issues[ $key ]['count']++;
			if ( 'resolved' === $status ) {
				$issues[ $key ]['resolved']++;
			} else {
				$issues[ $key ]['open']++;
			}
			if ( isset( $row['date'] ) && $row['date'] > $issues[ $key ]['lastDate'] ) {
				$issues[ $key ]['lastDate'] = $row['date'];
			}
		}
		$issues = array_values( $issues );
		usort(
			$issues,
			static function ( $a, $b ) {
				if ( $a['count'] === $b['count'] ) {
					return 0;
				}
				return ( $a['count'] > $b['count'] ) ? -1 : 1;
			}
		);
		return array(
			'total'    => count( $rows ),
			'open'     => $open,
			'resolved' => $res,
			'auto'     => $auto,
			'issues'   => $issues,
		);
	}

	/**
	 * @param array<string, mixed> $sum Summary.
	 * @param string               $title Heading.
	 * @return string
	 */
	public static function digest_text( array $sum, $title ) {
		$lines   = array();
		$lines[] = (string) $title;
		$lines[] = '';
		$lines[] = 'ثبت‌شده: ' . (int) $sum['total'];
		$lines[] = 'باز: ' . (int) $sum['open'];
		$lines[] = 'رفع‌شده: ' . (int) $sum['resolved'];
		$lines[] = 'خودکار: ' . (int) $sum['auto'];
		$lines[] = '';
		$lines[] = 'خطاها:';
		$issues  = isset( $sum['issues'] ) && is_array( $sum['issues'] ) ? $sum['issues'] : array();
		if ( ! $issues ) {
			$lines[] = 'در این بازه گزارشی نبود.';
		} else {
			foreach ( array_slice( $issues, 0, 20 ) as $i => $issue ) {
				$state = (int) $issue['open'] > 0 ? 'باز' : 'رفع‌شده';
				$lines[] = ( $i + 1 ) . '. ' . ( isset( $issue['label'] ) ? $issue['label'] : '' )
					. ' — ' . (int) $issue['count'] . ' بار — ' . $state;
			}
		}
		return implode( "\n", $lines );
	}

	/**
	 * @param string $kind week|month|last_month
	 * @param int    $now  Unix time.
	 * @return array{start:int,end:int,label:string,kind:string}
	 */
	public static function period( $kind, $now = 0 ) {
		$now = $now ? (int) $now : time();
		if ( 'last_month' === $kind ) {
			$start = strtotime( date( 'Y-m-01 00:00:00', strtotime( '-1 month', $now ) ) );
			$end   = strtotime( date( 'Y-m-01 00:00:00', $now ) ) - 1;
			return array(
				'kind'  => 'last_month',
				'start' => $start,
				'end'   => $end,
				'label' => 'ماه قبل (' . date( 'Y-m', $start ) . ')',
			);
		}
		if ( 'month' === $kind ) {
			$start = strtotime( date( 'Y-m-01 00:00:00', $now ) );
			return array(
				'kind'  => 'month',
				'start' => $start,
				'end'   => $now,
				'label' => 'این ماه (' . date( 'Y-m', $start ) . ')',
			);
		}
		$start = $now - ( 7 * 86400 );
		return array(
			'kind'  => 'week',
			'start' => $start,
			'end'   => $now,
			'label' => '۷ روز گذشته',
		);
	}

	/**
	 * @return int
	 */
	public static function unread() {
		if ( ! function_exists( 'get_option' ) ) {
			return 0;
		}
		return (int) get_option( 'npbr_unread', 0 );
	}

	public static function bump_unread() {
		if ( ! function_exists( 'update_option' ) ) {
			return;
		}
		update_option( 'npbr_unread', self::unread() + 1 );
	}

	public static function clear_unread() {
		if ( function_exists( 'update_option' ) ) {
			update_option( 'npbr_unread', 0 );
		}
	}

	/**
	 * @return int
	 */
	public static function open_count() {
		if ( ! class_exists( 'WP_Query', false ) ) {
			return 0;
		}
		$q = new WP_Query(
			array(
				'post_type'      => 'npbr_report',
				'post_status'    => array( 'private', 'publish', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_npbr_status',
						'value'   => 'resolved',
						'compare' => '!=',
					),
					array(
						'key'     => '_npbr_status',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * @param int $start Start ts.
	 * @param int $end   End ts.
	 * @return array<int, array<string, mixed>>
	 */
	public static function rows_between( $start, $end ) {
		if ( ! class_exists( 'WP_Query', false ) ) {
			return array();
		}
		$q = new WP_Query(
			array(
				'post_type'      => 'npbr_report',
				'post_status'    => array( 'private', 'publish', 'draft' ),
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_query'     => array(
					array(
						'after'     => date( 'Y-m-d H:i:s', (int) $start ),
						'before'    => date( 'Y-m-d H:i:s', (int) $end ),
						'inclusive' => true,
					),
				),
			)
		);
		$rows = array();
		foreach ( $q->posts as $post ) {
			$rows[] = self::row_from_post( $post );
		}
		return $rows;
	}

	/**
	 * @param WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	public static function row_from_post( $post ) {
		$id      = (int) $post->ID;
		$payload = json_decode( (string) get_post_meta( $id, '_npbr_payload', true ), true );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}
		$key = (string) get_post_meta( $id, '_npbr_issue_key', true );
		if ( ! $key ) {
			$key = self::issue_key( $payload );
		}
		$label = (string) get_post_meta( $id, '_npbr_issue_label', true );
		if ( ! $label ) {
			$label = self::issue_label( $payload );
		}
		return array(
			'id'         => $id,
			'date'       => isset( $post->post_date ) ? (string) $post->post_date : '',
			'status'     => self::status( $id ),
			'summary'    => (string) get_post_meta( $id, '_npbr_summary', true ),
			'pageUrl'    => (string) get_post_meta( $id, '_npbr_page_url', true ),
			'phone'      => (string) get_post_meta( $id, '_npbr_phone', true ),
			'auto'       => '1' === (string) get_post_meta( $id, '_npbr_auto', true ),
			'issueKey'   => $key,
			'issueLabel' => $label,
			'note'       => isset( $post->post_content ) ? (string) $post->post_content : '',
			'failed'     => isset( $payload['failed'] ) ? $payload['failed'] : array(),
			'payment'    => isset( $payload['payment'] ) ? $payload['payment'] : array(),
		);
	}

	/**
	 * @param int $id Report id.
	 * @return string
	 */
	public static function status( $id ) {
		$s = (string) get_post_meta( (int) $id, '_npbr_status', true );
		return 'resolved' === $s ? 'resolved' : 'open';
	}

	/**
	 * @param int    $id     Report id.
	 * @param string $status open|resolved.
	 */
	public static function set_status( $id, $status ) {
		$id     = (int) $id;
		$status = 'resolved' === $status ? 'resolved' : 'open';
		update_post_meta( $id, '_npbr_status', $status );
		if ( 'resolved' === $status ) {
			update_post_meta( $id, '_npbr_resolved_at', gmdate( 'Y-m-d H:i:s' ) );
		} else {
			delete_post_meta( $id, '_npbr_resolved_at' );
		}
	}

	public static function init_cron() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}
		add_action( 'npbr_digest_tick', array( __CLASS__, 'tick' ) );
		if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_event' ) && ! wp_next_scheduled( 'npbr_digest_tick' ) ) {
			wp_schedule_event( time() + 600, 'daily', 'npbr_digest_tick' );
		}
	}

	public static function clear_cron() {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( 'npbr_digest_tick' );
		}
	}

	public static function tick() {
		if ( ! function_exists( 'get_option' ) ) {
			return;
		}
		$settings = class_exists( 'NPBR_Plugin', false ) ? NPBR_Plugin::settings() : array();
		if ( empty( $settings['digest_email'] ) ) {
			return;
		}
		$week_key = date( 'o-W' );
		if ( (string) get_option( 'npbr_weekly_sent' ) !== $week_key ) {
			self::email_digest( 'week' );
			update_option( 'npbr_weekly_sent', $week_key );
		}
		$month_key = date( 'Y-m' );
		if ( (int) date( 'j' ) <= 2 && (string) get_option( 'npbr_monthly_sent' ) !== $month_key ) {
			self::email_digest( 'last_month' );
			update_option( 'npbr_monthly_sent', $month_key );
		}
	}

	/**
	 * @param string $kind week|month|last_month
	 * @return bool
	 */
	public static function email_digest( $kind ) {
		if ( ! class_exists( 'NPBR_Mailer', false ) || ! class_exists( 'NPBR_Plugin', false ) ) {
			return false;
		}
		$period = self::period( $kind );
		$sum    = self::summarize( self::rows_between( $period['start'], $period['end'] ) );
		$title  = ( 'week' === $kind ) ? 'گزارش هفتگی خطای خرید نهال' : 'گزارش ماهانه خطای خرید نهال';
		return NPBR_Mailer::send_digest( $title . ' — ' . $period['label'], $sum, $period );
	}
}
