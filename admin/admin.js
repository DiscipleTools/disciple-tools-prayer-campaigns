jQuery(document).ready(function ($) {
  $('.expand_translations').click(function () {
    event.preventDefault()
    $(this).siblings().toggleClass("hide");

    var buttonText = $(this).text();

    if (buttonText==='+') {
      $(this).text('-')
    }
    if (buttonText==='-') {
      $(this).text('+')
    }
  })

  $('#install_from_file_append_date').on('change', function(event) {
    const text = document.getElementById('install_from_file_append_date_text')

    if (event.target.value) {
      text.style.visibility = 'hidden'
    } else {
      text.style.visibility = 'visible'
    }

  })
})
