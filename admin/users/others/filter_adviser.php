<?php
// Capture the selected department and search query
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched advisers
function getAdvisers($database, $selected_department, $search_query, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total advisers (for pagination)
    $total_advisers_query = "SELECT COUNT(*) AS total FROM adviser WHERE 1";
    // Base query for fetching advisers with optional search and pagination
    $advisers_query = "SELECT * FROM adviser WHERE 1";

    // Add department filter if selected
    if (!empty($selected_department)) {
        $total_advisers_query .= " AND department = ?";
        $advisers_query .= " AND department = ?";
    }

    // Add search filter if applied
    if (!empty($search_query)) {
        $total_advisers_query .= " AND (adviser_firstname LIKE ? OR adviser_middle LIKE ? OR adviser_lastname LIKE ?)";
        $advisers_query .= " AND (adviser_firstname LIKE ? OR adviser_middle LIKE ? OR adviser_lastname LIKE ?)";
    }

    // Add pagination to the advisers query
    $advisers_query .= " ORDER BY adviser_id LIMIT ? OFFSET ?";

    // Prepare and execute the total advisers query for pagination
    if ($stmt = $database->prepare($total_advisers_query)) {
        // Bind parameters based on department and search query
        if (!empty($selected_department) && !empty($search_query)) {
            $stmt->bind_param("ssss", $selected_department, $search_query, $search_query, $search_query);
        } elseif (!empty($selected_department)) {
            $stmt->bind_param("s", $selected_department);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("sss", $search_query, $search_query, $search_query);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total_advisers = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    // Calculate total pages
    $total_pages = ceil($total_advisers / $limit);

    // Prepare and execute the advisers query with pagination
    $advisers = [];
    if ($stmt = $database->prepare($advisers_query)) {
        // Bind parameters based on department, search query, and pagination
        if (!empty($selected_department) && !empty($search_query)) {
            $stmt->bind_param("ssssii", $selected_department, $search_query, $search_query, $search_query, $limit, $offset);
        } elseif (!empty($selected_department)) {
            $stmt->bind_param("sii", $selected_department, $limit, $offset);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("sssii", $search_query, $search_query, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $advisers[] = $row;
        }
        $stmt->close();
    }

    // Return paginated data and pagination information
    return [
        'advisers' => $advisers,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ];
}

// Function to render pagination links with department and search persistence
function renderPaginationLinks($total_pages, $current_page, $selected_department, $search_query)
{
    $search_query_encoded = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES);
    $department_query_encoded = htmlspecialchars($_GET['department'] ?? '', ENT_QUOTES);

    // Display Previous button
    if ($current_page > 1) {
        echo '<a href="?page=' . ($current_page - 1) . '&department=' . $department_query_encoded . '&search=' . $search_query_encoded . '" class="prev">Previous</a>';
    }

    // Display page numbers (only show 5 page links)
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i == $current_page ? 'active' : '';
        echo '<a href="?page=' . $i . '&department=' . $department_query_encoded . '&search=' . $search_query_encoded . '" class="' . $active . '">' . $i . '</a>';
    }

    // Display Next button
    if ($current_page < $total_pages) {
        echo '<a href="?page=' . ($current_page + 1) . '&department=' . $department_query_encoded . '&search=' . $search_query_encoded . '" class="next">Next</a>';
    }
}
?>