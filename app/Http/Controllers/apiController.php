<?php
 
namespace App\Http\Controllers;

class apiController extends Controller
{
	public function api(){
		return view('api_information');
	}
}