<?php
// Read all users from users.txt
function readUsers() {
    if (!file_exists('users.txt')) {
        return [];
    }

    $data = file_get_contents('users.txt');
    return $data ? unserialize($data) : [];
}

// Write all users to users.txt
function writeUsers($users) {
    file_put_contents('users.txt', serialize($users));
}
