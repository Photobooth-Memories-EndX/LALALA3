<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Filter User</h3>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('manage.role.index') }}">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text"
                           name="s"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Cari nama / email / NIP...">
                </div>

                <div class="col-md-3">
                    <select name="division" class="form-select">
                        <option value="">All Division</option>
                        @foreach ($divisions as $item)
                            <option value="{{ $item }}" {{ $division == $item ? 'selected' : '' }}>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="department" class="form-select">
                        <option value="">All Department</option>
                        @foreach ($departments as $item)
                            <option value="{{ $item }}" {{ $department == $item ? 'selected' : '' }}>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="team" class="form-select">
                        <option value="">All Team</option>
                        @foreach ($teams as $item)
                            <option value="{{ $item }}" {{ $team == $item ? 'selected' : '' }}>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('manage.role.index') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
