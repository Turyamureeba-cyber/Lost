<?php
require_once '../config.php';
require_once '../includes/google-auth-config.php';

$action = $_GET['action'] ?? 'login'; // 'login' or 'register'

// Initialize Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
  // Handle Google callback
  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  
  if (!isset($token['error'])) {
    $client->setAccessToken($token['access_token']);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;
    
    // Check if user exists
    $user = $db->query("SELECT * FROM users WHERE email = ? OR google_id = ?", [$email, $google_id])->fetch();
    
    if ($user) {
      // Login existing user
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_role'] = $user['role'];
      
      header('Location: /business/index.php');
      exit();
    } elseif ($action === 'register') {
      // Register new user
      $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
      $password = bin2hex(random_bytes(8)); // Temporary password
      
      $db->query(
        "INSERT INTO users (username, email, password_hash, role, google_id, email_verified, provider) 
         VALUES (?, ?, ?, 'user', ?, 1, 'google')",
        [$username, $email, password_hash($password, PASSWORD_DEFAULT), $google_id]
      );
      
      $user_id = $db->lastInsertId();
      
      $_SESSION['user_id'] = $user_id;
      $_SESSION['user_email'] = $email;
      $_SESSION['user_role'] = 'user';
      
      header('Location: /business/index.php');
      exit();
    } else {
      // No account found for login
      header('Location: login.php?error=no_account');
      exit();
    }
  }
}

// Redirect to Google auth page
$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
?>