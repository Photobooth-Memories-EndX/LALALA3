<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== APPLYING ALL PLAYLIST COLUMN AND CONTENT PLAYLIST UPDATES ===\n";

// -----------------------------------------------------------------
// 1. Revert LearningDatatableService cuSelect query to original fast form
// -----------------------------------------------------------------
$ldPath = 'c:/laragon/www/owl/app/Services/Learning/LearningDatatableService.php';
$ldCode = file_get_contents($ldPath);

$ldCode = str_replace(
    "DB::raw(\"CASE WHEN (SELECT 1 FROM trainingplandetail tpd JOIN trainingplan tp ON tp.id = tpd.trainingplan_id WHERE tpd.content_id = cu.content_id AND tp.deleted_at IS NULL LIMIT 1) = 1 THEN 'Content Playlist' ELSE 'All Content' END as category_source\"),",
    "DB::raw(\"'All Content' as category_source\"),",
    $ldCode
);
$ldCode = str_replace(
    "DB::raw('(SELECT tpd.trainingplan_id FROM trainingplandetail tpd JOIN trainingplan tp ON tp.id = tpd.trainingplan_id WHERE tpd.content_id = cu.content_id AND tp.deleted_at IS NULL ORDER BY tpd.id ASC LIMIT 1) as trainingplan_id'),",
    "DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),",
    $ldCode
);
$ldCode = str_replace(
    "DB::raw(\"'Content Playlist' as category_source\"),\n                'pcu.user_id',",
    "DB::raw(\"'Playlist' as category_source\"),\n                'pcu.user_id',",
    $ldCode
);

// Inject TPD preloading cache in make()
$oldMakeStart = '        $query = self::buildBaseQuery($request->all());';
$newMakeStart = '        // Pre-query content to playlist map for instant resolution without N+1 or slow subqueries' . "\n"
    . '        $tpdCache = [];' . "\n"
    . '        $tpIdCache = [];' . "\n"
    . '        $tpdRows = DB::table(\'trainingplandetail as tpd\')' . "\n"
    . '            ->join(\'trainingplan as tp\', \'tp.id\', \'=\', \'tpd.trainingplan_id\')' . "\n"
    . '            ->whereNull(\'tp.deleted_at\')' . "\n"
    . '            ->orderBy(\'tpd.id\', \'asc\')' . "\n"
    . '            ->select(\'tpd.content_id\', \'tp.id as tp_id\', \'tp.title\')' . "\n"
    . '            ->get();' . "\n"
    . '        foreach ($tpdRows as $r) {' . "\n"
    . '            if (!isset($tpdCache[$r->content_id])) {' . "\n"
    . '                $tpdCache[$r->content_id] = trim($r->title);' . "\n"
    . '                $tpIdCache[$r->content_id] = $r->tp_id;' . "\n"
    . '            }' . "\n"
    . '        }' . "\n\n"
    . '        $query = self::buildBaseQuery($request->all());';

if (strpos($ldCode, 'Pre-query content to playlist map') === false) {
    $ldCode = str_replace($oldMakeStart, $newMakeStart, $ldCode);
}

// Update addColumn playlist and category in make()
$oldCols = "->addColumn('course_title', fn (\$row) => \$this->learningTitleColumn(\$row))\n"
    . "            ->addColumn('playlist', function (\$row) {\n"
    . "                if (!empty(\$row->playlist_title)) {\n"
    . "                    if (!empty(\$row->trainingplan_id)) {\n"
    . "                        \$url = route('learning.manage.playlist', ['id' => \$row->trainingplan_id]);\n"
    . "                        return '<a href=\"' . e(\$url) . '\" class=\"text-primary fw-semibold\" title=\"' . e(\$row->playlist_title) . '\">' . e(\$row->playlist_title) . '</a>';\n"
    . "                    }\n"
    . "                    return e(\$row->playlist_title);\n"
    . "                }\n"
    . "                return '<span class=\"text-muted small\">-</span>';\n"
    . "            })";

$newCols = "->addColumn('course_title', fn (\$row) => \$this->learningTitleColumn(\$row))\n"
    . "            ->addColumn('playlist', function (\$row) use (\$tpdCache, \$tpIdCache) {\n"
    . "                \$pTitle = !empty(\$row->playlist_title) ? trim(\$row->playlist_title) : (\$tpdCache[\$row->content_id ?? 0] ?? null);\n"
    . "                \$tpId = !empty(\$row->trainingplan_id) ? \$row->trainingplan_id : (\$tpIdCache[\$row->content_id ?? 0] ?? null);\n"
    . "                if (!empty(\$pTitle)) {\n"
    . "                    if (!empty(\$tpId)) {\n"
    . "                        \$url = route('learning.manage.playlist', ['id' => \$tpId]);\n"
    . "                        return '<a href=\"' . e(\$url) . '\" class=\"text-primary fw-semibold\" title=\"' . e(\$pTitle) . '\">' . e(\$pTitle) . '</a>';\n"
    . "                    }\n"
    . "                    return e(\$pTitle);\n"
    . "                }\n"
    . "                return '<span class=\"text-muted small\">-</span>';\n"
    . "            })\n"
    . "            ->addColumn('category', function (\$row) use (\$tpdCache) {\n"
    . "                \$pTitle = !empty(\$row->playlist_title) ? trim(\$row->playlist_title) : (\$tpdCache[\$row->content_id ?? 0] ?? null);\n"
    . "                if (!empty(\$pTitle) || \$row->category_source === 'Playlist' || \$row->category_source === 'Content Playlist') {\n"
    . "                    return '<span class=\"badge bg-purple-lt text-purple\">Content Playlist</span>';\n"
    . "                }\n"
    . "                return '<span class=\"badge bg-azure-lt text-azure\">All Content</span>';\n"
    . "            })";

$ldCode = str_replace($oldCols, $newCols, $ldCode);

file_put_contents($ldPath, $ldCode);
echo "1. LearningDatatableService updated successfully.\n";

// -----------------------------------------------------------------
// 2. Update ManageOnlineRecordExportService.php
// -----------------------------------------------------------------
$mosPath = 'c:/laragon/www/owl/app/Services/Record/ManageOnlineRecordExportService.php';
$mosCode = file_get_contents($mosPath);

// Headings: insert 'Playlist' after 'Course Title'
$oldMosHeadings = "            'Course Title',\n            'Sub Course Title',";
$newMosHeadings = "            'Course Title',\n            'Playlist',\n            'Sub Course Title',";
$mosCode = str_replace($oldMosHeadings, $newMosHeadings, $mosCode);

// Column widths: update for J => 35 (Playlist)
$oldMosWidths = "            'I'  => 35,  // Course Title\n            'J'  => 25,  // Sub Course Title\n            'K'  => 15,  // Type\n            'L'  => 18,  // Category\n            'M'  => 12,  // Level\n            'N'  => 12,  // Batch\n            'O'  => 25,  // Main Classification\n            'P'  => 25,  // Sub Classification\n            'Q'  => 18,  // Delivery Method\n            'R'  => 20,  // Sub Delivery Method\n            'S'  => 16,  // Provider\n            'T'  => 18,  // Sub Provider\n            'U'  => 25,  // Provider Name\n            'V'  => 20,  // Venue\n            'W'  => 22,  // Instructor\n            'X'  => 16,  // Course Duration\n            'Y'  => 22,  // Total Recognized Duration\n            'Z'  => 16,  // Learning Hours\n            'AA' => 14,  // % Progress\n            'AB' => 16,  // Progress Status\n            'AC' => 14,  // Start Date\n            'AD' => 14,  // End Date\n            'AE' => 14,  // Start Time\n            'AF' => 14,  // End Time\n            'AG' => 16,  // Record Month\n            'AH' => 15,  // Requirement\n            'AI' => 14,  // Attendance\n            'AJ' => 25,  // Attendance Reason\n            'AK' => 14,  // Bond Status\n            'AL' => 16,  // Bond Duration\n            'AM' => 16,  // Bond End Date\n            'AN' => 18,  // Bond Amount\n            'AO' => 20,  // Score (L1) - Course\n            'AP' => 15,  // Score (L2)\n            'AQ' => 15,  // L2 Status\n            'AR' => 15,  // Score (L3)\n            'AS' => 15,  // L3 Status\n            'AT' => 22,  // Certificate Availability\n            'AU' => 25,  // Certificate Institution\n            'AV' => 20,  // Certificate Issued Date\n            'AW' => 20,  // Certificate Expired Date\n            'AX' => 25,  // Person in Charge (PIC)\n            'AY' => 25,  // Total Engagement Time (LH)";

$newMosWidths = "            'I'  => 35,  // Course Title\n            'J'  => 35,  // Playlist\n            'K'  => 25,  // Sub Course Title\n            'L'  => 15,  // Type\n            'M'  => 18,  // Category\n            'N'  => 12,  // Level\n            'O'  => 12,  // Batch\n            'P'  => 25,  // Main Classification\n            'Q'  => 25,  // Sub Classification\n            'R'  => 18,  // Delivery Method\n            'S'  => 20,  // Sub Delivery Method\n            'T'  => 16,  // Provider\n            'U'  => 18,  // Sub Provider\n            'V'  => 25,  // Provider Name\n            'W'  => 20,  // Venue\n            'X'  => 22,  // Instructor\n            'Y'  => 16,  // Course Duration\n            'Z'  => 22,  // Total Recognized Duration\n            'AA' => 16,  // Learning Hours\n            'AB' => 14,  // % Progress\n            'AC' => 16,  // Progress Status\n            'AD' => 14,  // Start Date\n            'AE' => 14,  // End Date\n            'AF' => 14,  // Start Time\n            'AG' => 14,  // End Time\n            'AH' => 16,  // Record Month\n            'AI' => 15,  // Requirement\n            'AJ' => 14,  // Attendance\n            'AK' => 25,  // Attendance Reason\n            'AL' => 14,  // Bond Status\n            'AM' => 16,  // Bond Duration\n            'AN' => 16,  // Bond End Date\n            'AO' => 18,  // Bond Amount\n            'AP' => 20,  // Score (L1) - Course\n            'AQ' => 15,  // Score (L2)\n            'AR' => 15,  // L2 Status\n            'AS' => 15,  // Score (L3)\n            'AT' => 15,  // L3 Status\n            'AU' => 22,  // Certificate Availability\n            'AV' => 25,  // Certificate Institution\n            'AW' => 20,  // Certificate Issued Date\n            'AX' => 20,  // Certificate Expired Date\n            'AY' => 25,  // Person in Charge (PIC)\n            'AZ' => 25,  // Total Engagement Time (LH)";

$mosCode = str_replace($oldMosWidths, $newMosWidths, $mosCode);

// mapRow in ManageOnlineRecordExportService
$oldMapRowLogic = "        // 2. Category determination & Parent Playlist derivation\n"
    . "        \$isContentInPlaylist = !empty(\$row->trainingplan_id) && !empty(\$row->content_id);\n"
    . "        if (\$isContentInPlaylist) {\n"
    . "            \$category = 'Content Playlist';\n"
    . "        } elseif (\$isPlaylist) {\n"
    . "            \$category = 'Playlist';\n"
    . "        } else {\n"
    . "            \$category = 'All Content';\n"
    . "        }\n\n"
    . "        // Parent Playlist Column Logic:\n"
    . "        // If Category == 'Content Playlist', fetch parent Playlist title of current Course Title; otherwise '-'\n"
    . "        \$parentPlaylistTitle = '-';\n"
    . "        if (\$category === 'Content Playlist') {\n"
    . "            \$parentPlaylistTitle = !empty(\$row->playlist_title)\n"
    . "                ? trim((string) \$row->playlist_title)\n"
    . "                : \$this->resolveParentPlaylistTitle((int) (\$row->content_id ?? 0), (int) (\$row->trainingplan_id ?? 0));\n"
    . "        }";

$newMapRowLogic = "        // 2. Playlist & Category determination\n"
    . "        \$resolvedPlaylist = !empty(\$row->playlist_title)\n"
    . "            ? trim((string) \$row->playlist_title)\n"
    . "            : \$this->resolveParentPlaylistTitle((int) (\$row->content_id ?? 0), (int) (\$row->trainingplan_id ?? 0));\n\n"
    . "        if (\$resolvedPlaylist !== '' && \$resolvedPlaylist !== '-') {\n"
    . "            \$parentPlaylistTitle = \$resolvedPlaylist;\n"
    . "            \$category = 'Content Playlist';\n"
    . "        } else {\n"
    . "            \$parentPlaylistTitle = '-';\n"
    . "            \$category = 'All Content';\n"
    . "        }";

$mosCode = str_replace($oldMapRowLogic, $newMapRowLogic, $mosCode);

// Return array in mapRow
$oldMapReturn = "            'course_title'              => \$courseTitle,                  // 9. Course Title\n"
    . "            'sub_course_title'          => \$subCourseTitle,               // 10. Sub Course Title (Strictly '-')\n"
    . "            'type'                      => \$typeVal,                      // 11. Type\n"
    . "            'category'                  => \$category,                     // 12. Category";

$newMapReturn = "            'course_title'              => \$courseTitle,                  // 9. Course Title\n"
    . "            'playlist'                  => \$parentPlaylistTitle,          // 10. Playlist\n"
    . "            'sub_course_title'          => \$subCourseTitle,               // 11. Sub Course Title (Strictly '-')\n"
    . "            'type'                      => \$typeVal,                      // 12. Type\n"
    . "            'category'                  => \$category,                     // 13. Category";

$mosCode = str_replace($oldMapReturn, $newMapReturn, $mosCode);

// Sub Delivery Method for KP OWL
$mosCode = str_replace(
    "'sub_delivery_method'       => 'E-learning',",
    "'sub_delivery_method'       => 'KP OWL',",
    $mosCode
);

// In generateXlsxFile: slice 52
$mosCode = str_replace(
    "\$values = array_values(array_slice(\$mapped, 0, 51));",
    "\$values = array_values(array_slice(\$mapped, 0, 52));",
    $mosCode
);

// In generateXlsxFile: update column rules for shifted columns
$oldXmlColChecks = "} elseif (\$colLetter === 'X' || \$colLetter === 'Y' || \$colLetter === 'AY') {";
$newXmlColChecks = "} elseif (\$colLetter === 'Y' || \$colLetter === 'Z' || \$colLetter === 'AZ') {";
$mosCode = str_replace($oldXmlColChecks, $newXmlColChecks, $mosCode);

$oldXmlTimeChecks = "} elseif (\$colLetter === 'AE' || \$colLetter === 'AF') {";
$newXmlTimeChecks = "} elseif (\$colLetter === 'AF' || \$colLetter === 'AG') {";
$mosCode = str_replace($oldXmlTimeChecks, $newXmlTimeChecks, $mosCode);

$oldXmlScoreChecks = "} elseif (\$colLetter === 'Z' || \$colLetter === 'AO' || \$colLetter === 'AP' || \$colLetter === 'AR') {";
$newXmlScoreChecks = "} elseif (\$colLetter === 'AA' || \$colLetter === 'AP' || \$colLetter === 'AQ' || \$colLetter === 'AS') {";
$mosCode = str_replace($oldXmlScoreChecks, $newXmlScoreChecks, $mosCode);

$oldXmlPctChecks = "} elseif (\$colLetter === 'AA') {";
$newXmlPctChecks = "} elseif (\$colLetter === 'AB') {";
$mosCode = str_replace($oldXmlPctChecks, $newXmlPctChecks, $mosCode);

$oldXmlBondChecks = "} elseif (\$colLetter === 'AK' || \$colLetter === 'AL' || \$colLetter === 'AM' || \$colLetter === 'AN') {";
$newXmlBondChecks = "} elseif (\$colLetter === 'AL' || \$colLetter === 'AM' || \$colLetter === 'AN' || \$colLetter === 'AO') {";
$mosCode = str_replace($oldXmlBondChecks, $newXmlBondChecks, $mosCode);

$oldXmlDateChecks = "} elseif (\$colLetter === 'AC' || \$colLetter === 'AD' || \$colLetter === 'AV' || \$colLetter === 'AW') {";
$newXmlDateChecks = "} elseif (\$colLetter === 'AD' || \$colLetter === 'AE' || \$colLetter === 'AW' || \$colLetter === 'AX') {";
$mosCode = str_replace($oldXmlDateChecks, $newXmlDateChecks, $mosCode);

file_put_contents($mosPath, $mosCode);
echo "2. ManageOnlineRecordExportService updated successfully.\n";

// -----------------------------------------------------------------
// 3. Update ClassificationOnlineRecordExport.php
// -----------------------------------------------------------------
$corePath = 'c:/laragon/www/owl/app/Exports/Manage/ClassificationOnlineRecordExport.php';
$coreCode = file_get_contents($corePath);

// Headers
$oldCoreHeaders = "            'Course Title',\n            'Sub Course Title',";
$newCoreHeaders = "            'Course Title',\n            'Playlist',\n            'Sub Course Title',";
$coreCode = str_replace($oldCoreHeaders, $newCoreHeaders, $coreCode);

// Column widths
$oldCoreWidths = "            'I'  => 35,  // Course Title\n            'J'  => 25,  // Sub Course Title\n            'K'  => 15,  // Type\n            'L'  => 18,  // Category\n            'M'  => 12,  // Level\n            'N'  => 12,  // Batch\n            'O'  => 25,  // Main Classification\n            'P'  => 25,  // Sub Classification\n            'Q'  => 18,  // Delivery Method\n            'R'  => 20,  // Sub Delivery Method\n            'S'  => 16,  // Provider\n            'T'  => 18,  // Sub Provider\n            'U'  => 25,  // Provider Name\n            'V'  => 20,  // Venue\n            'W'  => 22,  // Instructor\n            'X'  => 16,  // Course Duration\n            'Y'  => 22,  // Total Recognized Duration\n            'Z'  => 16,  // Learning Hours\n            'AA' => 14,  // % Progress\n            'AB' => 16,  // Progress Status\n            'AC' => 14,  // Start Date\n            'AD' => 14,  // End Date\n            'AE' => 14,  // Start Time\n            'AF' => 14,  // End Time\n            'AG' => 16,  // Record Month\n            'AH' => 15,  // Requirement\n            'AI' => 14,  // Attendance\n            'AJ' => 25,  // Attendance Reason\n            'AK' => 14,  // Bond Status\n            'AL' => 16,  // Bond Duration\n            'AM' => 16,  // Bond End Date\n            'AN' => 18,  // Bond Amount\n            'AO' => 20,  // Score (L1) - Course\n            'AP' => 15,  // Score (L2)\n            'AQ' => 15,  // L2 Status\n            'AR' => 15,  // Score (L3)\n            'AS' => 15,  // L3 Status\n            'AT' => 22,  // Certificate Availability\n            'AU' => 25,  // Certificate Institution\n            'AV' => 20,  // Certificate Issued Date\n            'AW' => 20,  // Certificate Expired Date\n            'AX' => 25,  // Person in Charge (PIC)\n            'AY' => 25,  // Total Engagement Time (LH)";

$newCoreWidths = "            'I'  => 35,  // Course Title\n            'J'  => 35,  // Playlist\n            'K'  => 25,  // Sub Course Title\n            'L'  => 15,  // Type\n            'M'  => 18,  // Category\n            'N'  => 12,  // Level\n            'O'  => 12,  // Batch\n            'P'  => 25,  // Main Classification\n            'Q'  => 25,  // Sub Classification\n            'R'  => 18,  // Delivery Method\n            'S'  => 20,  // Sub Delivery Method\n            'T'  => 16,  // Provider\n            'U'  => 18,  // Sub Provider\n            'V'  => 25,  // Provider Name\n            'W'  => 20,  // Venue\n            'X'  => 22,  // Instructor\n            'Y'  => 16,  // Course Duration\n            'Z'  => 22,  // Total Recognized Duration\n            'AA' => 16,  // Learning Hours\n            'AB' => 14,  // % Progress\n            'AC' => 16,  // Progress Status\n            'AD' => 14,  // Start Date\n            'AE' => 14,  // End Date\n            'AF' => 14,  // Start Time\n            'AG' => 14,  // End Time\n            'AH' => 16,  // Record Month\n            'AI' => 15,  // Requirement\n            'AJ' => 14,  // Attendance\n            'AK' => 25,  // Attendance Reason\n            'AL' => 14,  // Bond Status\n            'AM' => 16,  // Bond Duration\n            'AN' => 16,  // Bond End Date\n            'AO' => 18,  // Bond Amount\n            'AP' => 20,  // Score (L1) - Course\n            'AQ' => 15,  // Score (L2)\n            'AR' => 15,  // L2 Status\n            'AS' => 15,  // Score (L3)\n            'AT' => 15,  // L3 Status\n            'AU' => 22,  // Certificate Availability\n            'AV' => 25,  // Certificate Institution\n            'AW' => 20,  // Certificate Issued Date\n            'AX' => 20,  // Certificate Expired Date\n            'AY' => 25,  // Person in Charge (PIC)\n            'AZ' => 25,  // Total Engagement Time (LH)";

$coreCode = str_replace($oldCoreWidths, $newCoreWidths, $coreCode);

// Add playlist memory map preloading in generateXlsxFile
$oldCorePrep = '$query = $this->query();';
$newCorePrep = '// Preload content to playlist map' . "\n"
    . '        $tpdCache = [];' . "\n"
    . '        $tpdRows = DB::table(\'trainingplandetail as tpd\')' . "\n"
    . '            ->join(\'trainingplan as tp\', \'tp.id\', \'=\', \'tpd.trainingplan_id\')' . "\n"
    . '            ->whereNull(\'tp.deleted_at\')' . "\n"
    . '            ->orderBy(\'tpd.id\', \'asc\')' . "\n"
    . '            ->select(\'tpd.content_id\', \'tp.title\')' . "\n"
    . '            ->get();' . "\n"
    . '        foreach ($tpdRows as $r) {' . "\n"
    . '            if (!isset($tpdCache[$r->content_id])) {' . "\n"
    . '                $tpdCache[$r->content_id] = trim($r->title);' . "\n"
    . '            }' . "\n"
    . '        }' . "\n\n"
    . '        $query = $this->query();';

if (strpos($coreCode, 'Preload content to playlist map') === false) {
    $coreCode = str_replace($oldCorePrep, $newCorePrep, $coreCode);
}

// In cursor loop: resolve parent playlist and category
$oldCoreTitles = "                // 2. Titles (Playlist: Course Title = Playlist Title, Sub Course Title = Content Title)\n"
    . "                if (\$isPlaylist) {\n"
    . "                    \$courseTitle = \$row->playlist_title ?: (\$row->content_title ?: 'Playlist');\n"
    . "                    \$subCourseTitle = '-';\n"
    . "                } else {\n"
    . "                    \$courseTitle = \$row->content_title ?: 'Untitled Content';\n"
    . "                    \$subCourseTitle = '-';\n"
    . "                }";

$newCoreTitles = "                // 2. Titles & Playlist resolution\n"
    . "                \$courseTitle = \$row->content_title ?: (\$row->title ?? (\$row->playlist_title ?: 'Untitled Content'));\n"
    . "                \$subCourseTitle = '-';\n"
    . "                \$resolvedPlaylist = !empty(\$row->playlist_title)\n"
    . "                    ? trim(\$row->playlist_title)\n"
    . "                    : (\$tpdCache[\$row->content_id ?? 0] ?? '-');\n"
    . "                if (\$resolvedPlaylist !== '' && \$resolvedPlaylist !== '-') {\n"
    . "                    \$parentPlaylistTitle = \$resolvedPlaylist;\n"
    . "                    \$category = 'Content Playlist';\n"
    . "                } else {\n"
    . "                    \$parentPlaylistTitle = '-';\n"
    . "                    \$category = 'All Content';\n"
    . "                }";

$coreCode = str_replace($oldCoreTitles, $newCoreTitles, $coreCode);

// Cells array in ClassificationOnlineRecordExport
$oldCoreCells = "                    ['I', 'str', \$courseTitle, '0'],                                             // 9. Course Title\n"
    . "                    ['J', 'str', \$subCourseTitle, '0'],                                          // 10. Sub Course Title\n"
    . "                    ['K', 'str', \$typeVal, '0'],                                                 // 11. Type (Document / Video)\n"
    . "                    ['L', 'str', \$isPlaylist ? 'Playlist' : 'Content', '0'],                     // 12. Category\n"
    . "                    ['M', 'str', '-', '0'],                                                      // 13. Level\n"
    . "                    ['N', 'str', '-', '0'],                                                      // 14. Batch\n"
    . "                    ['O', 'str', \$row->classification_name ?: 'Unclassified', '0'],             // 15. Main Classification\n"
    . "                    ['P', 'str', '-', '0'],                                                      // 16. Sub Classification\n"
    . "                    ['Q', 'str', 'Online', '0'],                                                 // 17. Delivery Method\n"
    . "                    ['R', 'str', 'E-learning', '0'],                                             // 18. Sub Delivery Method\n"
    . "                    ['S', 'str', 'Internal', '0'],                                               // 19. Provider\n"
    . "                    ['T', 'str', 'INTERNAL TRAINING TEAM', '0'],                                 // 20. Sub Provider\n"
    . "                    ['U', 'str', 'KP-OWL', '0'],                                                 // 21. Provider Name\n"
    . "                    ['V', 'str', 'Online LMS', '0'],                                             // 22. Venue\n"
    . "                    ['W', 'str', \$instructorVal, '0'],                                           // 23. Instructor (Team)\n"
    . "                    ['X', \$courseDurType, \$courseDurVal, \$courseDurStyle],                       // 24. Course Duration\n"
    . "                    ['Y', \$courseDurType, \$courseDurVal, \$courseDurStyle],                       // 25. Total Recognized Duration\n"
    . "                    ['Z', \$lhType, \$lhVal, \$lhStyle],                                            // 26. Learning Hours\n"
    . "                    ['AA', 'pct', \$progressDecimal, '2'],                                        // 27. % Progress\n"
    . "                    ['AB', 'str', \$progressStatus, '0'],                                         // 28. Progress Status\n"
    . "                    ['AC', \$startDateSerial !== null ? 'date' : 'str', \$startDateSerial ?? '-', \$startDateSerial !== null ? '3' : '0'], // 29. Start Date\n"
    . "                    ['AD', \$endDateSerial !== null ? 'date' : 'str', \$endDateSerial ?? '-', \$endDateSerial !== null ? '3' : '0'],       // 30. End Date\n"
    . "                    ['AE', \$startTimeSerial !== null ? 'time' : 'str', \$startTimeSerial ?? '-', \$startTimeSerial !== null ? '4' : '0'], // 31. Start Time\n"
    . "                    ['AF', \$endTimeSerial !== null ? 'time' : 'str', \$endTimeSerial ?? '-', \$endTimeSerial !== null ? '4' : '0'],       // 32. End Time\n"
    . "                    ['AG', 'str', \$recordMonthStr, '0'],                                         // 33. Record Month\n"
    . "                    ['AH', 'str', \$requirementStr, '0'],                                         // 34. Requirement\n"
    . "                    ['AI', 'str', \$attendanceStr, '0'],                                          // 35. Attendance\n"
    . "                    ['AJ', 'str', '-', '0'],                                                     // 36. Attendance Reason\n"
    . "                    ['AK', 'str', '-', '0'],                                                     // 37. Bond Status\n"
    . "                    ['AL', 'str', '-', '0'],                                                     // 38. Bond Duration\n"
    . "                    ['AM', 'str', '-', '0'],                                                     // 39. Bond End Date\n"
    . "                    ['AN', 'str', '-', '0'],                                                     // 40. Bond Amount\n"
    . "                    ['AO', 'str', '-', '0'],                                                     // 41. Score (L1) - Course\n"
    . "                    ['AP', \$scoreL2Type, \$scoreL2Val, \$scoreL2Style],                            // 42. Score (L2)\n"
    . "                    ['AQ', 'str', '-', '0'],                                                     // 43. L2 Status\n"
    . "                    ['AR', 'str', '-', '0'],                                                     // 44. Score (L3)\n"
    . "                    ['AS', 'str', '-', '0'],                                                     // 45. L3 Status\n"
    . "                    ['AT', 'str', \$certAvailability, '0'],                                       // 46. Certificate Availability\n"
    . "                    ['AU', 'str', \$certInstitution, '0'],                                        // 47. Certificate Institution\n"
    . "                    ['AV', \$endDateSerial !== null ? 'date' : 'str', \$endDateSerial ?? '-', \$endDateSerial !== null ? '3' : '0'],       // 48. Certificate Issued Date\n"
    . "                    ['AW', 'str', '-', '0'],                                                     // 49. Certificate Expired Date\n"
    . "                    ['AX', 'str', \$adminPic, '0'],                                               // 50. Person in Charge (PIC)\n"
    . "                    ['AY', 'dur', \$afkDurVal, '5'],                                              // 51. Total Engagement Time (LH)";

$newCoreCells = "                    ['I', 'str', \$courseTitle, '0'],                                             // 9. Course Title\n"
    . "                    ['J', 'str', \$parentPlaylistTitle, '0'],                                      // 10. Playlist (BARU!)\n"
    . "                    ['K', 'str', \$subCourseTitle, '0'],                                          // 11. Sub Course Title\n"
    . "                    ['L', 'str', \$typeVal, '0'],                                                 // 12. Type (Document / Video)\n"
    . "                    ['M', 'str', \$category, '0'],                                                // 13. Category\n"
    . "                    ['N', 'str', '-', '0'],                                                      // 14. Level\n"
    . "                    ['O', 'str', '-', '0'],                                                      // 15. Batch\n"
    . "                    ['P', 'str', \$row->classification_name ?: 'Unclassified', '0'],             // 16. Main Classification\n"
    . "                    ['Q', 'str', '-', '0'],                                                      // 17. Sub Classification\n"
    . "                    ['R', 'str', 'Online', '0'],                                                 // 18. Delivery Method\n"
    . "                    ['S', 'str', 'KP OWL', '0'],                                                 // 19. Sub Delivery Method\n"
    . "                    ['T', 'str', 'Internal', '0'],                                               // 20. Provider\n"
    . "                    ['U', 'str', '-', '0'],                                                      // 21. Sub Provider\n"
    . "                    ['V', 'str', 'KP-OWL', '0'],                                                 // 22. Provider Name\n"
    . "                    ['W', 'str', 'Online LMS', '0'],                                             // 23. Venue\n"
    . "                    ['X', 'str', \$instructorVal, '0'],                                           // 24. Instructor (Team)\n"
    . "                    ['Y', \$courseDurType, \$courseDurVal, \$courseDurStyle],                       // 25. Course Duration\n"
    . "                    ['Z', \$courseDurType, \$courseDurVal, \$courseDurStyle],                       // 26. Total Recognized Duration\n"
    . "                    ['AA', \$lhType, \$lhVal, \$lhStyle],                                            // 27. Learning Hours\n"
    . "                    ['AB', 'pct', \$progressDecimal, '2'],                                        // 28. % Progress\n"
    . "                    ['AC', 'str', \$progressStatus, '0'],                                         // 29. Progress Status\n"
    . "                    ['AD', \$startDateSerial !== null ? 'date' : 'str', \$startDateSerial ?? '-', \$startDateSerial !== null ? '3' : '0'], // 30. Start Date\n"
    . "                    ['AE', \$endDateSerial !== null ? 'date' : 'str', \$endDateSerial ?? '-', \$endDateSerial !== null ? '3' : '0'],       // 31. End Date\n"
    . "                    ['AF', \$startTimeSerial !== null ? 'time' : 'str', \$startTimeSerial ?? '-', \$startTimeSerial !== null ? '4' : '0'], // 32. Start Time\n"
    . "                    ['AG', \$endTimeSerial !== null ? 'time' : 'str', \$endTimeSerial ?? '-', \$endTimeSerial !== null ? '4' : '0'],       // 33. End Time\n"
    . "                    ['AH', 'str', \$recordMonthStr, '0'],                                         // 34. Record Month\n"
    . "                    ['AI', 'str', \$requirementStr, '0'],                                         // 35. Requirement\n"
    . "                    ['AJ', 'str', \$attendanceStr, '0'],                                          // 36. Attendance\n"
    . "                    ['AK', 'str', '-', '0'],                                                     // 37. Attendance Reason\n"
    . "                    ['AL', 'str', '-', '0'],                                                     // 38. Bond Status\n"
    . "                    ['AM', 'str', '-', '0'],                                                     // 39. Bond Duration\n"
    . "                    ['AN', 'str', '-', '0'],                                                     // 40. Bond End Date\n"
    . "                    ['AO', 'str', '-', '0'],                                                     // 41. Bond Amount\n"
    . "                    ['AP', 'str', '-', '0'],                                                     // 42. Score (L1) - Course\n"
    . "                    ['AQ', \$scoreL2Type, \$scoreL2Val, \$scoreL2Style],                            // 43. Score (L2)\n"
    . "                    ['AR', 'str', '-', '0'],                                                     // 44. L2 Status\n"
    . "                    ['AS', 'str', '-', '0'],                                                     // 45. Score (L3)\n"
    . "                    ['AT', 'str', '-', '0'],                                                     // 46. L3 Status\n"
    . "                    ['AU', 'str', \$certAvailability, '0'],                                       // 47. Certificate Availability\n"
    . "                    ['AV', 'str', \$certInstitution, '0'],                                        // 48. Certificate Institution\n"
    . "                    ['AW', \$endDateSerial !== null ? 'date' : 'str', \$endDateSerial ?? '-', \$endDateSerial !== null ? '3' : '0'],       // 49. Certificate Issued Date\n"
    . "                    ['AX', 'str', '-', '0'],                                                     // 50. Certificate Expired Date\n"
    . "                    ['AY', 'str', \$adminPic, '0'],                                               // 51. Person in Charge (PIC)\n"
    . "                    ['AZ', 'dur', \$afkDurVal, '5'],                                              // 52. Total Engagement Time (LH)";

$coreCode = str_replace($oldCoreCells, $newCoreCells, $coreCode);

file_put_contents($corePath, $coreCode);
echo "3. ClassificationOnlineRecordExport updated successfully.\n";

// -----------------------------------------------------------------
// 4. Update ClassificationRecordExport.php & AllRecordExport.php (All Record, 53 columns)
// -----------------------------------------------------------------
foreach ([
    'c:/laragon/www/owl/app/Exports/Manage/ClassificationRecordExport.php',
    'c:/laragon/www/owl/app/Exports/Manage/AllRecordExport.php'
] as $allFile) {
    $code = file_get_contents($allFile);

    // Headings
    $oldHead = "            'Course Title',\n            'Sub Course Title',";
    $newHead = "            'Course Title',\n            'Playlist',\n            'Sub Course Title',";
    $code = str_replace($oldHead, $newHead, $code);

    // Column widths: add K => 35 (Playlist)
    $oldW = "            'J'  => 35,  // Course Title\n            'K'  => 25,  // Sub Course Title\n            'L'  => 15,  // Type\n            'M'  => 18,  // Category\n            'N'  => 12,  // Level\n            'O'  => 12,  // Batch\n            'P'  => 25,  // Main Classification\n            'Q'  => 25,  // Sub Classification\n            'R'  => 18,  // Delivery Method\n            'S'  => 20,  // Sub Delivery Method\n            'T'  => 16,  // Provider\n            'U'  => 18,  // Sub Provider\n            'V'  => 25,  // Provider Name\n            'W'  => 20,  // Venue\n            'X'  => 22,  // Instructor\n            'Y'  => 16,  // Course Duration\n            'Z'  => 22,  // Total Recognized Duration\n            'AA' => 16,  // Learning Hours\n            'AB' => 14,  // % Progress\n            'AC' => 16,  // Progress Status\n            'AD' => 14,  // Start Date\n            'AE' => 14,  // End Date\n            'AF' => 14,  // Start Time\n            'AG' => 14,  // End Time\n            'AH' => 16,  // Record Month\n            'AI' => 15,  // Requirement\n            'AJ' => 14,  // Attendance\n            'AK' => 25,  // Attendance Reason\n            'AL' => 14,  // Bond Status\n            'AM' => 16,  // Bond Duration\n            'AN' => 16,  // Bond End Date\n            'AO' => 18,  // Bond Amount\n            'AP' => 20,  // Score (L1) - Course\n            'AQ' => 15,  // Score (L2)\n            'AR' => 15,  // L2 Status\n            'AS' => 15,  // Score (L3)\n            'AT' => 15,  // L3 Status\n            'AU' => 22,  // Certificate Availability\n            'AV' => 25,  // Certificate Institution\n            'AW' => 20,  // Certificate Issued Date\n            'AX' => 20,  // Certificate Expired Date\n            'AY' => 25,  // Person in Charge (PIC)\n            'AZ' => 25,  // Total Engagement Time (LH)";

    $newW = "            'J'  => 35,  // Course Title\n            'K'  => 35,  // Playlist\n            'L'  => 25,  // Sub Course Title\n            'M'  => 15,  // Type\n            'N'  => 18,  // Category\n            'O'  => 12,  // Level\n            'P'  => 12,  // Batch\n            'Q'  => 25,  // Main Classification\n            'R'  => 25,  // Sub Classification\n            'S'  => 18,  // Delivery Method\n            'T'  => 20,  // Sub Delivery Method\n            'U'  => 16,  // Provider\n            'V'  => 18,  // Sub Provider\n            'W'  => 25,  // Provider Name\n            'X'  => 20,  // Venue\n            'Y'  => 22,  // Instructor\n            'Z'  => 16,  // Course Duration\n            'AA' => 22,  // Total Recognized Duration\n            'AB' => 16,  // Learning Hours\n            'AC' => 14,  // % Progress\n            'AD' => 16,  // Progress Status\n            'AE' => 14,  // Start Date\n            'AF' => 14,  // End Date\n            'AG' => 14,  // Start Time\n            'AH' => 14,  // End Time\n            'AI' => 16,  // Record Month\n            'AJ' => 15,  // Requirement\n            'AK' => 14,  // Attendance\n            'AL' => 25,  // Attendance Reason\n            'AM' => 14,  // Bond Status\n            'AN' => 16,  // Bond Duration\n            'AO' => 16,  // Bond End Date\n            'AP' => 18,  // Bond Amount\n            'AQ' => 20,  // Score (L1) - Course\n            'AR' => 15,  // Score (L2)\n            'AS' => 15,  // L2 Status\n            'AT' => 15,  // Score (L3)\n            'AU' => 15,  // L3 Status\n            'AV' => 22,  // Certificate Availability\n            'AW' => 25,  // Certificate Institution\n            'AX' => 20,  // Certificate Issued Date\n            'AY' => 20,  // Certificate Expired Date\n            'AZ' => 25,  // Person in Charge (PIC)\n            'BA' => 25,  // Total Engagement Time (LH)";

    $code = str_replace($oldW, $newW, $code);

    // Preload tpdCache before cursor
    $oldCursorPrep = '$rowCount = 0;';
    $newCursorPrep = '// Preload content to playlist map' . "\n"
        . '        $tpdCache = [];' . "\n"
        . '        $tpdRows = DB::table(\'trainingplandetail as tpd\')' . "\n"
        . '            ->join(\'trainingplan as tp\', \'tp.id\', \'=\', \'tpd.trainingplan_id\')' . "\n"
        . '            ->whereNull(\'tp.deleted_at\')' . "\n"
        . '            ->orderBy(\'tpd.id\', \'asc\')' . "\n"
        . '            ->select(\'tpd.content_id\', \'tp.title\')' . "\n"
        . '            ->get();' . "\n"
        . '        foreach ($tpdRows as $r) {' . "\n"
        . '            if (!isset($tpdCache[$r->content_id])) {' . "\n"
        . '                $tpdCache[$r->content_id] = trim($r->title);' . "\n"
        . '            }' . "\n"
        . '        }' . "\n\n"
        . '        $rowCount = 0;';

    if (strpos($code, 'Preload content to playlist map') === false) {
        $code = str_replace($oldCursorPrep, $newCursorPrep, $code);
    }

    // In cursor loop: resolve parent playlist and category
    $oldAllTitleLogic = "                \$isPlaylistRow = (\$row->type === 'Playlist' || (\$row->source_type ?? '') === 'playlist');";
    $newAllTitleLogic = "                \$isPlaylistRow = (\$row->type === 'Playlist' || (\$row->source_type ?? '') === 'playlist');\n"
        . "                \$parentPlaylistTitle = '-';\n"
        . "                if (\$row->source !== 'offline') {\n"
        . "                    \$pTitle = !empty(\$row->playlist_title) ? trim(\$row->playlist_title) : (\$tpdCache[\$row->content_id ?? 0] ?? '-');\n"
        . "                    if (\$pTitle !== '' && \$pTitle !== '-') {\n"
        . "                        \$parentPlaylistTitle = \$pTitle;\n"
        . "                        \$categoryVal = 'Content Playlist';\n"
        . "                    } else {\n"
        . "                        \$categoryVal = 'All Content';\n"
        . "                    }\n"
        . "                } else {\n"
        . "                    \$categoryVal = 'Non KP-OWL';\n"
        . "                }";

    if (strpos($code, '$parentPlaylistTitle = \'-\';') === false) {
        $code = str_replace($oldAllTitleLogic, $newAllTitleLogic, $code);
    }

    // Replace 52 columns with 53 columns
    $oldCells = "                    ['J',  'str',   \$row->learning_title ?: '-', \$textStyle],                                                                           // 10. Course Title\n"
        . "                    ['K',  'str',   \$row->sub_course_name ?: '-', \$textStyle],                                                                          // 11. Sub Course Title\n"
        . "                    ['L',  'str',   (\$row->source === 'offline' || !\$isKpOwl) ? '-' : (\$row->type ?: '-'), \$textStyle],                                 // 12. Type\n"
        . "                    ['M',  'str',   \$row->category ?: '-', \$textStyle],                                                                                 // 13. Category\n"
        . "                    ['N',  'str',   \$row->level ?: '-', \$textStyle],                                                                                    // 14. Level\n"
        . "                    ['O',  'str',   \$row->batch ?: '-', \$textStyle],                                                                                    // 15. Batch\n"
        . "                    ['P',  'str',   \$row->classification ?: 'Unclassified', \$textStyle],                                                                // 16. Main Classification\n"
        . "                    ['Q',  'str',   \$row->sub_classification ?: '-', \$textStyle],                                                                       // 17. Sub Classification\n"
        . "                    ['R',  'str',   \$delivMethod, \$textStyle],                                                                                          // 18. Delivery Method\n"
        . "                    ['S',  'str',   \$subDelivMethod, \$textStyle],                                                                                       // 19. Sub Delivery Method\n"
        . "                    ['T',  'str',   \$row->source === 'online' ? 'Internal' : (\$row->provider ?: '-'), \$textStyle],                                     // 20. Provider\n"
        . "                    ['U',  'str',   \$row->source === 'online' ? '-' : (\$row->sub_provider ?: '-'), \$textStyle],                                        // 21. Sub Provider\n"
        . "                    ['V',  'str',   \$row->provider_name ?: (\$row->source === 'offline' ? '-' : 'KP-OWL'), \$textStyle],                                 // 22. Provider Name\n"
        . "                    ['W',  'str',   \$row->venue ?: (\$row->source === 'offline' ? '-' : 'Online LMS'), \$textStyle],                                     // 23. Venue\n"
        . "                    ['X',  'str',   \$row->instructor ?: '-', \$textStyle],                                                                               // 24. Instructor\n"
        . "                    ['Y',  is_numeric(\$courseDurVal) ? 'dur' : 'str', \$courseDurVal, is_numeric(\$courseDurVal) ? \$timeStyle : \$textStyle],            // 25. Course Duration\n"
        . "                    ['Z',  is_numeric(\$recognizedDurVal) ? 'dur' : 'str', \$recognizedDurVal, is_numeric(\$recognizedDurVal) ? \$timeStyle : \$textStyle],  // 26. Total Recognized Duration\n"
        . "                    ['AA', \$lhNumeric !== null ? 'lh' : 'str', \$lhNumeric !== null ? \$lhNumeric : '-', \$lhNumeric !== null ? \$lhStyle : \$textStyle], // 27. Learning Hours\n"
        . "                    ['AB', 'pct',   \$progressDecimal, \$pctStyle],                                                                                       // 28. % Progress\n"
        . "                    ['AC', 'str',   \$progressStatus, \$textStyle],                                                                                       // 29. Progress Status\n"
        . "                    ['AD', \$startDateStr !== '-' ? 'date' : 'str', \$startDateStr, \$startDateStr !== '-' ? '7' : \$textStyle],                          // 30. Start Date\n"
        . "                    ['AE', \$endDateStr !== '-' ? 'date' : 'str', \$endDateStr, \$endDateStr !== '-' ? '7' : \$textStyle],                              // 31. End Date\n"
        . "                    ['AF', \$startTimeStr !== '-' ? 'time' : 'str', \$startTimeStr, \$startTimeStr !== '-' ? \$timeStyle : \$textStyle],                  // 32. Start Time\n"
        . "                    ['AG', \$endTimeStr !== '-' ? 'time' : 'str', \$endTimeStr, \$endTimeStr !== '-' ? \$timeStyle : \$textStyle],                      // 33. End Time\n"
        . "                    ['AH', 'str',   \$finalRecordMonth, \$textStyle],                                                                                     // 34. Record Month\n"
        . "                    ['AI', 'str',   \$row->requirement ?: 'Mandatory', \$textStyle],                                                                      // 35. Requirement\n"
        . "                    ['AJ', 'str',   \$attendanceStr, \$textStyle],                                                                                        // 36. Attendance\n"
        . "                    ['AK', 'str',   \$row->attendance_reason ?: '-', \$textStyle],                                                                        // 37. Attendance Reason\n"
        . "                    ['AL', 'str',   \$row->bond_status ?: 'No', \$textStyle],                                                                             // 38. Bond Status\n"
        . "                    ['AM', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? 'month' : 'str', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? (int)\$row->bond_duration : '-', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? \$bondDurStyle : \$textStyle],\n"
        . "                    ['AN', !empty(\$row->bond_end_date) ? 'date' : 'str', !empty(\$row->bond_end_date) ? date('Y-m-d', strtotime(\$row->bond_end_date)) : '-', !empty(\$row->bond_end_date) ? '7' : \$textStyle], // 40. Bond End Date\n"
        . "                    ['AO', \$hasBond ? 'curr' : 'str', \$hasBond ? (float) \$idr : '-', \$hasBond ? '9' : \$textStyle],                                     // 41. Bond Amount\n"
        . "                    ['AP', \$scoreL1 !== null ? 'float' : 'str', \$scoreL1 !== null ? \$scoreL1 : '-', \$scoreL1 !== null ? \$floatStyle : \$textStyle],             // 42. Score (L1) - Course\n"
        . "                    ['AQ', \$scoreL2 !== null ? 'int' : 'str', \$scoreL2 !== null ? \$scoreL2 : '-', \$scoreL2 !== null ? \$intStyle : \$textStyle],        // 43. Score (L2)\n"
        . "                    ['AR', 'str',   \$row->l2_verbal ?: (\$scoreL2 !== null ? (\$scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), \$textStyle],                 // 44. L2 Status\n"
        . "                    ['AS', \$scoreL3 !== null ? 'float' : 'str', \$scoreL3 !== null ? \$scoreL3 : '-', \$scoreL3 !== null ? \$floatStyle : \$textStyle],             // 45. Score (L3)\n"
        . "                    ['AT', 'str',   \$row->l3_verbal ?: '-', \$textStyle],                                                                                // 46. L3 Status\n"
        . "                    ['AU', 'str',   \$certAvailability, \$textStyle],                                                                                     // 47. Certificate Availability\n"
        . "                    ['AV', 'str',   \$certInstitution, \$textStyle],                                                                                      // 48. Certificate Institution\n"
        . "                    ['AW', \$certIssuedDate !== '-' ? 'date' : 'str', \$certIssuedDate, \$certIssuedDate !== '-' ? '7' : \$textStyle],                    // 49. Certificate Issued Date\n"
        . "                    ['AX', \$certExpiredDate !== '-' ? 'date' : 'str', \$certExpiredDate, \$certExpiredDate !== '-' ? '7' : \$textStyle],                  // 50. Certificate Expired Date\n"
        . "                    ['AY', 'str',   \$row->pic ?: '-', \$textStyle],                                                                                      // 51. Person in Charge (PIC)\n"
        . "                    ['AZ', 'dur',   \$totalEngagementVal, \$timeStyle],                                                                              // 52. Total Engagement Time (LH)";

    $newCells = "                    ['J',  'str',   \$row->learning_title ?: '-', \$textStyle],                                                                           // 10. Course Title\n"
        . "                    ['K',  'str',   \$parentPlaylistTitle, \$textStyle],                                                                                  // 11. Playlist (BARU!)\n"
        . "                    ['L',  'str',   \$row->sub_course_name ?: '-', \$textStyle],                                                                          // 12. Sub Course Title\n"
        . "                    ['M',  'str',   (\$row->source === 'offline' || !\$isKpOwl) ? '-' : (\$row->type ?: '-'), \$textStyle],                                 // 13. Type\n"
        . "                    ['N',  'str',   \$categoryVal, \$textStyle],                                                                                          // 14. Category\n"
        . "                    ['O',  'str',   \$row->level ?: '-', \$textStyle],                                                                                    // 15. Level\n"
        . "                    ['P',  'str',   \$row->batch ?: '-', \$textStyle],                                                                                    // 16. Batch\n"
        . "                    ['Q',  'str',   \$row->classification ?: 'Unclassified', \$textStyle],                                                                // 17. Main Classification\n"
        . "                    ['R',  'str',   \$row->sub_classification ?: '-', \$textStyle],                                                                       // 18. Sub Classification\n"
        . "                    ['S',  'str',   \$delivMethod, \$textStyle],                                                                                          // 19. Delivery Method\n"
        . "                    ['T',  'str',   \$subDelivMethod, \$textStyle],                                                                                       // 20. Sub Delivery Method\n"
        . "                    ['U',  'str',   \$row->source === 'online' ? 'Internal' : (\$row->provider ?: '-'), \$textStyle],                                     // 21. Provider\n"
        . "                    ['V',  'str',   \$row->source === 'online' ? '-' : (\$row->sub_provider ?: '-'), \$textStyle],                                        // 22. Sub Provider\n"
        . "                    ['W',  'str',   \$row->provider_name ?: (\$row->source === 'offline' ? '-' : 'KP-OWL'), \$textStyle],                                 // 23. Provider Name\n"
        . "                    ['X',  'str',   \$row->venue ?: (\$row->source === 'offline' ? '-' : 'Online LMS'), \$textStyle],                                     // 24. Venue\n"
        . "                    ['Y',  'str',   \$row->instructor ?: '-', \$textStyle],                                                                               // 25. Instructor\n"
        . "                    ['Z',  is_numeric(\$courseDurVal) ? 'dur' : 'str', \$courseDurVal, is_numeric(\$courseDurVal) ? \$timeStyle : \$textStyle],            // 26. Course Duration\n"
        . "                    ['AA', is_numeric(\$recognizedDurVal) ? 'dur' : 'str', \$recognizedDurVal, is_numeric(\$recognizedDurVal) ? \$timeStyle : \$textStyle],  // 27. Total Recognized Duration\n"
        . "                    ['AB', \$lhNumeric !== null ? 'lh' : 'str', \$lhNumeric !== null ? \$lhNumeric : '-', \$lhNumeric !== null ? \$lhStyle : \$textStyle], // 28. Learning Hours\n"
        . "                    ['AC', 'pct',   \$progressDecimal, \$pctStyle],                                                                                       // 29. % Progress\n"
        . "                    ['AD', 'str',   \$progressStatus, \$textStyle],                                                                                       // 30. Progress Status\n"
        . "                    ['AE', \$startDateStr !== '-' ? 'date' : 'str', \$startDateStr, \$startDateStr !== '-' ? '7' : \$textStyle],                          // 31. Start Date\n"
        . "                    ['AF', \$endDateStr !== '-' ? 'date' : 'str', \$endDateStr, \$endDateStr !== '-' ? '7' : \$textStyle],                              // 32. End Date\n"
        . "                    ['AG', \$startTimeStr !== '-' ? 'time' : 'str', \$startTimeStr, \$startTimeStr !== '-' ? \$timeStyle : \$textStyle],                  // 33. Start Time\n"
        . "                    ['AH', \$endTimeStr !== '-' ? 'time' : 'str', \$endTimeStr, \$endTimeStr !== '-' ? \$timeStyle : \$textStyle],                      // 34. End Time\n"
        . "                    ['AI', 'str',   \$finalRecordMonth, \$textStyle],                                                                                     // 35. Record Month\n"
        . "                    ['AJ', 'str',   \$row->requirement ?: 'Mandatory', \$textStyle],                                                                      // 36. Requirement\n"
        . "                    ['AK', 'str',   \$attendanceStr, \$textStyle],                                                                                        // 37. Attendance\n"
        . "                    ['AL', 'str',   \$row->attendance_reason ?: '-', \$textStyle],                                                                        // 38. Attendance Reason\n"
        . "                    ['AM', 'str',   \$row->bond_status ?: 'No', \$textStyle],                                                                             // 39. Bond Status\n"
        . "                    ['AN', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? 'month' : 'str', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? (int)\$row->bond_duration : '-', (!empty(\$row->bond_duration) && is_numeric(\$row->bond_duration) && (int)\$row->bond_duration > 0) ? \$bondDurStyle : \$textStyle],\n"
        . "                    ['AO', !empty(\$row->bond_end_date) ? 'date' : 'str', !empty(\$row->bond_end_date) ? date('Y-m-d', strtotime(\$row->bond_end_date)) : '-', !empty(\$row->bond_end_date) ? '7' : \$textStyle], // 41. Bond End Date\n"
        . "                    ['AP', \$hasBond ? 'curr' : 'str', \$hasBond ? (float) \$idr : '-', \$hasBond ? '9' : \$textStyle],                                     // 42. Bond Amount\n"
        . "                    ['AQ', \$scoreL1 !== null ? 'float' : 'str', \$scoreL1 !== null ? \$scoreL1 : '-', \$scoreL1 !== null ? \$floatStyle : \$textStyle],             // 43. Score (L1) - Course\n"
        . "                    ['AR', \$scoreL2 !== null ? 'int' : 'str', \$scoreL2 !== null ? \$scoreL2 : '-', \$scoreL2 !== null ? \$intStyle : \$textStyle],        // 44. Score (L2)\n"
        . "                    ['AS', 'str',   \$row->l2_verbal ?: (\$scoreL2 !== null ? (\$scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), \$textStyle],                 // 45. L2 Status\n"
        . "                    ['AT', \$scoreL3 !== null ? 'float' : 'str', \$scoreL3 !== null ? \$scoreL3 : '-', \$scoreL3 !== null ? \$floatStyle : \$textStyle],             // 46. Score (L3)\n"
        . "                    ['AU', 'str',   \$row->l3_verbal ?: '-', \$textStyle],                                                                                // 47. L3 Status\n"
        . "                    ['AV', 'str',   \$certAvailability, \$textStyle],                                                                                     // 48. Certificate Availability\n"
        . "                    ['AW', 'str',   \$certInstitution, \$textStyle],                                                                                      // 49. Certificate Institution\n"
        . "                    ['AX', \$certIssuedDate !== '-' ? 'date' : 'str', \$certIssuedDate, \$certIssuedDate !== '-' ? '7' : \$textStyle],                    // 50. Certificate Issued Date\n"
        . "                    ['AY', \$certExpiredDate !== '-' ? 'date' : 'str', \$certExpiredDate, \$certExpiredDate !== '-' ? '7' : \$textStyle],                  // 51. Certificate Expired Date\n"
        . "                    ['AZ', 'str',   \$row->pic ?: '-', \$textStyle],                                                                                      // 52. Person in Charge (PIC)\n"
        . "                    ['BA', 'dur',   \$totalEngagementVal, \$timeStyle],                                                                              // 53. Total Engagement Time (LH)";

    $code = str_replace($oldCells, $newCells, $code);

    file_put_contents($allFile, $code);
    echo "4. Updated {$allFile}\n";
}

// -----------------------------------------------------------------
// 5. Update Web UI Blade Views
// -----------------------------------------------------------------
// A. Online Classification View
$onlineViewPath = 'c:/laragon/www/owl/resources/views/main/record/online/classification.blade.php';
$ovCode = file_get_contents($onlineViewPath);

$oldThOnline = '<th style="min-width: 220px;">COURSE TITLE</th>';
$newThOnline = '<th style="min-width: 220px;">COURSE TITLE</th>' . "\n"
    . '                                <th style="min-width: 200px;">PLAYLIST</th>';

if (strpos($ovCode, '<th style="min-width: 200px;">PLAYLIST</th>') === false) {
    $ovCode = str_replace($oldThOnline, $newThOnline, $ovCode);
}

$oldColOnline = "{ data: 'course_title', name: 'c.title' },";
$newColOnline = "{ data: 'course_title', name: 'c.title' },\n"
    . "                { data: 'playlist', name: 'tp.title' },";

if (strpos($ovCode, "{ data: 'playlist', name: 'tp.title' },") === false) {
    $ovCode = str_replace($oldColOnline, $newColOnline, $ovCode);
}

file_put_contents($onlineViewPath, $ovCode);
echo "5a. Updated resources/views/main/record/online/classification.blade.php\n";

// B. All Classification View
$allViewPath = 'c:/laragon/www/owl/resources/views/main/record/all/classification.blade.php';
$avCode = file_get_contents($allViewPath);

$oldThAll = '<th class="text-center" style="min-width: 220px; text-align: center;">COURSE TITLE</th>';
$newThAll = '<th class="text-center" style="min-width: 220px; text-align: center;">COURSE TITLE</th>' . "\n"
    . '                                    <th class="text-center" style="min-width: 200px; text-align: center;">PLAYLIST</th>';

if (strpos($avCode, '<th class="text-center" style="min-width: 200px; text-align: center;">PLAYLIST</th>') === false) {
    $avCode = str_replace($oldThAll, $newThAll, $avCode);
}

// Check datatable columns in all classification view
$oldColAll = "{ data: 'learning_title', name: 'learning_title' },";
$newColAll = "{ data: 'learning_title', name: 'learning_title' },\n"
    . "                    { data: 'playlist', name: 'tp.title', defaultContent: '-' },";

if (strpos($avCode, "{ data: 'playlist', name: 'tp.title'") === false && strpos($avCode, $oldColAll) !== false) {
    $avCode = str_replace($oldColAll, $newColAll, $avCode);
}

file_put_contents($allViewPath, $avCode);
echo "5b. Updated resources/views/main/record/all/classification.blade.php\n";

// -----------------------------------------------------------------
// 6. Update RecordAllCombinedService.php for playlist column in datatable
// -----------------------------------------------------------------
$racsPath = 'c:/laragon/www/owl/app/Services/Record/RecordAllCombinedService.php';
$racsCode = file_get_contents($racsPath);

$oldRacsCol = "->addColumn('learning_title', function (\$row) {";
$newRacsCol = "->addColumn('playlist', function (\$row) {\n"
    . "                if (!empty(\$row->playlist_title)) {\n"
    . "                    return e(\$row->playlist_title);\n"
    . "                }\n"
    . "                return '<span class=\"text-muted small\">-</span>';\n"
    . "            })\n"
    . "            ->addColumn('learning_title', function (\$row) {";

if (strpos($racsCode, "->addColumn('playlist'") === false) {
    $racsCode = str_replace($oldRacsCol, $newRacsCol, $racsCode);
}

file_put_contents($racsPath, $racsCode);
echo "6. Updated RecordAllCombinedService.php\n";

echo "ALL UPDATES APPLIED SUCCESSFULLY!\n";

