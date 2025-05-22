<?php
// Only display for logged in admin users (super admin and normal admin)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 1 || $_SESSION['user_type'] == 2)) {
    
    echo '<a href="../Admin/admin.php" class="admin-button">';
    echo '<i class="fa-solid fa-seedling"></i> Admin';
    echo '</a>';
}
?>
<!-- cvq -->