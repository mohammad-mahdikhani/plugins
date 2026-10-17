(function () {
	'use strict';

	var cfg = window.NPBR_CFG || {};
	var root = null;
	var modal = null;
	var overlay = null;
	var statusEl = null;
	var shotImg = null;
	var shotEmpty = null;
	var lastShot = '';
	var busy = false;

	function $(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function $all(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function buffer() {
		window.NPBRBuffer = window.NPBRBuffer || { failed: [], errors: [] };
		return window.NPBRBuffer;
	}

	function isPaymentUrl(url) {
		var u = String(url || '').toLowerCase();
		return /payment|checkout|gateway|digipay|zarinpal|zibal|idpay|nextpay|payping|saman|mellat|parsian|sadad|wc-checkout|order-pay/.test(u);
	}

	function visible(el) {
		if (!el) {
			return false;
		}
		var r = el.getBoundingClientRect();
		var st = window.getComputedStyle(el);
		return r.width > 0 && r.height > 0 && st.visibility !== 'hidden' && st.display !== 'none';
	}

	function fieldLabel(el) {
		if (!el) {
			return '';
		}
		if (el.id) {
			var byFor = document.querySelector('label[for="' + el.id.replace(/"/g, '') + '"]');
			if (byFor) {
				return (byFor.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80);
			}
		}
		var wrap = el.closest && el.closest('label');
		if (wrap) {
			return (wrap.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80);
		}
		return '';
	}

	function checkoutType() {
		var b = document.body.className || '';
		if (/\bcheckout-dore\b/.test(b)) {
			return 'course';
		}
		if (/\bcheckout-eyvand\b/.test(b)) {
			return 'event';
		}
		if (/\bcheckout-ordoo\b/.test(b)) {
			return 'camp';
		}
		if (/\bcheckout-mahsool\b/.test(b)) {
			return 'product';
		}
		if (/\bwoocommerce-order-pay\b/.test(b)) {
			return 'order-pay';
		}
		if (/\bwoocommerce-checkout\b/.test(b)) {
			return 'checkout';
		}
		return 'other';
	}

	function harvestPerformance() {
		var buf = buffer();
		if (!window.performance || !performance.getEntriesByType) {
			return;
		}
		var seen = {};
		buf.failed.forEach(function (row) {
			if (row && row.url) {
				seen[row.url] = true;
			}
		});
		performance.getEntriesByType('resource').forEach(function (entry) {
			var failed = entry.transferSize === 0 && entry.decodedBodySize === 0 && entry.duration < 20;
			if (!failed || seen[entry.name]) {
				return;
			}
			if (/\.(html|htm)(\?|$)/i.test(entry.name)) {
				return;
			}
			seen[entry.name] = true;
			buf.failed.push({
				url: entry.name,
				kind: 'resource',
				tag: entry.initiatorType || '',
				id: '',
				status: 'empty-transfer',
				message: 'resource did not load',
				time: new Date().toISOString(),
				payment: isPaymentUrl(entry.name),
			});
		});
	}

	function scanBrokenNodes() {
		var buf = buffer();
		$all('script[src], link[rel="stylesheet"], img[src], iframe[src]').forEach(function (el) {
			var url = el.currentSrc || el.src || el.href || '';
			if (!url) {
				return;
			}
			if (el.tagName === 'IMG' && el.complete && el.naturalWidth === 0) {
				buf.failed.push({
					url: url,
					kind: 'resource',
					tag: 'img',
					id: el.id || '',
					status: 'broken-image',
					message: 'image failed',
					time: new Date().toISOString(),
					payment: isPaymentUrl(url) || isPaymentUrl(el.id),
				});
			}
			if (el.tagName === 'IFRAME' && visible(el) === false && /pay|gateway|card/i.test(url + el.id + el.className)) {
				buf.failed.push({
					url: url,
					kind: 'iframe',
					tag: 'iframe',
					id: el.id || '',
					status: 'hidden-or-empty',
					message: 'payment iframe not visible',
					time: new Date().toISOString(),
					payment: true,
				});
			}
		});
	}

	function inspectPayment() {
		var box = document.getElementById('payment') || $('.woocommerce-checkout-payment') || $('.wc-block-components-radio-control');
		var methods = [];
		$all('.wc_payment_method, input[name="payment_method"]').forEach(function (el) {
			if (el.tagName === 'INPUT') {
				methods.push({
					id: el.value || el.id,
					label: fieldLabel(el),
					selected: !!el.checked,
				});
				return;
			}
			var input = $('input[name="payment_method"]', el);
			methods.push({
				id: input ? input.value : el.id || '',
				label: input ? fieldLabel(input) : (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
				selected: !!(input && input.checked),
			});
		});
		var uniq = [];
		var seen = {};
		methods.forEach(function (m) {
			var key = m.id + '|' + m.label;
			if (seen[key]) {
				return;
			}
			seen[key] = true;
			uniq.push(m);
		});
		var fields = [];
		var scope = box || document;
		$all('input, select, textarea, iframe', scope).forEach(function (el) {
			if (fields.length >= 40) {
				return;
			}
			if (el.closest && el.closest('#npbr-root')) {
				return;
			}
			var type = (el.type || el.tagName || '').toLowerCase();
			if (type === 'hidden' || type === 'submit' || type === 'button') {
				return;
			}
			var name = el.name || el.id || '';
			var empty = el.tagName === 'IFRAME' ? !el.src : !(el.value || '').length;
			var broken = el.tagName === 'IFRAME' && (!el.src || !visible(el));
			fields.push({
				name: name,
				id: el.id || '',
				type: type,
				tag: el.tagName.toLowerCase(),
				visible: visible(el),
				empty: empty,
				broken: broken,
			});
		});
		var emptyBox = !box || box.offsetHeight < 24;
		return {
			foundPaymentBox: !!box,
			emptyBox: emptyBox,
			noMethods: uniq.length === 0,
			checkoutType: checkoutType(),
			bodyClasses: (document.body.className || '').slice(0, 400),
			methods: uniq,
			fields: fields,
		};
	}

	function collect() {
		harvestPerformance();
		scanBrokenNodes();
		var buf = buffer();
		var failed = buf.failed.slice();
		failed.forEach(function (row) {
			row.payment = !!row.payment || isPaymentUrl(row.url) || isPaymentUrl(row.id);
		});
		return {
			note: ($('#npbr-note') && $('#npbr-note').value) || '',
			name: ($('#npbr-name') && $('#npbr-name').value) || '',
			phone: ($('#npbr-phone') && $('#npbr-phone').value) || '',
			pageUrl: location.href,
			pageTitle: document.title || '',
			userAgent: navigator.userAgent || '',
			viewport: window.innerWidth + 'x' + window.innerHeight,
			online: navigator.onLine !== false,
			failed: failed,
			errors: buf.errors.slice(),
			payment: inspectPayment(),
			capturedAt: new Date().toISOString(),
		};
	}

	function renderDiag(data) {
		var box = $('#npbr-diag');
		if (!box) {
			return;
		}
		var failed = data.failed || [];
		var errors = data.errors || [];
		var payN = failed.filter(function (r) { return r.payment; }).length;
		var html = '<h3>کدها و فایل‌هایی که لود نشدند (' + failed.length + '، پرداخت: ' + payN + ')</h3>';
		if (!failed.length) {
			html += '<p>تا این لحظه فایل لودنشده‌ای ثبت نشد. اگر فیلد پرداخت خالی است، باز هم گزارش را بفرستید.</p>';
		} else {
			html += '<ul>' + failed.slice(0, 12).map(function (r) {
				return '<li class="' + (r.payment ? 'is-pay' : '') + '">' + escapeHtml((r.payment ? '[پرداخت] ' : '') + (r.kind || '') + ' — ' + (r.status || '') + ' — ' + (r.url || '')) + '</li>';
			}).join('') + '</ul>';
		}
		html += '<h3>خطاهای اسکریپت (' + errors.length + ')</h3>';
		if (!errors.length) {
			html += '<p>خطای جاوااسکریپت ثبت نشد.</p>';
		} else {
			html += '<ul>' + errors.slice(0, 8).map(function (r) {
				return '<li>' + escapeHtml((r.type || 'error') + ': ' + (r.message || '')) + '</li>';
			}).join('') + '</ul>';
		}
		var p = data.payment || {};
		html += '<h3>فیلدهای پرداخت</h3><p>' +
			(p.foundPaymentBox ? 'باکس پرداخت پیدا شد' : 'باکس پرداخت پیدا نشد') +
			(p.emptyBox ? ' — خالی یا رندر نشده' : '') +
			(p.noMethods ? ' — هیچ درگاهی لود نشد' : '') +
			' — نوع صفحه: ' + escapeHtml(p.checkoutType || '') + '</p>';
		if (p.methods && p.methods.length) {
			html += '<ul>' + p.methods.map(function (m) {
				return '<li>' + escapeHtml((m.label || m.id || '') + (m.selected ? ' (انتخاب‌شده)' : '')) + '</li>';
			}).join('') + '</ul>';
		}
		box.innerHTML = html;
	}

	function escapeHtml(s) {
		return String(s || '').replace(/[&<>"']/g, function (ch) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
		});
	}

	function maskSensitive() {
		var changed = [];
		$all('input[type="password"], input[name*="card"], input[id*="card"], input[autocomplete="cc-number"], input[autocomplete="cc-csc"]').forEach(function (el) {
			changed.push({ el: el, value: el.value });
			el.value = el.value ? '••••' : el.value;
		});
		return changed;
	}

	function restoreMasked(changed) {
		changed.forEach(function (item) {
			item.el.value = item.value;
		});
	}

	function setReporterHidden(hide) {
		if (!root) {
			return;
		}
		root.style.visibility = hide ? 'hidden' : '';
	}

	function captureShot() {
		var changed = maskSensitive();
		setReporterHidden(true);
		var target = document.body;
		var opts = {
			useCORS: true,
			allowTaint: true,
			scale: Math.min(2, window.devicePixelRatio || 1),
			windowWidth: document.documentElement.clientWidth,
			height: Math.min(document.documentElement.scrollHeight, 2400),
			backgroundColor: '#ffffff',
		};
		var run = window.html2canvas
			? window.html2canvas(target, opts)
			: Promise.reject(new Error('html2canvas missing'));
		return run
			.then(function (canvas) {
				return canvas.toDataURL('image/jpeg', 0.72);
			})
			.catch(function () {
				return fallbackShot();
			})
			.then(function (uri) {
				setReporterHidden(false);
				restoreMasked(changed);
				lastShot = uri || '';
				if (shotImg && lastShot) {
					shotImg.src = lastShot;
					shotImg.hidden = false;
					if (shotEmpty) {
						shotEmpty.hidden = true;
					}
				}
				return lastShot;
			});
	}

	function fallbackShot() {
		var canvas = document.createElement('canvas');
		canvas.width = Math.min(window.innerWidth, 1200);
		canvas.height = Math.min(window.innerHeight, 900);
		var ctx = canvas.getContext('2d');
		ctx.fillStyle = '#f4f7f3';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.fillStyle = '#14221c';
		ctx.font = '16px Tahoma, sans-serif';
		ctx.textAlign = 'right';
		ctx.fillText(document.title || 'صفحه پرداخت', canvas.width - 24, 40);
		ctx.font = '12px Tahoma, sans-serif';
		ctx.fillStyle = '#4b6358';
		ctx.fillText(location.href, canvas.width - 24, 64);
		ctx.fillText('اسکرین پشتیبان — ' + new Date().toLocaleString('fa-IR'), canvas.width - 24, 88);
		var pay = document.getElementById('payment');
		if (pay) {
			var r = pay.getBoundingClientRect();
			ctx.strokeStyle = '#3f5d45';
			ctx.strokeRect(Math.max(10, r.left), Math.max(110, r.top + 40), Math.max(40, r.width), Math.max(40, r.height));
			ctx.fillText('باکس پرداخت', canvas.width - 24, 112);
		}
		return canvas.toDataURL('image/jpeg', 0.8);
	}

	function crc32(bytes) {
		var table = crc32.table;
		if (!table) {
			table = crc32.table = new Uint32Array(256);
			for (var i = 0; i < 256; i++) {
				var c = i;
				for (var k = 0; k < 8; k++) {
					c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
				}
				table[i] = c >>> 0;
			}
		}
		var crc = 0xffffffff;
		for (var n = 0; n < bytes.length; n++) {
			crc = table[(crc ^ bytes[n]) & 0xff] ^ (crc >>> 8);
		}
		return (crc ^ 0xffffffff) >>> 0;
	}

	function strBytes(text) {
		return new TextEncoder().encode(text);
	}

	function u32(n) {
		return new Uint8Array([n & 255, (n >>> 8) & 255, (n >>> 16) & 255, (n >>> 24) & 255]);
	}

	function u16(n) {
		return new Uint8Array([n & 255, (n >>> 8) & 255]);
	}

	function concat(parts) {
		var len = 0;
		parts.forEach(function (p) { len += p.length; });
		var out = new Uint8Array(len);
		var off = 0;
		parts.forEach(function (p) {
			out.set(p, off);
			off += p.length;
		});
		return out;
	}

	function zipStore(files) {
		var locals = [];
		var centrals = [];
		var offset = 0;
		files.forEach(function (file) {
			var name = strBytes(file.name);
			var data = file.data;
			var crc = crc32(data);
			var local = concat([
				u32(0x04034b50), u16(20), u16(0), u16(0), u16(0), u16(0),
				u32(crc), u32(data.length), u32(data.length), u16(name.length), u16(0),
				name, data,
			]);
			locals.push(local);
			var central = concat([
				u32(0x02014b50), u16(20), u16(20), u16(0), u16(0), u16(0), u16(0),
				u32(crc), u32(data.length), u32(data.length), u16(name.length), u16(0), u16(0), u16(0), u16(0), u32(0), u32(offset),
				name,
			]);
			centrals.push(central);
			offset += local.length;
		});
		var central = concat(centrals);
		var end = concat([
			u32(0x06054b50), u16(0), u16(0), u16(files.length), u16(files.length),
			u32(central.length), u32(offset), u16(0),
		]);
		return concat(locals.concat([central, end]));
	}

	function dataUriToBytes(uri) {
		var parts = String(uri).split(',');
		var bin = atob(parts[1] || '');
		var out = new Uint8Array(bin.length);
		for (var i = 0; i < bin.length; i++) {
			out[i] = bin.charCodeAt(i);
		}
		return out;
	}

	function reportHtml(data) {
		var failed = (data.failed || []).map(function (r) {
			return '<tr><td>' + escapeHtml(r.payment ? 'پرداخت' : 'عمومی') + '</td><td>' + escapeHtml(r.kind) + '</td><td>' + escapeHtml(r.status) + '</td><td dir="ltr">' + escapeHtml(r.url) + '</td></tr>';
		}).join('') || '<tr><td colspan="4">مورد لودنشده‌ای ثبت نشد.</td></tr>';
		var errors = (data.errors || []).map(function (r) {
			return '<tr><td>' + escapeHtml(r.type) + '</td><td>' + escapeHtml(r.message) + '</td><td dir="ltr">' + escapeHtml(r.source) + '</td></tr>';
		}).join('') || '<tr><td colspan="3">خطای اسکریپت ثبت نشد.</td></tr>';
		var shot = data.screenshot ? '<img style="max-width:100%" src="' + data.screenshot + '" alt="screenshot">' : '<p>اسکرین نیست</p>';
		return '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>گزارش خطای پرداخت</title></head><body style="font-family:Tahoma,sans-serif;padding:24px">' +
			'<h1>گزارش خطای پرداخت نهال</h1>' +
			'<p>' + escapeHtml(data.pageTitle) + '<br dir="ltr">' + escapeHtml(data.pageUrl) + '<br>' + escapeHtml(data.capturedAt) + '</p>' +
			'<h2>شرح کاربر</h2><pre>' + escapeHtml(data.note) + '</pre>' +
			'<p>نام: ' + escapeHtml(data.name || '—') + ' — موبایل: ' + escapeHtml(data.phone || '—') + '</p>' +
			'<h2>کدهای لودنشده</h2><table border="1" cellpadding="6">' + failed + '</table>' +
			'<h2>خطاهای اسکریپت</h2><table border="1" cellpadding="6">' + errors + '</table>' +
			'<h2>اسکرین‌شات</h2>' + shot +
			'</body></html>';
	}

	function downloadBlob(blob, filename) {
		var url = URL.createObjectURL(blob);
		clickDownload(url, filename);
		return url;
	}

	function clickDownload(url, filename) {
		var a = document.createElement('a');
		a.href = url;
		a.download = filename;
		a.rel = 'noopener';
		a.style.display = 'none';
		document.body.appendChild(a);
		a.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
		setTimeout(function () {
			if (a.parentNode) {
				a.parentNode.removeChild(a);
			}
		}, 4000);
	}

	function showDownloadLinks(zipUrl, zipName, shotUrl, htmlUrl) {
		var box = $('#npbr-downloads');
		if (!box) {
			return;
		}
		box.hidden = false;
		box.innerHTML =
			'<a class="npbr-btn npbr-btn-dark" id="npbr-zip-link" href="' + zipUrl + '" download="' + zipName + '">دانلود فایل ZIP</a>' +
			(shotUrl ? '<a class="npbr-btn npbr-btn-light" id="npbr-shot-link" href="' + shotUrl + '" download="nahal-payment-screenshot.jpg">دانلود اسکرین صفحه</a>' : '') +
			(htmlUrl ? '<a class="npbr-btn npbr-btn-light" id="npbr-html-link" href="' + htmlUrl + '" target="_blank" rel="noopener">باز کردن گزارش</a>' : '');
	}

	function downloadViaServer(data) {
		if (!cfg.ajax) {
			return false;
		}
		var iframe = document.getElementById('npbr-dl-iframe');
		if (!iframe) {
			iframe = document.createElement('iframe');
			iframe.id = 'npbr-dl-iframe';
			iframe.name = 'npbr-dl-iframe';
			iframe.setAttribute('hidden', 'hidden');
			document.body.appendChild(iframe);
		}
		var form = document.getElementById('npbr-dl-form');
		if (form && form.parentNode) {
			form.parentNode.removeChild(form);
		}
		form = document.createElement('form');
		form.id = 'npbr-dl-form';
		form.method = 'POST';
		form.action = cfg.ajax;
		form.target = 'npbr-dl-iframe';
		form.acceptCharset = 'UTF-8';
		form.style.display = 'none';
		function add(name, value) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = value;
			form.appendChild(input);
		}
		add('action', 'npbr_download');
		add('nonce', cfg.nonce || '');
		var copy = {};
		Object.keys(data).forEach(function (key) {
			if (key !== 'screenshot') {
				copy[key] = data[key];
			}
		});
		add('payload', JSON.stringify(copy));
		if (data.screenshot) {
			add('screenshot', data.screenshot);
		}
		document.body.appendChild(form);
		form.submit();
		return true;
	}

	function downloadZip(data) {
		var files = [
			{ name: 'report.json', data: strBytes(JSON.stringify(data, null, 2)) },
			{ name: 'report.html', data: strBytes(reportHtml(data)) },
		];
		if (data.screenshot) {
			files.push({ name: 'screenshot.jpg', data: dataUriToBytes(data.screenshot) });
		}
		var zip = zipStore(files);
		var blob = new Blob([zip], { type: 'application/octet-stream' });
		var stamp = (data.capturedAt || 'report').replace(/[:.]/g, '-');
		var zipName = 'nahal-payment-bug-' + stamp + '.zip';
		var zipUrl = downloadBlob(blob, zipName);
		var shotUrl = '';
		if (data.screenshot) {
			shotUrl = data.screenshot;
			clickDownload(shotUrl, 'nahal-payment-screenshot.jpg');
		}
		var htmlUrl = URL.createObjectURL(new Blob([reportHtml(data)], { type: 'text/html;charset=utf-8' }));
		showDownloadLinks(zipUrl, zipName, shotUrl, htmlUrl);
		downloadViaServer(data);
		return zipUrl;
	}

	function setStatus(text, isError) {
		if (!statusEl) {
			return;
		}
		statusEl.textContent = text || '';
		statusEl.classList.toggle('is-error', !!isError);
	}

	function requireNote(data) {
		if (cfg.requireNote !== false && !String(data.note || '').trim()) {
			setStatus('لطفاً همین مورد را بنویسید؛ بدون شرح، مدیر نمی‌تواند پیگیری کند.', true);
			return false;
		}
		return true;
	}

	function buildPayload() {
		var data = collect();
		data.screenshot = lastShot;
		return data;
	}

	function withShot(thenFn) {
		if (busy) {
			return;
		}
		var data = collect();
		if (!requireNote(data)) {
			return;
		}
		busy = true;
		setStatus('اسکرین صفحه در حال تهیه است…');
		$all('[data-npbr-download], [data-npbr-send]').forEach(function (b) { b.disabled = true; });
		captureShot()
			.then(function () {
				data = buildPayload();
				return thenFn(data);
			})
			.catch(function (err) {
				setStatus((err && err.message) || 'خطا در تهیه گزارش', true);
			})
			.then(function () {
				busy = false;
				$all('[data-npbr-download], [data-npbr-send]').forEach(function (b) { b.disabled = false; });
			});
	}

	function sendToManager(data) {
		setStatus('در حال ارسال برای مدیر…');
		if (!cfg.ajax) {
			downloadZip(data);
			setStatus('حالت پیش‌نمایش: گزارش دانلود شد. روی سایت واقعی برای مدیر ایمیل می‌شود.');
			return Promise.resolve();
		}
		var body = new URLSearchParams();
		body.set('action', 'npbr_submit');
		body.set('nonce', cfg.nonce || '');
		body.set('payload', JSON.stringify(data));
		return fetch(cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		}).then(function (res) {
			return res.json().then(function (json) {
				if (!res.ok || !json || !json.success) {
					throw new Error((json && json.data && json.data.message) || 'ارسال ناموفق بود');
				}
				setStatus(json.data.message || 'ارسال شد.');
			});
		}).catch(function (err) {
			downloadZip(data);
			setStatus((err && err.message ? err.message + ' — ' : '') + 'گزارش روی دستگاه شما دانلود شد تا بتوانید خودتان برای مدیر بفرستید.', true);
		});
	}

	function openModal() {
		if (!root) {
			return;
		}
		root.hidden = false;
		overlay.hidden = false;
		modal.hidden = false;
		renderDiag(collect());
		setStatus('');
		var note = $('#npbr-note');
		if (note) {
			note.focus();
		}
	}

	function closeModal() {
		if (!modal) {
			return;
		}
		modal.hidden = true;
		overlay.hidden = true;
	}

	function placeNearPayment() {
		if (cfg.showNearPay === false) {
			return;
		}
		var box = document.getElementById('payment');
		if (!box || box.querySelector('.npbr-pay-slot')) {
			return;
		}
		var slot = document.createElement('div');
		slot.className = 'npbr-pay-slot';
		slot.innerHTML = '<button type="button" class="npbr-pay-btn" data-npbr-open>گزارش خطا در فیلد پرداخت</button>';
		box.insertBefore(slot, box.firstChild);
	}

	function bind() {
		root = document.getElementById('npbr-root');
		if (!root) {
			return;
		}
		modal = $('.npbr-modal', root);
		overlay = $('.npbr-overlay', root);
		statusEl = $('#npbr-status', root);
		shotImg = $('#npbr-shot', root);
		shotEmpty = $('#npbr-shot-empty', root);
		root.hidden = false;
		if (cfg.showFloating === false) {
			var fab = $('.npbr-fab', root);
			if (fab) {
				fab.hidden = true;
			}
		}
		document.addEventListener('click', function (ev) {
			var open = ev.target.closest && ev.target.closest('[data-npbr-open]');
			if (open) {
				ev.preventDefault();
				openModal();
			}
			if (ev.target.closest && ev.target.closest('[data-npbr-close]')) {
				closeModal();
			}
			if (ev.target.closest && ev.target.closest('[data-npbr-download]')) {
				ev.preventDefault();
				ev.stopPropagation();
				withShot(function (data) {
					downloadZip(data);
					setStatus('اگر دانلود شروع نشد، لینک‌های آبی/سبز زیر اسکرین را بزنید.');
				});
			}
			if (ev.target.closest && ev.target.closest('[data-npbr-send]')) {
				ev.preventDefault();
				ev.stopPropagation();
				withShot(sendToManager);
			}
		});
		document.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape') {
				closeModal();
			}
		});
		placeNearPayment();
		setTimeout(placeNearPayment, 1200);
		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout payment_method_selected', placeNearPayment);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
})();
