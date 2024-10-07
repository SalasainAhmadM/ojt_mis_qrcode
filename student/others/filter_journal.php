<?php
// Capture the search query
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched journals
function getStudentJournals($database, $student_id, $search_query, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total journals (for pagination)
    $total_journals_query = "SELECT COUNT(*) AS total FROM student_journal WHERE student_id = ?";

    // Base query for fetching journals with optional search and pagination
    $journals_query = "SELECT * FROM student_journal WHERE student_id = ?";

    // Add search filter if applied
    if (!empty($search_query)) {
        $total_journals_query .= " AND (journal_name LIKE ?)";
        $journals_query .= " AND (journal_name LIKE ?)";
    }

    // Add pagination to the journals query
    $journals_query .= " ORDER BY journal_date DESC LIMIT ? OFFSET ?";

    // Prepare and execute the total journals query for pagination
    if ($stmt = $database->prepare($total_journals_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("is", $student_id, $search_query);
        } else {
            $stmt->bind_param("i", $student_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total_journals = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    // Calculate total pages
    $total_pages = ceil($total_journals / $limit);

    // Prepare and execute the journals query with pagination
    $journals = [];
    if ($stmt = $database->prepare($journals_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("issi", $student_id, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $student_id, $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $journals[] = $row;
        }
        $stmt->close();
    }

    // Return paginated data and pagination information
    return [
        'journals' => $journals,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ];
}

// Function to render pagination links with search persistence
function renderPaginationLinks($total_pages, $current_page, $search_query)
{
    $search_query_encoded = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES);

    // Display Previous button
    if ($current_page > 1) {
        echo '<a href="?page=' . ($current_page - 1) . '&search=' . $search_query_encoded . '" class="prev">Previous</a>';
    }

    // Display page numbers (only show 5 page links)
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i == $current_page ? 'active' : '';
        echo '<a href="?page=' . $i . '&search=' . $search_query_encoded . '" class="' . $active . '">' . $i . '</a>';
    }

    // Display Next button
    if ($current_page < $total_pages) {
        echo '<a href="?page=' . ($current_page + 1) . '&search=' . $search_query_encoded . '" class="next">Next</a>';
    }
}
?>