<?php
require_once 'config.php';
session_start();

if (isset($_GET['code'])) {
    $token = getGoogleAccessToken($_GET['code']);
    $user_info = getGoogleUserInfo($token);
    
    if ($user_info) {
        $email = $user_info['email'];
        $name = $user_info['name'];
        $google_id = $user_info['id'];
        
        // Check if user exists by email or google_id
        $sql = "SELECT id, name, email, password, role, is_active FROM users WHERE email = ? OR google_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $google_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            // User exists, log them in
            mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $role, $is_active);
            mysqli_stmt_fetch($stmt);
            
            // Update google_id if not set
            if (empty($google_id)) {
                $update_sql = "UPDATE users SET google_id = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $google_id, $id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            if ($is_active || $role === 'employee') {
                $_SESSION['user'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                
                // Redirect based on role
                redirectBasedOnRole($role);
                exit;
            } else {
                $_SESSION['error'] = "Your employer account is pending admin approval.";
                header("Location: login.php");
                exit;
            }
        } else {
            // User doesn't exist, create new account automatically
            $role = 'employee'; // Default role for Google signups
            $is_active = 1; // Auto-activate Google users
            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random password
            
            $insert_sql = "INSERT INTO users (name, email, password, role, is_active, google_id, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssssis", $name, $email, $password, $role, $is_active, $google_id);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $new_user_id = mysqli_insert_id($conn);
                
                $_SESSION['user'] = $new_user_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                
                redirectBasedOnRole($role);
                exit;
            } else {
                $_SESSION['error'] = "Failed to create account: " . mysqli_error($conn);
                header("Location: login.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Failed to get user information from Google.";
        header("Location: login.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Google authentication failed.";
    header("Location: login.php");
    exit;
}

function redirectBasedOnRole($role) {
    if ($role == 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($role == 'employee') {
        header("Location: employee_dashboard.php");
    } elseif ($role == 'employer') {
        header("Location: employer_dashboard.php");
    } else {
        header("Location: employee_dashboard.php"); // Default fallback
    }
}

function getGoogleAccessToken($code) {
    $url = 'https://oauth2.googleapis.com/token';
    
    $data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        error_log("Google token exchange failed");
        return false;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

function getGoogleUserInfo($access_token) {
    if (!$access_token) {
        return false;
    }
    
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
    
    $response = file_get_contents($url);
    
    if ($response === FALSE) {
        error_log("Google user info fetch failed");
        return false;
    }
    
    return json_decode($response, true);
}
?>