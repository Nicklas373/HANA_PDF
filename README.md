<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## EMSITPRO PDF Tools
EMSITPRO PDF Tools is a __Laravel__ based project with mix use from some front-end and back-end programming stack, that focusly to build this website. It also integrated with several front-end framework like __ViteJS__ and __Tailwind CSS__ and used of __Flowbite__ library to maintain responsive and materialize interface. And with integration from __iLovePDF__ API as one of the back-end, it have feature to merge, split, compress, convert, and add watermarks to PDF documents, that can handle easily and quickly.

---

![EMS](screenshot/1.png)

---

## Requirements

- [Apache 2.4](https://httpd.apache.org/download.cgi) (Use [XAMPP](https://www.apachefriends.org/download.html) if windows)
- [Composer](http://getcomposer.org/)
- [Java JRE 8.0.371](https://www.java.com/en/download/manual.jsp)
- [MySQL 8.2](https://www.mysql.com/downloads/) (Use [XAMPP](https://www.apachefriends.org/download.html) if windows)
- [Node JS 18.16](https://nodejs.org/en)
- [PHP 8.2.4](https://www.php.net/downloads.php)
- [Python 3.10.x](https://www.python.org/downloads/release/python-31011/) (Do not use 3.11.x for temporary)

---

## Node JS Module Requirements

- Vite

---

## Python Module Requirements

- Pandas
- Tabula-io (tabula-py)

---

## How to use

1. Clone the repository with __git clone__
2. Copy __.env.example__ file to __.env__ and modify database credentials
3. Run the following command

Node Environment
```bash
- npm install vite
- npm run dev
```

Laravel Environment
```bash
- composer install
- composer dump-autoload
- php artisan key:generate
- php artisan migrate
- php artisan serve
```

4. Modify some static path into your current laravel project location (__Make sure all static patch already re-mapped correctly__)
- public/pdftoxlsx.py
- public/pdftoword.py
- app/http/controllers/extractcontroller.php
- app/http/controllers/splitcontroller.php

5. Create folder __temp-csv__ & __temp-merge__ in the root folder
6. That's it

---

## Technology Stack
- [Flowbite](https://flowbite.com/)
- [iLovePDF](https://developer.ilovepdf.com/)
- [Node JS](https://nodejs.org/en)
- [Python](https://www.python.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Vite JS](https://vitejs.dev/)

---

## NOTE
- If this error show while migration __"Error: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes"__
  References: __[Stackoverflow](https://stackoverflow.com/questions/42244541/laravel-migration-error-syntax-error-or-access-violation-1071-specified-key-wa)__

---

## License
The EMSITPRO PDF Tools is a open source Laravel Project that has licensed under the [MIT license](https://opensource.org/licenses/MIT).

<br>

## HANA-CI Build Project 2016 - 2023