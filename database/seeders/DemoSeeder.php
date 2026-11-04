<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Mail\BookingConfirmed;
use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Fills the database with a fictional salon: team, services, clients, a few months of
 * appointments around "today", reviews and activity. Deterministic apart from the dates,
 * and safe to re-run: demo accounts are kept so signed-in visitors stay signed in.
 */
class DemoSeeder extends Seeder
{
    private Carbon $today;

    /** @var array<int, array<string, array<int, array{0:int,1:int}>>> staff id => date => busy minute ranges */
    private array $busy = [];

    /** @var array<int, array<int, array{0:int,1:int,2:?int,3:?int}>> staff id => weekday => [start, end, breakStart, breakEnd] */
    private array $schedule = [];

    /** @var array<int, array{0:int,1:int}> weekday => [opens, closes] in minutes */
    private array $hours = [];

    private array $closed = [];

    private array $leave = [];

    private array $rows = [];

    private array $refs = [];

    private array $services = [];

    public function run(): void
    {
        mt_srand(2026);
        $this->today = Carbon::today();
        $pw = config('salon.demo_password');

        DB::transaction(function () use ($pw) {
            $this->wipe();
            $users = $this->users($pw);
            $this->salon();
            $cats = $this->categories();
            $this->services($cats);
            $staff = $this->staff($users);
            $this->promos();
            $clients = $this->clients($users['client']);
            $this->appointments($staff, $clients);
            $this->reviews();
            $this->extras($users, $clients);
            Setting::put('demo_seeded_on', $this->today->toDateString());
        });

        $this->sampleEmails();
    }

    private function wipe(): void
    {
        foreach (['reviews', 'appointments', 'messages', 'activities', 'sent_emails', 'promo_codes', 'clients', 'time_off',
            'working_hours', 'service_staff', 'staff', 'services', 'service_categories', 'closures', 'business_hours', 'settings'] as $t) {
            DB::table($t)->delete();
        }
        User::query()->where('is_demo', false)->delete(); // accounts visitors created
    }

    // ----------------------------------------------------------------- people
    private function user(string $email, string $name, Role $role, string $pw, bool $loginable, ?string $phone = null): User
    {
        $u = User::query()->firstOrNew(['email' => $email]);
        $u->fill(['name' => $name, 'role' => $role, 'is_demo' => true, 'active' => true, 'phone' => $phone]);
        if ($loginable) {
            if (! $u->exists || ! Hash::check($pw, $u->password)) {
                $u->password = $pw;
            }
        } elseif (! $u->exists) {
            $u->password = Str::random(40); // team members are scenery: nobody can sign in as them
        }
        $u->email_verified_at ??= now();
        $u->save();

        return $u;
    }

    private function users(string $pw): array
    {
        return [
            'admin' => $this->user('admin@lumiere.demo', 'Isabella Laurent', Role::Admin, $pw, true, '(212) 555-0101'),
            'manager' => $this->user('manager@lumiere.demo', 'Jordan Blake', Role::Manager, $pw, true, '(212) 555-0102'),
            'stylist' => $this->user('stylist@lumiere.demo', 'Sofia Marino', Role::Stylist, $pw, true, '(212) 555-0103'),
            'client' => $this->user('client@lumiere.demo', 'Emma Wilson', Role::Customer, $pw, true, '(917) 555-0188'),
            'amara' => $this->user('amara@lumiere.demo', 'Amara Okafor', Role::Stylist, $pw, false),
            'marco' => $this->user('marco@lumiere.demo', 'Marco Bellini', Role::Stylist, $pw, false),
            'chloe' => $this->user('chloe@lumiere.demo', 'Chloe Dubois', Role::Stylist, $pw, false),
            'hana' => $this->user('hana@lumiere.demo', 'Hana Sato', Role::Stylist, $pw, false),
            'mei' => $this->user('mei@lumiere.demo', 'Mei Lin Tanaka', Role::Stylist, $pw, false),
            'layla' => $this->user('layla@lumiere.demo', 'Layla Haddad', Role::Stylist, $pw, false),
            'priya' => $this->user('priya@lumiere.demo', 'Priya Nair', Role::Stylist, $pw, false),
        ];
    }

    // ----------------------------------------------------------------- salon
    private function salon(): void
    {
        // weekday 0 = Sunday. Monday is closed.
        $open = [0 => ['10:00', '16:00'], 1 => null, 2 => ['09:00', '19:00'], 3 => ['09:00', '19:00'],
            4 => ['09:00', '20:00'], 5 => ['09:00', '20:00'], 6 => ['09:00', '18:00']];
        foreach ($open as $wd => $h) {
            DB::table('business_hours')->insert(['weekday' => $wd, 'is_open' => (bool) $h, 'opens' => $h[0] ?? null, 'closes' => $h[1] ?? null]);
            if ($h) {
                $this->hours[$wd] = [$this->min($h[0]), $this->min($h[1])];
            }
        }
        foreach ([[19, 'Team retreat'], [33, 'Staff training day']] as [$d, $why]) {
            $date = $this->today->copy()->addDays($d);
            DB::table('closures')->insert(['date' => $date->toDateString(), 'reason' => $why]);
            $this->closed[$date->toDateString()] = true;
        }
        Setting::put('slot_interval', 15);
    }

    private function categories(): array
    {
        $rows = [
            ['Hair Cuts & Styling', 'hair-cuts', '💇‍♀️', 'rose', 'Precision cuts, blowouts and event styling', false],
            ['Hair Color', 'hair-color', '🎨', 'plum', 'Balayage, highlights and lived-in color', true],
            ['Nails', 'nails', '💅', 'blush', 'Manicures, pedicures and nail art', true],
            ['Skin & Facials', 'skin', '✨', 'sage', 'Glow-giving facials and skin treatments', false],
            ['Makeup', 'makeup', '💄', 'coral', 'Everyday looks to bridal glam', true],
            ['Brows & Lashes', 'brows-lashes', '🦋', 'sky', 'Brow shaping, lamination and lash extensions', true],
            ['Massage & Spa', 'massage', '🧖‍♀️', 'gold', 'Unwind with a restorative massage', false],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $slug, $icon, $theme, $blurb, $ladies]) {
            $ids[$slug] = DB::table('service_categories')->insertGetId([
                'name' => $name, 'slug' => $slug, 'icon' => $icon, 'theme' => $theme, 'blurb' => $blurb,
                'ladies_pricing' => $ladies, 'sort' => $i,
            ]);
        }

        return $ids;
    }

    private function services(array $cats): void
    {
        // category slug, name, minutes, buffer, dollars, featured, description
        $list = [
            ['hair-cuts', "Women's Cut & Finish", 60, 0, 85, true, 'A consultation, shampoo, precision cut and a polished blow-dry finish.'],
            ['hair-cuts', "Men's Cut", 30, 0, 45, false, 'Classic or modern cut with a hot-towel neck finish.'],
            ['hair-cuts', 'Blowout & Style', 45, 0, 55, false, 'Shampoo and a long-lasting blowout, from sleek to voluminous.'],
            ['hair-cuts', 'Bridal & Event Updo', 90, 15, 140, false, 'A romantic updo or half-up style, with a trial available on request.'],
            ['hair-cuts', "Kids' Cut", 30, 0, 30, false, 'Gentle, fuss-free cuts for little ones under 12.'],
            ['hair-color', 'Root Touch-Up', 75, 0, 95, false, 'Keeps your color seamless between full appointments.'],
            ['hair-color', 'Full Color', 120, 15, 160, false, 'All-over permanent or demi-permanent color with a gloss and blow-dry.'],
            ['hair-color', 'Balayage', 180, 15, 260, true, 'Hand-painted, sun-kissed dimension that grows out beautifully.'],
            ['hair-color', 'Partial Highlights', 120, 15, 190, false, 'Brighten your face frame and crown with foil highlights.'],
            ['hair-color', 'Gloss & Tone', 45, 0, 70, false, 'A shine-boosting gloss that refreshes tone and seals the cuticle.'],
            ['hair-color', 'Keratin Smoothing', 150, 15, 280, false, 'Frizz-taming smoothing treatment that lasts up to three months.'],
            ['nails', 'Classic Manicure', 30, 0, 35, false, 'Shape, cuticle care, massage and polish in your color.'],
            ['nails', 'Gel Manicure', 45, 0, 55, true, 'Chip-free gel color that stays glossy for two weeks or more.'],
            ['nails', 'Classic Pedicure', 45, 0, 50, false, 'A soak, scrub, nail care and polish for happy feet.'],
            ['nails', 'Spa Pedicure', 60, 0, 72, false, 'Our deluxe pedicure with a hot-stone massage and mask.'],
            ['nails', 'Acrylic Full Set', 90, 0, 85, false, 'Sculpted, durable extensions in the shape and length you love.'],
            ['nails', 'Nail Art (per set)', 30, 0, 30, false, 'Hand-painted designs, chrome, French twists and more.'],
            ['skin', 'Signature Glow Facial', 60, 10, 120, true, 'Our most-loved facial: cleanse, exfoliate, massage, mask and serum.'],
            ['skin', 'Hydrating Facial', 45, 10, 95, false, 'Deep moisture for dry, tired or dehydrated skin.'],
            ['skin', 'Deep Cleanse & Extractions', 60, 10, 110, false, 'A purifying treatment for congested or blemish-prone skin.'],
            ['skin', 'Chemical Peel', 45, 10, 130, false, 'A gentle resurfacing peel for tone, texture and brightness.'],
            ['makeup', 'Everyday Makeup', 45, 0, 75, false, 'A fresh, polished look for work, dates or dinner.'],
            ['makeup', 'Evening Glam', 60, 0, 110, false, 'Full glam with lashes for parties and special occasions.'],
            ['makeup', 'Bridal Makeup & Trial', 120, 15, 250, false, 'A trial run plus wedding-day makeup designed to last.'],
            ['brows-lashes', 'Brow Shape & Tint', 30, 0, 45, false, 'Expert shaping and a tint that frames your face.'],
            ['brows-lashes', 'Brow Lamination', 45, 0, 75, true, 'Brushed-up, fluffy brows that hold their shape for weeks.'],
            ['brows-lashes', 'Classic Lash Extensions', 90, 0, 140, false, 'One extension per natural lash for an elegant, defined look.'],
            ['brows-lashes', 'Volume Lash Extensions', 120, 0, 185, false, 'Fluffy handmade fans for a fuller, dramatic effect.'],
            ['brows-lashes', 'Lash Lift & Tint', 60, 0, 95, false, 'Curl and darken your natural lashes, no mascara needed.'],
            ['massage', 'Swedish Massage', 60, 10, 95, false, 'Long, flowing strokes to melt away tension.'],
            ['massage', 'Deep Tissue Massage', 60, 10, 110, false, 'Firm pressure that targets knots and tight muscles.'],
            ['massage', 'Hot Stone Massage', 75, 10, 130, false, 'Heated stones and slow strokes for deep relaxation.'],
            ['massage', 'Scalp Therapy Ritual', 30, 0, 50, false, 'A blissful scalp massage with a nourishing treatment.'],
        ];
        foreach ($list as $i => [$cat, $name, $min, $buf, $price, $featured, $desc]) {
            $id = DB::table('services')->insertGetId([
                'service_category_id' => $cats[$cat], 'name' => $name, 'slug' => Str::slug($name), 'description' => $desc,
                'duration' => $min, 'buffer' => $buf, 'price' => $price * 100, 'deposit_required' => $price >= 50,
                'featured' => $featured, 'active' => true, 'sort' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->services[$id] = ['id' => $id, 'cat' => $cat, 'name' => $name, 'duration' => $min, 'buffer' => $buf,
                'price' => $price * 100, 'deposit' => $price >= 50, 'weight' => $featured ? 2.6 : 1.0];
        }
    }

    private function staff(array $users): array
    {
        $team = [
            ['stylist', 'Sofia Marino', 'Creative Director & Senior Colorist', '#e11d48', 12, ['hair-color', 'hair-cuts'],
                'Sofia trained in Milan and has spent twelve years perfecting lived-in color. Her balayage clients travel across the city for her signature soft, sun-kissed blends.', 'Balayage, color correction, cuts', [2, 3, 4, 5, 6], ['10:00', '19:00']],
            ['amara', 'Amara Okafor', 'Senior Hair Stylist', '#7c3aed', 7, ['hair-cuts', 'hair-color'],
                'Amara loves a great shape. She specializes in textured cuts, curls and silk-press blowouts, and makes every consultation feel like a chat with a friend.', 'Textured hair, curls, blowouts', [2, 3, 4, 5, 6], ['09:00', '18:00']],
            ['marco', 'Marco Bellini', 'Barber & Men\'s Grooming', '#0ea5e9', 9, ['hair-cuts'],
                'Marco brings Roman barbering tradition to Rosewood Avenue: sharp fades, classic scissor cuts and the best hot-towel finish in the neighborhood.', 'Fades, classic cuts, beard work', [3, 4, 5, 6, 0], ['10:00', '19:00']],
            ['chloe', 'Chloe Dubois', 'Nail Artist', '#ec4899', 6, ['nails'],
                'Chloe turns nails into tiny works of art. From minimalist French tips to hand-painted florals, she loves a good brief and a better color story.', 'Nail art, gel, chrome', [2, 3, 4, 5, 6], ['09:00', '18:00']],
            ['hana', 'Hana Sato', 'Nail Technician', '#f97316', 4, ['nails'],
                'Hana is known for flawless, long-wearing gel manicures and spa pedicures that feel like a mini vacation.', 'Gel, pedicures, acrylics', [3, 4, 5, 6, 0], ['10:00', '19:00']],
            ['mei', 'Mei Lin Tanaka', 'Licensed Esthetician', '#10b981', 8, ['skin'],
                'Mei Lin designs facials around your skin, not a menu. She is certified in peels and advanced extractions and obsessed with a healthy glow.', 'Facials, peels, skin health', [2, 3, 4, 5, 6], ['09:00', '18:00']],
            ['layla', 'Layla Haddad', 'Makeup Artist & Lash Specialist', '#f59e0b', 7, ['makeup', 'brows-lashes'],
                'Layla has done makeup for over two hundred brides and is a certified volume lash artist. Her signature look is skin that looks like skin, only better.', 'Bridal, lashes, brow lamination', [2, 3, 4, 5, 6], ['10:00', '19:00']],
            ['priya', 'Priya Nair', 'Massage Therapist', '#14b8a6', 10, ['massage'],
                'Priya is a licensed therapist with a decade of experience in deep tissue and hot stone work. Expect a calm room, warm hands and zero small talk unless you want it.', 'Deep tissue, hot stone, scalp rituals', [2, 3, 4, 5, 6], ['10:00', '19:00']],
        ];
        $staff = [];
        foreach ($team as $i => [$key, $name, $title, $color, $years, $cats, $bio, $spec, $days, [$from, $to]]) {
            $id = DB::table('staff')->insertGetId([
                'user_id' => $users[$key]->id, 'name' => $name, 'title' => $title, 'bio' => $bio, 'specialties' => $spec,
                'color' => $color, 'years' => $years, 'instagram' => '@'.Str::slug(explode(' ', $name)[0]).'.at.lumiere',
                'active' => true, 'sort' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $staff[$key] = $id;
            foreach ($this->services as $svc) {
                $ok = in_array($svc['cat'], $cats, true)
                    && ! ($key === 'marco' && $svc['name'] !== "Men's Cut" && $svc['name'] !== "Kids' Cut")
                    && ! ($key === 'amara' && in_array($svc['name'], ['Balayage', 'Keratin Smoothing'], true));
                if ($ok) {
                    DB::table('service_staff')->insert(['service_id' => $svc['id'], 'staff_id' => $id]);
                    $this->services[$svc['id']]['staff'][] = $id;
                }
            }
            for ($wd = 0; $wd < 7; $wd++) {
                $works = in_array($wd, $days, true) && isset($this->hours[$wd]);
                $breakAt = ['12:30', '13:00', '13:30'][$i % 3];
                $breakEnd = Carbon::parse($breakAt)->addHour()->format('H:i');
                DB::table('working_hours')->insert([
                    'staff_id' => $id, 'weekday' => $wd, 'is_working' => $works, 'starts' => $works ? $from : null,
                    'ends' => $works ? $to : null, 'break_starts' => $works ? $breakAt : null, 'break_ends' => $works ? $breakEnd : null,
                ]);
                if ($works) {
                    $this->schedule[$id][$wd] = [$this->min($from), $this->min($to), $this->min($breakAt), $this->min($breakEnd)];
                }
            }
        }
        // Chloe takes a short vacation next week
        $from = $this->today->copy()->addDays(6);
        $to = $this->today->copy()->addDays(9);
        DB::table('time_off')->insert(['staff_id' => $staff['chloe'], 'starts_on' => $from->toDateString(), 'ends_on' => $to->toDateString(), 'reason' => 'Vacation']);
        for ($d = $from->copy(); $d <= $to; $d->addDay()) {
            $this->leave[$staff['chloe']][$d->toDateString()] = true;
        }

        return $staff;
    }

    private function promos(): void
    {
        DB::table('promo_codes')->insert([
            ['code' => 'WELCOME10', 'percent' => 10, 'description' => '10% off your first visit', 'active' => true, 'uses' => 41, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GLOW15', 'percent' => 15, 'description' => '15% off facials this season', 'active' => true, 'uses' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FRIENDS', 'percent' => 20, 'description' => 'Refer-a-friend reward', 'active' => false, 'uses' => 9, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // --------------------------------------------------------------- clients
    private array $clientIds = [];

    private array $clientWeights = [];

    private function clients(User $emailUser): array
    {
        $first = ['Olivia', 'Sophia', 'Isabella', 'Mia', 'Charlotte', 'Amelia', 'Harper', 'Evelyn', 'Abigail', 'Emily', 'Ella', 'Scarlett', 'Grace', 'Chloe', 'Victoria',
            'Riley', 'Aria', 'Lily', 'Zoey', 'Penelope', 'Layla', 'Nora', 'Hannah', 'Lucy', 'Stella', 'Maya', 'Leah', 'Alice', 'Naomi', 'Ruby', 'Jade', 'Camila', 'Valentina',
            'Priya', 'Fatima', 'Yuki', 'Sofia', 'Elena', 'Ines', 'Tara', 'Zara', 'Daniela', 'Ava', 'Madison', 'Lena', 'Ivy', 'Eliza', 'Claire', 'Natalie', 'Brooke',
            'Liam', 'Noah', 'Ethan', 'Lucas', 'Mason', 'Daniel', 'Ryan', 'Oliver', 'James', 'Henry'];
        $last = ['Smith', 'Johnson', 'Brown', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson', 'Clark',
            'Rodriguez', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'King', 'Wright', 'Scott', 'Green', 'Baker', 'Adams', 'Nelson', 'Hill', 'Campbell', 'Mitchell',
            'Roberts', 'Carter', 'Phillips', 'Evans', 'Turner', 'Torres', 'Parker', 'Collins', 'Edwards', 'Stewart', 'Morris', 'Murphy', 'Cook', 'Rogers', 'Morgan', 'Cooper',
            'Peterson', 'Reed', 'Bailey', 'Bell', 'Kim', 'Patel', 'Nguyen', 'Rossi', 'Dubois', 'Haddad', 'Silva', 'Novak'];

        $emma = DB::table('clients')->insertGetId([
            'user_id' => $emailUser->id, 'name' => 'Emma Wilson', 'email' => 'client@lumiere.demo', 'phone' => '(917) 555-0188',
            'birthday' => $this->today->copy()->subYears(29)->addDays(40)->toDateString(), 'notes' => 'Prefers warm tones. Loves a good cappuccino.',
            'vip' => false, 'marketing_opt_in' => true, 'source' => 'web', 'created_at' => $this->today->copy()->subDays(130), 'updated_at' => now(),
        ]);
        $this->clientIds[] = $emma;

        $rows = [];
        $seen = [];
        for ($i = 0; count($rows) < 520; $i++) {
            $f = $first[mt_rand(0, count($first) - 1)];
            $l = $last[mt_rand(0, count($last) - 1)];
            if (isset($seen["$f$l"])) {
                continue;
            }
            $seen["$f$l"] = true;
            $rows[] = [
                'user_id' => null, 'name' => "$f $l", 'email' => strtolower("$f.$l").mt_rand(1, 99).'@example.com',
                'phone' => '(646) 555-'.str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'birthday' => mt_rand(0, 3) ? $this->today->copy()->subYears(mt_rand(20, 58))->subDays(mt_rand(0, 360))->toDateString() : null,
                'notes' => null, 'vip' => mt_rand(1, 100) <= 6, 'marketing_opt_in' => mt_rand(1, 100) <= 82,
                'source' => ['web', 'web', 'web', 'phone', 'walk-in', 'referral'][mt_rand(0, 5)],
                'created_at' => $this->today->copy()->subDays(mt_rand(5, 200)), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('clients')->insert($chunk);
        }
        $this->clientIds = array_merge($this->clientIds, DB::table('clients')->where('id', '!=', $emma)->orderBy('id')->pluck('id')->all());

        // Loyal clients come back more often
        $sum = 0;
        foreach ($this->clientIds as $i => $id) {
            $sum += $i === 0 ? 0 : min(9.0, 0.6 + 1.4 * pow(mt_rand(1, 1000) / 1000, -0.55) * 0.5);
            $this->clientWeights[$i] = $sum;
        }

        return ['emma' => $emma];
    }

    private function pickClient(): int
    {
        $r = mt_rand() / mt_getrandmax() * end($this->clientWeights);
        $lo = 1;
        $hi = count($this->clientWeights) - 1;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($this->clientWeights[$mid] < $r) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }

        return $this->clientIds[$lo];
    }

    // ---------------------------------------------------------- appointments
    private function appointments(array $staff, array $clients): void
    {
        $this->emmaAppointments($staff, $clients['emma']);

        $staffIds = array_values($staff);
        for ($d = -140; $d <= 24; $d++) {
            $date = $this->today->copy()->addDays($d);
            $key = $date->toDateString();
            $wd = $date->dayOfWeek;
            if (! isset($this->hours[$wd]) || isset($this->closed[$key])) {
                continue;
            }
            [$open, $close] = $this->hours[$wd];
            $keep = $d < 0 ? 0.82 : max(0.06, 0.62 - $d * 0.035);

            foreach ($staffIds as $sid) {
                if (! isset($this->schedule[$sid][$wd]) || isset($this->leave[$sid][$key])) {
                    continue;
                }
                [$s, $e, $bs, $be] = $this->schedule[$sid][$wd];
                $cursor = max($open, $s);
                $end = min($close, $e);
                $pool = array_values(array_filter($this->services, fn ($v) => in_array($sid, $v['staff'] ?? [], true)));
                if (! $pool) {
                    continue;
                }
                while ($cursor < $end) {
                    $cursor += [0, 0, 0, 15, 15, 30, 45][mt_rand(0, 6)];
                    $svc = $this->weighted($pool);
                    $dur = $svc['duration'];
                    do {
                        $moved = false;
                        if ($bs !== null && $cursor < $be && $cursor + $dur > $bs) {
                            $cursor = $be;
                            $moved = true;
                        }
                        foreach ($this->busy[$sid][$key] ?? [] as [$f, $t]) {
                            if ($cursor < $t && $cursor + $dur > $f) {
                                $cursor = $t;
                                $moved = true;
                            }
                        }
                    } while ($moved);
                    if ($cursor + $dur > $end) {
                        break;
                    }
                    if (mt_rand() / mt_getrandmax() < $keep) {
                        $this->addRow($this->pickClient(), $sid, $svc, $date, $cursor, null);
                    }
                    $cursor += $dur + $svc['buffer'];
                }
            }
        }

        foreach (array_chunk($this->rows, 400) as $chunk) {
            DB::table('appointments')->insert($chunk);
        }

        // Client records start at their first visit
        DB::statement('UPDATE clients SET created_at = COALESCE((SELECT MIN(created_at) FROM appointments WHERE appointments.client_id = clients.id), clients.created_at) WHERE clients.user_id IS NULL');
    }

    private function emmaAppointments(array $staff, int $emma): void
    {
        $find = fn (string $name) => collect($this->services)->firstWhere('name', $name);
        // [service, staff, day offset (negative = past), start hour:min, status, reviewed?]
        $plan = [
            ['Brow Lamination', 'layla', -96, 11 * 60, 'completed'],
            ['Gel Manicure', 'chloe', -62, 11 * 60, 'completed'],
            ['Signature Glow Facial', 'mei', -31, 14 * 60, 'completed'],
            ['Gel Manicure', 'chloe', -3, 11 * 60, 'completed'],
            ['Balayage', 'sofia', 0, 0, 'skip'],
            ['Gel Manicure', 'chloe', 4, 11 * 60, 'confirmed'],
            ['Brow Lamination', 'layla', 11, 11 * 60, 'pending'],
        ];
        foreach ($plan as [$svcName, $who, $offset, $minute, $status]) {
            if ($status === 'skip') {
                continue;
            }
            $sid = $staff[$who === 'sofia' ? 'stylist' : $who];
            $date = $this->workingDay($sid, $offset, $offset < 0 ? -1 : 1);
            $this->addRow($emma, $sid, $find($svcName), $date, $minute, $status);
        }
    }

    private function addRow(int $clientId, int $sid, array $svc, Carbon $date, int $startMin, ?string $forceStatus): void
    {
        $start = $date->copy()->startOfDay()->addMinutes($startMin);
        $end = $start->copy()->addMinutes($svc['duration']);
        $isPast = $end->lt(now());
        $days = $this->today->diffInDays($date, false);

        $status = $forceStatus ?? ($isPast
            ? $this->pick(['completed' => 88, 'cancelled' => 7, 'no_show' => 5])
            : ($days >= 1 ? $this->pick(['confirmed' => 82, 'pending' => 13, 'cancelled' => 5]) : $this->pick(['confirmed' => 95, 'cancelled' => 5])));

        $promo = mt_rand(1, 100) <= 9 ? ['WELCOME10', 10] : (mt_rand(1, 100) <= 4 && $svc['cat'] === 'skin' ? ['GLOW15', 15] : null);
        $price = (int) round($svc['price'] * (100 - ($promo[1] ?? 0)) / 100);
        $deposit = $svc['deposit'] ? (int) round($price * 0.2) : 0;

        do {
            $ref = strtoupper(Str::random(8));
        } while (isset($this->refs[$ref]));
        $this->refs[$ref] = true;

        $created = $start->copy()->subDays(mt_rand(1, 14))->subMinutes(mt_rand(0, 1200));
        if ($created->gt(now())) {
            $created = now()->subMinutes(mt_rand(5, 3000));
        }
        $notes = mt_rand(1, 100) <= 8 ? ['First visit, a little nervous!', 'Bringing a reference photo', 'Sensitive skin, please patch test', 'Running a few minutes late', 'Celebrating a birthday 🎉'][mt_rand(0, 4)] : null;

        $this->rows[] = [
            'reference' => $ref, 'client_id' => $clientId, 'staff_id' => $sid, 'service_id' => $svc['id'],
            'start_at' => $start->format('Y-m-d H:i:s'), 'end_at' => $end->format('Y-m-d H:i:s'), 'status' => $status,
            'price' => $price, 'deposit' => $deposit, 'deposit_paid' => $deposit > 0 && $status !== 'pending' || ($deposit > 0 && mt_rand(1, 100) <= 40),
            'promo_code' => $promo[0] ?? null, 'source' => $this->pick(['web' => 62, 'phone' => 20, 'walk-in' => 10, 'admin' => 8]),
            'notes' => $notes, 'cancel_reason' => $status === 'cancelled' ? ['Change of plans', 'Feeling unwell', 'Schedule conflict', 'Rescheduling'][mt_rand(0, 3)] : null,
            'created_at' => $created->format('Y-m-d H:i:s'), 'updated_at' => $created->format('Y-m-d H:i:s'),
        ];
        $this->busy[$sid][$date->toDateString()][] = [$startMin, $startMin + $svc['duration'] + $svc['buffer']];
    }

    // --------------------------------------------------------------- reviews
    private function reviews(): void
    {
        $five = [
            'Absolutely loved my {service}! {staff} was so thoughtful and I left feeling brand new.',
            "Best {service} I've ever had. {staff} listened to exactly what I wanted. Already booked my next visit!",
            'The whole studio is gorgeous and {staff} made me feel so comfortable. My {service} turned out perfectly.',
            "Five stars isn't enough. {staff} is a true artist. I got compliments all week!",
            'Booking online was effortless and the {service} was flawless. Highly recommend {staff}.',
            "I've been coming here for over a year and every visit is just as good. {staff} is the best.",
            'Such a relaxing experience. My {service} was exactly what I needed, thank you {staff}!',
            'Worth every penny. {staff} took their time and the result is stunning.',
            'Walked in stressed, walked out glowing. {staff} is magic and the {service} was perfect.',
        ];
        $four = [
            'Really happy with my {service}. {staff} was lovely, only a short wait but worth it.',
            'Great result and a very friendly team. Would book {staff} again.',
            'Lovely studio and a great {service}. A little pricey but the quality shows.',
            "Very good! {staff} did a careful job. I'd just love earlier morning slots.",
        ];
        $three = ['Good {service}, though it ran a bit behind schedule. {staff} was nice.', 'Decent experience. The result was fine but not quite what I pictured.'];
        $low = ["Not quite what I hoped for. The {service} didn't match my reference photo.", 'Had to wait 20 minutes past my appointment time.'];
        $reply = 'Thank you for your honest feedback. We would love to make this right. Please reach out and we will arrange a complimentary touch-up. Isabella';

        $appts = Appointment::query()->with(['staff:id,name', 'service:id,name'])->where('status', 'completed')->get(['id', 'client_id', 'staff_id', 'service_id', 'start_at']);
        $out = [];
        foreach ($appts as $a) {
            if (mt_rand(1, 100) > 17) {
                continue;
            }
            $rating = (int) $this->pick(['5' => 64, '4' => 25, '3' => 7, '2' => 3, '1' => 1]);
            $bank = $rating === 5 ? $five : ($rating === 4 ? $four : ($rating === 3 ? $three : $low));
            $text = str_replace(['{service}', '{staff}'], [Str::lower($a->service->name), explode(' ', $a->staff->name)[0]], $bank[mt_rand(0, count($bank) - 1)]);
            $out[] = [
                'appointment_id' => $a->id, 'client_id' => $a->client_id, 'staff_id' => $a->staff_id, 'service_id' => $a->service_id,
                'rating' => $rating, 'comment' => $text, 'published' => $rating >= 3 || mt_rand(0, 1), 'reply' => $rating <= 2 ? $reply : null,
                'created_at' => $a->start_at->copy()->addDays(mt_rand(0, 3)), 'updated_at' => $a->start_at->copy()->addDays(mt_rand(0, 3)),
            ];
        }
        // Emma has reviewed her older visits, but not the most recent one
        $emmaId = DB::table('clients')->where('email', 'client@lumiere.demo')->value('id');
        $older = $appts->where('client_id', $emmaId)->sortBy('start_at')->values();
        $out = array_values(array_filter($out, fn ($r) => $r['client_id'] !== $emmaId));
        foreach ($older->take(max(0, $older->count() - 1)) as $a) {
            $out[] = [
                'appointment_id' => $a->id, 'client_id' => $emmaId, 'staff_id' => $a->staff_id, 'service_id' => $a->service_id,
                'rating' => 5, 'comment' => str_replace(['{service}', '{staff}'], [Str::lower($a->service->name), explode(' ', $a->staff->name)[0]], $five[mt_rand(0, count($five) - 1)]),
                'published' => true, 'reply' => null, 'created_at' => $a->start_at->copy()->addDay(), 'updated_at' => $a->start_at->copy()->addDay(),
            ];
        }
        foreach (array_chunk($out, 300) as $chunk) {
            DB::table('reviews')->insert($chunk);
        }
    }

    // ---------------------------------------------------------------- extras
    private function extras(array $users, array $clients): void
    {
        $messages = [
            ['Dana Whitfield', 'dana.w@example.com', 'Bridal party of six in June?', 'Hi! I am getting married on June 14th and would love to book hair and makeup for six people that morning. Do you offer a group rate?'],
            ['Chris Alvarez', 'chris.a@example.com', 'Gift card question', 'Do you sell gift cards? I would like to get one for my mom for Mother\'s Day.'],
            ['Tamara Ng', 'tamara.ng@example.com', 'Lost earring', 'I think I left a small gold hoop earring at the nail station yesterday. Has anyone found it?'],
            ['Jules Parker', 'jules.p@example.com', 'Patch test before lash extensions', 'Quick question: do I need a patch test before my first lash appointment, and how far in advance should I do it?'],
            ['Nina Rossi', 'nina.r@example.com', 'Love the studio!', 'Just wanted to say thank you. Everyone was so kind and the space is beautiful. See you soon!'],
        ];
        foreach ($messages as $i => [$name, $email, $subject, $body]) {
            DB::table('messages')->insert([
                'name' => $name, 'email' => $email, 'subject' => $subject, 'body' => $body, 'read_at' => $i > 2 ? now()->subDays($i) : null,
                'created_at' => now()->subHours(($i + 1) * 9), 'updated_at' => now()->subHours(($i + 1) * 9),
            ]);
        }

        $recent = Appointment::query()->with(['client:id,name', 'service:id,name'])->where('start_at', '>=', now()->subDays(12))->where('start_at', '<=', now()->addDays(7))->inRandomOrder()->limit(70)->get();
        $actors = [$users['admin']->id, $users['manager']->id, $users['stylist']->id];
        $acts = [];
        foreach ($recent as $a) {
            $when = now()->subMinutes(mt_rand(20, 60 * 24 * 12));
            $kind = mt_rand(1, 10);
            [$action, $details, $actor] = match (true) {
                $kind <= 4 => ['booking', $a->client->name.' booked '.$a->service->name, null],
                $kind <= 6 => ['status', $a->client->name.': marked '.$a->status->label(), $actors[mt_rand(0, 2)]],
                $kind <= 7 => ['reschedule', $a->client->name.' moved to '.$a->start_at->format('M j, g:i A'), $actors[mt_rand(0, 1)]],
                $kind <= 8 => ['cancel', $a->client->name.' cancelled '.$a->service->name, null],
                default => ['review', $a->client->name.' left a review', null],
            };
            $acts[] = ['user_id' => $actor, 'action' => $action, 'subject' => $a->reference, 'details' => $details,
                'url' => '/admin/appointments/'.$a->id, 'ip' => '10.0.'.mt_rand(0, 9).'.'.mt_rand(2, 250), 'created_at' => $when];
        }
        foreach ([['login', 'admin'], ['login', 'manager'], ['login', 'stylist'], ['export', 'manager'], ['settings', 'admin'], ['service', 'manager'], ['staff', 'admin'], ['logout', 'manager']] as [$action, $who]) {
            $acts[] = ['user_id' => $users[$who]->id, 'action' => $action, 'subject' => $users[$who]->name, 'details' => match ($action) {
                'login' => 'Signed in', 'logout' => 'Signed out', 'export' => 'Exported appointments (CSV)', 'settings' => 'Updated booking rules',
                'service' => 'Edited a service price', default => 'Updated working hours',
            }, 'url' => null, 'ip' => '10.0.'.mt_rand(0, 9).'.'.mt_rand(2, 250), 'created_at' => now()->subMinutes(mt_rand(30, 60 * 24 * 10))];
        }
        DB::table('activities')->insert($acts);
    }

    /** A few sample outgoing emails so the Outbox page has something to show. */
    private function sampleEmails(): void
    {
        $upcoming = Appointment::query()->with(['client', 'service', 'staff'])->upcoming()->where('client_id', '!=', DB::table('clients')->where('email', 'client@lumiere.demo')->value('id'))
            ->orderBy('start_at')->limit(4)->get();
        foreach ($upcoming as $a) {
            Mail::to($a->client->email)->send(new BookingConfirmed($a));
        }
    }

    // --------------------------------------------------------------- helpers
    private function workingDay(int $sid, int $from, int $step): Carbon
    {
        for ($i = 0; $i < 25; $i++) {
            $date = $this->today->copy()->addDays($from + $i * $step);
            $key = $date->toDateString();
            if (isset($this->hours[$date->dayOfWeek], $this->schedule[$sid][$date->dayOfWeek]) && ! isset($this->closed[$key]) && ! isset($this->leave[$sid][$key])) {
                return $date;
            }
        }

        return $this->today->copy();
    }

    private function min(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    private function pick(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return (string) $key;
            }
        }

        return (string) array_key_first($weights);
    }

    private function weighted(array $pool): array
    {
        $total = array_sum(array_column($pool, 'weight'));
        $r = mt_rand() / mt_getrandmax() * $total;
        foreach ($pool as $item) {
            if (($r -= $item['weight']) <= 0) {
                return $item;
            }
        }

        return end($pool);
    }
}
