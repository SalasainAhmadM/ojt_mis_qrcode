<?php
// Capture the search query
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched companies
function getCompanies($database, $search_query, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total companies (for pagination)
    $total_companies_query = "SELECT COUNT(*) AS total FROM company WHERE 1";
    // Base query for fetching companies with optional search and pagination
    $companies_query = "SELECT * FROM company WHERE 1";

    // Add search filter if applied
    if (!empty($search_query)) {
        $total_companies_query .= " AND (company_name LIKE ?)";
        $companies_query .= " AND (company_name LIKE ?)";
    }

    // Add pagination to the companies query
    $companies_query .= " ORDER BY company_id LIMIT ? OFFSET ?";

    // Prepare and execute the total companies query for pagination
    if ($stmt = $database->prepare($total_companies_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("s", $search_query);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total_companies = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    // Calculate total pages
    $total_pages = ceil($total_companies / $limit);

    // Prepare and execute the companies query with pagination
    $companies = [];
    if ($stmt = $database->prepare($companies_query)) {
        if (!empty($search_query)) {
            $stmt->bind_param("ssi", $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $companies[] = $row;
        }
        $stmt->close();
    }

    // Return paginated data and pagination information
    return [
        'companies' => $companies,
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