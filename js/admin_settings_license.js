// Toggle for key input
jQuery(".handletbl").click(function () {
  $header = jQuery(this);
  $content = $header.next();
  $content.slideToggle(250, "swing", function () {
      jQuery("span.toggle-indicator", $header).toggleClass('dashicons-arrow-down');
  });
});
