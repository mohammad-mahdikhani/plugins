(function () {
	if (window.NPBRBuffer && window.NPBRBuffer.hooked) {
		return;
	}
	var b = window.NPBRBuffer = window.NPBRBuffer || { failed: [], errors: [], hooked: true };
	b.hooked = true;

	function push(list, item, max) {
		if (!item || list.length >= max) {
			return;
		}
		var key = (item.url || '') + '|' + (item.status || '') + '|' + (item.message || '');
		if (b._seen && b._seen[key]) {
			return;
		}
		b._seen = b._seen || {};
		b._seen[key] = true;
		list.push(item);
	}

	function now() {
		return new Date().toISOString();
	}

	function failed(url, status, message, tag) {
		if (!url) {
			return;
		}
		push(b.failed, {
			url: String(url).slice(0, 500),
			kind: 'resource',
			tag: tag || '',
			id: '',
			status: String(status || 'load-error'),
			message: String(message || 'resource failed').slice(0, 240),
			time: now(),
			payment: false
		}, 80);
	}

	function jsErr(message, source, type) {
		push(b.errors, {
			message: String(message || 'error').slice(0, 400),
			source: source ? String(source).slice(0, 500) : '',
			line: '',
			type: type || 'console.error'
		}, 60);
	}

	function parseStatus(text) {
		var m = String(text || '').match(/\b(4\d\d|5\d\d)\b/);
		return m ? m[1] : '';
	}

	function parseUrl(text) {
		var m = String(text || '').match(/https?:\/\/[^\s"'<>]+/i);
		return m ? m[0].replace(/[.,);]+$/, '') : '';
	}

	window.addEventListener('error', function (ev) {
		var t = ev.target || ev.srcElement;
		if (t && (t.src || t.href) && t.tagName) {
			failed(t.src || t.href, 'load-error', 'Failed to load resource', (t.tagName || '').toLowerCase());
			return;
		}
		jsErr(ev.message || 'error', ev.filename || '', 'error');
	}, true);

	window.addEventListener('unhandledrejection', function (ev) {
		var m = (ev.reason && ev.reason.message) || String(ev.reason || 'unhandledrejection');
		jsErr(m, '', 'unhandledrejection');
	});

	if (window.console) {
		['error', 'warn'].forEach(function (level) {
			var orig = console[level];
			if (typeof orig !== 'function') {
				return;
			}
			console[level] = function () {
				var parts = [];
				for (var i = 0; i < arguments.length; i++) {
					parts.push(typeof arguments[i] === 'string' ? arguments[i] : String(arguments[i]));
				}
				var text = parts.join(' ');
				var url = parseUrl(text);
				var status = parseStatus(text);
				if (url) {
					failed(url, status || 'console', text, 'console');
				}
				jsErr(text, url, 'console.' + level);
				return orig.apply(console, arguments);
			};
		});
	}

	if (window.fetch) {
		var origFetch = window.fetch;
		window.fetch = function () {
			return origFetch.apply(this, arguments).then(function (res) {
				if (res && !res.ok) {
					failed(res.url || '', String(res.status), 'HTTP ' + res.status + ' ' + (res.statusText || ''), 'fetch');
					jsErr('Failed to load resource: the server responded with a status of ' + res.status + ' (' + (res.url || '') + ')', res.url || '', 'console.error');
				}
				return res;
			});
		};
	}

	if (window.XMLHttpRequest && XMLHttpRequest.prototype) {
		var origOpen = XMLHttpRequest.prototype.open;
		var origSend = XMLHttpRequest.prototype.send;
		XMLHttpRequest.prototype.open = function (method, url) {
			this.__npbrUrl = url;
			return origOpen.apply(this, arguments);
		};
		XMLHttpRequest.prototype.send = function () {
			var xhr = this;
			xhr.addEventListener('loadend', function () {
				var code = xhr.status;
				if (code >= 400) {
					failed(xhr.__npbrUrl || '', String(code), 'HTTP ' + code, 'xhr');
					jsErr('Failed to load resource: the server responded with a status of ' + code + ' (' + (xhr.__npbrUrl || '') + ')', xhr.__npbrUrl || '', 'console.error');
				}
			});
			return origSend.apply(this, arguments);
		};
	}

	try {
		var po = new PerformanceObserver(function (list) {
			list.getEntries().forEach(function (entry) {
				var st = entry.responseStatus;
				if (st && st >= 400) {
					failed(entry.name, String(st), 'HTTP ' + st, entry.initiatorType || 'resource');
					jsErr('Failed to load resource: the server responded with a status of ' + st + ' (' + entry.name + ')', entry.name, 'console.error');
				}
			});
		});
		po.observe({ type: 'resource', buffered: true });
	} catch (e) {}
})();
