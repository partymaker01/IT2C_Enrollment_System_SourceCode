const container = document.querySelector(".container")
const registerBtn = document.querySelector(".register-btn")
const loginBtn = document.querySelectorAll(".login-btn")
const forgotLink = document.getElementById("forgotLink")

// Handle register button click
registerBtn.addEventListener("click", () => {
  container.classList.remove("forgot-mode")
  container.classList.add("active")
})

// Handle login button clicks
loginBtn.forEach((btn) => {
  btn.addEventListener("click", () => {
    container.classList.remove("active")
    container.classList.remove("forgot-mode")
  })
})

// Handle forgot password link click
forgotLink.addEventListener("click", (e) => {
  e.preventDefault()
  container.classList.remove("active")
  container.classList.add("forgot-mode")
})

// Initialize Select2 when document is ready
$(document).ready(() => {
  $(".select2").select2({
    placeholder: "Select an option",
    allowClear: true,
  })
})

// Form validation
document.addEventListener("DOMContentLoaded", () => {
  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const passwords = this.querySelectorAll('input[type="password"]')

      // Password confirmation validation
      if (passwords.length === 2) {
        if (passwords[0].value !== passwords[1].value) {
          e.preventDefault()
          showAlert("Passwords do not match!", "error")
          return false
        }
        if (passwords[0].value.length < 6) {
          e.preventDefault()
          showAlert("Password must be at least 6 characters long!", "error")
          return false
        }
      }

      // Email validation
      const emailInputs = this.querySelectorAll('input[type="email"]')
      emailInputs.forEach((email) => {
        if (email.value && !isValidEmail(email.value)) {
          e.preventDefault()
          showAlert("Please enter a valid email address!", "error")
          return false
        }
      })
    })
  })
})

// Email validation function
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Show alert function
function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type === "error" ? "danger" : "success"} alert-dismissible fade show`
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `

  const container = document.querySelector(".container")
  container.insertBefore(alertDiv, container.firstChild)

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove()
    }
  }, 5000)
}

// Password visibility toggle
document.addEventListener("DOMContentLoaded", () => {
  const passwordInputs = document.querySelectorAll('input[type="password"]')

  passwordInputs.forEach((input) => {
    const toggleBtn = document.createElement("button")
    toggleBtn.type = "button"
    toggleBtn.className = "password-toggle"
    toggleBtn.innerHTML = '<i class="bx bx-hide"></i>'

    toggleBtn.addEventListener("click", function () {
      const type = input.getAttribute("type") === "password" ? "text" : "password"
      input.setAttribute("type", type)

      const icon = this.querySelector("i")
      icon.className = type === "password" ? "bx bx-hide" : "bx bx-show"
    })

    // Insert toggle button after input
    input.parentNode.style.position = "relative"
    input.parentNode.appendChild(toggleBtn)
  })
})
