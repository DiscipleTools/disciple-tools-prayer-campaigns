"use strict";

var displayMailChimpStatus = function (data) {
  console.log(data)
}

/* help from and kudos to https://css-tricks.com/form-validation-part-4-validating-mailchimp-subscribe-form/ */
function submitMailChimpSubscribe(form) {
  let url = form.action;
  url = url.replace('/post', '/post-json')

  const serialisedFormData = new URLSearchParams( new FormData( form ) )

  url += `&${serialisedFormData.toString()}&c=displayMailChimpStatus`

  const script = window.document.createElement( 'script' );
  script.src = url;

  const ref = window.document.getElementsByTagName( 'script' )[ 0 ];
  ref.parentNode.insertBefore( script, ref );

  script.onload = function () {
      this.remove();
  };
}
