<?php
/**
 * Contact Form Handler
 * Handles form submissions and sends emails
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$config = [
    'recipient_email' => 'riverachrisviejohn@gmail.com', // Your email
    'subject_prefix' => 'Portfolio Contact: ',
    'enable_validation' => true,
    'enable_email' => true, // Set to false for testing without sending emails
    'max_message_length' => 5000,
    'rate_limit_enabled' => true,
    'rate_limit_time' => 3600, // 1 hour in seconds
    'rate_limit_max_attempts' => 5
];

/**
 * Send JSON response
 */
function sendResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check rate limiting
 */
function checkRateLimit($config) {
    if (!$config['rate_limit_enabled']) {
        return true;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $file = sys_get_temp_dir() . '/contact_form_' . md5($ip) . '.txt';
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $time_diff = time() - $data['timestamp'];
        
        if ($time_diff < $config['rate_limit_time']) {
            if ($data['attempts'] >= $config['rate_limit_max_attempts']) {
                return false;
            }
            $data['attempts']++;
        } else {
            $data = ['timestamp' => time(), 'attempts' => 1];
        }
    } else {
        $data = ['timestamp' => time(), 'attempts' => 1];
    }
    
    file_put_contents($file, json_encode($data));
    return true;
}

/**
 * Main processing
 */
try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    // Check rate limiting
    if (!checkRateLimit($config)) {
        sendResponse(false, 'Too many requests. Please try again later.');
    }
    
    // Get and sanitize form data
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Validation
    if ($config['enable_validation']) {
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = 'Please provide a valid email address';
        }
        
        if (empty($subject) || strlen($subject) < 3) {
            $errors[] = 'Subject must be at least 3 characters long';
        }
        
        if (empty($message) || strlen($message) < 10) {
            $errors[] = 'Message must be at least 10 characters long';
        }
        
        if (strlen($message) > $config['max_message_length']) {
            $errors[] = 'Message is too long (max ' . $config['max_message_length'] . ' characters)';
        }
        
        // Check for spam patterns
        $spam_patterns = ['/\[url=/i', '/\[link=/i', '/<a href=/i'];
        foreach ($spam_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $errors[] = 'Message contains prohibited content';
                break;
            }
        }
        
        if (!empty($errors)) {
            sendResponse(false, implode('. ', $errors));
        }
    }
    
    // Prepare email
    if ($config['enable_email']) {
        $to = $config['recipient_email'];
        $email_subject = $config['subject_prefix'] . $subject;
        
        // Email body
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #6366f1; }
                .value { margin-top: 5px; padding: 10px; background: white; border-left: 3px solid #6366f1; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>From:</div>
                        <div class='value'>{$name}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'>{$email}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Subject:</div>
                        <div class='value'>{$subject}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Message:</div>
                        <div class='value'>" . nl2br($message) . "</div>
                    </div>
                    <div class='footer'>
                        <p>Sent from: {$_SERVER['REMOTE_ADDR']}</p>
                        <p>Date: " . date('Y-m-d H:i:s') . "</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Send email
        $mail_sent = mail($to, $email_subject, $email_body, implode("\r\n", $headers));
        
        if ($mail_sent) {
            // Log successful submission (optional)
            $log_file = __DIR__ . '/submissions.log';
            $log_entry = date('Y-m-d H:i:s') . " - Email sent from: {$email} ({$name})\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            sendResponse(true, 'Thank you for your message! I will get back to you soon.');
        } else {
            sendResponse(false, 'Failed to send email. Please try again later.');
        }
    } else {
        // Testing mode - don't send email
        sendResponse(true, 'Form submitted successfully (test mode - no email sent)');
    }
    
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again later.');
}
?>


