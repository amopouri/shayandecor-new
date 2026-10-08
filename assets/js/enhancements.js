/* Shayan Decor — interaction safeguards (complements page scripts) */
(function(){
  'use strict';

  const lock = () => document.body.classList.add('no-scroll');
  const unlock = () => {
    if(!document.querySelector('.drawer.on,.modal.on,.modal-overlay.on,.nav-main.mobile-open,.img-lightbox.on')){
      document.body.classList.remove('no-scroll');
    }
  };
  window.ShayanDecorUI = { lock, unlock };

  window.addEventListener('keydown', e => {
    if(e.key !== 'Escape') return;
    document.querySelectorAll('.drawer.on,.modal.on,.modal-overlay.on,.drawer-overlay.on,.img-lightbox.on').forEach(el => {
      el.classList.remove('on');
    });
    const nav = document.getElementById('navMain');
    const toggle = document.getElementById('menuToggle');
    if(nav) nav.classList.remove('mobile-open');
    if(toggle) toggle.setAttribute('aria-expanded', 'false');
    unlock();
  });

  document.addEventListener('error', e => {
    const img = e.target;
    if(!(img instanceof HTMLImageElement) || img.dataset.sdFallback) return;
    img.dataset.sdFallback = '1';
    img.removeAttribute('srcset');
    img.style.objectFit = 'contain';
    img.style.opacity = '.35';
    img.setAttribute('alt', img.getAttribute('alt') || 'تصویر در دسترس نیست');
  }, true);

  /* Observe mobile-open class for scroll lock (page script owns toggle) */
  const nav = document.getElementById('navMain');
  const toggle = document.getElementById('menuToggle');
  if(nav){
    const sync = () => {
      const open = nav.classList.contains('mobile-open');
      if(open && window.innerWidth <= 900) lock();
      else unlock();
      if(toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    new MutationObserver(sync).observe(nav, { attributes:true, attributeFilter:['class'] });

    document.addEventListener('click', e => {
      if(!nav.classList.contains('mobile-open')) return;
      if(nav.contains(e.target) || (toggle && toggle.contains(e.target))) return;
      nav.classList.remove('mobile-open');
      if(toggle) toggle.setAttribute('aria-expanded', 'false');
      unlock();
    });
  }

  const revealItems = [...document.querySelectorAll('.reveal,.reveal-child')];
  if('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && revealItems.length){
    document.documentElement.classList.add('js-reveal');
    const io = new IntersectionObserver(entries => entries.forEach(entry => {
      if(entry.isIntersecting){
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      }
    }), { threshold: .1, rootMargin: '0px 0px -24px 0px' });
    revealItems.forEach(el => io.observe(el));
  }
})();
