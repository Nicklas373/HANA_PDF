<?php

namespace App\Http\Controllers\Api\Misc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\appLogModel;
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
            'appServicesReferrer' => ['required', 'in:FE,BE']
		]);

        $uuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'processId');
        $Muuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'groupId');

		if ($validator->fails()) {
            return $this->returnDataMesage(
                400,
                'Validation failed',
                null,
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
            $appMinorVersionBE = 6;
            $appPatchVersionBE = 6;
            $appVersioningBE = null;
            $appVersioningFE = null;
            $appServicesReferrerBE = "BE";
            $validateBE = false;
            $validateFE = false;
            $validateMessage = '';
            $url = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/versioning.json';

            appLogModel::create([
                'processId' => $uuid,
                'groupId' => $Muuid,
                'errReason' => null,
                'errStatus' => null
            ]);

            if (appHelper::instance()->checkWebAvailable($url)) {
                try {
                    $response = Http::timeout(30)
                                    ->acceptJson()
                                    ->get($url);
                    if ($response->successful()) {
                        $data = $response->json();
                        foreach ($data as $service) {
                            if ($service['appServices'] === 'BE') {
                                $majorVersionBE = $service['versioning']['majorVersion'];
                                $minorVersionBE = $service['versioning']['minorVersion'];
                                $patchVersionBE = $service['versioning']['patchVersion'];
                                $appVersioningBE = $appMajorVersionBE.'.'.$appMinorVersionBE.'.'.$appPatchVersionBE;
                                $versioningBE = $majorVersionBE.'.'.$minorVersionBE.'.'.$patchVersionBE;
                            } else if ($service['appServices'] === 'FE') {
                                $majorVersionFE = $service['versioning']['majorVersion'];
                                $minorVersionFE = $service['versioning']['minorVersion'];
                                $patchVersionFE = $service['versioning']['patchVersion'];
                                $appVersioningFE = $appMajorVersionFE.'.'.$appMinorVersionFE.'.'.$appPatchVersionFE;
                                $versioningFE = $majorVersionFE.'.'.$minorVersionFE.'.'.$patchVersionFE;
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
                            appLogModel::where('groupId', '=', $Muuid)
                                ->update([
                                    'errReason' => 'Version Check Failed !',
                                    'errStatus' => $validateMessage
                                ]);
                            NotificationHelper::Instance()->sendVersioningErrNotify(
                                $appVersioningFE,
                                $versioningFE,
                                $appVersioningBE,
                                $versioningBE,
                                'FAIL',
                                $Muuid,
                                'Version Check Failed !',
                                $validateMessage
                            );
                            return $this->returnVersioningMessage(
                                400,
                                'Version Check Failed !',
                                $appVersioningBE,
                                $versioningBE,
                                $appVersioningFE,
                                $versioningFE,
                                $validateMessage
                            );
                        }
                    } else {
                        appLogModel::where('groupId', '=', $Muuid)
                        ->update([
                            'errReason' => 'Failed to parse response from request server !',
                            'errStatus' => 'Version Check Failed !'
                        ]);
                        NotificationHelper::Instance()->sendVersioningErrNotify(
                            $appVersioningFE,
                            $versioningFE,
                            $appVersioningBE,
                            $versioningBE,
                            'FAIL',
                            $Muuid,
                            'Version Check Failed !',
                            'Failed to parse response from request server !'
                        );
                        return $this->returnVersioningMessage(
                            400,
                            'Version Check Failed !',
                            $appVersioningBE,
                            $versioningBE,
                            $appVersioningFE,
                            $versioningFE,
                            'Failed to parse response from request server !'
                        );
                    }
                } catch (\Exception $e) {
                    appLogModel::where('groupId', '=', $Muuid)
                        ->update([
                            'errReason' => $e->getMessage(),
                            'errStatus' => 'Cannot establish response with the server'
                        ]);
                    NotificationHelper::Instance()->sendVersioningErrNotify(
                        null,
                        null,
                        null,
                        null,
                        'FAIL',
                        $Muuid,
                        'Cannot establish response with the server',
                        $e->getMessage()
                    );
                    return $this->returnVersioningMessage(
                        500,
                        'Cannot establish response with the server',
                        null,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            } else {
                appLogModel::where('groupId', '=', $Muuid)
                    ->update([
                        'errReason' => 'Cannot establish response with the server',
                        'errStatus' => 'Version Check Failed !'
                    ]);
                NotificationHelper::Instance()->sendVersioningErrNotify(
                    null,
                    null,
                    null,
                    null,
                    'FAIL',
                    $uuid,
                    'Version Check Failed !',
                    'Cannot establish connection with the server'
                );
                return $this->returnVersioningMessage(
                    400,
                    'Version Check Failed !',
                    null,
                    null,
                    null,
                    null,
                    'Cannot establish connection with the server'
                );
            }
        }
    }

    public function versioningFetch(Request $request) {
        $uuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'processId');
        $Muuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'groupId');
        $endpoint = 'api/v1/version/fetch';
        $versionFetch = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/changelog.json';

        appLogModel::create([
            'processId' => $uuid,
            'groupId' => $Muuid,
            'errReason' => null,
            'errStatus' => null
        ]);

		if (appHelper::instance()->checkWebAvailable($versionFetch)) {
            try {
                $response = Http::timeout(30)
                                ->acceptJson()
                                ->get($versionFetch);
                if ($response->successful()) {
                    $data = $response->json();
                    return $this->returnDataMesage(
                        200,
                        'OK',
                        $data,
                        null,
                        null,
                        null
                    );
                } else {
                    appLogModel::where('groupId', '=', $Muuid)
                    ->update([
                        'errReason' => 'Failed to parse response from request server !',
                        'errStatus' => 'Version Fetch Failed !'
                    ]);
                    NotificationHelper::Instance()->sendErrGlobalNotify(
                        $endpoint,
                        'Version Fetch',
                        'FAIL',
                        $Muuid,
                        'Failed to parse response from request server !',
                        null,
                        false
                    );
                    return $this->returnDataMesage(
                        400,
                        'Failed to parse response from request server !',
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            } catch (\Exception $e) {
                appLogModel::where('groupId', '=', $Muuid)
                    ->update([
                        'errReason' => 'Failed to parsing JSON !',
                        'errStatus' =>  $e->getMessage()
                    ]);
                NotificationHelper::Instance()->sendErrGlobalNotify(
                    $endpoint,
                    'Version Fetch',
                    'FAIL',
                    $Muuid,
                    'Failed to parsing JSON !',
                    $e->getMessage(),
                    false
                );
                return $this->returnDataMesage(
                    400,
                    'Failed to parsing JSON !',
                    null,
                    null,
                    null,
                    $e->getMessage()
                );
            }
        } else {
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'Version fetch failed !',
                    'Failed to fetch response with the server'
                ]);
            NotificationHelper::Instance()->sendErrGlobalNotify(
                $endpoint,
                'Version Fetch',
                'FAIL',
                $uuid,
                'Version fetch failed !',
                'Cannot establish connection with the server'
            );
            return $this->returnDataMesage(
                400,
                'Version fetch failed !',
                null,
                null,
                null,
                'Cannot establish response with the server'
            );
        }
    }
}
