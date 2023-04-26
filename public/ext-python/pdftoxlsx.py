# Import the required Module
import tabula
import time
import pandas as pd
import os,sys, os.path
from os import path

pdf_input = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\upload-pdf"
csv_input = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\temp-csv"
pdf_output = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\temp-csv"
csv_output = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\temp"

df = tabula.read_pdf(pdf_input+"\\convert_xlsx.pdf", pages='all')[0]
tabula.convert_into(pdf_input+"\\convert_xlsx.pdf", pdf_output+"\\converted.csv", output_format="csv", pages='all')
time.sleep(2.5)

if os.path.isfile(csv_input+"\\converted.csv"):
        read_csv_file = pd.read_csv (csv_input+"\\converted.csv")
        read_csv_file.to_excel (csv_output+"\\converted.xlsx", index = None, header=True)
        if os.path.isfile(csv_output):
            os.remove(pdf_input+"\\convert_xlsx.pdf")
            os.remove(csv_input+"\\converted.csv")
            print("true")
        else:
            os.remove(pdf_input+"\\convert_xlsx.pdf")
            os.remove(csv_input+"\\converted.csv")
            print("false")
else:
    os.remove(pdf_input+"\\convert_xlsx.pdf")
    os.remove(csv_input+"\\converted.csv")
    print("false")