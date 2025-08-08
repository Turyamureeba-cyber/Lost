<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // DB connection
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=urbanpulse', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Form data
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $provider = 'local';
        $avatar_url = 'profile_default.jpg';
        $email_verified = 0;

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert query
        $sql = "INSERT INTO users (username, email, password_hash, role, avatar_url, email_verified, provider)
                VALUES (:username, :email, :password_hash, :role, :avatar_url, :email_verified, :provider)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':role' => $role,
            ':avatar_url' => $avatar_url,
            ':email_verified' => $email_verified,
            ':provider' => $provider
        ]);

        echo "<p style='color: green;'>User added successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
</head>
<body>
    <h2>Add New User</h2>
    <form method="POST" action="">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="user">User</option>
            <option value="business_owner">Business Owner</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <button type="submit">Add User</button>
    </form>
</body>
</html>
