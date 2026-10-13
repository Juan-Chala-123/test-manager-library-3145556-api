<?php

declare(strict_types=1);

namespace Library\Adapters\Outbound\Persistence;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\CategoryId;
use Library\Domain\Isbn;
use Library\Domain\Quantity;
use Library\Domain\Title;
use PDO;

/**
 * Adaptador de persistencia para libros (data-model.md §3, spec.md §3).
 */
final class BookRepositoryAdapter implements BookRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(Book $book): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO library.book (id, title, author, isbn, category_id, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        
        $stmt->execute([
            $book->id()->value(),
            $book->title()->value(),
            $book->author()->value(),
            $book->isbn()->value(),
            $book->categoryId()->value(),
            $book->totalCopies()->value(),
            $book->availableCopies()->value()
        ]);
    }

    public function findById(BookId $id): Book
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, author, isbn, category_id, total_copies, available_copies FROM library.book WHERE id = ?'
        );
        $stmt->execute([$id->value()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new \InvalidArgumentException('Libro no encontrado.');
        }
        
        return new Book(
            new BookId($row['id']),
            new Title($row['title']),
            new Author($row['author']),
            new Isbn($row['isbn']),
            new CategoryId($row['category_id']),
            new Quantity($row['total_copies']),
            new Quantity($row['available_copies'])
        );
    }

    public function search(
        ?array $categoryIds,
        ?string $searchText,
        int $page,
        int $size
    ): array {
        $offset = ($page - 1) * $size;
        
        $sql = 'SELECT id, title, author, isbn, category_id, total_copies, available_copies FROM library.book WHERE deleted_at IS NULL';
        $params = [];
        
        if ($categoryIds !== null && count($categoryIds) > 0) {
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $sql .= " AND category_id IN ($placeholders)";
            $params = array_merge($params, $categoryIds);
        }
        
        if ($searchText !== null) {
            $sql .= " AND (title ILIKE ? OR author ILIKE ?)";
            $params = array_merge($params, [$searchText, $searchText]);
        }
        
        $sql .= ' ORDER BY title LIMIT ? OFFSET ?';
        $params = array_merge($params, [$size, $offset]);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $books = [];
        foreach ($rows as $row) {
            $books[] = new Book(
                new BookId($row['id']),
                new Title($row['title']),
                new Author($row['author']),
                new Isbn($row['isbn']),
                new CategoryId($row['category_id']),
                new Quantity($row['total_copies']),
                new Quantity($row['available_copies'])
            );
        }
        
        // Obtener el total
        $countSql = 'SELECT COUNT(*) FROM library.book WHERE deleted_at IS NULL';
        $countParams = [];
        
        if ($categoryIds !== null && count($categoryIds) > 0) {
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $countSql .= " AND category_id IN ($placeholders)";
            $countParams = array_merge($countParams, $categoryIds);
        }
        
        if ($searchText !== null) {
            $countSql .= " AND (title ILIKE ? OR author ILIKE ?)";
            $countParams = array_merge($countParams, [$searchText, $searchText]);
        }
        
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        return ['items' => $books, 'total' => (int)$total];
    }

    public function update(Book $book): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE library.book SET title = ?, author = ?, category_id = ?, total_copies = ?, available_copies = ? WHERE id = ?'
        );
        
        $stmt->execute([
            $book->title()->value(),
            $book->author()->value(),
            $book->categoryId()->value(),
            $book->totalCopies()->value(),
            $book->availableCopies()->value(),
            $book->id()->value()
        ]);
    }

    public function delete(BookId $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE library.book SET deleted_at = NOW() WHERE id = ?'
        );
        
        $stmt->execute([$id->value()]);
    }

    public function isbnExists(Isbn $isbn): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM library.book WHERE isbn = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$isbn->value()]);
        return (bool)$stmt->fetchColumn();
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->pdo->prepare(
            "SELECT id, title, author, isbn, category_id, total_copies, available_copies FROM library.book WHERE id IN ($placeholders) AND deleted_at IS NULL"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $books = [];
        foreach ($rows as $row) {
            $books[] = new Book(
                new BookId($row['id']),
                new Title($row['title']),
                new Author($row['author']),
                new Isbn($row['isbn']),
                new CategoryId($row['category_id']),
                new Quantity($row['total_copies']),
                new Quantity($row['available_copies'])
            );
        }
        
        return $books;
    }

    public function updateAvailableCopies(BookId $id, Quantity $newAvailable): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE library.book SET available_copies = ? WHERE id = ?'
        );
        
        $stmt->execute([$newAvailable->value(), $id->value()]);
    }
}