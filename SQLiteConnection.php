<?php

class SQLiteConnection {
    
    // PDO instance
    private $pdo;
    
    // return an instance of the PDO object that connects to the database
    public function connect() {
        if ($this->pdo == null) {
            try {
                $this->pdo = new \PDO("sqlite:" . Config::PATH_TO_SQLITE_FILE);
            } catch (\PDOException $e) {
                print $e;
            }
        }
        return $this->pdo;
    }

}
