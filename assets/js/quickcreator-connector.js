jQuery(function ($) {
  var connected = quickcreator_connection_lang.connected

  if (connected) {
    $('.quickcreator-connected').show()
    $('.quickcreator-not-connected').hide()
  } else {
    $('.quickcreator-connected').hide()
    $('.quickcreator-not-connected').show()
  }

  var connection_check

  function check_connection_success() {
    var data = {
      action: "check_quickcreator_connection_status",
      _quickcreator_nonce: quickcreator_connection_lang._quickcreator_nonce,
    };

    $.ajax({
      url: quickcreator_connection_lang.ajaxurl,
      type: 'POST',
      data: data,
      dataType: 'json',
      async: true,
      success: function (response) {
        if (true === response.connection) {
          $('#quickcreator-connection-spinner').hide()

          $('.quickcreator-connected').show()
          $('.quickcreator-not-connected').hide()

          $('#quickcreator-organization-name').html(
            response.details.organization_name
          )
          $('#quickcreator-via-email').html(response.details.via_email)

          clearInterval(connection_check)
        }
      },
    })
  }

  function make_disconnection() {
    var data = {
      action: 'disconnect_quickcreator',
      _quickcreator_nonce: quickcreator_connection_lang._quickcreator_nonce,
    }

    $.ajax({
      url: quickcreator_connection_lang.ajaxurl,
      type: 'POST',
      data: data,
      dataType: 'text',
      async: true,
      success: function (response) {
        $('#quickcreator-reconnection-spinner').hide()

        $('.quickcreator-connected').hide()
        $('.quickcreator-not-connected').show()
      },
    })
  }

  function make_connection() {
    var data = {
      action: "generate_quickcreator_connection_url",
      auth_user_id: $("#quickcreator-auth-user").val(),
      _quickcreator_nonce: quickcreator_connection_lang._quickcreator_nonce,
    };

    $.ajax({
      url: quickcreator_connection_lang.ajaxurl,
      type: 'POST',
      data: data,
      dataType: 'json',
      async: true,
      success: function (response) {
        var win = window.open(response.url, '_blank')
        if (win) {
          connection_check = setInterval(check_connection_success, 5000)
          win.focus()
        } else {
          alert(quickcreator_connection_lang.popup_block_error)
        }
      },
    })
  }

  $("#quickcreator_reconnect").click(function (event) {
    event.preventDefault();

    $("#quickcreator-reconnection-spinner").show();
    make_disconnection();

    $("#quickcreator-connection-spinner").show();
    make_connection();
  });

  $('.quickcreator_make_connection').click(function (event) {
    event.preventDefault()

    $('#quickcreator-connection-spinner').show()
    make_connection()
  })

  $("#quickcreator_disconnect").click(function (event) {
    event.preventDefault();

    $("#quickcreator-reconnection-spinner").show();
    make_disconnection();
  });
})
