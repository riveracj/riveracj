<?php
/**
 * Visit Logging API
 * Logs visitor information to the weblogs table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Parse user agent to get browser and OS info
 */
function parseUserAgent($userAgent) {
    $browser = 'Unknown';
    $browserVersion = '';
    $os = 'Unknown';
    $deviceType = 'Desktop';
    
    // Detect Operating System
    if (preg_match('/windows|win32|win64/i', $userAgent)) {
        $os = 'Windows';
        if (preg_match('/Windows NT 10.0/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
            $os = 'Windows 7';
        }
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $os = 'macOS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/android/i', $userAgent)) {
        $os = 'Android';
        $deviceType = 'Mobile';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $os = 'iOS';
        $deviceType = 'Mobile';
        if (preg_match('/iPad/i', $userAgent)) {
            $deviceType = 'Tablet';
        }
    }
    
    // Detect Browser
    if (preg_match('/MSIE|Trident/i', $userAgent)) {
        $browser = 'Internet Explorer';
        if (preg_match('/MSIE ([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = 'Microsoft Edge';
        if (preg_match('/Edge\/([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edge/i', $userAgent)) {
        $browser = 'Chrome';
        if (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
        if (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Safari';
        if (preg_match('/Version\/([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
        $browser = 'Opera';
        if (preg_match('/(?:Opera|OPR)\/([0-9.]+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }
    }
    
    // Detect device brand/model for mobile
    $deviceBrand = '';
    $deviceModel = '';
    if (preg_match('/android/i', $userAgent)) {
        if (preg_match('/([A-Za-z]+)\s+([A-Za-z0-9\s]+)\s+build/i', $userAgent, $matches)) {
            $deviceBrand = $matches[1];
            $deviceModel = $matches[2];
        }
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $deviceBrand = 'Apple';
        if (preg_match('/iPhone/i', $userAgent)) {
            $deviceModel = 'iPhone';
        } elseif (preg_match('/iPad/i', $userAgent)) {
            $deviceModel = 'iPad';
        } elseif (preg_match('/iPod/i', $userAgent)) {
            $deviceModel = 'iPod';
        }
    }
    
    return [
        'browser' => $browser,
        'browser_version' => $browserVersion,
        'os' => $os,
        'device_type' => $deviceType,
        'device_brand' => $deviceBrand,
        'device_model' => $deviceModel
    ];
}

/**
 * Get geolocation info (simplified - you can integrate with a geolocation API)
 */
function getLocationInfo($ip) {
    // For production, you might want to use a service like ipapi.co, ip-api.com, or MaxMind GeoIP
    // This is a basic implementation
    return [
        'country' => '',
        'city' => '',
        'timezone' => ''
    ];
}

try {
    $conn = getDBConnection();
    
    if ($conn === false) {
        throw new Exception('Database connection failed');
    }
    
    // Get visitor data
    $ipAddress = getClientIP();
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $pageUrl = isset($_GET['url']) ? $_GET['url'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    $screenResolution = isset($_GET['screen']) ? $_GET['screen'] : '';
    $language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 10) : '';
    
    // Parse user agent
    $uaInfo = parseUserAgent($userAgent);
    
    // Get location info (optional - can be enhanced with geolocation API)
    $locationInfo = getLocationInfo($ipAddress);
    
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO weblogs (
        ip_address, user_agent, browser, browser_version, operating_system,
        device_type, device_brand, device_model, screen_resolution,
        referrer, page_url, country, city, timezone, language
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "sssssssssssssss",
        $ipAddress,
        $userAgent,
        $uaInfo['browser'],
        $uaInfo['browser_version'],
        $uaInfo['os'],
        $uaInfo['device_type'],
        $uaInfo['device_brand'],
        $uaInfo['device_model'],
        $screenResolution,
        $referrer,
        $pageUrl,
        $locationInfo['country'],
        $locationInfo['city'],
        $locationInfo['timezone'],
        $language
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Send JSON response (connection will auto-close when script ends)
        echo json_encode([
            'status' => 'success',
            'message' => 'Visit logged successfully'
        ]);
    } else {
        $stmt->close();
        throw new Exception('Failed to log visit: ' . $stmt->error);
    }
    
    // Connection will be automatically closed when script ends
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    error_log('Visit logging error: ' . $e->getMessage());
}
?>

