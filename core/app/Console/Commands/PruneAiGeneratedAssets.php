<?php

namespace App\Console\Commands;

use App\Services\Ai\GeneratedImageAssetService;
use Illuminate\Console\Command;

class PruneAiGeneratedAssets extends Command
{
    protected $signature = 'ai:prune-generated-assets';
    protected $description = 'Delete expired private Smartend AI image previews';

    public function handle(GeneratedImageAssetService $assets): int
    {
        $this->info('Pruned '.$assets->prune().' expired AI image preview(s).');

        return self::SUCCESS;
    }
}
