<?php

namespace Database\Seeders;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionRegistration;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Competition\CompetitionRegistrationService;
use App\Services\Competition\CompetitionTeamFormationService;
use App\Support\CompetitionFormat;
use App\Support\CompetitionStatus;
use Illuminate\Database\Seeder;

/**
 * Idempotent seeder untuk event dummy UAT Competition.
 *
 * - Hanya menyentuh data milik event "UAT Competition Dummy".
 * - Reuse desa/kelompok existing (KM 7, KM 10, Kariangau).
 * - Membuat 24 Person dummy "UAT Competition NN" bila belum ada.
 * - Daftarkan Person ke 5 class (satu per format); satu Person bisa ikut banyak lomba.
 * - Auto team formation untuk class team (team_vs_team, team_mass).
 * - Tidak menyentuh Regu, event lain, Person/Participation non-dummy.
 */
class UatCompetitionSeeder extends Seeder
{
    public const EVENT_NAME = 'UAT Competition Dummy';

    public function run(): void
    {
        $event = $this->ensureEvent();
        $kelompok = $this->resolveKelompok();
        [$catIndividu, $catBeregu] = $this->ensureCategories($event);
        $classes = $this->ensureClasses($event, $catIndividu, $catBeregu);

        $persons = $this->ensurePersons($kelompok);

        $regService = app(CompetitionRegistrationService::class);

        $plan = [
            'individual_heat' => ['class' => $classes['individual_heat'], 'persons' => [1, 2, 3, 4, 5]],
            'individual_mass' => ['class' => $classes['individual_mass'], 'persons' => [6, 7, 8, 9, 10]],
            'individual_vs_individual' => ['class' => $classes['individual_vs_individual'], 'persons' => [1, 2, 3, 4]],
            'team_vs_team' => ['class' => $classes['team_vs_team'], 'persons' => range(1, 18)],
            'team_mass' => ['class' => $classes['team_mass'], 'persons' => range(1, 24)],
        ];

        $registrationCounts = [];
        foreach ($plan as $key => $entry) {
            $registrationCounts[$key] = 0;
            foreach ($entry['persons'] as $index) {
                if ($this->ensureRegistration($event, $persons[$index], $entry['class'], $regService)) {
                    $registrationCounts[$key]++;
                }
            }
        }

        $formation = [];
        foreach (['team_vs_team', 'team_mass'] as $key) {
            $formation[$key] = app(CompetitionTeamFormationService::class)->formForClass(
                eventId: $event->id,
                competitionClassId: $classes[$key]->id,
            );
        }

        $this->command?->info(sprintf(
            'UAT dummy: event #%d | regs: %s | teams: %s',
            $event->id,
            json_encode($registrationCounts),
            json_encode(collect($formation)->map(fn ($f) => count($f['teams']))->all()),
        ));
    }

    private function ensureEvent(): Event
    {
        return Event::updateOrCreate(
            ['name' => self::EVENT_NAME],
            [
                'slug' => 'uat-competition-dummy',
                'event_type' => 'competition',
                'status' => 'active',
                'description' => 'Event dummy untuk UAT fitur Competition.',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
            ],
        );
    }

    /**
     * @return array<string, kelompok>
     */
    private function resolveKelompok(): array
    {
        $byName = fn (string $name) => kelompok::where('kelompok_asal', $name)->first()
            ?? kelompok::create(['kelompok_asal' => $name]);

        return [
            'A' => $byName('KM 7'),
            'B' => $byName('KM 10'),
            'C' => $byName('Kariangau'),
        ];
    }

    /**
     * @return array{CompetitionCategory, CompetitionCategory}
     */
    private function ensureCategories(Event $event): array
    {
        return [
            CompetitionCategory::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'UAT Lomba Individu'],
                ['code' => 'uat-individu', 'sort_order' => 1, 'is_active' => true],
            ),
            CompetitionCategory::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'UAT Lomba Beregu'],
                ['code' => 'uat-beregu', 'sort_order' => 2, 'is_active' => true],
            ),
        ];
    }

    /**
     * @return array<string, CompetitionClass>
     */
    private function ensureClasses(Event $event, CompetitionCategory $catIndividu, CompetitionCategory $catBeregu): array
    {
        $make = function (string $key, string $name, string $format, CompetitionCategory $category) use ($event): CompetitionClass {
            return CompetitionClass::updateOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                [
                    'competition_category_id' => $category->id,
                    'gender' => 'M',
                    'format' => $format,
                    'status' => CompetitionStatus::REGISTRATION_OPEN,
                    'sort_order' => array_search($format, CompetitionFormat::ALL, true),
                    'is_active' => true,
                ],
            );
        };

        return [
            'individual_heat' => $make('individual_heat', 'UAT Individual Heat', CompetitionFormat::INDIVIDUAL_HEAT, $catIndividu),
            'individual_mass' => $make('individual_mass', 'UAT Individual Mass', CompetitionFormat::INDIVIDUAL_MASS, $catIndividu),
            'individual_vs_individual' => $make('individual_vs_individual', 'UAT Individual vs Individual', CompetitionFormat::INDIVIDUAL_VS_INDIVIDUAL, $catIndividu),
            'team_vs_team' => $make('team_vs_team', 'UAT Team vs Team', CompetitionFormat::TEAM_VS_TEAM, $catBeregu),
            'team_mass' => $make('team_mass', 'UAT Team Mass', CompetitionFormat::TEAM_MASS, $catBeregu),
        ];
    }

    /**
     * @param  array<string, kelompok>  $kelompok
     * @return array<int, Person> keyed 1..24
     */
    private function ensurePersons(array $kelompok): array
    {
        $kelompokFor = function (int $i) use ($kelompok): int {
            if ($i <= 10) {
                return $kelompok['A']->id;
            }
            if ($i <= 18) {
                return $kelompok['B']->id;
            }

            return $kelompok['C']->id;
        };

        $persons = [];
        for ($i = 1; $i <= 24; $i++) {
            $nama = sprintf('UAT Competition %02d', $i);
            $persons[$i] = Person::updateOrCreate(
                ['nama' => $nama],
                [
                    'jenis_kelamin' => $i % 2 ? 'L' : 'P',
                    'tanggal_lahir' => '2000-01-'.str_pad((string) ($i % 28 + 1), 2, '0', STR_PAD_LEFT),
                    'desa_id' => 1,
                    'kelompok_id' => $kelompokFor($i),
                ],
            );
        }

        return $persons;
    }

    private function ensureRegistration(
        Event $event,
        Person $person,
        CompetitionClass $class,
        CompetitionRegistrationService $regService,
    ): bool {
        $participation = Participation::where('person_id', $person->id)
            ->where('event_id', $event->id)
            ->first();

        $exists = $participation !== null
            && CompetitionRegistration::where('competition_class_id', $class->id)
                ->where('participation_id', $participation->id)
                ->exists();

        if ($exists) {
            return false;
        }

        $regService->registerForPerson(
            person: $person,
            eventId: $event->id,
            competitionCategoryId: $class->competition_category_id,
            competitionClassId: $class->id,
        );

        return true;
    }
}
