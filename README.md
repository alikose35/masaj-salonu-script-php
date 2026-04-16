# Masaj Salonu Randevu Scripti

Docker destekli, PHP tabanli, AJAX rezervasyon akisina sahip profesyonel bir masaj salonu scriptidir. Uyeliksiz randevu alma, admin panelinden durum yonetimi, SEO ayarlari ve kurulum sihirbazi tek pakette sunulur.

## Kullanilan teknolojiler

- PHP 8.2
- MySQL 8
- Apache
- HTML, CSS, JavaScript
- AJAX tabanli rezervasyon ve panel aksiyonlari

## Ozellikler

- Uyeliksiz online randevu olusturma
- Admin giris paneli
- Randevu yoneticisi paneli
- Hizmet ve terapist listeleme
- SEO ve site bilgilerini kurulum ekranindan alma
- Zorunlu ve onerilen uyumluluk kontrolleri
- Docker ile hizli lokal kurulum

## Kurulum klavuzu mantigi

Kurulum sayfasi iki farkli kontrol grubuyla ilerler:

- Zorunlu: Bu gruptaki maddelerden biri eksikse kurulum butonu pasif kalir ve islem tamamlanamaz.
- Onerilenler: Bu gruptaki eksikler kurulumu durdurmaz, ancak ilgili deneyim veya gelismis ozellikler kisitlanabilir.

Kontrol edilen baslica uyumluluklar:

- PHP surumu
- `pdo_mysql`, `json`, `openssl` eklentileri
- `storage` klasoru yazma izni
- `mbstring`, `fileinfo`, `upload_max_filesize` gibi tavsiye edilen ortam nitelikleri

## Admin paneli

- `http://localhost:8080/admin.php`
- Kurulumda girdiginiz e-posta ve sifre ile giris yapabilirsiniz.
- Bekleyen randevulari onaylama, tamamlama veya iptal etme islemleri AJAX ile yapilir.

## Notlar

- Kurulumdan sonra ayarlar `storage/config.php` icine yazilir.
- Kurulum kilidi `storage/installed.lock` dosyasi ile tutulur.
- Script ilk acilista dogrudan installer'a yonlenir.
