function showPassword() {
  var x = document.getElementById("password");
  var y = document.getElementById("p1");
  if (x.type === "password") {
    x.type = "text";
    y.innerHTML = '<span onclick="showPassword()" class="field-icon material-icons">visibility_off</span>';
  } else {
    x.type = "password";
    y.innerHTML = '<span onclick="showPassword()" class="field-icon material-icons">visibility</span>';
  }
};

function showPassword1() {
  var x = document.getElementById("password_confirm");
  var y = document.getElementById("p2");
  if (x.type === "password") {
    x.type = "text";
    y.innerHTML = '<span onclick="showPassword1()" class="field-icon material-icons">visibility_off</span>';

  } else {
    x.type = "password";
    y.innerHTML = '<span onclick="showPassword1()" class="field-icon material-icons">visibility</span>';
  }
};

function showPassword2() {
  var x = document.getElementById("password_login");
  var y = document.getElementById("p3");
  if (x.type === "password") {
    x.type = "text";
    y.innerHTML = '<span onclick="showPassword2()" class="field-icon material-icons">visibility_off</span>';

  } else {
    x.type = "password";
    y.innerHTML = '<span onclick="showPassword2()" class="field-icon material-icons">visibility</span>';
  }
};





