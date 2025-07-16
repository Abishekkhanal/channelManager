<?php
class Auth {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($username, $password) {
        try {
            // Check login attempts
            if ($this->isLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Account locked due to too many failed attempts. Please try again later.'
                ];
            }
            
            // Get user from database
            $user = $this->db->selectOne('users', '*', [
                'username' => $username,
                'is_active' => 1
            ]);
            
            if (!$user) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Clear failed attempts
            $this->clearFailedAttempts($username);
            
            // Create session
            $this->createSession($user);
            
            // Update last login
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
            
            // Log successful login
            Logger::logUserAction($user['id'], 'login', ['username' => $username]);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUser($user)
            ];
            
        } catch (Exception $e) {
            Logger::error("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed due to system error'
            ];
        }
    }
    
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            Logger::logUserAction($userId, 'logout');
        }
        
        // Destroy session
        session_destroy();
        
        // Clear session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }
    
    public function register($data) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'full_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }
            
            // Check if username exists
            if ($this->db->exists('users', ['username' => $data['username']])) {
                return [
                    'success' => false,
                    'message' => 'Username already exists'
                ];
            }
            
            // Check if email exists
            if ($this->db->exists('users', ['email' => $data['email']])) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }
            
            // Validate password strength
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Create user
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'full_name' => $data['full_name'],
                'role' => $data['role'] ?? 'staff',
                'hotel_id' => $data['hotel_id'] ?? null,
                'is_active' => 1
            ];
            
            $userId = $this->db->insert('users', $userData);
            
            Logger::logUserAction($userId, 'register', ['username' => $data['username'], 'email' => $data['email']]);
            
            return [
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            Logger::error("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed due to system error'
            ];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = $this->db->selectOne('users', '*', ['id' => $userId]);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $this->db->update('users', ['password_hash' => $newPasswordHash], ['id' => $userId]);
            
            Logger::logUserAction($userId, 'password_change');
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch (Exception $e) {
            Logger::error("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Password change failed due to system error'
            ];
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->selectOne('users', '*', ['id' => $_SESSION['user_id']]);
    }
    
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Simple role-based permissions
        $rolePermissions = [
            'admin' => ['*'], // Admin has all permissions
            'manager' => ['view_dashboard', 'manage_inventory', 'manage_rates', 'view_reservations', 'sync_ota'],
            'staff' => ['view_dashboard', 'view_reservations']
        ];
        
        $userRole = $user['role'];
        $permissions = $rolePermissions[$userRole] ?? [];
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            } else {
                header('Location: /admin/login.php');
                exit;
            }
        }
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            } else {
                header('Location: /admin/access-denied.php');
                exit;
            }
        }
    }
    
    private function createSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['hotel_id'] = $user['hotel_id'];
        $_SESSION['login_time'] = time();
        
        // Set session timeout
        $_SESSION['timeout'] = time() + SESSION_TIMEOUT;
    }
    
    private function isLocked($username) {
        $cacheKey = 'failed_attempts_' . $username;
        $attempts = $this->getCache($cacheKey);
        
        return $attempts >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function recordFailedAttempt($username) {
        $cacheKey = 'failed_attempts_' . $username;
        $attempts = $this->getCache($cacheKey) + 1;
        
        $this->setCache($cacheKey, $attempts, LOCKOUT_DURATION);
        
        Logger::logSecurity('failed_login_attempt', ['username' => $username, 'attempts' => $attempts]);
    }
    
    private function clearFailedAttempts($username) {
        $cacheKey = 'failed_attempts_' . $username;
        $this->deleteCache($cacheKey);
    }
    
    private function sanitizeUser($user) {
        unset($user['password_hash']);
        return $user;
    }
    
    private function getCache($key) {
        // Simple file-based cache
        $cacheFile = sys_get_temp_dir() . '/hcm_cache_' . md5($key);
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && $data['expires'] > time()) {
                return $data['value'];
            }
        }
        
        return 0;
    }
    
    private function setCache($key, $value, $ttl = 3600) {
        $cacheFile = sys_get_temp_dir() . '/hcm_cache_' . md5($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cacheFile, json_encode($data));
    }
    
    private function deleteCache($key) {
        $cacheFile = sys_get_temp_dir() . '/hcm_cache_' . md5($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
?>