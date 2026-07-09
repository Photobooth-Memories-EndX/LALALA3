<div class="modal fade" id="modalEditPtkp" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formEditPtkp" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')

            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title mb-0">
                        Edit User PTKP
                    </h5>
                    <small class="text-white-50">
                        Perbarui data user berdasarkan employee ID.
                    </small>
                </div>
                    <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
            </div>

            <div class="modal-body p-4">

                    <div class="alert alert-info py-2 mb-3">
                        Click <strong>Check Update</strong> to sync the latest data from the employee system.
                    </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Employee ID</label>
                    <div class="input-group">
                        <input type="text"
                            class="form-control"
                            name="id_emp"
                            id="edit_id_emp">

                        {{-- <div class="input-group-append">
                            <button type="button" id="btn-api-edit" class="btn btn-success">
                                Check Update
                            </button>
                        </div> --}}
                    </div>
                </div>

                <input type="hidden" name="id_org_unit" id="edit_id_org_unit">
                <input type="hidden" name="id_user" id="edit_id_user">
                <input type="hidden" name="ssohash" id="edit_ssohash">
                <input type="hidden" name="sap_code" id="edit_sap_code">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Full Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Team</label>
                    <input type="text" class="form-control" name="team" id="edit_team">
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Department</label>
                    <input type="text" class="form-control" name="department" id="edit_department">
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">Division</label>
                    <input type="text" class="form-control" name="division" id="edit_division">
                </div>

            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-warning">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
