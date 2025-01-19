<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch admin details from the database
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE admin_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $admin_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc(); // Fetch admin details
  } else {
    // Handle case where admin is not found
    $admin = [
      'admin_firstname' => 'Unknown',
      'admin_middle' => 'U',
      'admin_lastname' => 'User',
      'admin_email' => 'unknown@wmsu.edu.ph'
    ];
  }
  $stmt->close(); // Close the statement
}
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
  <title>Admin - Feedback</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="./css/style.css">
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="./css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
  <div class="header">
    <i class="fas fa-school"></i>
    <div class="school-name">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $currentSemester; ?> &nbsp;&nbsp;&nbsp;
      <span id="sy-text"></span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>
  <div class="sidebar close">
    <div class="profile-details">
      <img src="../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
        alt="logout Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span
          class="profile_name"><?php echo $admin['admin_firstname'] . ' ' . $admin['admin_middle'] . '. ' . $admin['admin_lastname']; ?></span>
        <br />
        <span class="profile_email"><?php echo $admin['admin_email']; ?></span>
      </div>
    </div>
    <hr>
    <ul class="nav-links">
      <li>
        <a href="index.php">
          <i class="fas fa-th-large"></i>
          <span class="link_name">Dashboard</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="index.php">Dashboard</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="user-manage.php">
            <i class="fa-solid fa-user"></i>
            <span class="link_name">Manage Users</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="user-manage.php">User Management</a></li>
          <li><a href="./users/adviser.php">Adviser Management</a></li>
          <li><a href="./users/company.php">Company Management</a></li>
          <li><a href="./users/student.php">Student Management</a></li>
        </ul>
      </li>
      <li>
        <a href="others.php">
          <i class="fa-solid fa-ellipsis-h"></i>
          <span class="link_name">Others</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="others.php">Others</a></li>
        </ul>
      </li>
      <li>
        <a href="calendar.php">
          <i class="fa-regular fa-calendar-days"></i>
          <span class="link_name">Calendar</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="calendar.php">Calendar</a></li>
        </ul>
      </li>
      <li>
        <a href="feedback.php" class="active">
          <i class="fa-solid fa-percent"></i>
          <span class="link_name">Feedback</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="feedback.php">Feedback Management</a></li>
        </ul>
      </li>
      <li>
        <a href="setting.php">
          <i class="fas fa-cog"></i>
          <span class="link_name">Manage Profile</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="setting.php">Manage Profile</a></li>
        </ul>
      </li>
      <li>
        <a onclick="openLogoutModal()">
          <div class="logout-details">
            <div class="logout-content"></div>
            <div class="name-">
              <div class="logout_name">
                <i class="fas fa-sign-out-alt left-icon"></i>Logout
              </div>
            </div>
            <i class="fas fa-sign-out-alt right-icon"></i>
          </div>
        </a>
      </li>
    </ul>
  </div>
  <section class="home-section">
    <div class="home-content">
      <i style="z-index: 100;" class="fas fa-bars bx-menu"></i>
    </div>

    <div class="content-wrapper">

      <div class="header-box">
        <label style="color: #a6a6a6;">Feedback Management</label>
      </div>
      <div class="main-box">
        <div class="whole-box">
          <div class="header-group">
            <h2>Feedback Questions</h2>
            <div class="button-container">
              <button id="openAddModalBtn" class="add-btn">
                <i class="fa-solid fa-plus"></i>Add Question
              </button>
            </div>
          </div>

          <table>
            <thead>
              <th>Feedback Questions</th>
              <th class="action">Action</th>
            </thead>
            <tbody>
              <?php
              // Fetch feedback questions
              $sql = "SELECT * FROM feedback_questions";
              $result = $database->query($sql);

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  for ($i = 1; $i <= 10; $i++) { // Loop for questions 1 to 10
                    $question = $row["question$i"];
                    if (!empty($question)) {
                      // Define the action buttons based on the question number
                      $actionButtons = "
                            <button class=\"action-icon edit-btn\" onclick=\"openEditModal($row[id], 'question$i', '$question')\">
                                <i class=\"fa-solid fa-pen-to-square\"></i>
                            </button>
                        ";

                      // For question 6 to 10, add delete button
                      if ($i >= 6) {
                        $actionButtons .= "
                                <button class=\"action-icon delete-btn\" onclick=\"deleteQuestion($row[id], 'question$i')\">
                                    <i class=\"fa-solid fa-trash\"></i>
                                </button>
                            ";
                      }

                      echo "
                            <tr>
                                <td>$question</td>
                                <td class=\"action\">
                                    $actionButtons
                                </td>
                            </tr>
                        ";
                    }
                  }
                }
              } else {
                echo "<tr><td colspan='2'>No feedback questions found.</td></tr>";
              }
              ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </section>

  <!-- Add Feedback Modal -->
  <div id="addFeedbackModal" class="modal">
    <div class="modal-content-others">
      <span class="close" id="closeAddFeedbackModal">&times;</span>
      <h2>Add Feedback Question</h2>
      <form id="addFeedbackForm" action="./add_feedback.php" method="POST">
        <div class="input-group">
          <label for="addFeedbackText">Feedback Question</label>
          <textarea id="addFeedbackText" name="feedback_text" rows="4" placeholder="Enter feedback question"
            required></textarea>
        </div>
        <button type="submit" class="modal-btn">Add Feedback</button>
      </form>
    </div>
  </div>


  <!-- Edit Feedback Modal -->
  <div id="editFeedbackModal" class="modal">
    <div class="modal-content-others">
      <span class="close" id="closeEditFeedbackModal">&times;</span>
      <h2>Edit Feedback Question</h2>
      <form id="editFeedbackForm" action="./edit_feedback.php" method="POST">
        <input type="hidden" id="editFeedbackId" name="feedback_id">
        <input type="hidden" id="editFeedbackField" name="feedback_field">
        <div class="input-group">
          <label for="editFeedbackText">Feedback Question</label>
          <textarea id="editFeedbackText" name="feedback_text" rows="4" required></textarea>
        </div>
        <button type="submit" class="modal-btn">Update Feedback</button>
      </form>
    </div>
  </div>
  <!-- Success Modal -->
  <div id="updateFeedbackSuccessModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Updated Successfully!</h2>
      <p>The feedback question was updated successfully!</p>
      <button class="proceed-btn" onclick="closeModal('updateFeedbackSuccessModal')">Close</button>
    </div>
  </div>
  <!-- Add Success Modal -->
  <div id="addFeedbackSuccessModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Added Successfully!</h2>
      <p>The feedback question was added successfully!</p>
      <button class="proceed-btn" onclick="closeModal('addFeedbackSuccessModal')">Close</button>
    </div>
  </div>
  <!-- Delete Success Modal -->
  <div id="deleteFeedbackSuccessModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Deleted Successfully!</h2>
      <p>The feedback question was deleted successfully!</p>
      <button class="proceed-btn" onclick="closeModal('deleteFeedbackSuccessModal')">Close</button>
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
  <script>
    // Get modal elements
    const openAddFeedbackModalBtn = document.getElementById('openAddModalBtn');
    const addFeedbackModal = document.getElementById('addFeedbackModal');
    const closeAddFeedbackModal = document.getElementById('closeAddFeedbackModal');

    // Open modal
    openAddFeedbackModalBtn.addEventListener('click', () => {
      addFeedbackModal.style.display = 'block';
    });

    // Close modal
    closeAddFeedbackModal.addEventListener('click', () => {
      addFeedbackModal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
      if (event.target === addFeedbackModal) {
        addFeedbackModal.style.display = 'none';
      }
    });


    document.addEventListener("DOMContentLoaded", function () {
      const modal = document.getElementById("editFeedbackModal");
      const closeModal = document.getElementById("closeEditFeedbackModal");

      // Open modal
      window.openEditModal = function (id, field, text) {
        document.getElementById("editFeedbackId").value = id;
        document.getElementById("editFeedbackField").value = field;
        document.getElementById("editFeedbackText").value = text;

        modal.style.display = "block";
      };

      // Close modal
      closeModal.addEventListener("click", function () {
        modal.style.display = "none";
      });

      // Close modal when clicking outside the modal content
      window.addEventListener("click", function (event) {
        if (event.target === modal) {
          modal.style.display = "none";
        }
      });
    });

    // Function to open the modal
    function openModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    // Function to close the modal
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    // Check session variables for success messages and open respective modals
    window.onload = function () {
      <?php if (isset($_SESSION['update_success']) && $_SESSION['update_success'] === true): ?>
        showModal('updateFeedbackSuccessModal');
        <?php unset($_SESSION['update_success']); // Remove after displaying ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['add_success']) && $_SESSION['add_success'] === true): ?>
        showModal('addFeedbackSuccessModal');
        <?php unset($_SESSION['add_success']); // Remove after displaying ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['delete_success']) && $_SESSION['delete_success'] === true): ?>
        showModal('deleteFeedbackSuccessModal'); // Show a delete success modal
        <?php unset($_SESSION['delete_success']); ?>
      <?php endif; ?>

    };


    function deleteQuestion(feedbackId, questionField) {
      if (confirm("Are you sure you want to delete this question?")) {
        window.location.href = `./delete_feedback.php?feedback_id=${feedbackId}&question_field=${questionField}`;
      }
    }

  </script>

  <script src="./js/script.js"></script>
  <script src="../js/sy.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>