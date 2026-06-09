<div class="modal fade"
     id="editCertificateSettingModal{{ $setting->id }}"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow">

            <form method="POST"
                  action="{{ route('certificate.setting.update', $setting->id) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Certificate Setting
                    </h5>

                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    @include('main.certificate.components.form-fields', [
                        'setting' => $setting
                    ])

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-light"
                            data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        <i class="ti ti-device-floppy mr-1"></i>
                        Update Setting
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>