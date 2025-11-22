<?php
/**
 * Contact Form Processing Script for Time & Tide Education
 * Handles contact form submissions securely
 */

// Set content type for JSON response
header('Content-Type: application/json');

// Enable CORS if needed (for development)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Configuration
$config = [
    'admin_email' => 'info@timeandtide.lk',
    'from_email' => 'noreply@timeandtide.lk',
    'site_name' => 'Time & Tide Education',
    'enable_file_logging' => true,
    'log_file' => 'logs/contact_submissions.log'
];

// Validation rules
$validation_rules = [
    'first_name' => ['required' => true, 'max_length' => 50],
    'last_name' => ['required' => true, 'max_length' => 50],
    'email' => ['required' => true, 'type' => 'email'],
    'phone' => ['required' => true, 'min_length' => 8],
    'country' => ['required' => true],
    'message' => ['required' => true, 'max_length' => 1000]
];

// Sanitize and validate input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Remove all non-digit characters for validation
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($cleaned) >= 8;
}

function validateRequired($value) {
    return !empty(trim($value));
}

function validateMaxLength($value, $max) {
    return strlen($value) <= $max;
}

function validateMinLength($value, $min) {
    return strlen($value) >= $min;
}

// Process form submission
try {
    // Get and sanitize form data
    $data = [];
    $errors = [];

    foreach ($validation_rules as $field => $rules) {
        $value = isset($_POST[$field]) ? sanitizeInput($_POST[$field]) : '';
        $data[$field] = $value;

        // Validate required fields
        if (isset($rules['required']) && $rules['required'] && !validateRequired($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            continue;
        }

        // Skip other validations if field is empty and not required
        if (empty($value) && (!isset($rules['required']) || !$rules['required'])) {
            continue;
        }

        // Validate email
        if (isset($rules['type']) && $rules['type'] === 'email' && !validateEmail($value)) {
            $errors[] = 'Please enter a valid email address';
        }

        // Validate phone
        if ($field === 'phone' && !validatePhone($value)) {
            $errors[] = 'Please enter a valid phone number';
        }

        // Validate max length
        if (isset($rules['max_length']) && !validateMaxLength($value, $rules['max_length'])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is too long';
        }

        // Validate min length
        if (isset($rules['min_length']) && !validateMinLength($value, $rules['min_length'])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is too short';
        }
    }

    // Check for validation errors
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the following errors: ' . implode(', ', $errors)
        ]);
        exit;
    }

    // Map country codes to names
    $countries = [
        'uk' => 'United Kingdom',
        'canada' => 'Canada',
        'australia' => 'Australia',
        'italy' => 'Italy',
        'latvia' => 'Latvia'
    ];

    $country_name = isset($countries[$data['country']]) ? $countries[$data['country']] : $data['country'];

    // Prepare email content
    $subject = "New Contact Form Submission - {$config['site_name']}";
    
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
            .content { background: #f9fafb; padding: 30px; }
            .field { margin-bottom: 15px; }
            .field strong { display: inline-block; width: 150px; color: #1f2937; }
            .footer { background: #1f2937; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <div class='field'><strong>Name:</strong> {$data['first_name']} {$data['last_name']}</div>
                <div class='field'><strong>Email:</strong> {$data['email']}</div>
                <div class='field'><strong>Phone:</strong> {$data['phone']}</div>
                <div class='field'><strong>Preferred Country:</strong> {$country_name}</div>
                <div class='field'><strong>Message:</strong><br>{$data['message']}</div>
                <div class='field'><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</div>
                <div class='field'><strong>IP Address:</strong> " . $_SERVER['REMOTE_ADDR'] . "</div>
            </div>
            <div class='footer'>
                <p>This email was sent from the contact form on your website.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Prepare headers for HTML email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['site_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $data['email'],
        'X-Mailer: PHP/' . phpversion()
    ];

    // Send email
    $mail_sent = mail($config['admin_email'], $subject, $email_body, implode("\r\n", $headers));

    if (!$mail_sent) {
        throw new Exception('Failed to send email');
    }

    // Log submission if enabled
    if ($config['enable_file_logging']) {
        logSubmission($data, $config['log_file']);
    }

    // Send auto-reply to customer
    sendAutoReply($data, $config);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you within 24 hours.'
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Contact form error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'There was an error processing your request. Please try again or contact us directly.'
    ]);
}

// Function to log submissions
function logSubmission($data, $log_file) {
    try {
        // Create logs directory if it doesn't exist
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        ];

        $log_line = date('Y-m-d H:i:s') . " | " . json_encode($log_entry) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Failed to log submission: " . $e->getMessage());
    }
}

// Function to send auto-reply
function sendAutoReply($data, $config) {
    try {
        $subject = "Thank you for contacting {$config['site_name']}";
        
        $auto_reply_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank You for Contacting Us!</h2>
                </div>
                <div class='content'>
                    <p>Dear {$data['first_name']},</p>
                    <p>Thank you for your interest in our international education services. We have received your inquiry about studying abroad and will get back to you within 24 hours.</p>
                    <p>Our experienced counselors will review your requirements and provide you with personalized guidance for your educational journey.</p>
                    <p><strong>What happens next:</strong></p>
                    <ul>
                        <li>We'll review your inquiry within 24 hours</li>
                        <li>A counselor will contact you to discuss your goals</li>
                        <li>We'll provide personalized recommendations</li>
                        <li>Schedule a free consultation if needed</li>
                    </ul>
                    <p>If you have any urgent questions, please don't hesitate to call us at:</p>
                    <p><strong>Phone:</strong> +94 777701206 or +94 773215368</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>Time & Tide Education Team</p>
                    <p>Hill Street, Dehiwala, Sri Lanka | info@timeandtide.lk</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $config['site_name'] . ' <' . $config['from_email'] . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        mail($data['email'], $subject, $auto_reply_body, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Failed to send auto-reply: " . $e->getMessage());
    }
}
?>