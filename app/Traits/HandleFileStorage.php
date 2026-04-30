<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandleFileStorage
{
    public function getFileStorageColumns(): array
    {
        return ['photo'];
    }

    public function __call($method, $parameters)
    {
        if (preg_match('/^get([^;]+)ObjectAttribute$/', $method, $match)) {
            $column = Str::snake($match[1]);
            if (array_search($column, $this->getFileStorageColumns()) !== false) {
                $value = $this->{$column};

                if (!$value) {
                    return [];
                }

                // kalau cast array → multiple file
                if (is_array($value)) {
                    return collect($value)->map(function ($filePath) {
                        return [
                            'name' => basename($filePath),
                            'url' => Storage::url($filePath), // ini baru bisa handle file public
                        ];
                    })->all();
                }

                // single file
                else {
                    return [
                        "name" => basename($value),
                        "url" => Storage::url($value) // ini baru bisa handle file public
                    ];
                }
            }
        }

        return parent::__call($method, $parameters);
    }

    // OLD: Hanya bisa untuk single file
    // public function __call($method, $parameters)
    // {
    //     if (preg_match('/^get([^;]+)ObjectAttribute$/', $method, $match)) {
    //         $column = Str::snake($match[1]);
    //         if (array_search($column, $this->getFileStorageColumns()) !== false) {
    //             if (!$this->{$column}) {
    //                 return [];
    //             }

    //             return [
    //                 "name" => basename($this->{$column}),
    //                 "url" => Storage::url($this->{$column}) // ini baru bisa handle file public
    //             ];
    //         }
    //     }

    //     return parent::__call($method, $parameters);
    // }

    public static function bootHandlesFileStorage()
    {
        static::saved(function ($model) {
            Log::info("SAVED");
            $model->deleteOldFile();
        });

        static::deleting(function ($model) {
            Log::info("DELETING");
            $model->deleteAllStoredFiles();
        });
    }

    /**
     * Delete the old file if the file_name attribute is dirty.
     */

    protected function deleteOldFile()
    {
        foreach ($this->getFileStorageColumns() as $column) {
            if ($this->wasChanged($column) && $this->getOriginal($column)) {
                $oldFilePaths = $this->getOriginal($column);

                // Normalize ke array
                if (!is_array($oldFilePaths)) {
                    $oldFilePaths = [$oldFilePaths];
                }

                foreach ($oldFilePaths as $oldFilePath) {
                    if (Storage::exists($oldFilePath)) {
                        Storage::delete($oldFilePath);
                    } elseif (Storage::disk('public')->exists($oldFilePath)) {
                        Storage::disk('public')->delete($oldFilePath);
                    }
                }
            }
        }
    }
    
    // OLD: Hanya bisa untuk single file
    // protected function deleteOldFile()
    // {
    //     foreach ($this->getFileStorageColumns() as $column) {

    //         if ($this->wasChanged($column) && $this->getOriginal($column)) {
    //             $oldFilePath = $this->getOriginal($column);

    //             // Check if the old file exists before deleting
    //             if (Storage::exists($oldFilePath)) {
    //                 Storage::delete($oldFilePath);
    //             } else if (Storage::disk('public')->exists($oldFilePath)) {
    //                 Storage::disk('public')->delete($oldFilePath);
    //             }
    //         }
    //     }
    // }

    /**
     * Delete the all file.
     */

    protected function deleteAllStoredFiles()
    {
        foreach ($this->getFileStorageColumns() as $column) {
            $filePaths = $this->{$column};

            if (!$filePaths) {
                continue;
            }

            if (!is_array($filePaths)) {
                $filePaths = [$filePaths];
            }

            foreach ($filePaths as $filePath) {
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                } elseif (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
        }
    }

    // OLD: Hanya bisa untuk single file
    // protected function deleteAllStoredFiles()
    // {
    //     foreach ($this->getFileStorageColumns() as $column) {
    //         $filePath = $this->{$column};
    //         if (!$filePath) {
    //             continue;
    //         }
    //         if (Storage::exists($filePath)) {
    //             Storage::delete($filePath);
    //         } elseif (Storage::disk('public')->exists($filePath)) {
    //             Storage::disk('public')->delete($filePath);
    //         }
    //     }
    // }

    /**
     * Store and Handle file storage prefixing.
     */
    public function storeFile($file, $prefix = '', $disk = 'local')
    {
        if (!$prefix) {
            $prefix = $this->getTable();
        }
        $file_name = Str::random(10) . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs($prefix, $file_name, $disk);
        return $filePath;
    }
}
