<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentResultDuplicationService
{
    /**
     * Default chunk size for batch processing historical assessment results.
     */
    public const DEFAULT_CHUNK_SIZE = 100;

    /**
     * Copy assessment results from a source assessment to a new duplicated assessment.
     * Uses multiset (count-based) idempotency to ensure 100% of historical rows
     * (including twin/identical submissions) are copied accurately without omission.
     *
     * @param int $sourceAssessmentId
     * @param int $newAssessmentId
     * @param array $options
     * @return int Total number of results copied
     */
    public function copyResults(int $sourceAssessmentId, int $newAssessmentId, array $options = []): int
    {
        if ($sourceAssessmentId <= 0 || $newAssessmentId <= 0 || $sourceAssessmentId === $newAssessmentId) {
            return 0;
        }

        $sourceAssessment = Assesment::find($sourceAssessmentId);
        $newAssessment    = Assesment::find($newAssessmentId);

        if (!$sourceAssessment || !$newAssessment) {
            return 0;
        }

        $questionMapping = $this->buildQuestionMapping($sourceAssessmentId, $newAssessmentId);
        $chunkSize = (int) ($options['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE);
        if ($chunkSize <= 0) {
            $chunkSize = self::DEFAULT_CHUNK_SIZE;
        }

        $totalCopied = 0;

        DB::transaction(function () use ($sourceAssessmentId, $newAssessmentId, $questionMapping, $chunkSize, &$totalCopied) {
            // 1. Multiset Tracking: Count each result fingerprint currently present in Target
            $existingTargetResults = AssesmentResult::where('assesment_id', $newAssessmentId)->get();
            $targetCounts = [];
            foreach ($existingTargetResults as $tr) {
                $fp = $this->computeFingerprint($tr);
                $targetCounts[$fp] = ($targetCounts[$fp] ?? 0) + 1;
            }

            // 2. Multiset Tracking: Count occurrences seen in Source across chunk iterations
            $sourceSeenCounts = [];

            AssesmentResult::where('assesment_id', $sourceAssessmentId)
                ->orderBy('id')
                ->chunkById($chunkSize, function ($sourceResults) use ($newAssessmentId, $questionMapping, &$targetCounts, &$sourceSeenCounts, &$totalCopied) {
                    $batchToInsert = [];

                    foreach ($sourceResults as $sourceResult) {
                        $fp = $this->computeFingerprint($sourceResult);
                        $sourceSeenCounts[$fp] = ($sourceSeenCounts[$fp] ?? 0) + 1;
                        $seenIndex = $sourceSeenCounts[$fp];
                        $alreadyInTarget = $targetCounts[$fp] ?? 0;

                        // Multiset idempotency condition:
                        // If Target already contains this occurrence count for this fingerprint, skip it.
                        // If Target has fewer occurrences than seen so far in Source, copy it!
                        if ($seenIndex <= $alreadyInTarget) {
                            continue;
                        }

                        // Remap user_answer keys to new duplicated question IDs
                        $rawUserAnswer = $sourceResult->user_answer;
                        if (is_string($rawUserAnswer)) {
                            $decoded = json_decode($rawUserAnswer, true);
                            $rawUserAnswer = is_array($decoded) ? $decoded : [];
                        }
                        $newUserAnswer = $this->remapUserAnswer($rawUserAnswer, $questionMapping);

                        // Remap question_snapshot question_ids to new duplicated question IDs
                        $rawSnapshot = $sourceResult->question_snapshot;
                        if (is_string($rawSnapshot)) {
                            $decoded = json_decode($rawSnapshot, true);
                            $rawSnapshot = is_array($decoded) ? $decoded : null;
                        }
                        $newSnapshot = $this->remapQuestionSnapshot($rawSnapshot, $questionMapping);

                        $createdAt = $sourceResult->created_at
                            ? $sourceResult->created_at->format('Y-m-d H:i:s')
                            : Carbon::now()->format('Y-m-d H:i:s');
                        $updatedAt = $sourceResult->updated_at
                            ? $sourceResult->updated_at->format('Y-m-d H:i:s')
                            : Carbon::now()->format('Y-m-d H:i:s');

                        $batchToInsert[] = [
                            'user_id'              => (int) $sourceResult->user_id,
                            'assesment_id'         => (int) $newAssessmentId,
                            'user_answer'          => json_encode($newUserAnswer),
                            'question_snapshot'    => $newSnapshot !== null ? json_encode($newSnapshot) : null,
                            'final'                => (int) $sourceResult->final,
                            'score'                => $sourceResult->score !== null ? (int) $sourceResult->score : null,
                            'batch'                => (int) $sourceResult->batch,
                            'pass'                 => (int) $sourceResult->pass,
                            'paper_based'          => $sourceResult->paper_based ? 1 : 0,
                            'evidence_paper_based' => $sourceResult->evidence_paper_based,
                            'created_at'           => $createdAt,
                            'updated_at'           => $updatedAt,
                        ];

                        // Increment in-memory target count for this fingerprint
                        $targetCounts[$fp] = ($targetCounts[$fp] ?? 0) + 1;
                    }

                    if (!empty($batchToInsert)) {
                        AssesmentResult::insert($batchToInsert);
                        $totalCopied += count($batchToInsert);
                    }
                });
        });

        return $totalCopied;
    }

    /**
     * Copy results for a batch of created items produced by AssessmentBulkDuplicateService.
     *
     * @param array $createdItems Array of ['source_assessment_id' => int, 'new_assessment_id' => int, ...]
     * @param array $options
     * @return array Summary of copy operations
     */
    public function copyResultsForBatch(array $createdItems, array $options = []): array
    {
        $summary = [];

        foreach ($createdItems as $item) {
            $sourceId = isset($item['source_assessment_id']) ? (int) $item['source_assessment_id'] : 0;
            $newId    = isset($item['new_assessment_id']) ? (int) $item['new_assessment_id'] : 0;

            if ($sourceId > 0 && $newId > 0) {
                try {
                    $copiedCount = $this->copyResults($sourceId, $newId, $options);
                    $summary[] = [
                        'source_assessment_id' => $sourceId,
                        'new_assessment_id'    => $newId,
                        'copied_results_count' => $copiedCount,
                        'status'               => 'SUCCESS',
                    ];
                } catch (\Throwable $e) {
                    Log::error("AssessmentResultDuplicationService failed for Source {$sourceId} -> New {$newId}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $summary[] = [
                        'source_assessment_id' => $sourceId,
                        'new_assessment_id'    => $newId,
                        'copied_results_count' => 0,
                        'status'               => 'FAILED',
                        'error'                => $e->getMessage(),
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * Compute a structural fingerprint of an AssesmentResult for multiset idempotency.
     * Note: Does not include question keys in user_answer so that remapped target results
     * match source results with 100% fidelity.
     *
     * @param AssesmentResult|object $result
     * @return string
     */
    public function computeFingerprint($result): string
    {
        $answers = $result->user_answer;
        if (is_string($answers)) {
            $answers = json_decode($answers, true) ?: [];
        }
        $answerValues = is_array($answers) ? array_values($answers) : [];
        sort($answerValues);

        $snapshot = $result->question_snapshot;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }
        $snapshotCount = is_array($snapshot) ? count($snapshot) : -1;

        $createdAtStr = $result->created_at ? (string) $result->created_at : '';

        return implode('|', [
            (int) $result->user_id,
            (int) $result->batch,
            (int) $result->final,
            $result->score !== null ? (int) $result->score : 'NULL',
            (int) $result->pass,
            $result->paper_based ? 1 : 0,
            (string) $result->evidence_paper_based,
            $createdAtStr,
            md5(json_encode($answerValues)),
            $snapshotCount,
        ]);
    }

    /**
     * Build a deterministic mapping from source question IDs to new duplicated question IDs.
     * 
     * 1. Bank Questions: Deterministically matched via (question_bank_id, question_id).
     * 2. Direct Questions: Deterministically matched via ordinal sequential creation order,
     *    mirroring the exact foreach loop executed by AssessmentDuplicateService.
     *
     * @param int $sourceAssessmentId
     * @param int $newAssessmentId
     * @return array<int, int> Map of [source_question_id => new_question_id]
     */
    public function buildQuestionMapping(int $sourceAssessmentId, int $newAssessmentId): array
    {
        $mapping = [];

        // 1. Bank questions mapping: strictly by (question_bank_id, question_id)
        $sourceBankQuestions = AssesmentQuestion::where('assesment_id', $sourceAssessmentId)
            ->whereNotNull('question_bank_id')
            ->whereNotNull('question_id')
            ->orderBy('id')
            ->get();

        $newBankQuestions = AssesmentQuestion::where('assesment_id', $newAssessmentId)
            ->whereNotNull('question_bank_id')
            ->whereNotNull('question_id')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        foreach ($sourceBankQuestions as $sq) {
            $match = $newBankQuestions->first(function ($nq) use ($sq) {
                return (int) $nq->question_bank_id === (int) $sq->question_bank_id
                    && (int) $nq->question_id === (int) $sq->question_id;
            });

            if ($match) {
                $mapping[(int) $sq->id] = (int) $match->id;
                $newBankQuestions->forget($match->id);
            }
        }

        // 2. Direct questions mapping: strictly by ordinal sequential creation order
        $sourceDirectQuestions = AssesmentQuestion::where('assesment_id', $sourceAssessmentId)
            ->whereNull('question_bank_id')
            ->orderBy('id')
            ->get()
            ->values();

        $newDirectQuestions = AssesmentQuestion::where('assesment_id', $newAssessmentId)
            ->whereNull('question_bank_id')
            ->orderBy('id')
            ->get()
            ->values();

        if ($sourceDirectQuestions->count() === $newDirectQuestions->count() && $sourceDirectQuestions->count() > 0) {
            foreach ($sourceDirectQuestions as $idx => $sq) {
                $nq = $newDirectQuestions[$idx];
                // Verify attributes consistency
                if ((string) $sq->question === (string) $nq->question && (string) $sq->answer === (string) $nq->answer) {
                    $mapping[(int) $sq->id] = (int) $nq->id;
                } else {
                    Log::warning("Direct question attribute difference at ordinal index {$idx} between Source Q#{$sq->id} and New Q#{$nq->id}");
                    $mapping[(int) $sq->id] = (int) $nq->id;
                }
            }
        }

        return $mapping;
    }

    /**
     * Remap user_answer associative array keys from source question IDs to new question IDs.
     *
     * @param array|null $userAnswer
     * @param array<int, int> $questionMapping
     * @return array
     */
    public function remapUserAnswer(?array $userAnswer, array $questionMapping): array
    {
        if (empty($userAnswer) || !is_array($userAnswer)) {
            return [];
        }

        $remapped = [];
        foreach ($userAnswer as $qId => $answer) {
            $mappedQid = isset($questionMapping[(int) $qId])
                ? (string) $questionMapping[(int) $qId]
                : (string) $qId;

            $remapped[$mappedQid] = $answer;
        }

        return $remapped;
    }

    /**
     * Remap question_snapshot question_ids from source question IDs to new question IDs.
     *
     * @param array|null $questionSnapshot
     * @param array<int, int> $questionMapping
     * @return array|null
     */
    public function remapQuestionSnapshot(?array $questionSnapshot, array $questionMapping): ?array
    {
        if ($questionSnapshot === null) {
            return null;
        }

        if (!is_array($questionSnapshot)) {
            return [];
        }

        $remapped = [];
        foreach ($questionSnapshot as $item) {
            if (is_array($item)) {
                $oldQid = isset($item['question_id']) ? (int) $item['question_id'] : null;
                if ($oldQid !== null && isset($questionMapping[$oldQid])) {
                    $item['question_id'] = $questionMapping[$oldQid];
                }
                $remapped[] = $item;
            } else {
                $remapped[] = $item;
            }
        }

        return $remapped;
    }
}
