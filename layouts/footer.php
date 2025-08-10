</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src="<?php echo BASE_URL; ?>assets/js/utils.js"></script>
<?php
if (isset($page)) {
    $js_file_path = __DIR__ . '/../assets/js/' . $page . '.js';
    if (file_exists($js_file_path)) {
        echo '<script type="module" src="' . BASE_URL . 'assets/js/' . $page . '.js?v=' . time() . '"></script>';
    }
}
?>
</body>
</html>