@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="d-flex justify-content-center">
        <ul class="pagination owl-pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">
                        <i class="ti ti-chevron-left me-1"></i>Previous
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="ti ti-chevron-left me-1"></i>Previous
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        Next<i class="ti ti-chevron-right ms-1"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        Next<i class="ti ti-chevron-right ms-1"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>

    <style>
        .owl-pagination {
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .owl-pagination .page-item {
            margin: 0;
        }

        .owl-pagination .page-link {
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.75rem;
            color: #05507D;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .owl-pagination .page-link:hover {
            border-color: #05507D;
            background: rgba(37, 99, 235, 0.05);
            transform: translateY(-2px);
        }

        .owl-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #05507D, #6AAED6);
            border-color: #05507D;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .owl-pagination .page-item.disabled .page-link {
            color: #cbd5e1;
            border-color: #e2e8f0;
            background: transparent;
            cursor: not-allowed;
        }
    </style>
@endif
