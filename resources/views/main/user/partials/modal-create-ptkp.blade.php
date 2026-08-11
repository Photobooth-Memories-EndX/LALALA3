<div class="modal fade" id="modalCreatePtkp" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('user.store') }}"
            method="POST"
            class="modal-content border-0 shadow">

```
        @csrf

        <div class="modal-header bg-primary text-white border-0">

            <div>
                <h5 class="modal-title mb-0">
                    Create User PTKP
                </h5>

                <small class="text-white-50">
                    Tambahkan user baru berdasarkan Employee ID.
                </small>
            </div>

            <button type="button"
                class="modal-close-btn"
                data-dismiss="modal">

                <span>&times;</span>

            </button>

        </div>

        <div class="modal-body p-4">

                <div class="alert alert-info py-2 mb-4">
                    Click <strong>Get Data</strong> to synchronize employee data from the system.
                </div>

            <div class="form-group mb-4">

                <label class="font-weight-bold">
                    Employee ID
                </label>

                <div class="input-group">

                    <input type="text"
                        class="form-control"
                        placeholder="Enter Employee ID"
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

            </div>

            <input type="hidden" name="id_org_unit" id="create_id_org_unit">
            <input type="hidden" name="id_user" id="create_id_user">
            <input type="hidden" name="ssohash" id="create_ssohash">
            <input type="hidden" name="sap_code" id="create_sap_code">

            <div class="row">

                <div class="col-md-6">

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Full Name
                        </label>

                        <input type="text"
                            class="form-control"
                            name="name"
                            id="create_name">
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Email Address
                        </label>

                        <input type="email"
                            class="form-control"
                            name="email"
                            id="create_email">
                    </div>

                </div>

            </div>

            <div class="form-group">
                <label class="font-weight-bold">
                    Team
                </label>

                <input type="text"
                    class="form-control"
                    name="team"
                    id="create_team">
            </div>

            <div class="form-group">
                <label class="font-weight-bold">
                    Department
                </label>

                <input type="text"
                    class="form-control"
                    name="department"
                    id="create_department">
            </div>

            <div class="form-group mb-0">
                <label class="font-weight-bold">
                    Division
                </label>

                <input type="text"
                    class="form-control"
                    name="division"
                    id="create_division">
            </div>

        </div>

        <div class="modal-footer bg-light">

            <button type="button"
                class="btn btn-outline-secondary"
                data-dismiss="modal">

                Cancel

            </button>

            <button type="submit"
                class="btn btn-primary">

                Create User

            </button>

        </div>

    </form>
</div>
```

</div>
