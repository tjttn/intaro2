<?php
namespace App\Model;

class Book
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $author = null;
    private ?string $coverPath = null;
    private ?string $filePath = null;
    private ?string $readDate = null;
    private bool $allowDownload = false;
    
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): void { $this->title = $title; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): void { $this->author = $author; }
    public function getCoverPath(): ?string { return $this->coverPath; }
    public function setCoverPath(?string $coverPath): void { $this->coverPath = $coverPath; }
    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(?string $filePath): void { $this->filePath = $filePath; }
    public function getReadDate(): ?string { return $this->readDate; }
    public function setReadDate(?string $readDate): void { $this->readDate = $readDate; }
    public function isAllowDownload(): bool { return $this->allowDownload; }
    public function setAllowDownload(bool $allowDownload): void { $this->allowDownload = $allowDownload; }
}
