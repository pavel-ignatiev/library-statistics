<?php

class SQLiteOperate {
    
    // PDO instance
    private $pdo;
   
    // Connect to the database 
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // create table
    public function createTable() {
        $commands = ['CREATE TABLE IF NOT EXISTS stats (
                        id INTEGER PRIMARY KEY,
                        signature TEXT NOT NULL,
                        created DATE)'];

        // execute SQL commands  
        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }
    }

    // get the table list from the database
    public function getTableList() {
        
        $stmt = $this->pdo->query("SELECT name
                                    FROM sqlite_master
                                    WHERE type = 'table'
                                    ORDER BY name");
        
        $tables = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['name'];
        }

        return $tables;
    }

    // Insert a new book signature into the table
    public function insertSignature($signature) {
        $sql = 'INSERT INTO stats(signature, created) VALUES(:signature, :created);';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':signature', $signature, PDO::PARAM_STR);
        $stmt->bindValue(':created', date('d.m.y'), PDO::PARAM_STR);
        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    // Find signature in the DB using it's ID
    public function selectNameById ($signatureID) {
        $sql = 'SELECT signature FROM stats WHERE id = :signatureID;';
        $stmt = $this->pdo->prepare($sql);;

        $stmt->execute([':signatureID' => $signatureID]);

        // query storage
        $result = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = [
                'signature' => $row['signature'],
            ];
        }

        return $result;
    }
   
    // Transform SQL query result to an array 
    private function fetchData ( $sqlStatement ) {

        $result = [];

		  while ($row = $sqlStatement->fetch(\PDO::FETCH_ASSOC)) {

			  array_push($result, $row);

		  }
        
        return $result;
    }

    // Find ID and frequency using signature
    public function selectIdByName ($signature) {
        $sql = 'SELECT * FROM stats WHERE signature = :signature;';
        $stmt = $this->pdo->prepare($sql);;

        $stmt->execute([':signature' => $signature]);
        
        return $this->fetchData( $stmt );
  	 }

    // Select the whole table
    public function selectAll () {
        $sql = 'SELECT * FROM stats;';
        $stmt = $this->pdo->prepare($sql);;

        $stmt->execute();

        return $this->fetchData( $stmt );
	 }

    // Get absolute frequencies
    public function getAbsoluteFrequency () {
		  $sql = 'SELECT signature, COUNT(signature) AS frequency FROM stats GROUP BY signature ORDER BY 2 DESC;';
        $stmt = $this->pdo->prepare($sql);;

        $stmt->execute();

        return $this->fetchData( $stmt );
	 }

}
