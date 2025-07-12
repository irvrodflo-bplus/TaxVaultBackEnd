<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService {
    public function storeBase64File(string $base64Data, string $directory = 'uploads'): string {
        $base64String = preg_replace('/^data:[^;]+;base64,/', '', $base64Data);
        
        $fileData = base64_decode($base64String);
        
        if ($fileData === false) {
            throw new \Exception('Invalid base64 data');
        }
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileData);

        $extension = $this->mimeToExtension($mimeType);
        
        if (!$extension) {
            throw new \Exception('Unsupported file type');
        }
        
        $filename = Str::random(40) . '.' . $extension;

        $disk = Storage::disk('public');

        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $success = $disk->put($directory . '/' . $filename, $fileData);

        if (!$success) {
            throw new \Exception('Failed to store file on disk');
        }

        return $directory . '/' . $filename;
    }

    public function replaceBase64File(?string $base64Data, ?string $previousPath, string $directory = 'uploads'): ?string {
        if (empty($base64Data)) {
            return $previousPath ??  null;
        }

        if (!empty($previousPath)) {
            $this->deleteFile($previousPath);
        }

        return $this->storeBase64File($base64Data, $directory);
    }

    private function mimeToExtension(string $mimeType): ?string {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
        ];
        
        return $mimeMap[$mimeType] ?? null;
    }

    public function deleteFile(string $path): bool {
        $cleanPath = str_replace('storage/', '', $path);
        return Storage::disk('public')->delete($cleanPath);
    }
}