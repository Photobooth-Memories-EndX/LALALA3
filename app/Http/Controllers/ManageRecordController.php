<?php

namespace App\Http\Controllers;

use App\Exports\Manage\ClassificationAllRecordExport;
use App\Exports\Manage\ClassificationOfflineRecordExport;
use App\Exports\Manage\ClassificationOnlineRecordExport;
use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Http\Controllers\Manage\Record\RecordOnlineController;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ManageRecordController extends Controller
{
    protected RecordOnlineController $onlineController;
    protected RecordOfflineController $offlineController;
    protected RecordAllController $allController;

    public function __construct(
        RecordOnlineController $onlineController,
        RecordOfflineController $offlineController,
        RecordAllController $allController
    ) {
        $this->onlineController = $onlineController;
        $this->offlineController = $offlineController;
        $this->allController = $allController;
    }

    public function online(Request $request)
    {
        return $this->onlineController->index($request);
    }

    public function onlineCourse(Request $request, $id = null)
    {
        return view('main.record.online.course', [
            'courseId' => $id,
            'title'    => 'KP OWL Course Record',
            'subtitle' => 'Enrolled participants and progress tracking.',
            'backUrl'  => route('manage.record.online'),
            'backTitle'=> 'Back to KP OWL',
        ]);
    }

    public function offline(Request $request)
    {
        return $this->offlineController->index($request);
    }

    public function offlineCourse($training, Request $request)
    {
        return $this->offlineController->courseDetail($training, $request);
    }

    public function offlineClassification($classification, Request $request)
    {
        return $this->offlineController->classification($classification, $request);
    }

    public function all(Request $request)
    {
        return $this->allController->index($request);
    }

    public function allClassification($classification, Request $request)
    {
        return $this->allController->classification($classification, $request);
    }

    public function allParticipant($identifier, Request $request)
    {
        return $this->allController->participantDetail($identifier, $request);
    }

    public static function formatExportDateTime(?\DateTimeInterface $date = null): string
    {
        $dt = $date ? Carbon::parse($date) : Carbon::now();
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return "{$day}{$month}{$year} at {$time}";
    }

    public function exportOnline(Request $request)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeOWLrecord_{$dateTime}.xlsx";

        return app(\App\Services\Record\ManageOnlineRecordExportService::class)->download($request->all(), $fileName);
    }

    public function exportOffline(Request $request)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeNonOWLrecord_{$dateTime}.xlsx";

        return app(\App\Services\Record\ManageOfflineRecordExportService::class)->download($request->all(), $fileName);
    }

    public function exportAll(Request $request)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeAllrecord_{$dateTime}.xlsx";

        return app(\App\Services\Record\ManageAllRecordExportService::class)->download($request->all(), $fileName);
    }

    public function exportClassification(Request $request, $classification = null)
    {
        $source = strtolower((string) $request->input('source', 'all'));
        $classVal = $classification ?? $request->input('classification');

        if ($source === 'online' || $source === 'owl') {
            return $this->exportClassificationOnline($request, $classVal);
        }
        if ($source === 'offline' || $source === 'non-owl') {
            return $this->exportClassificationOffline($request, $classVal);
        }

        return $this->exportClassificationAll($request, $classVal);
    }

    public function exportClassificationOnline(Request $request, $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeOWLrecord_{$dateTime}.xlsx";

        return (new ClassificationOnlineRecordExport($classVal, $request->all(), $fileName))->download($fileName);
    }

    public function exportClassificationOffline(Request $request, $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeNonOWLrecord_{$dateTime}.xlsx";

        return Excel::download(new ClassificationOfflineRecordExport($classVal, $request->all(), $fileName), $fileName);
    }

    public function exportClassificationAll(Request $request, $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeAllrecord_{$dateTime}.xlsx";

        return Excel::download(new ClassificationAllRecordExport($classVal, $request->all(), $fileName), $fileName);
    }

    /**
     * Unified Edit route handler for manage.record.edit
     */
    public function edit(Request $request, $id)
    {
        // Try finding in offline_training_users
        $otu = OfflineTrainingUser::find($id);
        if ($otu) {
            return redirect()->route('manage.record.offline.edit', ['id' => $id]);
        }

        // Try offline_trainings
        $ot = OfflineTraining::find($id);
        if ($ot) {
            return redirect()->route('manage.record.offline.edit', ['id' => $id]);
        }

        // Online content user
        $cu = ContentUser::find($id);
        if ($cu) {
            return redirect()->route('manage.record.online');
        }

        // Try playlist_content_users
        $pcu = PlaylistContentUser::find($id);
        if ($pcu) {
            return redirect()->route('manage.record.online');
        }

        return redirect()->back()->with('error', 'Record not found.');
    }

    /**
     * Unified Detail API endpoint for More Information modal
     */
    public function getMoreInformationDetail($requestOrId, $idOrNull = null): JsonResponse
    {
        return $this->moreInformation($requestOrId, $idOrNull);
    }

    /**
     * Unified Detail endpoint returning More Information non-yellow attributes
     */
    public function moreInformation($requestOrId, $idOrNull = null): JsonResponse
    {
        if ($requestOrId instanceof Request) {
            $request = $requestOrId;
            $id = $idOrNull;
        } else {
            $id = $requestOrId;
            $request = request();
        }

        $sourceParam = $request ? strtolower(trim((string) $request->input('source', ''))) : '';
        $source = $sourceParam;

        // Normalize composite IDs (e.g. 'offline:123', 'online:content:456', 'online:playlist:789')
        if (is_string($id) && strpos($id, ':') !== false) {
            $parts = explode(':', $id);
            $id = end($parts);
            if (in_array('offline', $parts, true)) {
                $source = 'offline';
            } elseif (in_array('online', $parts, true)) {
                $source = 'online';
            }
        }

        // 1. Try finding in offline_training_users
        if ($source === '' || $source === 'offline' || $source === 'non kp owl' || $source === 'non-owl') {
            $record = DB::table('offline_training_users as otu')
                ->leftJoin('offline_trainings as ot', 'otu.offline_training_id', '=', 'ot.id')
                ->where('otu.id', $id)
                ->select([
                    'otu.id',
                    'otu.user_id',
                    'otu.employee_id',
                    'otu.name as employee_name',
                    'otu.grade',
                    'otu.job_position',
                    'otu.team',
                    'otu.department',
                    'otu.company',
                    'otu.attendance',
                    'otu.attendance_reason',
                    'otu.progress',
                    'otu.bond_status',
                    'otu.bond_duration',
                    'otu.bond_end_date',
                    'otu.bond_amount_idr',
                    'otu.l1_program',
                    'otu.l2_numerical',
                    'otu.l2_verbal',
                    'otu.l3_numerical',
                    'otu.l3_verbal',
                    'otu.assessment_score',
                    'otu.pic',
                    'otu.certificate',
                    'ot.id as offline_training_id',
                    'ot.course_name as course_title',
                    'ot.sub_course_name as sub_course_title',
                    'ot.type',
                    'ot.category',
                    'ot.level',
                    'ot.batch',
                    'ot.provider',
                    'ot.sub_provider',
                    'ot.provider_name',
                    'ot.venue',
                    'ot.instructor',
                    'ot.delivery_method',
                    'ot.sub_delivery_method',
                ])
                ->first();

            // Fallback: if not matched on otu.id, check if $id is an offline_training_id
            if (!$record) {
                $record = DB::table('offline_training_users as otu')
                    ->leftJoin('offline_trainings as ot', 'otu.offline_training_id', '=', 'ot.id')
                    ->where('otu.offline_training_id', $id)
                    ->select([
                        'otu.id',
                        'otu.user_id',
                        'otu.employee_id',
                        'otu.name as employee_name',
                        'otu.grade',
                        'otu.job_position',
                        'otu.team',
                        'otu.department',
                        'otu.company',
                        'otu.attendance',
                        'otu.attendance_reason',
                        'otu.progress',
                        'otu.bond_status',
                        'otu.bond_duration',
                        'otu.bond_end_date',
                        'otu.bond_amount_idr',
                        'otu.l1_program',
                        'otu.l2_numerical',
                        'otu.l2_verbal',
                        'otu.l3_numerical',
                        'otu.l3_verbal',
                        'otu.assessment_score',
                        'otu.pic',
                        'otu.certificate',
                        'ot.id as offline_training_id',
                        'ot.course_name as course_title',
                        'ot.sub_course_name as sub_course_title',
                        'ot.type',
                        'ot.category',
                        'ot.level',
                        'ot.batch',
                        'ot.provider',
                        'ot.sub_provider',
                        'ot.provider_name',
                        'ot.venue',
                        'ot.instructor',
                        'ot.delivery_method',
                        'ot.sub_delivery_method',
                    ])
                    ->first();
            }

            if ($record) {
                $bondStatusRaw = strtolower(trim((string) $record->bond_status));
                $hasBond = in_array($bondStatusRaw, ['yes', '1', 'true', 'ya'], true);
                $bondStatus = $hasBond ? 'Yes' : 'No';

                $bondAmt = ($hasBond && $record->bond_amount_idr !== null && (float) $record->bond_amount_idr > 0)
                    ? 'Rp ' . number_format((float) $record->bond_amount_idr, 0, ',', '.')
                    : '-';

                $bondDuration = ($hasBond && !empty($record->bond_duration))
                    ? (is_numeric($record->bond_duration) ? $record->bond_duration . ' Months' : $record->bond_duration)
                    : '-';

                $bondEndDate = ($hasBond && !empty($record->bond_end_date))
                    ? date('Y-m-d', strtotime($record->bond_end_date))
                    : '-';

                $progress = (float) ($record->progress ?? 0);
                if ($progress >= 100) {
                    $progressStatus = 'Passed';
                } elseif ($progress > 0) {
                    $progressStatus = 'Ongoing';
                } else {
                    $progressStatus = 'Not Started';
                }

                // L2 Status
                $l2 = trim((string) ($record->l2_verbal ?? ''));
                if ($l2 === '') {
                    $scoreL2 = $record->assessment_score ?? $record->l2_numerical;
                    if (is_numeric($scoreL2)) {
                        $l2 = ((float) $scoreL2 >= 70) ? 'Pass' : (((float) $scoreL2 > 0) ? 'Fail' : '-');
                    } else {
                        $l2 = '-';
                    }
                }

                $l3 = trim((string) ($record->l3_verbal ?? ''));
                if ($l3 === '') {
                    if (is_numeric($record->l3_numerical)) {
                        $l3 = ((float) $record->l3_numerical >= 70) ? 'Pass' : (((float) $record->l3_numerical > 0) ? 'Fail' : '-');
                    } else {
                        $l3 = '-';
                    }
                }

                $cleanEmpId = trim((string) ($record->employee_id ?: '-'));
                if ($cleanEmpId !== '-') {
                    $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
                }

                $empName = trim((string) ($record->employee_name ?: '-'));
                if ($empName !== '-') {
                    $empName = strtoupper($empName);
                }

                $rawAtt = strtolower(trim((string) ($record->attendance ?? '')));
                $attendance = in_array($rawAtt, ['present', 'yes', '1', 'true', 'finished', 'completed', 'hadir'], true)
                    || ((float) ($record->progress ?? 0) >= 100) ? 'Yes' : 'No';

                $sub = $record->sub_delivery_method ?? $record->training_type ?? $record->type ?? null;
                if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                    $rawDeliv = trim((string) ($record->delivery_method ?? ''));
                    if ($rawDeliv !== '' && strtolower($rawDeliv) !== 'offline') {
                        $sub = $rawDeliv;
                    }
                }
                $subDeliveryMethod = $sub ?: '-';

                // Certificate details from certificates table or certificate_settings
                $cert = DB::table('certificates as cert')
                    ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
                    ->where('cert.offline_training_user_id', $record->id)
                    ->select([
                        'cert.issued_at',
                        'cert.issued_date',
                        'cert.expired_date',
                        'cert.institution as cert_institution',
                        'cs.issuer_name',
                        'cs.institution as cs_institution',
                        'cs.issued_date as cs_issued_date',
                        'cs.expired_date as cs_expired_date',
                    ])
                    ->first();

                $certSetting = null;
                $trainingId = $record->offline_training_id ?? null;
                if ($trainingId) {
                    $certSetting = DB::table('certificate_settings')
                        ->where('offline_training_id', $trainingId)
                        ->first();
                }

                $certInstitution = $cert?->cert_institution
                    ?: ($cert?->cs_institution
                        ?: ($certSetting?->institution
                            ?: ($cert?->issuer_name
                                ?: ($certSetting?->issuer_name ?: ($record->certificate ?: '-')))));

                $certIssuedDate = $cert?->issued_date
                    ?: ($certSetting?->issued_date
                        ? date('Y-m-d', strtotime($certSetting->issued_date))
                        : ($cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : '-'));

                $certExpiredDate = $cert?->expired_date
                    ?: ($certSetting?->expired_date
                        ? date('Y-m-d', strtotime($certSetting->expired_date))
                        : '-');

                $certAvailRaw = strtolower(trim((string) ($record->certificate ?? '')));
                $certAvailability = (!empty($cert) || !empty($certSetting) || in_array($certAvailRaw, ['yes', '1', 'true', 'available', 'ada'], true))
                    ? 'Available'
                    : ($certAvailRaw !== '' && $certAvailRaw !== 'no' && $certAvailRaw !== '-' ? $record->certificate : 'Not Available');

                $recordData = [
                    'source'                  => 'NON KP OWL',
                    'course_title'            => $record->course_title ?: ($record->sub_course_title ?: 'Offline Training'),
                    'sub_course_title'        => $record->sub_course_title ?: '-',
                    'employee_id'             => $cleanEmpId,
                    'employee_name'           => $empName,
                    'grade'                   => $record->grade ?: '-',
                    'job_position'            => $record->job_position ?: '-',
                    'team'                    => $record->team ?: '-',
                    'level'                   => $record->level ?: '-',
                    'batch'                   => $record->batch ?: '-',
                    'provider'                => $record->provider ?: 'External',
                    'sub_provider'            => $record->sub_provider ?: '-',
                    'provider_name'           => $record->provider_name ?: ($record->provider ?: '-'),
                    'venue'                   => $record->venue ?: '-',
                    'delivery_method'         => 'Offline',
                    'sub_delivery_method'     => $subDeliveryMethod,
                    'instructor'              => $record->instructor ?: '-',
                    'attendance'              => $attendance,
                    'attendance_reason'       => $record->attendance_reason ?: '-',
                    'progress_status'         => $progressStatus,
                    'bond_status'             => $bondStatus,
                    'bond_duration'           => $bondDuration,
                    'bond_end_date'           => $bondEndDate,
                    'bond_amount'             => $bondAmt,
                    'bond_amount_idr'         => $bondAmt,
                    'l2_status'               => $l2,
                    'l3_status'               => $l3,
                    'certificate_availability'=> $certAvailability,
                    'certificate_status'      => $certAvailability,
                    'certificate_institution' => $certInstitution,
                    'certificate_issued_date' => $certIssuedDate,
                    'certificate_expired_date'=> $certExpiredDate,
                ];

                return response()->json(array_merge($recordData, [
                    'success'            => true,
                    'data'               => $recordData,
                    'source_type'        => 'NON KP OWL',
                ]));
            }

            // Fallback: check offline_trainings directly by id
            $training = DB::table('offline_trainings')->where('id', $id)->first();
            if ($training) {
                $sub = $training->sub_delivery_method ?? $training->training_type ?? $training->type ?? null;
                if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                    $rawDeliv = trim((string) ($training->delivery_method ?? ''));
                    if ($rawDeliv !== '' && strtolower($rawDeliv) !== 'offline') {
                        $sub = $rawDeliv;
                    }
                }
                $subDeliveryMethod = $sub ?: '-';

                $cs = DB::table('certificate_settings')->where('offline_training_id', $training->id)->first();
                $certInstitution = $cs?->institution ?: ($cs?->issuer_name ?: '-');
                $certIssuedDate = $cs?->issued_date ? date('Y-m-d', strtotime($cs->issued_date)) : '-';
                $certExpiredDate = $cs?->expired_date ? date('Y-m-d', strtotime($cs->expired_date)) : '-';

                $recordData = [
                    'source'                  => 'NON KP OWL',
                    'course_title'            => $training->course_name ?: ($training->title ?: 'Offline Training'),
                    'sub_course_title'        => $training->sub_course_name ?: '-',
                    'employee_id'             => '-',
                    'employee_name'           => '-',
                    'grade'                   => '-',
                    'job_position'            => '-',
                    'team'                    => '-',
                    'level'                   => $training->level ?: '-',
                    'batch'                   => $training->batch ?: '-',
                    'provider'                => $training->provider ?: 'External',
                    'sub_provider'            => $training->sub_provider ?: '-',
                    'provider_name'           => $training->provider_name ?: ($training->provider ?: '-'),
                    'venue'                   => $training->venue ?: '-',
                    'delivery_method'         => 'Offline',
                    'sub_delivery_method'     => $subDeliveryMethod,
                    'instructor'              => $training->instructor ?: '-',
                    'progress_status'         => 'Not Started',
                    'attendance'              => 'No',
                    'attendance_reason'       => '-',
                    'l2_status'               => '-',
                    'l3_status'               => '-',
                    'bond_status'             => 'No',
                    'bond_duration'           => '-',
                    'bond_end_date'           => '-',
                    'bond_amount'             => '-',
                    'bond_amount_idr'         => '-',
                    'certificate_availability'=> !empty($cs) ? 'Available' : 'Not Available',
                    'certificate_status'      => !empty($cs) ? 'Available' : 'Not Available',
                    'certificate_institution' => $certInstitution,
                    'certificate_issued_date' => $certIssuedDate,
                    'certificate_expired_date'=> $certExpiredDate,
                ];

                return response()->json(array_merge($recordData, [
                    'success'            => true,
                    'data'               => $recordData,
                    'source_type'        => 'NON KP OWL',
                ]));
            }
        }

        // 2. Online record: check content_users
        $cu = DB::table('content_users as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->leftJoin('content as c', 'cu.content_id', '=', 'c.id')
            ->leftJoin('classification as cl', 'c.classification_id', '=', 'cl.id')
            ->where('cu.id', $id)
            ->select([
                'cu.id',
                'cu.user_id',
                'cu.content_id',
                'cu.progress',
                'cu.finish',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.jobgrade as grade',
                'u.jobposition as job_position',
                'u.team',
                'u.department',
                'u.company_name as company',
                'c.title as course_title',
                'c.type',
                'c.author as instructor',
            ])
            ->first();

        if ($cu) {
            $progress = (float) ($cu->progress ?? 0);
            $progressStatus = ($cu->finish == '1' || $progress >= 100) ? 'Passed' : (($progress > 0) ? 'Ongoing' : 'Not Started');
            $attendance = ($cu->finish == '1' || $progress >= 100) ? 'Yes' : 'No';

            $cleanEmpId = trim((string) ($cu->employee_id ?: '-'));
            if ($cleanEmpId !== '-') {
                $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
            }

            $empName = trim((string) ($cu->employee_name ?: '-'));
            if ($empName !== '-') {
                $empName = strtoupper($empName);
            }

            // Assessment L2 lookup
            $assessmentResult = DB::table('assesment_results as ar')
                ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
                ->where('ar.user_id', $cu->user_id)
                ->where('a.content_id', $cu->content_id)
                ->where('ar.final', 1)
                ->orderByDesc('ar.score')
                ->orderByDesc('ar.id')
                ->select('ar.score', 'ar.pass')
                ->first();
            $l2Status = $assessmentResult
                ? ($assessmentResult->pass == 1 ? 'Pass' : ($assessmentResult->pass === 0 || $assessmentResult->pass === '0' ? 'Fail' : '-'))
                : '-';

            // Certificate lookup
            $cert = DB::table('certificates as cert')
                ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
                ->where('cert.user_id', $cu->user_id)
                ->where('cert.content_user_id', $cu->id)
                ->select([
                    'cert.issued_at',
                    'cert.issued_date',
                    'cert.expired_date',
                    'cert.institution as cert_institution',
                    'cs.issuer_name',
                    'cs.institution as cs_institution',
                    'cs.issued_date as cs_issued_date',
                    'cs.expired_date as cs_expired_date',
                ])
                ->first();
            if (!$cert && $cu->content_id) {
                $cert = DB::table('certificates as cert')
                    ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
                    ->where('cert.user_id', $cu->user_id)
                    ->where('cert.source_id', $cu->content_id)
                    ->where('cert.certificate_type', 'content')
                    ->select([
                        'cert.issued_at',
                        'cert.issued_date',
                        'cert.expired_date',
                        'cert.institution as cert_institution',
                        'cs.issuer_name',
                        'cs.institution as cs_institution',
                        'cs.issued_date as cs_issued_date',
                        'cs.expired_date as cs_expired_date',
                    ])
                    ->first();
            }

            $cs = null;
            if (!$cert && $cu->content_id) {
                $cs = DB::table('certificate_settings')->where('content_id', $cu->content_id)->first();
            }

            $certInstitution = $cert?->cert_institution
                ?: ($cert?->cs_institution
                    ?: ($certSetting?->institution
                        ?: ($cert?->issuer_name
                            ?: ($cs?->institution
                                ?: ($cs?->issuer_name
                                    ?: ($cu->finish == '1' || $progress >= 100 ? 'PT Krakatau Posco' : '-'))))));

            $certIssuedDate = $cert?->issued_date
                ?: ($cs?->issued_date
                    ? date('Y-m-d', strtotime($cs->issued_date))
                    : ($cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : '-'));

            $certExpiredDate = $cert?->expired_date
                ?: ($cs?->expired_date
                    ? date('Y-m-d', strtotime($cs->expired_date))
                    : '-');

            $certAvailability = ($cert || $cs || $cu->finish == '1' || $progress >= 100) ? 'Available' : 'Not Available';

            $recordData = [
                'source'                  => 'KP OWL',
                'course_title'            => $cu->course_title ?: 'Untitled Content',
                'sub_course_title'        => '-',
                'employee_id'             => $cleanEmpId,
                'employee_name'           => $empName,
                'grade'                   => $cu->grade ?: '-',
                'job_position'            => $cu->job_position ?: '-',
                'team'                    => $cu->team ?: '-',
                'level'                   => '-',
                'batch'                   => '-',
                'provider'                => 'Internal',
                'sub_provider'            => '-',
                'provider_name'           => 'KP-OWL',
                'venue'                   => 'KP-OWL',
                'delivery_method'         => 'Online',
                'sub_delivery_method'     => 'Self-Paced / LMS',
                'instructor'              => $cu->instructor ?: '-',
                'attendance'              => $attendance,
                'attendance_reason'       => '-',
                'progress_status'         => $progressStatus,
                'bond_status'             => 'No',
                'bond_duration'           => '-',
                'bond_end_date'           => '-',
                'bond_amount'             => '-',
                'bond_amount_idr'         => '-',
                'l2_status'               => $l2Status,
                'l3_status'               => '-',
                'certificate_availability'=> $certAvailability,
                'certificate_status'      => $certAvailability,
                'certificate_institution' => $certInstitution,
                'certificate_issued_date' => $certIssuedDate,
                'certificate_expired_date'=> $certExpiredDate,
            ];

            return response()->json(array_merge($recordData, [
                'success'            => true,
                'data'               => $recordData,
                'source_type'        => 'KP OWL',
            ]));
        }

        // 3. Check playlist_content_users
        $pcu = DB::table('playlist_content_users as pcu')
            ->join('users as u', 'pcu.user_id', '=', 'u.id')
            ->leftJoin('content as c', 'pcu.content_id', '=', 'c.id')
            ->leftJoin('trainingplan as tp', 'pcu.trainingplan_id', '=', 'tp.id')
            ->where('pcu.id', $id)
            ->select([
                'pcu.id',
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                'pcu.progress',
                'pcu.finish',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.jobgrade as grade',
                'u.jobposition as job_position',
                'u.team',
                'u.department',
                'u.company_name as company',
                'c.title as course_title',
                'tp.title as playlist_title',
                'tp.instructor as playlist_instructor',
                'c.type',
                'c.author as instructor',
            ])
            ->first();

        if ($pcu) {
            $progress = (float) ($pcu->progress ?? 0);
            $progressStatus = ($pcu->finish == '1' || $progress >= 100) ? 'Passed' : (($progress > 0) ? 'Ongoing' : 'Not Started');
            $attendance = ($pcu->finish == '1' || $progress >= 100) ? 'Yes' : 'No';

            $cleanEmpId = trim((string) ($pcu->employee_id ?: '-'));
            if ($cleanEmpId !== '-') {
                $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
            }

            $empName = trim((string) ($pcu->employee_name ?: '-'));
            if ($empName !== '-') {
                $empName = strtoupper($empName);
            }

            // Assessment L2 lookup
            $assessmentResult = DB::table('assesment_results as ar')
                ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
                ->where('ar.user_id', $pcu->user_id)
                ->where(function ($q) use ($pcu) {
                    if ($pcu->trainingplan_id) {
                        $q->where('a.trainingplan_id', $pcu->trainingplan_id);
                    }
                    if ($pcu->content_id) {
                        $q->orWhere('a.content_id', $pcu->content_id);
                    }
                })
                ->where('ar.final', 1)
                ->orderByDesc('ar.score')
                ->orderByDesc('ar.id')
                ->select('ar.score', 'ar.pass')
                ->first();
            $l2Status = $assessmentResult
                ? ($assessmentResult->pass == 1 ? 'Pass' : ($assessmentResult->pass === 0 || $assessmentResult->pass === '0' ? 'Fail' : '-'))
                : '-';

            // Certificate lookup
            $cert = DB::table('certificates as cert')
                ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
                ->where('cert.user_id', $pcu->user_id)
                ->where(function ($q) use ($pcu) {
                    if ($pcu->trainingplan_id) {
                        $q->where('cert.source_id', $pcu->trainingplan_id)->where('cert.certificate_type', 'playlist');
                    }
                    if ($pcu->content_id) {
                        $q->orWhere(function ($sub) use ($pcu) {
                            $sub->where('cert.source_id', $pcu->content_id)->where('cert.certificate_type', 'content');
                        });
                    }
                })
                ->select([
                    'cert.issued_at',
                    'cert.issued_date',
                    'cert.expired_date',
                    'cert.institution as cert_institution',
                    'cs.issuer_name',
                    'cs.institution as cs_institution',
                    'cs.issued_date as cs_issued_date',
                    'cs.expired_date as cs_expired_date',
                ])
                ->first();

            $cs = null;
            if (!$cert) {
                if ($pcu->trainingplan_id) {
                    $cs = DB::table('certificate_settings')->where('trainingplan_id', $pcu->trainingplan_id)->first();
                }
                if (!$cs && $pcu->content_id) {
                    $cs = DB::table('certificate_settings')->where('content_id', $pcu->content_id)->first();
                }
            }

            $certInstitution = $cert?->cert_institution
                ?: ($cert?->cs_institution
                    ?: ($cs?->institution
                        ?: ($cert?->issuer_name
                            ?: ($cs?->issuer_name
                                ?: ($pcu->finish == '1' || $progress >= 100 ? 'PT Krakatau Posco' : '-')))));

            $certIssuedDate = $cert?->issued_date
                ?: ($cs?->issued_date
                    ? date('Y-m-d', strtotime($cs->issued_date))
                    : ($cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : '-'));

            $certExpiredDate = $cert?->expired_date
                ?: ($cs?->expired_date
                    ? date('Y-m-d', strtotime($cs->expired_date))
                    : '-');

            $certAvailability = ($cert || $cs || $pcu->finish == '1' || $progress >= 100) ? 'Available' : 'Not Available';

            $recordData = [
                'source'                  => 'KP OWL',
                'course_title'            => $pcu->playlist_title ?: ($pcu->course_title ?: 'Playlist'),
                'sub_course_title'        => $pcu->playlist_title && $pcu->course_title ? $pcu->course_title : '-',
                'employee_id'             => $cleanEmpId,
                'employee_name'           => $empName,
                'grade'                   => $pcu->grade ?: '-',
                'job_position'            => $pcu->job_position ?: '-',
                'team'                    => $pcu->team ?: '-',
                'level'                   => '-',
                'batch'                   => '-',
                'provider'                => 'Internal',
                'sub_provider'            => '-',
                'provider_name'           => 'KP-OWL',
                'venue'                   => 'KP-OWL',
                'delivery_method'         => 'Online',
                'sub_delivery_method'     => 'Playlist / Path',
                'instructor'              => $pcu->playlist_instructor ?: ($pcu->instructor ?: '-'),
                'attendance'              => $attendance,
                'attendance_reason'       => '-',
                'progress_status'         => $progressStatus,
                'bond_status'             => 'No',
                'bond_duration'           => '-',
                'bond_end_date'           => '-',
                'bond_amount'             => '-',
                'bond_amount_idr'         => '-',
                'l2_status'               => $l2Status,
                'l3_status'               => '-',
                'certificate_availability'=> $certAvailability,
                'certificate_status'      => $certAvailability,
                'certificate_institution' => $certInstitution,
                'certificate_issued_date' => $certIssuedDate,
                'certificate_expired_date'=> $certExpiredDate,
            ];

            return response()->json(array_merge($recordData, [
                'success'            => true,
                'data'               => $recordData,
                'source_type'        => 'KP OWL',
            ]));
        }

        return response()->json([
            'success'                  => false,
            'message'                  => 'Record not found',
            'source'                   => '-',
            'course_title'             => '-',
            'sub_course_title'         => '-',
            'employee_id'              => '-',
            'employee_name'            => '-',
            'grade'                    => '-',
            'job_position'             => '-',
            'level'                    => '-',
            'batch'                    => '-',
            'sub_provider'             => '-',
            'delivery_method'          => '-',
            'sub_delivery_method'      => '-',
            'instructor'               => '-',
            'progress_status'          => '-',
            'attendance'               => '-',
            'attendance_reason'        => '-',
            'l2_status'                => '-',
            'l3_status'                => '-',
            'bond_status'              => 'No',
            'bond_duration'            => '-',
            'bond_end_date'            => '-',
            'bond_amount'              => '-',
            'bond_amount_idr'          => '-',
            'certificate_availability' => 'Not Available',
            'certificate_status'       => 'Not Available',
            'certificate_institution'  => '-',
            'certificate_issued_date'  => '-',
            'certificate_expired_date' => '-',
        ], 404);
    }
}
