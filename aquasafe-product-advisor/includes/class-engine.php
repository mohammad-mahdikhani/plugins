<?php
/**
 * Rule-based product advisor engine (no paid AI).
 */
class AS_Advisor_Engine {

	const MAX_RESULTS = 3;

	/**
	 * @param array $answers
	 * @param array $catalog List of normalized products.
	 * @param array $compat  List of {device_id, part_id, status: confirmed|unconfirmed}
	 * @return array{items:array,empty_reason:?string,warnings:array,summary:string}
	 */
	public function recommend( array $answers, array $catalog, array $compat = array() ) {
		$path = isset( $answers['path'] ) ? $answers['path'] : '';
		$warnings = array();

		if ( 'water' === $path ) {
			$pool = $this->filter_kind( $catalog, array( 'water_device' ) );
			$pool = $this->apply_water_rules( $pool, $answers, $warnings );
		} elseif ( 'air' === $path ) {
			$pool = $this->filter_kind( $catalog, array( 'air_device' ) );
			$pool = $this->apply_air_rules( $pool, $answers, $warnings );
		} elseif ( 'filter' === $path ) {
			return $this->recommend_parts( $answers, $catalog, $compat );
		} elseif ( 'other' === $path ) {
			$kind = isset( $answers['other_kind'] ) ? $answers['other_kind'] : 'part';
			$pool = $this->filter_kind( $catalog, array( $kind ) );
		} else {
			return $this->empty_result( 'مسیر انتخاب مشخص نیست.', array() );
		}

		$budget    = $this->budget_max( $answers, $pool );
		$in_budget = $this->apply_budget( $pool, $budget );
		if ( $budget && ! $in_budget && $pool ) {
			return $this->empty_result(
				'در بازه بودجه انتخاب‌شده محصول موجودی پیدا نشد. می‌توانید بودجه را افزایش دهید یا «هنوز مشخص نکرده‌ام» را انتخاب کنید.',
				$warnings,
				$answers
			);
		}
		$pool = $budget ? $in_budget : $pool;
		$pool = $this->only_in_stock( $pool );
		if ( ! $pool ) {
			return $this->empty_result(
				'برای این پاسخ‌ها محصول موجودی در فروشگاه نیست. پاسخ‌ها را تغییر دهید یا با پشتیبانی تماس بگیرید.',
				$warnings,
				$answers
			);
		}

		usort( $pool, array( $this, 'sort_water_air' ) );
		$items = array_slice( $pool, 0, self::MAX_RESULTS );
		$out   = array();
		foreach ( $items as $p ) {
			$out[] = $this->decorate( $p, $answers, $warnings, 'device' );
		}
		return array(
			'items'        => $out,
			'empty_reason' => null,
			'warnings'     => $warnings,
			'summary'      => $this->summary( $answers ),
			'need_summary' => $this->summary( $answers ),
		);
	}

	/**
	 * @return array
	 */
	public function recommend_parts( array $answers, array $catalog, array $compat ) {
		$warnings = array();
		$dtype    = isset( $answers['device_type'] ) ? $answers['device_type'] : 'water';
		$want     = isset( $answers['part_type'] ) ? $answers['part_type'] : '';
		$model    = isset( $answers['model'] ) ? trim( (string) $answers['model'] ) : '';
		if ( ! empty( $answers['device_id'] ) ) {
			$model = (string) (int) $answers['device_id'];
		}
		$unknown  = ! empty( $answers['model_unknown'] );

		$kinds = array();
		if ( 'air' === $dtype ) {
			$kinds = ( 'part' === $want ) ? array( 'air_part', 'air_filter' ) : array( 'air_filter' );
		} else {
			if ( 'tank' === $want ) {
				$kinds = array( 'tank' );
			} elseif ( 'pump' === $want ) {
				$kinds = array( 'pump' );
			} elseif ( 'part' === $want ) {
				$kinds = array( 'part', 'tank', 'pump' );
			} else {
				$kinds = array( 'water_filter', 'cartridge' );
			}
		}
		$pool = $this->filter_kind( $catalog, $kinds );
		$pool = $this->only_in_stock( $pool );

		$confirmed_ids = array();
		$likely_ids    = array();
		if ( $model && ! $unknown ) {
			foreach ( $compat as $row ) {
				if ( (int) $row['device_id'] !== $this->find_device_id( $catalog, $model, $dtype ) &&
					stripos( (string) $row['device_model'], $model ) === false &&
					(int) $row['device_id'] !== (int) $model ) {
					continue;
				}
				$id = (int) $row['part_id'];
				if ( 'confirmed' === $row['status'] ) {
					$confirmed_ids[ $id ] = true;
				} else {
					$likely_ids[ $id ] = true;
				}
			}
			$device_id = $this->find_device_id( $catalog, $model, $dtype );
			foreach ( $compat as $row ) {
				if ( $device_id && (int) $row['device_id'] === $device_id ) {
					$id = (int) $row['part_id'];
					if ( 'confirmed' === $row['status'] ) {
						$confirmed_ids[ $id ] = true;
					} else {
						$likely_ids[ $id ] = true;
					}
				}
			}
		}

		$matched = array();
		if ( $confirmed_ids || $likely_ids ) {
			foreach ( $pool as $p ) {
				if ( isset( $confirmed_ids[ $p['id'] ] ) || isset( $likely_ids[ $p['id'] ] ) ) {
					$p['_compat'] = isset( $confirmed_ids[ $p['id'] ] ) ? 'confirmed' : 'unconfirmed';
					$matched[]    = $p;
				}
			}
			$pool = $matched;
		} elseif ( $model && ! $unknown ) {
			return array(
				'items'        => array(),
				'empty_reason' => 'برای این مدل، سازگاری فیلتر یا قطعه در فروشگاه ثبت نشده است. آن را «قطعاً سازگار» اعلام نمی‌کنیم. مدل روی برچسب دستگاه را بررسی کنید یا با پشتیبانی تماس بگیرید.',
				'warnings'     => array( 'سازگاری بدون رابطه ثبت‌شده اعلام نمی‌شود.' ),
			'summary'      => $this->summary( $answers ),
			'need_summary' => $this->summary( $answers ),
			'need_support' => true,
			'compat_status'=> 'unknown',
		);
		} elseif ( $unknown || ! $model ) {
			$warnings[] = 'مدل دستگاه مشخص نیست. تا زمان تأیید برچسب مدل، قطعات را «سازگار قطعی» نشان نمی‌دهیم.';
			$pool       = array_slice( $this->sort_by_price( $pool ), 0, self::MAX_RESULTS );
			$items      = array();
			foreach ( $pool as $p ) {
				$p['_compat'] = 'unknown';
				$items[]      = $this->decorate( $p, $answers, $warnings, 'part' );
			}
			return array(
				'items'        => $items,
				'empty_reason' => $items ? null : 'قطعه‌ای در این گروه موجود نیست.',
				'warnings'     => $warnings,
				'summary'      => $this->summary( $answers ),
				'need_summary' => $this->summary( $answers ),
				'need_support' => true,
				'compat_status'=> 'unknown',
			);
		}

		$budget    = $this->budget_max( $answers, $pool );
		$in_budget = $this->apply_budget( $pool, $budget );
		$pool      = ( $budget && $in_budget ) ? $in_budget : ( $budget ? array() : $pool );
		if ( $budget && ! $pool ) {
			return $this->empty_result( 'در این بودجه فیلتر یا قطعه موجودی یافت نشد.', $warnings, $answers );
		}
		if ( ! $pool ) {
			return $this->empty_result( 'فیلتر یا قطعه موجودی مطابق انتخاب شما پیدا نشد.', $warnings, $answers );
		}
		usort(
			$pool,
			function ( $a, $b ) {
				$ra = ( 'confirmed' === ( $a['_compat'] ?? '' ) ) ? 0 : 1;
				$rb = ( 'confirmed' === ( $b['_compat'] ?? '' ) ) ? 0 : 1;
				if ( $ra !== $rb ) {
					return $ra - $rb;
				}
				return $a['price'] <=> $b['price'];
			}
		);
		$items = array();
		foreach ( array_slice( $pool, 0, self::MAX_RESULTS ) as $p ) {
			$items[] = $this->decorate( $p, $answers, $warnings, 'part' );
		}
		return array(
			'items'        => $items,
			'empty_reason' => null,
			'warnings'     => $warnings,
			'summary'      => $this->summary( $answers ),
			'need_summary' => $this->summary( $answers ),
			'need_support' => ! empty( $unknown ),
			'compat_status'=> ! empty( $unknown ) ? 'unknown' : 'registered',
		);
	}

	private function apply_water_rules( array $pool, array $answers, array &$warnings ) {
		$place  = isset( $answers['place'] ) ? $answers['place'] : '';
		$source = isset( $answers['water_source'] ) ? $answers['water_source'] : '';
		$need   = isset( $answers['need'] ) ? $answers['need'] : 'general';
		$users  = isset( $answers['users'] ) ? $answers['users'] : '';
		$space  = isset( $answers['space'] ) ? $answers['space'] : '';

		if ( 'well' === $source ) {
			$warnings[] = 'برای آب چاه یا منابع غیرشهری، انتخاب دقیق به آزمایش آب وابسته است. پیشنهادها عمومی هستند و جایگزین آزمایش نیستند.';
		}
		if ( 'unknown' === $source ) {
			$warnings[] = 'منبع آب نامشخص است؛ گزینه‌ها برای مصرف خانگی متداول آب شهری در نظر گرفته شده‌اند.';
		}

		$out = array();
		foreach ( $pool as $p ) {
			if ( $place && ! empty( $p['place'] ) && 'both' !== $p['place'] && $p['place'] !== $place ) {
				continue;
			}
			if ( $users && ! empty( $p['users_max'] ) ) {
				$n = $this->users_num( $users );
				if ( $n && $n > (int) $p['users_max'] ) {
					continue;
				}
			}
			if ( 'tight' === $space && ! empty( $p['install'] ) && 'under_sink' !== $p['install'] && 'compact' !== $p['install'] ) {
				continue;
			}
			$p['_score'] = 0;
			if ( $need && ! empty( $p['need'] ) && ( $p['need'] === $need || 'general' === $p['need'] ) ) {
				$p['_score'] += 2;
			}
			if ( 'pro' === $place && false !== strpos( $p['name'], 'صنعت' ) ) {
				$p['_score'] += 1;
			}
			$out[] = $p;
		}
		return $out;
	}

	private function apply_air_rules( array $pool, array $answers, array &$warnings ) {
		$area  = isset( $answers['area'] ) ? $answers['area'] : '';
		$noise = isset( $answers['noise'] ) ? $answers['noise'] : '';
		$issue = isset( $answers['air_issue'] ) ? $answers['air_issue'] : '';
		$out   = array();
		foreach ( $pool as $p ) {
			if ( $area && ! empty( $p['room_m2'] ) ) {
				$need_m2 = $this->area_num( $area );
				if ( $need_m2 && (int) $p['room_m2'] < $need_m2 ) {
					continue;
				}
			}
			if ( 'sleep' === $noise && ! empty( $p['noise'] ) && 'loud' === $p['noise'] ) {
				continue;
			}
			$p['_score'] = 0;
			if ( $issue && ! empty( $p['air_issue'] ) && $p['air_issue'] === $issue ) {
				$p['_score'] += 2;
			}
			$out[] = $p;
		}
		if ( $area && $pool && ! $out ) {
			$warnings[] = 'دستگاهی با مساحت پوشش ثبت‌شده برای این فضا موجود نیست. نتیجه را خالی می‌گذاریم تا محدودیت سازنده نادیده گرفته نشود.';
		}
		return $out;
	}

	private function decorate( array $p, array $answers, array $warnings, $mode ) {
		$reasons = $this->reasons( $p, $answers, $mode );
		$limits  = array();
		if ( isset( $p['_compat'] ) && 'confirmed' !== $p['_compat'] ) {
			$limits[] = 'سازگاری قطعی در فروشگاه ثبت نشده؛ قبل از خرید با پشتیبانی یا برچسب مدل دستگاه تطبیق دهید.';
		}
		if ( 'well' === ( $answers['water_source'] ?? '' ) ) {
			$limits[] = 'مناسب بودن برای آب چاه بدون آزمایش آب قابل تضمین نیست.';
		}
		$yearly = null;
		if ( ! empty( $p['replace_months'] ) && ! empty( $p['filter_price'] ) && (int) $p['replace_months'] > 0 ) {
			$sets   = 12 / (int) $p['replace_months'];
			$yearly = array(
				'amount' => (int) round( $sets * (int) $p['filter_price'] ),
				'basis'  => 'قیمت فیلتر ثبت‌شده ' . number_format( (int) $p['filter_price'] ) . ' و تعویض هر ' . (int) $p['replace_months'] . ' ماه.',
			);
		}
		return array(
			'id'            => $p['id'],
			'name'          => $p['name'],
			'permalink'     => $p['permalink'],
			'image'         => $p['image'],
			'price'         => $p['price'],
			'price_html'    => $p['price_html'],
			'stock_status'  => $p['stock_status'],
			'in_stock'      => 'instock' === $p['stock_status'],
			'reasons'       => array_slice( $reasons, 0, 3 ),
			'limits'        => $limits,
			'specs'         => array_values( array_filter( (array) $p['specs'] ) ),
			'yearly_cost'   => $yearly,
			'limitation'    => $limits ? $limits[0] : '',
			'compat'        => $p['_compat'] ?? null,
			'add_to_cart'   => $p['add_to_cart'],
		);
	}

	private function reasons( array $p, array $answers, $mode ) {
		$r = array();
		if ( 'instock' === $p['stock_status'] ) {
			$r[] = 'الان در فروشگاه موجود است.';
		}
		if ( ! empty( $answers['budget'] ) && 'unknown' !== $answers['budget'] && $p['price'] ) {
			$r[] = 'قیمت آن داخل بازه بودجه اعلام‌شده شماست.';
		}
		if ( 'water' === ( $answers['path'] ?? '' ) && 'home' === ( $answers['place'] ?? '' ) ) {
			$r[] = 'در دسته دستگاه تصفیه آب خانگی فروشگاه قرار دارد.';
		}
		if ( 'air' === ( $answers['path'] ?? '' ) ) {
			$r[] = 'دستگاه تصفیه هوای موجود در فروشگاه است.';
			if ( ! empty( $p['room_m2'] ) ) {
				$r[] = 'پوشش فضا طبق مشخصه ثبت‌شده حدود ' . (int) $p['room_m2'] . ' متر مربع است.';
			}
		}
		if ( 'part' === $mode && 'confirmed' === ( $p['_compat'] ?? '' ) ) {
			$r[] = 'سازگاری این قطعه با مدل انتخابی در فروشگاه ثبت شده است.';
		}
		if ( 'part' === $mode && 'unconfirmed' === ( $p['_compat'] ?? '' ) ) {
			$r[] = 'در فهرست قطعات مرتبط آمده؛ سازگاری قطعی را باید تأیید کنید.';
		}
		if ( count( $r ) < 2 ) {
			$r[] = 'با مسیر انتخاب شما در دسته‌بندی فروشگاه هم‌خوان است.';
		}
		return $r;
	}

	private function filter_kind( array $catalog, array $kinds ) {
		$out = array();
		foreach ( $catalog as $p ) {
			if ( in_array( $p['kind'], $kinds, true ) ) {
				$out[] = $p;
			}
		}
		return $out;
	}

	private function only_in_stock( array $pool ) {
		return array_values(
			array_filter(
				$pool,
				function ( $p ) {
					return 'instock' === $p['stock_status'];
				}
			)
		);
	}

	private function apply_budget( array $pool, $max ) {
		if ( ! $max ) {
			return $pool;
		}
		return array_values(
			array_filter(
				$pool,
				function ( $p ) use ( $max ) {
					return $p['price'] && (int) $p['price'] <= $max;
				}
			)
		);
	}

	/**
	 * Budget caps from in-stock prices of the given pool (no hardcoded shop prices).
	 *
	 * @return array{low:int,mid:int}
	 */
	public function budget_caps( array $pool ) {
		$prices = array();
		foreach ( $pool as $p ) {
			if ( ( $p['stock_status'] ?? '' ) !== 'instock' ) {
				continue;
			}
			$price = (int) ( $p['price'] ?? 0 );
			if ( $price > 0 ) {
				$prices[] = $price;
			}
		}
		$prices = array_values( array_unique( $prices ) );
		sort( $prices, SORT_NUMERIC );
		if ( ! $prices ) {
			return array( 'low' => 0, 'mid' => 0 );
		}
		$n     = count( $prices );
		$i_low = (int) round( ( $n - 1 ) * 0.35 );
		$i_mid = (int) round( ( $n - 1 ) * 0.7 );
		$low   = $this->nice_price( $prices[ $i_low ] );
		$mid   = $this->nice_price( $prices[ $i_mid ] );
		if ( $mid < $low ) {
			$mid = $low;
		}
		return array( 'low' => $low, 'mid' => $mid );
	}

	public function budget_options_from_catalog( array $catalog, array $kinds ) {
		$pool = $this->only_in_stock( $this->filter_kind( $catalog, $kinds ) );
		return $this->budget_option_rows( $pool );
	}

	/**
	 * Min/max and budget choices from live in-stock prices of one category.
	 *
	 * @return array{min:int,max:int,count:int,note:string,options:array}
	 */
	public function budget_pack( array $catalog, array $kinds ) {
		$pool   = $this->only_in_stock( $this->filter_kind( $catalog, $kinds ) );
		$bounds = $this->price_bounds( $pool );
		$note   = '';
		if ( $bounds['min'] && $bounds['max'] ) {
			$note = 'بازه قیمت محصولات موجود این دسته از ' . $this->format_toman( $bounds['min'] ) . ' تا ' . $this->format_toman( $bounds['max'] ) . ' است.';
		}
		return array(
			'min'     => $bounds['min'],
			'max'     => $bounds['max'],
			'count'   => $bounds['count'],
			'note'    => $note,
			'options' => $this->budget_option_rows( $pool ),
		);
	}

	/**
	 * @return array{min:int,max:int,count:int}
	 */
	public function price_bounds( array $pool ) {
		$prices = array();
		foreach ( $pool as $p ) {
			if ( ( $p['stock_status'] ?? '' ) !== 'instock' ) {
				continue;
			}
			$price = (int) ( $p['price'] ?? 0 );
			if ( $price > 0 ) {
				$prices[] = $price;
			}
		}
		if ( ! $prices ) {
			return array( 'min' => 0, 'max' => 0, 'count' => 0 );
		}
		return array(
			'min'   => (int) min( $prices ),
			'max'   => (int) max( $prices ),
			'count' => count( $prices ),
		);
	}

	public function budget_option_rows( array $pool ) {
		$caps = $this->budget_caps( $pool );
		$rows = array();
		if ( ! empty( $caps['low'] ) ) {
			$rows[] = array(
				'id'    => 'low',
				'max'   => $caps['low'],
				'label' => 'تا حدود ' . $this->format_toman( $caps['low'] ),
			);
		}
		if ( ! empty( $caps['mid'] ) && (int) $caps['mid'] > (int) $caps['low'] ) {
			$rows[] = array(
				'id'    => 'mid',
				'max'   => $caps['mid'],
				'label' => 'تا حدود ' . $this->format_toman( $caps['mid'] ),
			);
		}
		$rows[] = array(
			'id'    => 'high',
			'max'   => 0,
			'label' => 'بالاتر از این بازه / بدون سقف',
		);
		$rows[] = array(
			'id'    => 'unknown',
			'max'   => 0,
			'label' => 'هنوز مشخص نکرده‌ام',
		);
		return $rows;
	}

	private function format_toman( $n ) {
		$n = (int) $n;
		if ( function_exists( 'number_format_i18n' ) ) {
			return number_format_i18n( $n ) . ' تومان';
		}
		return number_format( $n ) . ' تومان';
	}

	private function nice_price( $n ) {
		$n = (int) $n;
		if ( $n <= 0 ) {
			return 0;
		}
		$step = 10000000;
		if ( $n < 10000000 ) {
			$step = 500000;
		}
		if ( $n < 1000000 ) {
			$step = 50000;
		}
		return (int) ( ceil( $n / $step ) * $step );
	}

	private function budget_max( array $answers, array $pool = array() ) {
		if ( ! empty( $answers['budget_max'] ) ) {
			return (int) $answers['budget_max'];
		}
		$b = isset( $answers['budget'] ) ? $answers['budget'] : '';
		if ( ! $b || 'unknown' === $b || 'high' === $b ) {
			return 0;
		}
		$caps = $this->budget_caps( $this->only_in_stock( $pool ) );
		if ( 'low' === $b ) {
			return (int) $caps['low'];
		}
		if ( 'mid' === $b ) {
			return (int) $caps['mid'];
		}
		return 0;
	}

	private function users_num( $users ) {
		if ( '1-3' === $users ) {
			return 3;
		}
		if ( '4-6' === $users ) {
			return 6;
		}
		if ( '7plus' === $users ) {
			return 8;
		}
		return 0;
	}

	private function area_num( $area ) {
		if ( 's' === $area ) {
			return 15;
		}
		if ( 'm' === $area ) {
			return 30;
		}
		if ( 'l' === $area ) {
			return 50;
		}
		return 0;
	}

	private function find_device_id( array $catalog, $model, $dtype ) {
		if ( ctype_digit( (string) $model ) ) {
			return (int) $model;
		}
		$kind = ( 'air' === $dtype ) ? 'air_device' : 'water_device';
		$q    = $this->norm( $model );
		foreach ( $catalog as $p ) {
			if ( $p['kind'] !== $kind ) {
				continue;
			}
			if ( $this->norm( $p['name'] ) === $q || ( $p['model'] && $this->norm( $p['model'] ) === $q ) ) {
				return (int) $p['id'];
			}
			if ( $p['model'] && false !== strpos( $this->norm( $p['name'] ), $q ) ) {
				return (int) $p['id'];
			}
		}
		return 0;
	}

	private function norm( $s ) {
		$s = mb_strtolower( (string) $s, 'UTF-8' );
		$s = str_replace( array( ' ', '-', '‌', '‏' ), '', $s );
		return $s;
	}

	private function sort_water_air( $a, $b ) {
		$sa = isset( $a['_score'] ) ? $a['_score'] : 0;
		$sb = isset( $b['_score'] ) ? $b['_score'] : 0;
		if ( $sa !== $sb ) {
			return $sb <=> $sa;
		}
		return $a['price'] <=> $b['price'];
	}

	private function sort_by_price( array $pool ) {
		usort(
			$pool,
			function ( $a, $b ) {
				return $a['price'] <=> $b['price'];
			}
		);
		return $pool;
	}

	private function empty_result( $reason, array $warnings, array $answers = array() ) {
		return array(
			'items'        => array(),
			'empty_reason' => $reason,
			'warnings'     => $warnings,
			'summary'      => $this->summary( $answers ),
			'need_summary' => $this->summary( $answers ),
		);
	}

	public function summary( array $answers ) {
		$labels = array(
			'water'     => 'دستگاه تصفیه آب',
			'air'       => 'دستگاه تصفیه هوا',
			'filter'    => 'فیلتر یا قطعه دستگاه فعلی',
			'other'     => 'سایر تجهیزات',
			'home'      => 'خانه',
			'office'    => 'محل کار',
			'pro'       => 'فضای پرتردد',
			'city'      => 'آب شهری',
			'well'      => 'چاه',
			'unknown'   => 'نامشخص',
			'taste'     => 'طعم و بو',
			'sediment'  => 'ذرات',
			'tds'       => 'املاح و رسوب',
			'general'   => 'انتخاب عمومی',
		);
		$bits = array();
		foreach ( array( 'path', 'place', 'water_source', 'need', 'device_type', 'model' ) as $k ) {
			if ( empty( $answers[ $k ] ) ) {
				continue;
			}
			$v      = $answers[ $k ];
			$bits[] = isset( $labels[ $v ] ) ? $labels[ $v ] : $v;
		}
		return $bits ? implode( ' · ', $bits ) : 'پاسخ‌های شما';
	}
}
