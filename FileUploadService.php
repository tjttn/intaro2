<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    private string $uploadDir;
    
    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../uploads';
    }
    
    public function uploadCover(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return null;
        }
        
        $allowed = ['png', 'jpg', 'jpeg'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowed)) {
            return null;
        }
        
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $year = date('Y');
        $month = date('m');
        $directory = "covers/{$year}/{$month}";
        
        $fullDir = $this->uploadDir . '/' . $directory;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        
        $file->move($fullDir, $filename);
        
        return "/{$directory}/{$filename}";
    }
    
    public function uploadBookFile(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return null;
        }
        
        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }
        
        $allowed = ['pdf', 'epub', 'fb2', 'mobi'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowed)) {
            return null;
        }
        
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $year = date('Y');
        $month = date('m');
        $week = date('W');
        $directory = "files/{$year}/{$month}/{$week}";
        
        $fullDir = $this->uploadDir . '/' . $directory;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        
        $file->move($fullDir, $filename);
        
        return "/{$directory}/{$filename}";
    }
    
    public function deleteFile(?string $path): void
    {
        if ($path && file_exists($this->uploadDir . $path)) {
            unlink($this->uploadDir . $path);
            
            // Удаляем пустые директории
            $dir = dirname($this->uploadDir . $path);
            while ($dir != $this->uploadDir) {
                if (is_dir($dir) && count(scandir($dir)) == 2) {
                    rmdir($dir);
                }
                $dir = dirname($dir);
            }
        }
    }
    
    public function getFullPath(string $path): string
    {
        return $this->uploadDir . $path;
    }
}
