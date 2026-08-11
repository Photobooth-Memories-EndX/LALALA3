<div class="modal fade" id="modalCreateOutsourcing" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('manage.store.user.outsourcing') }}"
            method="POST"
            class="modal-content border-0 shadow">

```
        @csrf

        <div class="modal-header bg-success text-white border-0">

            <div>
                <h5 class="modal-title mb-0">
                    Create User Non-PTKP
                </h5>

                <small class="text-white-50">
                    Tambahkan user outsourcing atau vendor ke OWL LMS.
                </small>
            </div>

            <button type="button"
                class="modal-close-btn"
                data-dismiss="modal">

                <span>&times;</span>

            </button>

        </div>

        <div class="modal-body p-4">

    <div class="alert alert-success py-2 mb-4">
        Complete the Non-PTKP user details before creating the account.
    </div>

    <div class="form-group">
        <label class="font-weight-bold">
            Employee ID
        </label>

        <input type="text"
            class="form-control"
            name="id_employee"
            placeholder="e.g., 10001">
    </div>

    <div class="form-group">
        <label class="font-weight-bold">
            Full Name
        </label>

        <input type="text"
            class="form-control"
            name="name"
            placeholder="Enter the full name">
    </div>

    <div class="form-group">
        <label class="font-weight-bold">
            Email Address
        </label>

        <input type="email"
            class="form-control"
            name="email"
            placeholder="user@company.com">
    </div>

    <div class="form-group mb-0">
        <label class="font-weight-bold">
            Company Name
        </label>

        <input type="text"
            class="form-control"
            name="company_name"
            placeholder="Enter the company or vendor name">
    </div>

</div>

        <div class="modal-footer bg-light">

            <button type="button"
                class="btn btn-outline-secondary"
                data-dismiss="modal">

                Cancel

            </button>

            <button type="submit"
                class="btn btn-success">

                Create User

            </button>

        </div>

    </form>
</div>
```

</div>
