# Masaj Salonu Randevu Scripti (PHP)

Masaj ve spa salonları için geliştirilmiş, üyelik gerektirmeden online randevu almayı sağlayan bir web uygulamasıdır. Kurulum sihirbazı sayesinde veritabanı bağlantısı, site ayarları ve yönetici hesabı tek akışta tamamlanır.

## Proje Özeti

Bu proje iki ana bölümden oluşur:

- Ziyaretçi tarafı: Hizmet ve terapist seçerek tarih/saat bazlı randevu oluşturma
- Yönetim paneli: Randevuları onaylama, tamamlama, iptal etme ve temel ayarları yönetme

## Öne Çıkan Özellikler

- Üyeliksiz randevu oluşturma akışı
- AJAX tabanlı hızlı form işlemleri
- Yönetici girişi ve panel yönetimi
- Kurulum sihirbazı (uyumluluk kontrolleri + başlangıç ayarları)
- Otomatik şema oluşturma ve örnek veri hazırlama
- SEO ve temel site kimliği alanları

## Teknoloji Yığını

- PHP 8.1+
- MySQL 8+
- Apache (veya PHP built-in server ile geliştirme)
- HTML, CSS, JavaScript

## Gereksinimler

### Zorunlu Gereksinimler

- PHP `8.1` veya üzeri
- PHP eklentileri:
  - `pdo_mysql`
  - `json`
  - `openssl`
- Yazılabilir `storage/` klasörü
- Çalışan bir MySQL sunucusu

### Önerilen Gereksinimler

- `mbstring`
- `fileinfo`
- `upload_max_filesize >= 5M`

## Kurulum Rehberi

### 1. Projeyi Çalıştırın

Proje kök dizininde bir web sunucusu ile çalıştırın.

Örnek (geliştirme için):

```bash
php -S localhost:8080
```

Ardından tarayıcıdan:

- `http://localhost:8080/install.php`

### 2. Kurulum Sihirbazını Tamamlayın

Kurulum ekranında sırasıyla:

- Veritabanı bağlantı bilgilerini girin
- Site/SEO alanlarını doldurun
- Yönetici hesabı oluşturun

Kurulum tamamlandığında uygulama:

- Veritabanı şemasını oluşturur
- Başlangıç verilerini (hizmet/terapist) ekler
- `storage/config.php` dosyasını yazar
- `storage/installed.lock` dosyasını oluşturur

### Temiz Kaynak Dağıtımı

Bu depo, kurulum yapılmamış temiz kaynak olarak tutulur. Bu yüzden aşağıdaki dosyalar repoda bilerek yoktur:

- `storage/config.php`
- `storage/installed.lock`

Bu dosyalar kurulum tamamlandığında otomatik olarak oluşturulur.
`storage/config.example.php` yalnızca örnek amaçlıdır.

### 3. Giriş ve Kullanım

- Site: `http://localhost:8080/index.php`
- Admin paneli: `http://localhost:8080/admin.php`

## Veritabanı Dosyaları

- [database/schema.sql](database/schema.sql): Şema tanımları
- [database/live-20260416-230041.sql](database/live-20260416-230041.sql): Örnek/dump veri

Not: Normal kullanımda `install.php` şemayı otomatik hazırlar. Manuel kurulum yapmak isterseniz bu SQL dosyalarını referans alabilirsiniz.

## Proje Yapısı

- `app/`: Çekirdek sınıflar ve yardımcı fonksiyonlar
- `app/Core/`: Kimlik doğrulama, veritabanı, bildirim mantığı
- `app/Views/`: Yönetim paneli görünümleri
- `public/assets/`: CSS ve JS dosyaları
- `storage/`: Çalışma zamanı dosyaları (`config.php`, `installed.lock`) ve örnek yapılandırma
- `database/`: SQL şema ve yedek dosyaları

## Sık Karşılaşılan Sorunlar

- Kurulum butonu pasifse: Zorunlu uyumluluk kontrollerinden en az biri başarısızdır.
- Veritabanına bağlanamıyorsa: Host/port/kullanıcı/parola bilgilerini kontrol edin.
- Uygulama sürekli kurulum ekranına dönüyorsa: `storage/config.php` ve `storage/installed.lock` dosyalarının yazıldığını doğrulayın.

## Güvenlik Notu

- `storage/config.php` veritabanı bilgilerini içerir.
- Üretim ortamında bu dosyanın erişim izinlerini sınırlandırın ve mümkünse gizli yönetimi (secret management) kullanın.
