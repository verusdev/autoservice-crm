<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPart;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Work;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CrmSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private array $posts = [];

    private array $mechanics = [];

    private array $advisors = [];

    private array $services = [];

    private array $parts = [];

    private array $seq = [];

    public function run(): void
    {
        mt_srand(20260101);

        $this->command?->info('Очистка таблиц CRM…');
        $this->truncate();

        $this->createPosts();
        $this->createEmployees();
        $this->createServices();
        $this->createParts();
        [$customers, $vehicles] = $this->createCustomersAndVehicles();
        $this->createOrders($customers, $vehicles);

        $this->command?->info('Готово: демо-данные загружены.');
    }

    private function truncate(): void
    {
        $tables = [
            'payments', 'stock_movements', 'order_parts', 'works', 'orders',
            'vehicles', 'customers', 'parts', 'services', 'users', 'posts',
        ];

        DB::statement('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
        DB::statement('PRAGMA foreign_keys = ON');
    }

    /* ---------------- Посты ---------------- */

    private function createPosts(): void
    {
        $rows = [
            ['name' => 'Слесарный пост №1', 'code' => 'П-1', 'capacity_hours' => 8, 'hourly_rate' => 1300, 'description' => 'Ходовая, подвеска, тормоза'],
            ['name' => 'Слесарный пост №2', 'code' => 'П-2', 'capacity_hours' => 8, 'hourly_rate' => 1300, 'description' => 'Двигатель, КПП, охлаждение'],
            ['name' => 'Диагностика', 'code' => 'Д-1', 'capacity_hours' => 8, 'hourly_rate' => 1600, 'description' => 'Компьютерная диагностика, сканер'],
            ['name' => 'Кузовной участок', 'code' => 'К-1', 'capacity_hours' => 7, 'hourly_rate' => 1500, 'description' => 'Кузовной ремонт, покраска'],
            ['name' => 'Шиномонтаж', 'code' => 'Ш-1', 'capacity_hours' => 8, 'hourly_rate' => 900, 'description' => 'Шины, диски, балансировка'],
        ];

        foreach ($rows as $row) {
            $this->posts[] = Post::create($row + ['is_active' => true]);
        }
    }

    /* ---------------- Сотрудники ---------------- */

    private function createEmployees(): void
    {
        $password = Hash::make(self::PASSWORD);

        $admin = User::create([
            'name' => 'Сергей Ковалёв', 'email' => 'admin@autoservice.ru', 'password' => $password,
            'phone' => '+7 903 111-22-33', 'position' => 'Управляющий', 'role' => 'admin',
            'hourly_rate' => 0, 'post_id' => null, 'is_active' => true,
        ]);

        $advisor1 = User::create([
            'name' => 'Анна Соколова', 'email' => 'priem@autoservice.ru', 'password' => $password,
            'phone' => '+7 903 444-55-66', 'position' => 'Мастер-приёмщик', 'role' => 'advisor',
            'hourly_rate' => 0, 'post_id' => null, 'is_active' => true,
        ]);

        $advisor2 = User::create([
            'name' => 'Марина Егорова', 'email' => 'm.egorova@autoservice.ru', 'password' => $password,
            'phone' => '+7 903 777-88-99', 'position' => 'Мастер-приёмщик', 'role' => 'advisor',
            'hourly_rate' => 0, 'post_id' => null, 'is_active' => true,
        ]);

        $this->advisors = [$advisor1, $advisor2, $admin];

        $mechanics = [
            ['Алексей Морозов', 'alexey@autoservice.ru', 1500, 0, '+7 904 100-01-01'],
            ['Дмитрий Орлов', 'd.orlov@autoservice.ru', 1400, 0, '+7 904 100-01-02'],
            ['Игорь Лебедев', 'i.lebedev@autoservice.ru', 1300, 2, '+7 904 100-01-03'],
            ['Руслан Ганиев', 'r.ganiev@autoservice.ru', 1350, 2, '+7 904 100-01-04'],
            ['Сергей Панов', 's.panov@autoservice.ru', 1250, 3, '+7 904 100-01-05'],
            ['Артём Волков', 'a.volkov@autoservice.ru', 1200, 4, '+7 904 100-01-06'],
        ];

        foreach ($mechanics as [$name, $email, $rate, $postIndex, $phone]) {
            $this->mechanics[] = User::create([
                'name' => $name, 'email' => $email, 'password' => $password,
                'phone' => $phone, 'position' => 'Автослесарь', 'role' => 'mechanic',
                'hourly_rate' => $rate, 'post_id' => $this->posts[$postIndex]->id, 'is_active' => true,
            ]);
        }
    }

    /* ---------------- Справочник услуг ---------------- */

    private function createServices(): void
    {
        $rows = [
            ['Замена масла двигателя и масляного фильтра', 'ТО', 1.0, 2500],
            ['Замена передних тормозных колодок', 'Ремонт', 1.5, 3200],
            ['Замена задних тормозных колодок', 'Ремонт', 1.2, 2900],
            ['Замена тормозных дисков в сборе', 'Ремонт', 2.0, 4800],
            ['Диагностика подвески', 'Диагностика', 1.0, 1800],
            ['Замена шаровой опоры', 'Ремонт', 2.5, 5200],
            ['Замена ступичного подшипника', 'Ремонт', 2.0, 4600],
            ['Замена амортизатора в сборе', 'Ремонт', 2.2, 4900],
            ['Замена свечей зажигания', 'ТО', 0.8, 1600],
            ['Замена ремня ГРМ', 'Ремонт', 4.0, 9800],
            ['Замена радиатора печки', 'Ремонт', 3.0, 6400],
            ['Замена помпы (водяной насос)', 'Ремонт', 3.5, 7600],
            ['Замена сайлентблоков', 'Ремонт', 2.6, 5600],
            ['Замена стойки стабилизатора (компл.)', 'Ремонт', 2.0, 4300],
            ['Компьютерная диагностика', 'Компьютерная диагностика', 1.0, 2500],
            ['Диагностика системы кондиционирования', 'Диагностика', 1.5, 2800],
            ['Чистка дроссельной заслонки', 'Ремонт', 1.2, 2600],
            ['Замена свечей накаливания', 'Ремонт', 1.6, 3200],
            ['Шиномонтаж (компл. 4 колеса)', 'Шиномонтаж', 1.0, 2400],
            ['Балансировка колеса', 'Шиномонтаж', 0.4, 600],
            ['Правка «разъезда» дисков', 'Шиномонтаж', 1.5, 2000],
            ['Полировка фар', 'Кузовной ремонт', 1.5, 3200],
            ['Замена лобового стекла', 'Кузовной ремонт', 2.0, 4500],
            ['Локальная покраска элемента', 'Кузовной ремонт', 4.0, 8800],
            ['Антикоррозийная обработка днища', 'ТО', 2.0, 5600],
            ['Замена форсунок', 'Ремонт', 4.5, 9600],
        ];

        foreach ($rows as [$name, $category, $hours, $price]) {
            $this->services[$category][] = Service::create([
                'name' => $name, 'category' => $category,
                'norm_hours' => $hours, 'price' => $price, 'is_active' => true,
            ]);
        }
    }

    /* ---------------- Склад ---------------- */

    private function createParts(): void
    {
        $rows = [
            ['Масляный фильтр OEM', 'Filtron', 'Фильтры', 'шт', 420, 850, 8],
            ['Масляный фильтр неоригинал', 'Mann', 'Фильтры', 'шт', 280, 650, 6],
            ['Воздушный фильтр', 'Mann', 'Фильтры', 'шт', 560, 1150, 6],
            ['Салонный фильтр', 'Bosch', 'Фильтры', 'шт', 340, 720, 10],
            ['Тормозные колодки комплект (перед)', 'TRW', 'Тормоза', 'компл', 3200, 5900, 4],
            ['Тормозные колодки комплект (зад)', 'TRW', 'Тормоза', 'компл', 2600, 4800, 4],
            ['Тормозной диск (компл. 4 шт)', 'Brembo', 'Тормоза', 'компл', 8400, 14500, 2],
            ['Моторное масло 5W-30 (4 л)', 'Shell Helix', 'Масла', 'л', 900, 1450, 40],
            ['Моторное масло 0W-20 (4 л)', 'Mobil', 'Масла', 'л', 1100, 1750, 30],
            ['Охлаждающая жидкость (5 л)', 'Felix', 'Масла', 'л', 800, 1300, 24],
            ['Свеча зажигания', 'NGK', 'Электрика', 'шт', 780, 1350, 20],
            ['Свеча накаливания', 'NGK', 'Электрика', 'шт', 1650, 2800, 12],
            ['Ремень ГРМ комплект', 'Gates', 'Двигатель', 'компл', 5200, 8900, 2],
            ['Помпа (водяной насос)', 'Airtex', 'Двигатель', 'шт', 3400, 6200, 3],
            ['Термостат', 'Airtex', 'Двигатель', 'шт', 1600, 2950, 4],
            ['Амортизатор передний', 'KYB', 'Подвеска', 'шт', 3800, 6900, 4],
            ['Амортизатор задний', 'KYB', 'Подвеска', 'шт', 2900, 5400, 4],
            ['Шаровая опора', 'Lemförder', 'Подвеска', 'шт', 1450, 2700, 8],
            ['Сайлентблок', 'CTR', 'Подвеска', 'компл', 1250, 2400, 8],
            ['Стойка стабилизатора', 'CTR', 'Подвеска', 'шт', 780, 1600, 10],
            ['Ступичный подшипник', 'SKF', 'Подвеска', 'шт', 2400, 4300, 6],
            ['Радиатор охлаждения', 'Denso', 'Охлаждение', 'шт', 5200, 8900, 2],
            ['Патрубок радиатора', 'Gates', 'Охлаждение', 'шт', 1100, 2200, 5],
            ['Антифриз-концентрат', 'Felix', 'Масла', 'л', 320, 600, 30],
            ['Лобовое стекло', 'Pilkington', 'Кузовной ремонт', 'шт', 4200, 8200, 2],
            ['Фара в сборе', 'Depo', 'Кузовной ремонт', 'шт', 6400, 11500, 2],
            ['Бампер передний', 'Sigma', 'Кузовной ремонт', 'шт', 5300, 9800, 1],
            ['Шина 185/65 R15', 'Kama', 'Шины', 'шт', 3400, 5900, 12],
            ['Шина 205/55 R16', 'Continental', 'Шины', 'шт', 7200, 11500, 8],
            ['Масло для ГУР', 'Idemitsu', 'Масла', 'л', 480, 850, 20],
        ];

        foreach ($rows as $index => [$name, $brand, $category, $unit, $price, $sell, $qty]) {
            $stock = $qty * 6; // запас заводим с запасом, чтобы за год он не закончился

            $part = Part::create([
                'sku' => strtoupper(Str::substr(preg_replace('/[^a-zA-Z]/', '', $brand) ?: 'PT', 0, 3)).'-'.str_pad((string) (1000 + $index * 7), 5, '0', STR_PAD_LEFT),
                'name' => $name, 'brand' => $brand, 'category' => $category, 'unit' => $unit,
                'price' => $price, 'sell_price' => $sell, 'min_stock' => 3,
                'qty' => $stock, 'location' => chr(65 + intdiv($index, 10)).'-'.(($index % 10) + 1),
                'is_active' => true,
            ]);

            $this->parts[] = $part;

            StockMovement::create([
                'part_id' => $part->id, 'type' => 'in', 'qty' => $stock, 'price' => $price,
                'note' => 'Первичная закупка', 'user_id' => 1,
                'created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6),
            ]);
        }
    }

    /* ---------------- Клиенты и автомобили ---------------- */

    /**
     * @return array{0: array<int, Customer>, 1: array<int, Vehicle>}
     */
    private function createCustomersAndVehicles(): array
    {
        $people = [
            ['Иван Петрович Смирнов', '+7 903 201-11-01', '2ГИС', 5],
            ['Ольга Николаевна Волкова', '+7 903 201-11-02', 'Рекомендация', 0],
            ['Дмитрий Александрович Кузнецов', '+7 903 201-11-03', 'Сайт', 0],
            ['Екатерина Львовна Белова', '+7 903 201-11-04', '2ГИС', 3],
            ['Артём Игоревич Фёдоров', '+7 903 201-11-05', 'Рекомендация', 0],
            ['Наталья Викторовна Тарасова', '+7 903 201-11-06', 'Инстаграм', 7],
            ['Павел Андреевич Гончаров', '+7 903 201-11-07', 'Карты', 0],
            ['Светлана Юрьевна Мельник', '+7 903 201-11-08', 'Рекомендация', 2],
            ['Константин Олегович Зайцев', '+7 903 201-11-09', 'Сайт', 0],
            ['Юлия Сергеевна Панкратова', '+7 903 201-11-10', '2ГИС', 0],
            ['Виктор Степанович Голубев', '+7 903 201-11-11', 'Карты', 0],
            ['Анна Дмитриевна Соколова', '+7 903 201-11-12', 'Рекомендация', 5],
            ['Максим Русланович Данилов', '+7 903 201-11-13', '2ГИС', 0],
            ['Ирина Борисовна Лаврова', '+7 903 201-11-14', 'Сайт', 3],
            ['Тимур Наилевич Ахметов', '+7 903 201-11-15', 'Рекомендация', 0],
            ['Людмила Фёдоровна Рыбина', '+7 903 201-11-16', 'Карты', 0],
            ['Егор Валерьевич Крылов', '+7 903 201-11-17', '2ГИС', 0],
            ['Вероника Игоревна Шестакова', '+7 903 201-11-18', 'Инстаграм', 0],
            ['Роман Валерьевич Титов', '+7 903 201-11-19', 'Сайт', 0],
            ['Ксения Андреевна Жукова', '+7 903 201-11-20', 'Рекомендация', 2],
        ];

        $companies = [
            ['ООО «Такси-Прогресс»', '+7 495 210-20-01', 'Корпоративный', 10],
            ['ИП Григорьев А.В.', '+7 903 301-20-02', 'Карты', 5],
            ['ООО «Ромашка+»', '+7 495 210-20-03', 'Рекомендация', 8],
            ['Автопарк «Деловые решения»', '+7 495 210-20-04', 'Корпоративный', 12],
        ];

        $brands = [
            ['Toyota', ['Camry', 'Corolla', 'RAV4', 'Land Cruiser']],
            ['Lada', ['Vesta', 'Granta', 'Niva', 'Datsun']],
            ['Kia', ['Rio', 'Ceed', 'Sportage', 'K5']],
            ['Hyundai', ['Solaris', 'Accent', 'Tucson', 'Creta']],
            ['Volkswagen', ['Polo', 'Jetta', 'Passat', 'Tiguan']],
            ['Mazda', ['3', '6', 'CX-5', 'BT']],
            ['Nissan', ['Almera', 'Qashqai', 'X-Trail', 'Note']],
            ['BMW', ['318i', '320d', 'X3', 'X5']],
            ['Mercedes-Benz', ['C200', 'E300', 'GLC', 'Vito']],
            ['Skoda', ['Octavia', 'Rapid', 'Kodiaq', 'Karoq']],
            ['Renault', ['Logan', 'Sandero', 'Duster', 'Megane']],
            ['Chevrolet', ['Cruze', 'Aveo', 'Niva', 'Captiva']],
        ];

        $colors = ['белый', 'чёрный', 'серебристый', 'серый', 'синий', 'красный', 'зелёный', 'бежевый'];
        $fuelOptions = ['Бензин', 'Бензин', 'Бензин', 'Дизель', 'Гибрид'];
        $transOptions = ['Механика', 'Автомат', 'Автомат', 'Робот'];

        $customers = [];
        $vehicles = [];

        $build = function (string $name, string $phone, string $type, string $source, int $discount, ?string $company) use (
            $brands, $colors, $fuelOptions, $transOptions, &$customers, &$vehicles
        ) {
            $customer = Customer::create([
                'name' => $type === 'company' ? 'Представитель компании' : $name,
                'phone' => preg_replace('/\D/', '', $phone),
                'email' => Str::slug($company ?: $name, '.').'@mail.ru',
                'type' => $type,
                'company' => $company,
                'source' => $source,
                'discount' => $discount,
                'address' => 'г. Москва, ул. Автозаводская, '.mt_rand(1, 40),
                'created_at' => now()->subDays(mt_rand(30, 700)),
            ]);

            $customers[] = $customer;

            $count = mt_rand(1, 2);
            for ($i = 0; $i < $count; $i++) {
                [$brand, $models] = $brands[array_rand($brands)];
                $model = $models[array_rand($models)];
                $year = mt_rand(2006, (int) date('Y'));
                $mileage = mt_rand(15, 320) * 1000;

                $vehicle = Vehicle::create([
                    'customer_id' => $customer->id,
                    'brand' => $brand,
                    'model' => $model,
                    'year' => $year,
                    'plate' => strtoupper(chr(65 + mt_rand(0, 25)).mt_rand(100, 999).chr(65 + mt_rand(0, 25)).mt_rand(10, 99).chr(65 + mt_rand(0, 25)).mt_rand(10, 99)),
                    'vin' => strtoupper(Str::random(17)),
                    'color' => $colors[array_rand($colors)],
                    'engine' => round(mt_rand(12, 50) / 10, 1),
                    'transmission' => $transOptions[array_rand($transOptions)],
                    'fuel' => $fuelOptions[array_rand($fuelOptions)],
                    'mileage' => $mileage,
                    'next_service_mileage' => $mileage + mt_rand(2, 20) * 1000,
                    'next_service_date' => now()->addDays(mt_rand(-15, 120))->format('Y-m-d'),
                    'created_at' => now()->subDays(mt_rand(30, 700)),
                ]);

                $vehicles[] = $vehicle;
            }
        };

        foreach ($people as [$name, $phone, $source, $discount]) {
            $build($name, $phone, 'person', $source, $discount, null);
        }
        foreach ($companies as [$company, $phone, $source, $discount]) {
            $build('', $phone, 'company', $source, $discount, $company);
        }

        return [$customers, $vehicles];
    }

    /* ---------------- Заказ-наряды ---------------- */

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<int, Vehicle>  $vehicles
     */
    private function createOrders(array $customers, array $vehicles): void
    {
        $complaints = [
            'Стук в передней подвеске на неровностях',
            'Масло подтекает под двигателем',
            'Педаль тормоза стала ватной',
            'Перегрев, стрелка температуры заходит в красную зону',
            'Скрип при повороте руля',
            'Горит лампа давления масла',
            'Неравномерный износ шин, руль уводит',
            'Посторонний шум при холостых оборотах',
            'Не работает кондиционер',
            'Плохо ловит радио, гудит динамик',
            'Вибрация в руле при 90-100 км/ч',
            'Запах бензина в салоне',
            'Тормозные шумы сзади',
            'Подвеска стучит на кочках',
            'Не заводится с утра, при повороте ключа',
        ];

        $today = Carbon::today();

        for ($dayOffset = 330; $dayOffset >= 0; $dayOffset--) {
            $date = $today->copy()->subDays($dayOffset);
            $weekday = (int) $date->dayOfWeekIso;

            $perDay = $weekday === 6 ? mt_rand(0, 2) : ($weekday === 7 ? mt_rand(0, 1) : mt_rand(2, 5));
            if ($dayOffset > 60 && $dayOffset % 7 === 0) {
                $perDay = 1; // больше «истории», меньше нагрузки
            }

            for ($i = 0; $i < $perDay; $i++) {
                $this->createOrder($date, $customers, $vehicles, $complaints);
            }
        }

        $this->command?->info('Создано нарядов: '.Order::count());
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<int, Vehicle>  $vehicles
     * @param  array<int, string>  $complaints
     */
    private function createOrder(Carbon $date, array $customers, array $vehicles, array $complaints): void
    {
        $vehicle = $vehicles[array_rand($vehicles)];
        $customer = Customer::find($vehicle->customer_id);
        $post = $this->posts[array_rand($this->posts)];
        $mechanic = $this->mechanics[array_rand($this->mechanics)];
        $advisor = $this->advisors[array_rand($this->advisors)];

        $daysAgo = (int) $date->diffInDays(Carbon::today());
        $status = $this->statusFor($daysAgo);

        $intake = $date->copy()->setTime(mt_rand(8, 18), mt_rand(0, 59));
        $promised = $intake->copy()->addDays(mt_rand(1, 4))->setTime(mt_rand(12, 19), 0);
        $closed = in_array($status, ['issued', 'cancelled'], true)
            ? $intake->copy()->addHours(mt_rand(4, 60))
            : null;

        // Не выдаём машины «из будущего»: дата закрытия не может быть позже текущего момента.
        if ($closed !== null && $closed->isFuture()) {
            $closed = Carbon::now()->subMinutes(mt_rand(10, 900));
        }

        $year = $intake->year;
        $this->seq[$year] = ($this->seq[$year] ?? 0) + 1;

        $order = Order::create([
            'number' => sprintf('ЗН-%d-%04d', $year, $this->seq[$year]),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'post_id' => $post->id,
            'mechanic_id' => $mechanic->id,
            'advisor_id' => $advisor->id,
            'status' => $status,
            'mileage' => $vehicle->mileage + mt_rand(0, 1200),
            'intake_at' => $intake,
            'promised_at' => $promised,
            'closed_at' => $closed,
            'complaint' => $complaints[array_rand($complaints)],
            'note' => mt_rand(0, 6) ? null : 'Автомобиль оставлен до конца дня, ключи у приёмщика',
            'discount' => mt_rand(0, 100) === 1 ? mt_rand(3, 10) : 0,
            'is_invoice' => $customer->type === 'company',
            'created_at' => $intake,
            'updated_at' => $closed ?? $intake,
        ]);

        /* --- Работы --- */
        $categories = ['ТО', 'Ремонт', 'Диагностика', 'Шиномонтаж'];
        $workCount = mt_rand(1, 3);
        $workSum = 0.0;

        for ($w = 0; $w < $workCount; $w++) {
            $category = $categories[array_rand($categories)];
            $service = $this->services[$category][array_rand($this->services[$category])];
            $hours = (float) $service->norm_hours + mt_rand(0, 8) / 10;
            $rate = $post->hourly_rate;
            $price = round($hours * $rate * mt_rand(100, 130) / 100, 2);

            // В выданных нарядах работы почти все закрыты, в активных — ещё идут.
            $done = $status === 'issued'
                ? mt_rand(1, 100) <= 92
                : mt_rand(1, 100) <= 35;

            Work::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'name' => $service->name,
                'norm_hours' => $hours,
                'hourly_rate' => $rate,
                'price' => $price,
                'mechanic_id' => $mechanic->id,
                'status' => $done ? 'done' : 'planned',
                'completed_at' => $done ? $intake->copy()->addHours(mt_rand(1, 20)) : null,
                'created_at' => $intake,
                'updated_at' => $intake,
            ]);

            $workSum += $price;
        }

        /* --- Запчасти --- */
        $partsSum = 0.0;
        if (mt_rand(0, 100) < 70) {
            $partCount = mt_rand(1, 2);
            $used = [];

            for ($p = 0; $p < $partCount; $p++) {
                $part = $this->parts[array_rand($this->parts)];
                if (in_array($part->id, $used, true) || (float) $part->qty < 2) {
                    continue;
                }
                $used[] = $part->id;

                $qty = mt_rand(1, 4);
                if ($qty > (float) $part->qty) {
                    $qty = (int) $part->qty;
                }
                if ($qty < 1) {
                    continue;
                }

                OrderPart::create([
                    'order_id' => $order->id,
                    'part_id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'qty' => $qty,
                    'price' => (float) $part->sell_price,
                    'cost' => (float) $part->price,
                    'created_at' => $intake,
                    'updated_at' => $intake,
                ]);

                $part->decrement('qty', $qty);

                if ($status !== 'cancelled') {
                    StockMovement::create([
                        'part_id' => $part->id,
                        'type' => 'out',
                        'qty' => $qty,
                        'price' => (float) $part->price,
                        'order_id' => $order->id,
                        'user_id' => $advisor->id,
                        'note' => 'Выдано по наряду '.$order->number,
                        'created_at' => $intake,
                        'updated_at' => $intake,
                    ]);
                }

                $partsSum += $qty * (float) $part->sell_price;
            }
        }

        /* --- Итоги --- */
        $discount = max((float) $order->discount, (float) $customer->discount);
        $total = round(($workSum + $partsSum) * (1 - $discount / 100), 2);

        $paid = $this->paidAmount($status, $total, $closed ?? $intake);

        $order->update([
            'total_work' => round($workSum, 2),
            'total_parts' => round($partsSum, 2),
            'total' => $total,
            'paid' => $paid,
        ]);

        /* --- Платежи --- */
        $this->createPayments($order, $paid, $closed ?? $intake);

        /* --- Пробег автомобиля --- */
        $vehicle->update(['mileage' => max($vehicle->mileage, $order->mileage)]);
    }

    /** Подбирает статус наряда так, чтобы «сегодняшние» наряды были в работе, а давние — выданы. */
    private function statusFor(int $daysAgo): string
    {
        if ($daysAgo > 45) {
            return mt_rand(1, 100) <= 3 ? 'cancelled' : 'issued';
        }
        if ($daysAgo > 21) {
            return 'issued';
        }
        if ($daysAgo > 12) {
            return mt_rand(0, 100) <= 85 ? 'issued' : 'in_progress';
        }
        if ($daysAgo > 7) {
            $roll = mt_rand(0, 100);

            return $roll <= 65 ? 'issued' : ($roll <= 85 ? 'waiting' : 'ready');
        }
        if ($daysAgo > 3) {
            $roll = mt_rand(1, 100);

            return $roll <= 45 ? 'issued' : ($roll <= 85 ? 'in_progress' : 'waiting');
        }
        if ($daysAgo > 1) {
            $roll = mt_rand(0, 100);

            return $roll <= 45 ? 'issued' : ($roll <= 85 ? 'in_progress' : 'waiting');
        }

        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 32 => 'issued',   // выданы сегодня — чтобы витрина была живой
            $roll <= 52 => 'ready',    // готовы, ждут клиента
            $roll <= 72 => 'waiting',  // ждём запчасть
            $roll <= 88 => 'in_progress',
            default => 'new',
        };
    }

    private function paidAmount(string $status, float $total, Carbon $date): float
    {
        if ($status === 'cancelled' || $total <= 0) {
            return 0.0;
        }

        return match (mt_rand(1, 100)) {
            0, 1, 2, 3, 4, 5, 6 => 0.0,
            7, 8, 9, 10, 11, 12 => round($total * mt_rand(30, 80) / 100, 2),
            13 => round($total * mt_rand(100, 110) / 100, 2),
            default => $total,
        };
    }

    private function createPayments(Order $order, float $paid, Carbon $date): void
    {
        if ($paid <= 0) {
            return;
        }

        if ($paid > (float) $order->total) {
            Payment::create([
                'order_id' => $order->id, 'customer_id' => $order->customer_id, 'user_id' => $order->advisor_id,
                'amount' => $paid, 'method' => 'card', 'paid_at' => $date, 'note' => 'Полная оплата',
            ]);

            return;
        }

        $first = round($paid * mt_rand(50, 100) / 100, 2);
        $second = round($paid - $first, 2);

        foreach ([[$first, 0], [$second, 1]] as [$amount, $index]) {
            if ($amount <= 0) {
                continue;
            }

            $paidAt = $date->copy()->addHours(mt_rand(1, 48));
            if ($paidAt->isFuture()) {
                $paidAt = Carbon::now()->subMinutes(mt_rand(5, 600));
            }

            Payment::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'user_id' => $order->advisor_id,
                'amount' => $amount,
                'method' => ['cash', 'card', 'card', 'transfer'][mt_rand(0, 3)],
                'paid_at' => $paidAt,
                'note' => $index === 0 ? 'Предоплата' : null,
            ]);
        }
    }
}
