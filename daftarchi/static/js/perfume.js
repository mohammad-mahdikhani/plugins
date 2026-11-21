/* Exact integer-toman calculations with gram inputs to nine decimal places. */
(() => {
  'use strict';
  const SCALE = 1000000000n;
  const text = value => String(value ?? '').trim().replace(/[۰-۹]/g,d=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g,d=>'٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[,٬]/g,'').replace(/٫/g,'.');
  const round = (a,b) => (a + b / 2n) / b;
  const decimal = value => {const whole=value/SCALE, fraction=String(value%SCALE).padStart(9,'0').replace(/0+$/,'');return `${whole}${fraction?'.'+fraction:''}`;};
  function quote(rateValue, inputValue, basis='weight') {
    const rateText=text(rateValue), input=text(inputValue);
    if (!/^\d+$/.test(rateText) || !rateText || BigInt(rateText)<=0n || BigInt(rateText)>10000000000n) throw new Error('ابتدا قیمت هر گرم عطر را ثبت کنید');
    const rate=BigInt(rateText);
    let weight, amount, approximate=false;
    if (basis==='weight') {
      if (input.length>40 || !/^\d+(\.\d{1,9})?$/.test(input)) throw new Error('وزن معتبر به گرم وارد کنید');
      const [whole,fraction='']=input.split('.');
      weight=BigInt(whole)*SCALE+BigInt(fraction.padEnd(9,'0'));
      amount=round(weight*rate,SCALE);
    } else if (basis==='amount') {
      if (input.length>16 || !/^\d+$/.test(input)) throw new Error('مبلغ را به تومان و بدون اعشار وارد کنید');
      amount=BigInt(input); weight=round(amount*SCALE,rate); approximate=weight*rate!==amount*SCALE;
    } else throw new Error('نوع محاسبه نامعتبر است');
    if (weight<1000000n || weight>100000n*SCALE || amount<1n || amount>1000000000000n) throw new Error('وزن از ۰٫۰۰۱ تا ۱۰۰٬۰۰۰ گرم و مبلغ بیشتر از صفر باشد');
    return {basis,input,grams:decimal(weight),rate:Number(rate),amount:Number(amount),approximate};
  }
  function formatGrams(value) {
    return String(value).replace(/[0-9]/g,d=>'۰۱۲۳۴۵۶۷۸۹'[Number(d)]).replace('.', '٫');
  }
  function refresh(modal, basis) {
    const grams=document.getElementById('perfume-grams'), amount=document.getElementById('perfume-amount'), output=document.getElementById('perfume-result');
    if (!grams || !amount || !output) return;
    modal.perfumeBasis=basis || modal.perfumeBasis || 'weight';
    try {
      const result=quote(modal.product.price_per_gram,modal.perfumeBasis==='amount'?amount.value:grams.value,modal.perfumeBasis);
      if (modal.perfumeBasis==='weight') amount.value=String(result.amount); else grams.value=result.grams;
      output.textContent=`${result.approximate?'حدود ':''}${formatGrams(result.grams)} گرم · ${new Intl.NumberFormat('fa-IR').format(result.amount)} تومان`;
      output.classList.remove('error'); modal.perfumeQuote=result;
      document.querySelector('[data-act="add-perfume-line"]').disabled=false;
    } catch(error) {
      modal.perfumeQuote=null; output.textContent=error.message; output.classList.add('error');
      document.querySelector('[data-act="add-perfume-line"]').disabled=true;
    }
  }
  window.DaftarchiPerfume={quote,formatGrams,refresh};
})();
