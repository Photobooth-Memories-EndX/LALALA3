<?php

namespace Tests\Unit;

use App\Exports\AssessmentExport;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\TrainingPlan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AssessmentExportTest extends TestCase
{
    public function test_my_learning_assessment_workbook_has_expected_data_and_format(): void
    {
        $result = (new AssesmentResult())->forceFill([
            'user_id' => 7,
            'final' => 1,
            'pass' => 1,
            'score' => 85,
            'batch' => 2,
        ]);
        $playlist = (new TrainingPlan())->forceFill(['title' => 'Leadership Playlist']);
        $assessment = (new Assesment())->forceFill([
            'title' => 'Leadership Final',
            'type' => 'trainingplan',
            'timer' => 'y',
            'fromdate' => '2026-07-13 12:00:00',
            'todate' => '2026-07-15 12:00:00',
            'passing_grade' => 80,
        ]);
        $assessment->setRelation('scores', new Collection([$result]));
        $assessment->setRelation('trainingplan', $playlist);

        $binary = Excel::raw(
            AssessmentExport::forMyLearning(collect([$assessment]), 7),
            ExcelWriter::XLSX
        );
        $path = tempnam(sys_get_temp_dir(), 'assessment-export-');
        file_put_contents($path, $binary);
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        $this->assertSame(
            ['No', 'Assessment', 'Type', 'Category', 'Date & Time', 'Score', 'Passing Grade', 'Status', 'Action / Result'],
            $sheet->rangeToArray('A1:I1')[0]
        );
        $this->assertSame("Leadership Final\nRelated to: Leadership Playlist", $sheet->getCell('B2')->getValue());
        $this->assertSame('Playlist Assessment', $sheet->getCell('C2')->getValue());
        $this->assertSame('—', $sheet->getCell('D2')->getValue());
        $this->assertSame("From : 13/07/2026 12:00\nTo : 15/07/2026 12:00", $sheet->getCell('E2')->getValue());
        $this->assertSame(85, $sheet->getCell('F2')->getValue());
        $this->assertSame(80, $sheet->getCell('G2')->getValue());
        $this->assertSame('Passed', $sheet->getCell('H2')->getValue());
        $this->assertSame('Passed', $sheet->getCell('I2')->getValue());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertSame('A1:I1', $sheet->getAutoFilter()->getRange());
        $this->assertTrue($sheet->getStyle('B2')->getAlignment()->getWrapText());
        $this->assertSame('C6EFCE', $sheet->getStyle('H2')->getFill()->getStartColor()->getRGB());
    }

    public function test_my_learning_rows_preserve_query_ordering(): void
    {
        $makeAssessment = function (string $title, string $type): Assesment {
            $assessment = (new Assesment())->forceFill([
                'title' => $title,
                'type' => $type,
                'timer' => 'n',
            ]);
            $assessment->setRelation('scores', new Collection());
            $assessment->setRelation('trainingplan', null);

            return $assessment;
        };

        $rows = AssessmentExport::forMyLearning(collect([
            $makeAssessment('Zulu Global', 'global'),
            $makeAssessment('Zulu Content', 'content'),
            $makeAssessment('Bravo Playlist', 'trainingplan'),
            $makeAssessment('Alpha Content', 'content'),
        ]), 7)->array();

        $this->assertSame(
            ['Zulu Global', 'Zulu Content', 'Bravo Playlist', 'Alpha Content'],
            collect(array_slice($rows, 1))->pluck(1)->map(fn ($value) => strtok($value, "\n"))->all()
        );
    }
}
