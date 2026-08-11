    <div class="modal fade"
     id="addCertificateSettingModal"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow">

            <form method="POST"
                  action="{{ route('certificate.setting.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Certificate Setting
                    </h5>

                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    @include('main.certificate.components.form-fields')

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
                        Save Setting
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>