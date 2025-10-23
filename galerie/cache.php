<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
echo "🕒 " . date('H:i:s') . " - Cache désactivé";
?>