var formData = JSON.parse(localStorage.getItem("contacts"));

var newsletterString = "";

for (var i = 0; i < formData[0].newsletter.length; i++) {
  newsletterString += formData[0].newsletter[i] + " / ";
}

document.getElementById("feedback").innerHTML =
  "<br /><br/>Hi " +
  formData[0].fname +
  " " +
  formData[0].lname +
  '<br /><br/>Thanks for your feedback regarding <br /> "' +
  formData[0].subject +
  '"<br />' +
  "<br />We'll keep you posted with newsletter of <br />" +
  newsletterString +
  "<br/> <br/> by sending to your email address at <br />" +
  formData[0].email +
  "<br /><br />Thank you and have a nice day!<br/><br/>" +
  "<img src='images/Icon3.png'/>";
