<?php
class SessionManager {
    private $sessionDir;

    public function __construct($sessionDir = null) {
        if ($sessionDir === null) {
            $sessionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rangantodapp_sessions';
        }
        $this->sessionDir = $sessionDir;
        if (!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir, 0700, true);
        }
    }

    private function getSessionFilePath($sessionId) {
        return $this->sessionDir . DIRECTORY_SEPARATOR . "sess_$sessionId.json";
    }

    public function createSession($username) {
        $sessionId = bin2hex(random_bytes(16));
        $sessionData = [
            'username' => $username,
            'created_at' => time()
        ];
        $sessionFile = $this->getSessionFilePath($sessionId);
        file_put_contents($sessionFile, json_encode($sessionData));
        return $sessionId;
    }

    public function getSession($sessionId) {
        $sessionFile = $this->getSessionFilePath($sessionId);
        if (file_exists($sessionFile)) {
            $data = json_decode(file_get_contents($sessionFile), true);
            if ($data && isset($data['username'])) {
                return $data;
            }
        }
        return null;
    }

    public function destroySession($sessionId) {
        $sessionFile = $this->getSessionFilePath($sessionId);
        if (file_exists($sessionFile)) {
            unlink($sessionFile);
        }
    }
}
?>
