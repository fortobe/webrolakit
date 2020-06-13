<?php
if (is_array($_GET['debug_vars']) && !empty($_GET['debug_vars'])) {
foreach ($_GET['debug_vars'] as $var) {
ea($var.':', false);
ea($$var, false);
}
ea('debug complete.');
}