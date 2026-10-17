/**
 * Nahal Payment Bug Reporter — Telegram Bot API reverse proxy.
 *
 * Deploy this on YOUR Cloudflare account (Workers), then paste the
 * https://….workers.dev URL into plugin settings as «آدرس پروکسی API تلگرام».
 *
 * WordPress calls:  {this-worker}/bot{token}/{method}
 * Worker fetches:   https://api.telegram.org/bot{token}/{method}
 *
 * Do not use a public third-party proxy — it would see your bot token.
 * Do not publish this Worker as an open proxy for strangers.
 *
 * Body is forwarded as a stream so sendPhoto (multipart) still works.
 * Do not convert the body with request.text().
 */
export default {
  async fetch(request) {
    const url = new URL(request.url);

    if (url.pathname === '/' || url.pathname === '') {
      return new Response('npbr-telegram-proxy', {
        status: 200,
        headers: { 'content-type': 'text/plain; charset=utf-8' },
      });
    }

    if (!url.pathname.startsWith('/bot')) {
      return new Response('not found', { status: 404 });
    }

    const target = 'https://api.telegram.org' + url.pathname + url.search;
    const headers = new Headers();
    const contentType = request.headers.get('content-type');
    if (contentType) {
      headers.set('content-type', contentType);
    }

    const init = {
      method: request.method,
      headers,
      redirect: 'follow',
    };

    if (request.method !== 'GET' && request.method !== 'HEAD') {
      init.body = request.body;
    }

    return fetch(target, init);
  },
};
