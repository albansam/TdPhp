<?php
class Model
{
    protected $pdo;
    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }
    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }
        return $query;
    }
    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($title, $author, $synopsis, $image));
        $querySelection= $this->pdo->prepare('SELECT id FROM livres ORDER BY id DESC LIMIT 1;');
        $querySelection->execute();
        while ($row = $querySelection->fetch()) {
            $id = $row['id'];
            for($i = 0; $i < $copies; $i++){
                $queryInsertion = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (?)');
                $this->execute($queryInsertion, array($id));
            }
        }
        $querySelection = null;
    }
    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');
        $this->execute($query);
        return $query->fetchAll();
    }
    /**
     * Getting specific book details
     */
    public function getDetailsBooks($bookId){
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE livres.id = ?');
        $this->execute($query,array($bookId));
        return $query->fetchAll();
    }
    /* Gets every copy of a specified book */
    public function getBookCopies($bookId){
        $query = $this->pdo->prepare('SELECT * FROM exemplaires WHERE book_id = ?');
        $this->execute($query,array($bookId));
        $copies = $query->fetchAll();
        foreach ($copies as &$copy){
            $copy['hold'] = 0;
            $querySelection = $this->pdo->prepare('SELECT COUNT(*) AS Eexists FROM emprunts WHERE exemplaire = ? AND fini = 0');
            $querySelection->execute(array($copy['id']));
            while ($row = $querySelection->fetch()) {
                if($row['Eexists'] > 0){
                    $copy['hold'] = 1;
                }
            }
        }
        return $copies;
    }
    /* Counts book copies */
    public function getCopiesNumber($bookId){
        $number = 0;
        $querySelection = $this->pdo->prepare('SELECT COUNT(*) AS numCopies FROM exemplaires WHERE book_id = ?');
        $querySelection->execute(array($bookId));
        while ($row = $querySelection->fetch()) {
            $number = $row['numCopies'];
        }
        return $number;
    }
    /* Counts available copies of a book */
    public function getHoldNumber($bookId){
            $number = 0;
            $querySelection = $this->pdo->prepare('SELECT COUNT(*) AS numCopies FROM exemplaires
                                                    WHERE book_id = ? AND id NOT IN
                                                      (SELECT emprunts.exemplaire
                                                        FROM emprunts
                                                          JOIN exemplaires ON  emprunts.exemplaire = exemplaires.id
                                                          JOIN livres ON exemplaires.book_id = livres.id
                                                          WHERE livres.id = ?)');
            $querySelection->execute(array($bookId,$bookId));
            while ($row = $querySelection->fetch()) {
                $number = $row['numCopies'];
            }
            return $number;
    }
    /* Checks if a book is already hold by someone */
    public function checkIfEmpruntExists($empruntID){
        $exists = false;
        $querySelection = $this->pdo->prepare('SELECT COUNT(*) AS Eexists FROM emprunts WHERE exemplaire = ? AND fini = 0');
        $querySelection->execute(array($empruntID));
        while ($row = $querySelection->fetch()) {
            if($row['Eexists'] > 0){
                $exists = true;
            }
        }
        return $exists;
    }
    /* Sets a new hold */
    public function setNewEmprunt($exId,$bookHolder,$dateFin){
        $query = $this->pdo->prepare('INSERT INTO emprunts (personne, exemplaire, debut, fin, fini)
            VALUES (?, ?, ?, ?, ?)');
        $bookHolder = str_replace("'","",$bookHolder);
        $bookHolder = strip_tags($bookHolder);
        $dateFin = explode('/',$dateFin);
        $dateFin = $dateFin[2] . '-' . $dateFin[1] . '-' . $dateFin[0];
        $this->execute($query, array($bookHolder, $exId, date('Y-m-d') , $dateFin, 0));
    }
    public function returnEmprunt($empruntId,$dateFin){
        $query = $this->pdo->prepare('UPDATE emprunts
                                      SET fin = ?,
                                          fini = \'1\'
                                      WHERE exemplaire = ? AND fini = 0');
        $dateFin = explode('/',$dateFin);
        $dateFin = $dateFin[2] . '-' . $dateFin[1] . '-' . $dateFin[0];
        $this->execute($query, array($dateFin, $empruntId));
    }
}
