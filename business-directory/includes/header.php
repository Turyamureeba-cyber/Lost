<?php
// includes/header.php
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Business Directory'); // Fallback value
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'Business Directory'; ?></title>
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/style.css">
</head>
<body>