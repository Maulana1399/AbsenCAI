<?php

namespace App\Providers;

use App\Services\Import\Adapters\Desa\DesaImportActivityLogger;
use App\Services\Import\Adapters\Desa\DesaImportCommitter;
use App\Services\Import\Adapters\Desa\DesaImportDefinition;
use App\Services\Import\Adapters\Desa\DesaImportDuplicateDetector;
use App\Services\Import\Adapters\Desa\DesaImportNormalizer;
use App\Services\Import\Adapters\Desa\DesaImportParser;
use App\Services\Import\Adapters\Desa\DesaImportValidator;
use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\Adapters\Kelompok\KelompokImportActivityLogger;
use App\Services\Import\Adapters\Kelompok\KelompokImportCommitter;
use App\Services\Import\Adapters\Kelompok\KelompokImportDefinition;
use App\Services\Import\Adapters\Kelompok\KelompokImportDuplicateDetector;
use App\Services\Import\Adapters\Kelompok\KelompokImportNormalizer;
use App\Services\Import\Adapters\Kelompok\KelompokImportParser;
use App\Services\Import\Adapters\Kelompok\KelompokImportValidator;
use App\Services\Import\Adapters\Participation\ParticipationImportActivityLogger;
use App\Services\Import\Adapters\Participation\ParticipationImportCommitter;
use App\Services\Import\Adapters\Participation\ParticipationImportDefinition;
use App\Services\Import\Adapters\Participation\ParticipationImportDuplicateDetector;
use App\Services\Import\Adapters\Participation\ParticipationImportNormalizer;
use App\Services\Import\Adapters\Participation\ParticipationImportParser;
use App\Services\Import\Adapters\Participation\ParticipationImportValidator;
use App\Services\Import\Adapters\Person\PersonImportActivityLogger;
use App\Services\Import\Adapters\Person\PersonImportCommitter;
use App\Services\Import\Adapters\Person\PersonImportDefinition;
use App\Services\Import\Adapters\Person\PersonImportDuplicateDetector;
use App\Services\Import\Adapters\Person\PersonImportNormalizer;
use App\Services\Import\Adapters\Person\PersonImportParser;
use App\Services\Import\Adapters\Person\PersonImportValidator;
use App\Services\Import\Adapters\Peserta\PesertaImportCommitter;
use App\Services\Import\Adapters\Peserta\PesertaImportDefinition;
use App\Services\Import\Adapters\Regu\ReguImportActivityLogger;
use App\Services\Import\Adapters\Regu\ReguImportCommitter;
use App\Services\Import\Adapters\Regu\ReguImportDefinition;
use App\Services\Import\Adapters\Regu\ReguImportDuplicateDetector;
use App\Services\Import\Adapters\Regu\ReguImportNormalizer;
use App\Services\Import\Adapters\Regu\ReguImportParser;
use App\Services\Import\Adapters\Regu\ReguImportValidator;
use App\Services\Import\Contracts\ImportLogger;
use App\Services\Import\NullObjects\NullImportActivityLogger;
use App\Services\Import\NullObjects\NullImportDuplicateDetector;
use App\Services\Import\NullObjects\NullImportLogger;
use App\Services\Import\NullObjects\NullImportNormalizer;
use App\Services\Import\NullObjects\NullImportParser;
use App\Services\Import\NullObjects\NullImportValidator;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Pipeline\ImportPipeline;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use App\Services\Import\Support\FileParser;
use App\Services\Import\Support\PipelineStageRunner;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Import Framework into the container.
 *
 * - Core singletons: registry, runner, pipeline, coordinator.
 * - DI-constructible definitions for the adapters that are complete today
 *   (desa fully; kelompok/regu/peserta on NullObject collaborators).
 * - Registers those definitions into the singleton registry so future
 *   framework-based modules can resolve them without manual instantiation.
 *
 * Pengajian is intentionally NOT registered yet — its parser/validator/
 * normalizer/duplicate/logger adapters are incomplete (IF-04 will complete
 * and register it). Legacy controllers keep constructing their own local
 * registry/coordinator and are untouched by this wiring.
 */
class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImportLogger::class, NullImportLogger::class);
        $this->app->singleton(ImportRegistry::class);
        $this->app->singleton(FileParser::class);

        $this->app->singleton(ImportAdapter::class, function ($app) {
            return new ImportAdapter(
                $app->make(ImportRegistry::class),
                $app->make(ImportCoordinator::class),
            );
        });

        $this->app->singleton(PipelineStageRunner::class, function ($app) {
            return new ArrayPipelineStageRunner(
                stages: null,
                container: $app,
                logger: $app->make(ImportLogger::class),
            );
        });

        $this->app->singleton(ImportPipeline::class, function ($app) {
            return new DefaultImportPipeline($app->make(PipelineStageRunner::class));
        });

        $this->app->singleton(ImportCoordinator::class, function ($app) {
            return new ImportCoordinator(
                $app->make(ImportRegistry::class),
                $app->make(ImportPipeline::class),
            );
        });

        $this->bindDefinition('desa', DesaImportDefinition::class, [
            DesaImportParser::class,
            DesaImportValidator::class,
            DesaImportNormalizer::class,
            DesaImportDuplicateDetector::class,
            DesaImportCommitter::class,
            DesaImportActivityLogger::class,
        ]);

        $this->bindDefinition('person', PersonImportDefinition::class, [
            PersonImportParser::class,
            PersonImportValidator::class,
            PersonImportNormalizer::class,
            PersonImportDuplicateDetector::class,
            PersonImportCommitter::class,
            PersonImportActivityLogger::class,
        ]);

        $this->bindDefinition('participation', ParticipationImportDefinition::class, [
            ParticipationImportParser::class,
            ParticipationImportValidator::class,
            ParticipationImportNormalizer::class,
            ParticipationImportDuplicateDetector::class,
            ParticipationImportCommitter::class,
            ParticipationImportActivityLogger::class,
        ]);

        $this->bindDefinition('kelompok', KelompokImportDefinition::class, [
            KelompokImportParser::class,
            KelompokImportValidator::class,
            KelompokImportNormalizer::class,
            KelompokImportDuplicateDetector::class,
            KelompokImportCommitter::class,
            KelompokImportActivityLogger::class,
        ]);

        $this->bindDefinition('regu', ReguImportDefinition::class, [
            ReguImportParser::class,
            ReguImportValidator::class,
            ReguImportNormalizer::class,
            ReguImportDuplicateDetector::class,
            ReguImportCommitter::class,
            ReguImportActivityLogger::class,
        ]);

        $this->bindDefinition('peserta', PesertaImportDefinition::class, [
            NullImportParser::class,
            NullImportValidator::class,
            NullImportNormalizer::class,
            NullImportDuplicateDetector::class,
            PesertaImportCommitter::class,
            NullImportActivityLogger::class,
        ]);
    }

    public function boot(): void
    {
        $registry = $this->app->make(ImportRegistry::class);

        foreach (['desa', 'kelompok', 'regu', 'peserta', 'person', 'participation'] as $key) {
            $definition = $this->app->make('import.definition.'.$key);

            if ($definition !== null && $registry->resolve($key) === null) {
                $registry->register($definition);
            }
        }
    }

    /**
     * Bind a definition class with ordered collaborators so the definition can
     * be resolved from the container (by class name and by the
     * `import.definition.{key}` binding), then register it into the registry.
     *
     * @param  array<int, class-string>  $collaborators
     */
    private function bindDefinition(string $key, string $definitionClass, array $collaborators): void
    {
        foreach ($collaborators as $class) {
            $this->app->singleton($class);
        }

        $factory = function ($app) use ($definitionClass, $collaborators) {
            return new $definitionClass(...array_map(
                fn (string $class) => $app->make($class),
                $collaborators,
            ));
        };

        $this->app->singleton('import.definition.'.$key, $factory);
        $this->app->singleton($definitionClass, $factory);
    }
}
