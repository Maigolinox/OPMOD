<?php

if (defined('SID')) { $extra = '?' . strip_tags(SID); }
else { $extra = ''; }
header("Location: openconf.php" . $extra);
exit;

?>
