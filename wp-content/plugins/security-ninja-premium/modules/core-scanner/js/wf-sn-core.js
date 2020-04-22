/* globals jQuery:true, ajaxurl:true, wf_sn_cs:true, sn_block_ui:true */
/* globals: dialog_id */
/*
 * Security Ninja - Scheduled Scanner add-on
 * (c) 2014. Web factory Ltd
 */


 jQuery(document).ready(function($) {




// Asks before deleting all unknowns files in WP core scanner
$(document).on('click', '#wf-cs-delete-all-unknown-button', function() {
  if (!confirm('Are you sure you want to delete all unknown files?')) { //i8n
    return false;
  }
  else {
    return true;
  }
});







$('button.sn-show-source').click(function() {
  $($(this).attr('href')).dialog('option', {
    title: 'File source: ' + $(this).attr('data-file'),
    file_path: $(this).attr('data-file'),
    file_hash: $(this).attr('data-hash')
  }).dialog('open');
  return false;
});

$('button.sn-restore-source').click(function() {
  $($(this).attr('href')).dialog('option', { title: 'Restore file: ' + $(this).attr('data-file-short'), file_path: $(this).attr('data-file'), file_hash: $(this).attr('data-hash') } ).dialog('open');
  return false;
});

$('button.sn-delete-source').click(function() {
  $($(this).attr('href')).dialog('option', { title: 'Delete file: ' + $(this).attr('data-file-short'), file_path: $(this).attr('data-file'), file_hash: $(this).attr('data-hash') } ).dialog('open');
  return false;
});

$('#delete-dialog').dialog({'dialogClass': 'wp-dialog',
  'modal': true,
  'resizable': false,
  'zIndex': 9999,
  'width': 800,
  'height': 250,
  'hide': 'fade',
  'open': function(event, ui) { renderDelete(event, ui); fixDialogClose(event, ui); },
  'close': function() { jQuery('#delete-dialog').html('<p>Please wait.</p>') },
  'show': 'fade',
  'autoOpen': false,
  'closeOnEscape': true
});

$('#source-dialog').dialog({'dialogClass': 'wp-dialog',
  'modal': true,
  'resizable': false,
  'zIndex': 9999,
  'width': 800,
  'height': 550,
  'hide': 'fade',
  'open': function(event, ui) { renderSource(event, ui); fixDialogClose(event, ui); },
  'close': function() { jQuery('#source-dialog').html('<p>Please wait.</p>') },
  'show': 'fade',
  'autoOpen': false,
  'closeOnEscape': true
});

$('#restore-dialog').dialog({'dialogClass': 'wp-dialog',
 'modal': true,
 'resizable': false,
 'zIndex': 9999,
 'width': 600,
 'height': 350,
 'hide': 'fade',
 'open': function(event, ui) { renderRestore(event, ui); fixDialogClose(event, ui); },
 'close': function() { jQuery('#restore-dialog').html('<p>Please wait.</p>') },
 'show': 'fade',
 'autoOpen': false,
 'closeOnEscape': true
});
  // scan files
  $('#sn-run-core-scan').click(function(e){
    e.preventDefault();
    var data = {
      action: 'sn_core_run_scan',
      _ajax_nonce: wf_sn_cs.nonce
    };

    sn_block_ui('#sn-core-scanner');

    $.get(ajaxurl, data, function(response) {
      //console.log(response);
      if (response != '1') {
        alert('Error. Please check Events tab for details. Page will reload when you click OK.');
        window.location.reload();
      } else {
        window.location.reload();
      }
    }, 'html');
  }); // run tests
}); // onload


 function renderDelete(event) {
  let dialog_id = '#' + event.target.id;

  jQuery.post(ajaxurl, {
    action: 'sn_core_delete_file',
    filename: jQuery(dialog_id).dialog('option', 'file_path'),
    hash: jQuery(dialog_id).dialog('option', 'file_hash'),
    _ajax_nonce: wf_sn_cs.nonce


  }, function(response) {
    if (response) {
      if (response.err) {
        jQuery(dialog_id).html('<p><b>An error occured.</b> ' + response.err + '</p>');
      } else {
        jQuery(dialog_id).html(response.out);
        jQuery('#sn-delete-file').focus();
//todo - set focus on - #sn-delete-file
jQuery('#sn-delete-file').on('click', function(){
  jQuery(this).attr('disabled', 'disabled').attr('value', 'Please wait ...');
  jQuery.post(ajaxurl, {
    action: 'sn_core_delete_file_do',
    _ajax_nonce: wf_sn_cs.nonce,
    filename: jQuery(this).attr('data-filename')
  }, function(response) {
    if (response == '1') {
              // Removes the file from the list
              var hash = jQuery(dialog_id).dialog('option', 'file_hash');
              jQuery(dialog_id).dialog('close');
              jQuery('[data-hash="'+hash+'"]').closest('li').fadeOut( "slow", function() {
                jQuery(this).remove();
              });
            } else {
              alert('An error occured - ' + response);
              jQuery(this).attr('disabled', '').attr('value', 'Delete File');
            }
          });
});
}
} else {
  alert('An undocumented error occured. The page will reload.');
  window.location.reload();
}
}, 'json');


} // renderDelete


function renderSource(event) {
  let dialog_id = '#' + event.target.id;

  jQuery.post(ajaxurl, {
    action: 'sn_core_get_file_source',
    filename: jQuery('#source-dialog').dialog('option', 'file_path'),
    hash: jQuery('#source-dialog').dialog('option', 'file_hash'),
    _ajax_nonce: wf_sn_cs.nonce

  }, function(response) {
    if (response) {
      if (response.err) {
        jQuery(dialog_id).html('<p><b>An error occured.</b> ' + response.err + '</p>');
      } else {
        jQuery(dialog_id).html('<pre class="sn-core-source"></pre>');
        jQuery('pre', dialog_id).text(response.source);
        jQuery('pre', dialog_id).snippet(response.ext, {style: 'whitengrey'});
      }
    } else {
      alert('An undocumented error occured. The page will reload.');
      window.location.reload();
    }
  }, 'json');
} // renderSource


function renderRestore(event) {
  let dialog_id = '#' + event.target.id;

  jQuery.post(ajaxurl, {
    action: 'sn_core_restore_file',
    _ajax_nonce: wf_sn_cs.nonce,
    filename: jQuery(dialog_id).dialog('option', 'file_path'),
    hash: jQuery(dialog_id).dialog('option', 'file_hash')
  }, function(response) {
      if (response) {
        if (response.err) {
          jQuery(dialog_id).html('<p><b>An error occured.</b> ' + response.err + '</p>');
        } else {
          jQuery(dialog_id).html(response.out);

          jQuery('#sn-restore-file').on('click', function(){
            jQuery(this).attr('disabled', 'disabled').attr('value', 'Please wait ...');
            jQuery.post(ajaxurl, {
              action: 'sn_core_restore_file_do',
              _ajax_nonce: wf_sn_cs.nonce,
              filename: jQuery(this).attr('data-filename')
            }, function(response) {
              if (response == '1') {
              //alert('File successfully restored!\nThe page will reload and files will be rescanned.'); // a little more action...
              window.location.reload();
            } else {
              alert('An error occured - ' + response);
              jQuery(this).attr('disabled', '').attr('value', 'Restore file');
            }
          });
          });
        }
      } else {
        alert('An undocumented error occured. The page will reload.');
        window.location.reload();
      }
    }, 'json');
} // renderSource


function fixDialogClose(event) {
  jQuery('.ui-widget-overlay').bind('click', function(){ jQuery('#' + event.target.id).dialog('close'); });
} // fixDialogClose