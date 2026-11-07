(function ($) {
	$('#as-hira-import-btn').on('click', function () {
		var $btn = $(this);
		var $status = $('#as-hira-import-status');
		var url = $('#_as_hira_url').val();
		if (!url) {
			$status.text('ابتدا لینک هیراب‌سان را وارد کنید.');
			return;
		}
		$btn.prop('disabled', true);
		$status.text('در حال دریافت…');
		$.post(asHiraSync.ajax, {
			action: 'as_hira_import',
			nonce: asHiraSync.nonce,
			product_id: $btn.data('product-id'),
			url: url,
		})
			.done(function (res) {
				if (res.success) {
					$status.text(res.data.message + ' — ' + res.data.synced);
				} else {
					$status.text(res.data && res.data.message ? res.data.message : 'خطا در دریافت.');
				}
			})
			.fail(function () {
				$status.text('خطای ارتباط با سرور.');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});
})(jQuery);
