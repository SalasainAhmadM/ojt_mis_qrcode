<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch adviser details from the database
$adviser_id = $_SESSION['user_id'];
$query = "SELECT * FROM adviser WHERE adviser_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $adviser_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $adviser = $result->fetch_assoc(); // Fetch adviser details
    } else {
        // Handle case where adviser is not found
        $adviser = [
            'adviser_firstname' => 'Unknown',
            'adviser_middle' => 'U',
            'adviser_lastname' => 'User',
            'adviser_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
}
// Fetch all addresses for the dropdown
$query = "SELECT * FROM address";
$addresses = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
}


include './others/filter_company.php';

// Fetch companys with pagination, department, and search functionality
$pagination_data = getCompanies($database, $search_query);
$companies = $pagination_data['companies'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];

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
    <title>Adviser - Company</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/style.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&family=Poppins:wght@600&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400&family=Poppins:wght@600&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Roboto+Mono:wght@400&display=swap"
        rel="stylesheet">

</head>
<style>
    table td {
        /* font-family: 'Roboto', sans-serif; */
        /* font-family: 'Lato', sans-serif; */
        /* font-family: 'Roboto Mono', monospace; */
        font-weight: 400;
    }
</style>

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
            <img src="../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
                alt="logout Image" class="logout-img">
            <div style="margin-top: 10px;" class="profile-info">
                <span
                    class="profile_name"><?php echo $adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '. ' . $adviser['adviser_lastname']; ?></span>
                <br />
                <span class="profile_email"><?php echo $adviser['adviser_email']; ?></span>
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
                <div class="iocn-link">
                    <a href="interns.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="interns.php">Manage Interns</a></li>
                    <!-- <li><a href="./intern/intern-profile.php">Student Profile</a></li> -->
                    <li><a href="./intern/intern-reports.php">Intern Reports</a></li>
                </ul>
            </li>
            <li>
                <div style="background-color: #07432e;" class="iocn-link">
                    <a href="company.php">
                        <i class="fa-regular fa-building"></i>
                        <span class="link_name">Company</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="company.php">Manage Company</a></li>
                    <li><a href="./company/company-intern.php">Company Interns</a></li>
                    <!-- <li><a href="./company/company-feedback.php">Company List</a></li> -->
                    <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
                    <li><a href="./intern/attendance-intern.php">Intern Attendance</a></li>
                    <li><a href="./intern/attendance-monitor.php">Monitoring</a></li>
                    <li><a href="./intern/intern_hours.php">Intern Total Hours</a></li>
                </ul>
            </li>
            <li>
                <a href="announcement.php">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="link_name">Announcement</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="announcement.php">Announcement</a></li>
                </ul>
            </li>
            <li>
                <a href="message.php">
                    <i class="fa-regular fa-comments"></i>
                    <span class="link_name">Message</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="message.php">Message</a></li>
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
                <label style="color: #a6a6a6;">Company Management</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Company Details</h2>
                    </div>

                    <div class="filter-group">

                        <!-- Search Bar Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="department"
                                value="<?php echo htmlspecialchars($selected_department, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="company.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>


                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Company Name</th>
                                <th class="name">Representative</th>
                                <th class="email">Email</th>
                                <th class="cnumber">Contact Number</th>
                                <th class="address">Address</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
                                                alt="Company Image">
                                        </td>
                                        <td title="<?php echo $company['company_name']; ?>" class="name">
                                            <?php echo $company['company_name']; ?>
                                        </td>
                                        <td title="<?php echo $company['company_rep_firstname'] . ' ' . $company['company_rep_middle'] . '.' . ' ' . $company['company_rep_lastname']; ?>"
                                            class="name">
                                            <?php echo $company['company_rep_firstname'] . ' ' . $company['company_rep_middle'] . '.' . ' ' . $company['company_rep_lastname']; ?>
                                        </td>
                                        <td title="<?php echo $company['company_email']; ?>" class="email">
                                            <?php echo $company['company_email']; ?>
                                        </td>
                                        <td class="cnumber"><?php echo $company['company_number']; ?></td>
                                        <td class="address"><?php echo $company['company_address']; ?></td>
                                        <!-- Display the full address -->
                                        <td class="action">
                                            <a href="./company/company-intern.php?company_id=<?php echo $company['company_id']; ?>"
                                                class="action-icon delete-btn">
                                                <i class='fa-solid fa-users'></i>
                                            </a>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No companies found</td>
                                    <!-- Adjusted column span to match the number of columns -->
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>

                    <!-- Display pagination links -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $search_query); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
    </section>
    <style>
        .whole-box {
            width: 100%;
            padding: 20px;
            padding-left: 30px;
            padding-right: 30px;
            height: 600px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }

        .whole-box tbody {
            display: block;
            max-height: 420px;
        }

        .input-group-company {
            display: flex;
            flex-direction: column;
        }

        .input-group-company {
            width: 300px;
        }
    </style>
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
    <script src="./js/scripts.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>