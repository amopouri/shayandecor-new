<?php
declare(strict_types=1);
/**
 * نصب‌کنندهٔ شایان دکور — یک‌بار اجرا می‌شود و سپس قفل می‌گردد.
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
mb_internal_encoding('UTF-8');
session_start();

const ROOT = __DIR__;
$LOCK = ROOT . '/install_lock.php';
$CONFIG = ROOT . '/config/config.php';

if (is_file($LOCK) || is_file($CONFIG)) {
    http_response_code(403);
    exit('<!DOCTYPE html><html lang="fa" dir="rtl"><meta charset="utf-8"><body style="font-family:Tahoma;text-align:center;padding:60px 20px">نصب قبلاً انجام شده و این صفحه قفل شده است.<br>اگر فایل <code>config/config.php</code> وجود دارد، نصب‌کننده غیرفعال است.<br>برای نصب مجدد فقط در صورت اطمینان کامل، فایل‌های <code>install_lock.php</code> و در صورت نیاز <code>config/config.php</code> را حذف کنید.</body></html>');
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function step_url(int $s): string { return '?step=' . $s; }

$step = (int)($_GET['step'] ?? 1);
$err = '';
$data = $_SESSION['install'] ?? [];

/* ─── مرحله ۲: بررسی نیازمندی‌ها ─── */
function checks(): array {
    $u = ROOT;
    return [
        ['PHP نسخهٔ ۸٫۱ یا بالاتر', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION],
        ['افزونهٔ PDO MySQL', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'فعال' : 'غیرفعال'],
        ['افزونهٔ mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'فعال' : 'غیرفعال'],
        ['افزونهٔ fileinfo', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'فعال' : 'غیرفعال'],
        ['افزونهٔ GD (اختیاری، برای بهینه‌سازی تصاویر)', extension_loaded('gd'), extension_loaded('gd') ? 'فعال' : 'غیرفعال — آپلود بدون بهینه‌سازی کار می‌کند', true],
        ['پوشهٔ config/ قابل نوشتن', is_writable(ROOT . '/config'), is_writable(ROOT . '/config') ? 'قابل نوشتن' : 'غیرقابل نوشتن'],
        ['پوشهٔ uploads/products/ قابل نوشتن', is_writable(ROOT . '/uploads/products'), is_writable(ROOT . '/uploads/products') ? 'قابل نوشتن' : 'غیرقابل نوشتن'],
        ['پوشهٔ uploads/categories/ قابل نوشتن', is_writable(ROOT . '/uploads/categories'), is_writable(ROOT . '/uploads/categories') ? 'قابل نوشتن' : 'غیرقابل نوشتن'],
        ['پوشهٔ uploads/site/ قابل نوشتن', is_writable(ROOT . '/uploads/site'), is_writable(ROOT . '/uploads/site') ? 'قابل نوشتن' : 'غیرقابل نوشتن'],
    ];
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['db_host'] = trim($_POST['db_host'] ?? 'localhost');
    $data['db_name'] = trim($_POST['db_name'] ?? '');
    $data['db_user'] = trim($_POST['db_user'] ?? '');
    $data['db_pass'] = (string)($_POST['db_pass'] ?? '');
    if ($data['db_name'] === '' || $data['db_user'] === '') {
        $err = 'نام دیتابیس و نام کاربری الزامی است.';
        $step = 3;
    } else {
        try {
            $pdo = new PDO('mysql:host=' . $data['db_host'] . ';dbname=' . $data['db_name'] . ';charset=utf8mb4', $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $sql = file_get_contents(ROOT . '/database.sql');
            if ($sql === false) throw new RuntimeException('فایل database.sql پیدا نشد.');
            $exists = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='products'")->fetchColumn();
            if ($exists > 0 && empty($_POST['confirm_overwrite'])) {
                $err = 'به نظر می‌رسد این دیتابیس قبلاً جداول سایت را دارد. اگر مطمئن هستید ادامه دهید، جداول قبلی حذف و بازسازی می‌شوند.';
                $data['need_confirm'] = true;
                $_SESSION['install'] = $data;
                $step = 3;
            } else {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                    if ($stmt !== '' && stripos(ltrim($stmt), '--') !== 0) $pdo->exec($stmt);
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                $_SESSION['install'] = $data;
                $_SESSION['install']['db_ok'] = true;
                header('Location: ' . step_url(4));
                exit;
            }
        } catch (Throwable $e) {
            $err = 'اتصال یا اجرای SQL ناموفق بود: ' . esc($e->getMessage());
            $step = 3;
            $_SESSION['install'] = $data;
        }
    }
}

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_SESSION['install'] ?? [];
    if (empty($data['db_ok'])) { header('Location: ' . step_url(3)); exit; }
    $name = trim($_POST['admin_name'] ?? '');
    $user = trim($_POST['admin_user'] ?? '');
    $pass = (string)($_POST['admin_pass'] ?? '');
    $rep  = (string)($_POST['admin_pass2'] ?? '');
    if ($name === '' || $user === '') $err = 'نام و نام کاربری الزامی است.';
    elseif (!preg_match('/^[A-Za-z0-9_.\-]{3,60}$/', $user)) $err = 'نام کاربری فقط می‌تواند شامل حروف لاتین، عدد، نقطه، خط تیره و زیرخط باشد (حداقل ۳ نویسه).';
    elseif (mb_strlen($pass) < 8) $err = 'رمز عبور باید حداقل ۸ نویسه باشد.';
    elseif ($pass !== $rep) $err = 'تکرار رمز عبور مطابقت ندارد.';
    if ($err === '') {
        try {
            $pdo = new PDO('mysql:host=' . $data['db_host'] . ';dbname=' . $data['db_name'] . ';charset=utf8mb4', $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $st = $pdo->prepare('INSERT INTO users (name, username, password, role, status) VALUES (?,?,?,\'admin\',1) ON DUPLICATE KEY UPDATE password=VALUES(password), name=VALUES(name)');
            $st->execute([$name, $user, password_hash($pass, PASSWORD_DEFAULT)]);
            $cfg = "<?php\n/** تنظیمات اتصال به دیتابیس — توسط نصب‌کننده ساخته شد. */\n"
                 . "define('DB_HOST', " . var_export($data['db_host'], true) . ");\n"
                 . "define('DB_NAME', " . var_export($data['db_name'], true) . ");\n"
                 . "define('DB_USER', " . var_export($data['db_user'], true) . ");\n"
                 . "define('DB_PASS', " . var_export($data['db_pass'], true) . ");\n";
            if (!@file_put_contents(ROOT . '/config/config.php', $cfg)) throw new RuntimeException('نوشتن فایل config/config.php ممکن نشد.');
            @chmod(ROOT . '/config/config.php', 0640);
            $lock = "<?php\n// نصب در " . date('c') . " انجام شد. برای نصب مجدد این فایل را حذف کنید.\nhttp_response_code(403); exit('نصب قبلاً انجام شده است.');\n";
            if (!@file_put_contents($LOCK, $lock)) {
                @unlink(ROOT . '/config/config.php');
                throw new RuntimeException('نوشتن فایل قفل (install_lock.php) ممکن نشد. مجوز نوشتن ریشهٔ سایت را بررسی کنید. نصب متوقف شد.');
            }
            unset($_SESSION['install']);
            $_SESSION['install_done'] = ['user' => $user];
            header('Location: ' . step_url(5));
            exit;
        } catch (Throwable $e) {
            $err = 'ساخت مدیر ناموفق بود: ' . esc($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>نصب شایان دکور</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--g:#C9A961;--ink:#15151A;--bg:#F4F1EA;--line:#E6E0D2;--red:#C0392B;--grn:#2E8B57}
*{box-sizing:border-box}body{margin:0;font-family:Vazirmatn,Tahoma;background:var(--bg);color:#1a1a1a;padding:24px}
.wrap{max-width:640px;margin:0 auto}
.steps{display:flex;gap:6px;margin-bottom:22px}
.steps span{flex:1;height:6px;border-radius:99px;background:#E6E0D2}
.steps span.on{background:var(--g)}
.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px}
h1{font-size:22px;margin:0 0 6px}.sub{color:#6B675C;margin:0 0 20px;font-size:14px}
label{display:block;font-weight:600;font-size:13px;margin:14px 0 5px}
input,select{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font:inherit}
.btn{display:inline-block;margin-top:20px;padding:11px 26px;border-radius:10px;border:1px solid var(--g);background:var(--g);color:#1a1a1a;font-weight:700;cursor:pointer;font:inherit;text-decoration:none}
.btn.gh{background:#fff;color:#1a1a1a}
.chk{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line);font-size:14px}
.ok{color:var(--grn);font-weight:700}.bad{color:var(--red);font-weight:700}
.err{background:#FBE0DC;color:var(--red);padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13.5px}
.note{font-size:12.5px;color:#6B675C;margin-top:16px}
code{background:#F4F1EA;padding:1px 6px;border-radius:6px}
</style>
<link rel="stylesheet" href="assets/css/luxury.css">
</head>
<body><div class="wrap">
<div class="steps">
<?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= $step ? 'on' : '' ?>"></span><?php endfor; ?>
</div>
<div class="card">
<?php if ($err): ?><div class="err"><?= esc($err) ?></div><?php endif; ?>

<?php if ($step === 1): ?>
  <h1>نصب شایان دکور</h1>
  <p class="sub">به نصب‌کنندهٔ سایت شایان دکور خوش آمدید. این فرایند دیتابیس را می‌سازد و یک حساب مدیر ایجاد می‌کند.</p>
  <p>قبل از شروع، اطلاعات دیتابیس MySQL را از پنل هاست (مثلاً cPanel → MySQL Databases) آماده کنید.</p>
  <a class="btn" href="<?= step_url(2) ?>">شروع نصب</a>

<?php elseif ($step === 2):
  $rows = checks(); $hardFail = false;
  foreach ($rows as $r) { $optional = !empty($r[3]); if (!$r[1] && !$optional) $hardFail = true; }
?>
  <h1>بررسی نیازمندی‌های سرور</h1>
  <p class="sub">این موارد باید برقرار باشند.</p>
  <?php foreach ($rows as $r): [$label, $pass, $val] = $r; ?>
    <div class="chk"><span><?= esc($label) ?></span><span class="<?= $pass ? 'ok' : 'bad' ?>"><?= esc(is_string($val) ? $val : ($pass ? 'موفق' : 'ناموفق')) ?></span></div>
  <?php endforeach; ?>
  <?php if ($hardFail): ?>
    <p class="err" style="margin-top:16px">برخی نیازمندی‌های اصلی برقرار نیست. دسترسی نوشتن پوشه‌ها را با FTP/File Manager روی ۷۵۵ تنظیم کنید و صفحه را رفرش کنید.</p>
    <a class="btn gh" href="<?= step_url(2) ?>">بررسی مجدد</a>
  <?php else: ?>
    <a class="btn" href="<?= step_url(3) ?>">ادامه</a>
  <?php endif; ?>

<?php elseif ($step === 3): ?>
  <h1>اتصال به دیتابیس MySQL</h1>
  <p class="sub">اطلاعات دیتابیسی که در هاست ساخته‌اید را وارد کنید.</p>
  <form method="post" action="<?= step_url(3) ?>">
    <label>Database Host</label><input name="db_host" value="<?= esc($data['db_host'] ?? 'localhost') ?>" required>
    <label>Database Name</label><input name="db_name" value="<?= esc($data['db_name'] ?? '') ?>" required>
    <label>Database Username</label><input name="db_user" value="<?= esc($data['db_user'] ?? '') ?>" required>
    <label>Database Password</label><input type="password" name="db_pass" value="">
    <?php if (!empty($data['need_confirm'])): ?>
      <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="confirm_overwrite" value="1" style="width:auto"> جداول قبلی این دیتابیس حذف و با ساختار جدید جایگزین شوند (غیرقابل بازگشت)</label>
    <?php endif; ?>
    <button class="btn" type="submit">اتصال و ساخت جداول</button>
  </form>

<?php elseif ($step === 4): ?>
  <h1>ساخت حساب مدیر</h1>
  <p class="sub">با این نام کاربری و رمز وارد <code>admin.html</code> می‌شوید.</p>
  <form method="post" action="<?= step_url(4) ?>">
    <label>نام نمایشی</label><input name="admin_name" value="<?= esc($_POST['admin_name'] ?? '') ?>" required>
    <label>نام کاربری (لاتین)</label><input name="admin_user" value="<?= esc($_POST['admin_user'] ?? 'admin') ?>" required dir="ltr">
    <label>رمز عبور (حداقل ۸ نویسه)</label><input type="password" name="admin_pass" required>
    <label>تکرار رمز عبور</label><input type="password" name="admin_pass2" required>
    <button class="btn" type="submit">ساخت مدیر و اتمام نصب</button>
  </form>

<?php elseif ($step === 5):
  $u = $_SESSION['install_done']['user'] ?? '—'; unset($_SESSION['install_done']);
?>
  <h1>نصب با موفقیت انجام شد 🎉</h1>
  <p>اکنون می‌توانید وارد پنل مدیریت شوید:</p>
  <div class="chk"><span>آدرس پنل</span><span><code>admin.html</code></span></div>
  <div class="chk"><span>نام کاربری</span><span><code><?= esc($u) ?></code></span></div>
  <div class="chk"><span>رمز عبور</span><span>همانی که وارد کردید</span></div>
  <a class="btn" href="admin.html">ورود به پنل مدیریت</a>
  <a class="btn gh" href="index.html">مشاهدهٔ سایت</a>
  <p class="note">این نصب‌کننده اکنون قفل شد. برای امنیت بیشتر می‌توانید فایل <code>install.php</code> را از روی هاست حذف کنید.</p>
<?php endif; ?>
</div>
</div></body></html>
