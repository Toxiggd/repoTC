<?php
function authenticateRequest() {
    $users = loadUsers();
    
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        sendAuthChallenge();
    }
    
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
            return true; 
        }
    }
    
    sendAuthChallenge();
}

function loadUsers() {
    $usersFile = __DIR__ . '/data/users.json';
    if (!file_exists($usersFile)) {
        ///TEST3_NIEDZIALA
        $defaultHash = password_hash('admin123', PASSWORD_BCRYPT);
        $users = [['id' => 1, 'username' => 'admin', 'password_hash' => $defaultHash]];
        file_put_contents($usersFile, json_encode($users));
        return $users;
    }
    return json_decode(file_get_contents($usersFile), true) ?? [];
}

function sendAuthChallenge() {
    header('WWW-Authenticate: Basic realm="Movie Catalog"');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
?>