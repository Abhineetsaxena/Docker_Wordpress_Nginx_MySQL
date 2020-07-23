(function ($) {
  "use strict";

  $(".le-section-title").click(function () {
    var scn = $(this).attr("data-section");
    $(".le-section-title").removeClass("active");
    $(this).addClass("active");

    localStorage.setItem('le-state', scn);

    $(".le-section.active").fadeOut("fast").removeClass("active").promise().done(function () {
      $("." + scn).fadeIn("fast").addClass("active");
    });

  });

  //restore last tab
  if (localStorage.getItem('le-state') !== null) {
    var section = localStorage.getItem('le-state');

    $(".le-section-title[data-section=" + section + "]").click();
  }

  // Since 2.5.0  
  $('.wple-tooltip').each(function () {
    var $this = $(this);

    tippy('.wple-tooltip', {
      //content: $this.attr('data-content'),
      placement: 'top',
      onShow(instance) {
        instance.popper.hidden = instance.reference.dataset.tippy ? false : true;
        instance.setContent(instance.reference.dataset.tippy);
      }
      //arrow: false
    });
  });

  $(".toggle-debugger").click(function () {
    $(this).find("span").toggleClass("rotate");

    $(".le-debugger").slideToggle('fast');
  });

  //since 4.6.0
  $("#admin-verify-dns").submit(function (e) {
    e.preventDefault();

    var $this = $(this);

    jQuery.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "text",
      data: {
        action: 'wple_admin_dnsverify',
        nc: $("#checkdns").val()
      },
      beforeSend: function () {
        $(".dns-notvalid").removeClass("active");
        $this.addClass("buttonrotate");
        $this.find("button").attr("disabled", true);
      },
      error: function () {
        $(".dns-notvalid").removeClass("active");
        $this.removeClass("buttonrotate");
        $this.find("button").removeAttr("disabled");
        alert("Something went wrong! Please try again");
      },
      success: function (response) {
        $this.removeClass("buttonrotate");
        $this.find("button").removeAttr("disabled");

        if (response === '1') {
          $this.find("button").text("Verified");
          setTimeout(function () {
            window.location.href = window.location.href + "&wpleauto=dns";
            exit();
          }, 1000);

          // } else if (response !== 'fail') {
          //   alert("Partially verified. Could not verify " + String(response));
        } else {
          $(".dns-notvalid").addClass("active");
        }
      }
    });

    return false;
  });

  //since 4.7.0
  $("#verify-subdns").click(function (e) {
    e.preventDefault();

    var $this = $(this);

    jQuery.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "text",
      data: {
        action: 'wple_admin_dnsverify',
        nc: $this.prev().val()
      },
      beforeSend: function () {
        $(".dns-notvalid").removeClass("active");
        $this.addClass("buttonrotate");
        $this.attr("disabled", true);
      },
      error: function () {
        $(".dns-notvalid").removeClass("active");
        $this.removeClass("buttonrotate");
        $this.removeAttr("disabled");
        alert("Something went wrong! Please try again");
      },
      success: function (response) {
        $this.removeClass("buttonrotate");
        $this.removeAttr("disabled");

        if (response === '1') {
          $this.text("Verified");
          setTimeout(function () {
            window.location.href = window.location.href + "&subdir=1&wpleauto=dns";
            exit();
          }, 1000);

          // } else if (response !== 'fail') {
          //   alert("Partially verified. Could not verify " + String(response));
        } else {
          $(".dns-notvalid").addClass("active");
        }
      }
    });

    return false;
  });

  $("#verify-subhttp").click(function (e) {
    e.preventDefault();

    var $this = $(this);

    jQuery.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "text",
      data: {
        action: 'wple_admin_httpverify',
        nc: $this.prev().val()
      },
      beforeSend: function () {
        $(".http-notvalid").removeClass("active");
        $this.addClass("buttonrotate");
        $this.attr("disabled", true);
      },
      error: function () {
        $(".http-notvalid").removeClass("active");
        $this.removeClass("buttonrotate");
        $this.removeAttr("disabled");
        alert("Something went wrong! Please try again");
      },
      success: function (response) {
        $this.removeClass("buttonrotate");
        $this.removeAttr("disabled");

        if (response === '1') {
          $this.text("Verified");
          setTimeout(function () {
            window.location.href = window.location.href + "&subdir=1&wpleauto=http";
            exit();
          }, 1000);

          // } else if (response !== 'fail') {
          //   alert("Partially verified. Could not verify " + String(response));
        } else {
          $(".http-notvalid").addClass("active");
        }
      }
    });

    return false;
  });

  //since 4.7.1
  $("#singledvssl").click(function (e) {
    //e.preventDefault();

    var flag = 0;
    if ($("input.wple_email").val() == '') {
      flag = 1;
      $("#wple-error-popper .wple-error").text('Email address is required');
      $("#wple-error-popper").fadeIn('slow');
    } else if (!$("input.wple_agree_le").is(":checked") || !$("input.wple_agree_gws").is(":checked")) {
      flag = 1;
      $("#wple-error-popper .wple-error").text('Agree to TOS required');
      $("#wple-error-popper").fadeIn('slow');
    }

    if (flag == 0) {
      $("#wple-error-popper .wple-error").hide();
      $("#wple-error-popper").fadeIn('fast');
      $("#wple-error-popper .wple-flex img").show();
      //$(this).closest(".le-genform").submit();
    } else {
      setTimeout(function () {
        $("#wple-error-popper").fadeOut(500);
      }, 2000);
      return false;
    }

  });

  
/* Premium Code Stripped by Freemius */



})(jQuery);