# Import the required Module
import tabula
import time
import pandas as pd
import os,sys, os.path
from os import path

pdf_input = r"/var/www/html/hanaci-pdf/storage/app/public/upload-pdf"
csv_input = r"/var/www/html/hanaci-pdf/storage/app/public/temp-csv"
pdf_output = r"/var/www/html/hanaci-pdf/storage/app/public/temp-csv"
csv_output = r"/var/www/html/hanaci-pdf/storage/app/public/temp"

df = tabula.read_pdf(pdf_input+"/convert_xlsx.pdf", encoding='utf-8', multiple_tables=True, pages='all')
tabula.convert_into(pdf_input+"/convert_xlsx.pdf", pdf_output+"/converted.csv", output_format="csv", pages='all')
time.sleep(2.5)

if os.path.isfile(csv_input+"/converted.csv"):
        read_csv_file = pd.read_csv (csv_input+"/converted.csv", on_bad_lines='skip', encoding = 'unicode_escape')
        read_csv_file.to_excel (csv_output+"/converted.xlsx", header=True)
        if os.path.isfile(csv_output+"/converted.xlsx"):
            os.remove(pdf_input+"/convert_xlsx.pdf")
            os.remove(csv_input+"/converted.csv")
            print("true")
        else:
            os.remove(pdf_input+"/convert_xlsx.pdf")
            os.remove(csv_input+"/converted.csv")
            print("false")
else:
    os.remove(pdf_input+"/convert_xlsx.pdf")
    os.remove(csv_input+"/converted.csv")
    print("false")
