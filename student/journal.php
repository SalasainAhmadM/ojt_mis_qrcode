<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header("Location: ../index.php");
  exit();
}
// Fetch student details
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM student WHERE student_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->num_rows > 0 ? $result->fetch_assoc() : [
    'student_firstname' => 'Unknown',
    'student_middle' => 'U',
    'student_lastname' => 'User',
    'student_email' => 'unknown@wmsu.edu.ph'
  ];
  $stmt->close();
}

$qr_url = ($student['ojt_type'] === 'Project-Based') ? "qr-code_project_based.php" : "qr-code.php";
// Fetch company_id associated with the student
$query = "
    SELECT student.student_id, student.student_firstname, student.student_lastname, company.company_id 
    FROM student 
    JOIN company ON student.company = company.company_id 
    WHERE student.student_id = ?";

if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $company_id = $data['company_id'];
  }
  $stmt->close();
}
// Function to check for "Absent" remark
function isAbsent($student_id, $current_date)
{
  global $database;
  $query = "SELECT * FROM attendance_remarks 
            WHERE student_id = ? AND remark_type = 'Absent' 
              AND EXISTS (SELECT 1 FROM schedule WHERE schedule_id = attendance_remarks.schedule_id AND date = ?)";

  if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("is", $student_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_absent = $result->num_rows > 0;
    $stmt->close();
    return $is_absent;
  }
  return false;
}

// Function to check if a day is Suspended
function isSuspended($current_date)
{
  global $database;
  $query = "SELECT * FROM schedule WHERE date = ? AND day_type = 'Suspended'";

  if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_suspended = $result->num_rows > 0;
    $stmt->close();
    return $is_suspended;
  }
  return false;
}

// Function to check if a day is a Holiday
function isHoliday($current_date)
{
  global $database;
  $query = "SELECT holiday_name FROM holiday WHERE holiday_date = ?";

  if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $holiday = $result->fetch_assoc();
    $stmt->close();
    return $holiday ? $holiday['holiday_name'] : false;
  }
  return false;
}
// Fetch the first schedule date for the student's company
$schedule_query = "SELECT MIN(date) AS earliest_schedule FROM schedule WHERE company_id = ?";
if ($stmt = $database->prepare($schedule_query)) {
  $stmt->bind_param("i", $company_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $earliest_schedule = $result->fetch_assoc()['earliest_schedule'] ?? date('Y-m-d');
  $stmt->close();
}

// Fetch the earliest journal entry date for the student
$first_journal_query = "SELECT MIN(journal_date) AS earliest_date FROM student_journal WHERE student_id = ?";
if ($stmt = $database->prepare($first_journal_query)) {
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $earliest_date = $result->fetch_assoc()['earliest_date'] ?? date('Y-m-d');
  $stmt->close();
}

// Calculate total weeks between the earliest journal entry and today
$now = new DateTime();
$earliest = new DateTime($earliest_date);
$total_weeks = ceil($now->diff($earliest)->days / 7);

// Determine the current page for pagination (latest week is page 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;

function getWeekRange($page)
{
  $now = new DateTime();
  $offset = ($page - 1) * 7;  // Offset based on current week

  $start_of_week = clone $now;
  $start_of_week->modify("-$offset days")->modify('monday this week');

  $end_of_week = clone $start_of_week;
  $end_of_week->modify('sunday this week');

  return [$start_of_week->format('Y-m-d'), $end_of_week->format('Y-m-d')];
}

// Fetch journals for the current week (most recent on Week 1)
[$start_date, $end_date] = getWeekRange($page);

// Fetch journals for the current week
$journals_query = "
  SELECT * FROM student_journal 
  WHERE student_id = ? 
    AND journal_date BETWEEN ? AND ?
  ORDER BY journal_date DESC";

if ($stmt = $database->prepare($journals_query)) {
  $stmt->bind_param("iss", $student_id, $start_date, $end_date);
  $stmt->execute();
  $result = $stmt->get_result();

  $journals = [];
  while ($row = $result->fetch_assoc()) {
    $journals[] = $row;
  }
  $stmt->close();
}
// Check for absences on days without journal entries

// Function to render pagination links with reverse week numbering
function renderPaginationLinks($current_page, $total_weeks)
{
  $reversed_week_number = $total_weeks - $current_page + 1;

  // Previous Week button (older weeks)
  if ($current_page < $total_weeks) {
    echo '<a href="?page=' . ($current_page + 1) . '" class="prev">Previous Week</a>';
  }

  // Show the current week as the active page
  echo '<span class="active">Week ' . $reversed_week_number . '</span>';

  // Next Week button (more recent weeks)
  if ($current_page > 1) {
    echo '<a href="?page=' . ($current_page - 1) . '" class="next">Next Week</a>';
  }
}

$search_date = isset($_GET['search']) ? $_GET['search'] : null;

// If search date is provided, fetch journal entries and statuses for that date
if ($search_date) {
  // Fetch journal for the specified date
  $journals_query = "SELECT * FROM student_journal WHERE student_id = ? AND journal_date = ?";
  if ($stmt = $database->prepare($journals_query)) {
    $stmt->bind_param("is", $student_id, $search_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $journals = [];
    while ($row = $result->fetch_assoc()) {
      $journals[] = $row;
    }
    $stmt->close();
  }

  // Check if the date is a holiday
  $holiday_name = isHoliday($search_date);

  // Check if the date is suspended
  $is_suspended = isSuspended($search_date);

  // Check if the student is marked as absent on the date
  $is_absent = isAbsent($student_id, $search_date);
} else {
  // Handle regular pagination as in your original code
  $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
  [$start_date, $end_date] = getWeekRange($page);
  $journals_query = "
      SELECT * FROM student_journal 
      WHERE student_id = ? 
        AND journal_date BETWEEN ? AND ?
      ORDER BY journal_date DESC";
  if ($stmt = $database->prepare($journals_query)) {
    $stmt->bind_param("iss", $student_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $journals = [];
    while ($row = $result->fetch_assoc()) {
      $journals[] = $row;
    }
    $stmt->close();
  }
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
<!-- include './others/filter_journal.php'; -->

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern - Journal</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
  <link rel="stylesheet" href="../css/main.css">
  <!-- <link rel="stylesheet" href="./css/style.css"> -->
  <!-- <link rel="stylesheet" href="./css/index.css"> -->
  <link rel="stylesheet" href="../css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

  <style>

  </style>
</head>
<style>
  /* For Mobile Screens */
  @media (max-width: 768px) {
    .bx-menu {
      display: block;
      /* Show the hamburger icon in mobile view */
    }

    .sidebar.close {
      width: 78px;
      margin-left: -78px;
    }




    .home-section .home-content .bx-menu {
      margin: 0 15px;
      cursor: pointer;
      margin-left: -68px;

    }

    .home-section .home-content .text {
      font-size: 26px;
      font-weight: 600;
      margin-left: -68px;
    }

    .header-box {
      margin-left: 10px;
      width: 110%;
      padding-left: 10px;
      width: calc(110% - 78px);
      margin-left: -60px;
    }

    .content-wrapper {
      margin-top: 0;
    }

    .whole-box {
      padding: 0px;
      padding-left: 10px;
      padding-right: 0px;
      margin-left: -68px;
      width: 120%;
    }

    .modal-content {
      z-index: 1000;
      width: 50%;
      padding: 15px;
    }

    .content-wrapper {
      margin-top: 0;
    }
  }

  /* For Web/Desktop Screens */
  @media (min-width: 769px) {
    .bx-menu {
      display: none;
      /* Hide the hamburger icon in web/desktop view */
    }
  }

  /* Sidebar */
  @media (max-width: 420px) {
    .sidebar.close .nav-links li .sub-menu {
      display: none;
    }
  }
</style>

<body>
  <div class="header">
    <i class=""></i>
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
        <a href="<?php echo $qr_url; ?>">
          <i class="fa-solid fa-qrcode"></i>
          <span class="link_name">QR Scanner</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $qr_url; ?>">QR Scanner</a></li>
        </ul>
      </li>

      <?php if ($student['ojt_type'] !== 'Project-Based'): ?>
        <li>
          <a href="dtr.php">
            <i class="fa-solid fa-clipboard-question"></i>
            <span class="link_name">Remarks</span>
          </a>
          <ul class="sub-menu blank">
            <li><a class="link_name" href="dtr.php">Remarks</a></li>
          </ul>
        </li>
      <?php endif; ?>


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
    <?php
    [$start_date, $end_date] = getWeekRange($page);

    $current_week = [];
    $period = new DatePeriod(
      new DateTime($start_date),
      new DateInterval('P1D'),
      (new DateTime($end_date))->modify('+1 day')
    );

    foreach ($period as $day) {
      if ($day->format("N") < 6) {
        $current_week[$day->format("Y-m-d")] = null;
      }
    }

    foreach ($journals as $journal) {
      $journal_date = date("Y-m-d", strtotime($journal['journal_date']));
      if (array_key_exists($journal_date, $current_week)) {
        $current_week[$journal_date] = $journal;
      }
    }
    ?>


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
              <button class="export-btn" id="openJournalModalBtn">
                <i class="fa-solid fa-file-export"></i> Export
              </button>

            </div>
          </div>
          <div class="filter-group">
            <form method="GET" action="journal.php" class="search-form">
              <div class="search-bar-container">
                <input type="date" class="search-bar" id="searchDate" name="search"
                  value="<?= htmlspecialchars($search_date); ?>" onchange="this.form.submit()">
              </div>
            </form>


            <script>
              document.addEventListener('DOMContentLoaded', function () {
                const searchDateInput = document.getElementById('searchDate');
                const earliestScheduleDate = document.getElementById('earliestScheduleDate')?.value || '2024-01-01'; // Default fallback

                searchDateInput.setAttribute('min', earliestScheduleDate);

                const today = new Date().toISOString().split('T')[0];
                searchDateInput.setAttribute('max', today);

                // Prevent weekend selection
                searchDateInput.addEventListener('input', function () {
                  const selectedDate = new Date(this.value);
                  const dayOfWeek = selectedDate.getDay();

                  if (dayOfWeek === 6 || dayOfWeek === 0) {
                    openModal('weekendSearchErrorModal'); // Open modal for error
                    this.value = ''; // Clear invalid selection
                  }
                });
              });
            </script>
            <!-- Dropdown form to navigate by week -->
            <form method="GET" action="journal.php">
              <div class="search-bar-container">
                <select class="search-bar" name="page" onchange="this.form.submit()">
                  <option value="" disabled selected>Select Week</option>
                  <?php
                  for ($i = 1; $i <= $total_weeks; $i++) {
                    $reversed_week_number = $total_weeks - $i + 1;
                    echo "<option value=\"$i\">Week $reversed_week_number</option>";
                  }
                  ?>
                </select>
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
                <th class="title">Title</th>
                <th class="description">Description</th>
                <th class="date">Date Submitted</th>
                <th class="date">Status</th>
                <th class="action">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $today = new DateTime();
              $dates_to_display = $search_date ? [$search_date] : array_keys($current_week); // Only show search date if provided
              
              foreach ($dates_to_display as $date) {
                $current_date = new DateTime($date);

                if ($current_date > $today && !$search_date) {
                  // Skip future dates in weekly view, but include if it's the searched date
                  continue;
                }

                $is_absent = isAbsent($student_id, $date);
                $is_suspended = isSuspended($date);
                $holiday_name = isHoliday($date);

                // Determine CSS class and description
                $status_class = $is_absent ? 'absent' : ($is_suspended ? 'suspended' : ($holiday_name ? 'holiday' : ''));
                $description = $is_absent ? 'Absent as per attendance record'
                  : ($is_suspended ? 'Suspended' : ($holiday_name ? "$holiday_name" : 'No Entry'));

                // Fetch journal data if available for the date
                $journal = isset($current_week[$date]) ? $current_week[$date] : null;
                ?>

                <?php if ($journal): ?>
                  <tr>
                    <td class="title"><?php echo htmlspecialchars($journal['journal_name']); ?></td>
                    <td class="description"><?php echo htmlspecialchars($journal['journal_description']); ?></td>
                    <td class="date"><?php echo date("M d, Y", strtotime($journal['journal_date'])); ?></td>
                    <td class="date">
                      <?php echo $journal['adviser_viewed'] == 1 ? 'Checked' : 'Pending'; ?>
                    </td>

                    <td class="action">
                      <button class="action-icon delete-btn"
                        onclick="openJournalImages(<?php echo $journal['journal_id']; ?>)">
                        <i class="fa-solid fa-images"></i>
                      </button>
                      <button class="action-icon edit-btn" data-id="<?php echo $journal['journal_id']; ?>"
                        data-student-id="<?php echo $journal['student_id']; ?>">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <button class="action-icon delete-btn"
                        onclick="openDeleteModal(<?php echo $journal['journal_id']; ?>)">
                        <i class="fa-solid fa-trash"></i>
                      </button>

                    </td>
                  </tr>
                <?php else: ?>
                  <tr>
                    <td class="title <?php echo $status_class; ?>">
                      <?php echo $is_absent ? 'Absent' : ($is_suspended ? 'Suspended' : ($holiday_name ? 'Holiday' : 'No Entry')); ?>
                    </td>
                    <td class="description <?php echo $status_class; ?>"><?php echo $description; ?></td>
                    <td class="date <?php echo $status_class; ?>"><?php echo date("M d, Y", strtotime($date)); ?></td>
                    <td class="date">N/A</td>
                    <td class="action">—</td>
                  </tr>
                <?php endif; ?>
              <?php } ?>
            </tbody>



          </table>

          <!-- Pagination Links -->
          <div class="paginationJournal">
            <?php renderPaginationLinks($page, $total_weeks); ?>
          </div>

        </div>
      </div>
    </div>
    </div>
    <style>
      .journal-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }

      .journal-item input[type="checkbox"] {
        margin-right: 10px;
      }

      .search-input-journal {
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #fff;
        font-size: 14px;
        margin-bottom: 4px;
      }
    </style>
    <!-- Journal Selection Modal -->
    <div id="journalModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" id="closeJournalModal">&times;</span>
        <h2>Select Journals to Export</h2>

        <select id="weekDropdown" class="search-input-journal">
          <!-- Week options will be dynamically inserted here -->
        </select>


        <ul id="journalList" class="green-palette">
          <!-- Journal list items with checkboxes will be dynamically inserted here -->
        </ul>

        <button id="exportSelectedJournalsBtn" class="assign-btn">Export Selected Journals</button>
      </div>
    </div>




    <script>
      let allJournals = {}; // Store journals by week
      const selectedJournals = new Set(); // Track selected journals
      let selectedWeeks = new Set(); // Track weeks with selected journals

      document.getElementById('openJournalModalBtn').addEventListener('click', function () {
        fetch(`get_journals.php?student_id=<?php echo $student_id; ?>`)
          .then(response => response.json())
          .then(data => {
            allJournals = data;
            const weeks = Object.keys(allJournals); // Use "Week N" labels from PHP
            populateWeekDropdown(weeks); // Populate dropdown with "Week N"
            renderJournalList(allJournals[weeks[weeks.length - 1]]); // Render the latest week's journals by default
            document.getElementById('weekDropdown').value = weeks[weeks.length - 1]; // Select the latest week in dropdown
            document.getElementById('journalModal').style.display = 'block';
          })
          .catch(error => console.error('Error fetching journals:', error));
      });

      // Populate the week dropdown
      function populateWeekDropdown(weeks) {
        const dropdown = document.getElementById('weekDropdown');
        dropdown.innerHTML = ''; // Clear existing options

        const sortedWeeks = Array.from(selectedWeeks).concat(weeks.filter(week => !selectedWeeks.has(week)));

        sortedWeeks.forEach(week => {
          const option = document.createElement('option');
          option.value = week;
          option.textContent = week; // Display "Week N"
          dropdown.appendChild(option);
        });

        dropdown.addEventListener('change', function () {
          const selectedWeek = this.value;
          renderJournalList(allJournals[selectedWeek] || []);
        });
      }

      // Render the journal list with checkboxes
      function renderJournalList(journals) {
        const journalList = document.getElementById('journalList');
        journalList.innerHTML = ''; // Clear existing list

        if (!journals || journals.length === 0) {
          const noJournalsMessage = document.createElement('li');
          noJournalsMessage.textContent = "No journals found!";
          noJournalsMessage.classList.add('no-journals-message');
          journalList.appendChild(noJournalsMessage);
          return;
        }

        journals.forEach(journal => {
          const li = document.createElement('li');
          li.classList.add('journal-item');

          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.value = journal.journal_id;
          checkbox.checked = selectedJournals.has(journal.journal_id);

          checkbox.addEventListener('change', function () {
            if (checkbox.checked) {
              selectedJournals.add(journal.journal_id);
              selectedWeeks.add(getCurrentWeekLabel(journal.journal_date));
            } else {
              selectedJournals.delete(journal.journal_id);
              const weekLabel = getCurrentWeekLabel(journal.journal_date);
              if (![...selectedJournals].some(id => allJournals[weekLabel]?.find(j => j.journal_id === id))) {
                selectedWeeks.delete(weekLabel);
              }
            }
            populateWeekDropdown(Object.keys(allJournals)); // Update dropdown order
          });

          const label = document.createElement('label');
          label.textContent = `${journal.journal_name} (${journal.journal_date})`;
          label.prepend(checkbox);

          li.appendChild(label);
          journalList.appendChild(li);
        });
      }

      // Utility function to find the "Week N" label of a given date
      function getCurrentWeekLabel(date) {
        for (const weekLabel in allJournals) {
          if (allJournals[weekLabel].some(j => j.journal_date === date)) {
            return weekLabel;
          }
        }
        return null;
      }

      // Close the modal
      document.getElementById('closeJournalModal').addEventListener('click', function () {
        document.getElementById('journalModal').style.display = 'none';
      });

      // Close the modal when clicking outside it
      window.addEventListener('click', function (event) {
        const modal = document.getElementById('journalModal');
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });

      // Export selected journals
      document.getElementById('exportSelectedJournalsBtn').addEventListener('click', function () {
        const selected = Array.from(selectedJournals).join(',');
        if (selected.length === 0) {
          alert('Please select at least one journal to export.');
          return;
        }
        window.location.href = `export_journal.php?student_id=<?php echo $student_id; ?>&journal_ids=${selected}`;
      });

    </script>


    <!-- No Journals Found Error Modal -->
    <div id="noJournalsModal" class="modal" style="display:none;">
      <div class="modal-content">
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">No Journals Found!</h2>
        <p>No journal entries match the selected date.</p>
        <button class="proceed-btn" onclick="closeModal('noJournalsModal')">Close</button>
      </div>
    </div>

    <!-- Images Modal -->
    <div id="openJournalImages" class="modal-img">
      <div class="modal-content-img">
        <button class="close-btn-img" onclick="closeModal('openJournalImages')">&times;</button>
        <h2>Journal Images</h2>

        <!-- Swiper Container -->
        <div class="swiper-container">
          <div class="swiper-wrapper">
            <!-- Dynamic slides will be added here -->
          </div>

          <!-- Swiper Navigation -->
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>

          <!-- Swiper Pagination -->
          <div class="swiper-pagination"></div>
        </div>
      </div>
    </div>


    <script>
      function openJournalImages(journalId) {
        const modal = document.getElementById('openJournalImages');
        modal.style.display = 'flex';

        const swiperWrapper = document.querySelector('.swiper-wrapper');
        swiperWrapper.innerHTML = '';

        fetch(`get_journal_images.php?journal_id=${journalId}`)
          .then(response => response.json())
          .then(images => {
            if (images.length === 0) {
              addSlide('../img/empty.png');
            } else {
              images.forEach(image => {
                addSlide(image.path);
              });
            }

            new Swiper('.swiper-container', {
              loop: true,
              centeredSlides: true,
              slidesPerView: 1,
              spaceBetween: 30,
              navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
              },
              pagination: {
                el: '.swiper-pagination',
                clickable: true,
              },
            });
          })
          .catch(error => {
            console.error('Error fetching images:', error);
            addSlide('../img/empty.png');
          });

        function addSlide(imagePath) {
          const slide = document.createElement('div');
          slide.classList.add('swiper-slide');
          slide.innerHTML = `<img src="${imagePath}" alt="Journal Image" class="journal-image" />`;
          swiperWrapper.appendChild(slide);
        }
      }

      function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
      }


    </script>

    <!-- Add Modal -->
    <div id="addModal" style="padding-top: 50px;" class="modal">
      <div class="modal-content-big">
        <span class="close" id="closeAddModal">&times;</span>
        <h2>Add Journal Entry</h2>

        <form action="add_journal.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" id="earliestScheduleDate" value="<?php echo $earliest_schedule; ?>">
          <input type="hidden" id="company" name="company" value="<?php echo $company_id; ?>">
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

          <!-- <input type="hidden" id="journalSize" name="journalSize" required> -->

          <label for="journalDescription">Description</label>
          <textarea id="journalDescription" name="journalDescription" placeholder="Input Journal Description"
            required></textarea>
          <div class="image-upload-row">
            <!-- Image 1 -->
            <div class="journal-img-container">
              <label for="image1">
                <img id="imagePreview1" src="../img/default.png" alt="journal Preview 1"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="image1" name="image1" accept="image/*" onchange="previewAddImage(1)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 1</p>
            </div>

            <!-- Image 2 -->
            <div class="journal-img-container">
              <label for="image2">
                <img id="imagePreview2" src="../img/default.png" alt="journal Preview 2"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="image2" name="image2" accept="image/*" onchange="previewAddImage(2)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 2</p>
            </div>

            <!-- Image 3 -->
            <div class="journal-img-container">
              <label for="image3">
                <img id="imagePreview3" src="../img/default.png" alt="journal Preview 3"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="image3" name="image3" accept="image/*" onchange="previewAddImage(3)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 3</p>
            </div>
          </div>

          <button type="submit" class="modal-btn">Add Entry</button>
        </form>
      </div>
    </div>


    <script>

    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const journalDateInput = document.getElementById('journalDate');
        const earliestScheduleDate = document.getElementById('earliestScheduleDate').value;

        // Set the minimum and maximum date for journalDate input
        journalDateInput.setAttribute('min', earliestScheduleDate);

        // Set maximum date to today
        const today = new Date().toISOString().split('T')[0];
        journalDateInput.setAttribute('max', today);

        // Disable weekends and trigger the modal if a weekend is selected
        journalDateInput.addEventListener('input', function () {
          const selectedDate = new Date(this.value);
          const dayOfWeek = selectedDate.getDay();

          // Check if the selected day is Saturday (6) or Sunday (0)
          if (dayOfWeek === 6 || dayOfWeek === 0) {
            openModal('weekendErrorModal'); // Trigger the modal
            this.value = ""; // Clear the invalid selection
          }
        });
      });
    </script>

    <script>
      // Function to open the error modal
      function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
      }

      // Function to close the error modal
      function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
      }

      // Disable weekends and trigger the modal if a weekend is selected
      document.getElementById('journalDate').addEventListener('input', function () {
        const input = this;
        const selectedDate = new Date(input.value);
        const dayOfWeek = selectedDate.getDay();

        // Check if the selected day is Saturday (6) or Sunday (0)
        if (dayOfWeek === 6 || dayOfWeek === 0) {
          openModal('weekendErrorModal'); // Trigger the modal
          input.value = ""; // Clear the invalid selection
        }
      });
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
    <div id="editModal" style="padding-top: 50px;" class="modal">
      <div class="modal-content-big">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Edit Journal Entry</h2>

        <form action="edit_journal.php" method="POST" enctype="multipart/form-data">
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
          <div class="image-upload-row">
            <!-- Image 1 -->
            <div class="journal-img-container">
              <label for="imageEdit1">
                <img id="imageEditPreview1" src="../img/default.png" alt="journal Preview 1"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="imageEdit1" name="imageEdit1" accept="image/*" onchange="previewEditImage(1)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 1</p>
            </div>

            <!-- Image 2 -->
            <div class="journal-img-container">
              <label for="imageEdit2">
                <img id="imageEditPreview2" src="../img/default.png" alt="journal Preview 2"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="imageEdit2" name="imageEdit2" accept="image/*" onchange="previewEditImage(2)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 2</p>
            </div>

            <!-- Image 3 -->
            <div class="journal-img-container">
              <label for="imageEdit3">
                <img id="imageEditPreview3" src="../img/default.png" alt="journal Preview 3"
                  class="journal-preview-img square-img" />
              </label>
              <input type="file" id="imageEdit3" name="imageEdit3" accept="image/*" onchange="previewEditImage(3)"
                style="display: none;">
              <p class="journal-img-label">Click to upload image 3</p>
            </div>
          </div>

          <button type="submit" class="modal-btn">Save Changes</button>
        </form>
      </div>
    </div>
    <script>
      function previewEditImage(imageNumber) {
        const fileInput = document.getElementById(`imageEdit${imageNumber}`);
        const previewImage = document.getElementById(`imageEditPreview${imageNumber}`);

        if (fileInput.files && fileInput.files[0]) {
          const reader = new FileReader();
          reader.onload = function (e) {
            previewImage.src = e.target.result;
          };
          reader.readAsDataURL(fileInput.files[0]);
        }
      }
      function previewAddImage(imageAddNumber) {
        const input = document.getElementById(`image${imageAddNumber}`);
        const preview = document.getElementById(`imagePreview${imageAddNumber}`);

        if (input.files && input.files[0]) {
          const addreader = new FileReader();
          addreader.onload = function (e) {
            preview.src = e.target.result;
          };
          addreader.readAsDataURL(input.files[0]);
        }
      }
    </script>


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
        <p>Thank you for submitting your journal, <span style="color: #095d40; font-size: 20px">
            <?php echo $_SESSION['full_name']; ?>!</span></p>
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
    <!-- Weekend Selection Error Modal -->
    <div id="weekendErrorModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation for Error -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">Weekends Are Not Selectable!</h2>
        <p>Please choose a weekday for your journal entry.</p>
        <button class="proceed-btn" onclick="closeModal('weekendErrorModal')">Close</button>
      </div>
    </div>
    <!-- Weekend Search Selection Error Modal -->
    <div id="weekendSearchErrorModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation for Error -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">Weekends Are Not Selectable!</h2>
        <p>Please choose a weekday for your search.</p>
        <button class="proceed-btn" onclick="closeModal('weekendSearchErrorModal')">Close</button>
      </div>
    </div>
    <!-- Absent Error Modal -->
    <div id="absentErrorModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation for Error -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">You are Absent on this Day!</h2>
        <p>Please choose another day for your journal entry.</p>
        <button class="proceed-btn" onclick="closeModal('absentErrorModal')">Close</button>
      </div>
    </div>
    <!-- Suspended Error Modal -->
    <div id="suspendedErrorModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation for Error -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">This day is Suspended!</h2>
        <p>Please choose another day for your journal entry.</p>
        <button class="proceed-btn" onclick="closeModal('suspendedErrorModal')">Close</button>
      </div>
    </div>
    <!-- Holiday Error Modal -->
    <div id="holidayErrorModal" class="modal">
      <div class="modal-content">
        <!-- Lottie Animation for Error -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2 style="color: #8B0000">This day is Holiday!</h2>
        <p>Please choose another day for your journal entry.</p>
        <button class="proceed-btn" onclick="closeModal('holidayErrorModal')">Close</button>
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
        <h2 style="color: #8B0000">You've Already Submitted On This Date!</h2>
        <p>Just edit your journal, <span
            style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeModal('journalErrorSuccessModal')">Close</button>
      </div>
    </div>

    <div id="deleteModal" class="modal" style="display: none;">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/alert-095d40.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay></lottie-player>
        </div>
        <h2 style="color: #000">Are you sure you want to delete?</h2>
        <input type="hidden" id="delete-journal-id" value="">
        <div style="display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 20px">
          <button class="confirm-btn" onclick="confirmDelete()">Confirm</button>
          <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div id="deleteSuccessModal" class="modal" style="display: none;">
      <div class="modal-content">
        <!-- Lottie Animation -->
        <div style="display: flex; justify-content: center; align-items: center;">
          <lottie-player src="../animation/delete.json" background="transparent" speed="1"
            style="width: 150px; height: 150px;" loop autoplay>
          </lottie-player>
        </div>
        <h2>Journal Deleted Successfully!</h2>
        <p>The journal entry has been deleted successfully <br> <span
            style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
        <button class="proceed-btn" onclick="closeDeleteSuccessModal()">Close</button>
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

      <?php elseif (isset($_SESSION['journal_absent'])): ?>
        showModal('absentErrorModal');
        <?php unset($_SESSION['journal_absent']); ?>
      <?php elseif (isset($_SESSION['journal_suspended'])): ?>
        showModal('suspendedErrorModal');
        <?php unset($_SESSION['journal_suspended']); ?>
      <?php elseif (isset($_SESSION['journal_holiday'])): ?>
        showModal('holidayErrorModal');
        <?php unset($_SESSION['journal_holiday']); ?>

      <?php elseif (isset($_SESSION['journal_error'])): ?>
        showModal('journalErrorSuccessModal');
        <?php unset($_SESSION['journal_error']); ?>

      <?php elseif (isset($_SESSION['journal_delete_success'])): ?>
        showModal('deleteSuccessModal');
        <?php unset($_SESSION['journal_delete_success']); ?>
      <?php endif; ?>
    };

    // Open Add and Edit Modals
    var addModal = document.getElementById("addModal");
    var editModal = document.getElementById("editModal");
    var addBtn = document.getElementById("openAddModalBtn");

    addBtn.onclick = function () {
      addModal.style.display = "block";
      const today = new Date();
      const day = String(today.getDate()).padStart(2, '0');
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const year = today.getFullYear();
      document.getElementById('journalDate').value = `${year}-${month}-${day}`;
    }

    var closeAddModal = document.getElementById("closeAddModal");
    var closeEditModal = document.getElementById("closeEditModal");

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

    // Edit button functionality
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

            // Load current images if they exist
            if (data.journal_image1) {
              document.getElementById('imageEditPreview1').src = data.journal_image1;
            } else {
              document.getElementById('imageEditPreview1').src = "../img/default.png";
            }

            if (data.journal_image2) {
              document.getElementById('imageEditPreview2').src = data.journal_image2;
            } else {
              document.getElementById('imageEditPreview2').src = "../img/default.png";
            }

            if (data.journal_image3) {
              document.getElementById('imageEditPreview3').src = data.journal_image3;
            } else {
              document.getElementById('imageEditPreview3').src = "../img/default.png";
            }

            editModal.style.display = 'block';
          })
          .catch(error => console.error('Error:', error));
      });
    });

    function openDeleteModal(journalId) {
      document.getElementById("delete-journal-id").value = journalId; // Store journalId in hidden field
      document.getElementById("deleteModal").style.display = "block"; // Show delete modal
    }

    function closeDeleteModal() {
      document.getElementById("deleteModal").style.display = "none"; // Hide delete modal
    }

    function closeDeleteSuccessModal() {
      document.getElementById('deleteSuccessModal').style.display = 'none';
      window.location.reload(); // Reload page after closing success modal
    }

    function confirmDelete() {
      const journalId = document.getElementById("delete-journal-id").value; // Get journal ID

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "delete_journal.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);

          if (response.status === 'success') {
            showModal('deleteSuccessModal'); // Show success modal
          } else {
            alert(response.message); // Show error message if deletion fails
          }
        }
      };

      xhr.send("id=" + journalId); // Send the journal ID via POST

      closeDeleteModal(); // Close the delete modal
    }

  </script>

  <script src="./js/script.js"></script>
  <script src="../js/sy.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>