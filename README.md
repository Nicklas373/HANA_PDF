## HANA PDF

<br>
<div style="text-align: center;">
  <img src="screenshot/logo.png" alt="HANA" width="300" height="300">
</div>
<br>

__HANA PDF__ is your go-to solution for effortlessly managing your PDFs. We've designed it with simplicity in mind, so you can edit,
combine, shrink, convert, and personalize your PDFs with just a few clicks. And was implemented with front-end framework like ViteJS and Tailwind CSS and used of Flowbite library to maintain responsive and materialize interface. And powered with iLovePDF and Aspose Cloud API as one of the back-end.

---

![HANA](screenshot/1.png)

---

### Requirements
- [Apache 2.4](https://httpd.apache.org) or [Nginx](https://www.nginx.com)
- [Composer](http://getcomposer.org/)
- [Docker](https://www.docker.com/)
    * On Windows use Docker Desktop
    * On Linux use docker-compose and docker.io
- [Node JS 20.11](https://nodejs.org/en)
- [PHP 8.2.12](https://www.php.net/downloads.php)
- [PostgreSQL 16.2](https://www.postgresql.org/)
- [Python 3.10.x](https://www.python.org/downloads/release/python-31011/)
<<<<<<< HEAD

---

### Node JS Module Requirements
- [Flowbite](https://flowbite.com/)
- [Tailwind CSS](https://tailwindcss.com/)
=======
>>>>>>> 84f95fa (README: Updated documentation)
- [Vite JS](https://vitejs.dev/)

---

### Python Module Requirements
- Requests

---

### Build Status
- [![CodeQL](https://github.com/Nicklas373/Hana-PDF/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/Nicklas373/Hana-PDF/actions/workflows/github-code-scanning/codeql)
- [![HANA-CI PDF SIT Container](https://github.com/Nicklas373/Hana-PDF/actions/workflows/docker-sit-env.yml/badge.svg)](https://github.com/Nicklas373/Hana-PDF/actions/workflows/docker-sit-env.yml)
- [![HANA-CI PDF PROD Container](https://github.com/Nicklas373/hana-ci-docker-prod/actions/workflows/docker-prod-env.yml/badge.svg)](https://github.com/Nicklas373/hana-ci-docker-prod/actions/workflows/docker-prod-env.yml)

---

### Deployment
## Step to configure
1. Clone the repository with __git clone__
2. Copy __.env.example__ file to __.env__ and modify database credentials
3. Add additional environment into __.env__ with this string (Add yourself value :p)
````bash
<<<<<<< HEAD
- ASPOSE_CLOUD_CLIENT_ID="Aspose cloud AppId, get it in [https://dashboard.aspose.cloud/]"
- ASPOSE_CLOUD_TOKEN="Aspose Cloud storage token, get it in [https://dashboard.aspose.cloud/]"
- ADOBE_CLIENT_ID="Adobe API key for PDF embed API, get it in [https://developer.adobe.com/document-services/docs/overview/pdf-embed-api/]"
- FTP_USERNAME="Used for 3rd party cloud storage for Aspose Cloud"
- FTP_USERPASS="Used for 3rd party cloud storage for Aspose Cloud"
- FTP_SERVER="Used for 3rd party cloud storage for Aspose Cloud"
- ILOVEPDF_ENC_KEY="Generate your hash key (Max. 25 digits)"
- ILOVEPDF_PUBLIC_KEY="iLovePDF public key, get it in [https://developer.ilovepdf.com/]"
- ILOVEPDF_SECRET_KEY="iLovePDF secret key, get it in _[https://developer.ilovepdf.com/]"
- ILOVEPDF_EXT_IMG_DIR="temp-image"
- PDF_MERGE_TEMP="temp-merge"
- PDF_UPLOAD="upload-pdf"
- PDF_DOWNLOAD="temp"
=======
- ASPOSE_CLOUD_CLIENT_ID="ASPOSE_CLOUD_CLIENT_ID" [https://dashboard.aspose.cloud/]
- ASPOSE_CLOUD_TOKEN="ASPOSE_CLOUD_TOKEN" [https://dashboard.aspose.cloud/]
- ADOBE_CLIENT_ID="ADOBE_CLIENT_ID" [https://developer.adobe.com/document-services/docs/overview/pdf-embed-api/]
- FTP_USERNAME="FTP_USERNAME" [https://dashboard.aspose.cloud/]
- FTP_USERPASS="FTP_USERPASS" [https://dashboard.aspose.cloud/]
- FTP_SERVER="FTP_SERVER" [https://dashboard.aspose.cloud/]
- ILOVEPDF_ENC_KEY="ILOVEPDF_ENC_KEY" [Generate your hash key (Max. 25 digits)]
- ILOVEPDF_PUBLIC_KEY="ILOVEPDF_PUBLIC_KEY" [https://developer.ilovepdf.com/]
- ILOVEPDF_SECRET_KEY="ILOVEPDF_SECRET_KEY" [https://developer.ilovepdf.com/]
- PDF_IMG_POOL="image"
- PDF_BATCH="batch"
- PDF_UPLOAD="upload"
- PDF_DOWNLOAD="download"
- PDF_POOL="pool"
- TELEGRAM_BOT_ID="YOUR_TELEGRAM_BOT_ID" [https://telegram-bot-sdk.com/docs/getting-started/installation]
- TELEGRAM_CHAT_ID="YOUR_TELEGRAM_CHANNEL_ID" [https://telegram-bot-sdk.com/docs/getting-started/installation]
- TOKEN_GENERATE="YOUR_ENCODE_SHA512_TOKEN"
>>>>>>> 84f95fa (README: Updated documentation)
````
4. Run the following command [Make sure to configure database connectivity before use migrate function]
```bash
- composer install
- php artisan key:generate
- php artisan storage:link
- php artisan migrate
```
5. Create new directory inside storage/app/public
    - image
    - batch
    - upload
    - download
    - pool
6. Start to deploy
    ```bash
    - npm run dev -- --host
    - php artisan serve --host=localhost --port=80
    ```
    
---

## Technology Stack
- [Aspose](https://www.aspose.cloud/)
- [Docker](https://www.docker.com/)
- [DropzoneJS](https://www.dropzone.dev/)
- [Flowbite](https://flowbite.com/)
- [iLovePDF](https://developer.ilovepdf.com/)
- [Node JS](https://nodejs.org/en)
- [PDFJS](https://mozilla.github.io/pdf.js/)
- [PHPOffice](https://github.com/PHPOffice)
- [Python](https://www.python.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Vite JS](https://vitejs.dev/)

---

<<<<<<< HEAD
## License
The HANA-CI PDF is a open source Laravel Project that has licensed under the [MIT license](https://opensource.org/licenses/MIT).
=======
### License
The HANA PDF is a open source Laravel Project that has licensed under the [MIT license](https://opensource.org/licenses/MIT).
>>>>>>> 84f95fa (README: Updated documentation)

---

## HANA-CI Build Project 2016 - 2024
