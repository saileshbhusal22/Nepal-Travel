<?php
file_put_contents(__DIR__ . '/debug.log', print_r($_POST, true) . "\n\n" . print_r($_FILES, true), FILE_APPEND);
