<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        $factory = new Factory();
        
        $this->database = $factory
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'))
            ->createDatabase();
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getSystemStatus()
    {
        try {
            return $this->database->getReference('systemStatus')->getValue();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getSlaveData()
    {
        try {
            return $this->database->getReference('all_slave_data')->getValue();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getBellStatus()
    {
        try {
            return $this->database->getReference('bell_status')->getValue();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllData()
    {
        try {
            return $this->database->getReference('/')->getValue();
        } catch (\Exception $e) {
            return null;
        }
    }
}