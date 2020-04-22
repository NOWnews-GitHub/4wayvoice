/*
 * Security Ninja - Database Optimizer add-on
 * (c) 2018. Web factory Ltd
 */


jQuery(document).ready(function($) {
  $('#sn_do a.sn-do-action').click(function(e) {
    e.preventDefault();
    
    optimization_action = $(this).data('action-id');
    if (!optimization_action) {
      alert(wf_sn_do.undocumented_error);
      return;
    }
    
    if (!confirm(wf_sn_do.confirm)) {
      return;
    }
    
    $('td.do-optimization-desc-done').removeClass('do-optimization-desc-done');
    
    sn_block_ui('#sn-do');
    data = {action: 'sn_do_run_optimization', optimization: optimization_action, _ajax_nonce: wf_sn_do.sn_do_nonce};
    
    $.get(ajaxurl, data, function(response) {
      if (response.success == true) {
        $.each(response.data, function (action_name, text) {
          $('#' + action_name + '_desc').html(text).addClass('do-optimization-desc-done');
        });
      } else {
        alert(wf_sn_do.undocumented_error);
      }
    }).always(function() { sn_unblock_ui('#sn-do'); });
  });
});
