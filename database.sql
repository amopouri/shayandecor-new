-- شایان دکور — ساختار و داده‌های اولیه (utf8mb4)
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'admin',
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(60) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description TEXT NULL,
  image VARCHAR(500) NULL,
  models_count INT UNSIGNED NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cat_slug (slug),
  KEY idx_cat_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  short_description VARCHAR(500) NULL,
  description TEXT NULL,
  features TEXT NULL,
  price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  old_price BIGINT UNSIGNED NULL,
  discount TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  sku VARCHAR(60) NULL,
  shaneh VARCHAR(20) NULL,
  size TEXT NULL,
  color TEXT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0,
  reviews_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_new TINYINT(1) NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_prod_slug (slug),
  UNIQUE KEY uq_prod_sku (sku),
  KEY idx_prod_cat (category_id),
  KEY idx_prod_active (is_active, created_at),
  KEY idx_prod_price (price),
  KEY idx_prod_name (name),
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_img_prod (product_id, sort_order),
  CONSTRAINT fk_img_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS attributes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(20) NOT NULL,
  name VARCHAR(120) NOT NULL,
  value VARCHAR(60) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_attr_type (type, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS testimonials (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  city VARCHAR(120) NULL,
  initials VARCHAR(10) NULL,
  text TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(500) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS blog_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(250) NOT NULL,
  slug VARCHAR(260) NULL,
  excerpt TEXT NULL,
  image VARCHAR(500) NULL,
  category VARCHAR(80) NULL,
  read_time VARCHAR(40) NULL,
  published_label VARCHAR(60) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS subscribers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sub_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key,setting_value) VALUES
('site_name','شایان دکور'),
('site_tagline','گالری تخصصی فرش و کفپوش'),
('logo',''),
('phone',''),
('phone_raw',''),
('mobile',''),
('whatsapp',''),
('email',''),
('address','تهران، سهروردی شمالی، پلاک ۷۶۷'),
('instagram','shayandecor'),
('hero_title','فضای خانه‌تان را با {فرش‌های اصیل} کامل کنید'),
('hero_subtitle','گالری تخصصی فرش ماشینی، موکت و کفپوش با بیش از ۴۰ سال تجربه؛ کاتالوگ واقعی، قیمت واقعی و مشاوره حضوری.'),
('about_text','فروشگاه شایان دکور با بیش از ۴۰ سال سابقه در زمینه عرضه فرش ماشینی، موکت و کفپوش‌های باکیفیت، همراه خانه‌های ایرانی است.'),
('footer_text',''),
('primary_color','#C9A961'),
('default_theme','light'),
('maintenance','0');

INSERT INTO categories (id,name,slug,description,image,models_count,sort_order,is_active) VALUES
(1,'فرش مدرن','فرش-مدرن','',NULL,0,1,1),
(2,'فرش مینیمال مدرن','فرش-مینیمال-مدرن','',NULL,0,2,1),
(3,'فرش شگی','فرش-شگی','',NULL,0,3,1),
(4,'فرش گرد شگی','فرش-گرد-شگی','',NULL,0,4,1),
(5,'گبه بلوچ','گبه-بلوچ','',NULL,0,5,1),
(6,'فرش گرد','فرش-گرد','',NULL,0,6,1),
(7,'موکت','موکت','',NULL,0,7,1),
(8,'پادری','پادری','',NULL,0,8,1),
(9,'پادری خز','پادری-خز','',NULL,0,9,1),
(10,'تابلو','تابلو','',NULL,0,10,1),
(11,'فرش کلاسیک','فرش-کلاسیک','',NULL,0,11,1),
(12,'گرین وال','گرین-وال','',NULL,0,12,1),
(13,'کناره','کناره','',NULL,0,13,1),
(14,'چمن مصنوعی','چمن-مصنوعی','',NULL,0,14,1);

INSERT INTO attributes (type,name,value,sort_order) VALUES
('color','مشکی','#0A0A0B',0),
('color','سفید','#F2EFE8',1),
('color','طوسی','#666666',2),
('color','کرم','#F9CB9C',3),
('color','نسکافه‌ای','#B96C47',4),
('color','قهوه‌ای','#783F04',5),
('color','قرمز','#E81A1A',6),
('color','سبز','#2AAC39',7),
('color','آبی','#31A3CF',8),
('color','طلایی','#C9A961',9),
('size','400×300 (۱۲ متری)',NULL,0),
('size','350×250 (۹ متری)',NULL,1),
('size','200×300 (۶ متری)',NULL,2),
('size','225×150 (۴ متری)',NULL,3),
('size','300×400',NULL,4),
('shaneh','500',NULL,0),
('shaneh','700',NULL,1),
('shaneh','1000',NULL,2),
('shaneh','1200',NULL,3),
('size','3×2',NULL,5),
('size','3×3',NULL,6),
('size','3×4',NULL,7),
('size','3×5',NULL,8),
('size','3×6',NULL,9),
('size','3×7',NULL,10),
('size','3×8',NULL,11),
('size','3×9',NULL,12),
('size','3×10',NULL,13),
('size','3×11',NULL,14),
('size','200 در 300 سانتیمتر(۶متری)',NULL,15),
('size','250 در350 سانتیمتر (9متری)',NULL,16),
('size','150 در 225 سانتیمتر (۴متری)',NULL,17),
('size','کناره 2×1',NULL,18),
('size','250 در 350 سانتیمتر(9متری)',NULL,19),
('size','200 در 300 سانتیمتر(6متری)',NULL,20),
('size','150 در225 سانتیمتر(4متری)',NULL,21),
('size','300 در 100 سانتیمتر',NULL,22),
('size','200 در 100 سانتیمتر',NULL,23),
('size','150 در 100 سانتیمتر',NULL,24),
('size','120 در 180 سانتیمتر',NULL,25),
('size','300 در 100سانتیمتر',NULL,26),
('size','180در120 سانتیمتر',NULL,27),
('size','150در100سانتیمتر',NULL,28),
('size','400در300 سانتیمتر(12متری)',NULL,29);



-- testimonials seed removed (no fake reviews)
INSERT INTO faqs (question,answer,sort_order,is_active) VALUES
('چطور سایز مناسب فرش را انتخاب کنم؟','برای پذیرایی‌های معمول، فرش ۲۰۰×۳۰۰ (۶ متری) مناسب است. برای فضاهای بزرگ‌تر ۳۵۰×۲۵۰ یا ۴۰۰×۳۰۰ توصیه می‌شود. بهترین کار این است که ابعاد دقیق فضا را اندازه بگیرید و در فروشگاه با مشاوره تخصصی انتخاب کنید.',1,1),
('تفاوت شانه فرش در چیست؟','شانه به تعداد گره در هر متر عرض فرش گفته می‌شود. هرچه شانه بالاتر باشد، بافت ریزتر و کیفیت بالاتر است. شانه‌های ۷۰۰ تا ۱۲۰۰ رایج‌ترین شانه‌های بازار هستند.',2,1),
('فرش شگی چگونه نگهداری می‌شود؟','فرش شگی نیاز به جاروکشی منظم با برس نرم دارد. از شامپوهای مخصوص فرش شگی برای شستشو استفاده کنید. سالی یک‌بار قالیشویی تخصصی توصیه می‌شود.',3,1),
('امکان رنگ‌بینی دقیق وجود دارد؟','بله، یکی از مزیت‌های مهم گالری شایان دکور امکان مشاهده حضوری محصولات زیر نور طبیعی و مصنوعی است. این باعث می‌شود رنگ واقعی فرش را دقیق ببینید.',4,1),
('ارسال به شهرستان دارید؟','بله، ارسال به سراسر ایران انجام می‌شود. هزینه و زمان ارسال بسته به شهر متغیر است و توسط فروشگاه به شما اعلام می‌شود.',5,1),
('شرایط مرجوعی چگونه است؟','تا ۷ روز پس از خرید، در صورت عدم استفاده و سالم بودن بسته‌بندی، امکان مرجوع کردن کالا وجود دارد.',6,1);

INSERT INTO blog_posts (title,slug,excerpt,image,category,read_time,published_label,sort_order,is_published) VALUES
('چطور فرش مناسب پذیرایی را انتخاب کنیم؟','post-1','انتخاب سایز، رنگ و جنس فرش پذیرایی بر اساس سبک دکوراسیون و متراژ فضا...','https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=700&q=80','راهنمای خرید','۵ دقیقه مطالعه','۲ هفته پیش',0,1),
('راهنمای کامل نگهداری فرش شگی','post-2','فرش‌های شگی به دلیل پرزهای بلند نیاز به مراقبت ویژه دارند. در این مقاله...','https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=700&q=80','نگهداری','۴ دقیقه مطالعه','۱ ماه پیش',1,1),
('فرش مدرن یا کلاسیک؟ راهنمای انتخاب سبک','post-3','هر سبک دکوراسیون نیازمند فرش مناسب خود است. در این مقاله تفاوت‌ها را...','https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?w=700&q=80','ترند','۶ دقیقه مطالعه','۲ ماه پیش',2,1);
