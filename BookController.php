<?php
namespace App\Controller;

use App\Repository\BookRepository;
use App\Model\Book;
use App\Service\FileUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BookController
{
    private BookRepository $bookRepository;
    private FileUploadService $uploadService;
    
    public function __construct()
    {
        $this->bookRepository = new BookRepository();
        $this->uploadService = new FileUploadService();
    }
    
    public function index(): Response
    {
        $books = $this->bookRepository->findAll();
        $isLoggedIn = isset($_SESSION['user_id']);
        
        $content = $this->render('books/index.php', [
            'books' => $books,
            'isLoggedIn' => $isLoggedIn,
            'username' => $_SESSION['username'] ?? null,
            'showAddButton' => true
        ]);
        
        return new Response($content);
    }
    
    public function add(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return new RedirectResponse('/login');
        }
        
        if ($request->getMethod() === 'POST') {
            $book = new Book();
            $book->setTitle($request->request->get('title'));
            $book->setAuthor($request->request->get('author'));
            $book->setReadDate($request->request->get('read_date'));
            $book->setAllowDownload($request->request->has('allow_download'));
            
            $cover = $request->files->get('cover');
            if ($cover && $cover->isValid()) {
                $path = $this->uploadService->uploadCover($cover);
                if ($path) {
                    $book->setCoverPath($path);
                }
            }
            
            $bookFile = $request->files->get('book_file');
            if ($bookFile && $bookFile->isValid()) {
                $path = $this->uploadService->uploadBookFile($bookFile);
                if ($path) {
                    $book->setFilePath($path);
                }
            }
            
            $this->bookRepository->save($book);
            return new RedirectResponse('/');
        }
        
        $content = $this->render('books/form.php', ['book' => null, 'isEdit' => false]);
        return new Response($content);
    }
    
    public function create(Request $request): Response
    {
        return $this->add($request);
    }
    
    public function edit(Request $request, int $id): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return new RedirectResponse('/login');
        }
        
        $book = $this->bookRepository->findById($id);
        if (!$book) {
            return new Response('Книга не найдена', 404);
        }
        
        if ($request->getMethod() === 'POST') {
            $oldCoverPath = $book->getCoverPath();
            $oldFilePath = $book->getFilePath();
            
            $book->setTitle($request->request->get('title'));
            $book->setAuthor($request->request->get('author'));
            $book->setReadDate($request->request->get('read_date'));
            $book->setAllowDownload($request->request->has('allow_download'));
            
            $cover = $request->files->get('cover');
            if ($cover && $cover->isValid()) {
                if ($oldCoverPath) {
                    $this->uploadService->deleteFile($oldCoverPath);
                }
                $path = $this->uploadService->uploadCover($cover);
                if ($path) {
                    $book->setCoverPath($path);
                }
            } else {
                $book->setCoverPath($oldCoverPath);
            }
            
            $bookFile = $request->files->get('book_file');
            if ($bookFile && $bookFile->isValid()) {
                if ($oldFilePath) {
                    $this->uploadService->deleteFile($oldFilePath);
                }
                $path = $this->uploadService->uploadBookFile($bookFile);
                if ($path) {
                    $book->setFilePath($path);
                }
            } else {
                $book->setFilePath($oldFilePath);
            }
            
            if ($request->request->has('delete_cover')) {
                $this->uploadService->deleteFile($book->getCoverPath());
                $book->setCoverPath(null);
            }
            
            if ($request->request->has('delete_file')) {
                $this->uploadService->deleteFile($book->getFilePath());
                $book->setFilePath(null);
            }
            
            $this->bookRepository->save($book);
            return new RedirectResponse('/');
        }
        
        $content = $this->render('books/form.php', ['book' => $book, 'isEdit' => true]);
        return new Response($content);
    }
    
    public function update(Request $request, int $id): Response
    {
        return $this->edit($request, $id);
    }
    
    public function delete(Request $request, int $id): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return new RedirectResponse('/login');
        }
        
        $book = $this->bookRepository->findById($id);
        if ($book) {
            if ($book->getCoverPath()) {
                $this->uploadService->deleteFile($book->getCoverPath());
            }
            if ($book->getFilePath()) {
                $this->uploadService->deleteFile($book->getFilePath());
            }
            $this->bookRepository->delete($id);
        }
        
        return new RedirectResponse('/');
    }
    
    public function download(Request $request, int $id): Response
    {
        $book = $this->bookRepository->findById($id);
        
        if (!$book || !$book->getFilePath() || !$book->isAllowDownload()) {
            return new Response('Файл не найден или скачивание запрещено', 404);
        }
        
        $fullPath = $this->uploadService->getFullPath($book->getFilePath());
        
        if (!file_exists($fullPath)) {
            return new Response('Файл не найден на сервере', 404);
        }
        
        $originalName = basename($fullPath);
        $originalName = preg_replace('/^[a-f0-9]+_\d+_/', '', $originalName);
        if (empty($originalName)) {
            $originalName = 'book_' . $id . '.' . pathinfo($fullPath, PATHINFO_EXTENSION);
        }
        
        return new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $originalName . '"',
            'Content-Length' => filesize($fullPath)
        ]);
    }
    
    private function render(string $template, array $params = []): string
    {
        extract($params);
        ob_start();
        require __DIR__ . '/../../templates/layout.php';
        return ob_get_clean();
    }
}
