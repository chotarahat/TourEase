<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ReviewPhotoUploadService
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 */
class ReviewPhotoUploadService
{
    protected string $disk = 'public';
    protected string $folder = 'uploads/reviews';

    /**
     * @param  UploadedFile[]  $files
     * @return string[]
     */
    public function uploadMany(array $files): array
    {
        $storedFilenames = [];

        foreach ($files as $file) {
            $storedFilenames[] = $this->uploadOne($file);
        }

        return $storedFilenames;
    }

    public function uploadOne(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->storeAs($this->folder, $filename, $this->disk);

        return $filename;
    }

    /**
     * @param  string[]|null  $filenames
     */
    public function deleteMany(?array $filenames): void
    {
        if (empty($filenames)) {
            return;
        }

        foreach ($filenames as $filename) {
            $path = $this->folder . '/' . $filename;

            if (Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }
    }
}