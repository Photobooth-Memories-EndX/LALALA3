<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Http\Controllers\Manage\Record\RecordOnlineController;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Unified Detail endpoint returning More Information non-yellow attributes
     */
    public function moreInformation(Request $request, $id): JsonResponse
    {
        $source = strtolower(trim((string) $request->input('source', 'offline')));

        if ($source === 'offline') {
            $record = DB::table('offline_training_users as otu')
                ->leftJoin('offline_trainings as ot', 'otu.offline_training_id', '=', 'ot.id')
                ->where('otu.id', $id)
                ->select([
                    'otu.id',
                    'otu.employee_id',
                    'otu.name as employee_name',
                    'otu.grade',
                    'otu.job_position',
                    'otu.team',
                    'otu.department',
                    'otu.company',
                    'otu.attendance',
                    'otu.attendance_reason',
                    'otu.bond_status',
                    'otu.bond_duration',
                    'otu.bond_end_date',
                    'otu.bond_amount_idr',
                    'otu.l1_program',
                    'otu.l2_numerical',
                    'otu.l2_verbal',
                    'otu.l3_numerical',
                    'otu.l3_verbal',
                    'otu.pic',
                    'otu.certificate',
                    'ot.course_name as course_title',
                    'ot.sub_course_name as sub_course_title',
                    'ot.type',
                    'ot.category',
                    'ot.level',
                    'ot.batch',
                    'ot.instructor',
                ])
                ->first();

            if ($record) {
                $bondAmt = ($record->bond_amount_idr !== null && (float) $record->bond_amount_idr > 0)
                    ? 'Rp ' . number_format((float) $record->bond_amount_idr, 0, ',', '.')
                    : '-';

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'grade'              => $record->grade ?: '-',
                        'job_position'        => $record->job_position ?: '-',
                        'sub_course_title'   => $record->sub_course_title ?: '-',
                        'level'              => $record->level ?: '-',
                        'batch'              => $record->batch ?: '-',
                        'instructor'         => $record->instructor ?: '-',
                        'attendance_reason'  => $record->attendance_reason ?: '-',
                        'bond_status'        => $record->bond_status ?: 'No',
                        'bond_duration'      => !empty($record->bond_duration) ? $record->bond_duration . ' Months' : '-',
                        'bond_end_date'      => $record->bond_end_date ? date('Y-m-d', strtotime($record->bond_end_date)) : '-',
                        'bond_amount_idr'    => $bondAmt,
                        'l2_status'          => $record->l2_verbal ?: '-',
                        'l3_status'          => $record->l3_verbal ?: '-',
                        'certificate_status' => !empty($record->certificate) ? 'Available' : 'Not Available',
                    ],
                ]);
            }

            // Fallback: check offline_trainings directly by id
            $training = DB::table('offline_trainings')->where('id', $id)->first();
            if ($training) {
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'grade'              => '-',
                        'job_position'        => '-',
                        'sub_course_title'   => $training->sub_course_name ?: '-',
                        'level'              => $training->level ?: '-',
                        'batch'              => $training->batch ?: '-',
                        'instructor'         => $training->instructor ?: '-',
                        'attendance_reason'  => '-',
                        'bond_status'        => 'No',
                        'bond_duration'      => '-',
                        'bond_end_date'      => '-',
                        'bond_amount_idr'    => '-',
                        'l2_status'          => '-',
                        'l3_status'          => '-',
                        'certificate_status' => 'Not Available',
                    ],
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Record not found'], 404);
        }

        // Online record: check content_users first
        $cu = DB::table('content_users as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->leftJoin('contents as c', 'cu.content_id', '=', 'c.id')
            ->leftJoin('classifications as cl', 'c.classification_id', '=', 'cl.id')
            ->where('cu.id', $id)
            ->select([
                'cu.id',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.team',
                'u.department',
                'u.company_name as company',
                'c.title as course_title',
                'c.type',
                'c.author as instructor',
            ])
            ->first();

        if ($cu) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'grade'              => '-',
                    'job_position'        => '-',
                    'sub_course_title'   => '-',
                    'level'              => '-',
                    'batch'              => '-',
                    'instructor'         => $cu->instructor ?: '-',
                    'attendance_reason'  => '-',
                    'bond_status'        => 'No',
                    'bond_duration'      => '-',
                    'bond_end_date'      => '-',
                    'bond_amount_idr'    => '-',
                    'l2_status'          => '-',
                    'l3_status'          => '-',
                    'certificate_status' => 'Not Available',
                ],
            ]);
        }

        // Check playlist_content_users
        $pcu = DB::table('playlist_content_users as pcu')
            ->join('users as u', 'pcu.user_id', '=', 'u.id')
            ->leftJoin('contents as c', 'pcu.content_id', '=', 'c.id')
            ->leftJoin('trainingplans as tp', 'pcu.trainingplan_id', '=', 'tp.id')
            ->where('pcu.id', $id)
            ->select([
                'pcu.id',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.team',
                'u.department',
                'u.company_name as company',
                'c.title as course_title',
                'tp.title as playlist_title',
                'c.type',
                'c.author as instructor',
            ])
            ->first();

        if ($pcu) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'grade'              => '-',
                    'job_position'        => '-',
                    'sub_course_title'   => $pcu->playlist_title ?: '-',
                    'level'              => '-',
                    'batch'              => '-',
                    'instructor'         => $pcu->instructor ?: '-',
                    'attendance_reason'  => '-',
                    'bond_status'        => 'No',
                    'bond_duration'      => '-',
                    'bond_end_date'      => '-',
                    'bond_amount_idr'    => '-',
                    'l2_status'          => '-',
                    'l3_status'          => '-',
                    'certificate_status' => 'Not Available',
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Online record not found'], 404);
    }
}
