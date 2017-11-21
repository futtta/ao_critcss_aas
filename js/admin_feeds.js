var feed   = new Array;
feed[1]    = "autoptimizefeed";
feed[2]    = "wordpressfeed";
feed[3]    = "webtechfeed";
cookiename = "autoptimize_feed";

jQuery(document).ready(function() {
  check_ini_state();
  jQuery('#autoptimize_admin_feed').fadeTo("slow",1).show();
  jQuery('.autoptimize_banner').unslider({autoplay:true, delay:3500, infinite: false, arrows:{prev:'<a class="unslider-arrow prev"></a>', next:'<a class="unslider-arrow next"></a>'}}).fadeTo("slow",1).show();

  jQuery( "#feed_dropdown" ).change(function() {
    jQuery("#futtta_feed").fadeTo(0,0);
    jQuery("#futtta_feed").fadeTo("slow",1);
  });

  jQuery( "#ao_show_adv" ).click(function() {
    jQuery( "#ao_show_adv" ).hide();
    jQuery( "#ao_hide_adv" ).show();
    jQuery( ".ao_adv" ).removeClass("hidden");
    jQuery( ".ao_adv" ).show("slow");
    if (jQuery("#autoptimize_css").attr('checked')) {
      jQuery(".css_sub:visible").fadeTo("fast",1);
      if (!jQuery("#autoptimize_css_defer").attr('checked')) {
        jQuery("#autoptimize_css_defer_inline").hide();
      }
    }
    if (jQuery("#autoptimize_js").attr('checked')) {
      jQuery(".js_sub:visible").fadeTo("fast",1);
    }
    check_ini_state()
    jQuery( "input#autoptimize_show_adv" ).val("1");
  });

  jQuery( "#ao_hide_adv" ).click(function() {
    jQuery( "#ao_hide_adv" ).hide();
    jQuery( "#ao_show_adv" ).show();
    jQuery( ".ao_adv" ).hide("slow");
    jQuery( ".ao_adv" ).addClass("hidden");
    if (!jQuery("#autoptimize_css").attr('checked')) {
      jQuery(".css_sub:visible").fadeTo("fast",.33);
    }
    if (!jQuery("#autoptimize_js").attr('checked')) {
      jQuery(".js_sub:visible").fadeTo("fast",.33);
    }
    check_ini_state()
    jQuery( "input#autoptimize_show_adv" ).val("0");
  });

  jQuery( "#autoptimize_html" ).change(function() {
    if (this.checked) {
      jQuery(".html_sub:visible").fadeTo("fast",1);
    } else {
      jQuery(".html_sub:visible").fadeTo("fast",.33);
    }
  });

  jQuery( "#autoptimize_js" ).change(function() {
    if (this.checked) {
      jQuery(".js_sub:visible").fadeTo("fast",1);
    } else {
      jQuery(".js_sub:visible").fadeTo("fast",.33);
    }
  });

  jQuery( "#autoptimize_css" ).change(function() {
    if (this.checked) {
      jQuery(".css_sub:visible").fadeTo("fast",1);
    } else {
      jQuery(".css_sub:visible").fadeTo("fast",.33);
    }
  });

  jQuery( "#autoptimize_css_inline" ).change(function() {
    if (this.checked) {
      jQuery("#autoptimize_css_defer").prop("checked",false);
      jQuery("#autoptimize_css_defer_inline").hide("slow");
    }
  });

  jQuery( "#autoptimize_css_defer" ).change(function() {
    if (this.checked) {
      jQuery("#autoptimize_css_inline").prop("checked",false);
      jQuery("#autoptimize_css_defer_inline").show("slow");
    } else {
      jQuery("#autoptimize_css_defer_inline").hide("slow");
    }
  });

  jQuery("#feed_dropdown").change(function() { show_feed(jQuery("#feed_dropdown").val()) });
  feedid=jQuery.cookie(cookiename);
  if(typeof(feedid) !== "string") feedid=1;
  show_feed(feedid);
})

function check_ini_state() {
  if (!jQuery("#autoptimize_css_defer").attr('checked')) {
    jQuery("#autoptimize_css_defer_inline").hide();
  }
  if (!jQuery("#autoptimize_html").attr('checked')) {
    jQuery(".html_sub:visible").fadeTo('fast',.33);
  }
  if (!jQuery("#autoptimize_css").attr('checked')) {
    jQuery(".css_sub:visible").fadeTo('fast',.33);
  }
  if (!jQuery("#autoptimize_js").attr('checked')) {
    jQuery(".js_sub:visible").fadeTo('fast',.33);
  }
}

function show_feed(id) {
  jQuery('#futtta_feed').children().hide();
  jQuery('#'+feed[id]).show();
  jQuery("#feed_dropdown").val(id);
  jQuery.cookie(cookiename,id,{ expires: 365 });
}