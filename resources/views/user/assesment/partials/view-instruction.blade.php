<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md">
                <h3 class="mb-1">Instruksi Pengerjaan</h3>
                <div class="text-muted">
                    Pilih satu jawaban yang menurut Anda paling benar. Jawaban akan tersimpan otomatis saat berpindah soal.
                </div>
            </div>

            <div class="col-md-auto">
                <span class="badge bg-blue-lt text-blue">
                    {{ count($assesment_question ?? []) }} Questions
                </span>
            </div>
        </div>
    </div>
</div>
