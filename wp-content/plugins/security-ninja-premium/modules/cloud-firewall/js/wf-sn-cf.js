/* globals jQuery:true, ajaxurl:true, wf_sn_cf:true, wf_sn:true, sn_block_ui:true, alert:true */
/*
 * Security Ninja PRO
 * (c) 2018. Web factory Ltd
 */


 jQuery(document).ready(function($) {



  $('#sn_cf').on('click', '.testresults h3', function(e){
    e.preventDefault();
    var content = $(this).parents('.testresults').toggleClass('opened').find('table');
  });



  // Select2 on country dropdown
  $('#wf_sn_cf_blocked_countries').select2({
    multiple: true,
    dropdownAutoWidth: true,
 //   width: 'resolve'
    closeOnSelect: false,
    theme:'classic'
  });

  $('#sn-enable-firewall-overlay').click(function(e){
    e.preventDefault();

    var sn_close_timeout;
    var sn_close_timeout_count = 3;
    $('#abort-scan').hide();
    $('.loader').hide();
    sn_block_ui('#sn-cloud-firewall');
    $('#sn-close-firewall').attr('disabled','disabled');
    var data = {
     action: 'sn_enable_firewall',
     _ajax_nonce: wf_sn_cf.nonce
   };


   $.get(
    ajaxurl,
    data,
    function(response) {
      if (response !== '1') {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        sn_close_timeout = setInterval(function() {
          if(sn_close_timeout_count === 0){
            $('#sn-close-firewall').val('Close');
            clearInterval(sn_close_timeout);
            $('#sn-close-firewall').removeAttr('disabled');
          } else {
            $('#sn-close-firewall').val('Close (' + sn_close_timeout_count + ')');
          }
          sn_close_timeout_count--;
        }, 1000);
      }
    }, 'html');
  }); // enable firewall

  $('#sn-close-firewall').on('click',function(){
    window.location.reload();
  });

  $('#sn-send-unlock-code').click(function(e){
    e.preventDefault();

    var data = {
      action: 'sn_send_unblock_email',
      email:$('#sn-ublock-email').val(),
      _ajax_nonce: wf_sn_cf.nonce
    };


    $('#sn-unblock-message').html('<img title="Loading ..." src="'+wf_sn.sn_plugin_url+'images/ajax-loader.gif" alt="Loading...">');
    $('#sn-unblock-message').removeClass('sn-unblock-message-bad');
    $('#sn-unblock-message').removeClass('sn-unblock-message-good');

    $.get(ajaxurl, data, function(response) {
      if (response !== '1') {
        $('#sn-unblock-message').html('An error occured and the message could not be sent.');
        $('#sn-unblock-message').addClass('sn-unblock-message-bad');
      } else {
        $('#sn-unblock-message').html('Email sent successfully.');
        $('#sn-unblock-message').addClass('sn-unblock-message-good');

      }
    }, 'html').fail(function() {
      $('#sn-unblock-message').html('An error occured. The email could not be sent.');
      $('#sn-unblock-message').addClass('sn-unblock-message-bad');
    });
  }); // send unlock code

  $('#sn-firewall-blacklist-clear').click(function(e){
    e.preventDefault();

    var data = {
      action: 'sn_clear_blacklist',
      email:$('#sn-ublock-email').val(),
      _ajax_nonce: wf_sn_cf.nonce
    };


    $('#sn-firewall-blacklist-clear').remove();
    $('#sn-firewall-blacklist').append('<img title="Loading ..." src="'+wf_sn.sn_plugin_url+'images/ajax-loader.gif" alt="Loading...">');

    $.get(ajaxurl, data, function(response) {
      if (response !== '1') {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        alert('List has been cleared.');
        $('#sn-firewall-blacklist').html('No locally banned IPs');
      }
    }, 'html').fail(function() {
      alert('Undocumented error. Page will automatically reload.');
      window.location.reload();
    });
  }); // send unlock code



  // Disable firewall
  $('#sn-disable-firewall').on('click',function(){
    $('#wf_sn_cf_active').val(0);
    $('#sn-firewall-settings-form').submit();
  });



  // Test IP
  $('#wf-cf-do-test-ip').on('click',function(e){
    e.preventDefault();
    var data = {
      action: 'sn_test_ip',
      ip: $('#wf-cf-ip-test').val(),
      _ajax_nonce: wf_sn_cf.nonce
    };

    $.get(
      ajaxurl,
      data,
      function(response) {
        if (response.data && response.success) {
          alert(response.data);
        } else {
          alert('An undocumented error has occured. Page will automatically reload.');
          window.location.reload();
        }
      }, 'json').fail(function() {
        alert('An undocumented error has occured. Page will automatically reload.');
        window.location.reload();
      });

      return false;
    });
});
