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
// Fetch student's journal entries
$query = "SELECT * FROM student_journal WHERE student_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id); // Bind student ID parameter
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  $journals = [];
  if ($result->num_rows > 0) {
    // Fetch all journal entries
    while ($row = $result->fetch_assoc()) {
      $journals[] = $row;
    }
  }
  $stmt->close(); // Close the statement
}

include './others/filter_journal.php';

$pagination_data = getStudentJournals($database, $student_id, $search_query);
$journals = $pagination_data['journals'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern - Journal</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="./css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <style>

  </style>
</head>

<body>
  <div class="header">
    <i class=""></i>
    <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>
  <div class="sidebar close">
    <div class="profile-details">
      <img
        src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
        alt="logout Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span
          class="profile_name"><?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '. ' . $student['student_lastname']; ?></span>
        <br />
        <span class="profile_email"><?php echo $student['student_email']; ?></span>
      </div>
    </div>
    <hr>
    <ul class="nav-links">
      <li>
        <a href="index.php">
          <i class="fa-solid fa-house"></i>
          <span class="link_name">Home</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="index.php">Home</a></li>
        </ul>
      </li>
      <li>
        <a href="journal.php" class="active">
          <i class="fa-solid fa-pen"></i>
          <span class="link_name">Journal</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="journal.php">Journal</a></li>
        </ul>
      </li>
      <li>
        <a href="qr-code.php">
          <i class="fa-solid fa-qrcode"></i>
          <span class="link_name">QR Scanner</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="qr-code.php">QR Scanner</a></li>
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
  <style>
    /* pagination adviser */
    .pagination {
      margin-top: 20px;
      text-align: center;
    }

    .pagination a {
      display: inline-block;
      margin: 0 5px;
      padding: 6px 14px;
      background-color: #f4f4f4;
      border: 1px solid #ccc;
      color: #333;
      text-decoration: none;
      border-radius: 4px;
    }

    .pagination a.active {
      background-color: #07432e;
      color: white;
    }

    .pagination a:hover {
      background-color: #ddd;
    }

    .pagination a.prev,
    .pagination a.next {
      font-weight: bold;
    }

    /* filter button */
    .search-bar-container {
      position: relative;
      width: 150px;
      display: inline-block;
    }

    .search-bar {
      width: 100%;
      padding: 5px 25px 5px 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #fff;
      font-size: 14px;
      box-sizing: border-box;
    }

    .search-bar:focus {
      border-color: #07432e;
      outline: none;
    }

    .search-bar-icon {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      font-size: 14px;
      color: #888;
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
    }

    .reset-bar-icon {
      padding: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #fff;
      font-size: 14px;
    }

    .reset-bar-icon:hover {
      color: #07432e;
    }
  </style>
  <section class="home-section">
    <div class="home-content">
      <i class="fas fa-bars bx-menu"></i>
    </div>

    <div class="content-wrapper">

      <div class="header-box">
        <label style="color: #a6a6a6; margin-left: 10px;">Manage Journal</label>
      </div>
      <div class="main-box">
        <div class="whole-box">
          <div class="header-group">
            <h2>Journal Details</h2>

            <div class="button-container">
              <button id="openAddModalBtn" class="add-btn">
                <i class="fa-solid fa-plus"></i>Add
              </button>
              <button class="export-btn"
                onclick="window.location.href='export_journal.php?student_id=<?php echo $student_id; ?>'">
                <i class="fa-solid fa-file-export"></i> Export
              </button>
            </div>
          </div>
          <div class="filter-group">
            <form method="GET" action="journal.php">
              <div class="search-bar-container">
                <input type="text" class="search-bar" name="search" placeholder="Search"
                  value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-bar-icon">
                  <i class="fa fa-search"></i>
                </button>
              </div>
            </form>
            <!-- Reset Button Form -->
            <form method="GET" action="journal.php">
              <button type="submit" class="reset-bar-icon">
                <i class="fa fa-times-circle"></i>
              </button>
            </form>
          </div>
          <table>
            <thead>
              <tr>
                <th style='width: 20%;'>Title</th>
                <th>Description</th>
                <th style='width: 15%;'>Date Submitted</th>
                <!-- <th style='width: 5%;'>Size</th> -->
                <th class="action">Action</th>
              </tr>
            </thead>
            <style>
              td.description {
                max-width: 300px;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
              }
            </style>
            <tbody>
              <?php if (!empty($journals)): ?>
                <?php foreach ($journals as $journal): ?>
                  <tr>
                    <td style="width: 20%;">
                      <?php echo htmlspecialchars($journal['journal_name']); ?>
                    </td>
                    <td class="description">
                      <?php echo htmlspecialchars($journal['journal_description']); ?>
                    </td>
                    <td style="width: 15%;">
                      <?php echo date("M d, Y", strtotime($journal['journal_date'])); ?>
                    </td>
                    <td class="action">
                      <button class="action-icon edit-btn" data-id="<?php echo $journal['journal_id']; ?>"
                        data-student-id="<?php echo $journal['student_id']; ?>">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <button class="action-icon delete-btn" data-id="<?php echo $journal['journal_id']; ?>">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">No journal entries found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>

          <div class="pagination">
            <?php renderPaginationLinks($total_pages, $current_page, $search_query); ?>
          </div>

        </div>
      </div>
    </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
      <div class="modal-content-big">
        <span class="close" id="closeAddModal">&times;</span>
        <h2>Add Journal Entry</h2>

        <form action="add_journal.php" method="POST">
          <div class="horizontal-group">
            <div class="input-group">
              <label for="journalTitle">Title</label>
              <input class="title" type="text" id="journalTitle" name="journalTitle" placeholder="Input Title" required>
            </div>

            <div class="input-group">
              <label for="journalDate">Date</label>
              <input class="date" type="date" id="journalDate" name="journalDate" required>
            </div>
          </div>

          <input type="hidden" id="journalSize" name="journalSize" required>

          <label for="journalDescription">Description</label>
          <textarea id="journalDescription" name="journalDescription" placeholder="Input Journal Description"
            required></textarea>

          <button type="submit" class="modal-btn">Add Entry</button>
        </form>
      </div>
    </div>
    <script>
      addBtn.onclick = function () {
        addModal.style.display = "block";

        // Get today's date
        const today = new Date();

        // Format the date to DD/MM/YYYY
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
        const year = today.getFullYear();

        const formattedDate = `${day}/${month}/${year}`;

        // Set the formatted date in the journalDate input
        document.getElementById('journalDate').value = formattedDate;
      };

    </script>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
      <div class="modal-content-big">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Edit Journal Entry</h2>

        <form action="edit_journal.php" method="POST">
          <input type="hidden" id="journalIdDisplay" name="journalIdDisplay" disabled>
          <input type="hidden" id="studentIdDisplay" name="studentIdDisplay" disabled
            value="<?php echo $student_id; ?>">
          <input type="hidden" id="journalId" name="journalId">
          <input type="hidden" id="studentId" name="studentId" value="<?php echo $student_id; ?>">

          <div class="horizontal-group">
            <div class="input-group">
              <label for="editJournalTitle">Title</label>
              <input class="title" type="text" id="editJournalTitle" name="editJournalTitle" required>
            </div>

            <div class="input-group">
              <label for="editJournalDate">Date</label>
              <input class="date" type="date" id="editJournalDate" name="editJournalDate" readonly>
            </div>
          </div>

          <label for="editJournalDescription">Description</label>
          <textarea id="editJournalDescription" name="editJournalDescription" required></textarea>

          <button type="submit" class="modal-btn">Save Changes</button>
        </form>
      </div>
    </div>


    <!-- Success Modal for Journal Submission -->
    <div id="journalSuccessModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2>Journal Submitted Successfully!</h2>
        <p>Thank you for submitting your journal, <span
            style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeModal('journalSuccessModal')">Close</button>
      </div>
    </div>

    <!-- Success Modal for Journal Update -->
    <div id="journalUpdateSuccessModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2>Journal Updated Successfully!</h2>
        <p>Your journal entry has been updated successfully, <span
            style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeModal('journalUpdateSuccessModal')">Close</button>
      </div>
    </div>
    <!-- Success Modal for Journal Deletion -->
    <div id="journalDeleteSuccessModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2>Journal Deleted Successfully!</h2>
        <p>Your journal entry has been deleted, <span
            style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeModal('journalDeleteSuccessModal')">Close</button>
      </div>
    </div>
    <!-- Success Modal for Journal Duplicate Day -->
    <div id="journalErrorSuccessModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">You've Already Submitted Today!</h2>
        <p>Just edit your journal for today, <span
            style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeModal('journalErrorSuccessModal')">Close</button>
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

  </section>
  <script>
    function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    // Show the appropriate modal based on session variables
    window.onload = function () {
      <?php if (isset($_SESSION['journal_success'])): ?>
        showModal('journalSuccessModal');
        <?php unset($_SESSION['journal_success']); ?>
      <?php elseif (isset($_SESSION['journal_update_success'])): ?>
        showModal('journalUpdateSuccessModal');
        <?php unset($_SESSION['journal_update_success']); ?>
      <?php elseif (isset($_SESSION['journal_delete_success'])): ?>
        showModal('journalDeleteSuccessModal');
        <?php unset($_SESSION['journal_delete_success']); ?>
      <?php elseif (isset($_SESSION['journal_error'])): ?>
        showModal('journalErrorSuccessModal');
        <?php unset($_SESSION['journal_error']); ?>
      <?php endif; ?>
    };

    // Get the modal elements
    var addModal = document.getElementById("addModal");
    var editModal = document.getElementById("editModal");

    // Get the buttons that open the modals
    var addBtn = document.getElementById("openAddModalBtn");

    // Get the <span> elements that close the modals
    var closeAddModal = document.getElementById("closeAddModal");
    var closeEditModal = document.getElementById("closeEditModal");

    // Open the Add modal and set the date to today in DD/MM/YYYY format
    addBtn.onclick = function () {
      addModal.style.display = "block";

      // Get today's date
      const today = new Date();

      // Format the date to DD/MM/YYYY
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
      const year = today.getFullYear();

      // Set the formatted date in the journalDate input (as HTML date input needs YYYY-MM-DD format)
      document.getElementById('journalDate').value = `${year}-${month}-${day}`;
    }

    closeAddModal.onclick = function () {
      addModal.style.display = "none";
    }

    closeEditModal.onclick = function () {
      editModal.style.display = "none";
    }

    window.onclick = function (event) {
      if (event.target == addModal) {
        addModal.style.display = "none";
      }
      if (event.target == editModal) {
        editModal.style.display = "none";
      }
    }

    const editButtons = document.querySelectorAll('.edit-btn');

    editButtons.forEach(button => {
      button.addEventListener('click', () => {
        const journalId = button.getAttribute('data-id');

        fetch(`fetch_journal.php?id=${journalId}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('editJournalTitle').value = data.journal_name;
            document.getElementById('editJournalDate').value = data.journal_date;
            document.getElementById('editJournalDescription').value = data.journal_description;

            document.getElementById('journalIdDisplay').value = data.journal_id;
            document.getElementById('journalId').value = data.journal_id;

            document.getElementById('editModal').style.display = 'block';
          })
          .catch(error => console.error('Error:', error));
      });
    });

    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
      button.addEventListener('click', () => {
        const journalId = button.getAttribute('data-id');

        if (confirm('Are you sure you want to delete this journal entry?')) {
          window.location.href = `delete_journal.php?id=${journalId}`;
        }
      });
    });

  </script>
  <script src="./js/script.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>