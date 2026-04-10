const emailInput = document.getElementById("email");
const usernameInput = document.getElementById("username");
const phoneInput = document.getElementById("phone");
const createBtn = document.getElementById("createBtn");

const emailMsg = document.getElementById("emailMsg");
const usernameMsg = document.getElementById("usernameMsg");
const phoneMsg = document.getElementById("phoneMsg");

let emailValid = false;
let usernameValid = false;
let phoneValid = false;

function updateButtonState() {
  if (emailValid && usernameValid && phoneValid) {
    createBtn.disabled = false;
    createBtn.style.opacity = "1";
    createBtn.style.cursor = "pointer";
  } else {
    createBtn.disabled = true;
    createBtn.style.opacity = "0.6";
    createBtn.style.cursor = "not-allowed";
  }
}

updateButtonState();

/* EMAIL CHECK */
emailInput.addEventListener("keyup", function () {
  let email = this.value.trim();

  if (email.length === 0) {
    emailMsg.innerHTML = "";
    emailValid = false;
    updateButtonState();
    return;
  }

  // ✅ STRICT EMAIL FORMAT CHECK
  let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  if (!emailPattern.test(email)) {
    emailMsg.innerHTML = "<span style='color:red'>Invalid email format</span>";
    emailValid = false;
    updateButtonState();
    return; // 🚨 VERY IMPORTANT (stops AJAX)
  }

  // ✅ ONLY RUN IF VALID EMAIL
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "check_email_username_phone.php", true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (xhr.responseText.trim() === "taken") {
      emailMsg.innerHTML =
        "<span style='color:red'>Email already registered</span>";
      emailValid = false;
    } else {
      emailMsg.innerHTML = "<span style='color:green'>Valid & available</span>";
      emailValid = true;
    }
    updateButtonState();
  };

  xhr.send("email=" + encodeURIComponent(email));
});

/* USERNAME CHECK */
usernameInput.addEventListener("keyup", function () {
  let username = this.value.trim();

  if (username.length === 0) {
    usernameMsg.innerHTML = "";
    usernameValid = false;
    updateButtonState();
    return;
  }

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "check_email_username_phone.php", true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (xhr.responseText.trim() === "taken") {
      usernameMsg.innerHTML =
        "<span style='color:red'>Username already taken</span>";
      usernameValid = false;
    } else {
      usernameMsg.innerHTML =
        "<span style='color:green'>Username available</span>";
      usernameValid = true;
    }
    updateButtonState();
  };

  xhr.send("username=" + encodeURIComponent(username));
});

/* PHONE CHECK */
phoneInput.addEventListener("input", function () {
  this.value = this.value.replace(/\D/g, "");
  let phone = this.value.trim();

  if (phone.length === 0) {
    phoneMsg.innerHTML = "";
    phoneValid = false;
    updateButtonState();
    return;
  }

  if (!/^\d+$/.test(phone)) {
    phoneMsg.innerHTML =
      "<span style='color:red'>Phone must contain only numbers</span>";
    phoneValid = false;
    updateButtonState();
    return;
  }

  if (phone.length !== 10) {
    phoneMsg.innerHTML =
      "<span style='color:red'>Phone number must be exactly 10 digits</span>";
    phoneValid = false;
    updateButtonState();
    return;
  }

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "check_email_username_phone.php", true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (xhr.responseText.trim() === "taken") {
      phoneMsg.innerHTML =
        "<span style='color:red'>Phone number already registered</span>";
      phoneValid = false;
    } else {
      phoneMsg.innerHTML =
        "<span style='color:green'>Phone number available</span>";
      phoneValid = true;
    }
    updateButtonState();
  };

  xhr.send("phone=" + encodeURIComponent(phone));
});

// ===== SPINNER ON SUBMIT =====
const form = document.querySelector("form");
const spinnerBox = document.getElementById("spinnerBox");

form.addEventListener("submit", function () {
  // 🚫 prevent spinner if validation not passed
  if (!emailValid || !usernameValid || !phoneValid) {
    return;
  }

  createBtn.disabled = true;
  createBtn.style.opacity = "0.6";
  createBtn.style.cursor = "not-allowed";
  createBtn.innerText = "Please wait...";

  spinnerBox.style.display = "flex";
});
