"use strict";function sn_block_ui(e){jQuery("html.wp-toolbar").addClass("sn-overlay-active"),jQuery("#wpadminbar").addClass("sn-overlay-active"),jQuery("#sn_overlay .wf-sn-overlay-outer").css("height",jQuery(window).height()-200+"px"),jQuery("#sn_overlay").show(),e&&jQuery(e,"#sn_overlay").show()}function sn_fix_dialog_close(e){jQuery(".ui-widget-overlay").bind("click",(function(){jQuery("#"+e.target.id).dialog("close")}))}function sn_unblock_ui(e){jQuery("html.wp-toolbar").removeClass("sn-overlay-active"),jQuery("#wpadminbar").removeClass("sn-overlay-active"),jQuery("#sn_overlay").hide(),e&&jQuery(e,"#sn_overlay").hide()}jQuery(document).ready((function(){jQuery(document).on("click","#run-selected-tests",(function(e){e.preventDefault(),jQuery("#run-selected-tests").attr("disabled",!0);var t=[],s="";jQuery("input[name='sntest[]']").each((function(){this.checked&&(s=jQuery(this).val(),jQuery(".test_"+s).addClass("testing"),jQuery(".test_"+s+" .spinner").addClass("is-active"),jQuery(".test_"+s+" .sn-result-details").hide(),t.push(s))})),function e(t,s,a){var n=s[t];jQuery(".test_"+n).addClass("testing");jQuery(".test_"+n+" .spinner").addClass("is-active");jQuery(".test_"+n+" .sn-result-details").hide();jQuery.ajax({type:"POST",url:ajaxurl,data:{_ajax_nonce:wf_sn.nonce_run_tests,testarr:s,action:"sn_run_single_test",stepid:t},dataType:"json",success:function(t){jQuery(".test_"+n+" .spinner").removeClass("is-active"),jQuery(".test_"+n+" .wf-sn-label").replaceWith(t.data.label).fadeIn("slow"),jQuery(".test_"+n).removeClass("testing");var i=t.data.msg;t.data.details&&(i=i+" "+t.data.details),jQuery(".test_"+n+" .sn-result-details").replaceWith('<span class="sn-result-details">'+i+"</span>").fadeIn("slow"),jQuery(".test_"+n).removeClass("wf-sn-test-row-status-0").removeClass("wf-sn-test-row-status-5").removeClass("wf-sn-test-row-status-10").addClass("wf-sn-test-row-status-"+t.data.status),jQuery(".test_"+n+' input[type="checkbox"]').prop("checked",!1),t.data.scores.output&&jQuery("#testscores").html(t.data.scores.output),"-1"==t.data.nexttest||parseInt(t.data.nexttest)>0&&e(parseInt(t.data.nexttest),s,a)}}).fail((function(e){window.console&&window.console.log&&window.console.log(e.statusCode+" "+e.statusText)}))}(0,t,self),jQuery("#run-selected-tests").attr("disabled",!1)})),jQuery(document).on("click","#sn-quickselect-all",(function(e){e.preventDefault(),jQuery("#security-ninja :checkbox").prop("checked",!0),jQuery("#security-ninja tr.test").fadeIn("fast")})),jQuery(document).on("click","#sn-quickselect-failed",(function(e){e.preventDefault(),jQuery("#security-ninja :checkbox").prop("checked",!1),jQuery("#security-ninja .wf-sn-test-row-status-0 :checkbox").prop("checked",!0),jQuery("#security-ninja .wf-sn-test-row-status-10").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-5").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-0").fadeIn("fast")})),jQuery(document).on("click","#sn-quickselect-warning",(function(e){e.preventDefault(),jQuery("#security-ninja :checkbox").prop("checked",!1),jQuery("#security-ninja .wf-sn-test-row-status-5 :checkbox").prop("checked",!0),jQuery("#security-ninja .wf-sn-test-row-status-10").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-0").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-5").fadeIn("fast")})),jQuery(document).on("click","#sn-quickselect-okay",(function(e){e.preventDefault(),jQuery("#security-ninja :checkbox").prop("checked",!1),jQuery("#security-ninja .wf-sn-test-row-status-10 :checkbox").prop("checked",!0),jQuery("#security-ninja .wf-sn-test-row-status-0").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-5").fadeOut("fast"),jQuery("#security-ninja .wf-sn-test-row-status-10").fadeIn("fast")})),jQuery(".wfsn-dismiss-review-notice, .wfsn-review-notice .notice-dismiss").on("click",(function(){jQuery(this).hasClass("wfsn-reviewlink")||event.preventDefault(),jQuery.post(ajaxurl,{action:"wf_sn_dismiss_review"}),jQuery(".wfsn-review-notice").slideUp().remove()})),jQuery("#test-details-dialog").dialog({dialogClass:"wp-dialog sn-dialog",modal:!0,resizable:!1,zIndex:9999,width:750,height:"auto",hide:"fade",open:function(e,t){sn_fix_dialog_close(e,t)},close:function(){jQuery("#test-details-dialog").html("<p>Please wait.</p>")},show:"fade",autoOpen:!1,closeOnEscape:!0}),jQuery(document).on("click",".openhelpscout",(function(){Beacon("open")}));var e=window.location.hash;if(e){var t=jQuery(window).scrollTop();jQuery("#wf-sn-tabs").find("a").removeClass("nav-tab-active"),jQuery(".wf-sn-tab").removeClass("active"),jQuery('a[href="'+e+'"]').addClass("nav-tab-active").removeClass("hidden"),jQuery(e).addClass("active"),jQuery(this).addClass("nav-tab-active"),jQuery(window).scrollTop(t),jQuery('[name="_wp_http_referer"]').val(window.location)}jQuery("#wf-sn-tabs").tabs({activate:function(e,t){var s=jQuery(window).scrollTop();window.location.hash=t.newPanel.attr("id"),jQuery(window).scrollTop(s)}}).fadeIn("fast"),jQuery("#tabs").tabs({activate:function(){jQuery.cookie("sn_tabs_selected",jQuery("#tabs").tabs("option","active"))},active:jQuery("#tabs").tabs({active:jQuery.cookie("sn_tabs_selected")})}),jQuery("#wf-sn-tabs").find("a").click((function(e){e.preventDefault(),jQuery("#wf-sn-tabs").find("a").removeClass("nav-tab-active"),jQuery(".wf-sn-tab").removeClass("active");var t=jQuery(this).attr("id").replace("-tab",""),s=jQuery("#"+t);s.addClass("active"),jQuery(this).addClass("nav-tab-active"),s.hasClass("nosave")?jQuery("#submit").hide():jQuery("#submit").show();var a=jQuery(window).scrollTop();window.location.hash=t,jQuery(window).scrollTop(a),jQuery('[name="_wp_http_referer"]').val(window.location)})),jQuery(document).on("click","#wf-import-settings-button",(function(){return!!confirm("Are you sure you want to import and overwrite the current settings?")})),jQuery("#abort-scan").on("click",(function(e){e.preventDefault(),window.location.reload()})),jQuery("#sn_tests .sn-details a.button").live("click",(function(e){e.preventDefault();var t=jQuery(this).data("test-id"),s=jQuery("#"+t+" .test_name").text(),a=jQuery("#"+t+" .test_description").html();return""===s?(s="Unknown test ID",a="Help is not available for this test. Make sure you have the latest version of Security Ninja installed."):(a='<span class="ui-helper-hidden-accessible"><input type="text"></span><div id="testtimedetails"><span class="spinner"></span></div>'+jQuery("#"+t+" .test_description").html(),a+='<div id="auto-fixer-content-cont"><hr><h3>Auto Fixer</h3><div id="auto-fixer-content"></div></div>'),jQuery("#test-details-dialog").html(a),jQuery("#test-details-dialog").dialog("option",{title:s,test_id:t}).dialog("open"),jQuery(document).trigger("sn_test_details_dialog_open",[t,jQuery(this).data("test-status")]),jQuery("#testtimedetails .spinner").addClass("is-active"),jQuery.ajax({type:"POST",url:ajaxurl,data:{_ajax_nonce:wf_sn.nonce_run_tests,action:"sn_get_single_test_details",testid:t},dataType:"json",success:function(e){e.success&&(e.data.runtime&&jQuery("#testtimedetails").prepend('<span id="runtime"> Runtime: '+e.data.runtime+" sec.</span>"),e.data.timestamp&&jQuery("#testtimedetails").prepend('<span id="lasttest">Last test: '+e.data.timestamp+"</span>")),jQuery("#testtimedetails .spinner").remove()},error:function(){jQuery("#testtimedetails .spinner").remove()}}),!1}))}));