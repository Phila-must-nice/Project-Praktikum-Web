<!DOCTYPE html>
<html lang="">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
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
        linear-gradient(rgba(15, 40, 90, 0.65),
        rgba(15, 40, 90, 0.65)),
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

    /* Right Side - Login */
    .auth-login {
      width: 50%;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 50px;
      background: #ffffff;
    }

    .auth-form {
      width: 100%;
      max-width: 420px;
      padding: 20px 0;
    }

    /* Logo */
    .auth-logo {
      margin-bottom: 45px;
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
      margin-bottom: 35px;
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
      margin-bottom: 23px;
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

    /* Password */
    .password-group input {
      padding-right: 65px;
    }

    #passwordToggle {
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

    /* Register */
    .auth-register {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 25px;
    }

    .auth-register a {
      font-size: 13px;
      color: #2563eb;
      text-decoration: none;
    }

    .auth-register a:hover {
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
      margin-top: 30px;
      font-size: 12px;
      color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .auth-company {
        display: none;
      }

      .auth-login {
        width: 100%;
        padding: 30px 25px;
      }

      .auth-form {
        max-width: 400px;
      }

      .auth-logo {
        margin-bottom: 35px;
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
    <section class="auth-company">
      <h1>TalentTrack</h1>
      <p>
        Temukan peluang karier terbaik dan bangun masa depan
        profesionalmu bersama perusahaan kami.
      </p>
    </section>
    <main class="auth-login">

      <div class="auth-form">

        <div class="auth-logo">
          <img src="assets/images/company.jpg" alt="Company Logo">
        </div>

        <h2>Welcome Back!</h2>

        <p class="auth-description">
          Silakan masuk menggunakan akun Anda untuk melanjutkan.
        </p>

        <form action="" method="post">

          <label for="email">Email Address</label>
          <div class="input-group">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="you@email.com"
              required
            >
          </div>

          <label for="password">Password</label>
          <div class="input-group password-group">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            >

            <button
              id="passwordToggle"
              type="button"
              aria-label="Show password"
            >
              Show
            </button>
          </div>

          <div class="auth-register">
            <a href="register.php">Create an account</a>
          </div>

          <button class="auth-submit" type="submit">
            Sign In
          </button>

        </form>

        <div class="auth-footer">
          &copy; 2026 TalentTrack. All rights reserved.
        </div>

      </div>

    </main>
  </div>
  <script>
    const password = document.getElementById("password");
    const passwordToggle = document.getElementById("passwordToggle");

    passwordToggle.addEventListener("click", function () {
      if (password.type === "password") {
        password.type = "text";
        passwordToggle.textContent = "Hide";
        passwordToggle.setAttribute("aria-label", "Hide password");
      } else {
        password.type = "password";
        passwordToggle.textContent = "Show";
        passwordToggle.setAttribute("aria-label", "Show password");
      }
    });
  </script>
</body>

</html>