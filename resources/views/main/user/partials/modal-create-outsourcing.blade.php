<div class="modal fade" id="modalCreateOutsourcing" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('manage.store.user.outsourcing') }}"
            method="POST"
            class="modal-content">

            @csrf

            <div class="modal-header">
                <h5 class="modal-title">
                    Create User Non-PTKP
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
                        name="id_employee">
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text"
                        class="form-control"
                        name="name">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                        class="form-control"
                        name="email">
                </div>

                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text"
                        class="form-control"
                        name="company_name">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-success">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>