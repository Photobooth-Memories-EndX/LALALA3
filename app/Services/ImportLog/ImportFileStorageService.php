<?php

namespace App\Services\ImportLog;

use App\Models\ImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class ImportFileStorageService
{
    public const DISK = 'local';
    public const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /**
     * Store an import file into private local storage and return file metadata.
     *
     * @param UploadedFile|string $file UploadedFile instance or absolute file path
     * @param string $sourceType 'PTKP' or 'NON_PTKP'
     * @param string $batchUuid UUID string for the batch
     * @param string|null $originalFilename Optional override for original file name
     * @return array{file_path: string, file_disk: string, file_hash: string, file_size: int, original_filename: string}
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function storeImportFile(
        UploadedFile|string $file,
        string $sourceType,
        string $batchUuid,
        ?string $originalFilename = null
    ): array {
        // Resolve real path and original filename
        if ($file instanceof UploadedFile) {
            $realPath = $file->getRealPath();
            $originalName = $originalFilename ?: $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        } elseif (is_string($file)) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException("File not found at path: {$file}");
            }
            $realPath = $file;
            $originalName = $originalFilename ?: basename($file);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: pathinfo($file, PATHINFO_EXTENSION));
        } else {
            throw new InvalidArgumentException('File must be an instance of UploadedFile or a valid file path string.');
        }

        // Validate allowed extensions
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException(
                "Invalid file extension [{$extension}]. Allowed extensions: " . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Validate UUID format to prevent directory traversal
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $batchUuid)) {
            throw new InvalidArgumentException("Invalid batch UUID format: {$batchUuid}");
        }

        // Normalize source folder: 'ptkp' or 'non-ptkp'
        $normalizedSource = strtolower(str_replace('_', '-', $sourceType));
        if (!in_array($normalizedSource, ['ptkp', 'non-ptkp'], true)) {
            throw new InvalidArgumentException("Unsupported source type [{$sourceType}]. Must be PTKP or NON_PTKP.");
        }

        $folder = "imports/{$normalizedSource}/{$batchUuid}";
        $targetFilename = "original.{$extension}";
        $relativePath = "{$folder}/{$targetFilename}";

        // Compute hash and size from source file
        $fileHash = hash_file('sha256', $realPath);
        if ($fileHash === false) {
            throw new RuntimeException("Failed to compute SHA-256 hash for file: {$realPath}");
        }

        $fileSize = filesize($realPath);
        if ($fileSize === false) {
            throw new RuntimeException("Failed to determine file size for file: {$realPath}");
        }

        // Store into private local disk
        $stream = fopen($realPath, 'r');
        if (!$stream) {
            throw new RuntimeException("Failed to open file stream for: {$realPath}");
        }

        try {
            $saved = Storage::disk(self::DISK)->put($relativePath, $stream);
            if (!$saved) {
                throw new RuntimeException("Failed to write import file to private storage at: {$relativePath}");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'file_path'         => $relativePath,
            'file_disk'         => self::DISK,
            'file_hash'         => $fileHash,
            'file_size'         => (int) $fileSize,
            'original_filename' => $originalName,
        ];
    }

    /**
     * Check if the stored file exists in storage.
     */
    public function fileExists(ImportBatch|string $batchOrPath, ?string $disk = null): bool
    {
        $targetDisk = $disk ?: self::DISK;
        $path = $batchOrPath instanceof ImportBatch ? $batchOrPath->file_path : $batchOrPath;

        if (empty($path)) {
            return false;
        }

        if ($batchOrPath instanceof ImportBatch && !empty($batchOrPath->file_disk)) {
            $targetDisk = $batchOrPath->file_disk;
        }

        return Storage::disk($targetDisk)->exists($path);
    }

    /**
     * Delete stored file and its batch folder (cleanup/rollback).
     */
    public function deleteStoredFile(ImportBatch|string $batchOrPath, ?string $disk = null): bool
    {
        $targetDisk = $disk ?: self::DISK;
        $path = $batchOrPath instanceof ImportBatch ? $batchOrPath->file_path : $batchOrPath;

        if (empty($path)) {
            return true;
        }

        if ($batchOrPath instanceof ImportBatch && !empty($batchOrPath->file_disk)) {
            $targetDisk = $batchOrPath->file_disk;
        }

        if (Storage::disk($targetDisk)->exists($path)) {
            Storage::disk($targetDisk)->delete($path);
        }

        // Also clean up parent directory if empty
        $directory = dirname($path);
        if ($directory !== '.' && $directory !== '/' && Storage::disk($targetDisk)->exists($directory)) {
            $remainingFiles = Storage::disk($targetDisk)->files($directory);
            if (empty($remainingFiles)) {
                Storage::disk($targetDisk)->deleteDirectory($directory);
            }
        }

        return true;
    }

    /**
     * Get stream resource of the stored private Excel file.
     *
     * @return resource|null
     */
    public function getFileStream(ImportBatch $batch)
    {
        if (!$this->fileExists($batch)) {
            return null;
        }

        $disk = $batch->file_disk ?: self::DISK;
        return Storage::disk($disk)->readStream($batch->file_path);
    }

    /**
     * Get the absolute filesystem path on the local disk.
     */
    public function getAbsolutePath(ImportBatch $batch): ?string
    {
        if (!$this->fileExists($batch)) {
            return null;
        }

        $disk = $batch->file_disk ?: self::DISK;
        return Storage::disk($disk)->path($batch->file_path);
    }
}
