var contacts = [];

$("#submitButton").click(function () {
  var $contactForm = $("#contactForm");

  $.validator.addMethod(
    "noSpace",
    function (value, element) {
      return value == "" || value.trim().length != 0;
    },
    "Spaces are not allowed"
  );

  if ($contactForm.length) {
    $contactForm.validate({
      rules: {
        firstname: {
          required: true,
          noSpace: true,
        },
        lastname: {
          required: true,
          noSpace: true,
        },
        country: {
          required: true,
        },
        email: {
          required: true,
          email: true,
        },
        subject: {
          required: true,
          noSpace: true,
        },
        profession: {
          required: true,
        },
        "newsletter[]": {
          required: true,
        },
      },

      messages: {
        firstname: {
          required: "Please enter your first name!<br />",
          noSpace: "No empty space<br />",
        },
        lastname: {
          required: "Please enter your last name!<br />",
          noSpace: "No empty space<br />",
        },
        country: {
          required: "Please select a country!<br />",
        },
        email: {
          required: "Please enter your email!<br />",
          email: "Please enter a valid email<br />",
        },
        subject: {
          required: "Please enter your message to us!<br />",
          noSpace: "No empty space<br />",
        },
        profession: {
          required: "Please choose your profession!<br />",
        },
        "newsletter[]": {
          required: "Please choose the newsletter you want!<br />",
        },
      },

      errorPlacement: function (error, element) {
        if (element.is(":radio")) {
          error.appendTo(element.parent("#profession"));
        } else if (element.is(":checkbox")) {
          error.appendTo(element.parent("#newsletter"));
        } else {
          error.insertAfter(element);
        }
      },

      submitHandler: function (form) {
        var newsArr = [];

        $('input[type="checkbox"]:checked').each(function () {
          newsArr.push(this.value);
        });

        contacts.push({
          contact_id: uuidv4(),
          fname: $("#fname").val(),
          lname: $("#lname").val(),
          country: $("#country").val(),
          email: $("#email").val(),
          subject: $("#subject").val(),
          profession: $("input[name='profession']:checked").val(),
          newsletter: newsArr,
        });

        localStorage.setItem("contacts", JSON.stringify(contacts));

        form.submit();
        window.top.location.href = "thanks.html";
        //window.location.assign(document.location.origin + "/Library_Project/thanks.html");
        form.reset();
      },
    });
  }
});
