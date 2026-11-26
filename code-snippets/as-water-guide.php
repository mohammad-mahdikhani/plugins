<?php
/**
 * AquaSafe shopper guide.
 *
 * This is not a plugin. It is appended to the live Code Snippet
 * «استایل داخلی مینیمال آکواسیف» (id 6) and renders only on
 * https://aquasafe.ir/product-advisor/
 *
 * Product names, prices, images and links are read from the public
 * WooCommerce Store API at runtime. Spec lines below are taken from
 * those same product pages.
 */

if ( ! function_exists( 'as_water_guide_on' ) ) {
	function as_water_guide_on() {
		if ( ! function_exists( 'is_page' ) ) {
			return false;
		}
		return is_page( 'product-advisor' ) || is_page( 2122 );
	}

	function as_water_guide_css() {
		if ( ! as_water_guide_on() ) {
			return;
		}
		echo '<style id="as-guide-css">';
		echo <<<'ASGCSS'
body.page-id-2122 .as-advisor-fab-host{display:none!important}
.as-water-guide{--navy:#1e4b9a;--navy2:#163d7a;--ink:#44566a;--mute:#5b7388;--teal:#76cdd9;--mint:#f4fbfb;--mint2:#eefbfc;--line:#e7eef2;--card:#fff;direction:rtl;text-align:right;color:var(--ink);font-family:"Peyda","Vazirmatn",Tahoma,sans-serif;max-width:1080px;margin:4px auto 40px;padding:0 12px 12px;word-break:keep-all;overflow-wrap:normal;hyphens:none;line-break:strict;box-sizing:border-box}
.as-water-guide *,.as-water-guide *::before,.as-water-guide *::after{box-sizing:border-box;word-break:keep-all;overflow-wrap:normal;hyphens:none}
.as-water-guide h2,.as-water-guide h3,.as-water-guide p{word-break:keep-all}
.as-g-hero{background:linear-gradient(180deg,#f7fcfc 0%,#fff 70%);border:1px solid var(--line);border-radius:28px;padding:22px 18px 18px;box-shadow:0 18px 50px rgba(30,75,154,.06)}
.as-g-kicker{margin:0 0 6px;color:var(--navy);font-size:12px;font-weight:700;letter-spacing:0}
.as-g-hero h2{margin:0 0 8px;color:var(--navy);font-size:clamp(22px,3vw,32px);line-height:1.7;font-weight:800}
.as-g-lead{margin:0;line-height:1.95;font-size:15px;max-width:68ch}
.as-g-block{margin-top:18px}
.as-g-q{margin:0 0 8px;color:var(--navy);font-size:15px;font-weight:800}
.as-g-choices{display:grid;grid-template-columns:1fr;gap:8px}
.as-water-guide button.as-g-choice{display:flex;flex-direction:column;align-items:flex-start;gap:2px;width:100%;text-align:right;border:1px solid #d7e4ea;background:#fff!important;color:var(--navy)!important;border-radius:16px;padding:12px 14px;font-family:inherit;font-size:15px;font-weight:700;line-height:1.6;cursor:pointer;box-shadow:none}
.as-water-guide button.as-g-choice small{font-weight:500;color:var(--mute);font-size:12px;line-height:1.7}
.as-water-guide button.as-g-choice:hover,.as-water-guide button.as-g-choice:focus{border-color:var(--teal)!important;background:var(--mint2)!important;outline:none}
.as-water-guide button.as-g-choice.is-on{border-color:var(--teal)!important;background:var(--mint)!important;box-shadow:inset 0 0 0 1px var(--teal)}
.as-g-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:16px}
.as-water-guide button.as-g-go{border:0;border-radius:999px;background:var(--navy)!important;color:#fff!important;font-family:inherit;font-weight:800;font-size:15px;padding:12px 20px;min-height:48px;cursor:pointer}
.as-water-guide button.as-g-go:hover,.as-water-guide button.as-g-go:focus{background:var(--navy2)!important;outline:2px solid var(--teal);outline-offset:2px}
.as-water-guide button.as-g-go:disabled{opacity:.55;cursor:progress}
.as-g-count{color:var(--mute);font-size:13px}
.as-g-nav{position:sticky;top:72px;z-index:30;display:flex;gap:8px;overflow:auto;padding:10px 0;margin:0 0 8px;background:rgba(251,254,254,.94);-webkit-overflow-scrolling:touch}
.as-g-nav a{flex:0 0 auto;white-space:nowrap;text-decoration:none;color:var(--navy)!important;background:#fff;border:1px solid var(--line);border-radius:999px;padding:8px 12px;font-size:13px;font-weight:700}
.as-g-nav a:hover{border-color:var(--teal);background:var(--mint)}
.as-g-result{margin-top:8px}
.as-g-why{background:var(--mint);border:1px solid #d8f4f8;border-radius:18px;padding:12px 14px;line-height:1.9;margin:0 0 12px}
.as-g-grid{display:grid;grid-template-columns:1fr;gap:12px}
.as-g-card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:12px;display:grid;grid-template-columns:96px 1fr;gap:12px;min-width:0}
.as-g-card.is-pick{border-color:var(--teal);box-shadow:0 12px 32px rgba(118,205,217,.18)}
.as-g-card img{width:96px;height:96px;object-fit:contain;background:var(--mint);border-radius:16px}
.as-g-card h3{margin:0 0 4px;color:var(--navy);font-size:16px;line-height:1.65;font-weight:800}
.as-g-badge{display:inline-block;background:var(--navy);color:#fff;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px;margin-bottom:6px}
.as-g-price{margin:0 0 6px;color:var(--navy);font-weight:800}
.as-g-card ul{margin:0 0 8px;padding:0 18px 0 0;line-height:1.75;font-size:13px}
.as-g-card li{margin:0 0 2px}
.as-g-actions{display:flex;flex-wrap:wrap;gap:8px}
.as-water-guide a.as-g-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 14px;border-radius:999px;background:var(--navy);color:#fff!important;text-decoration:none;font-weight:800;font-size:13px}
.as-water-guide a.as-g-btn.ghost{background:var(--mint2);color:var(--navy)!important;border:1px solid var(--teal)}
.as-g-acc{margin-top:10px;border:1px solid var(--line);border-radius:20px;background:#fff;overflow:hidden}
.as-water-guide button.as-g-accbtn{width:100%;display:flex;justify-content:space-between;align-items:center;gap:12px;text-align:right;background:#fff!important;color:var(--navy)!important;border:0;padding:14px 16px;font-family:inherit;font-size:16px;font-weight:800;cursor:pointer;line-height:1.6}
.as-water-guide button.as-g-accbtn span.mark{flex:0 0 auto;width:28px;height:28px;border-radius:50%;background:var(--mint);color:var(--navy);display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.as-g-panel{padding:0 16px 16px;line-height:1.95}
.as-g-panel h3{margin:12px 0 6px;color:var(--navy);font-size:15px}
.as-g-panel p{margin:0 0 10px}
.as-g-note{font-size:13px;color:var(--mute);line-height:1.85}
.as-g-mini{display:grid;grid-template-columns:1fr;gap:10px;margin-top:8px}
.as-g-mini article{border:1px solid var(--line);border-radius:16px;padding:10px 12px;background:var(--mint);min-width:0}
.as-g-mini h3{margin:0 0 4px;font-size:14px}
.as-g-mini a{color:var(--navy);font-weight:800}
.as-g-check{background:#fff;border:1px dashed var(--teal);border-radius:16px;padding:10px 12px;margin:0 0 8px}
.as-g-status{font-size:13px;color:var(--mute)}
@media(min-width:800px){
.as-g-hero{padding:28px 28px 22px}
.as-g-choices{grid-template-columns:repeat(3,minmax(0,1fr))}
.as-g-choices.four{grid-template-columns:repeat(4,minmax(0,1fr))}
.as-g-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
.as-g-card{grid-template-columns:1fr;align-content:start}
.as-g-card img{width:100%;height:140px}
.as-g-mini{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:799px){
.as-g-nav{top:64px}
.as-g-card{grid-template-columns:76px 1fr}
.as-g-card img{width:76px;height:76px}
}
ASGCSS;
		echo '</style>';
	}

	function as_water_guide_js() {
		if ( ! as_water_guide_on() ) {
			return;
		}
		echo '<script id="as-guide-js" data-no-defer="1">';
		echo <<<'ASGJS'
(function(){
"use strict";
var IDS=[811,807,803,1893,788,801,1712,830,833,1827,1248,1839,1889,1244,1837,1749,900,904,919,877,873,871,1016];
var FACTS={
811:["پنج مرحله اسمز معکوس","تا ۲۰۰ لیتر در شبانه‌روز","بدنه استیل ضدزنگ","ابعاد ۳۷ در ۲۰ در ۴۵ سانتی‌متر","مخزن ۱۲ لیتری","برق ۲۲۰ ولت و مصرف ۲۴ ولت"],
807:["پنج مرحله اسمز معکوس برای آب شهری","تا ۲۰۰ لیتر در شبانه‌روز","دقت ۰٫۰۰۰۱ میکرون در برگه محصول"],
803:["اسمز معکوس","۲۰۰ لیتر در شبانه‌روز","مخزن ۹ تا ۱۲ لیتری","پمپ تأمین فشار"],
1893:["زیرسینکی، پنج مرحله اسمز معکوس","پمپ برای فشار آب پایین","مخزن تحت فشار ۱۲ لیتری","دقت حدود ۰٫۰۰۰۱ میکرون"],
788:["دقت ۰٫۰۰۰۱ میکرون","مناسب فشار آب پایین","مخزن ۱۲ لیتری","فیلتر اینلاین با تعویض آسان"],
801:["پنج مرحله اسمز معکوس","۲۰۰ لیتر در شبانه‌روز","مخزن فلزی ۱۰ تا ۱۲ لیتر","پمپ و اتصال سریع","گارانتی ۳۶ ماه در برگه محصول"],
1712:["۲۰۰ لیتر در شبانه‌روز","مخزن فلزی ۱۲ لیتری","پمپ تأمین فشار","شیر برقی و محدودکننده جریان"],
830:["۶۰۰ لیتر در شبانه‌روز، حدود ۲۵ لیتر در ساعت","دو ممبران ۷۵ گالن","بدنه استیل","ابعاد ۳۷ در ۲۰ در ۷۸ سانتی‌متر","برق ۲۲۰ ولت"],
833:["۱۲۰۰ لیتر در شبانه‌روز، حدود ۵۰ لیتر در ساعت","چهار ممبران ۷۵ گالن","بدنه استیل","ابعاد ۳۷ در ۲۰ در ۷۸ سانتی‌متر","مناسب فشار آب پایین"],
1827:["پیش‌تصفیه سه‌مرحله‌ای روی ورودی ساختمان","خروجی یک اینچ","بدنه پلی‌پروپیلن و اورینگ سیلیکونی","برای شن، رسوب و کلر قبل از دستگاه نوشیدنی"],
1248:["هوزینگ پلی‌پروپیلن برای کارتریج قطر ۴٫۵ اینچ","دنده یک اینچ","طول‌های ۱۰ و ۲۰ اینچ در برگه محصول","برای جریان زیاد و رسوب سنگین"],
1839:["محفظه کارتریج ۱۰ اینچ","بدنه پلاستیک مقاوم و درپوش رزوه‌ای","معمولاً اتصال ۳/۴ اینچ","برای الیافی، کربن و بلاک خانگی"],
1889:["محفظه ممبران مرحله چهارم","پلاستیک فودگرید و اورینگ دوبل","فشار کاری حدود ۲۰۰ پی‌اس‌آی در برگه محصول","خانگی و نیمه‌صنعتی"],
1244:["پلی‌پروپیلن یا ABS درجه غذایی","دو اورینگ","اتصال ۱/۴ و ۱/۸ اینچ","تا ۱۰۰ پی‌اس‌آی، استاندارد NSF/ANSI 58"],
1837:["محفظه ممبران با اتصال دنده‌ای ۱/۸ اینچ","پلاستیک مهندسی و اورینگ","وقتی فیتینگ دستگاه ریزرزوه است"],
1749:["مرحله اول","الیاف پلی‌پروپیلن، دقت ۵ میکرون","طول ۱۲٫۵ اینچ","عمر مفید ۶ ماه در برگه محصول"],
900:["مرحله دوم","کربن گیاهی پوسته نارگیل","گرفتن کلر، بو و طعم","عمر مفید ۶ ماه در برگه محصول"],
904:["مرحله سوم","الیاف پلی‌پروپیلن، دقت ۱ میکرون","محافظ ممبران","عمر مفید ۶ ماه در برگه محصول"],
919:["غشای TFC، ۱۰ اینچ، ۵۰ یا ۷۵ گالن","حذف تا ۹۶ درصد در برگه محصول","بازه پی‌اچ ورودی ۴ تا ۱۱","حداکثر TDS ورودی ۲۰۰۰ ppm"],
877:["مرحله پنجم، اینلاین با اتصال QC","کربن جامد درجه غذایی","عمر یک سال یا ۷۰۰۰ لیتر"],
873:["مرحله پنجم، اینلاین با اتصال QC","کربن گیاهی پوسته نارگیل","عمر یک سال یا حدود ۵۵۰۰ لیتر"],
871:["پک سه‌تایی اینلاین: الیافی ۵، کربن UDF، الیافی ۱","عمر مفید ۶ ماه","برای دستگاه‌های پنج‌مرحله‌ای مثل لوتوس"],
1016:["کربن بیگ‌بلو ۲۰ اینچ، قطر ۴٫۵","برای هوزینگ بیگ‌بلو","عمر ۶ تا ۱۲ ماه در برگه محصول"]
};
var PICK={
home:{taste:[811,1893,807],sediment:[811,801,788],tds:[788,1893,801],unknown:[811,807,788]},
cafe:{taste:[833,830,1827],sediment:[1827,833,1248],tds:[833,830,1827],unknown:[833,830,1827]},
office:{taste:[830,1712,811],sediment:[830,1827,833],tds:[830,833,788],unknown:[830,1712,833]}
};
var PH={
"home|low":[1893,811,801],
"home|high":[788,803,801],
"cafe|low":[833,830,1893],
"cafe|high":[833,1827,830],
"office|low":[830,1893,811],
"office|high":[830,833,788]
};
var state={place:"",problem:"",ph:"",products:{},ready:false};
function $(s,r){return (r||document).querySelector(s)}
function el(tag,cls,text){var n=document.createElement(tag);if(cls)n.className=cls;if(text!=null)n.textContent=text;return n}
function money(n){var x=Number(n);if(!isFinite(x)||x<=0)return "";return x.toLocaleString("fa-IR")+" تومان"}
function cleanName(s){var t=document.createElement("textarea");t.innerHTML=s||"";return (t.value||"").replace(/\s+/g," ").trim()}
function claimHost(){
document.querySelectorAll('[data-as-advisor="embedded"]').forEach(function(n){n.removeAttribute("data-as-advisor")});
var h=document.getElementById("as-water-guide");
if(!h){
var old=document.querySelector(".as-advisor");
if(old && !old.classList.contains("as-advisor-modal")){h=old}
}
if(!h){
var main=document.querySelector(".page-content")||document.querySelector("main");
if(!main)return null;
h=document.createElement("div");
main.insertBefore(h,main.firstChild);
}
h.id="as-water-guide";
h.className="as-water-guide";
return h;
}
function choice(group,val,title,sub){
var b=el("button","as-g-choice");
b.type="button";
b.setAttribute("data-group",group);
b.setAttribute("data-val",val);
b.setAttribute("aria-pressed","false");
b.appendChild(el("b",null,title));
if(sub)b.appendChild(el("small",null,sub));
return b;
}
function shell(root){
root.innerHTML="";
var hero=el("section","as-g-hero");
hero.appendChild(el("p","as-g-kicker","ابزار خرید آکواسیف"));
hero.appendChild(el("h2",null,"کدام دستگاه برای این آب؟"));
hero.appendChild(el("p","as-g-lead","جای استفاده، مشکل آب و پی‌اچ را مشخص کنید. پیشنهاد از دستگاه‌های همین فروشگاه می‌آید، با نام، قیمت و لینک زنده. بخش‌های پرسش، سنجش آب، تعویض فیلتر و هوزینگ همین‌جا باز می‌شوند."));
var q1=el("div","as-g-block");
q1.appendChild(el("p","as-g-q","۱. دستگاه کجا کار می‌کند؟"));
var c1=el("div","as-g-choices");
c1.appendChild(choice("place","home","خانه","آشپزخانه و آب آشامیدن"));
c1.appendChild(choice("place","cafe","کافه و رستوران","مصرف پیوسته در ساعت شلوغ"));
c1.appendChild(choice("place","office","دفتر و محل کار","آبدارخانه یا آبخوری"));
q1.appendChild(c1);
var q2=el("div","as-g-block");
q2.appendChild(el("p","as-g-q","۲. مشکل آب بیشتر کدام است؟"));
var c2=el("div","as-g-choices four");
c2.appendChild(choice("problem","taste","طعم یا بو","کلر یا بوی نامطبوع"));
c2.appendChild(choice("problem","sediment","گل و ذره","شن، گل‌ولای، کدری"));
c2.appendChild(choice("problem","tds","رسوب و سختی","املاح، جرم کتری"));
c2.appendChild(choice("problem","unknown","هنوز دقیق نیست","با همین اطلاعات ادامه بده"));
q2.appendChild(c2);
var q3=el("div","as-g-block");
q3.appendChild(el("p","as-g-q","۳. پی‌اچ آب را می‌دانید؟"));
var c3=el("div","as-g-choices four");
c3.appendChild(choice("ph","unknown","نمی‌دانم","اشکالی ندارد"));
c3.appendChild(choice("ph","low","کمتر از ۶٫۵","آب متمایل به اسیدی"));
c3.appendChild(choice("ph","mid","۶٫۵ تا ۸٫۵","بازه معمول آب شهری"));
c3.appendChild(choice("ph","high","بیشتر از ۸٫۵","احتمال رسوب بیشتر"));
q3.appendChild(c3);
var bar=el("div","as-g-bar");
var go=el("button","as-g-go","پیشنهاد را نشان بده");
go.type="button";
go.id="as-g-go";
bar.appendChild(go);
bar.appendChild(el("span","as-g-count","۰ از ۳"));
hero.appendChild(q1);hero.appendChild(q2);hero.appendChild(q3);hero.appendChild(bar);
hero.appendChild(el("p","as-g-status",""));
root.appendChild(hero);
var nav=el("nav","as-g-nav");
[["#as-g-result","پیشنهاد و مقایسه"],["#as-g-qa","پرسش محصول"],["#as-g-ph","پی‌اچ"],["#as-g-measure","سنجش قبل از خرید"],["#as-g-replace","تعویض فیلتر"],["#as-g-filters","تفاوت فیلترها"],["#as-g-housing","هوزینگ"]].forEach(function(pair){
var a=el("a",null,pair[1]);a.href=pair[0];nav.appendChild(a);
});
root.appendChild(nav);
var result=el("section","as-g-result");
result.id="as-g-result";
result.setAttribute("aria-live","polite");
root.appendChild(result);
root.appendChild(panel("as-g-qa","پرسش‌های محصول و خرید",qaBody));
root.appendChild(panel("as-g-ph","کدام دستگاه برای کدام پی‌اچ",phBody));
root.appendChild(panel("as-g-measure","سنجش کیفیت آب قبل از خرید",measureBody));
root.appendChild(panel("as-g-replace","تعویض فیلتر و کار نمایندگی",replaceBody));
root.appendChild(panel("as-g-filters","فیلترها چه فرقی با هم دارند",function(box){box.appendChild(el("p",null,"هر کارت یک کالای واقعی فروشگاه است. قیمت را از ووکامرس می‌خوانیم."));box.appendChild(liveList([1749,900,904,919,877,871,1016]))}));
root.appendChild(panel("as-g-housing","کدام هوزینگ را انتخاب کنید",function(box){box.appendChild(el("p",null,"هوزینگ محفظه فیلتر است، خودِ فیلتر نیست. این‌ها همان کالاهای فروشگاه‌اند."));box.appendChild(liveList([1839,1244,1889,1837,1248,1827]))}));
bind(root);
}
function panel(id,title,fill){
var wrap=el("section","as-g-acc");
wrap.id=id;
var btn=el("button","as-g-accbtn");
btn.type="button";
btn.setAttribute("aria-expanded","false");
btn.appendChild(el("span",null,title));
btn.appendChild(el("span","mark","+"));
var body=el("div","as-g-panel");
body.hidden=true;
fill(body);
btn.addEventListener("click",function(){
var open=btn.getAttribute("aria-expanded")==="true";
btn.setAttribute("aria-expanded",open?"false":"true");
btn.querySelector(".mark").textContent=open?"+":"–";
body.hidden=open;
});
wrap.appendChild(btn);wrap.appendChild(body);
wrap._btn=btn;wrap._body=body;
return wrap;
}
function openPanel(id){
var wrap=document.getElementById(id);
if(!wrap||!wrap._btn)return;
wrap._btn.setAttribute("aria-expanded","true");
wrap._btn.querySelector(".mark").textContent="–";
wrap._body.hidden=false;
}
function qaBody(box){
box.appendChild(el("h3",null,"ابعاد"));
box.appendChild(el("p",null,"در برگه مدل ۴۰۱ اکونومی ابعاد ۳۷ در ۲۰ در ۴۵ سانتی‌متر آمده است؛ این اندازه برای کابینت زیر سینک خانه است. دستگاه ۶۰۰ لیتر و ۱۲۰۰ لیتر هر دو ۳۷ در ۲۰ در ۷۸ سانتی‌متر هستند و جای ایستاده می‌خواهند. اگر برگه مدلی ابعاد ندارد، همان صفحه محصول معیار است و اینجا عددی برایش ساخته نمی‌شود."));
box.appendChild(el("h3",null,"جنس"));
box.appendChild(el("p",null,"بدنه ۴۰۱ اکونومی استیل ضدزنگ است. مخزن همان مدل پلی‌اتیلن درجه غذایی است. الیت ممبران LP مخزن فلزی دو لایه دارد. هوزینگ ۱۰ اینچ HS پلاستیک مقاوم است. هوزینگ بیگ‌بلو پلی‌پروپیلن تقویت‌شده است. هوزینگ ممبران HS در برگه‌اش پلاستیک فودگرید نوشته شده و مدل BS پلی‌پروپیلن یا ABS درجه غذایی است."));
box.appendChild(el("h3",null,"طول عمر"));
box.appendChild(el("p",null,"کارتریج الیافی ۵ میکرون، الیافی ۱ میکرون و کربن UDF آکواجوی عمر مفید ۶ ماه دارند. کربن نهایی با اتصال QC مدل LP یک سال یا ۷۰۰۰ لیتر است و مدل تایوان یک سال یا حدود ۵۵۰۰ لیتر. کربن بیگ‌بلو ۲۰ اینچ ۶ تا ۱۲ ماه. برای ممبران، برگه محصول ماه ثابتی نداده است؛ نمایندگی وقتی تولید آب کم شود یا اختلاف عدد ورودی و خروجی آب کم شود آن را عوض می‌کند."));
box.appendChild(el("h3",null,"نحوه نصب"));
box.appendChild(el("p",null,"نصب را نمایندگی نیشابور انجام می‌دهد. دستگاه خانگی زیر سینک به برق ۲۲۰ ولت، شیر ورودی و مسیر فاضلاب وصل می‌شود. مدل‌های ۶۰۰ و ۱۲۰۰ لیتر جای ایستاده و همان برق ۲۲۰ ولت را می‌خواهند. پیش‌تصفیه بیگ‌بلو روی لوله ورودی با دنده یک اینچ می‌نشیند، قبل از دستگاه نوشیدنی. برای هماهنگی نصب با ۰۹۳۷۳۲۱۱۳۴۴ یا واتساپ تماس بگیرید."));
box.appendChild(el("h3",null,"خرید و ارسال"));
box.appendChild(el("p",null,"قیمت روی کارت‌ها همین حالا از فروشگاه خوانده می‌شود و واحدش تومان است، مثل صفحه محصول. در صفحه روش پرداخت، تنها پرداخت آنلاین از درگاه زیبال فعال است. بعد از پرداخت موفق، آماده‌سازی و ارسال شروع می‌شود. نشانی نمایندگی: نیشابور، شهرک صنعتی عطار، بلوک ۱۳."));
box.appendChild(contactLine());
}
function phBody(box){
box.appendChild(el("p",null,"برگه ممبران آکواجوی بازه پی‌اچ آب ورودی را ۴ تا ۱۱ نوشته است. دستگاه‌های این صفحه اسمز معکوس هستند، دستگاه یونیزه قلیایی نیستند."));
box.appendChild(el("h3",null,"کمتر از ۶٫۵"));
box.appendChild(el("p",null,"هنوز داخل بازه ممبران است. برای خانه، پرایم اف‌ال‌پی به‌خاطر پمپ و مخزن ۱۲ لیتری پیشنهاد می‌شود تا طعم آب اسیدی‌تر جمع شود. برای دفتر و کافه همان دستگاه ظرفیت بالاتر می‌ماند، چون پی‌اچ مدل را عوض نمی‌کند."));
box.appendChild(el("h3",null,"۶٫۵ تا ۸٫۵"));
box.appendChild(el("p",null,"بازه معمول آب شهری است. انتخاب با مکان و مشکل آب انجام می‌شود: خانه با ۴۰۱، دفتر با ۶۰۰ لیتر، کافه با ۱۲۰۰ لیتر."));
box.appendChild(el("h3",null,"بیشتر از ۸٫۵"));
box.appendChild(el("p",null,"بازه ممبران هنوز برقرار است و احتمال رسوب بیشتر می‌شود. مدل پمپ‌دار مناسب‌تر است: در خانه الیت، در دفتر دستگاه ۶۰۰ لیتر، در کافه دستگاه ۱۲۰۰ لیتر و اگر گل هم هست پیش‌تصفیه بیگ‌بلو."));
box.appendChild(el("p","as-g-note","اگر پی‌اچ را نمی‌دانید، پیشنهاد بدون این عدد ساخته می‌شود. سنجش پایین صفحه کمک می‌کند قبل از خرید یک تصویر عملی از آب داشته باشید."));
}
function measureBody(box){
box.appendChild(el("p",null,"این بررسی جای آزمایشگاه را نمی‌گیرد. نتیجه‌اش را هنگام خرید به فروشگاه بگویید."));
var home=el("div","as-g-check");home.id="as-g-m-home";
home.appendChild(el("h3",null,"خانه"));
home.appendChild(el("p",null,"یک لیوان شفاف را پر کنید و ده دقیقه کنار بگذارید. ته لیوان اگر شن یا رنگ نشست، مشکل ذره‌ای است. آب را بو کنید؛ بوی کلر یعنی کربن مرحله دوم در مسیر خرید مهم است. داخل کتری را ببینید؛ رسوب سفید یعنی سختی و دستگاه اسمز معکوس این قفسه به‌کار می‌آید. اگر قلم تی‌دی‌اس دارید عدد آب شیر را یادداشت کنید. سقف TDS ورودی در برگه ممبران ۲۰۰۰ ppm است. این عدد به‌تنهایی سالم بودن آب را ثابت نمی‌کند."));
var cafe=el("div","as-g-check");cafe.id="as-g-m-cafe";
cafe.appendChild(el("h3",null,"کافه"));
cafe.appendChild(el("p",null,"همان لیوان، بو و کتری را روی شیری که نوشیدنی از آن پر می‌شود انجام دهید، در یک ساعت شلوغ. لیوان‌های همان ساعت را بشمارید. حدود ۵۰ لیتر در ساعت به دستگاه ۱۲۰۰ لیتر در روز نزدیک است و حدود ۲۵ لیتر در ساعت به دستگاه ۶۰۰ لیتر. اگر شن از شیر می‌آید، پیش‌تصفیه بیگ‌بلو با خروجی یک اینچ قبل از دستگاه نوشیدنی است."));
var office=el("div","as-g-check");office.id="as-g-m-office";
office.appendChild(el("h3",null,"دفتر"));
office.appendChild(el("p",null,"تعداد نفرها را بشمارید و ببینید آب از یک شیر آبدارخانه می‌آید یا آبخوری همه روز روشن است. تا حدود ۲۰۰ لیتر در روز، لوتوس یا ۴۰۱ در قفسه خانه کافی است. اگر برداشت آب در طول روز قطع نمی‌شود، دستگاه ۶۰۰ لیتر در روز مناسب‌تر است. لیوان شفاف و نگاه به رسوب کتری را اینجا هم انجام دهید."));
box.appendChild(home);box.appendChild(cafe);box.appendChild(office);
}
function replaceBody(box){
box.appendChild(el("p",null,"زمان تعویض را برگه همان فیلتر گفته است، نه یک عدد کلی برای همه مرحله‌ها."));
box.appendChild(el("p",null,"الیافی ۵ میکرون، الیافی ۱ میکرون، کربن UDF و پک سه‌تایی پیش‌تصفیه: ۶ ماه. اگر آب گل‌آلود است این موعد زودتر می‌رسد، چون کارتریج پر می‌شود. کربن نهایی QC مدل LP: یک سال یا ۷۰۰۰ لیتر. مدل تایوان: یک سال یا حدود ۵۵۰۰ لیتر. کربن بیگ‌بلو: ۶ تا ۱۲ ماه. ممبران ماه ثابتی در برگه ندارد؛ کم شدن آب خروجی یا نزدیک شدن کیفیت خروجی به آب ورودی علامت تعویض است."));
box.appendChild(el("h3",null,"تعویض را نمایندگی چطور انجام می‌دهد"));
box.appendChild(el("p",null,"مدل دستگاه را به ۰۹۳۷۳۲۱۱۳۴۴ یا واتساپ بگویید. فروشگاه کارتریج همان مرحله را از موجودی واقعی جدا می‌کند. در نیشابور، تعویض در نمایندگی شهرک صنعتی عطار، بلوک ۱۳ انجام می‌شود. برای سفارش بیرون از شهر، پرداخت آنلاین زیبال ثبت می‌شود و بعد از پرداخت موفق آماده‌سازی و ارسال شروع می‌شود."));
box.appendChild(contactLine());
box.appendChild(liveList([1749,900,904,877,919]));
}
function contactLine(){
var p=el("p",null,"");
p.appendChild(document.createTextNode("تماس: "));
var tel=el("a",null,"۰۹۳۷۳۲۱۱۳۴۴");
tel.href="tel:09373211344";
p.appendChild(tel);
p.appendChild(document.createTextNode(" · "));
var wa=el("a",null,"واتساپ");
wa.href="https://wa.me/989373211344";
p.appendChild(wa);
return p;
}
function liveList(ids){
var wrap=el("div","as-g-mini");
wrap.setAttribute("data-live",ids.join(","));
ids.forEach(function(id){
var card=el("article");
card.setAttribute("data-pid",String(id));
var facts=FACTS[id]||[];
card.appendChild(el("h3",null,facts[0]||"کالا"));
card.appendChild(el("p","as-g-status","در حال خواندن قیمت"));
wrap.appendChild(card);
});
return wrap;
}
function bind(root){
root.querySelectorAll(".as-g-choice").forEach(function(b){
b.addEventListener("click",function(){
var g=b.getAttribute("data-group");
state[g]=b.getAttribute("data-val");
root.querySelectorAll('.as-g-choice[data-group="'+g+'"]').forEach(function(x){
var on=x===b;x.classList.toggle("is-on",on);x.setAttribute("aria-pressed",on?"true":"false");
});
var n=(state.place?1:0)+(state.problem?1:0)+(state.ph?1:0);
root.querySelector(".as-g-count").textContent=n.toLocaleString("fa-IR")+" از ۳";
if(g==="place")highlightPlace();
});
});
root.querySelector("#as-g-go").addEventListener("click",recommend);
}
function highlightPlace(){
["home","cafe","office"].forEach(function(k){
var n=document.getElementById("as-g-m-"+k);
if(!n)return;
n.style.outline=state.place===k?"2px solid #76cdd9":"";
});
}
function whyText(){
var place={home:"خانه",cafe:"کافه",office:"دفتر"}[state.place]||"";
var problem={taste:"طعم و بو",sediment:"ذره و گل",tds:"رسوب و سختی",unknown:"انتخاب عمومی"}[state.problem]||"";
var ph={low:"پی‌اچ کمتر از ۶٫۵ هنوز در بازه ممبران است و مدل پمپ‌دار طعم را جمع می‌کند.",mid:"پی‌اچ در بازه معمول شهری است و انتخاب با ظرفیت محل انجام شده.",high:"پی‌اچ بالا احتمال رسوب را بیشتر می‌کند؛ مدل پمپ‌دار و در صورت گل، پیش‌تصفیه بیگ‌بلو کنارش آمده.",unknown:"پی‌اچ وارد محاسبه نشده چون گفته شد مشخص نیست."}[state.ph]||"";
var lead={
"home|taste":"برای خانه و آب بدطعم، مدل ۴۰۱ اکونومی پنج‌مرحله‌ای با برگه مشخصات کامل در صدر است.",
"home|sediment":"برای ذره خانگی، ۴۰۱ اکونومی کنار الیت ممبران LP آمده که فیلتر اینلاین دارد.",
"home|tds":"برای سختی خانه، الیت با دقت ۰٫۰۰۰۱ میکرون و پمپ فشار پایین در صدر است.",
"home|unknown":"برای خانه، ۴۰۱ اکونومی نقطه شروع است چون ابعاد، جنس و ظرفیت در برگه‌اش هست.",
"cafe|taste":"برای کافه، دستگاه ۱۲۰۰ لیتر حدود ۵۰ لیتر در ساعت آب می‌دهد.",
"cafe|sediment":"برای گل کافه، اول پیش‌تصفیه بیگ‌بلو روی ورودی است و دستگاه ۱۲۰۰ لیتر آب نوشیدنی را جدا می‌کند.",
"cafe|tds":"برای سختی کافه، دستگاه ۱۲۰۰ لیتر با چهار ممبران در صدر است.",
"cafe|unknown":"برای کافه، دستگاه ۱۲۰۰ لیتر پیشنهاد اصلی است و ۶۰۰ لیتر گزینه جای کوچک‌تر.",
"office|taste":"برای دفتر، دستگاه ۶۰۰ لیتر حدود ۲۵ لیتر در ساعت جواب آبخوری روزانه را می‌دهد.",
"office|sediment":"برای ذره دفتر، ۶۰۰ لیتر کنار پیش‌تصفیه بیگ‌بلو آمده است.",
"office|tds":"برای سختی دفتر، دستگاه ۶۰۰ لیتر در صدر است.",
"office|unknown":"برای دفتر، ۶۰۰ لیتر پیشنهاد اصلی است. لوتوس اگر برداشت روزانه حدود ۲۰۰ لیتر بماند کافی است."
}[state.place+"|"+state.problem]||"";
return "مسیر شما: "+place+"، "+problem+". "+lead+" "+ph;
}
function idsFor(){
var base=(PICK[state.place]&&PICK[state.place][state.problem])||[];
if(state.ph==="low"||state.ph==="high"){
var over=PH[state.place+"|"+state.ph];
if(over)return over.slice();
}
return base.slice();
}
function recommend(){
var root=document.getElementById("as-water-guide");
var status=root.querySelector(".as-g-status");
if(!state.place||!state.problem||!state.ph){status.textContent="هر سه پاسخ را انتخاب کنید تا پیشنهاد ساخته شود.";return}
var box=document.getElementById("as-g-result");
box.innerHTML="";
box.appendChild(el("p","as-g-why",whyText()));
if(!state.ready){box.appendChild(el("p",null,"قیمت فروشگاه هنوز نرسیده. چند ثانیه دیگر دوباره بزنید."));return}
var grid=el("div","as-g-grid");
var missing=0;
idsFor().forEach(function(id,idx){
var p=state.products[id];
if(!p){missing++;return}
grid.appendChild(card(p,idx===0));
});
if(!grid.childNodes.length){box.appendChild(el("p",null,"این کالاها الان در پاسخ فروشگاه نیستند. از صفحه فروشگاه دستگاه را باز کنید."));}
else box.appendChild(grid);
if(missing)box.appendChild(el("p","as-g-note","بعضی شناسه‌ها در این پاسخ فروشگاه نبودند و نشان داده نشدند."));
box.appendChild(el("p","as-g-note","مقایسه فقط بین کالاهای موجود همین پاسخ است. جزئیات فنی از برگه همان محصول آمده است."));
openPanel("as-g-qa");
box.scrollIntoView({behavior:"smooth",block:"start"});
}
function card(p,pick){
var art=el("article","as-g-card"+(pick?" is-pick":""));
var img=el("img");
img.alt="";
img.src=p.image||"";
art.appendChild(img);
var body=el("div");
if(pick)body.appendChild(el("span","as-g-badge","پیشنهاد"));
body.appendChild(el("h3",null,p.name));
var price=money(p.price);
body.appendChild(el("p","as-g-price",price?price:"قیمت را در صفحه محصول ببینید"));
body.appendChild(el("p","as-g-note",p.stock?"موجود در فروشگاه":"در این لحظه ناموجود"));
var ul=el("ul");
(FACTS[p.id]||[]).slice(0,4).forEach(function(line){ul.appendChild(el("li",null,line))});
body.appendChild(ul);
var acts=el("div","as-g-actions");
if(p.link){var a=el("a","as-g-btn","مشاهده و خرید");a.href=p.link;acts.appendChild(a)}
body.appendChild(acts);
art.appendChild(body);
return art;
}
function fillMinis(){
document.querySelectorAll("[data-live]").forEach(function(wrap){
wrap.querySelectorAll("[data-pid]").forEach(function(card){
var id=Number(card.getAttribute("data-pid"));
var p=state.products[id];
var h=card.querySelector("h3");
var slot=card.querySelector(".as-g-status");
if(!p){if(slot)slot.textContent="در فهرست این لحظه فروشگاه نیست";return}
if(h)h.textContent=p.name;
if(slot){
slot.textContent="";
var bits=FACTS[id]||[];
slot.appendChild(document.createTextNode((bits[0]||"")+(bits[1]?" · "+bits[1]:"")));
slot.appendChild(el("br"));
var price=money(p.price);
slot.appendChild(document.createTextNode(price?price+" · ":""));
slot.appendChild(document.createTextNode(p.stock?"موجود":"ناموجود"));
if(p.link){slot.appendChild(document.createTextNode(" · "));var a=el("a",null,"صفحه محصول");a.href=p.link;slot.appendChild(a)}
}
});
});
}
function load(){
var url="/wp-json/wc/store/v1/products?per_page=100&include="+IDS.join(",");
fetch(url,{credentials:"same-origin"}).then(function(r){return r.json()}).then(function(rows){
if(!Array.isArray(rows))return;
rows.forEach(function(p){
var img=(p.images&&p.images[0]&&p.images[0].src)||"";
var price=p.prices&&p.prices.price;
state.products[p.id]={id:p.id,name:cleanName(p.name),price:price,link:p.permalink||"",image:img,stock:!!p.is_in_stock};
});
state.ready=true;
fillMinis();
var st=document.querySelector("#as-water-guide .as-g-status");
if(st && !st.textContent)st.textContent="قیمت‌ها از فروشگاه خوانده شد.";
}).catch(function(){
var st=document.querySelector("#as-water-guide .as-g-status");
if(st)st.textContent="خواندن قیمت زنده ممکن نشد. لینک صفحه محصول در بخش‌ها می‌ماند.";
});
}
function boot(){
var h=claimHost();
if(!h||h.getAttribute("data-as-guide")==="1")return;
h.setAttribute("data-as-guide","1");
shell(h);
load();
var obs=new MutationObserver(function(){
document.querySelectorAll(".page-content .as-adv-card, main .as-adv-card").forEach(function(n){
if(!n.closest(".as-advisor-modal"))n.remove();
});
});
var watch=document.querySelector("main")||document.body;
obs.observe(watch,{childList:true,subtree:true});
}
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",boot);
else boot();
document.addEventListener("DOMContentLiteSpeedLoaded",boot);
})();
ASGJS;
		echo '</script>';
	}

	add_action( 'wp_head', 'as_water_guide_css', 40 );
	add_action( 'wp_footer', 'as_water_guide_js', 99 );
}
