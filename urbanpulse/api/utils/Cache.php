<?php
// api/utils/Cache.php

class Cache {
    private $cachePath;
    
    public function __construct() {
        $this->cachePath = __DIR__ . '/../../cache/';
        if (!file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function has($key) {
        $file = $this->cachePath . $key;
        return file_exists($file) && (filemtime($file) + $this->getTTL($file) > time());
    }
    
    public function get($key) {
        $file = $this->cachePath . $key;
        return unserialize(file_get_contents($file));
    }
    
    public function set($key, $data, $ttl = 300) {
        $file = $this->cachePath . $key;
        file_put_contents($file, serialize($data));
        // Store TTL in first line of file
        file_put_contents($file, $ttl . PHP_EOL, FILE_APPEND);
    }
    
    private function getTTL($file) {
        $content = file($file);
        return (int)trim($content[0]);
    }
}