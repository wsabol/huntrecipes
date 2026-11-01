<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class MigrateLegacyUploads extends Command {
    protected $signature = 'huntrecipes:migrate-uploads {--source=} {--dry-run}';
    protected $description = 'Copy legacy recipe and profile uploads to Laravel public storage and update their database paths.';

    public function handle(): int {
        $source = realpath((string)($this->option('source') ?: public_path('assets')));
        if ($source === false || !is_dir($source)) {
            $this->error('The legacy assets source directory does not exist.');

            return self::FAILURE;
        }

        $dryRun = (bool)$this->option('dry-run');
        $failures = 0;
        $failures += $this->migrateModels(
            Recipe::where('image_filename', 'like', '/assets/images/recipes/%')->where('image_filename', '<>', '/assets/images/recipes/generic_recipe.jpg'),
            'image_filename',
            $source . '/images/recipes',
            'recipes/legacy',
            $dryRun,
        );
        $failures += $this->migrateModels(
            User::where('profile_picture', 'like', '/assets/images/users/%')->where('profile_picture', '<>', '/assets/images/users/generic_avatar.jpg'),
            'profile_picture',
            $source . '/images/users',
            'users/legacy',
            $dryRun,
        );

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function migrateModels($query, string $column, string $sourceDirectory, string $targetDirectory, bool $dryRun): int {
        $failures = 0;
        $query->orderBy('id')->chunkById(100, function ($models) use ($column, $sourceDirectory, $targetDirectory, $dryRun, &$failures): void {
            foreach ($models as $model) {
                /** @var Model $model */
                $basename = basename((string)$model->{$column});
                $source = $sourceDirectory . '/' . $basename;
                if ($basename === '' || !is_file($source)) {
                    $this->warn("Missing upload for {$model->getTable()} #{$model->getKey()}: {$basename}");
                    $failures++;

                    continue;
                }
                $target = $targetDirectory . '/' . $basename;
                $this->line(($dryRun ? '[dry-run] ' : '') . "{$source} => {$target}");
                if ($dryRun) {
                    continue;
                }
                Storage::disk('public')->put($target, file_get_contents($source));
                $model->forceFill([$column => '/storage/' . $target])->save();
            }
        });

        return $failures;
    }
}
