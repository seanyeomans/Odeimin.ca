<?php
if (!isset($contentFile) || !is_file($contentFile)) {
    http_response_code(500);
    echo '<p class="empty">Page content missing.</p>';
    return;
}

require $contentFile;
