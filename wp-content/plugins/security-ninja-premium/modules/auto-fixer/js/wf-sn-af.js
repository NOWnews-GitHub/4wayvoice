/*
 * Security Ninja - Auto Fixer add-on
 * (c) 2014. Web factory Ltd
 */


 jQuery(document).ready(function($) {


  $(document).on('click', '#sn_af_run_fix', function(e) {
    e.preventDefault();

    if (!confirm('Are you sure you want to apply the fix?')) {
      return false;
    }

    var fix_fields={};

    $('#auto-fixer-content input').each(function() {
      if ($(this).attr('type') == 'checkbox') {
        if ($(this).is(':checked')) {
          fix_fields[$(this).attr('name')] = 'true';
        } else {
          fix_fields[$(this).attr('name')] = 'false';
        }
      } else {
        fix_fields[$(this).attr('name')] = $(this).val();
      }
    });

    $('#auto-fixer-content').html('<div class="sn-fixer-loader">Applying the fix.</div>');

    data = {'action': 'sn_af_do_fix',
    '_ajax_nonce': wf_sn_af.nonce_do_fix,
    'test_id': $(this).data('test-id'),
    'fields': JSON.stringify(fix_fields)};

    $.get(ajaxurl, data, function(response) {
      if (response.success) {
        $('#auto-fixer-content').html('<p>' + response.data + '<br><b>Please note</b>: analyze the site again if you want to update its overall score.</p>');
      } else {
        $('#auto-fixer-content').html('<p>The fix could not be applied.</p>');
      }
    });

    return false;
  }); // do fix


  $(document).on('sn_test_details_dialog_open', function(e, test_id, test_status) {

//jQuery('#test-details-dialog').append('<hr><h3>Auto Fixer</h3><div id="auto-fixer-content"></div>');
    data = {
      'action': 'sn_af_get_fix_info',
      '_ajax_nonce': wf_sn_af.nonce_get_fix_info,
      'test_id': test_id,
      'test_status': test_status
    };

    $.get(ajaxurl, data, function(response) {

      if (response.success) {
        content = response.data;
      } else {
        content = 'Undocumented error. Unable to get fix info. Please reload the page and try again.';
      }
      $('#auto-fixer-content').html( content );

    });
  }); // get_fix_info










}); // onload
