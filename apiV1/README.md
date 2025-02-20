<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


It looks like MySQL is failing to start due to some existing issues with XAMPP’s MySQL/MariaDB setup. Let’s **fully reset and reinstall XAMPP** properly.

**🚀 Step 1: Completely Remove XAMPP**

To **delete everything** and start fresh, run these commands:

**1️⃣ Stop XAMPP Services**

Run this in **Terminal**:

```
sudo /Applications/XAMPP/xamppfiles/xampp stop
```

If the command doesn’t work, stop MySQL manually:

```
sudo pkill mysqld
sudo pkill httpd
```

**2️⃣ Delete XAMPP Completely**

**Run this to ** **delete all XAMPP files** **:**

```
sudo rm -rf /Applications/XAMPP
sudo rm -rf ~/.bitnami
```

**3️⃣ Remove MySQL Data (Optional)**

If you want to remove **all MySQL databases**, delete this folder:

```
sudo rm -rf /Applications/XAMPP/xamppfiles/var/mysql
```

**🚀 Step 2: Download and Install XAMPP Again**

**	**1.**	**Go to [**XAMPP Download Page**](https://www.apachefriends.org/download.html).

**	**2.**	**Download the latest **XAMPP for macOS**.

**	**3.**	**Open the **.dmg** file and install it.

**🚀 Step 3: Fix macOS Security Issues**

Since macOS blocks XAMPP, **follow these steps**:

**Allow Installation:**

**	**1.**	****Go to:**System Settings** → **Privacy & Security**.**

**	**2.**	**Scroll down and **click “Allow Anyway”** for XAMPP.

**Bypass macOS Gatekeeper (If needed)**

If installation still fails, run this command:

```
sudo xattr -d com.apple.quarantine /Applications/XAMPP/xampp-osx-*-installer.dmg
```

Then, try installing again.

**🚀 Step 4: Start XAMPP Properly**

Once installed, start MySQL **properly**:

```
sudo /Applications/XAMPP/xamppfiles/xampp start
```

Check if MySQL is running:

```
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server status
```

If MySQL is **not running**, force restart:

```
sudo /Applications/XAMPP/xamppfiles/xampp restartmysql
```

**🚀 Step 5: Set MySQL Root Password**

**	**1.**	**Open Terminal and enter MySQL:

```
sudo /Applications/XAMPP/xamppfiles/bin/mysql -u root
```

**	**2.**	**Set a  **new password** **:**

```
ALTER USER 'root'@'localhost' IDENTIFIED BY 'Pakistan@321';
FLUSH PRIVILEGES;
EXIT;
```

**🚀 Step 6: Verify MySQL Connection**

Run:

```
mysql -u root -p
```

Enter your password (**Pakistan@321**).

If it connects successfully, **XAMPP is fully working!** 🎉

**💥 If Problems Still Happen:**

**	**•**	**Check MySQL logs:

```
cat /Applications/XAMPP/xamppfiles/var/mysql/*.err
```

**	**•**	**Restart your Mac.

**	**•**	**Run **brew doctor** to check for Homebrew conflicts.

Let me know if any errors still happen! 🚀
