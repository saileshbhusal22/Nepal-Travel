<?php
session_start();
$pub_id = session_id();
session_write_close();

session_name('nepal_admin_session');
// We need to check if setting session_id to cookie works
if (isset($_COOKIE['nepal_admin_session'])) {
    session_id($_COOKIE['nepal_admin_session']);
} else {
    // If not set, we can just let PHP generate a new one by unsetting the current one?
    // Wait, session_id('') actually forces a new ID or throws an error.
    // Let's try session_create_id()
    session_id(session_create_id());
}
session_start();
$admin_id = session_id();

echo "Public ID: $pub_id\n";
echo "Admin ID: $admin_id\n";
?>
