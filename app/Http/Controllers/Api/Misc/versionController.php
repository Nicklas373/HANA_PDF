<?php

namespace App\Http\Controllers\Api\Misc;

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
            return $this->returnDataMesage(
                401,
                'Validation failed',
                null,
                null,
                $validator->messages()->first()
            );
		} else {
            $appMajorVersionFE = $request->post('appMajorVersion');
            $appMinorVersionFE = $request->post('appMinorVersion');
            $appPatchVersionFE = $request->post('appPatchVersion');
            $appGitVersionFE = $request->post('appGitVersion');
            $appServicesReferrerFE = $request->post('appServicesReferrer');
            $appMajorVersionBE = 3;
            $appMinorVersionBE = 4;
            $appPatchVersionBE = 1;
            $appGitVersionBE = appHelper::instance()->getGitCommitHash();
            $appVersioningBE = null;
            $appVersioningFE = null;
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
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Version Check Failed !',$validateMessage,true);
                                return $this->returnVersioningMessage(
                                    400,
                                    'Version Check Failed !',
                                    $appVersioningBE,
                                    $versioningBE,
                                    $appVersioningFE,
                                    $versioningFE,
                                    $validateMessage
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Database connection error !',$ex->getMessage(),false);
                                return $this->returnVersioningMessage(
                                    500,
                                    'Database connection error !',
                                    $appVersioningBE,
                                    $versioningBE,
                                    $appVersioningFE,
                                    $versioningFE,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendVersioningErrNotify($appVersioningFE, $versioningFE, $appVersioningBE, $versioningBE, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(),false);
                                return $this->returnVersioningMessage(
                                    500,
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
                            NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Unable to parsing JSON versioning !',$e->getMessage(),true);
                            return $this->returnVersioningMessage(
                                500,
                                'Unable to parsing JSON versioning !',
                                null,
                                null,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Database connection error !',$ex->getMessage(),false);
                            return $this->returnVersioningMessage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(),false);
                            return $this->returnVersioningMessage(
                                500,
                                'Eloquent transaction error !',
                                null,
                                null,
                                null,
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
                            'errStatus' => 'Cannot establish response with the server'
                        ]);
                        NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Version Check failed !','Cannot establish response with the server',true);
                        return $this->returnVersioningMessage(
                            400,
                            'Version Check Failed !',
                            null,
                            null,
                            null,
                            null,
                            'Cannot establish response with the server'
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Database connection error !',$ex->getMessage(),false);
                        return $this->returnVersioningMessage(
                            500,
                            'Database connection error !',
                            null,
                            null,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(),false);
                        return $this->returnVersioningMessage(
                            500,
                            'Eloquent transaction error !',
                            null,
                            null,
                            null,
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
                    NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Version Check Failed !','Cannot establish connection with the server',true);
                    return $this->returnVersioningMessage(
                        400,
                        'Version Check Failed !',
                        null,
                        null,
                        null,
                        null,
                        'Cannot establish connection with the server'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Database connection error !',$ex->getMessage(),false);
                    return $this->returnVersioningMessage(
                        500,
                        'Database connection error !',
                        null,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendVersioningErrNotify(null, null, null, null, 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(),false);
                    return $this->returnVersioningMessage(
                        500,
                        'Eloquent transaction error !',
                        null,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            }
        }
    }

    public function versioningFetch(Request $request) {
        $uuid = AppHelper::Instance()->get_guid();
        $endpoint = 'api/v1/version/fetch';
        $versionFetch = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/changelog.json';

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

		if (appHelper::instance()->checkWebAvailable($versionFetch)) {
            $response = Http::get($versionFetch);
            if ($response->successful()) {
                try {
                    $data = $response->json();
                    return $this->returnDataMesage(
                        200,
                        'OK',
                        $data,
                        null,
                        null
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Failed to parsing JSON !', $e->getMessage(), false);
                    return $this->returnDataMesage(
                        400,
                        'Failed to parsing JSON !',
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            } else {
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Versioning Fetch Failed !',
                        'errStatus' => 'Failed to fetch response with the server'
                    ]);
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Version fetch failed !','Failed to fetch response with the server', true);
                    return $this->returnDataMesage(
                        400,
                        'Version fetch failed !',
                        null,
                        null,
                        'Failed to fetch response with the server'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Database connection error !',
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Eloquent transaction error !',
                        null,
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
                    'errStatus' => 'Failed to fetch response with the server'
                ]);
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Version fetch failed !','Cannot establish connection with the server', true);
                return $this->returnDataMesage(
                    400,
                    'Version fetch failed !',
                    null,
                    null,
                    'Cannot establish response with the server'
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Database connection error !',$ex->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Database connection error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrGlobalNotify($endpoint, 'Version Fetch', 'FAIL', $uuid,'Eloquent transaction error !', $e->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Eloquent transaction error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            }
        }
    }
}
