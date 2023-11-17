## HANA PDF
HANA PDF is a __Laravel__ based project powered by front-end framework like __ViteJS__ and __Tailwind CSS__ and used of __Flowbite__ library to maintain responsive and materialize interface. And with integration from __iLovePDF__ and __Aspose Cloud__ API as one of the back-end, it have feature to merge, split, compress, convert, and add watermarks to PDF documents, that can handle easily and quickly.

---

![HANA](screenshot/1.png)

---

## Requirements

- [Apache 2.4](https://httpd.apache.org/download.cgi) (Use [XAMPP](https://www.apachefriends.org/download.html) if windows)
- [Composer](http://getcomposer.org/)
- [PostgreSQL 16.2](https://www.postgresql.org/)
- [Node JS 18.18](https://nodejs.org/en)
- [PHP 8.2.12](https://www.php.net/downloads.php)
- [Python 3.10.x](https://www.python.org/downloads/release/python-31011/)

---

## Node JS Module Requirements

- [Flowbite](https://flowbite.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Vite JS](https://vitejs.dev/)

---

## Python Module Requirements

- Requests

---

## How to use

1. Clone the repository with __git clone__
2. Copy __.env.example__ file to __.env__ and modify database credentials
3. Add additional environment into __.env__ with this string (Add yourself value :p)

````bash
- ASPOSE_CLOUD_CLIENT_ID="Aspose cloud AppId, get it in __(Aspose Cloud)__[https://dashboard.aspose.cloud/]"
- ASPOSE_CLOUD_TOKEN="Aspose Cloud storage token, get it in __(Aspose Cloud)__[https://dashboard.aspose.cloud/]"
- ADOBE_CLIENT_ID="Adobe API key for PDF embed API, get it in __(Adobe PDF)__[https://developer.adobe.com/document-services/docs/overview/pdf-embed-api/]"
- FTP_USERNAME="Used for 3rd party cloud storage for Aspose Cloud"
- FTP_USERPASS="Used for 3rd party cloud storage for Aspose Cloud"
- FTP_SERVER="Used for 3rd party cloud storage for Aspose Cloud"
- ILOVEPDF_ENC_KEY="Generate your hash key (Max. 25 digits)"
- ILOVEPDF_PUBLIC_KEY="iLovePDF public key, get it in __(iLovePDF)__[https://developer.ilovepdf.com/]"
- ILOVEPDF_SECRET_KEY="iLovePDF secret key, get it in __(iLovePDF)__[https://developer.ilovepdf.com/]"
- PDF_MERGE_TEMP="temp-merge"
- PDF_UPLOAD="upload-pdf"
- PDF_DOWNLOAD="temp"
````

4. Run the following command

Node Environment
```bash
- npm i @material-tailwind/react
- npm install flowbite
- npm install -D tailwindcss postcss autoprefixer
- npm install vite
```

Laravel Environment
```bash
- composer install
- composer dump-autoload
- php artisan key:generate
- php artisan migrate
```

5. Modify some static path (on ENV) into your current laravel project location (__Make sure all static patch already re-mapped correctly__)
7. npm run dev -- --host
8. php artisan serve --host=localhost --port=80

---

## Technology Stack
- [Aspose](https://www.aspose.cloud/)
- [Flowbite](https://flowbite.com/)
- [iLovePDF](https://developer.ilovepdf.com/)
- [Node JS](https://nodejs.org/en)
- [Python](https://www.python.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Vite JS](https://vitejs.dev/)

## License
The HANA-CI PDF is a open source Laravel Project that has licensed under the [MIT license](https://opensource.org/licenses/MIT).

<br>

## HANA-CI Build Project 2016 - 2023
