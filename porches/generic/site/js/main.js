/*
   CounterUp
   ========================================================================== */
    jQuery(document).ready(function( $ ) {
      //if counterUp is a function
      if ( typeof counterUp === 'function'){
        $('.counter').counterUp({
          time: 500
        });

      }

    });



/*
   Sticky Nav
   ========================================================================== */
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 200) {
            $('.header-top-area').addClass('menu-bg');
        } else {
            $('.header-top-area').removeClass('menu-bg');
        }
    });


/*
   Back Top Link
   ========================================================================== */
    var offset = 200;
    var duration = 500;
    $(window).scroll(function() {
      if ($(this).scrollTop() > offset) {
        $('.back-to-top').fadeIn(400);
      } else {
        $('.back-to-top').fadeOut(400);
      }
    });
    $('.back-to-top').click(function(event) {
      event.preventDefault();
      $('html, body').animate({
        scrollTop: 0
      }, 600);
      return false;
    })

/*
   One Page Navigation & wow js
   ========================================================================== */
  jQuery(function($) {
    if ( typeof WOW !== 'function'){
      return
    }
    //Initiat WOW JS
    new WOW().init();

    // one page navigation
    $('.main-navigation').onePageNav({
            currentClass: 'active'
    });
  });

  jQuery(document).ready(function() {
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 200) {
            $('.fixed-top').addClass('menu-bg');
        } else {
            $('.fixed-top').removeClass('menu-bg');
        }
    });
    if ( typeof scrollspy !== 'function'){
      return
    }

    $('body').scrollspy({
        target: '.navbar-collapse',
        offset: 195
    });

  });



  /* stellar js
  ========================================================*/
  $(function(){
    if ( typeof $.stellar !== 'function') {
      return
    }
    $.stellar({
      horizontalScrolling: false,
      verticalOffset: 0,
      responsive: true
    });
  });

/*
   Page Loader
   ========================================================================== */
   $(window).on('load', function() {
    "use strict";
    $('#loader').fadeOut();
   });

/*
  Language Selector
  ========================================================================== */
  jQuery(document).ready(function( $ ) {
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
(function() {

  var bodyEl = document.body,
    content = document.querySelector( '.content-wrap' ),
    openbtn = document.getElementById( 'open-button' ),
    closebtn = document.getElementById( 'close-button' ),
    isOpen = false;

  function init() {
    initEvents();
  }

  function initEvents() {
    openbtn.addEventListener( 'click', toggleMenu );
    if( closebtn ) {
      closebtn.addEventListener( 'click', toggleMenu );
    }
  }

  function toggleMenu() {
    if( isOpen ) {
      classie.remove( bodyEl, 'show-menu' );
    }
    else {
      classie.add( bodyEl, 'show-menu' );
    }
    isOpen = !isOpen;
  }

  init();

})();