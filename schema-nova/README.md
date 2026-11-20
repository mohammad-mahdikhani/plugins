# هوش‌اسکیما | Schema Nova

پلاگین وردپرس برای افزودن **اسکیمای JSON-LD** به هر قالبی، و **تغییر هوشمند تایتل و متا** بر اساس جستجوی واقعی کاربران.

مستند گوگل که این افزونه روی آن بنا شده:

- [Rich result report (Search Console)](https://support.google.com/webmasters/answer/7552505)
- [Structured data Google supports](https://developers.google.com/search/docs/appearance/structured-data/search-gallery)
- [Title links](https://developers.google.com/search/docs/appearance/title-link)
- [Meta descriptions / snippets](https://developers.google.com/search/docs/appearance/snippet)

خروجی همیشه یک گراف متصل است (`@graph` با `@id`)، نه چند اسکریپت پراکنده. این همان چیزی است که گوگل برای فهم موجودیت‌ها می‌خواهد و از پلاگین‌های «یک JSON جدا برای هر نوع» دقیق‌تر است.

قالب در کار نیست: مارک‌آپ با `wp_head` چاپ می‌شود.

## چه اسکیمایی ساخته می‌شود

| نوع | چه زمانی |
| --- | --- |
| Organization / OnlineStore / LocalBusiness / NewsMediaOrganization | هویت سایت + سیاست مرجوعی و ارسال فروشگاه |
| WebSite + SearchAction | جعبه جستجوی سایت‌لینک |
| WebPage / CollectionPage / SearchResultsPage / ProfilePage | هر URL |
| BreadcrumbList | مسیر منطقی صفحه، نه لزوماً ساختار URL |
| Article / NewsArticle / BlogPosting | نوشته‌ها، با author جداگانه طبق راهنمای گوگل |
| Product | ووکامرس + فیلدهای merchant listing (قیمت، موجودی، ارسال، مرجوعی) |
| FAQPage | بلوک FAQ، FAQ گوتنبرگ، یا تیتر سؤال‌مانند |
| VideoObject | یوتیوب، ویمو، آپارات، ویدئوی خود میزبان |
| Event, JobPosting, Recipe, Course, SoftwareApplication, Dataset, Review, Service, QAPage | از متاباکس هر صفحه |

FAQ هنوز در schema.org معتبر است؛ گوگل ریچ‌ریزالت FAQ را فقط برای سایت‌های محدود نشان می‌دهد. HowTo دیگر ریچ‌ریزالت نیست و عمداً برای CTR به آن تکیه نشده.

## تایتل و متای هوشمند

داده از **ابزاری شبیه گوگل آنالیتیکس** می‌آید که با API وصل می‌کنید:

1. **REST عمومی** — هر سرویسی که JSON بدهد (ماتومو، انبار داده، پنل ایرانی، پروکسی سرچ کنسول). فیلدها را نگاشت کنید.
2. **Google Search Console** — بهترین منبع اینتنت (کوئری، CTR، ایمپرشن، رتبه).
3. **GA4** — اگر فقط آنالیتیکس دارید.
4. **Webhook** — `POST /wp-json/schema-nova/v1/ingest` با هدر `X-Schema-Nova-Token`.

شکل JSON مورد انتظار:

```json
{
  "rows": [
    {
      "page": "https://example.com/laptop/",
      "query": "خرید لپ تاپ گیمینگ",
      "clicks": 18,
      "impressions": 640,
      "ctr": 0.028,
      "position": 5.4
    }
  ]
}
```

الگوریتم (عمداً محافظه‌کار، مطابق سیاست گوگل):

- فقط صفحاتی که ایمپرشن کافی دارند و CTRشان از CTR مورد انتظار آن رتبه پایین‌تر است.
- کوئری باید با متن صفحه همپوشانی داشته باشد (جلوگیری از تایتل گمراه‌کننده / cloaking).
- بدون keyword stuffing، تایتل یکتا، برند در انتها، سقف حدود ۶۰ کاراکتر.
- پیش‌فرض: **فقط پیشنهاد**. حالت Auto را وقتی روشن کنید که تاریخچه را دیده‌اید.
- اگر بعد از تغییر CTR افت کند، تایتل اصلی برمی‌گردد.
- حداکثر هر N روز (پیش‌فرض ۳) برای هر URL.

## نصب

1. پوشهٔ `schema-nova` را در `wp-content/plugins/` کپی کنید.
2. افزونه را فعال کنید.
3. **هویت سازمان** را پر کنید (نام، لوگو حداقل ۱۱۲×۱۱۲، آدرس، sameAs شبکه‌های اجتماعی).
4. در **منبع داده** API را وصل و Test کنید.
5. بهینه‌ساز را ابتدا روی Suggest بگذارید.

اگر Rank Math / Yoast هم اسکیما می‌زنند، گزینهٔ «غیرفعال کردن JSON-LD رقبا» را روشن کنید تا گراف تکراری نسازید.

## تست‌ها

```bash
php schema-nova/tests/run.php
```

بدون وردپرس اجرا می‌شود (استاب‌های سبک).
