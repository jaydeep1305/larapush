<!-- LaraPush Push Notification Integration by Plugin -->
<script src="<?php echo esc_url($script_url); ?>"></script>
<?php if ($pwa_ios_enabled && isset($pwa_header_code) && !empty($pwa_header_code)): ?>
<link rel="manifest" href="<?php echo esc_url(home_url('/manifest.json')); ?>">
<script>
    var additionalJsCode = <?php echo wp_json_encode($pwa_header_code); ?>;
    eval(additionalJsCode);
</script>
<?php else: ?>
<?php if (isset($additional_js_code) && !empty($additional_js_code)): ?>
<script>
    var additionalJsCode = <?php echo wp_json_encode($additional_js_code); ?>;
    eval(additionalJsCode);
</script>
<?php endif; ?>
<?php endif; ?>
<!-- /.LaraPush Push Notification Integration by Plugin -->
