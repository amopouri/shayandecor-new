<?php
declare(strict_types=1);
/**
 * هستهٔ مشترک همهٔ APIها: اتصال دیتابیس، Session، CSRF، خروجی JSON، آپلود امن.
 */
define('ROOT', dirname(__DIR__));
const SESSION_TTL = 7200;          // ۲ ساعت عدم فعالیت
const MAX_UPLOAD  = 5 * 1024 * 1024; // ۵ مگابایت

ini_set('display_errors', '0');
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tehran');

function json_out(array $data, int $code = 200): never {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function ok(string $message = 'عملیات با موفقیت انجام شد', array $extra = []): never {
    json_out(['success' => true, 'message' => $message] + $extra);
}
function fail(string $message = 'خطایی رخ داده است', int $code = 400, array $extra = []): never {
    json_out(['success' => false, 'message' => $message] + $extra, $code);
}

set_exception_handler(function (Throwable $e) {
    error_log('[shayandecor] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail('خطایی در سرور رخ داد. لطفاً دوباره تلاش کنید.', 500);
});
set_error_handler(function ($no, $str, $file, $line) {
    if (!(error_reporting() & $no)) return false;
    throw new ErrorException($str, 0, $no, $file, $line);
});

if (!is_file(ROOT . '/config/config.php')) {
    fail('سایت هنوز نصب نشده است. ابتدا install.php را اجرا کنید.', 503);
}
require ROOT . '/config/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET time_zone = '+03:30'");
    } catch (PDOException $e) {
        error_log('[shayandecor] DB connect: ' . $e->getMessage());
        fail('اتصال به پایگاه داده برقرار نشد.', 503);
    }
    return $pdo;
}
function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ───────── Session ───────── */
function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('SDSESS');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => is_https(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
    if (isset($_SESSION['last']) && time() - $_SESSION['last'] > SESSION_TTL) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (!empty($_SESSION['uid'])) $_SESSION['last'] = time();
}
start_session();

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $u = false;
    if ($u === false) {
        $u = q('SELECT id,name,username,role FROM users WHERE id=? AND status=1', [$_SESSION['uid']])->fetch() ?: null;
        if (!$u) { $_SESSION = []; }
    }
    return $u;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_method(string $m): void {
    if ($_SERVER['REQUEST_METHOD'] !== $m) fail('متد درخواست نامعتبر است.', 405);
}
/** ورود اجباری + CSRF برای درخواست‌های تغییردهنده */
function require_admin(bool $csrf = true): array {
    $u = current_user();
    if (!$u) fail('برای انجام این کار باید وارد شوید.', 401);
    if ($csrf && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) fail('توکن امنیتی نامعتبر است. صفحه را دوباره بارگذاری کنید.', 403);
    }
    return $u;
}

/* ───────── Input ───────── */
function input(): array {
    static $in = null;
    if ($in !== null) return $in;
    $raw = file_get_contents('php://input');
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if ($raw !== '' && stripos($ct, 'application/json') !== false) {
        $j = json_decode($raw, true);
        $in = is_array($j) ? $j : [];
    } else {
        $in = $_POST;
    }
    return $in;
}
function s(mixed $v, int $max = 255): string {
    $v = trim((string)($v ?? ''));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? '';
    return mb_substr($v, 0, $max);
}
function n(mixed $v, int $min = 0, int $max = PHP_INT_MAX): int {
    return max($min, min($max, (int)$v));
}
function b(mixed $v): int { return in_array($v, [1, '1', true, 'true', 'on'], true) ? 1 : 0; }
function to_persian_digits(string $s): string { return strtr($s, '0123456789', '۰۱۲۳۴۵۶۷۸۹'); }
function from_persian_digits(string $s): string { return strtr($s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','٬'=>'',',' =>'']); }
function make_slug(string $s): string {
    $s = trim(preg_replace('/[\s\/\\\\?#%&"\'<>]+/u', '-', $s) ?? '', '-');
    return mb_substr($s !== '' ? $s : 'item', 0, 120);
}
function unique_slug(string $table, string $base, int $exceptId = 0): string {
    if (!in_array($table, ['products', 'categories'], true)) throw new InvalidArgumentException('table');
    $slug = $base; $i = 2;
    while (q("SELECT 1 FROM $table WHERE slug=? AND id<>?", [$slug, $exceptId])->fetch()) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45); }

/* ───────── Settings ───────── */
const PUBLIC_SETTINGS = ['site_name','site_tagline','logo','phone','phone_raw','mobile','whatsapp','email','address','instagram','hero_title','hero_subtitle','about_text','footer_text','primary_color','default_theme'];
const ALL_SETTINGS    = ['site_name','site_tagline','logo','phone','phone_raw','mobile','whatsapp','email','address','instagram','hero_title','hero_subtitle','about_text','footer_text','primary_color','default_theme','maintenance'];
function settings_all(): array {
    $out = [];
    foreach (q('SELECT setting_key,setting_value FROM settings')->fetchAll() as $r) $out[$r['setting_key']] = $r['setting_value'];
    return $out;
}

/* ───────── Uploads ───────── */
const UPLOAD_DIRS = ['products', 'categories', 'site'];

/** مسیر نسبی امن (فقط داخل uploads/) یا URL خارجی http(s) */
function is_local_upload(string $p): bool { return (bool)preg_match('#^uploads/(products|categories|site)/[A-Za-z0-9_\-]+\.(jpg|png|webp)$#', $p); }
function delete_upload(?string $path): void {
    if (!$path || !is_local_upload($path)) return;   // لینک‌های خارجی و مسیرهای مشکوک نادیده گرفته می‌شوند
    $full = ROOT . '/' . $path;
    $real = realpath($full);
    $base = realpath(ROOT . '/uploads');
    if ($real && $base && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) @unlink($real);
}
function valid_image_ref(string $p): bool {
    if ($p === '' || is_local_upload($p)) return true;
    if (!preg_match('#^https://[^\s"\'<>]{4,480}$#i', $p)) return false;
    $u = parse_url($p);
    return is_array($u) && strtolower($u['scheme'] ?? '') === 'https' && !empty($u['host']);
}
/**
 * آپلود امن یک فایل تصویری. خروجی: مسیر نسبی مثل uploads/products/product_ab12.webp
 * @param array $f عنصر $_FILES
 */
function save_image(array $f, string $dir): string {
    if (!in_array($dir, UPLOAD_DIRS, true)) throw new RuntimeException('پوشهٔ نامعتبر');
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $m = [UPLOAD_ERR_INI_SIZE => 'حجم فایل بیشتر از حد مجاز سرور است.', UPLOAD_ERR_FORM_SIZE => 'حجم فایل زیاد است.', UPLOAD_ERR_NO_FILE => 'فایلی انتخاب نشده است.'];
        throw new RuntimeException($m[$f['error'] ?? 4] ?? 'آپلود ناموفق بود.');
    }
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('فایل نامعتبر است.');
    if ($f['size'] > MAX_UPLOAD) throw new RuntimeException('حجم هر تصویر حداکثر ۵ مگابایت است.');
    // ۱) پسوند
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) throw new RuntimeException('فقط JPG، PNG و WEBP مجاز است.');
    // ۲) MIME واقعی از محتوای فایل
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($map[$mime])) throw new RuntimeException('نوع فایل مجاز نیست.');
    // ۳) واقعاً تصویر باشد
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 60000000) throw new RuntimeException('فایل تصویر معتبر نیست.');
    $name = 'product_' . bin2hex(random_bytes(8));
    if ($dir === 'categories') $name = 'category_' . bin2hex(random_bytes(8));
    if ($dir === 'site') $name = 'site_' . bin2hex(random_bytes(8));
    $destDir = ROOT . '/uploads/' . $dir;
    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) throw new RuntimeException('پوشهٔ uploads قابل نوشتن نیست.');
    // ۴) بهینه‌سازی با GD (بازنویسی کامل تصویر، هر محتوای اضافه را هم پاک می‌کند)
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $img = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if ($img) {
            $w = imagesx($img); $h = imagesy($img); $max = 1600;
            if ($w > $max || $h > $max) {
                $r = min($max / $w, $max / $h);
                $nw = max(1, (int)round($w * $r)); $nh = max(1, (int)round($h * $r));
                $res = imagecreatetruecolor($nw, $nh);
                imagealphablending($res, false); imagesavealpha($res, true);
                imagecopyresampled($res, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img); $img = $res;
            }
            imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true);
            $dest = $destDir . '/' . $name . '.webp';
            if (@imagewebp($img, $dest, 82)) { imagedestroy($img); @chmod($dest, 0644); return "uploads/$dir/$name.webp"; }
            imagedestroy($img);
        }
    }
    // بدون GD: ذخیره فایل اصلی با پسوند مطابق MIME واقعی (نه نام کاربر)
    $dest = $destDir . '/' . $name . '.' . $map[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('ذخیرهٔ فایل ناموفق بود.');
    @chmod($dest, 0644);
    return "uploads/$dir/$name." . $map[$mime];
}
/** آرایهٔ $_FILES چندگانه را به فهرست تک‌فایلی تبدیل می‌کند */
function files_list(string $field): array {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return [$f];
    $out = [];
    foreach ($f['name'] as $i => $_) $out[] = ['name' => $f['name'][$i], 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    return $out;
}

/* ───────── Products helpers ───────── */
function decode_list(?string $j): array {
    $a = json_decode((string)$j, true);
    return is_array($a) ? array_values(array_filter($a, 'is_string')) : [];
}
function images_for(array $ids): array {
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $out = [];
    foreach (q("SELECT id,product_id,image_path,is_primary,sort_order FROM product_images WHERE product_id IN ($in) ORDER BY is_primary DESC, sort_order, id", $ids)->fetchAll() as $r) {
        $out[(int)$r['product_id']][] = ['id' => (int)$r['id'], 'path' => $r['image_path'], 'is_primary' => (int)$r['is_primary'], 'sort_order' => (int)$r['sort_order']];
    }
    return $out;
}
function shape_product(array $r, array $imgs): array {
    $id = (int)$r['id'];
    return [
        'id' => $id, 'category_id' => $r['category_id'] !== null ? (int)$r['category_id'] : null, 'category_name' => $r['category_name'] ?? null,
        'name' => $r['name'], 'slug' => $r['slug'], 'short_description' => $r['short_description'], 'description' => $r['description'], 'features' => $r['features'],
        'price' => (int)$r['price'], 'old_price' => $r['old_price'] !== null ? (int)$r['old_price'] : null, 'discount' => (int)$r['discount'],
        'stock' => (int)$r['stock'], 'sku' => $r['sku'], 'shaneh' => $r['shaneh'], 'sizes' => decode_list($r['size']), 'colors' => decode_list($r['color']),
        'rating' => (float)$r['rating'], 'reviews_count' => (int)$r['reviews_count'],
        'is_new' => (int)$r['is_new'], 'is_featured' => (int)$r['is_featured'], 'is_active' => (int)$r['is_active'],
        'created_at' => $r['created_at'], 'images' => $imgs[$id] ?? [], 'image' => ($imgs[$id][0]['path'] ?? null),
    ];
}

/* ───────── Generic content (testimonials, faqs, blog, attributes) ───────── */
const CONTENT = [
    'testimonials' => ['table' => 'testimonials', 'cols' => ['name' => 'str:120', 'city' => 'str:120', 'initials' => 'str:10', 'text' => 'str:2000', 'rating' => 'int:1:5', 'sort_order' => 'int:0:9999', 'is_active' => 'bool'], 'required' => ['name', 'text'], 'order' => 'sort_order,id'],
    'faqs'         => ['table' => 'faqs', 'cols' => ['question' => 'str:500', 'answer' => 'str:4000', 'sort_order' => 'int:0:9999', 'is_active' => 'bool'], 'required' => ['question', 'answer'], 'order' => 'sort_order,id'],
    'blog'         => ['table' => 'blog_posts', 'cols' => ['title' => 'str:250', 'slug' => 'str:260', 'excerpt' => 'str:1000', 'image' => 'img', 'category' => 'str:80', 'read_time' => 'str:40', 'published_label' => 'str:60', 'sort_order' => 'int:0:9999', 'is_published' => 'bool'], 'required' => ['title'], 'order' => 'sort_order,id'],
    'attributes'   => ['table' => 'attributes', 'cols' => ['type' => 'enum:color,size,shaneh', 'name' => 'str:120', 'value' => 'str:20', 'sort_order' => 'int:0:9999'], 'required' => ['type', 'name'], 'order' => 'type,sort_order,id'],
];
function content_def(): array {
    $t = $_GET['type'] ?? (input()['type'] ?? '');
    if (!isset(CONTENT[$t])) fail('نوع محتوا نامعتبر است.', 400);
    return CONTENT[$t];
}
