<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Management Information System using QR Code</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="../css/verify.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
  <div class="verification-container">
    <div class="verification-card">
      <img src="../img/ccs.png" alt="Logo" class="logo">
      <h2>OTP Verification</h2>
      <p>Enter the verification code sent to your email.</p>
      <form action="./verify-otp.php" method="POST">
        <div class="input-group">
          <input type="text" name="otp" placeholder="Enter OTP" required />
        </div>
        <button type="submit" class="verify-button">Verify</button>
      </form>
      <a href="./resend-otp.php" class="resend-link">Didn't receive the code? Resend</a>
    </div>
  </div>

  <!-- Modal for Registration Success -->
  <div id="registrationSuccessModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeRegistrationModal()">&times;</span>
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/email-sent.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Registration Successful!</h2>
      <p>Your registration was successful. Please check your email for the OTP.</p>
      <button class="proceed-btn" onclick="window.open('https://mail.google.com', '_blank');">Proceed</button>
    </div>
  </div>

  <!-- Modal for Email Verification Success -->
  <div id="emailVerifiedModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Email Verified Successfully!</h2>
      <p>Your email has been successfully verified.</p>
      <button class="proceed-btn" onclick="closeEmailModal()">Proceed To Login</button>
    </div>
  </div>

  <script>
    // Check if the query parameter indicates a registration success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('registration') && urlParams.get('registration') === 'success') {
      document.getElementById('registrationSuccessModal').style.display = 'block';
    }

    if (urlParams.has('verification') && urlParams.get('verification') === 'success') {
      document.getElementById('emailVerifiedModal').style.display = 'block';
    }

    // Close modals
    function closeRegistrationModal() {
      document.getElementById('registrationSuccessModal').style.display = 'none';
    }

    function closeEmailModal() {
      document.getElementById('emailVerifiedModal').style.display = 'none';
      window.location.href = '../index.php';
    }
  </script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>