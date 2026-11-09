
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | TalentTrack</title>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      min-height: 100vh;
      background: #ffffff;
    }

    /* Main Layout */
    .auth-container {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }

    /* Left Side - Company */
    .auth-company {
      width: 50%;
      min-height: 100vh;
      background:
        linear-gradient(
          rgba(15, 40, 90, 0.65),
          rgba(15, 40, 90, 0.65)
        ),
        url("assets/images/company.jpg");

      background-size: cover;
      background-position: center;

      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 60px;
      color: white;
    }

    .auth-company h1 {
      font-size: 36px;
      margin-bottom: 15px;
    }

    .auth-company p {
      font-size: 16px;
      line-height: 1.7;
      max-width: 450px;
      color: #e2e8f0;
    }

    /* Right Side - Register */
    .auth-register {
      width: 50%;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 50px;
      background: #ffffff;
    }

    .auth-form {
      width: 100%;
      max-width: 420px;
      padding: 30px 0;
    }

    /* Logo */
    .auth-logo {
      margin-bottom: 35px;
    }

    .auth-logo img {
      max-width: 150px;
      max-height: 65px;
      object-fit: contain;
    }

    /* Heading */
    .auth-form h2 {
      font-size: 30px;
      color: #1e293b;
      margin-bottom: 10px;
    }

    .auth-description {
      font-size: 14px;
      color: #64748b;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    /* Form */
    .auth-form form {
      display: flex;
      flex-direction: column;
    }

    .auth-form label {
      font-size: 14px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 9px;
    }

    .input-group {
      position: relative;
      margin-bottom: 20px;
    }

    .auth-form input {
      width: 100%;
      padding: 14px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      outline: none;
      transition: 0.2s;
    }

    .auth-form input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .auth-form input::placeholder {
      color: #94a3b8;
    }

    .auth-form input[readonly] {
      background: #f8fafc;
      color: #64748b;
      cursor: default;
    }

    /* Password */
    .password-group input {
      padding-right: 65px;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: transparent;
      color: #2563eb;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
    }

    /* Login Link */
    .auth-login {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 25px;
    }

    .auth-login a {
      font-size: 13px;
      color: #2563eb;
      text-decoration: none;
    }

    .auth-login a:hover {
      text-decoration: underline;
    }

    /* Submit Button */
    .auth-submit {
      width: 100%;
      padding: 14px;
      background: #2563eb;
      color: white;
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.2s;
    }

    .auth-submit:hover {
      background: #1d4ed8;
    }

    .auth-submit:active {
      transform: scale(0.98);
    }

    /* Footer */
    .auth-footer {
      text-align: center;
      margin-top: 25px;
      font-size: 12px;
      color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .auth-company {
        display: none;
      }

      .auth-register {
        width: 100%;
        padding: 30px 25px;
      }

      .auth-form {
        max-width: 400px;
      }

      .auth-logo {
        margin-bottom: 30px;
      }
    }

    @media (max-width: 480px) {
      .auth-form h2 {
        font-size: 26px;
      }
    }
  </style>
</head>

<body>

  <div class="auth-container">

    <!-- Company Section -->
    <section class="auth-company">
      <h1>TalentTrack</h1>

      <p>
        Temukan peluang karier terbaik dan bangun masa depan
        profesionalmu bersama perusahaan kami.
      </p>
    </section>

    <!-- Register Section -->
    <main class="auth-register">

      <div class="auth-form">

        <div class="auth-logo">
          <img src="assets/images/company.jpg" alt="Company Logo">
        </div>

        <h2>Create Account</h2>

        <p class="auth-description">
          Create your account and start your career journey
          with TalentTrack.
        </p>

        <form action="" method="post">

          <!-- Full Name -->
          <label for="fullname">Full Name</label>

          <div class="input-group">
            <input
              type="text"
              id="fullname"
              name="name"
              placeholder="Enter your full name"
              autocomplete="name"
              required
            >
          </div>

          <!-- Email -->
          <label for="email">Email Address</label>

          <div class="input-group">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="you@email.com"
              autocomplete="email"
              required
            >
          </div>

          <!-- Phone -->
          <label for="phone">Phone Number</label>

          <div class="input-group">
            <input
              type="tel"
              id="phone"
              name="phone"
              placeholder="e.g. 081234567890"
              autocomplete="tel"
              required
            >
          </div>

          <!-- Password -->
          <label for="password">Password</label>

          <div class="input-group password-group">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              autocomplete="new-password"
              required
            >

            <button
              class="password-toggle"
              id="passwordToggle"
              type="button"
              aria-label="Show password"
            >
              Show
            </button>
          </div>

          <!-- Confirm Password -->
          <label for="confirmPassword">Confirm Password</label>

          <div class="input-group password-group">
            <input
              type="password"
              id="confirmPassword"
              name="password_confirmation"
              placeholder="Confirm your password"
              autocomplete="new-password"
              required
            >

            <button
              class="password-toggle"
              id="confirmPasswordToggle"
              type="button"
              aria-label="Show confirm password"
            >
              Show
            </button>
          </div>

          <!-- Account Type -->
          <label for="accountType">Account Type</label>

          <div class="input-group">
            <input
              type="text"
              id="accountType"
              value="Job Applicant"
              readonly
            >
          </div>

          <!-- Login Link -->
          <div class="auth-login">
            <a href="login.php">
              Already have an account? Login
            </a>
          </div>

          <!-- Submit -->
          <button class="auth-submit" type="submit">
            Create Account
          </button>

        </form>

        <!-- Footer -->
        <div class="auth-footer">
          &copy; 2026 TalentTrack. All rights reserved.
        </div>

      </div>

    </main>

  </div>

  <!-- JavaScript -->
  <script>
    function setupPasswordToggle(inputId, buttonId) {
      const password = document.getElementById(inputId);
      const toggle = document.getElementById(buttonId);

      toggle.addEventListener("click", function () {

        if (password.type === "password") {
          password.type = "text";
          toggle.textContent = "Hide";
          toggle.setAttribute("aria-label", "Hide password");
        } else {
          password.type = "password";
          toggle.textContent = "Show";
          toggle.setAttribute("aria-label", "Show password");
        }

      });
    }

    setupPasswordToggle("password", "passwordToggle");

    setupPasswordToggle(
      "confirmPassword",
      "confirmPasswordToggle"
    );
  </script>

</body>
</html>
