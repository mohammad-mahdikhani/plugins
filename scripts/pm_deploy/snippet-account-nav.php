<?php
/**
 * Account and order links in the header and footer.
 */

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-account-nav">'
			. '@media(min-width:901px){.pm-footer__grid{grid-template-columns:1.3fr repeat(3,minmax(0,1fr)) minmax(120px,150px)!important}}'
			. '@media(max-width:900px){.pm-header__bar,.pm-header.is-scrolled .pm-header__bar,.pm-site--has-hero .pm-header.is-scrolled .pm-header__bar,.pm-site--has-hero .pm-header:not(.is-scrolled) .pm-header__bar,body.pm-wc-shell .pm-header__bar{grid-template-columns:auto minmax(0,1fr) auto auto!important}.pm-header__account.pm-mobile-only{display:flex!important;grid-column:3;justify-self:end}.pm-header__cart-mobile,body.pm-wc-shell .pm-header__cart-mobile{grid-column:4!important;justify-self:end}}'
			. '</style>';
		echo '<script id="pm-account-nav-js">'
			. '(function(){var orders="/my-account/orders/";var account="/my-account/";var user="<svg width=\\"20\\" height=\\"20\\" viewBox=\\"0 0 24 24\\" fill=\\"none\\" stroke=\\"currentColor\\" stroke-width=\\"1.85\\" aria-hidden=\\"true\\"><circle cx=\\"12\\" cy=\\"8\\" r=\\"3.5\\"/><path d=\\"M6 20v-1a6 6 0 0 1 12 0v1\\"/></svg>";'
			. 'function link(cls,href,label,key,icon){var a=document.createElement("a");a.className=cls;a.href=href;a.setAttribute("data-pm-account",key);if(label)a.textContent=label;if(icon){a.setAttribute("aria-label",label||"سفارش‌های من");a.innerHTML=user;}if(location.pathname.indexOf("/my-account")===0)a.classList.add("is-active");return a;}'
			. 'function mount(){var seg=document.querySelector(".pm-header__nav .pm-seg");if(seg&&!seg.querySelector("[data-pm-account=\\"nav\\"]"))seg.appendChild(link("pm-seg__link",orders,"حساب کاربری","nav"));'
			. 'var tools=document.querySelector(".pm-header__tools");if(tools&&!tools.querySelector("[data-pm-account=\\"tools\\"]")){var icon=link("pm-header__icon-btn pm-header__account",orders,"سفارش‌های من","tools",1);var cart=tools.querySelector(".pm-cart");cart?tools.insertBefore(icon,cart):tools.appendChild(icon);}'
			. 'var bar=document.querySelector(".pm-header__bar");if(bar&&!bar.querySelector("[data-pm-account=\\"mobile\\"]")){var mob=link("pm-header__icon-btn pm-header__account pm-mobile-only",orders,"سفارش‌های من","mobile",1);var mcart=bar.querySelector(".pm-header__cart-mobile");mcart?bar.insertBefore(mob,mcart):bar.appendChild(mob);}'
			. 'var drawer=document.querySelector(".pm-drawer__links");if(drawer&&!drawer.querySelector("[data-pm-account=\\"orders\\"]")){var row=link("pm-drawer__link",orders,"سفارش‌های من","orders");var acc=drawer.querySelector("[data-pm-account=\\"drawer\\"],a[href$=\\"/my-account/\\"],a[href*=\\"my-account\\"]");acc?acc.after(row):drawer.appendChild(row);}'
			. 'var grid=document.querySelector(".pm-footer__grid");if(grid&&!grid.querySelector("[data-pm-account-col]")){var col=document.createElement("div");col.className="pm-footer__col pm-footer__account";col.setAttribute("data-pm-account-col","1");col.innerHTML="<h2 class=\\"pm-footer__title\\">حساب کاربری</h2><ul class=\\"pm-footer__links\\"><li><a href=\\""+orders+"\\">سفارش‌های من</a></li><li><a href=\\""+account+"\\">جزئیات حساب</a></li></ul>";var enamad=grid.querySelector(".pm-footer__enamad");enamad?grid.insertBefore(col,enamad):grid.appendChild(col);}}'
			. 'mount();new MutationObserver(function(){mount();}).observe(document.documentElement,{childList:true,subtree:true});})();'
			. '</script>';
	},
	30
);
