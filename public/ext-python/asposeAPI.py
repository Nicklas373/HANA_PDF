import http.client
import json
import sys
import requests
import os
import os.path

asposeToken=""
asposeClientID=str(sys.argv[1])
asposeClientSecret=str(sys.argv[2])
asposeContainer=str(sys.argv[3])
asposeFile=str(sys.argv[4])
asposeOut=str(sys.argv[5])
asposeFileDirFix = asposeFile.replace("/upload-pdf/","/app/public/upload-pdf/")
WinSemanticCnv = asposeFileDirFix.replace("/", "\\\\")
WinSemanticDir = r"C:\\Users\\Nickl\\Documents\\GitHub\\emsitpro-pdftools-tailwind"+WinSemanticCnv

os.chdir('..')

def getAsposeToken():
    asposeCloudAPI = http.client.HTTPSConnection("api.aspose.cloud")
    asposeCredential = 'grant_type=client_credentials&client_id='+asposeClientID+'&client_secret='+asposeClientSecret
    headers = {
        'Content-Type': "application/x-www-form-urlencoded",
        'Accept': "application/json"
    }
    asposeCloudAPI.request("POST", "/connect/token", asposeCredential, headers)
    asposeResponse = asposeCloudAPI.getresponse()
    asposePayload = asposeResponse.read()
    asposeJsonObject = json.loads(asposePayload.decode("utf-8"))
    global asposeToken
    if asposeJsonObject != "":
        asposeToken = str(asposeJsonObject["access_token"])
    else:
        asposeToken = "ERROR GETTING TOKEN"

def convAsposeAPI(token, container):
    headers = {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
        'Authorization': 'Bearer '+token,
    }
    params = {
        'outPath': asposeOut,
    }
    if os.path.isfile(WinSemanticDir):
        with open(WinSemanticDir, 'rb') as f:
            data = f.read()
        if len(container) != 0:
            asposeResponse = requests.put('https://api.aspose.cloud/v3.0/pdf/convert/'+container, params=params, headers=headers, data=data)
            values = asposeResponse.status_code
            if values == 200:
                print('File conversion success!')
            else:
                print('File conversion failed!')
        else:
            print("Invalid Container")
    else:
        print('File source not found! :'+WinSemanticDir)

getAsposeToken()

if asposeToken != "ERROR GETTING TOKEN":
    convAsposeAPI(asposeToken, asposeContainer)
else:
    print("Failed to generated Aspose Token")
