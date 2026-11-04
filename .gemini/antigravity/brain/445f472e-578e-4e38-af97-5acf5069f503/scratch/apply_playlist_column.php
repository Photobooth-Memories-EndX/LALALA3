<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting update for Playlist column & Content Playlist category...\n";

// ---------------------------------------------------------
// 1. Update LearningDatatableService.php
// ---------------------------------------------------------
$ldPath = 'c:/laragon/www/owl/app/Services/Learning/LearningDatatableService.php';
$ldCode = file_get_contents($ldPath);
$eol = (strpos($ldCode, "\r\n") !== false) ? "\r\n" : "\n";

// A. Subquery for trainingplan_id in cuSelect
$oldCuTp = "DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),";
$newCuTp = "DB::raw('(SELECT tpd.trainingplan_id FROM trainingplandetail tpd JOIN trainingplan tp ON tp.id = tpd.trainingplan_id WHERE tpd.content_id = cu.content_id AND tp.deleted_at IS NULL ORDER BY tpd.id ASC LIMIT 1) as trainingplan_id'),";
if (strpos($ldCode, $oldCuTp) !== false) {
    $ldCode = str_replace($oldCuTp, $newCuTp, $ldCode);
    echo "LearningDatatableService: Updated cuSelect trainingplan_id subquery.\n";
}

// B. Category source in cuSelect
$oldCuCat = "DB::raw(\"'All Content' as category_source\"),";
$newCuCat = "DB::raw(\"CASE WHEN (SELECT 1 FROM trainingplandetail tpd JOIN trainingplan tp ON tp.id = tpd.trainingplan_id WHERE tpd.content_id = cu.content_id AND tp.deleted_at IS NULL LIMIT 1) = 1 THEN 'Content Playlist' ELSE 'All Content' END as category_source\"),";
if (strpos($ldCode, $oldCuCat) !== false) {
    $ldCode = str_replace($oldCuCat, $newCuCat, $ldCode);
    echo "LearningDatatableService: Updated cuSelect category_source.\n";
}

// C. Category source in pcuSelect
$oldPcuCat = "DB::raw(\"'Playlist' as category_source\"),";
$newPcuCat = "DB::raw(\"'Content Playlist' as category_source\"),";
if (strpos($ldCode, $oldPcuCat) !== false) {
    $ldCode = str_replace($oldPcuCat, $newPcuCat, $ldCode);
    echo "LearningDatatableService: Updated pcuSelect category_source.\n";
}

// D. Add column 'playlist' in make()
$oldCourseCol = "->addColumn('course_title', fn (\$row) => \$this->learningTitleColumn(\$row))";
$newCourseCol = "->addColumn('course_title', fn (\$row) => \$this->learningTitleColumn(\$row))\n"
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

if (strpos($ldCode, $oldCourseCol) !== false && strpos($ldCode, "->addColumn('playlist'") === false) {
    $ldCode = str_replace($oldCourseCol, $newCourseCol, $ldCode);
    echo "LearningDatatableService: Added playlist column to DataTable.\n";
}

// E. Update categoryBadge method
$oldCatBadge = "        if (\$row->category_source === 'Playlist') {";
$newCatBadge = "        if (\$row->category_source === 'Content Playlist' || \$row->category_source === 'Playlist' || !empty(\$row->playlist_title)) {\n"
    . "            return '<span class=\"badge bg-purple-lt text-purple\">Content Playlist</span>';\n"
    . "        }\n"
    . "        if (false) {";
if (strpos($ldCode, $oldCatBadge) !== false) {
    $ldCode = str_replace($oldCatBadge, $newCatBadge, $ldCode);
    echo "LearningDatatableService: Updated categoryBadge.\n";
}

file_put_contents($ldPath, $ldCode);

echo "LearningDatatableService updated successfully.\n";

