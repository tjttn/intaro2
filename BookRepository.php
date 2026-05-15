<?php
namespace App\Repository;

use App\Model\Book;
use PDO;

class BookRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = new PDO("sqlite:" . __DIR__ . "/../../database.sqlite");
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initTable();
    }
    
    private function initTable(): void
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS books (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                author TEXT NOT NULL,
                cover_path TEXT,
                file_path TEXT,
                read_date DATE NOT NULL,
                allow_download INTEGER DEFAULT 0
            )
        ");
    }
    
    public function findAll(): array
    {
        $stmt = $this->connection->query("SELECT * FROM books ORDER BY read_date DESC");
        $books = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $books[] = $this->hydrate($data);
        }
        return $books;
    }
    
    public function findById(int $id): ?Book
    {
        $stmt = $this->connection->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }
    
    public function save(Book $book): void
    {
        if ($book->getId()) {
            $stmt = $this->connection->prepare("
                UPDATE books SET title=?, author=?, cover_path=?, file_path=?, read_date=?, allow_download=?
                WHERE id=?
            ");
            $stmt->execute([
                $book->getTitle(), 
                $book->getAuthor(), 
                $book->getCoverPath(),
                $book->getFilePath(), 
                $book->getReadDate(), 
                $book->isAllowDownload() ? 1 : 0, 
                $book->getId()
            ]);
        } else {
            $stmt = $this->connection->prepare("
                INSERT INTO books (title, author, cover_path, file_path, read_date, allow_download)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $book->getTitle(), 
                $book->getAuthor(), 
                $book->getCoverPath(),
                $book->getFilePath(), 
                $book->getReadDate(),
                $book->isAllowDownload() ? 1 : 0
            ]);
        }
    }
    
    public function delete(int $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    private function hydrate(array $data): Book
    {
        $book = new Book();
        $book->setId($data['id']);
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setCoverPath($data['cover_path']);
        $book->setFilePath($data['file_path']);
        $book->setReadDate($data['read_date']);
        $book->setAllowDownload((bool)$data['allow_download']);
        return $book;
    }
}
