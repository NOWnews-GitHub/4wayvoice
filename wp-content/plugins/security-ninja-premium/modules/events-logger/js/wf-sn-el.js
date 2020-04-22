/*
 * Security Ninja - Events Logger add-on
 * (c) Web factory Ltd, 2015
 */


jQuery(document).ready(function($){
  var el_table = $('#sn-el-datatable').dataTable({ sDom: '<"wf-sn-el-options">ftip', ordering: false, pageLength: 40, 'columns': [ null, null, { 'width': '150px' }, null, null, null ] });

  $('.wf_sn_el_filter').on('click', function(e){
    e.preventDefault();
    el_table.fnFilter($(this).text());
  });

  // truncate log table
  $('#sn-el-truncate').click(function(e){
    e.preventDefault();

    answer = confirm("Are you sure you want to delete all log entries?");
    if (answer) {
      data = {action: 'sn_el_truncate_log'};
      $.post(ajaxurl, data, function(response) {
        if (!response) {
          alert('Bad AJAX response. Please reload the page.');
        } else {
          alert('All log entries have been deleted.');
          window.location.reload();
        }
      });
    }
  });
}); // onload