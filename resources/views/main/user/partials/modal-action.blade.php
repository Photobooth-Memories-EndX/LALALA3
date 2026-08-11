<div class="modal fade" id="modalAction" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form id="formAction" method="POST" class="modal-content">

            @csrf

            <input type="hidden" id="action_method" name="_method">

            <div class="modal-body text-center">

                <h4 id="action_title">
                    Confirmation
                </h4>

                <p id="action_message" class="text-muted">
                    Are you sure?
                </p>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Cancel

                </button>

                <button
                    type="submit"
                    id="action_button"
                    class="btn btn-danger">

                    Confirm

                </button>

            </div>

        </form>
    </div>
</div>