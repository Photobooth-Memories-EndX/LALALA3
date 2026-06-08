@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="page-title mb-1">
                    Certificate Setting
                </h2>

                <div class="text-muted">
                    Pengaturan syarat sertifikat OWL LMS.
                </div>
            </div>

            <a href="#" class="btn btn-primary">
                <i class="ti ti-plus mr-1"></i>
                Add Setting
            </a>

        </div>

        <div class="card border-0 shadow-sm">

            <div class="table-responsive">

                <table class="table table-vcenter table-hover card-table mb-0">

                    <thead>
                        <tr>
                            <th>Content</th>
                            <th>Assessment</th>
                            <th>Min Progress</th>
                            <th>Min Score</th>
                            <th>Issuer</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($settings as $setting)

                            <tr class="certificate-row">

                                <td>
                                    @if($setting->content)
                                        <a href="{{ route('content.edit', $setting->content->id) }}"
                                           class="text-primary font-weight-bold"
                                           title="Buka modul content">
                                            {{ $setting->content->title }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if($setting->assessment)
                                        <a href="{{ route('assesment.edit', $setting->assessment->id) }}"
                                           class="text-primary font-weight-bold"
                                           title="Buka assessment">
                                            {{ $setting->assessment->title }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ $setting->minimum_progress }}%
                                </td>

                                <td>
                                    {{ $setting->minimum_score }}
                                </td>

                                <td>
                                    {{ $setting->issuer_name }}
                                </td>

                                <td>
                                    @if($setting->is_active)
                                        <span class="badge bg-green-lt text-green">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge bg-red-lt text-red">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="text-right">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-toggle="modal"
                                        data-target="#editCertificateSettingModal{{ $setting->id }}">
                                        <i class="ti ti-edit mr-1"></i>
                                        Edit
                                    </button>
                                </td>

                            </tr>

                            <div class="modal fade"
                                 id="editCertificateSettingModal{{ $setting->id }}"
                                 tabindex="-1"
                                 role="dialog"
                                 aria-hidden="true">

                                <div class="modal-dialog modal-dialog-centered" role="document">

                                    <div class="modal-content border-0 shadow">

                                        <form method="POST" action="#">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Edit Certificate Setting
                                                </h5>

                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">

                                                <div class="form-group">
                                                    <label class="form-label">Content</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           value="{{ $setting->content->title ?? '-' }}"
                                                           readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Assessment</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           value="{{ $setting->assessment->title ?? '-' }}"
                                                           readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Minimum Progress</label>
                                                    <input type="number"
                                                           name="minimum_progress"
                                                           class="form-control"
                                                           min="0"
                                                           max="100"
                                                           value="{{ $setting->minimum_progress }}">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Minimum Score</label>
                                                    <input type="number"
                                                           name="minimum_score"
                                                           class="form-control"
                                                           min="0"
                                                           max="100"
                                                           value="{{ $setting->minimum_score }}">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Issuer Name</label>
                                                    <input type="text"
                                                           name="issuer_name"
                                                           class="form-control"
                                                           value="{{ $setting->issuer_name }}">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Status</label>
                                                    <select name="is_active" class="form-control">
                                                        <option value="1" {{ $setting->is_active ? 'selected' : '' }}>
                                                            Active
                                                        </option>
                                                        <option value="0" {{ !$setting->is_active ? 'selected' : '' }}>
                                                            Inactive
                                                        </option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-dismiss="modal">
                                                    Cancel
                                                </button>

                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ti ti-device-floppy mr-1"></i>
                                                    Save Setting
                                                </button>
                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No certificate setting found
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="mt-3">
            {{ $settings->links() }}
        </div>

    </div>
</div>

@endsection

@section('style')
<style>
    .certificate-row:hover {
        background: rgba(32, 107, 196, .08);
        cursor: pointer;
    }

    .certificate-row a {
        text-decoration: none;
    }

    .certificate-row a:hover {
        text-decoration: underline;
    }
</style>
@endsection 
