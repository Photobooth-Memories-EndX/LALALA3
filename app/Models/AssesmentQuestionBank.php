<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentQuestionBank extends Model
{
    use HasFactory;

    protected $table   = 'assesment_question_banks';
    protected $guarded = ['id'];

    public function question_bank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id', 'id');
    }
}
