<?php
/**
 * Get Visit Statistics API
 * Returns visit statistics for display in footer
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

try {
    $conn = getDBConnection();
    
    if ($conn === false) {
        throw new Exception('Database connection failed');
    }
    
    // Initialize default values
    $totalVisits = 0;
    $uniqueVisitors = 0;
    $visitsToday = 0;
    $visitsThisWeek = 0;
    $visitsThisMonth = 0;
    
    // Get total visits
    $result = $conn->query("SELECT COUNT(*) as total FROM weblogs");
    if ($result) {
        $totalVisits = (int)$result->fetch_assoc()['total'];
    }
    
    // Get unique visitors (by IP)
    $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM weblogs");
    if ($result) {
        $uniqueVisitors = (int)$result->fetch_assoc()['unique_visitors'];
    }
    
    // Get visits today
    $result = $conn->query("SELECT COUNT(*) as today FROM weblogs WHERE DATE(visited_at) = CURDATE()");
    if ($result) {
        $visitsToday = (int)$result->fetch_assoc()['today'];
    }
    
    // Get visits this week
    $result = $conn->query("SELECT COUNT(*) as week FROM weblogs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($result) {
        $visitsThisWeek = (int)$result->fetch_assoc()['week'];
    }
    
    // Get visits this month
    $result = $conn->query("SELECT COUNT(*) as month FROM weblogs WHERE MONTH(visited_at) = MONTH(NOW()) AND YEAR(visited_at) = YEAR(NOW())");
    if ($result) {
        $visitsThisMonth = (int)$result->fetch_assoc()['month'];
    }
    
    // Get top countries (if available)
    $topCountries = [];
    $countryResult = $conn->query("SELECT country, COUNT(*) as count FROM weblogs WHERE country != '' GROUP BY country ORDER BY count DESC LIMIT 5");
    if ($countryResult) {
        while ($row = $countryResult->fetch_assoc()) {
            $topCountries[] = $row;
        }
    }
    
    // Get top browsers
    $topBrowsers = [];
    $browserResult = $conn->query("SELECT browser, COUNT(*) as count FROM weblogs WHERE browser != 'Unknown' GROUP BY browser ORDER BY count DESC LIMIT 5");
    if ($browserResult) {
        while ($row = $browserResult->fetch_assoc()) {
            $topBrowsers[] = $row;
        }
    }
    
    // Get device types breakdown
    $deviceTypes = [];
    $deviceResult = $conn->query("SELECT device_type, COUNT(*) as count FROM weblogs GROUP BY device_type ORDER BY count DESC");
    if ($deviceResult) {
        while ($row = $deviceResult->fetch_assoc()) {
            $deviceTypes[] = $row;
        }
    }
    
    // Get recent visits (last 10)
    $recentVisits = [];
    $recentResult = $conn->query("SELECT ip_address, browser, operating_system, device_type, visited_at FROM weblogs ORDER BY visited_at DESC LIMIT 10");
    if ($recentResult) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentVisits[] = $row;
        }
    }
    
    // Send JSON response (connection will auto-close when script ends)
    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_visits' => (int)$totalVisits,
            'unique_visitors' => (int)$uniqueVisitors,
            'visits_today' => (int)$visitsToday,
            'visits_this_week' => (int)$visitsThisWeek,
            'visits_this_month' => (int)$visitsThisMonth,
            'top_countries' => $topCountries,
            'top_browsers' => $topBrowsers,
            'device_types' => $deviceTypes,
            'recent_visits' => $recentVisits
        ]
    ]);
    
    // Connection will be automatically closed when script ends
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    error_log('Get visits error: ' . $e->getMessage());
}
?>

