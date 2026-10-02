<?php
/**
 * Run once: php tools/generate_listings_seed.php
 */

declare(strict_types=1);

$outputFile = __DIR__ . '/../sql/seed_sample_listings.sql';

$count = 500;
$lenders = [2, 3];
$locations = [
    'Lipa City, Batangas',
    'Batangas City',
    'Tanauan City, Batangas',
    'Santo Tomas, Batangas',
    'Malvar, Batangas',
    'Mataas na Kahoy, Batangas',
    'San Jose, Batangas',
    'Rosario, Batangas',
    'Ibaan, Batangas',
    'Padre Garcia, Batangas',
    'Balete, Batangas',
    'Talisay, Batangas',
];
$conditions = ['Like New', 'Good', 'Fair', 'Used'];

$templates = [
    1 => [
        ['Raspberry Pi 4 Kit #%d', 'Pi 4, microSD, power supply, case, GPIO kit', 45, 270],
        ['Arduino Uno R3 Bundle #%d', 'Uno R3, breadboard, sensors, jumper wires', 30, 180],
        ['ESP32 Dev Board Pack #%d', 'ESP32-WROOM, OLED, DHT22, ultrasonic sensor', 40, 240],
        ['NodeMCU IoT Starter #%d', 'WiFi module, relay, temp sensor, USB cable', 35, 210],
        ['Raspberry Pi Pico W #%d', 'Pico W, headers, USB cable, sample projects', 25, 150],
    ],
    2 => [
        ['Samsung Galaxy S21 Test Phone #%d', 'Unlocked, Android 13+, for app QA testing', 100, 600],
        ['iPhone 13 Test Unit #%d', 'Factory reset, iOS testing, good battery', 150, 900],
        ['Xiaomi Redmi Note #%d', 'Budget Android test device, dual SIM', 80, 480],
        ['Google Pixel %d QA Unit', 'Stock Android, developer options enabled', 120, 720],
    ],
    3 => [
        ['MacBook Pro 14" Rental #%d', 'M-series, 16GB RAM, charger included', 420, 2600],
        ['Dell XPS 15 Dev Laptop #%d', 'i7, 16GB, 512GB SSD, Windows/Linux dual boot', 350, 2100],
        ['Lenovo ThinkPad #%d', '16GB RAM, ideal for coding and lab work', 280, 1680],
        ['ASUS ROG Zephyrus #%d', 'RTX laptop for game dev and rendering', 380, 2280],
        ['HP Pavilion %d', '8GB RAM, suitable for web dev and docs', 200, 1200],
    ],
    4 => [
        ['NVIDIA RTX 3060 #%d', 'Graphics card for ML, CUDA, rendering projects', 260, 1560],
        ['AMD RX 6700 XT #%d', 'GPU for compute and gaming dev testing', 220, 1320],
        ['GTX 1660 Super #%d', 'Budget GPU for intro ML and graphics coursework', 150, 900],
    ],
    5 => [
        ['Meta Quest 2 #%d', 'VR headset + controllers for immersive dev', 300, 1800],
        ['PS VR2 Kit #%d', 'VR headset for Unity/Unreal prototyping', 280, 1680],
        ['Oculus Rift S #%d', 'PC VR headset with touch controllers', 200, 1200],
    ],
    6 => [
        ['Canon EOS M50 Kit #%d', 'Mirrorless camera, lens, spare battery, SD card', 320, 1920],
        ['Sony A6400 Body #%d', 'Mirrorless for video and photo projects', 350, 2100],
        ['GoPro Hero 11 #%d', 'Action cam for field recording and vlogs', 180, 1080],
        ['Logitech Brio 4K #%d', 'Webcam for streaming and online demos', 90, 540],
    ],
    7 => [
        ['Blue Yeti Mic #%d', 'USB condenser mic for recordings and podcasts', 85, 510],
        ['Audio-Technica AT2020 #%d', 'Studio mic with stand and pop filter', 90, 550],
        ['Focusrite Scarlett 2i2 #%d', 'Audio interface for music and podcast lab', 110, 660],
        ['Rode VideoMic #%d', 'Shotgun mic for video production', 95, 570],
    ],
];

$imageMap = [
    1 => 'assets/img/listings/iot.svg',
    2 => 'assets/img/listings/phone.svg',
    3 => 'assets/img/listings/laptop.svg',
    4 => 'assets/img/listings/gpu.svg',
    5 => 'assets/img/listings/vr.svg',
    6 => 'assets/img/listings/camera.svg',
    7 => 'assets/img/listings/audio.svg',
];

$statuses = array_merge(
    array_fill(0, (int) ($count * 0.82), 'Available'),
    array_fill(0, (int) ($count * 0.10), 'Pending'),
    array_fill(0, (int) ($count * 0.08), 'Rented')
);
shuffle($statuses);

ob_start();

echo "-- LipaByte sample listings seed (~{$count} items)\n";
echo "-- InfinityFree: select YOUR database in phpMyAdmin left sidebar, then run this.\n";
echo "-- DO NOT use USE lipabyte_db;\n\n";
echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET time_zone = '+08:00';\n\n";

echo "-- Clear old sample listings (keeps users/categories). Adjust IDs if needed.\n";
echo "DELETE FROM `listing_images` WHERE `listing_id` > 9;\n";
echo "DELETE FROM `rental_requests` WHERE `listing_id` > 9;\n";
echo "DELETE FROM `listings` WHERE `listing_id` > 9;\n\n";

$listingsSql = [];
$imagesSql = [];
$listingId = 10;

for ($i = 0; $i < $count; $i++) {
    $catId = ($i % 7) + 1;
    $tplList = $templates[$catId];
    $tpl = $tplList[$i % count($tplList)];
    $num = 100 + $i;
    $name = sprintf($tpl[0], $num);
    $specs = $tpl[1] . '. Campus rental - handle with care, return on agreed date.';
    $daily = $tpl[2] + ($i % 5) * 5;
    $weekly = $tpl[3] + ($i % 4) * 20;
    $lender = $lenders[$i % 2];
    $location = $locations[$i % count($locations)];
    $condition = $conditions[$i % count($conditions)];
    $status = $statuses[$i] ?? 'Available';

    $nameEsc = addslashes($name);
    $specsEsc = addslashes($specs);
    $locEsc = addslashes($location);

    $listingsSql[] = "({$listingId}, {$lender}, {$catId}, '{$nameEsc}', '{$specsEsc}', {$daily}.00, {$weekly}.00, '{$condition}', '{$locEsc}', '{$status}', 0, NOW())";
    $img = $imageMap[$catId];
    $imagesSql[] = "({$listingId}, '{$img}', 1, 1, NOW())";
    $listingId++;
}

echo "INSERT INTO `listings` (`listing_id`, `lender_id`, `category_id`, `item_name`, `specifications`, `daily_rate`, `weekly_rate`, `item_condition`, `location`, `availability_status`, `is_deleted`, `created_at`) VALUES\n";
echo implode(",\n", $listingsSql) . ";\n\n";

echo "INSERT INTO `listing_images` (`listing_id`, `image_url`, `display_order`, `is_primary`, `uploaded_at`) VALUES\n";
echo implode(",\n", $imagesSql) . ";\n";

$sql = ob_get_clean();
file_put_contents($outputFile, $sql);
echo "Wrote " . strlen($sql) . " bytes to sql/seed_sample_listings.sql ({$count} listings)\n";
