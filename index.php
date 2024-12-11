<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Management Information System using QR Code</title>
  <link rel="icon" href="./img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="./css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>

<body>
  <div class="container">
    <div class="forms-container">
      <!-- SignIn Form -->
      <div class="form-control signin-form">
        <form action="./endpoint/login.php" method="POST">
          <h2>Sign in</h2>
          <input type="email" name="email" placeholder="Email" required />
          <div class="input-wrapper">
            <input type="password" id="signin-password" name="password" placeholder="Password" required />
            <span class="toggle-password">
              <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
            </span>
          </div>
          <div class="forgot-password-link">
            <a href="./endpoint/forgotpassword.php">Forgot Password?</a>
          </div>
          <button type="submit">Sign in</button>
        </form>
      </div>

      <!-- SignUp Form -->
      <div class="form-control signup-form">
        <form action="./endpoint/register.php" method="POST">
          <h2>Create an Account</h2>
          <div class="input-wrapper flex-wrapper">
            <input type="text" name="student_firstname" placeholder="First Name" required />
            <input type="text" name="student_middle" placeholder="M.I." />
          </div>
          <input type="text" name="student_lastname" placeholder="Last Name" required />
          <input type="email" id="signup-email" name="email" placeholder="Email" required />
          <div class="input-wrapper">
            <input type="password" id="signup-password" name="password" placeholder="Password" required />
            <span class="toggle-password">
              <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
            </span>
          </div>
          <div class="input-wrapper">
            <input type="password" id="signup-confirm-password" name="confirm-password" placeholder="Confirm password"
              required />
            <span class="toggle-password">
              <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
            </span>
          </div>
          <button type="submit" id="signup-button" disabled>Sign up</button>
          <div class="password-strength" id="signup_password_strength"></div>
          <div class="password-strength" id="signup_instruction_text"></div>
        </form>
      </div>
    </div>
    <div class="intros-container">
      <div class="intro-control signin-intro">
        <div class="intro-control__inner">
          <img src="./img/ccs.png">
          <button style="font-weight: bold;" id="signup-btn">No account yet? Sign up.</button>
        </div>
      </div>
      <div class="intro-control signup-intro">
        <div class="intro-control__inner">
          <img src="./img/ccs.png">
          <button style="font-weight: bold;" id="signin-btn">Already have an account? Sign in.</button>
        </div>
      </div>
    </div>
  </div>

  <div id="loader" style="display:none;"></div>
  <!-- Login Error Modal -->
  <div id="loginErrorModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="./animation/error-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #8B0000">Login Failed</h2>
      <p style="color: #8B0000">
        <?php echo isset($_SESSION['login_error']) ? $_SESSION['login_error'] : 'Invalid email or password'; ?>
      </p>
      <button class="proceed-btn" onclick="closeModal('loginErrorModal')">Close</button>
    </div>
  </div>

  <!-- Email Not Verified Modal -->
  <div id="emailNotVerifiedModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('emailNotVerifiedModal')">&times;</span>
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="./animation/error-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #8B0000">Login Failed</h2>
      <p style="color: #8B0000">Your account has not been verified yet. Please check your email for the verification
        code.</p>
      <button class="proceed-btn" onclick="window.location.href='./endpoint/verify.php';">Go to Verification
        Page</button>
    </div>
  </div>

  <script src="./js/script.js"></script>
  <script>



    // Show the appropriate modal based on URL parameters or session variables
    <?php if (isset($_GET['login']) && $_GET['login'] === 'error'): ?>
      window.onload = function () {
        showModal('loginErrorModal');
        window.history.replaceState({}, document.title, window.location.pathname);
      };
    <?php elseif (isset($_GET['login']) && $_GET['login'] === 'not_verified'): ?>
      window.onload = function () {
        showModal('emailNotVerifiedModal');
        window.history.replaceState({}, document.title, window.location.pathname);
      };
    <?php endif; ?>

    function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }
    // Signup form event listener
    document.querySelector('form[action="./endpoint/register.php"]').addEventListener('submit', function (e) {
      // Show the loader
      document.getElementById('loader').style.display = "block";

      // Disable the signup button to prevent multiple submissions
      document.getElementById('signup-button').disabled = true;

      // Optional: Hide the form while the loader is shown (if desired)
      document.querySelector('.signup-form').style.opacity = '0.5';
    });

    const signupEmailField = document.getElementById('signup-email');
    const signupPasswordField = document.getElementById('signup-password');
    const signupConfirmPasswordField = document.getElementById('signup-confirm-password');
    const signupStrengthIndicator = document.getElementById('signup_password_strength');
    const signupInstructionText = document.getElementById('signup_instruction_text');
    const signupButton = document.getElementById('signup-button');

    // Email validation event listener
    signupEmailField.addEventListener('input', function () {
      const email = signupEmailField.value;
      const emailRegex = /^[^\s@]+@wmsu\.edu\.ph$/;
      if (emailRegex.test(email)) {
        signupEmailField.setCustomValidity('');
        signupInstructionText.textContent = '';
        signupButton.disabled = false;
      } else {
        signupEmailField.setCustomValidity('Email must be a valid wmsu email address');
        signupInstructionText.style.color = '#8B0000';
        signupInstructionText.textContent = 'Please enter a valid wmsu email address.';
        signupButton.disabled = true;
      }
    });

    // Password validation event listener
    signupPasswordField.addEventListener('input', function () {
      const password = signupPasswordField.value;
      const strength = checkPasswordStrength(password);
      signupStrengthIndicator.textContent = `Password Strength: ${strength}`;

      if (strength === 'Weak' || strength === 'Moderate') {
        signupStrengthIndicator.style.color = '#8B0000';
      } else {
        signupStrengthIndicator.style.color = '#095d40';
      }

      if (password.length >= 8 && strength !== 'Strong') {
        signupInstructionText.style.color = '#8B0000';
        signupInstructionText.textContent = 'Password must include at least 2 numbers, 5 lowercase letters, and 1 uppercase letter.';
        signupButton.disabled = true;
      } else if (strength === 'Strong' && signupConfirmPasswordField.value === password && signupEmailField.validity.valid) {
        signupInstructionText.textContent = '';
        signupButton.disabled = false;
      } else {
        signupInstructionText.textContent = '';
        signupButton.disabled = true;
      }
    });

    // Confirm password match check
    signupConfirmPasswordField.addEventListener('input', function () {
      if (signupConfirmPasswordField.value !== signupPasswordField.value) {
        signupConfirmPasswordField.setCustomValidity('Passwords do not match');
        signupButton.disabled = true;
      } else {
        signupConfirmPasswordField.setCustomValidity('');
        if (checkPasswordStrength(signupPasswordField.value) === 'Strong' && signupEmailField.validity.valid) {
          signupButton.disabled = false;
        }
      }
    });

    function checkPasswordStrength(password) {
      const regexStrong = /(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/;

      if (password.length >= 8 && regexStrong.test(password)) {
        return 'Strong';
      } else if (password.length >= 6) {
        return 'Moderate';
      } else {
        return 'Weak';
      }
    }

  </script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>