<?php
/**
 * Creates experience subscription tables if missing. Safe to call multiple times.
 */
function install_experience_subscriptions_if_needed(mysqli $conn): bool {
    $check = $conn->query("SHOW TABLES LIKE 'experience_subscription_plans'");
    if ($check && $check->num_rows > 0) {
        return true;
    }

    $sql = file_get_contents(__DIR__ . '/../sql/create_experience_subscriptions.sql');
    if ($sql === false) {
        return false;
    }

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    $check2 = $conn->query("SHOW TABLES LIKE 'experience_subscription_plans'");
    return $check2 && $check2->num_rows > 0;
}
