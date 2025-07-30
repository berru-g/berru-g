<?php
$hash = password_hash('goldendessert', PASSWORD_DEFAULT);
echo "Hash généré : <strong>" . $hash . "</strong>";