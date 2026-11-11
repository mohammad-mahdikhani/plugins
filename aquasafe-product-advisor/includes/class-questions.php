<?php
class AS_Advisor_Questions {

	public static function tree() {
		$saved = get_option( 'as_advisor_questions', array() );
		return $saved ? $saved : self::defaults();
	}

	public static function defaults() {
		return array(
			'start_title' => 'محصول مناسب شما کدام است؟',
			'start_lead'  => 'چند سؤال کوتاه جواب بده تا گزینه‌های مناسب نیازت را پیدا کنیم.',
			'paths'       => array(
				array( 'id' => 'water', 'label' => 'می‌خواهم دستگاه تصفیه آب بخرم.' ),
				array( 'id' => 'air', 'label' => 'می‌خواهم دستگاه تصفیه هوا بخرم.' ),
				array( 'id' => 'filter', 'label' => 'برای دستگاه فعلی‌ام فیلتر یا قطعه می‌خواهم.' ),
				array( 'id' => 'other', 'label' => 'سایر محصولات و تجهیزات' ),
			),
		);
	}
}
