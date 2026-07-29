@php
    $selectedEventDate = request('eventdate');
    $eventDateTitle = $selectedEventDate
        ? 'Event Date: ' . date('d M Y', strtotime($selectedEventDate))
        : 'Event Date';
@endphp

<div class="gallery-filter-card mb-4">
    <form action="{{ route('gallery.user.index') }}" method="GET" class="gallery-filter-form">
        <div class="gallery-filter-toolbar">
            <div class="gallery-filter-options">
                <div class="gallery-filter-option">
                    <label class="visually-hidden" for="gallery-uploader">Uploader</label>
                    <select id="gallery-uploader" name="uploader" class="form-select" aria-label="Filter by uploader">
                        <option value="">All Uploaders</option>
                        @foreach($uploaders as $up)
                            <option value="{{ $up }}" {{ request('uploader') === $up ? 'selected' : '' }}>
                                {{ $up }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="gallery-filter-option">
                    <label class="visually-hidden" for="gallery-views">Views</label>
                    <select id="gallery-views" name="views" class="form-select" aria-label="Order by views">
                        <option value="">All Views</option>
                        <option value="most_viewed" {{ request('views') === 'most_viewed' ? 'selected' : '' }}>Most Viewed</option>
                        <option value="least_viewed" {{ request('views') === 'least_viewed' ? 'selected' : '' }}>Least Viewed</option>
                    </select>
                </div>

                <div class="gallery-filter-option">
                    <label class="visually-hidden" for="gallery-sort">Sort By</label>
                    <select id="gallery-sort" name="sort" class="form-select" aria-label="Sort gallery">
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="az" {{ request('sort') === 'az' ? 'selected' : '' }}>A → Z</option>
                        <option value="za" {{ request('sort') === 'za' ? 'selected' : '' }}>Z → A</option>
                    </select>
                </div>
            </div>

            <div class="gallery-filter-actions">
                <div class="gallery-filter-search input-group">
                    <span class="input-group-text bg-transparent">
                        <i class="ti ti-search"></i>
                    </span>
                    <label class="visually-hidden" for="gallery-search">Search Events</label>
                    <input
                        id="gallery-search"
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-control"
                        placeholder="Search by title..."
                    >
                </div>

                <div class="gallery-date-picker">
                    <button
                        type="button"
                        class="gallery-date-trigger {{ $selectedEventDate ? 'is-active' : '' }}"
                        aria-label="Select event date"
                        title="{{ $eventDateTitle }}"
                        aria-controls="gallery-eventdate"
                    >
                        <i class="ti ti-calendar"></i>
                    </button>
                    <input
                        id="gallery-eventdate"
                        type="date"
                        name="eventdate"
                        value="{{ $selectedEventDate }}"
                        class="gallery-date-input"
                        aria-label="Select event date"
                        tabindex="-1"
                    >
                </div>

                <button
                    type="submit"
                    class="gallery-filter-submit"
                    aria-label="Search gallery"
                    title="Search"
                >
                    <i class="ti ti-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>
