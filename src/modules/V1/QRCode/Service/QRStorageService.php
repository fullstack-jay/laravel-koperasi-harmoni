<?php

declare(strict_types=1);

namespace Modules\V1\QRCode\Service;

use Illuminate\Support\Facades\Storage;

final class QRStorageService
{
    public function store(string $fileName, string $content): string
    {
        Storage::disk('public')->put($fileName, $content);

        return Storage::url($fileName);
    }

    public function delete(string $path): bool
    {
        $diskPath = str_replace(Storage::url(''), '', $path);

        return Storage::disk('public')->delete($diskPath);
    }

    public function getUrl(string $fileName): string
    {
        return Storage::url($fileName);
    }
}
