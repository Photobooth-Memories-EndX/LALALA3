<div class="modal fade" id="modalEditPtkp" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formEditPtkp"
            method="POST"
            class="modal-content">

            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">
                    Edit User PTKP
                </h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="input-group mb-3">
                    <input type="text"
                        class="form-control"
                        name="id_emp"
                        id="edit_id_emp">

                    <div class="input-group-append">
                        <button
                            type="button"
                            id="btn-api-edit"
                            class="btn btn-success">
                            Check Update
                        </button>
                    </div>
                </div>

                <input type="hidden" name="id_org_unit" id="edit_id_org_unit">
                <input type="hidden" name="id_user" id="edit_id_user">
                <input type="hidden" name="ssohash" id="edit_ssohash">
                <input type="hidden" name="sap_code" id="edit_sap_code">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text"
                        class="form-control"
                        name="name"
                        id="edit_name">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                        class="form-control"
                        name="email"
                        id="edit_email">
                </div>

                <div class="form-group">
                    <label>Team</label>
                    <input type="text"
                        class="form-control"
                        name="team"
                        id="edit_team">
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text"
                        class="form-control"
                        name="department"
                        id="edit_department">
                </div>

                <div class="form-group">
                    <label>Division</label>
                    <input type="text"
                        class="form-control"
                        name="division"
                        id="edit_division">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-warning">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>