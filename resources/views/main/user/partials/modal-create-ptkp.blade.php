<div class="modal fade" id="modalCreatePtkp" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('user.store') }}" method="POST" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">
                    Create User PTKP
                </h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="input-group mb-3">
                    <input type="text"
                        class="form-control"
                        placeholder="Enter ID Employee"
                        name="id_emp"
                        id="create_id_emp">

                    <div class="input-group-append">
                        <button
                            type="button"
                            id="btn-api-create"
                            class="btn btn-success">
                            Get Data
                        </button>
                    </div>
                </div>

                <input type="hidden" name="id_org_unit" id="create_id_org_unit">
                <input type="hidden" name="id_user" id="create_id_user">
                <input type="hidden" name="ssohash" id="create_ssohash">
                <input type="hidden" name="sap_code" id="create_sap_code">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text"
                        class="form-control"
                        name="name"
                        id="create_name">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                        class="form-control"
                        name="email"
                        id="create_email">
                </div>

                <div class="form-group">
                    <label>Team</label>
                    <input type="text"
                        class="form-control"
                        name="team"
                        id="create_team">
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text"
                        class="form-control"
                        name="department"
                        id="create_department">
                </div>

                <div class="form-group">
                    <label>Division</label>
                    <input type="text"
                        class="form-control"
                        name="division"
                        id="create_division">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-primary">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>