<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class versionController extends Controller {
    public function versioningCheck(Request $request) {
        $validator = Validator::make($request->all(),[
            'appMajorVersion' => ['required', 'numeric'],
            'appMinorVersion' => ['required', 'numeric'],
            'appPatchVersion' => ['required', 'numeric'],
            'appGitVersion' => ['required'],
            'appServicesReferrer' => ['required', 'in:FE,BE']
		]);

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

		if ($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => 'Validation Failed!',
                    'errStatus' => $validator->messages()
                ]);
                NotificationHelper::Instance()->sendVersioningErrNotify(null,null,null,null,null,null, $uuid, 'FAIL','Version Check','Versioning check failed !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'Version Check Failed !',
                    null,
                    null,
                    'Version Check',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendVersioningErrNotify(null,null,null,null,null,null, $uuid, 'FAIL','Version Check','Database connection error !',$ex->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'Version Check',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendVersioningErrNotify(null,null,null,null,null, null, $uuid, 'FAIL','Version Check','Eloquent transaction error !', $e->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'Version Check',
                    $uuid,
                    null,
                    null,
                    null,
                    $e->getMessage()
                );
            }
		} else {
            $appMajorVersionFE = $request->post('appMajorVersion');
            $appMinorVersionFE = $request->post('appMinorVersion');
            $appPatchVersionFE = $request->post('appPatchVersion');
            $appGitVersionFE = $request->post('appGitVersion');
            $appServicesReferrerFE = $request->post('appServicesReferrer');
            $appMajorVersionBE = 3;
            $appMinorVersionBE = 2;
            $appPatchVersionBE = 7;
            $appGitVersionBE = appHelper::instance()->getGitCommitHash();
            $appServicesReferrerBE = "BE";
            $validateBE = false;
            $validateFE = false;
            $validateMessage = '';
            $url = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/versioning.json';

            if (appHelper::instance()->checkWebAvailable($url)) {
                $response = Http::get($url);
                if ($response->successful()) {
                    $data = $response->json();
                    try {
                        foreach ($data as $service) {
                            if ($service['appServices'] === 'BE') {
                                $majorVersionBE = $service['versioning']['majorVersion'];
                                $minorVersionBE = $service['versioning']['minorVersion'];
                                $patchVersionBE = $service['versioning']['patchVersion'];
                                $gitRevisionBE = $service['versioning']['gitRevision'];
                                $appVersioningBE = $appMajorVersionBE.'.'.$appMinorVersionBE.'.'.$appPatchVersionBE.'-'.$appGitVersionBE;
                                $versioningBE = $majorVersionBE.'.'.$minorVersionBE.'.'.$patchVersionBE.'-'.$gitRevisionBE;
                            } else if ($service['appServices'] === 'FE') {
                                $majorVersionFE = $service['versioning']['majorVersion'];
                                $minorVersionFE = $service['versioning']['minorVersion'];
                                $patchVersionFE = $service['versioning']['patchVersion'];
                                $gitRevisionFE = $service['versioning']['gitRevision'];
                                $appVersioningFE = $appMajorVersionFE.'.'.$appMinorVersionFE.'.'.$appPatchVersionFE.'-'.$appGitVersionFE;
                                $versioningFE = $majorVersionFE.'.'.$minorVersionFE.'.'.$patchVersionFE.'-'.$gitRevisionFE;
                            }
                        }

                        if ($appVersioningBE == $versioningBE) {
                            $validateBE = true;
                            if ($appVersioningFE == $versioningFE) {
                                $validateFE = true;
                            } else {
                                $validateFE = false;
                                $validateMessage = 'Front-End module version missmatch !';
                            }
                        } else {
                            $validateBE = false;
                            $validateFE = false;
                            $validateMessage = 'Back-End module version missmatch !';
                        }

                        if ($validateBE && $validateFE) {
                            return $this->returnVersioningMessage(
                                200,
                                'OK',
                                $appVersioningBE,
                                $versioningBE,
                                $appVersioningFE,
                                $versioningFE,
                                null
                            );
                        } else {
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => 'Version Check Failed !',
                                    'errStatus' => $validateMessage
                                ]);
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Version Check Failed !',$validateMessage);
                                return $this->returnVersioningMessage(
                                    200,
                                    'Version Check Failed !',
                                    $appVersioningBE,
                                    $versioningBE,
                                    $appVersioningFE,
                                    $versioningFE,
                                    $validateMessage
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                                return $this->returnVersioningMessage(
                                    200,
                                    'Database connection error !',
                                    $appVersioningBE,
                                    $versioningBE,
                                    $appVersioningFE,
                                    $versioningFE,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                                return $this->returnVersioningMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $appVersioningBE,
                                    $versioningBE,
                                    $appVersioningFE,
                                    $versioningFE,
                                    $e->getMessage()
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => 'Unable to parsing JSON versioning !',
                                'errStatus' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Unable to parsing JSON versioning !',$e->getMessage());
                            return $this->returnVersioningMessage(
                                200,
                                'Unable to parsing JSON versioning !',
                                $appVersioningBE,
                                $versioningBE,
                                $appVersioningFE,
                                $versioningFE,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                            return $this->returnVersioningMessage(
                                200,
                                'Database connection error !',
                                $appVersioningBE,
                                $versioningBE,
                                $appVersioningFE,
                                $versioningFE,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                            return $this->returnVersioningMessage(
                                200,
                                'Eloquent transaction error !',
                                $appVersioningBE,
                                $versioningBE,
                                $appVersioningFE,
                                $versioningFE,
                                $e->getMessage()
                            );
                        }
                    }
                } else {
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'Version Check Failed !',
                            'errStatus' => 'Cannot establish response with the server'
                        ]);
                        NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Version Check failed !','Cannot establish response with the server');
                        return $this->returnVersioningMessage(
                            200,
                            'Version Check Failed !',
                            $appVersioningBE,
                            null,
                            $appVersioningFE,
                            null,
                            'Cannot establish response with the server'
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                        return $this->returnVersioningMessage(
                            200,
                            'Database connection error !',
                            $appVersioningBE,
                            null,
                            $appVersioningFE,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                        return $this->returnVersioningMessage(
                            200,
                            'Eloquent transaction error !',
                            $appVersioningBE,
                            null,
                            $appVersioningFE,
                            null,
                            $e->getMessage()
                        );
                    }
                }
            } else {
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Version Check Failed !',
                        'errStatus' => 'Cannot establish connection with the server'
                    ]);
                    NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Version Check Failed !','Cannot establish connection with the server');
                    return $this->returnVersioningMessage(
                        200,
                        'Version Check Failed !',
                        $appVersioningBE,
                        null,
                        $appVersioningFE,
                        null,
                        'Cannot establish connection with the server'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                    return $this->returnVersioningMessage(
                        200,
                        'Database connection error !',
                        $appVersioningBE,
                        null,
                        $appVersioningFE,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, null, $appVersioningBE, null, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                    return $this->returnVersioningMessage(
                        200,
                        'Eloquent transaction error !',
                        $appVersioningBE,
                        null,
                        $appVersioningFE,
                        null,
                        $e->getMessage()
                    );
                }
            }
        }
    }

    public function versioningFetch(Request $request) {
        $validator = Validator::make($request->all(),[
            'appServicesReferrer' => ['required', 'in:FE']
		]);

        $uuid = AppHelper::Instance()->get_guid();
        $endpoint = 'api/v1/version/fetch';
        $versionFetch = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/changelog.json';

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

		if ($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => 'Validation Failed!',
                    'errStatus' => $validator->messages()
                ]);
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Version fetch failed !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'Version fetch failed !',
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                return $this->returnMessage(
                    200,
                    'Database connection error !',
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                return $this->returnMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    $e->getMessage()
                );
            }
		} else {
            if (appHelper::instance()->checkWebAvailable($versionFetch)) {
                $response = Http::get($versionFetch);
                if ($response->successful()) {
                    $data = $response->json();
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errStatus' => null
                        ]);
                        return $this->returnMessage(
                            200,
                            'OK',
                            $data,
                            null
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                        return $this->returnMessage(
                            200,
                            'Database connection error !',
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                        return $this->returnMessage(
                            200,
                            'Eloquent transaction error !',
                            null,
                            $e->getMessage()
                        );
                    }
                } else {
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'Versioning Fetch Failed !',
                            'errStatus' => 'Cannot establish response with the server'
                        ]);
                        NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Version fetch failed !','Cannot establish response with the server');
                        return $this->returnMessage(
                            200,
                            'Version fetch failed !',
                            null,
                            'Cannot establish response with the server'
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                        return $this->returnMessage(
                            200,
                            'Database connection error !',
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                        return $this->returnMessage(
                            200,
                            'Eloquent transaction error !',
                            null,
                            $ex->getMessage()
                        );
                    }
                }
            } else {
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Versioning Fetch Failed !',
                        'errStatus' => 'Cannot establish connection with the server'
                    ]);
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Version fetch failed !','Cannot establish connection with the server');
                    return $this->returnMessage(
                        200,
                        'Version fetch failed !',
                        null,
                        'Cannot establish response with the server'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage());
                    return $this->returnMessage(
                        200,
                        'Database connection error !',
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage());
                    return $this->returnMessage(
                        200,
                        'Eloquent transaction error !',
                        null,
                        $ex->getMessage()
                    );
                }
            }
        }
    }
}
