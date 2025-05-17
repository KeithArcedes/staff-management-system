<?php
if (isset($_SESSION['flash'])) {
    foreach ($_SESSION['flash'] as $type => $message) {
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    unset($_SESSION['flash']);
}