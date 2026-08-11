<div class="modal fade" id="modalEditOutsourcing" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditOutsourcing"
            method="POST"
            class="modal-content">

            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">
                    Edit User Non-PTKP
                </h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="form-group">
                    <label>ID Employee</label>
                    <input type="text"
                        class="form-control"
                        name="id_employee"
                        id="edit_out_id_employee">
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text"
                        class="form-control"
                        name="name"
                        id="edit_out_name">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                        class="form-control"
                        name="email"
                        id="edit_out_email">
                </div>

                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text"
                        class="form-control"
                        name="company_name"
                        id="edit_out_company">
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