<?php
// includes/footer.php

// Make sure database connection and functions are available
if (!function_exists('getCategories')) {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/functions.php';
}

$categories = function_exists('getCategories') ? getCategories() : [];
?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <!-- Other footer content -->
            
            <div class="footer-col">
                <h4>Categories</h4>
                <ul>
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li>
                            <a href="search.php?category=<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><a href="search.php">View All Categories</a></li>
                </ul>
            </div>
            
            <!-- Rest of footer -->
        </div>
    </div>
</footer>