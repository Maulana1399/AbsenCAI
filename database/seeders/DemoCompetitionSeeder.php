<?php

namespace Database\Seeders;

use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\Venue;
use App\Models\desa;
use App\Models\kelompok;
use App\Services\Competition\CompetitionRegistrationService;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCompetitionSeeder extends Seeder
{
    private array $desaNames = [
        'Sukamaju', 'Sukamulya', 'Sukaresmi', 'Sukadana', 'Sukakarya',
        'Mekarjaya', 'Mekarsari', 'Mekarwangi', 'Mekarmulya', 'Mekarindah',
    ];

    private array $kelompokNames = [
        'Al-Ikhlas', 'Al-Hidayah', 'Al-Barokah', 'Al-Falah',
        'Nurul Huda', 'Nurul Iman', 'Nurul Yaqin', 'Nurul Falah',
    ];

    private array $firstNameM = [
        'Ahmad', 'Muhammad', 'Rizky', 'Fajar', 'Dimas', 'Andi', 'Budi',
        'Rudi', 'Hendra', 'Agus', 'Doni', 'Eko', 'Firman', 'Gilang',
        'Hafidz', 'Irfan', 'Joko', 'Kurnia', 'Lukman', 'Miftah',
        'Nanda', 'Oki', 'Pratama', 'Rahmat', 'Sandi', 'Taufik',
        'Ujang', 'Wahyu', 'Yudi', 'Zainal',
    ];

    private array $firstNameF = [
        'Siti', 'Nurul', 'Dewi', 'Rina', 'Fitri', 'Lina', 'Maya',
        'Nina', 'Putri', 'Rani', 'Sari', 'Titin', 'Wulan', 'Yuni',
        'Aisyah', 'Bunga', 'Citra', 'Dian', 'Elok', 'Friska',
        'Gita', 'Hana', 'Indah', 'Juwita', 'Kartika', 'Lestari',
        'Mega', 'Nadia', 'Nita', 'Dwi',
    ];

    private array $lastNames = [
        'Wijaya', 'Kusuma', 'Pratama', 'Utama', 'Santoso', 'Hidayat',
        'Nugroho', 'Saputra', 'Setiawan', 'Wibowo', 'Yulianto',
        'Rahmawati', 'Hasanah', 'Maryam', 'Fitriani', 'Handayani',
        'Pertiwi', 'Wulandari', 'Amalia', 'Khairunnisa',
    ];

    private array $categoryNames = [
        'Tanding', 'Seni Tunggal', 'Seni Ganda', 'Seni Regu',
        'Usia Dini', 'Pra Remaja', 'Remaja', 'Dewasa',
    ];

    private array $classNames = [
        'Tanding' => ['Tanding -45kg', 'Tanding -50kg', 'Tanding -55kg'],
        'Seni Tunggal' => ['Tunggal Putra', 'Tunggal Putri'],
        'Seni Ganda' => ['Ganda Putra', 'Ganda Putri', 'Ganda Campuran'],
        'Seni Regu' => ['Regu Putra', 'Regu Putri'],
        'Usia Dini' => ['Dini 6-8 Thn', 'Dini 9-11 Thn'],
        'Pra Remaja' => ['Pra Remaja Putra', 'Pra Remaja Putri'],
        'Remaja' => ['Remaja Putra -60kg', 'Remaja Putri -50kg'],
        'Dewasa' => ['Dewasa Putra -70kg', 'Dewasa Putri -60kg'],
    ];

    private array $arenaNames = ['Arena A', 'Arena B', 'Arena C', 'Arena D'];

    private array $announcements = [
        ['Pembukaan FOSDA 2026 akan dimulai pukul 08.00 WIB di Arena Utama.', true, '+2 hours'],
        ['Seluruh peserta wajib melakukan registrasi ulang 30 menit sebelum bertanding.', true, '+3 hours'],
        ['Jadwal semifinal akan diumumkan setelah seluruh babak penyisihan selesai.', false, '+5 hours'],
        ['Door prize bagi 10 penonton yang hadir hingga akhir acara.', false, '+8 hours'],
        ['Pengumuman juara umum akan disampaikan pada sesi penutupan pukul 17.00 WIB.', true, '+4 hours'],
    ];

    public function run(): void
    {
        $event = $this->createEvent();
        app(ActiveEventContext::class)->set($event);

        $desaIds = $this->createDesa();
        $kelompokIds = $this->createKelompok($desaIds);

        $personIds = $this->createPersons($desaIds, $kelompokIds);
        $participationIds = $this->createParticipations($event->id, $personIds);

        $categoryIds = $this->createCategories($event->id);
        $classIds = $this->createClasses($event->id, $categoryIds);

        $registrationIds = $this->createRegistrations($event->id, $participationIds, $categoryIds, $classIds);

        $venueIds = $this->createVenues($event->id);
        $scheduleIds = $this->createSchedules($event->id, $classIds, $venueIds);

        $this->createOutcomes($scheduleIds, $registrationIds);
        $this->createAnnouncements($event->id);

        $this->outputSummary();
    }

    private function createEvent(): Event
    {
        $existing = Event::where('slug', 'fosda-2026')->first();
        if ($existing) {
            $this->cleanupEvent($existing);
        }

        return Event::create([
            'name' => 'FOSDA 2026',
            'slug' => 'fosda-2026',
            'event_type' => 'competition',
            'description' => 'Festival Olahraga dan Seni Daerah 2026 — Ajang kompetisi pencak silat antar desa se-Kabupaten Sukamaju.',
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDay()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function cleanupEvent(Event $event): void
    {
        CompetitionAnnouncement::where('event_id', $event->id)->delete();
        CompetitionOutcome::whereHas('competitionRegistration', function ($q) use ($event) {
            $q->whereHas('competitionClass', fn($q2) => $q2->where('event_id', $event->id));
        })->delete();
        CompetitionSchedule::whereIn('competition_class_id', CompetitionClass::where('event_id', $event->id)->pluck('id'))->delete();
        CompetitionRegistration::whereIn('competition_class_id', CompetitionClass::where('event_id', $event->id)->pluck('id'))->delete();
        Participation::where('event_id', $event->id)->delete();
        Venue::where('event_id', $event->id)->delete();
        CompetitionClass::where('event_id', $event->id)->delete();
        CompetitionCategory::where('event_id', $event->id)->delete();
        $event->delete();
    }

    private function createDesa(): array
    {
        $ids = [];
        foreach ($this->desaNames as $name) {
            $ids[] = desa::firstOrCreate(['desa_asal' => $name])->id;
        }
        return $ids;
    }

    private function createKelompok(array $desaIds): array
    {
        $ids = [];
        foreach ($this->kelompokNames as $name) {
            $ids[] = kelompok::firstOrCreate([
                'kelompok_asal' => $name,
                'desa_id' => $desaIds[array_rand($desaIds)],
            ])->id;
        }
        return $ids;
    }

    private function createPersons(array $desaIds, array $kelompokIds): array
    {
        $existing = Person::count();
        if ($existing >= 300) {
            return Person::pluck('id')->toArray();
        }

        $ids = Person::pluck('id')->toArray();
        $needed = 300 - count($ids);

        for ($i = 0; $i < $needed; $i++) {
            $gender = fake()->randomElement(['L', 'P']);
            $firstNames = $gender === 'L' ? $this->firstNameM : $this->firstNameF;
            $nama = $firstNames[array_rand($firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)];

            $person = Person::create([
                'nama' => $nama,
                'jenis_kelamin' => $gender,
                'tanggal_lahir' => fake()->date('Y-m-d', '2014-01-01'),
                'desa_id' => $desaIds[array_rand($desaIds)],
                'kelompok_id' => $kelompokIds[array_rand($kelompokIds)],
            ]);
            $ids[] = $person->id;
        }
        return $ids;
    }

    private function createParticipations(int $eventId, array $personIds): array
    {
        $personIds = array_slice($personIds, 0, 300);
        $existing = Participation::where('event_id', $eventId)->pluck('person_id')->toArray();
        $ids = Participation::where('event_id', $eventId)->pluck('id')->toArray();

        $toCreate = array_diff($personIds, $existing);
        $counter = count($ids);

        foreach ($toCreate as $personId) {
            $counter++;
            $participation = Participation::create([
                'person_id' => $personId,
                'event_id' => $eventId,
                'participant_number' => 'FSD' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
                'attendance_code' => 'KJA-' . Str::upper(Str::random(8)),
                'jenis_peserta' => 'Peserta',
            ]);
            $ids[] = $participation->id;
        }
        return $ids;
    }

    private function createCategories(int $eventId): array
    {
        $ids = [];
        foreach ($this->categoryNames as $i => $name) {
            $cat = CompetitionCategory::create([
                'event_id' => $eventId,
                'name' => $name,
                'code' => 'CAT-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
            $ids[] = $cat->id;
        }
        return $ids;
    }

    private function createClasses(int $eventId, array $categoryIds): array
    {
        $ids = [];
        $sort = 1;

        foreach ($categoryIds as $catId) {
            $cat = CompetitionCategory::find($catId);
            $classList = $this->classNames[$cat->name] ?? [];

            foreach ($classList as $name) {
                $lower = mb_strtolower($name);
                $gender = 'M';
                if (str_contains($lower, 'putra') && !str_contains($lower, 'campuran')) {
                    $gender = 'L';
                } elseif (str_contains($lower, 'putri') && !str_contains($lower, 'campuran')) {
                    $gender = 'P';
                }

                $class = CompetitionClass::create([
                    'event_id' => $eventId,
                    'competition_category_id' => $catId,
                    'name' => $name,
                    'gender' => $gender,
                    'code' => 'CLS-' . str_pad((string) $sort, 2, '0', STR_PAD_LEFT),
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]);
                $ids[] = $class->id;
            }
        }

        return $ids;
    }

    private function createRegistrations(int $eventId, array $participationIds, array $categoryIds, array $classIds): array
    {
        $ids = [];
        $used = [];
        $target = min(220, count($participationIds));
        $selectedParticipations = fake()->randomElements($participationIds, $target);

        foreach ($selectedParticipations as $participationId) {
            $classId = $classIds[array_rand($classIds)];
            $pair = $participationId . '-' . $classId;

            if (isset($used[$pair])) {
                continue;
            }
            $used[$pair] = true;

            $class = CompetitionClass::find($classId);
            $categoryId = $class->competition_category_id;

            $reg = CompetitionRegistration::create([
                'participation_id' => $participationId,
                'competition_category_id' => $categoryId,
                'competition_class_id' => $classId,
                'registration_type' => 'individual',
            ]);
            $ids[] = $reg->id;
        }

        return $ids;
    }

    private function createVenues(int $eventId): array
    {
        $ids = [];
        foreach ($this->arenaNames as $i => $name) {
            $venue = Venue::create([
                'event_id' => $eventId,
                'name' => $name,
                'code' => 'ARN-' . chr(65 + $i),
                'location_detail' => 'Gedung Olahraga ' . $name,
                'sort_order' => $i + 1,
            ]);
            $ids[] = $venue->id;
        }
        return $ids;
    }

    private function createSchedules(int $eventId, array $classIds, array $venueIds): array
    {
        $ids = [];
        $today = Carbon::now()->startOfDay();

        for ($i = 0; $i < 80; $i++) {
            $roll = fake()->numberBetween(1, 100);
            $status = match (true) {
                $roll <= 20 => 'Scheduled',
                $roll <= 35 => 'Ready',
                $roll <= 40 => 'NowPlaying',
                default => 'Finished',
            };

            $startHour = fake()->numberBetween(8, 16);
            $duration = fake()->numberBetween(30, 120);
            $start = (clone $today)->addHours($startHour)->addMinutes(fake()->randomElement([0, 30]));
            $end = (clone $start)->addMinutes($duration);

            $schedule = CompetitionSchedule::create([
                'competition_class_id' => $classIds[array_rand($classIds)],
                'venue_id' => $venueIds[array_rand($venueIds)],
                'start_at' => $start,
                'end_at' => $end,
                'status' => $status,
                'notes' => fake()->optional(0.3)->sentence(),
                'sort_order' => $i + 1,
            ]);
            $ids[] = $schedule->id;
        }

        return $ids;
    }

    private function createOutcomes(array $scheduleIds, array $registrationIds): void
    {
        $finishedSchedules = CompetitionSchedule::whereIn('id', $scheduleIds)
            ->where('status', 'Finished')
            ->get();

        $usedRegistrations = [];

        foreach ($finishedSchedules as $schedule) {
            $classRegistrations = CompetitionRegistration::where('competition_class_id', $schedule->competition_class_id)
                ->whereIn('id', $registrationIds)
                ->get();

            $position = 1;
            foreach ($classRegistrations as $reg) {
                if (isset($usedRegistrations[$reg->id])) {
                    continue;
                }
                $usedRegistrations[$reg->id] = true;

                CompetitionOutcome::create([
                    'competition_registration_id' => $reg->id,
                    'position' => $position++,
                    'status' => fake()->randomElement(['Lolos', 'Gugur', 'Diskualifikasi', 'Tidak Hadir', null]),
                    'score' => fake()->optional(0.7)->randomFloat(2, 50, 100),
                    'remarks' => fake()->optional(0.4)->sentence(),
                ]);
            }
        }
    }

    private function createAnnouncements(int $eventId): void
    {
        foreach ($this->announcements as [$message, $isActive, $expiresMod]) {
            $expiresAt = $isActive ? Carbon::parse($expiresMod) : Carbon::parse($expiresMod);

            CompetitionAnnouncement::create([
                'event_id' => $eventId,
                'message' => $message,
                'is_active' => $isActive,
                'expires_at' => $expiresAt,
            ]);
        }
    }

    private function outputSummary(): void
    {
        $this->command->info('');
        $this->command->info('===================================');
        $this->command->info('  DEMO COMPETITION DATA GENERATED');
        $this->command->info('===================================');
        $this->command->info('Persons ................ ' . Person::count());
        $this->command->info('Participations ......... ' . Participation::count());
        $this->command->info('Categories ............. ' . CompetitionCategory::count());
        $this->command->info('Classes ............... ' . CompetitionClass::count());
        $this->command->info('Registrations .......... ' . CompetitionRegistration::count());
        $this->command->info('Venues ................. ' . Venue::count());
        $this->command->info('Schedules .............. ' . CompetitionSchedule::count());
        $this->command->info('Outcomes ............... ' . CompetitionOutcome::count());
        $this->command->info('Announcements .......... ' . CompetitionAnnouncement::count());
        $this->command->info('===================================');
    }
}
