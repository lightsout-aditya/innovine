import { auto } from "@popperjs/core";

window.$ = window.jQuery = require("jquery");

import "bootstrap";

import "./styles/app.scss";

require("@splidejs/splide");
require("@splidejs/splide/dist/css/splide.min.css");
import { Splide } from "@splidejs/splide";
import { AutoScroll } from "@splidejs/splide-extension-auto-scroll";

import GLightbox from "glightbox";
import "glightbox/dist/css/glightbox.min.css";

const ajax = function (config, loader) {
  let Deferred = $.Deferred(),
    $loader = $(".loader");

  if (loader) {
    $loader.show();
  }
  $.ajax(
    $.extend(
      {
        method: "post",
        success: function (data, status, xhr) {
          Deferred.resolve(data, status, xhr);
        },
        error: function (status, data) {
          Deferred.resolve(status);
        },
      },
      config
    )
  ).done(function () {
    if (loader) {
      $loader.hide();
    }
  });
  return Deferred.promise();
};

const imJs = {
  m: function (e) {
    imJs.d();
    imJs.methods();
  },
  d: function (e) {
    this._window = $(window);
    this._document = $(document);
    this._body = $("body");
    this._html = $("html");
  },

  methods: function (e) {
    imJs.stickyHeader();
  },

  stickyHeader: function (e) {
    $(window).scroll(function () {
      if ($(this).scrollTop() > 200) {
        $("#navbar_top").addClass("fixed-top");
      } else {
        $("#navbar_top").removeClass("fixed-top");
      }
    });
  },
};
imJs.m();

$(function () {
  const body = $("body");
  const cart = $("#cart");
  let maxQty = parseInt($(".quantity-widget").data("max") || 800);

  function updateCart(el, qty) {
    let isCart = el.parents(".cart-item");
    if (isCart.length) {
      let hasCanvas = el.parents(".offcanvas");
      ajax({
        url: "/cart/update",
        data: { id: el.parents(".card").data("item"), qty: qty },
      }).then(function (r) {
        if (r.success) {
          if (hasCanvas.length && cart.length) {
            cart.html(r.carts);
          } else {
            location.reload();
          }
        }
      });
    }
  }

  body
    .on(
      "keypress",
      "input[type=number], input[type=tel], .only-number, .only-decimal",
      function (e) {
        let charCode = e.which ? e.which : e.keyCode;
        let char = String.fromCharCode(charCode);
        let decimal = $(this).hasClass("only-decimal");
        if (decimal && char === "." && e.target.value.indexOf(".") !== -1) {
          return false; // If a decimal point already exists, don't allow another
        }
        return !char.match(decimal ? /[^0-9.]/g : /[^0-9]/g);
      }
    )
    .on("keypress", ".only-alphabet", function (e) {
      let charCode = e.which ? e.which : e.keyCode;
      return !String.fromCharCode(charCode).match(/[^a-z ]/gi);
    })
    .on("click", ".password-toggle", function () {
      let input = $(this).prev();
      input.attr(
        "type",
        input.prop("type") === "password" ? "text" : "password"
      );
      $(this).toggleClass("fa-eye fa-eye-slash");
    })
    .on("change", ".form-floating .form-select", function () {
      $(this).toggleClass("blank", !$(this).val());
    })
    .on("keyup change", ".validate:not(:disabled)", function () {
      let valid = $(this).is(":not(:invalid)");
      $(this).toggleClass("is-invalid", !valid);
      $(this).toggleClass("is-valid", valid);
    })
    .on("keyup change", ".input-again.validate:not(:disabled)", function () {
      let field = $(this).data("again");
      let valid = $(this).val() === $("." + field).val();
      this.setCustomValidity(valid ? "" : "Value not matching");
      $(this).toggleClass("is-invalid", !valid);
      $(this).toggleClass("is-valid", valid);
    })
    .on("click", ".clear-search", function (e) {
      let form = $(".search-form");
      form[0].reset();
      form.submit();
    })
    .on("click", ".quantity-widget button", function () {
      let input = $(this).parents(".quantity-widget").find("input");
      let qty = Math.min(
        Math.max(
          1,
          (parseInt(input.val()) || 0) + (1 * $(this).hasClass("plus") ? 1 : -1)
        ),
        maxQty
      );
      input.val(qty).change();
      //updateCart($(this), qty);
    })
    .on("change", ".quantity-widget input", function () {
      let valid = $(this).is(":not(:invalid)");
      if ($(this).val() < 2) {
        $(this).val(1);
      } else if ($(this).val() > maxQty) {
        $(this).val(maxQty);
      }
      if (valid) {
        updateCart($(this), $(this).val());
      }
    })
    .on("click", ".delete-cart", function () {
      let btn = $(this);
      let hasCanvas = $(this).parents(".offcanvas");
      ajax({ url: $(this).data("url") }).then(function (r) {
        if (r.success) {
          if (hasCanvas.length && cart.length) {
            btn.parents(".card").fadeOut("slow");
            cart.html(r.carts);
            $(".cart-counter").text(cart.find(".cart-item ").length);
          } else {
            location.reload();
          }
        }
      });
    })
    .on("change", ".stateCode", function () {
      body.find(".stateName").val($(this).find("option:selected").text());
    });

  if ($(".glightbox").length) {
    const lightbox = GLightbox({
      loop: true,
      zoomable: true,
    });
  }

  const checkout = $("section.checkout");
  if (checkout.length) {
    let checkoutBtn = $(".btn-checkout");
    let paymentModal = $("#paymentModal");
    checkout
      .on("change", "input[name=shippingAddress]", function () {
        ajax({
          url: "/checkout/summary",
          data: { address: $(this).val() },
        }).then(function (res) {
          $(".order-summary").html(res);
          checkoutBtn.prop("disabled", false);
        });
      })
      .on("click", ".btn-checkout:not([data-bs-toggle])", function (e) {
        e.preventDefault();
        let alert = checkout.find(".alert");
        checkoutBtn.prop("disabled", true).addClass("btn-spin");
        ajax({
          url: "/checkout",
          data: {
            address: $("input[name=shippingAddress]:checked").val(),
            billing: $("input[name=billingAddress]:checked").val(),
            sameShipping: $("#sameShipping").is(":checked"),
          },
        }).then(function (res) {
          if (res.success) {
            res.options.modal = {
              ondismiss: function () {
                checkoutBtn.prop("disabled", false).removeClass("btn-spin");
              },
            };
            const rzp1 = new Razorpay(res.options);
            rzp1.open();
          } else {
            alert
              .html(res.message)
              .addClass(res.success ? "alert-success" : "alert-danger")
              .removeClass("d-none");
            checkoutBtn.prop("disabled", false).removeClass("btn-spin");
          }
        });
      })
      .on("change", "#sameShipping", function () {
        let isChecked = $(this).is(":checked");
        $(".billing-card")
          .slideToggle(isChecked)
          .find("input")
          .prop("disabled", isChecked);
      });
    if ($("input[name=shippingAddress]:checked").length) {
      checkoutBtn.prop("disabled", false);
    }
    if (paymentModal.length) {
      paymentModal.on("submit", "form", function (e) {
        e.preventDefault();
        let form = $(this);
        let formData = new FormData(this);
        let submitBtn = form.find(".submit-btn");
        let alert = form.find(".alert");
        formData.append(
          "address",
          $("input[name=shippingAddress]:checked").val()
        );
        formData.append(
          "billing",
          $("input[name=billingAddress]:checked").val()
        );
        formData.append("sameShipping", $("#sameShipping").is(":checked"));
        submitBtn.prop("disabled", true).addClass("btn-spin");
        alert.addClass("d-none");
        ajax({
          url: $(this).attr("action"),
          data: formData,
          processData: false,
          contentType: false,
        }).then(function (res) {
          if (res.success) {
            location.href = res.redirect;
          } else {
            alert
              .html(res.message)
              .addClass(res.success ? "alert-success" : "alert-danger")
              .removeClass("d-none");
            submitBtn.prop("disabled", false).removeClass("btn-spin");
          }
        });
      });
    }
  }

  const contactForm = $(".contact-form");
  if (contactForm.length) {
    contactForm.on("submit", function (e) {
      let submitBtn = $(".submit-btn");
      e.preventDefault();
      let form = $(this);
      let alert = form.find(".alert");
      submitBtn.prop("disabled", true).addClass("btn-spin");
      $.ajax({
        url: form.attr("action"),
        data: form.serialize(),
        method: "POST",
      }).done(function (res) {
        submitBtn.prop("disabled", false).removeClass("btn-spin");
        alert
          .html(res.message)
          .removeClass("alert-success alert-danger")
          .addClass(res.success ? "alert-success" : "alert-danger")
          .removeClass("d-none");
        if (res.success) {
          form[0].reset();
        }
      });
    });
  }

  if ($(".splide.trending-product").length) {
    const trendingProductSlider = new Splide(".splide.trending-product", {
      type: "loop",
      gap: 20,
      lazyLoad: "nearby",
      height: "100%",
      perPage: 3,
      perMove: 1,
      breakpoints: {
        768: {
          perPage: 1,
          arrows: false,
        },
        1024: {
          perPage: 2,
        },
      },
    }).mount();
  }
  if ($(".splide.combined-kit").length) {
    const combinedKitSlider = new Splide(".splide.combined-kit", {
      type: "loop",
      gap: 20,
      height: "100%",
      perPage: 3,
      perMove: 1,
      breakpoints: {
        768: {
          perPage: 1,
          arrows: false,
        },
        1024: {
          perPage: 2,
        },
      },
    }).mount();
  }
  if ($("#main-slider").length) {
    const main = new Splide("#main-slider", {
      type: "fade",
      heightRatio: 0.8,
      pagination: false,
      arrows: false,
      cover: true,
    });

    const thumbnails = new Splide("#thumbnail-slider", {
      rewind: true,
      fixedWidth: 100,
      fixedHeight: 60,
      isNavigation: true,
      gap: 5,
      focus: "center",
      pagination: false,
      cover: true,
      arrows: false,
      dragMinThreshold: {
        mouse: 4,
        touch: 10,
      },
    });

    main.sync(thumbnails);
    main.mount();
    thumbnails.mount();
  }

  const addToCart = $(".add-to-cart");
  if (addToCart.length) {
    addToCart.on("click", function (e) {
      $(this).prop("disabled", true).addClass("btn-spin bg-primary");
      let wrapper = $(this).parents(".cart-wrapper");
      let qty = wrapper.length
        ? wrapper.find(".quantity-widget input").val()
        : 1;
      let mItems = {};
      if (wrapper.length) {
        let mandatoryItems = wrapper.find(".mandatory-items");
        if (mandatoryItems.length) {
          mandatoryItems.find(".card").each(function () {
            $(this)
              .find("input")
              .each(function () {
                if ($(this).val()) {
                  let row = $(this).parents("tr");
                  if (row.length) {
                    mItems[row.find("select").val()] =
                      (mItems[row.find("select").val()]
                        ? mItems[row.find("select").val()]
                        : 0) + parseInt($(this).val());
                  } else {
                    mItems[$(this).data("id")] =
                      (mItems[$(this).data("id")]
                        ? mItems[$(this).data("id")]
                        : 0) + parseInt($(this).val());
                  }
                }
              });
          });
        }
      }
      $.ajax({
        url: "/cart/add",
        method: "POST",
        data: { id: $(this).data("id"), qty: qty, mItems },
      }).done(function (res) {
        $(this).prop("disabled", true).removeClass("btn-spin");
        location.reload();
      });
    });
  }

  const filterSelect = $(".filter-select");
  if (filterSelect.length) {
    filterSelect.on("change", function () {
      location.href = $(this).val();
    });
  }

  const filterGender = $(".filter-gender");
  if (filterGender.length) {
    filterGender.on("change", function () {
      ajax({ url: $(this).data("url"), data: { g: $(this).val() } }).then(
        function () {
          location.reload();
        }
      );
    });
  }

  const deleteAddress = $(".delete-address");
  if (deleteAddress.length) {
    deleteAddress.on("click", function () {
      ajax({ url: $(this).data("url") }).then(function (r) {
        location.reload();
      });
    });
  }

  const signupForm = $("form[name=signup]");
  if (signupForm.length) {
    signupForm.on("submit", function () {
      signupForm
        .find(".signup-btn")
        .prop("disabled", true)
        .addClass("btn-spin");
    });
  }

  function fetchOptions(type, target, data) {
    const url = "/fetch/options/" + type;
    ajax({ url: url, data: data }).then(function (r) {
      target.html(r);
      if (target.data("val")) {
        target.val(target.data("val"));
        target.removeAttr("data-val");
      }
      target.change();
    });
  }
  function initFetchOption(type, target, data) {
    let fetchOption = $(".fetch-option.auto");
    if (fetchOption.length) {
      fetchOption.each(function () {
        fetchOptions($(this).attr("name"), $(this), {});
      });
    }
  }
  body.on("change", ".fetch-option", function () {
    const form = $(this).parents("form");
    const type = $(this).data("fetch");
    if (type) {
      fetchOptions(type, form.find("#" + type), { filter: $(this).val() });
    }
  });

  let addressModal = $("#addressModal");
  if (addressModal.length) {
    addressModal
      .on("show.bs.modal", function (e) {
        ajax({ url: $(e.relatedTarget).data("url"), method: "get" }).then(
          function (r) {
            addressModal.find(".modal-content").html(r);
            initFetchOption();
          }
        );
      })
      .on("submit", "form", function (e) {
        e.preventDefault();
        let form = $(this);
        form
          .find(".btn.btn-primary")
          .prop("disabled", true)
          .addClass("btn-spin");
        ajax({
          url: form.attr("action"),
          data: form.serialize(),
        }).then(function (r) {
          location.reload();
          form
            .find(".btn.btn-primary")
            .prop("disabled", true)
            .removeClass("btn-spin bg-primary");
        });
      });
  }

  let searchBtn = $(".home-search-btn");
  if (searchBtn.length) {
    searchBtn.on("click", function () {
      let term = $.trim($(this).prev("input").val());
      if (term) {
        location.href = "products?s=" + term;
      }
    });
  }

  const profileAvatar = $(".profile-avatar");
  if (profileAvatar.length) {
    let preview = profileAvatar.find(".preview");
    profileAvatar
      .on("change", 'input[type="file"].img', function () {
        let file = this.files[0];
        if (preview.length) {
          preview.attr("src", "");
          preview.attr("src", URL.createObjectURL(file));
        }
      })
      .on("click", ".fa-cloud-upload", function () {
        profileAvatar.find('input[type="file"]').click();
      })
      .on("change", ".remove-check", function () {
        if ($(this).is(":checked")) {
          preview.attr("src", preview.data("src"));
          $(this).parent("label").hide();
        }
      });
  }

  const trackingModal = $("#trackingModal");
  trackingModal.on("show.bs.modal", function (e) {
    let modelBody = trackingModal.find(".modal-content");
    modelBody.html("");
    $.ajax({
      url: "/tracking",
      method: "POST",
      data: { id: $(e.relatedTarget).data("id") },
    }).done(function (res) {
      modelBody.html(res);
    });
  });

  const gstIn = $(".gst-in");
  if (gstIn.length) {
    let pan = $(".company-pan");
    gstIn.on("keyup", function () {
      let self = $(this);
      let gstNum = $(this).val();
      $(".company-name, .company-pan").val("");
      pan.keyup();
      pan.prop("required", gstNum);
      if (gstNum.length === 0) {
        setTimeout(function () {
          gstIn
            .add(pan)
            .removeClass("is-valid is-invalid validate")
            .blur()
            .addClass("validate");
        }, 200);
      } else if (gstNum.length === 15) {
        $.ajax({
          url: "/getGst",
          method: "POST",
          data: { gst: gstNum },
        }).done(function (res) {
          if (res.status) {
            $(".company-name").val(res.message).keyup();
            pan.val(gstNum.substring(2, 12)).keyup();
          }
          self.toggleClass("is-invalid", !res.status);
          self.toggleClass("is-valid", res.status);
          self
            .parent()
            .find(res.status ? ".valid-feedback" : ".invalid-feedback")
            .html(res.message);
          self[0].setCustomValidity(res.status ? "" : res.message);
        });
      }
    });
    pan.on("keyup", function () {
      pan.prop("required", gstIn.val());
    });
  }

  $('button[data-bs-target="#returnModal"]').on("click", function () {
    let orderID = $(this).data("order-id");
    $("#orderInput").val(orderID);
  });

  let orderReturnForm = $("#orderReturn");
  if (orderReturnForm.length) {
    orderReturnForm.on("submit", function (e) {
      e.preventDefault();
      let form = $(this);
      let alert = form.find(".alert");
      let formData = new FormData(this);
      ajax({
        url: form.attr("action"),
        data: formData,
        enctype: "multipart/form-data",
        cache: false,
        contentType: false,
        processData: false,
      }).done(function (r) {
        if (r.success) {
          form[0].reset();
          location.reload();
        } else {
          alert
            .html(r.message)
            .addClass(r.success ? "alert-success" : "alert-danger")
            .removeClass("d-none");
        }
      });
    });
  }
  let mandatoryItems = $(".mandatory-items");
  if (mandatoryItems.length) {
    let cartQty = 1;

    function validateAndCorrectSum(currentInput) {
      let inputs = currentInput.parents("table").find("input");
      //let targetSum = parseInt(inputs.first().data('qty')) * cartQty;
      let targetSum = cartQty;
      const inputValues = [];

      let total = 0;
      inputs.each(function () {
        inputValues.push(parseInt($(this).val()) || 0);
        total += parseInt($(this).val()) || 0;
      });

      if (inputValues.length === 1) {
        currentInput.val(targetSum);
      } else {
        if (parseInt(currentInput.val()) > targetSum) {
          currentInput.val(targetSum);
        }
        let difference = targetSum - total;
        let temp = 0;
        inputs.each(function () {
          if (this !== currentInput[0]) {
            $(this).val(Math.max(0, parseInt($(this).val()) + difference));
            inputs.each(function () {
              temp += parseInt($(this).val()) || 0;
            });
            if (temp === targetSum) {
              return false;
            }
          }
        });
      }
    }

    mandatoryItems
      .on("click", ".fa-plus-circle", function () {
        let row = $(this).parents("tr");
        let clonedRow = row.clone();
        clonedRow
          .find(".fa-plus-circle")
          .removeClass("fa-plus-circle")
          .addClass("fa-minus-circle");
        clonedRow.find("input").val(0).data("qty", 0);
        row.after(clonedRow);
      })
      .on("click", ".fa-minus-circle", function () {
        $(this).parents("tr").remove();
      })
      .on("change", "input", function () {
        validateAndCorrectSum($(this));
      });

    let cartWrapper = $(".cart-wrapper");
    cartWrapper.on("change", ".quantity-widget input", function () {
      cartQty = parseInt($(this).val());
      mandatoryItems.find(".card").each(function () {
        $(this)
          .find("input")
          .each(function (i) {
            //$(this).val(parseInt($(this).data('qty')) * cartQty);
            $(this).val((i ? 0 : 1) * cartQty);
          });
      });
    });
  }
});
