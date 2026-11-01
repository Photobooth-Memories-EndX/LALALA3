<?php

namespace App\Http\Controllers;

use App\Exports\Manage\AllRecordExport;
use App\Exports\UserProfileLearningExport;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\User\UserProfileLearningHistoryService;
use App\Support\DurationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProfileUserController extends Controller
{
    public function index(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        // Handle export actions via query parameters if requested directly
        if ($request->input('action') === 'download' || $request->input('format') === 'excel') {
            return $this->exportExcel($request, $hash);
        }
        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            return $this->exportCsv($request, $hash);
        }
        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            return $this->exportPdf($request, $hash);
        }

        $user_id = $user->id;
        $content = ContentUser::with(['content', 'content.classification'])->whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->get();

        // Calculate combined stats across KP OWL and Non KP OWL
        $combinedQuery = UserProfileLearningHistoryService::buildCombinedQuery($user, []);
        $combinedRecords = (clone $combinedQuery)->get();

        $totalLearning = $combinedRecords->count();
        $totalFinished = $combinedRecords->filter(fn ($r) => ($r->status ?? '') === 'Finished' || (float) ($r->progress ?? 0) >= 100)->count();
        $totalOngoing = $combinedRecords->filter(fn ($r) => ($r->status ?? '') === 'Ongoing' || ((float) ($r->progress ?? 0) > 0 && ($r->status ?? '') !== 'Finished' && (float) ($r->progress ?? 0) < 100))->count();
        $learningHours = round($combinedRecords->sum(fn ($r) => (float) ($r->learning_hours ?? 0)), 1);

        $lastLearning = $combinedRecords->first()?->learning_title ?? '-';

        $favoriteCategory = $combinedRecords
            ->groupBy('classification')
            ->filter(fn ($items, $key) => !empty($key) && $key !== 'Unclassified')
            ->sortByDesc(fn ($items) => $items->count())
            ->keys()
            ->first() ?? ($combinedRecords->first()?->classification ?? '-');

        $filterOptions = UserProfileLearningHistoryService::getFilterOptions($user);

        return view('main.profile.index', [
            "profile"          => $user,
            "content"          => $content,
            'totalLearning'    => $totalLearning,
            'totalFinished'    => $totalFinished,
            'totalOngoing'     => $totalOngoing,
            'lastLearning'     => $lastLearning,
            'favoriteCategory' => $favoriteCategory,
            'learningHours'    => $learningHours,
            'classifications'  => $filterOptions['classifications'],
            'trainingTypes'    => $filterOptions['trainingTypes'],
            'years'            => $filterOptions['years'],
        ]);
    }

    /**
     * Server-side DataTables AJAX endpoint for user's learning history (34 UI columns).
     */
    public function learningDatatable(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        return UserProfileLearningHistoryService::datatable($user, $request);
    }

    /**
     * Export user learning history to Excel (.xlsx) with all 52 fields.
     */
    public function exportExcel(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $filename = sprintf(
            '%s_%s_learning_history.xlsx',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $user->id_emp));
        $filters = array_merge($request->all(), [
            'exact_participant' => [
                'user_id'     => $user->id,
                'employee_id' => $cleanEmpId,
                'name'        => $user->name,
            ],
        ]);

        return (new AllRecordExport($filters, $filename))->download($filename);
    }

    /**
     * Export user learning history to CSV (.csv) with all 52 fields.
     */
    public function exportCsv(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $filename = sprintf(
            '%s_%s_learning_history.csv',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        $tempCsv = tempnam(sys_get_temp_dir(), 'owl_usr_csv_');
        $handle = fopen($tempCsv, 'w+');

        // UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        // 52 CSV Headings
        fputcsv($handle, AllRecordExport::headings());

        $query = UserProfileLearningHistoryService::buildCombinedQuery($user, $request->all())
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');

        $rowNum = 0;
        foreach ($query->cursor() as $row) {
            $rowNum++;

            $isFinished = (($row->status ?? '') === 'Finished' || (float) ($row->progress ?? 0) >= 100);
            $durSec = (int) ($row->duration_seconds ?? 0);
            $recSec = (int) ($row->recognized_duration_seconds ?? 0);
            $durFmt = $durSec > 0 ? DurationFormatter::fromSeconds($durSec) : '-';
            $recFmt = $recSec > 0 ? DurationFormatter::fromSeconds($recSec) : '-';

            $afkSec = (int) ($row->afk_duration_seconds ?? 0);
            if ($afkSec <= 0 && $row->source === 'offline') {
                $afkSec = (int) round(((float) ($row->learning_hours ?? 0)) * 3600);
            }
            $engFmt = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : '-';

            $startDate = !empty($row->start_date) && $row->start_date !== '-' ? date('Y-m-d', strtotime($row->start_date)) : '-';
            $endDate = !empty($row->end_date) && $row->end_date !== '-' ? date('Y-m-d', strtotime($row->end_date)) : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');
            $startTime = !empty($row->start_time) && $row->start_time !== '-' ? date('H:i:s', strtotime($row->start_time)) : '-';
            $endTime = !empty($row->end_time) && $row->end_time !== '-' ? date('H:i:s', strtotime($row->end_time)) : '-';

            $recordMonth = ($endDate !== '-') ? date('F', strtotime($endDate)) : '-';
            $attendance = (in_array(strtolower(trim((string) ($row->attendance ?? ''))), ['1', 'yes', 'present'], true) || (float) ($row->progress ?? 0) > 0 || $isFinished) ? 'Yes' : 'No';

            $certAvail = $isFinished ? 'Available' : '-';
            $certInst = $isFinished ? (($row->source ?? '') === 'online' ? 'PT Krakatau Posco' : (($row->provider_name ?? '') ?: 'PT Krakatau Posco')) : '-';
            $certIssued = ($isFinished && $endDate !== '-') ? $endDate : '-';

            fputcsv($handle, [
                $rowNum,                                                        // 1. NO
                ($row->source ?? '') === 'online' ? 'KP OWL' : 'Non KP OWL',     // 2. Source
                $row->employee_id ?? '-',                                       // 3. Employee ID
                $row->employee_name ?? $user->name,                             // 4. Employee Name
                $row->grade ?? '-',                                             // 5. Grade
                $row->job_position ?? '-',                                      // 6. Job Position
                $row->team ?? '-',                                              // 7. Team
                $row->department ?? '-',                                        // 8. Department
                $row->company ?? 'PT Krakatau Posco',                           // 9. Company
                $row->learning_title ?? '-',                                    // 10. Course Title
                $row->sub_course_name ?? '-',                                   // 11. Sub Course Title
                $row->type ?? '-',                                              // 12. Type
                $row->category ?? '-',                                          // 13. Category
                $row->level ?? '-',                                             // 14. Level
                $row->batch ?? '-',                                             // 15. Batch
                $row->classification ?? 'Unclassified',                         // 16. Main Classification
                $row->sub_classification ?? '-',                                // 17. Sub Classification
                $row->delivery_method ?? (($row->source ?? '') === 'online' ? 'Online' : 'Offline'), // 18. Delivery Method
                $row->sub_delivery_method ?? '-',                               // 19. Sub Delivery Method
                $row->provider ?? (($row->source ?? '') === 'online' ? 'Internal' : '-'), // 20. Provider
                $row->sub_provider ?? '-',                                      // 21. Sub Provider
                $row->provider_name ?? (($row->source ?? '') === 'online' ? 'KP OWL' : '-'), // 22. Provider Name
                $row->venue ?? (($row->source ?? '') === 'online' ? 'Online LMS' : '-'), // 23. Venue
                $row->instructor ?? '-',                                        // 24. Instructor
                $durFmt,                                                        // 25. Course Duration
                $recFmt,                                                        // 26. Total Recognized Duration
                round((float) ($row->learning_hours ?? 0), 2),                  // 27. Learning Hours
                ((int) round((float) ($row->progress ?? 0))) . '%',             // 28. % Progress
                $isFinished ? 'Finished' : ((float) ($row->progress ?? 0) > 0 ? 'Ongoing' : 'Not Started'), // 29. Progress Status
                $startDate,                                                     // 30. Start Date
                $endDate,                                                       // 31. End Date
                $startTime,                                                     // 32. Start Time
                $endTime,                                                       // 33. End Time
                $recordMonth,                                                   // 34. Record Month
                $row->requirement ?? 'Non-Mandatory',                           // 35. Requirement
                $attendance,                                                    // 36. Attendance
                $row->attendance_reason ?? '-',                                 // 37. Attendance Reason
                $row->bond_status ?? 'No',                                      // 38. Bond Status
                $row->bond_duration ?? '-',                                     // 39. Bond Duration
                $row->bond_end_date ?? '-',                                     // 40. Bond End Date
                $row->bond_amount ?? '-',                                       // 41. Bond Amount
                $row->l1_program ?? ($row->assessment_score ?? '-'),            // 42. Score (L1) - Course
                $row->l2_numerical ?? '-',                                      // 43. Score (L2)
                $row->l2_verbal ?? ($isFinished ? 'Passed' : '-'),              // 44. L2 Status
                $row->l3_numerical ?? '-',                                      // 45. Score (L3)
                $row->l3_verbal ?? '-',                                         // 46. L3 Status
                $certAvail,                                                     // 47. Certificate Availability
                $certInst,                                                      // 48. Certificate Institution
                $certIssued,                                                    // 49. Certificate Issued Date
                $row->certificate_expired_date ?? '-',                          // 50. Certificate Expired Date
                $row->pic ?? '-',                                               // 51. Person in Charge (PIC)
                $engFmt,                                                        // 52. Total Engagement Time
            ]);
        }

        fclose($handle);

        return response()->download($tempCsv, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export user learning history to PDF (.pdf).
     */
    public function exportPdf(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $records = UserProfileLearningHistoryService::buildCombinedQuery($user, $request->all())
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc')
            ->get();

        $activeFilters = UserProfileLearningHistoryService::activeFilterLabels($request->all());

        $filename = sprintf(
            '%s_%s_learning_history.pdf',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        $pdf = Pdf::loadView('main.profile.export-pdf', [
            'profile'       => $user,
            'records'       => $records,
            'activeFilters' => $activeFilters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Return 17+ Detail fields for a single record on the user profile.
     */
    public function learningRecordDetail(Request $request, string $hash, string $source, $id)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $detailService = app(\App\Services\Manage\RecordDetailService::class);
        $detail = $detailService->getDetail($source, $id);

        return response()->json(array_merge([
            'success'     => $detail['success'] ?? true,
            'data'        => $detail,
            'source_type' => $detail['source'] ?? 'KP OWL',
        ], $detail));
    }

    public function editOutsourcing(User $user)
    {
        return view('manage.user.edit_outsourcing', [
            'user' => $user,
        ]);
    }
}
