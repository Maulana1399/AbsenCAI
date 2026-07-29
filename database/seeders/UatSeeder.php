<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionBracket;
use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Models\Venue;
use App\Models\desa;
use App\Models\kelompok;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UatSeeder extends Seeder
{
    private array $firstNameM = [
        'Ahmad', 'Muhammad', 'Rizky', 'Fajar', 'Dimas', 'Andi', 'Budi',
        'Rudi', 'Hendra', 'Agus', 'Doni', 'Eko', 'Firman', 'Gilang',
        'Hafidz', 'Irfan', 'Joko', 'Kurnia', 'Lukman', 'Miftah',
        'Nanda', 'Oki', 'Pratama', 'Rahmat', 'Sandi', 'Taufik',
        'Ujang', 'Wahyu', 'Yudi', 'Zainal', 'Aditya', 'Bagas',
        'Chandra', 'Denny', 'Farhan', 'Gunawan', 'Habibie', 'Indra',
        'Jefri', 'Kevin', 'Mochamad', 'Noval', 'Qori', 'Rafi',
    ];

    private array $firstNameF = [
        'Siti', 'Nurul', 'Dewi', 'Rina', 'Fitri', 'Lina', 'Maya',
        'Nina', 'Putri', 'Rani', 'Sari', 'Titin', 'Wulan', 'Yuni',
        'Aisyah', 'Bunga', 'Citra', 'Dian', 'Elok', 'Friska',
        'Gita', 'Hana', 'Indah', 'Juwita', 'Kartika', 'Lestari',
        'Mega', 'Nadia', 'Nita', 'Dwi', 'Intan', 'Ratna',
        'Vina', 'Winda', 'Zaskia', 'Olivia', 'Pratiwi', 'Ranti',
        'Septi', 'Tri', 'Umi', 'Vera', 'Wahyuningsih', 'Yuliana',
    ];

    private array $lastNames = [
        'Wijaya', 'Kusuma', 'Pratama', 'Utama', 'Santoso', 'Hidayat',
        'Nugroho', 'Saputra', 'Setiawan', 'Wibowo', 'Yulianto',
        'Rahmawati', 'Hasanah', 'Fitriani', 'Handayani',
        'Pertiwi', 'Wulandari', 'Amalia', 'Khairunnisa',
        'Susanti', 'Purnama', 'Anggraini', 'Maulana', 'Ramadhan',
        'Ardiansyah', 'Gunawan', 'Prasetyo', 'Kurniawan', 'Hermawan',
        'Lesmana', 'Susilo', 'Akbar', 'Wicaksono', 'Hermawan',
    ];

    private array $arenaNames = [
        'Arena A', 'Arena B', 'Arena C', 'Arena D',
    ];

    private array $committeeUsers = [
        ['name' => 'Admin Event', 'email' => 'event@kja.local', 'role' => Role::Admin],
        ['name' => 'Operator Lapangan', 'email' => 'lapangan@kja.local', 'role' => Role::OperatorScan],
        ['name' => 'Operator Registrasi', 'email' => 'registrasi@kja.local', 'role' => Role::OperatorRegistrasi],
        ['name' => 'Viewer', 'email' => 'viewer@kja.local', 'role' => Role::Viewer],
    ];

    private array $categoryDefs = [
        [
            'name' => 'Tanding Dewasa Putra',
            'code' => 'TDW',
            'classes' => [
                ['name' => 'Dewasa Putra -55kg', 'gender' => 'L'],
                ['name' => 'Dewasa Putra -60kg', 'gender' => 'L'],
                ['name' => 'Dewasa Putra -65kg', 'gender' => 'L'],
                ['name' => 'Dewasa Putra -70kg', 'gender' => 'L'],
            ],
        ],
        [
            'name' => 'Tanding Dewasa Putri',
            'code' => 'TDP',
            'classes' => [
                ['name' => 'Dewasa Putri -50kg', 'gender' => 'P'],
                ['name' => 'Dewasa Putri -55kg', 'gender' => 'P'],
                ['name' => 'Dewasa Putri -60kg', 'gender' => 'P'],
            ],
        ],
        [
            'name' => 'Tanding Remaja Putra',
            'code' => 'TRP',
            'classes' => [
                ['name' => 'Remaja Putra -50kg', 'gender' => 'L'],
                ['name' => 'Remaja Putra -55kg', 'gender' => 'L'],
                ['name' => 'Remaja Putra -60kg', 'gender' => 'L'],
            ],
        ],
        [
            'name' => 'Tanding Remaja Putri',
            'code' => 'TRR',
            'classes' => [
                ['name' => 'Remaja Putri -45kg', 'gender' => 'P'],
                ['name' => 'Remaja Putri -50kg', 'gender' => 'P'],
                ['name' => 'Remaja Putri -55kg', 'gender' => 'P'],
            ],
        ],
        [
            'name' => 'Seni Tunggal',
            'code' => 'STG',
            'classes' => [
                ['name' => 'Tunggal Putra', 'gender' => 'L'],
                ['name' => 'Tunggal Putri', 'gender' => 'P'],
            ],
        ],
        [
            'name' => 'Seni Ganda',
            'code' => 'SGN',
            'classes' => [
                ['name' => 'Ganda Putra', 'gender' => 'L'],
                ['name' => 'Ganda Putri', 'gender' => 'P'],
                ['name' => 'Ganda Campuran', 'gender' => 'M'],
            ],
        ],
        [
            'name' => 'Seni Regu',
            'code' => 'SRG',
            'classes' => [
                ['name' => 'Regu Putra', 'gender' => 'L'],
                ['name' => 'Regu Putri', 'gender' => 'P'],
            ],
        ],
    ];

    private array $announcements = [
        ['Pembukaan Festival Pencak Silat 2026 pukul 08.00 WITA di Arena Utama.', true, '+2 hours'],
        ['Seluruh peserta wajib registrasi ulang 30 menit sebelum bertanding.', true, '+3 hours'],
        ['Jadwal semifinal akan diumumkan setelah babak penyisihan selesai.', false, '+5 hours'],
        ['Pengumuman juara umum pada sesi penutupan pukul 17.00 WITA.', true, '+4 hours'],
        ['Seluruh official dan pelatih harap hadir di ruang rapat pukul 07.00 WITA.', true, '+1 hours'],
    ];

    private int $totalPersons = 0;
    private int $totalParticipations = 0;
    private int $totalCategories = 0;
    private int $totalClasses = 0;
    private int $totalRegistrations = 0;
    private int $totalVenues = 0;
    private int $totalSchedules = 0;
    private int $totalScheduleEntries = 0;
    private int $totalBrackets = 0;
    private int $totalBracketMatches = 0;
    private int $totalOutcomes = 0;
    private int $totalSesi = 0;
    private int $totalAttendances = 0;
    private int $totalCommitteeAssignments = 0;

    public function run(): void
    {
        $event = Event::where('slug', 'festival-pencak-silat-2026')->first();
        if ($event) {
            $this->cleanupEvent($event);
        }
        $event = $this->createEvent();
        app(ActiveEventContext::class)->set($event);

        $eventRoles = $this->createEventRoles($event);
        $this->createCommitteeUsers($event, $eventRoles);

        $desaIds = desa::pluck('id')->toArray();
        $kelompokIds = kelompok::pluck('id')->toArray();

        $personIds = $this->createPersons($desaIds, $kelompokIds);
        $participationIds = $this->createParticipations($event->id, $personIds);

        $this->createCommitteeAssignments($event->id, $personIds, $eventRoles);

        $classInfo = $this->createCompetitionStructure($event->id);

        $registrationIds = $this->createRegistrations($event->id, $participationIds, $classInfo);

        $venueIds = $this->createVenues($event->id);

        [$scheduleIds, $scheduleEntryIds] = $this->createSchedules(
            $event->id, $classInfo, $venueIds, $registrationIds
        );

        $this->createOutcomes($scheduleIds, $registrationIds);

        $this->createBrackets($classInfo, $scheduleIds, $registrationIds, $venueIds);

        $this->createSesiAbsensi($event->id);
        $this->createAttendances($event->id, $participationIds);

        $this->createAnnouncements($event->id);

        $this->outputSummary();
    }

    private function cleanupEvent(Event $event): void
    {
        CompetitionAnnouncement::where('event_id', $event->id)->delete();
        CompetitionOutcome::whereHas('competitionRegistration.competitionClass', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })->delete();
        CompetitionBracketMatch::whereIn('competition_schedule_id',
            CompetitionSchedule::whereIn('competition_class_id',
                CompetitionClass::where('event_id', $event->id)->pluck('id')
            )->pluck('id')
        )->delete();
        CompetitionBracket::whereIn('competition_class_id',
            CompetitionClass::where('event_id', $event->id)->pluck('id')
        )->delete();
        CompetitionScheduleEntry::whereIn('competition_schedule_id',
            CompetitionSchedule::whereIn('competition_class_id',
                CompetitionClass::where('event_id', $event->id)->pluck('id')
            )->pluck('id')
        )->delete();
        CompetitionSchedule::whereIn('competition_class_id',
            CompetitionClass::where('event_id', $event->id)->pluck('id')
        )->delete();
        CompetitionRegistration::whereIn('competition_class_id',
            CompetitionClass::where('event_id', $event->id)->pluck('id')
        )->delete();
        EventAttendance::where('event_id', $event->id)->delete();
        SesiAbsensi::where('event_id', $event->id)->delete();
        Venue::where('event_id', $event->id)->delete();
        EventCommitteeAssignment::where('event_id', $event->id)->delete();
        EventRole::where('event_id', $event->id)->delete();
        CompetitionClass::where('event_id', $event->id)->delete();
        CompetitionCategory::where('event_id', $event->id)->delete();
        Participation::where('event_id', $event->id)->delete();
        $event->delete();
    }

    private function createEvent(): Event
    {
        return Event::firstOrCreate(
            ['slug' => 'festival-pencak-silat-2026'],
            [
                'name' => 'Festival Pencak Silat 2026',
                'event_type' => 'competition',
                'description' => 'Festival Pencak Silat 2026 — Ajang kompetisi pencak silat antar desa se-wilayah Batam dan sekitarnya. Mempertandingkan kategori Tanding dan Seni.',
                'start_date' => Carbon::now()->subDay()->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'status' => 'active',
            ],
        );
    }

    private function createEventRoles(Event $event): array
    {
        $roles = [
            ['name' => 'Super Admin', 'code' => 'super_admin', 'scope' => 'event', 'sort_order' => 1],
            ['name' => 'Admin Event', 'code' => 'admin_event', 'scope' => 'event', 'sort_order' => 2],
            ['name' => 'Operator Lapangan', 'code' => 'operator_lapangan', 'scope' => 'venue', 'sort_order' => 3],
            ['name' => 'Operator Registrasi', 'code' => 'operator_registrasi', 'scope' => 'registration', 'sort_order' => 4],
            ['name' => 'Viewer', 'code' => 'viewer', 'scope' => 'view', 'sort_order' => 5],
        ];

        $ids = [];
        foreach ($roles as $i => $r) {
            $role = EventRole::create([
                'event_id' => $event->id,
                'name' => $r['name'],
                'code' => $r['code'],
                'scope' => $r['scope'],
                'description' => 'Event role for ' . $r['name'],
                'sort_order' => $r['sort_order'],
                'is_active' => true,
            ]);
            $ids[$r['code']] = $role->id;
        }

        return $ids;
    }

    private function createCommitteeUsers(Event $event, array $eventRoles): void
    {
        foreach ($this->committeeUsers as $cu) {
            $user = User::where('email', $cu['email'])->first();

            if ($user && $user->person_id) {
                $person = Person::find($user->person_id);
                if (!$person) {
                    $person = Person::create([
                        'nama' => $cu['name'],
                        'jenis_kelamin' => 'L',
                        'desa_id' => desa::inRandomOrder()->first()->id,
                        'kelompok_id' => kelompok::inRandomOrder()->first()->id,
                        'tanggal_lahir' => '1990-01-01',
                    ]);
                    $user->update(['person_id' => $person->id]);
                }
            } else {
                $person = Person::create([
                    'nama' => $cu['name'],
                    'jenis_kelamin' => 'L',
                    'desa_id' => desa::inRandomOrder()->first()->id,
                    'kelompok_id' => kelompok::inRandomOrder()->first()->id,
                    'tanggal_lahir' => '1990-01-01',
                ]);

                $user = User::create([
                    'name' => $cu['name'],
                    'email' => $cu['email'],
                    'password' => Hash::make('admin123'),
                    'email_verified_at' => now(),
                    'role' => $cu['role'],
                    'person_id' => $person->id,
                ]);
            }

            $participation = Participation::updateOrCreate(
                ['person_id' => $person->id, 'event_id' => $event->id],
                [
                    'participant_number' => 'KJA-' . Str::upper(Str::random(6)),
                    'attendance_code' => 'KJA-' . Str::upper(Str::random(8)),
                    'jenis_peserta' => 'Panitia',
                    'status_registrasi' => 'checked_in',
                ],
            );

            $roleCode = match ($cu['role']->value) {
                'admin' => 'admin_event',
                'operator_scan' => 'operator_lapangan',
                'operator_registrasi' => 'operator_registrasi',
                'viewer' => 'viewer',
                default => 'viewer',
            };

            EventCommitteeAssignment::updateOrCreate(
                ['event_id' => $event->id, 'person_id' => $person->id],
                [
                    'participation_id' => $participation->id,
                    'event_role_id' => $eventRoles[$roleCode] ?? $eventRoles['viewer'],
                    'assigned_at' => now(),
                ],
            );

            $this->totalCommitteeAssignments++;
        }
    }

    private function createPersons(array $desaIds, array $kelompokIds): array
    {
        $usedNames = [];
        $ids = Person::pluck('id')->toArray();
        $existingCount = count($ids);
        $targetTotal = max(0, 150 - $existingCount);

        for ($i = 0; $i < $targetTotal; $i++) {
            $gender = fake()->randomElement(['L', 'P']);
            $firstNames = $gender === 'L' ? $this->firstNameM : $this->firstNameF;

            $nama = '';
            do {
                $nama = $firstNames[array_rand($firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)];
                $attempt = 0;
                while (isset($usedNames[strtolower($nama)]) && $attempt < 20) {
                    $nama = $firstNames[array_rand($firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)];
                    $attempt++;
                }
            } while (isset($usedNames[strtolower($nama)]));

            $usedNames[strtolower($nama)] = true;

            $person = Person::create([
                'nama' => $nama,
                'jenis_kelamin' => $gender,
                'tanggal_lahir' => fake()->date('Y-m-d', '2014-01-01'),
                'desa_id' => $desaIds[array_rand($desaIds)],
                'kelompok_id' => $kelompokIds[array_rand($kelompokIds)],
            ]);

            $ids[] = $person->id;
        }

        $this->totalPersons = count($ids);
        return $ids;
    }

    private function createParticipations(int $eventId, array $personIds): array
    {
        $ids = Participation::where('event_id', $eventId)->pluck('id')->toArray();
        $existingPersonIds = Participation::where('event_id', $eventId)->pluck('person_id')->toArray();
        $counter = count($ids);

        foreach ($personIds as $personId) {
            if (in_array($personId, $existingPersonIds)) {
                continue;
            }
            $counter++;
            $participation = Participation::create([
                'person_id' => $personId,
                'event_id' => $eventId,
                'participant_number' => 'FPS' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
                'attendance_code' => 'KJA-' . Str::upper(Str::random(8)),
                'jenis_peserta' => 'Peserta',
                'status_registrasi' => fake()->randomElement(['registered', 'registered', 'registered', 'checked_in']),
            ]);
            $ids[] = $participation->id;
        }

        $this->totalParticipations = count($ids);
        return $ids;
    }

    private function createCommitteeAssignments(int $eventId, array $personIds, array $eventRoles): void
    {
        $assignments = [
            ['role' => 'super_admin', 'count' => 1],
            ['role' => 'viewer', 'count' => 2],
        ];

        $personPool = $personIds;
        $assigned = [];

        foreach ($assignments as $a) {
            for ($i = 0; $i < $a['count']; $i++) {
                if (empty($personPool)) {
                    break;
                }
                $personId = array_pop($personPool);
                $assigned[] = $personId;

                EventCommitteeAssignment::create([
                    'event_id' => $eventId,
                    'person_id' => $personId,
                    'event_role_id' => $eventRoles[$a['role']],
                    'assigned_at' => now(),
                    'notes' => 'Committee assignment for ' . $a['role'],
                ]);

                $this->totalCommitteeAssignments++;
            }
        }
    }

    private function createCompetitionStructure(int $eventId): array
    {
        $categoryIds = [];
        $classIds = [];
        $classInfo = [];
        $sortClass = 1;

        foreach ($this->categoryDefs as $i => $catDef) {
            $category = CompetitionCategory::create([
                'event_id' => $eventId,
                'name' => $catDef['name'],
                'code' => $catDef['code'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
            $categoryIds[] = $category->id;

            foreach ($catDef['classes'] as $classDef) {
                $class = CompetitionClass::create([
                    'event_id' => $eventId,
                    'competition_category_id' => $category->id,
                    'name' => $classDef['name'],
                    'gender' => $classDef['gender'],
                    'code' => $catDef['code'] . '-' . str_pad((string) $sortClass, 2, '0', STR_PAD_LEFT),
                    'sort_order' => $sortClass++,
                    'is_active' => true,
                ]);
                $classIds[] = $class->id;
                $classInfo[$class->id] = [
                    'category_id' => $category->id,
                    'gender' => $classDef['gender'],
                    'name' => $classDef['name'],
                    'cat_name' => $catDef['name'],
                ];
            }
        }

        $this->totalCategories = count($categoryIds);
        $this->totalClasses = count($classIds);

        return [
            'categoryIds' => $categoryIds,
            'classIds' => $classIds,
            'classInfo' => $classInfo,
        ];
    }

    private function createRegistrations(int $eventId, array $participationIds, array $classInfo): array
    {
        $ids = [];
        $used = [];

        $participations = Participation::whereIn('id', $participationIds)
            ->with('person')
            ->get();

        $classIds = $classInfo['classIds'];

        foreach ($participations as $participation) {
            $gender = $participation->person->jenis_kelamin;

            $eligibleClasses = [];
            foreach ($classInfo['classInfo'] as $classId => $info) {
                if ($info['gender'] === 'M' || $info['gender'] === $gender) {
                    $eligibleClasses[] = $classId;
                }
            }

            if (empty($eligibleClasses)) {
                continue;
            }

            $numRegistrations = fake()->numberBetween(1, 2);
            $selectedClasses = (array) fake()->randomElements($eligibleClasses, $numRegistrations);

            foreach ($selectedClasses as $classId) {
                $pair = $participation->id . '-' . $classId;
                if (isset($used[$pair])) {
                    continue;
                }
                $used[$pair] = true;

                $reg = CompetitionRegistration::create([
                    'participation_id' => $participation->id,
                    'competition_category_id' => $classInfo['classInfo'][$classId]['category_id'],
                    'competition_class_id' => $classId,
                    'registration_type' => 'individual',
                ]);
                $ids[] = $reg->id;
            }
        }

        $this->totalRegistrations = count($ids);
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
        $this->totalVenues = count($ids);
        return $ids;
    }

    private function createSchedules(
        int $eventId, array $classInfo, array $venueIds, array $registrationIds
    ): array {
        $scheduleIds = [];
        $scheduleEntryIds = [];
        $today = Carbon::now()->startOfDay();
        $registrationsByClass = [];

        $registrations = CompetitionRegistration::whereIn('id', $registrationIds)->get();
        foreach ($registrations as $reg) {
            $registrationsByClass[$reg->competition_class_id][] = $reg;
        }

        $scheduleIndex = 0;

        foreach ($classInfo['classIds'] as $classId) {
            $classRegs = $registrationsByClass[$classId] ?? [];
            if (count($classRegs) < 2) {
                continue;
            }

            $catName = $classInfo['classInfo'][$classId]['cat_name'] ?? '';
            $isPerformance = str_contains($catName, 'Seni');
            $usesBracket = str_contains($catName, 'Tanding');

            if ($usesBracket) {
                continue;
            }

            $numSchedules = $isPerformance
                ? min(2, intdiv(count($classRegs), 2))
                : 0;

            for ($i = 0; $i < $numSchedules; $i++) {
                $roll = fake()->numberBetween(1, 100);
                $status = match (true) {
                    $roll <= 20 => 'Scheduled',
                    $roll <= 35 => 'Ready',
                    $roll <= 45 => 'Playing',
                    $roll <= 55 => 'Waiting Result',
                    default => 'Finished',
                };

                $startHour = fake()->numberBetween(8, 17);
                $duration = fake()->numberBetween(15, 60);
                $start = (clone $today)->addDays(fake()->numberBetween(0, 2))
                    ->addHours($startHour)
                    ->addMinutes(fake()->randomElement([0, 30]));
                $end = (clone $start)->addMinutes($duration);

                $schedule = CompetitionSchedule::create([
                    'competition_class_id' => $classId,
                    'venue_id' => $venueIds[array_rand($venueIds)],
                    'start_at' => $start,
                    'end_at' => $end,
                    'status' => $status,
                    'required_participants' => 2,
                    'notes' => $isPerformance ? null : fake()->optional(0.3)->sentence(),
                    'sort_order' => ++$scheduleIndex,
                ]);
                $scheduleIds[] = $schedule->id;

                $eligibleRegs = $classRegs;
                shuffle($eligibleRegs);
                $entriesCount = min(2, count($eligibleRegs));
                $assignedRegs = array_slice($eligibleRegs, 0, $entriesCount);

                foreach ($assignedRegs as $pos => $reg) {
                    $entry = CompetitionScheduleEntry::create([
                        'competition_schedule_id' => $schedule->id,
                        'competition_registration_id' => $reg->id,
                        'order_number' => $pos + 1,
                        'corner' => $pos + 1 <= 2 ? (string) ($pos + 1) : null,
                        'notes' => null,
                    ]);
                    $scheduleEntryIds[] = $entry->id;
                }
            }
        }

        $this->totalSchedules = count($scheduleIds);
        $this->totalScheduleEntries = count($scheduleEntryIds);

        return [$scheduleIds, $scheduleEntryIds];
    }

    private function createOutcomes(array $scheduleIds, array $registrationIds): void
    {
        $finishedSchedules = CompetitionSchedule::whereIn('id', $scheduleIds)
            ->where('status', 'Finished')
            ->get();

        foreach ($finishedSchedules as $schedule) {
            $entries = CompetitionScheduleEntry::where('competition_schedule_id', $schedule->id)
                ->orderBy('order_number')
                ->get();

            $first = $entries->first();
            $last = $entries->last();

            if ($first) {
                CompetitionOutcome::create([
                    'competition_registration_id' => $first->competition_registration_id,
                    'position' => 1,
                    'status' => 'Juara 1',
                    'score' => fake()->randomFloat(2, 80, 100),
                    'remarks' => 'Pemenang',
                ]);

                $schedule->update([
                    'winner_registration_id' => $first->competition_registration_id,
                    'finished_at' => now(),
                ]);

                $this->totalOutcomes++;
            }

            if ($last && $last->id !== $first?->id) {
                CompetitionOutcome::create([
                    'competition_registration_id' => $last->competition_registration_id,
                    'position' => 2,
                    'status' => 'Juara 2',
                    'score' => fake()->randomFloat(2, 60, 85),
                    'remarks' => 'Runner up',
                ]);
                $this->totalOutcomes++;
            }
        }
    }

    private function createBrackets(
        array $classInfo, array $scheduleIds, array $registrationIds, array $venueIds
    ): void {
        $tandingClassIds = [];
        foreach ($this->categoryDefs as $catDef) {
            if (str_starts_with($catDef['name'], 'Tanding')) {
                foreach ($catDef['classes'] as $classDef) {
                    $class = CompetitionClass::where('name', $classDef['name'])->first();
                    if ($class) {
                        $tandingClassIds[] = $class->id;
                    }
                }
            }
        }

        $today = Carbon::now()->startOfDay();

        foreach ($tandingClassIds as $classId) {
            $classRegs = CompetitionRegistration::where('competition_class_id', $classId)
                ->whereIn('id', $registrationIds)
                ->get();

            $count = $classRegs->count();
            if ($count < 4) {
                continue;
            }

            $bracketSize = 8;
            while ($bracketSize / 2 >= $count) {
                $bracketSize = intdiv($bracketSize, 2);
            }
            if ($bracketSize < 4) {
                $bracketSize = 4;
            }

            $bracket = CompetitionBracket::create([
                'competition_class_id' => $classId,
                'name' => 'Bracket ' . ($classInfo['classInfo'][$classId]['name'] ?? ''),
                'participant_count' => min($count, $bracketSize),
                'status' => 'active',
            ]);
            $this->totalBrackets++;

            $rounds = (int) log($bracketSize, 2);
            $bracketMatchIds = [];
            $roundSchedules = [];

            for ($r = 1; $r <= $rounds; $r++) {
                $matchesInRound = intdiv($bracketSize, 2 ** $r);
                $roundSchedules[$r] = [];

                for ($p = 1; $p <= $matchesInRound; $p++) {
                    $startHour = fake()->numberBetween(8, 17);
                    $start = (clone $today)->addDays($r - 1)
                        ->addHours($startHour)
                        ->addMinutes(fake()->randomElement([0, 30]));
                    $end = (clone $start)->addMinutes(30);

                    $isLastRound = $r === $rounds;
                    $status = $isLastRound ? fake()->randomElement(['Scheduled', 'Ready', 'Finished']) : 'Finished';

                    $schedule = CompetitionSchedule::create([
                        'competition_class_id' => $classId,
                        'venue_id' => $venueIds[array_rand($venueIds)],
                        'start_at' => $start,
                        'end_at' => $end,
                        'status' => $status,
                        'required_participants' => 2,
                        'sort_order' => $this->totalSchedules + 1,
                    ]);
                    $this->totalSchedules++;
                    $scheduleIds[] = $schedule->id;

                    $roundSchedules[$r][$p] = $schedule->id;

                    $sourceMatchAId = null;
                    $sourceMatchBId = null;

                    if ($r > 1) {
                        $prevMatches = $roundSchedules[$r - 1] ?? [];
                        $aPos = ($p * 2) - 1;
                        $bPos = $p * 2;

                        if (isset($prevMatches[$aPos])) {
                            $sourceMatchAId = $bracketMatchIds[$prevMatches[$aPos]] ?? null;
                        }
                        if (isset($prevMatches[$bPos])) {
                            $sourceMatchBId = $bracketMatchIds[$prevMatches[$bPos]] ?? null;
                        }
                    }

                    $roundNumber = $rounds - $r + 1;

                    $bracketMatch = CompetitionBracketMatch::create([
                        'competition_bracket_id' => $bracket->id,
                        'competition_schedule_id' => $schedule->id,
                        'round' => $roundNumber,
                        'position' => $p,
                        'source_match_a_id' => $sourceMatchAId,
                        'source_match_b_id' => $sourceMatchBId,
                    ]);
                    $bracketMatchIds[$schedule->id] = $bracketMatch->id;
                    $this->totalBracketMatches++;

                    if ($r === 1) {
                        $regA = $classRegs->shift();
                        $regB = $classRegs->shift();

                        if ($regA) {
                            CompetitionScheduleEntry::create([
                                'competition_schedule_id' => $schedule->id,
                                'competition_registration_id' => $regA->id,
                                'order_number' => 1,
                                'corner' => '1',
                            ]);
                            $this->totalScheduleEntries++;
                        }
                        if ($regB) {
                            CompetitionScheduleEntry::create([
                                'competition_schedule_id' => $schedule->id,
                                'competition_registration_id' => $regB->id,
                                'order_number' => 2,
                                'corner' => '2',
                            ]);
                            $this->totalScheduleEntries++;
                        }

                        if ($status === 'Finished' && $regA) {
                            CompetitionOutcome::create([
                                'competition_registration_id' => $regA->id,
                                'position' => 1,
                                'status' => 'Lolos',
                                'score' => fake()->randomFloat(2, 80, 100),
                            ]);
                            $this->totalOutcomes++;

                            $schedule->update([
                                'winner_registration_id' => $regA->id,
                                'finished_at' => now(),
                            ]);
                        }
                    } else {
                        $sourceA = $sourceMatchAId
                            ? CompetitionBracketMatch::with('schedule')->find($sourceMatchAId)
                            : null;
                        $sourceB = $sourceMatchBId
                            ? CompetitionBracketMatch::with('schedule')->find($sourceMatchBId)
                            : null;

                        $winnerAId = $sourceA?->schedule?->winner_registration_id;
                        $winnerBId = $sourceB?->schedule?->winner_registration_id;

                        if ($winnerAId) {
                            CompetitionScheduleEntry::create([
                                'competition_schedule_id' => $schedule->id,
                                'competition_registration_id' => $winnerAId,
                                'order_number' => 1,
                                'corner' => 'Merah',
                                'position' => 1,
                            ]);
                            $this->totalScheduleEntries++;
                        }
                        if ($winnerBId) {
                            CompetitionScheduleEntry::create([
                                'competition_schedule_id' => $schedule->id,
                                'competition_registration_id' => $winnerBId,
                                'order_number' => 2,
                                'corner' => 'Biru',
                                'position' => 2,
                            ]);
                            $this->totalScheduleEntries++;
                        }

                        if ($status === 'Finished') {
                            $winnerId = $winnerAId ?? $winnerBId;
                            $loserId = $winnerId === $winnerAId ? $winnerBId : $winnerAId;

                            if ($winnerId) {
                                CompetitionOutcome::create([
                                    'competition_registration_id' => $winnerId,
                                    'position' => 1,
                                    'status' => $r === $rounds ? 'Juara 1' : 'Lolos',
                                    'score' => fake()->randomFloat(2, 80, 100),
                                ]);
                                $this->totalOutcomes++;

                                $schedule->update([
                                    'winner_registration_id' => $winnerId,
                                    'finished_at' => now(),
                                ]);
                            }
                            if ($loserId) {
                                CompetitionOutcome::create([
                                    'competition_registration_id' => $loserId,
                                    'position' => 2,
                                    'status' => $r === $rounds ? 'Juara 2' : 'Gugur',
                                    'score' => fake()->randomFloat(2, 60, 85),
                                ]);
                                $this->totalOutcomes++;
                            }
                        }
                    }
                }
            }
        }
    }

    private function createSesiAbsensi(int $eventId): void
    {
        $sesiNames = ['Sesi Pagi', 'Sesi Siang', 'Sesi Sore'];
        foreach ($sesiNames as $i => $name) {
            SesiAbsensi::create([
                'event_id' => $eventId,
                'nama_sesi' => $name,
                'tanggal' => Carbon::now()->addDays(intdiv($i, 2))->toDateString(),
                'aktif' => $i === 0,
            ]);
            $this->totalSesi++;
        }
    }

    private function createAttendances(int $eventId, array $participationIds): void
    {
        $sesiIds = SesiAbsensi::where('event_id', $eventId)->pluck('id')->toArray();

        foreach ($participationIds as $participationId) {
            $participation = Participation::find($participationId);
            if (!$participation) {
                continue;
            }

            $numSesi = fake()->numberBetween(1, min(2, count($sesiIds)));
            $selectedSesi = (array) fake()->randomElements($sesiIds, $numSesi);

            foreach ($selectedSesi as $sesiId) {
                $status = fake()->randomElement(['hadir', 'hadir', 'hadir', 'tidak_hadir', 'izin']);

                EventAttendance::create([
                    'participation_id' => $participationId,
                    'sesi_absensi_id' => $sesiId,
                    'event_id' => $eventId,
                    'desa_id' => $participation->person?->desa_id ?? desa::inRandomOrder()->first()->id,
                    'status' => $status,
                    'attended_at' => now(),
                    'method' => $status === 'hadir' ? fake()->randomElement(['scan', 'manual']) : 'manual',
                    'recorded_by' => User::inRandomOrder()->first()?->id,
                ]);
                $this->totalAttendances++;
            }
        }
    }

    private function createAnnouncements(int $eventId): void
    {
        foreach ($this->announcements as [$message, $isActive, $expiresMod]) {
            CompetitionAnnouncement::create([
                'event_id' => $eventId,
                'message' => $message,
                'is_active' => $isActive,
                'expires_at' => $isActive ? Carbon::parse($expiresMod) : Carbon::parse($expiresMod),
            ]);
        }
    }

    private function outputSummary(): void
    {
        $this->command->info('');
        $this->command->info('============================================');
        $this->command->info('  UAT SEEDER - DATA GENERATED');
        $this->command->info('============================================');
        $this->command->info('Event ................. 1');
        $this->command->info('Event Roles ........... 5');
        $this->command->info('Committee Assignments.. ' . $this->totalCommitteeAssignments);
        $this->command->info('Persons .............. ' . Person::count());
        $this->command->info('Participations ....... ' . Participation::where('event_id', Event::where('slug', 'festival-pencak-silat-2026')->first()?->id)->count());
        $this->command->info('Categories ........... ' . $this->totalCategories);
        $this->command->info('Classes .............. ' . $this->totalClasses);
        $this->command->info('Registrations ........ ' . $this->totalRegistrations);
        $this->command->info('Venues ............... ' . $this->totalVenues);
        $this->command->info('Schedules ............ ' . $this->totalSchedules);
        $this->command->info('Schedule Entries ..... ' . $this->totalScheduleEntries);
        $this->command->info('Brackets ............. ' . $this->totalBrackets);
        $this->command->info('Bracket Matches ...... ' . $this->totalBracketMatches);
        $this->command->info('Outcomes ............. ' . $this->totalOutcomes);
        $this->command->info('Sesi Absensi ......... ' . $this->totalSesi);
        $this->command->info('Attendances .......... ' . $this->totalAttendances);
        $this->command->info('Announcements ........ ' . CompetitionAnnouncement::count());
        $this->command->info('============================================');
        $this->command->info('');
        $this->command->info('Committee Users:');
        $this->command->info('  admin@kja.local     (Super Admin)');
        $this->command->info('  event@kja.local     (Event Admin)');
        $this->command->info('  lapangan@kja.local  (Operator Lapangan)');
        $this->command->info('  registrasi@kja.local (Operator Registrasi)');
        $this->command->info('  viewer@kja.local    (Viewer)');
        $this->command->info('  Password: admin123');
        $this->command->info('');
        $this->command->info('============================================');
    }
}
