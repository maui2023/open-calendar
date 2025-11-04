<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}
?>
<style>
    /* Make sure footer stays at the bottom of the viewport */
    footer.fixed-bottom-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #fff;
        border-top: 1px solid #dee2e6;
        padding: 0.75rem 0;
        text-align: center;
        z-index: 1030; /* keeps it above most content */
    }

    /* Prevent content from being hidden behind footer */
    body {
        padding-bottom: 60px; /* Adjust based on footer height */
    }
</style>

<footer class="fixed-bottom-footer">
    <div class="container text-center">
        <small class="text-muted">Powered by Maui Sabily 2025 - <?php echo date('Y'); ?></small>
    </div>
</footer>
