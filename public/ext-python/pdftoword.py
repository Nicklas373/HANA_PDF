import win32com.client
import os

word = win32com.client.Dispatch("Word.Application")
word.visible = True

path_input = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\upload-pdf"
path_output = r"C:\xampp\htdocs\emsitpro-pdftools-tailwind\public\temp"

try:
    in_file = os.path.abspath(path_input+'\\convert_docx.pdf')
    wb = word.Documents.Open(in_file)
    out_file = os.path.abspath(path_output+'\\converted.docx')
    wb.SaveAs2(out_file, FileFormat=16)
    wb.Close()
    word.Quit()
except:
    word.Quit()
