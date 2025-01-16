/*
   Sticky Nav
   ========================================================================== */
$(window).on('scroll', function () {
  if ($(window).scrollTop() > 200) {
    $('.header-top-area').addClass('menu-bg');
  } else {
    $('.header-top-area').removeClass('menu-bg');
  }
});


/*
   Back Top Link
   ========================================================================== */
$(window).scroll(function () {
  if ($(this).scrollTop() > 200) {
    $('.back-to-top').fadeIn(400);
  } else {
    $('.back-to-top').fadeOut(400);
  }
});
$('.back-to-top').click(function (event) {
  event.preventDefault();
  $('html, body').animate({
    scrollTop: 0
  }, 600);
  return false;
})

/*
   One Page Navigation & wow js
   ========================================================================== */
jQuery(function ($) {
  if (typeof WOW!=='function') {
    return
  }
  //Initiat WOW JS
  new WOW().init();
});

jQuery(document).ready(function () {
  $(window).on('scroll', function () {
    if ($(window).scrollTop() > 200) {
      $('.fixed-top').addClass('menu-bg');
    } else {
      $('.fixed-top').removeClass('menu-bg');
    }
  });

  if (typeof scrollspy!=='function') {
    return
  }
  $('body').scrollspy({
    target: '.navbar-collapse',
    offset: 195
  });
});

window.addEventListener('scroll', function () {
  const scrollPosition = window.scrollY;
  const bgParallax = document.getElementById('hero-area');
  const limit = bgParallax.offsetTop + bgParallax.offsetHeight;
  if (scrollPosition > bgParallax.offsetTop && scrollPosition <= limit) {
    bgParallax.style.backgroundPositionY = (scrollPosition / 2) + 'px';
  } else {
    bgParallax.style.backgroundPositionY = '50%';
  }
});


/*
   Page Loader
   ========================================================================== */
$(window).on('load', function () {
  "use strict";
  $('#loader').fadeOut();
});

/*
  Language Selector
  ========================================================================== */
jQuery(document).ready(function ($) {
  $('.dt-magic-link-language-selector').on('change', (e) => {
    const val = $(e.currentTarget).val()
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('lang', val);
    window.location.search = urlParams;
  })
});

/**
 * main.js
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2014, Codrops
 * http://www.codrops.com
 */
(function () {

  var bodyEl = document.body,
    content = document.querySelector('.content-wrap'),
    openbtn = document.getElementById('open-button'),
    closebtn = document.getElementById('close-button'),
    isOpen = false;

  function init() {
    initEvents();
  }

  function initEvents() {
    if (!openbtn) {
      return
    }
    openbtn.addEventListener('click', toggleMenu);
    if (closebtn) {
      closebtn.addEventListener('click', toggleMenu);
    }
  }

  function toggleMenu() {
    if (isOpen) {
      classie.remove(bodyEl, 'show-menu');
    } else {
      classie.add(bodyEl, 'show-menu');
    }
    isOpen = !isOpen;
  }

  init();

})();

if (window.navigator.canShare) {
  const share_button = document.getElementById('share-button');
  share_button.style.display = 'block';
  share_button.onclick = function () {
    const data = {
      title: document.title,
      text: 'Please join me in prayer on ' + document.title,
      url: window.location.href,
    }
    if (window.navigator.canShare && window.navigator.canShare(data)) {
      window.navigator.share(data)
    }
  }
}
