<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Management Information System using QR Code</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <style>
    /* CSS for Verification Page */
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

    .verification-container {
      background-color: #fff;
      width: 400px;
      max-width: 100%;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .verification-card {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .verification-card img.logo {
      width: 80px;
      margin-bottom: 20px;
    }

    .verification-card h2 {
      font-size: 24px;
      margin-bottom: 10px;
      color: #333;
    }

    .verification-card p {
      font-size: 14px;
      color: #666;
      margin-bottom: 20px;
    }

    .verification-card .input-group {
      width: 100%;
      margin-bottom: 20px;
    }

    .verification-card .input-group input {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ddd;
      font-size: 16px;
      text-align: center;
    }

    .verification-card .verify-button {
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

    .verification-card .verify-button:hover {
      background-color: #074d34;
    }

    .verification-card .resend-link {
      display: block;
      margin-top: 20px;
      color: #095d40;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s;
    }

    .verification-card .resend-link:hover {
      color: #074d34;
    }

    @media screen and (max-width: 400px) {
      .verification-container {
        width: 90%;
        padding: 20px;
      }

      .verification-card h2 {
        font-size: 20px;
      }

      .verification-card p {
        font-size: 12px;
      }

      .verification-card .input-group input,
      .verification-card .verify-button {
        font-size: 14px;
      }
    }

    /* CSS for Modal */
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

    /* Modal */
    @media (max-width: 768px) {
      .modal {
        z-index: 100;
      }

      .modal-content {
        width: 50%;
        margin: 15% auto;
      }
    }

    @media (max-width: 480px) {
      .modal-content {
        width: 60%;
        margin: 20% auto;
      }

      .close-btn {
        font-size: 24px;
      }

      .proceed-btn {
        padding: 10px 20px;
        font-size: 14px;
      }
    }
  </style>
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