// Init single surveyor
function init_surveyor(userid) {
    load_small_table_item(userid, '#surveyor', 'surveyorid', 'surveyors/get_surveyor_data_ajax', '.table-surveyors');
}


// Validates surveyor add/edit form
function validate_surveyor_form(selector) {

    selector = typeof (selector) == 'undefined' ? '#surveyor-form' : selector;

    appValidateForm($(selector), {
        clientid: {
            required: {
                depends: function () {
                    var customerRemoved = $('select#clientid').hasClass('customer-removed');
                    return !customerRemoved;
                }
            }
        },
        date: 'required',
        office_id: 'required',
        number: {
            required: true
        }
    });

    $("body").find('input[name="number"]').rules('add', {
        remote: {
            url: admin_url + "surveyors/validate_surveyor_number",
            type: 'post',
            data: {
                number: function () {
                    return $('input[name="number"]').val();
                },
                isedit: function () {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function () {
                    return $('input[name="number"]').data('original-number');
                },
                date: function () {
                    return $('body').find('.surveyor input[name="date"]').val();
                },
            }
        },
        messages: {
            remote: app.lang.surveyor_number_exists,
        }
    });

}


// Get the preview main values
function get_surveyor_item_preview_values() {
    var response = {};
    response.description = $('.main textarea[name="description"]').val();
    response.long_description = $('.main textarea[name="long_description"]').val();
    response.qty = $('.main input[name="quantity"]').val();
    return response;
}


// From surveyor table mark as
function surveyor_mark_as(state_id, surveyor_id) {
    var data = {};
    data.state = state_id;
    data.surveyorid = surveyor_id;
    $.post(admin_url + 'surveyors/update_surveyor_state', data).done(function (response) {
        //table_surveyors.DataTable().ajax.reload(null, false);
        reload_surveyors_tables();
    });
}

// Reload all surveyors possible table where the table data needs to be refreshed after an action is performed on task.
function reload_surveyors_tables() {
    var av_surveyors_tables = ['.table-surveyors', '.table-rel-surveyors'];
    $.each(av_surveyors_tables, function (i, selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}


function init_permit() {

  // On hidden modal permit set all values to empty and set the form action to ADD in case edit was clicked
  $("body").on("hidden.bs.modal", ".modal-permit", function (e) {
    var $this = $(this);
    var rel_id = $this.find('input[name="rel_id"]').val();
    var rel_type = $this.find('input[name="rel_type"]').val();
    $this
      .find("form")
      .attr(
        "action",
        admin_url + "surveyors/add_permit/" + rel_id + "/" + rel_type
      );
    $this.find("form").removeAttr("data-edit");
    $this.find(":input:not([type=hidden]), textarea").val("");
    $this.find('input[type="checkbox"]').prop("checked", false);
    $this.find("select").selectpicker("val", "");
  });

  // Focus the date field on permit modal shown
  $("body").on("shown.bs.modal", ".modal-permit", function (e) {
    if ($(this).find('form[data-edit="true"]').length == 0) {
      $(this).find("#date").focus();
    }
  });

  // On delete permit reload the tables
  $("body").on("click", ".delete-permit", function () {
    if (confirm_delete()) {
      requestGetJSON($(this).attr("href")).done(function (response) {
        alert_float(response.alert_type, response.message);
        if ($("#task-modal").is(":visible")) {
          _task_append_html(response.taskHtml);
        }
        reload_permits_tables();
      });
    }
    return false;
  });


  // Custom close function for permit modals in case is modal in modal
  $("body").on("click", ".close-permit-modal", function () {
    $(
      ".permit-modal-" +
        $(this).data("rel-type") +
        "-" +
        $(this).data("rel-id")
    ).modal("hide");
  });
  
}
  
// Validate the form permit
function init_form_permit(rel_type) {
  var forms = !rel_type
    ? $('[id^="form-permit-"]')
    : $("#form-permit-" + rel_type);

  $.each(forms, function (i, form) {
    $(form).appFormValidator({
      rules: {
        date: "required",
        staff: "required",
        description: "required",
      },
      submitHandler: permitFormHandler,
    });
  });
}

// New task permit custom function
function new_task_permit(id) {
  var $container = $("#newTaskpermitToggle");
  if (
    !$container.is(":visible") ||
    ($container.is(":visible") && $container.attr("data-edit") != undefined)
  ) {
    $container.slideDown(400, function () {
      fix_task_modal_left_col_height();
    });

    $("#taskpermitFormSubmit").html(app.lang.create_permit);
    $container
      .find("form")
      .attr("action", admin_url + "tasks/add_permit/" + id);

    $container.find("#description").val("");
    $container.find("#date").val("");
    $container
      .find("#staff")
      .selectpicker(
        "val",
        $container.find("#staff").attr("data-current-staff")
      );
    $container.find("#notify_by_email").prop("checked", false);
    if ($container.attr("data-edit") != undefined) {
      $container.removeAttr("data-edit");
    }
    if (!$container.isInViewport()) {
      $("#task-modal").animate(
        {
          scrollTop: $container.offset().top + "px",
        },
        "fast"
      );
    }
  } else {
    $container.slideUp();
  }
}

// Edit permit function
function edit_permit(id, e) {
  requestGetJSON("surveyors/get_permit/" + id).done(function (response) {
    var $container = $(
      ".permit-modal-" + response.rel_type + "-" + response.rel_id
    );
    var actionURL = admin_url + "surveyors/edit_permit/" + id;
    if ($container.length === 0 && $("body").hasClass("all-permits")) {
      // maybe from view all permits?
      $container = $(".permit-modal--");
      $container.find('input[name="rel_type"]').val(response.rel_type);
      $container.find('input[name="rel_id"]').val(response.rel_id);
    }
    $container.find("form").attr("action", actionURL);
    // For focusing the date field
    $container.find("form").attr("data-edit", true);
    $container.find("#description").val(response.description);
    //$container.find("#date").val(response.date);
    $container.find("#date_issued").val(response.date_issued);
    $container.find("#date_expired").val(response.date_expired);
    $container.find("#permit_number").val(response.permit_number);
    $container.find("#staff").selectpicker("val", response.staff);
    $container.find("#category_id").selectpicker("val", response.category_id);
    $container
      .find("#notify_by_email")
      .prop("checked", response.notify_by_email == 1 ? true : false);
    if ($container.hasClass("modal")) {
      $container.modal("show");
    }
  });
}

// Handles permit modal form
function permitFormHandler(form) {
  form = $(form);
  var data = form.serialize();
  $.post(form.attr("action"), data).done(function (data) {
    data = JSON.parse(data);
    if (data.message !== "") {
      alert_float(data.alert_type, data.message);
    }
    form.trigger("reinitialize.areYouSure");
    if ($("#task-modal").is(":visible")) {
      _task_append_html(data.taskHtml);
    }
    reload_permits_tables();
  });

  if ($("body").hasClass("all-permits")) {
    $(".permit-modal--").modal("hide");
  } else {
    $(
      ".permit-modal-" +
        form.find('[name="rel_type"]').val() +
        "-" +
        form.find('[name="rel_id"]').val()
    ).modal("hide");
  }

  return false;
}

// Reloads permits table eq when permit is deleted
function reload_permits_tables() {
  var available_permits_table = [
    ".table-permits",
    ".table-staff_permits",
    ".table-my-permits",
  ];

  $.each(available_permits_table, function (i, table) {
    if ($.fn.DataTable.isDataTable(table)) {
      $("body").find(table).DataTable().ajax.reload();
    }
  });
}

