import{M as C}from"./index-f475cd16.js";const X=document.getElementById("loadingModal"),G=document.getElementById("errModal"),_={placement:"bottom-right",backdrop:"dynamic",backdropClasses:"bg-gray-900 bg-opacity-50 backdrop-filter backdrop-blur-sm fixed inset-0 z-40",closable:!0,onHide:()=>{},onShow:()=>{},onToggle:()=>{}},u=new C(X,_),t=new C(G,_);let d=!1,P=document.getElementById("submitBtn");var a=document.getElementById("titleMessageModal"),l=document.getElementById("altSubMessageModal"),n=document.getElementById("errMessageModal"),e=document.getElementById("errSubMessageModal"),N=document.getElementById("err-list"),r=document.getElementById("err-list-title"),w=document.getElementById("submitBtn_1"),D=document.getElementById("submitBtn_2"),k=document.getElementById("submitBtn_3");P&&(P.onclick=function(i){d=!0,T(i)});w&&(w.onclick=function(i){d=!1,T(i)});D&&(D.onclick=function(i){d=!1,T(i)});k&&(k.onclick=function(i){d=!1,T(i)});function T(i){if(document.getElementById("filelist")!==null){var M=document.getElementById("multiple_files").files,g=!1,h=0;if(document.getElementById("multiple_files").value==""&&document.getElementById("fileAlt")!=null&&d==!1)a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else if(document.getElementById("multiple_files").value!=""&&document.getElementById("fileAlt")!=null&&d==!0||document.getElementById("multiple_files").value!=""&&document.getElementById("fileAlt")==null&&d==!0){for(var L=0;L<M.length;L++){var I=M[L];let y=I.size;I.type=="application/pdf"?y>=26214400&&h++:(h++,g=!0)}h>0?g?(i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: PDF"),l.style=null,t.show()):(i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 25 MB"),l.style=null,t.show()):(a.innerHTML="Uploading PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show())}else document.getElementById("multiple_files").value==""&&document.getElementById("fileAlt")==null&&d==!0||document.getElementById("multiple_files").value==""&&document.getElementById("fileAlt")!=null&&d==!0?(i.preventDefault(),n.innerHTML="Please choose PDF file!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show()):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Merge decision logic error"),l.style=null,t.show())}if(document.getElementById("file_input")!==null)if(document.getElementById("cnvFrPDF")!==null||document.getElementById("compPDF")!==null)if(!document.getElementById("file_input").value&&document.getElementById("fileAlt")==null&&d==!0||!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!0)i.preventDefault(),n.innerHTML="Please choose PDF file!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show();else if(!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!1)if(document.getElementById("compPDF")!==null)if(!document.getElementById("comp-low").checked&&!document.getElementById("comp-rec").checked&&!document.getElementById("comp-high").checked){var F=document.getElementById("lowestChk"),x=document.getElementById("recChk"),S=document.getElementById("highestChk");i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("Compression Quality"),F.style.borderColor="#dc2626",x.style.borderColor="#dc2626",S.style.borderColor="#dc2626",t.show()}else a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else if(document.getElementById("cnvFrPDF")!==null)if(!document.getElementById("lowestChkA").checked&&!document.getElementById("ulChkA").checked&&!document.getElementById("recChkA").checked&&!document.getElementById("highestChkA").checked){var A=document.getElementById("lowestChk"),z=document.getElementById("ulChk"),U=document.getElementById("recChk"),R=document.getElementById("highestChk");i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("Document Format"),A.style.borderColor="#dc2626",z.style.borderColor="#dc2626",U.style.borderColor="#dc2626",R.style.borderColor="#dc2626",t.show()}else a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else{var m=document.getElementById("file_input");let y=m.files[0].size;m.files[0].type=="application/pdf"?y>=26214400?(i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 25 MB"),l.style=null,t.show()):(a.innerHTML="Uploading PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: PDF"),l.style=null,t.show())}else if(document.getElementById("cnvToPDF")!==null)if(!document.getElementById("file_input").value&&document.getElementById("fileAlt")==null&&d==!0||!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!0)i.preventDefault(),n.innerHTML="Please choose document file!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show();else if(!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!1)a.innerHTML="Processing Document...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else{var m=document.getElementById("file_input");let f=m.files[0].size;m.files[0].type=="application/vnd.openxmlformats-officedocument.wordprocessingml.document"||m.files[0].type=="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"||m.files[0].type=="application/vnd.openxmlformats-officedocument.presentationml.presentation"?f>=26214400?(i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 25 MB"),l.style=null,t.show()):(a.innerHTML="Upload Document",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: DOCX, XLSX, PPTX"),l.style=null,t.show())}else if(document.getElementById("splitLayout1"))if(!document.getElementById("file_input").value&&d==!1)if(document.getElementById("SplitOpta").checked){let y=!1,f=!1,c=!1;var H=document.getElementById("customPage"),E=document.getElementById("fromPage"),v=document.getElementById("toPage");document.getElementById("SplitOpta").value=="split"?document.getElementById("splitRadio")?document.getElementById("SplitOpt2a").checked?document.getElementById("SplitOpt2a").value=="selPages"?(document.getElementById("fromPage").value?f=!0:f=!1,document.getElementById("toPage").value?c=!0:c=!1,f&&c?parseInt(document.getElementById("fromPage").value)>=parseInt(document.getElementById("toPage").value)?(i.preventDefault(),n.innerHTML="Invalid page number range!",r.innerHTML="Error message",l.style=null,o(),s("First page can't be more than last page"),E.style.borderColor="#dc2626",t.show()):(a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):!f&&!c?(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("First Pages"),s("Last Pages"),E.style.borderColor="#dc2626",v.style.borderColor="#dc2626",t.show()):!f&&c?(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("First Pages"),E.style.borderColor="#dc2626",t.show()):f&&!c?(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("Last Pages"),v.style.borderColor="#dc2626",t.show()):(a.innerHTML="Processing PDF...",n.style=null,e.style=null,l.style.display="none",t.hide(),u.show())):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",l.style=null,r.innerHTML="Error message",o(),s("Split selected page logic error"),l.style=null,t.show()):document.getElementById("SplitOpt2b").checked?document.getElementById("SplitOpt2b").value=="cusPages"?(document.getElementById("customPage").value?y=!0:y=!1,y?(a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("Custom Pages"),H.style.borderColor="#dc2626",t.show())):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Split custom page logic error"),l.style=null,t.show()):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Cannot define selected or custom page"),l.style=null,t.show()):(i.preventDefault(),n.innerHTML="Kaori",e.style.visibility=null,l.style.display="none",t.show()):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Split options decision logic error"),l.style=null,t.show())}else if(document.getElementById("SplitOptb").checked){let y=!1;var H=document.getElementById("customPage");document.getElementById("SplitOptb").value=="extract"?(document.getElementById("customPage").value?y=!0:y=!1,y?(a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",l.style=null,o(),s("Custom Pages"),e.style.visibility=null,H.style.borderColor="#dc2626",t.show())):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Extract options decision logic error"),l.style=null,t.show())}else i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Split decision logic error"),l.style=null,t.show();else if(document.getElementById("file_input").value&&d==!0){var m=document.getElementById("file_input");let f=m.files[0].size;m.files[0].type=="application/pdf"?f>=26214400?(i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 25 MB"),l.style=null,t.show()):(a.innerHTML="Uploading PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: PDF"),l.style=null,t.show())}else i.preventDefault(),n.innerHTML="Please choose PDF file!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show();else if(document.getElementById("wmLayout1"))if(!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!1)if(document.getElementById("wmType")!=null)if(document.getElementById("wmType").value=="text"){var b=document.getElementById("watermarkText");if(!document.getElementById("watermarkText").value&&!document.getElementById("watermarkPage").value){var p=document.getElementById("watermarkPage");i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("Pages"),s("Text"),l.style=null,b.style.borderColor="#dc2626",p.style.borderColor="#dc2626",t.show()}else if(document.getElementById("watermarkText").value)if(document.getElementById("watermarkPage").value)n.style.visibility=null,a.innerHTML="Processing PDF...",e.style.visibility=null,l.style.display="none",t.hide(),u.show();else{var p=document.getElementById("watermarkPage");i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("Pages"),l.style=null,p.style.borderColor="#dc2626",t.show()}else i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("Text"),l.style=null,b.style.borderColor="#dc2626",t.show()}else if(document.getElementById("wmType").value=="image"){var q=document.getElementById("wm_file_input");if(document.getElementById("wm_file_input").value){var B=document.getElementById("wm_file_input");let y=B.files[0].size;if(B.files[0].type=="image/jpeg"||B.files[0].type=="image/png")if(y>=5242880)i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 5 MB"),l.style=null,t.show();else if(document.getElementById("watermarkPage").value)a.innerHTML="Processing PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show();else{var p=document.getElementById("watermarkPage");i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("Pages"),l.style=null,p.style.borderColor="#dc2626",t.show()}else i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: JPG, PNG"),l.style=null,t.show()}else i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("Image"),l.style=null,q.style.borderColor="#dc2626",t.show()}else i.preventDefault(),n.innerHTML="Please choose watermark options!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show();else i.preventDefault(),n.innerHTML="Please choose watermark options!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show();else if(document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!0||document.getElementById("file_input").value&&document.getElementById("fileAlt")==null&&d==!0){var m=document.getElementById("file_input");let f=m.files[0].size;m.files[0].type=="application/pdf"?f>=26214400?(i.preventDefault(),n.innerHTML="Uploaded file has exceeds the limit!",e.innerHTML="",r.innerHTML="Error message",o(),s("Maximum file size 25 MB"),l.style=null,t.show()):(a.innerHTML="Uploading PDF...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Unsupported file format!",e.innerHTML="",r.innerHTML="Error message",o(),s("Supported file format: PDF"),l.style=null,t.show())}else!document.getElementById("file_input").value&&document.getElementById("fileAlt")!=null&&d==!0||!document.getElementById("file_input").value&&document.getElementById("fileAlt")==null&&d==!0?(i.preventDefault(),n.innerHTML="Please choose PDF file!",e.innerHTML="",e.style.visibility=null,l.style.display="none",t.show()):(i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("Watermark decision logic error"),l.style=null,t.show());else i.preventDefault(),n.innerHTML="Index out of bound!",e.innerHTML="",r.innerHTML="Error message",o(),s("PDF decision logic error"),l.style=null,t.show();if(document.getElementById("urlToPDF")!==null){var O=document.getElementById("urlToPDF");document.getElementById("urlToPDF").value?(a.innerHTML="Processing URL...",n.style.visibility=null,e.style.visibility=null,l.style.display="none",t.hide(),u.show()):(i.preventDefault(),n.innerHTML="Please fill out these fields!",e.innerHTML="",r.innerHTML="Required fields:",o(),s("URL Address"),l.style=null,O.style.borderColor="#dc2626",t.show())}}function o(){N.innerHTML=`
        <ul id="err-list"class="mt-1.5 list-disc list-inside font-bold"></ul>
    `}function s(i){var M=document.getElementById("err-list"),g=document.createElement("li");g.appendChild(document.createTextNode(i)),M.appendChild(g)}
