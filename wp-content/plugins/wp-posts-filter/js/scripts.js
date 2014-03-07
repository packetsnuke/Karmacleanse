function wppf_toggle(name) {
    if(jQuery("#" + name + "_sw").is(":checked")) {
        jQuery("." + name).attr("disabled", false);
    } else {
        jQuery("." + name).attr("disabled", true);
    }
}

function wppf_uncheck_check_all(event_starter_id) {
    if(!jQuery("#" + event_starter_id).attr("checked")) {
        jQuery("#" + event_starter_id).parents("fieldset:eq(0)").find(".checkall").attr("checked", false);
    } else {
        if(jQuery("#" + event_starter_id).parents("fieldset:eq(0)").find(".wppf_opts_checkboxes").length == jQuery("#" + event_starter_id).parents("fieldset:eq(0)").find(".wppf_opts_checkboxes:checked").length) {
            jQuery("#" + event_starter_id).parents("fieldset:eq(0)").find(".checkall").attr("checked", true);
        }
    }
}

jQuery(document).ready(function() {
    jQuery("[id^=wppf_box_]").each(function() {
        if(!jQuery(this).hasClass("closed")) {
            jQuery(this).addClass("closed");
        }
    });
});

jQuery(function() {
    jQuery(".checkall").click(function() {
        jQuery(this).parents("fieldset:eq(0)").find(":checkbox").attr("checked", this.checked);
    });
});
