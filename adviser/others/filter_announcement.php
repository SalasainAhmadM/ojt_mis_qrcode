<?php
// Capture the search query
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched announcements
function getAdviserAnnouncements($database, $adviser_id, $search_query, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total announcements (for pagination)
    $total_announcements_query = "SELECT COUNT(*) AS total FROM adviser_announcement WHERE adviser_id = ?";

    // Base query for fetching announcements with optional search and pagination
    $announcements_query = "SELECT * FROM adviser_announcement WHERE adviser_id = ?";

    // Add search filter if applied
    if (!empty($search_query)) {
        $total_announcements_query .= " AND (announcement_name LIKE ?)";
        $announcements_query .= " AND (announcement_name LIKE ?)";
    }

    // Add pagination to the announcements query
    $announcements_query .= " ORDER BY announcement_date DESC LIMIT ? OFFSET ?";

    // Prepare and execute the total announcements query for pagination
    if ($stmt = $database->prepare($total_announcements_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("is", $adviser_id, $search_query);
        } else {
            $stmt->bind_param("i", $adviser_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total_announcements = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    // Calculate total pages
    $total_pages = ceil($total_announcements / $limit);

    // Prepare and execute the announcements query with pagination
    $announcements = [];
    if ($stmt = $database->prepare($announcements_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("issi", $adviser_id, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $adviser_id, $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
        $stmt->close();
    }

    // Return paginated data and pagination information
    return [
        'announcements' => $announcements,
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