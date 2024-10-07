<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<style>
    /* CSS for Reset Password Page */
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

    .reset-password-container {
        background-color: #fff;
        width: 400px;
        max-width: 100%;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .reset-password-card {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .reset-password-card img.logo {
        width: 80px;
        margin-bottom: 20px;
    }

    .reset-password-card h2 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
    }

    .reset-password-card p {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
    }

    .reset-password-card .input-group {
        width: 100%;
        margin-bottom: 20px;
        position: relative;
    }

    .reset-password-card .input-group input {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ddd;
        font-size: 16px;
    }

    .reset-password-card .input-group .toggle-password {
        position: absolute;
        top: 65%;
        left: 205px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
    }

    .reset-password-card .reset-button {
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

    .reset-password-card .reset-button:hover {
        background-color: #074d34;
    }

    .reset-password-card .reset-button:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .reset-password-card .back-to-login {
        display: block;
        margin-top: 20px;
        color: #095d40;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s;
    }

    .reset-password-card .back-to-login:hover {
        color: #074d34;
    }

    .password-strength {
        margin-top: 10px;
        font-size: 12px;
        width: 200px;
        color: #666;
    }

    @media screen and (max-width: 400px) {
        .reset-password-container {
            width: 90%;
            padding: 20px;
        }

        .reset-password-card h2 {
            font-size: 20px;
        }

        .reset-password-card p {
            font-size: 12px;
        }

        .reset-password-card .input-group input,
        .reset-password-card .reset-button {
            font-size: 14px;
        }
    }
</style>

<body>
    <div class="reset-password-container">
        <div class="reset-password-card">
            <img src="../img/ccs.png" alt="Logo" class="logo">
            <h2>Reset Password</h2>
            <p>Enter your new password below to reset it.</p>
            <form action="./update-password.php" method="POST">
                <div class="input-group">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                    <span class="toggle-password">
                        <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
                    </span>
                </div>
                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password"
                        required>
                    <span class="toggle-password">
                        <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
                    </span>
                </div>
                <button type="submit" class="reset-button" id="reset_button" disabled>Reset Password</button>
                <div class="password-strength" id="password_strength"></div>
                <div class="password-strength" id="instruction_text"></div>
            </form>
            <a href="../index.php" class="back-to-login">Back to Signin</a>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.querySelectorAll('.toggle-password');
        togglePassword.forEach(icon => {
            icon.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });

        // Password strength checker
        const passwordField = document.getElementById('new_password');
        const strengthIndicator = document.getElementById('password_strength');
        const instructionText = document.getElementById('instruction_text');
        const resetButton = document.getElementById('reset_button');

        passwordField.addEventListener('input', function () {
            const password = passwordField.value;
            const strength = checkPasswordStrength(password);
            strengthIndicator.textContent = `Password Strength: ${strength}`;

            if (strength === 'Weak' || strength === 'Moderate') {
                strengthIndicator.style.color = '#8B0000';
            } else {
                strengthIndicator.style.color = '#074f34';
            }

            if (password.length >= 8 && strength !== 'Strong') {
                instructionText.style.color = '#8B0000';
                instructionText.textContent = 'Password must include at least 2 numbers, 5 lowercase letters, and 1 uppercase letter.';
                resetButton.disabled = true;
            } else if (strength === 'Strong') {
                instructionText.textContent = '';
                resetButton.disabled = false;
            } else {
                instructionText.textContent = '';
                resetButton.disabled = true;
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
</body>

</html>