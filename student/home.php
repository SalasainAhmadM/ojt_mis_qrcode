<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch student details from the database
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM student WHERE student_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $student = $result->fetch_assoc(); // Fetch student details
  } else {
    // Handle case where student is not found
    $student = [
      'student_firstname' => 'Unknown',
      'student_middle' => 'U',
      'student_lastname' => 'User',
      'student_email' => 'unknown@wmsu.edu.ph'
    ];
  }
  $stmt->close(); // Close the statement
}

// Fetch companies from the database
$company_query = "SELECT company_id, company_name FROM company";
$companies = [];
if ($company_stmt = $database->prepare($company_query)) {
  $company_stmt->execute();
  $company_result = $company_stmt->get_result();
  while ($row = $company_result->fetch_assoc()) {
    $companies[] = $row;
  }
  $company_stmt->close();
}

// Fetch advisers from the database
$adviser_query = "SELECT adviser_id, adviser_firstname, adviser_middle, adviser_lastname FROM adviser";
$advisers = [];
if ($adviser_stmt = $database->prepare($adviser_query)) {
  $adviser_stmt->execute();
  $adviser_result = $adviser_stmt->get_result();
  while ($row = $adviser_result->fetch_assoc()) {
    $advisers[] = $row;
  }
  $adviser_stmt->close();
}

// fetch departments
$department_query = "SELECT department_id, department_name FROM departments";
$departments_result = $database->query($department_query);
$departments = [];
if ($departments_result->num_rows > 0) {
  while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
  }
}

// course sections
$course_section_query = "SELECT id, course_section_name FROM course_sections";
$course_sections_result = $database->query($course_section_query);
$course_sections = [];
if ($course_sections_result->num_rows > 0) {
  while ($row = $course_sections_result->fetch_assoc()) {
    $course_sections[] = $row;
  }
}
// address
$address_query = "SELECT address_id, address_barangay FROM address";
$address_result = $database->query($address_query);
$address = [];
if ($address_result->num_rows > 0) {
  while ($row = $address_result->fetch_assoc()) {
    $address[] = $row;
  }
}

// street
$street_query = "SELECT street_id, name FROM street";
$street_result = $database->query($street_query);
$street = [];
if ($street_result->num_rows > 0) {
  while ($row = $street_result->fetch_assoc()) {
    $street[] = $row;
  }
}

$student_email = $student['student_email'];

// Extract characters from the 3rd position (0-indexed) up to the '@' character
$at_position = strpos($student_email, '@');
$extracted_email_part = substr($student_email, 2, $at_position - 2);

// Insert a hyphen after the 4th character
$formatted_email = substr_replace($extracted_email_part, '-', 4, 0);

$currentSemester = "1st Sem";
$semesterQuery = "SELECT `type` FROM `semester` WHERE `id` = 1";
if ($result = $database->query($semesterQuery)) {
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentSemester = $row['type'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern - Student Profiling</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <link rel="stylesheet" href="../css/main.css">
  <!-- <link rel="stylesheet" href="./css/style.css"> -->
  <!-- <link rel="stylesheet" href="./css/index.css"> -->
  <link rel="stylesheet" href="../css/mobile.css">
</head>
<style>
  @media (max-width: 768px) {
    .modal-content {
      z-index: 1000;
      width: 80%;
      padding: 15px;
    }

    .content-wrapper {
      margin-top: 0;
    }

    .form-container {
      width: 95%;
      flex-direction: column;
      padding: 10px;
      margin-left: 10px;
      margin-right: 10px;
      font-size: 12px;
    }
  }
</style>

<body>
  <div class="header">
    <a style=" background-color: #095d40; color: white; padding: 5px; border-radius: 6px" class="logout-icon"
      onclick="openLogoutModal()">
      <i style=" padding-left: 5px;" class="fas fa-sign-out-alt"></i>
    </a>
    <div class="school-name">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $currentSemester; ?> &nbsp;&nbsp;&nbsp;
      <span id="sy-text"></span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>

  <style>

  </style>
  <form style="margin-top: 30px" class="form-container">
    <div style="padding: 10px; margin-left: 40px;" class="form-section">
      <label style="color: #a6a6a6">Student Profiling</label>
    </div>
  </form>
  <form class="form-container" action="insert_details.php" method="POST" enctype="multipart/form-data">
    <!-- Left Side Form -->
    <div class="form-section">
      <div class="form-group">
        <label for="wmsu-id">School ID</label>
        <!-- Add hidden input fields for student name and email -->
        <input type="hidden" id="student-id" name="student_id" value="<?php echo $student['student_id']; ?>" required>
        <input type="hidden" id="student-firstname" name="student_firstname"
          value="<?php echo $student['student_firstname']; ?>" required>
        <input type="hidden" id="student-middle" name="student_middle" value="<?php echo $student['student_middle']; ?>"
          required>
        <input type="hidden" id="student-lastname" name="student_lastname"
          value="<?php echo $student['student_lastname']; ?>" required>
        <input type="hidden" id="student-email" name="student_email" value="<?php echo $student['student_email']; ?>"
          required>
        <input type="text" id="wmsu-id" name="wmsu_id" placeholder="Enter WMSU Student ID" required>
      </div>
      <div class="form-group">
        <label for="contact">Contact Number</label>
        <input type="text" id="contact" name="contact_number" value="+63" required maxlength="13"
          oninput="limitInput(this)">
      </div>
      <script>
        function limitInput(input) {
          if (input.value.length > 13) {
            input.value = input.value.slice(0, 13);
          }
        }
      </script>
      <div class="form-group">
        <label for="course_section">Section</label>
        <select id="course_section" name="course_section" onchange="fetchAdviser()">
          <option disabled selected>Select Section</option>
          <?php foreach ($course_sections as $course_section): ?>
            <option value="<?php echo $course_section['id']; ?>" <?php if ($student['course_section'] == $course_section['id'])
                 echo 'selected'; ?>>
              <?php echo $course_section['course_section_name']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="adviser">Adviser</label>
        <input type="text" id="adviser" name="adviser" value="Adviser" readonly required>
        <input type="hidden" id="adviser_id" name="adviser_id" value="<?php echo $student['adviser']; ?>">
      </div>

      <div class="form-group">
        <label for="batch-year">School Year</label>
        <input id="batch-year" name="batch_year" readonly />
      </div>

      <script>
        const now = new Date(
          new Intl.DateTimeFormat("en-US", {
            timeZone: "Asia/Manila",
            year: "numeric",
            month: "numeric",
            day: "numeric",
          }).format(new Date())
        );

        const input = document.getElementById("batch-year");
        const syText = document.getElementById("sy-text");

        const year = now.getFullYear();
        const month = now.getMonth() + 1;

        let batchYear;
        if (month <= 5) {
          batchYear = `${year - 1}-${year}`;
        } else {
          batchYear = `${year}-${year + 1}`;
        }

        input.value = batchYear;
        syText.textContent = batchYear;
      </script>


      <script>
        // document.addEventListener("DOMContentLoaded", function () {
        //   const selectBatchYear = document.getElementById("batch-year");
        //   const currentYear = new Date().getFullYear(); // Get the current year
        //   const pastYearsToExclude = 5;
        //   const futureYearsToInclude = 1;

        //   // Generate batch year options
        //   for (let i = -pastYearsToExclude + 1; i <= futureYearsToInclude; i++) {
        //     const startYear = currentYear + i;
        //     if (startYear < currentYear - pastYearsToExclude) continue;
        //     if (startYear > currentYear + futureYearsToInclude) break;
        //     const endYear = startYear + 1;

        //     const option = document.createElement("option");
        //     option.value = `${startYear}-${endYear}`;
        //     option.textContent = `${startYear}-${endYear}`;
        //     selectBatchYear.appendChild(option);
        //   }
        // });
      </script>

      <div class="form-group">
        <label for="department">Department</label>
        <select id="department" name="department" required>
          <option value="" disabled selected>Select Department</option>
          <?php foreach ($departments as $department): ?>
            <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
              <?php echo htmlspecialchars($department['department_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="ojt_type">OJT Type</label>
        <select id="ojt_type" name="ojt_type" required>
          <option value="" disabled selected>Select OJT Type</option>
          <option value="Field-Based">Field-Based</option>
          <option value="Project-Based">Project-Based</option>
        </select>
      </div>
      <!-- <div class="form-group">
        <label for="company">OJT Company</label>
        <select id="company" name="company" required>
          <option value="" disabled selected>Select OJT Company</option>
          <?php foreach ($companies as $company): ?>
            <option value="<?php echo $company['company_id']; ?>">
              <?php echo $company['company_name']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div> -->

    </div>

    <!-- Right Side Form -->
    <div class="form-section">
      <?php
      usort($address, function ($a, $b) {
        return strcmp($a['address_barangay'], $b['address_barangay']);
      });
      ?>

      <div class="form-group">
        <label for="address">Address</label>
        <select name="address" id="address">
          <option value="" disabled selected>Select Address</option>
          <?php foreach ($address as $address_item): ?>
            <option value="<?php echo htmlspecialchars($address_item['address_id']); ?>">
              <?php echo htmlspecialchars($address_item['address_barangay']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="street">Street</label>
        <select name="street" id="street">
          <option value="" disabled selected>Select Street</option>
          <?php foreach ($street as $street_item): ?>
            <option value="<?php echo htmlspecialchars($street_item['street_id']); ?>">
              <?php echo htmlspecialchars($street_item['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="student-image">Student Image</label>
        <input type="file" id="student-image" name="student_image" accept="image/*">
      </div>
      <div class="image-preview" id="image-preview">
        <img id="preview-image" src="../img/user.png" alt="Preview Image">
      </div>

      <button type="submit" class="btn-confirm"><i style="margin-right: 4px;"
          class="fa-solid fa-circle-check"></i>Confirm</button>
    </div>
  </form>
  <!-- Login Success Modal -->
  <div id="loginSuccessModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Login Successful!</h2>
      <p>Welcome, <span style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
      <button class="proceed-btn" onclick="closeModal('loginSuccessModal')">Proceed</button>
    </div>
  </div>
  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/logout-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #000">Are you sure you want to logout?</h2>
      <div style="display: flex; justify-content: space-around; margin-top: 20px;">
        <button class="confirm-btn" onclick="logout()">Confirm</button>
        <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
      </div>
    </div>
  </div>

  <!-- JavaScript for modal behavior -->
  <script>
    function fetchAdviser() {
      var course_section_id = document.getElementById('course_section').value;

      // AJAX request to fetch the adviser details
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "get_adviser.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          var response = JSON.parse(xhr.responseText);
          if (response.adviser_firstname) {
            document.getElementById('adviser').value = response.adviser_firstname + ' ' + response.adviser_middle + '. ' + response.adviser_lastname;
            document.getElementById('adviser_id').value = response.adviser_id;
          } else {
            document.getElementById('adviser').value = "No adviser found";
            document.getElementById('adviser_id').value = "";
          }
        }
      };
      xhr.send("course_section_id=" + course_section_id);
    }

    document.getElementById('student-image').addEventListener('change', function (event) {
      const file = event.target.files[0]; // Get the selected file
      const previewImage = document.getElementById('preview-image'); // Get the image element

      if (file) {
        const reader = new FileReader(); // Create a FileReader to read the file

        reader.onload = function (e) {
          previewImage.src = e.target.result; // Set the image source to the file's result
        };

        reader.readAsDataURL(file); // Read the file as a data URL
      } else {
        previewImage.src = '../img/user.png'; // Reset to default if no file selected
      }
    });

    // Assign the formatted email part from PHP to a JavaScript variable
    const formattedEmailPart = "<?php echo $formatted_email; ?>";

    // Set the value of the WMSU ID input field
    document.getElementById('wmsu-id').value = formattedEmailPart;

    function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    // Show the modal if the session variable is set
    <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
      window.onload = function () {
        showModal('loginSuccessModal');
        // Unset the session variable to ensure the modal only appears once
        <?php unset($_SESSION['login_success']); ?>
      };
    <?php endif; ?>

  </script>
  <script src="./js/script.js"></script>
  <!-- <script src="../js/sy.js"></script> -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>