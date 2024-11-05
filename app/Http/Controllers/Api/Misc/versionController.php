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
            'appGitVersion' => ['required'],
            'appServicesReferrer' => ['required', 'in:FE,BE']
		]);

        $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');

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
            $appMinorVersionBE = 5;
            $appPatchVersionBE = 2;
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
                            appLogModel::create([
                                'processId' => $uuid,
                                'groupId' => $uuid,
                                'errReason' => 'Version Check Failed !',
                                'errStatus' => $validateMessage
                            ]);
                            NotificationHelper::Instance()->sendVersioningErrNotify(
                                $appVersioningFE,
                                $versioningFE,
                                $appVersioningBE,
                                $versioningBE,
                                'FAIL',
                                $uuid,
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
                    } catch (\Exception $e) {
                        appLogModel::create([
                            'processId' => $uuid,
                            'groupId' => $uuid,
                            'errReason' => 'Unable to parsing JSON versioning !',
                            'errStatus' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendVersioningErrNotify(
                            null,
                            null,
                            null,
                            null,
                            'FAIL',
                            $uuid,
                            'Unable to parsing JSON versioning !',
                            $e->getMessage()
                        );
                        return $this->returnVersioningMessage(
                            500,
                            'Unable to parsing JSON versioning !',
                            null,
                            null,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                } else {
                    appLogModel::create([
                        'processId' => $uuid,
                        'groupId' => $uuid,
                        'errReason' => 'Version Check Failed !',
                        'errStatus' => 'Cannot establish response with the server'
                    ]);
                    NotificationHelper::Instance()->sendVersioningErrNotify(
                        null,
                        null,
                        null,
                        null,
                        'FAIL',
                        $uuid,
                        'Version Check failed !',
                        'Cannot establish response with the server'
                    );
                    return $this->returnVersioningMessage(
                        400,
                        'Version Check Failed !',
                        null,
                        null,
                        null,
                        null,
                        'Cannot establish response with the server'
                    );
                }
            } else {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $uuid,
                    'errReason' => 'Version Check Failed !',
                    'errStatus' => 'Cannot establish connection with the server'
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
        $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');
        $endpoint = 'api/v1/version/fetch';
        $versionFetch = 'https://raw.githubusercontent.com/Nicklas373/Hana-PDF/versioning/changelog.json';

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
                        null,
                        $e->getMessage()
                    );
                }
            } else {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $uuid,
                    'errReason' => 'Versioning Fetch Failed !',
                    'errStatus' => 'Failed to fetch response with the server'
                ]);
                NotificationHelper::Instance()->sendErrGlobalNotify(
                    $endpoint,
                    'Version Fetch',
                    'FAIL',
                    $uuid,
                    'Version fetch failed !',
                    'Failed to fetch response with the server'
                );
                return $this->returnDataMesage(
                    400,
                    'Version fetch failed !',
                    null,
                    null,
                    null,
                    'Failed to fetch response with the server'
                );
            }
        } else {
            appLogModel::create([
                'processId' => $uuid,
                'groupId' => $uuid,
                'errReason' => 'Versioning Fetch Failed !',
                'errStatus' => 'Failed to fetch response with the server'
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
