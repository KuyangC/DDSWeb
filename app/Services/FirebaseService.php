<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        $serviceAccountPath = base_path(env('FIREBASE_CREDENTIALS'));
        
        $this->database = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'))
            ->createDatabase();
    }

    public function getAllData()
    {
        try {
            return $this->database->getReference('/')->getValue();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getSystemStatus()
{
    try {
        $data = $this->database->getReference('systemStatus')->getValue();
        return $this->ensureString($data);
    } catch (\Exception $e) {
        return 'NO DATA';
    }
}

public function getSlaveData()
{
    try {
        $data = $this->database->getReference('all_slave_data')->getValue();
        return $this->ensureString($data);
    } catch (\Exception $e) {
        return '000000';
    }
}

public function getBellStatus()
{
    try {
        $data = $this->database->getReference('bell_status')->getValue();
        return $this->ensureString($data);
    } catch (\Exception $e) {
        return 'OFF';
    }
}

public function getLastUpdateTime()
{
    try {
        $data = $this->database->getReference('lastUpdateTime')->getValue();
        return $this->ensureString($data);
    } catch (\Exception $e) {
        return now()->format('Y-m-d H:i:s');
    }
}

/**
 * Helper untuk ensure data string
 */
private function ensureString($data)
{
    if (is_array($data)) {
        return $data[0] ?? ''; // Ambil elemen pertama
    }
    if (is_null($data)) {
        return '';
    }
    return (string) $data;
}
}