<div class="card playlist-filter-card border-0 shadow-sm rounded-4 mb-4">

    <div class="card-body p-3">

        <div class="row g-3 align-items-center">

            <div class="col-lg-9">

                <div class="input-icon">

                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>

                    <input
                        type="text"
                        id="playlistSearch"
                        class="form-control"
                        placeholder="Search playlist...">

                </div>

            </div>

            {{-- <div class="col-lg-3">

                <select id="playlistCategoryFilter" class="form-select">
                    <option value="all">All Categories</option>
                    <option value="business">Business</option>
                    <option value="safety">Safety</option>
                    <option value="leadership">Leadership</option>
                    <option value="technical">Technical</option>
                </select>

            </div> --}}

            <div class="col-lg-3">

                <select id="playlistSort" class="form-select">
                    {{-- <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option> --}}
                    <option value="az">A - Z</option>
                    <option value="za">Z - A</option>
                </select>

            </div>

        </div>

    </div>

</div>
