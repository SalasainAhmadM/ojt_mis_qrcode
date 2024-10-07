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
</body>

</html>