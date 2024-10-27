<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <!-- <link rel="stylesheet" href="./css/style.css"> -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<style>
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 30%;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
  }

  .close-btn {
    color: gray;
    float: right;
    font-size: 28px;
    font-weight: bold;
    border: none;
    background: none;
    cursor: pointer;
  }

  .close-btn:hover,
  .close-btn:focus {
    color: darkgray;
  }

  .proceed-btn {
    background-color: #074f34;
    color: white;
    padding: 12px 24px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
    margin-top: 20px;
    transition: background-color 0.3s ease;
  }

  .proceed-btn:hover {
    background-color: #05613e;
  }

  /* CSS for Forgot Password Page */
  body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #ddd;
    font-size: 14px;
  }

  .forgot-password-container {
    background-color: #fff;
    width: 400px;
    max-width: 100%;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  .forgot-password-card {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .forgot-password-card img.logo {
    width: 80px;
    margin-bottom: 20px;
  }

  .forgot-password-card h2 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #333;
  }

  .forgot-password-card p {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
  }

  .forgot-password-card .input-group {
    width: 100%;
    margin-bottom: 20px;
  }

  .forgot-password-card .input-group input {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
    font-size: 16px;
  }

  .forgot-password-card .reset-button {
    width: 100%;
    padding: 10px;
    background-color: #095d40;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .forgot-password-card .reset-button:hover {
    background-color: #074d34;
  }

  .forgot-password-card .back-to-login {
    display: block;
    margin-top: 20px;
    color: #095d40;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
  }

  .forgot-password-card .back-to-login:hover {
    color: #074d34;
  }

  @media screen and (max-width: 400px) {
    .modal-content {
      width: 80%;
    }

    .forgot-password-container {
      width: 90%;
      padding: 20px;
    }

    .forgot-password-card h2 {
      font-size: 20px;
    }

    .forgot-password-card p {
      font-size: 12px;
    }

    .forgot-password-card .input-group input,
    .forgot-password-card .reset-button {
      font-size: 14px;
    }
  }
</style>

<body>
  <div class="forgot-password-container">
    <div class="forgot-password-card">
      <img src="../img/ccs.png" alt="Logo" class="logo">
      <h2>Forgot Password</h2>
      <p>Enter your email address below and we'll send you a link to reset your password.</p>
      <form action="./forgot-password-request.php" method="POST">
        <div class="input-group">
          <input type="email" name="email" placeholder="Email Address" required>
        </div>
        <button type="submit" class="reset-button">Send Reset Link</button>
      </form>
      <a href="../index.php" class="back-to-login">Back to Signin</a>
    </div>
  </div>

  <!-- Password Reset Success Modal -->
  <div id="passwordResetSuccessModal" class="modal">
    <div class="modal-content">
      <button class="close-btn" onclick="closeModal('passwordResetSuccessModal')">&times;</button>
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Password Reset Link Sent!</h2>
      <p>A password reset link has been sent to your email.</p>
      <button class="proceed-btn" onclick="goToGmail()">Proceed</button>
    </div>
  </div>

  <!-- Password Reset Failure Modal -->
  <div id="passwordResetFailureModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #8B0000">Failed to Send Password Reset Link</h2>
      <p style="color: #8B0000">There was an error sending the password reset email. Please try again later.</p>
      <button class="proceed-btn" onclick="closeModal('passwordResetFailureModal')">Cancel</button>
    </div>
  </div>

  <!-- Token Storage Failure Modal -->
  <div id="tokenStorageFailureModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #8B0000">Failed to Store Token</h2>
      <p style="color: #8B0000">There was an error saving the reset token. Please try again later.</p>
      <button class="proceed-btn" onclick="closeModal('tokenStorageFailureModal')">Cancel</button>
    </div>
  </div>

  <!-- No User Found Modal -->
  <div id="noUserFoundModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #8B0000">No User Found</h2>
      <p style="color: #8B0000">The email address you entered does not match any account. Please try again.</p>
      <button class="proceed-btn" onclick="closeModal('noUserFoundModal')">Cancel</button>
    </div>
  </div>

  <!-- Script to handle modals and URL-based logic -->
  <script>
    function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }
    function goToGmail() {
      window.location.href = "https://mail.google.com";
    }

    // Show modals based on URL parameter
    document.addEventListener("DOMContentLoaded", function () {
      const urlParams = new URLSearchParams(window.location.search);
      const resetParam = urlParams.get('reset');

      if (resetParam === 'success') {
        showModal('passwordResetSuccessModal');
      } else if (resetParam === 'email_failure') {
        showModal('passwordResetFailureModal');
      } else if (resetParam === 'token_failure') {
        showModal('tokenStorageFailureModal');
      } else if (resetParam === 'no_user') {
        showModal('noUserFoundModal');
      }
    });
  </script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>