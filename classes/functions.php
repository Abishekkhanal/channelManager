<?php
// Common functions for Hotel Channel Manager

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date)) {
        return '';
    }
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    return formatDate($datetime, $format);
}

function formatCurrency($amount, $currency = 'USD') {
    if (!is_numeric($amount)) {
        return $amount;
    }
    
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    
    return $symbol . number_format($amount, 2);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function csvResponse($data, $filename = 'export.csv') {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

function getPaginationData($total, $page, $perPage) {
    $lastPage = ceil($total / $perPage);
    $from = ($page - 1) * $perPage + 1;
    $to = min($page * $perPage, $total);
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => $lastPage,
        'from' => $from,
        'to' => $to,
        'has_previous' => $page > 1,
        'has_next' => $page < $lastPage,
        'previous_page' => $page > 1 ? $page - 1 : null,
        'next_page' => $page < $lastPage ? $page + 1 : null
    ];
}

function generatePaginationHTML($pagination, $baseUrl) {
    if ($pagination['last_page'] <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Pagination">';
    $html .= '<ul class="pagination">';
    
    // Previous page
    if ($pagination['has_previous']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['previous_page'] . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['last_page'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next page
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['next_page'] . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

function getLanguageOptions() {
    return [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português'
    ];
}

function getCurrentLanguage() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

function setLanguage($lang) {
    if (in_array($lang, SUPPORTED_LANGUAGES)) {
        $_SESSION['language'] = $lang;
    }
}

function __($key, $params = []) {
    static $translations = [];
    
    $lang = getCurrentLanguage();
    
    if (!isset($translations[$lang])) {
        $translationFile = __DIR__ . "/../lang/{$lang}.php";
        if (file_exists($translationFile)) {
            $translations[$lang] = require $translationFile;
        } else {
            $translations[$lang] = [];
        }
    }
    
    $text = $translations[$lang][$key] ?? $key;
    
    // Replace parameters
    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $text = str_replace('{' . $param . '}', $value, $text);
        }
    }
    
    return $text;
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'no_show' => 'secondary',
        'active' => 'success',
        'inactive' => 'secondary',
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning'
    ];
    
    $class = $badges[$status] ?? 'secondary';
    return '<span class="badge badge-' . $class . '">' . ucfirst($status) . '</span>';
}

function uploadFile($file, $allowedTypes = null, $maxSize = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload failed'
        ];
    }
    
    $allowedTypes = $allowedTypes ?? ALLOWED_EXTENSIONS;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileType = $file['type'];
    
    // Check file size
    if ($fileSize > $maxSize) {
        return [
            'success' => false,
            'message' => 'File size exceeds maximum allowed size'
        ];
    }
    
    // Check file extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'File type not allowed'
        ];
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '.' . $fileExtension;
    $uploadPath = UPLOAD_DIR . $newFileName;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $newFileName,
            'path' => $uploadPath
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to save uploaded file'
        ];
    }
}

function deleteFile($filename) {
    $filePath = UPLOAD_DIR . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

function sendEmail($to, $subject, $message, $from = null, $fromName = null) {
    $from = $from ?? SMTP_FROM;
    $fromName = $fromName ?? SMTP_FROM_NAME;
    
    $headers = [
        'From' => "$fromName <$from>",
        'Reply-To' => $from,
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'PHP/' . phpversion()
    ];
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }
    
    return mail($to, $subject, $message, $headerString);
}

function renderTemplate($template, $data = []) {
    $templateFile = __DIR__ . "/../templates/{$template}.php";
    
    if (!file_exists($templateFile)) {
        throw new Exception("Template not found: {$template}");
    }
    
    extract($data);
    
    ob_start();
    include $templateFile;
    return ob_get_clean();
}

function debugLog($message, $data = null) {
    if (defined('DEBUG') && DEBUG) {
        $logMessage = "[DEBUG] $message";
        if ($data !== null) {
            $logMessage .= " | Data: " . json_encode($data);
        }
        Logger::debug($logMessage);
    }
}

function getRoomTypeOptions($hotelId) {
    $db = Database::getInstance();
    $roomTypes = $db->select('room_types', 'id, name', ['hotel_id' => $hotelId]);
    
    $options = [];
    foreach ($roomTypes as $roomType) {
        $options[$roomType['id']] = $roomType['name'];
    }
    
    return $options;
}

function getOTAChannelOptions() {
    $db = Database::getInstance();
    $channels = $db->select('ota_channels', 'id, name', ['is_active' => 1]);
    
    $options = [];
    foreach ($channels as $channel) {
        $options[$channel['id']] = $channel['name'];
    }
    
    return $options;
}

function calculateNights($checkIn, $checkOut) {
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    
    return $checkInDate->diff($checkOutDate)->days;
}

function getDateRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $dates = [];
    
    while ($start <= $end) {
        $dates[] = $start->format('Y-m-d');
        $start->add(new DateInterval('P1D'));
    }
    
    return $dates;
}

function validateDateRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    return $start <= $end;
}

function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return $dayOfWeek >= 6; // Saturday = 6, Sunday = 7
}

function rateLimitCheck($key, $limit = API_RATE_LIMIT, $window = 60) {
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key);
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data && $data['expires'] > time()) {
            if ($data['count'] >= $limit) {
                return false;
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'expires' => time() + $window];
        }
    } else {
        $data = ['count' => 1, 'expires' => time() + $window];
    }
    
    file_put_contents($cacheFile, json_encode($data));
    return true;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

function getReservationStatusOptions() {
    return [
        'pending' => __('Pending'),
        'confirmed' => __('Confirmed'),
        'cancelled' => __('Cancelled'),
        'no_show' => __('No Show')
    ];
}

function getUserRoleOptions() {
    return [
        'admin' => __('Administrator'),
        'manager' => __('Manager'),
        'staff' => __('Staff')
    ];
}
?>