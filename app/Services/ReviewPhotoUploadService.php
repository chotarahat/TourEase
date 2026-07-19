<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ReviewPhotoUploadService
 *
 * Owner: MD. Neamatullah Rahat
 * Feature: Review & Rating System
 *
 * Responsibility: Handles all file storage logic for review photos —
 * uploading, generating safe unique filenames, and deleting. Kept out
 * of ReviewController entirely, matching the Services/ pattern already
 * used for external API integrations elsewhere in the project (this
 * one just wraps Laravel's Storage facade instead of an external API).
 */
class ReviewPhotoUploadService
{
    /**
     * Storage disk to use. 'public' disk points to storage/app/public,
     * symlinked to public/storage — matches the public/uploads/reviews/
     * folder structure planned in Step 1.
     */
    protected string $disk = 'public';

    /**
     * Subfolder within the disk where review photos are stored.
     */
    protected string $folder = 'uploads/reviews';

    /**
     * Upload multiple photo files and return their stored filenames.
     *
     * @param  UploadedFile[]  $files
     * @return string[]  Filenames only (not full paths) — matches what
     *                    Review::images stores and what _review-card.blade.php
     *                    expects when building asset() URLs.
     */
    public function uploadMany(array $files): array
    {
        $storedFilenames = [];

        foreach ($files as $file) {
            $storedFilenames[] = $this->uploadOne($file);
        }

        return $storedFilenames;
    }

    /**
     * Upload a single photo and return its generated filename.
     *
     * Generates a random, collision-safe filename rather than trusting
     * the original filename — never trust user-supplied filenames
     * (path traversal / overwrite risk, and duplicate name collisions).
     */
    public function uploadOne(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->storeAs($this->folder, $filename, $this->disk);

        return $filename;
    }

    /**
     * Delete multiple previously uploaded photos (used when a review
     * is deleted, or when photos are replaced during an update).
     *
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