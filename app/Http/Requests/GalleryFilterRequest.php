<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q'           => ['nullable', 'string', 'max:100'],
            'gallery_source' => ['nullable', Rule::in(['advanced'])],
            'uploader'    => ['nullable', 'string', 'max:255'],
            'modal_uploader' => ['nullable', 'string', 'max:255'],
            'category'    => ['nullable', 'array', 'max:50'],
            'category.*'  => ['string', 'max:255', 'distinct'],
            'year'        => ['nullable', 'array', 'max:30'],
            'year.*'      => ['integer', 'between:1900,' . (now()->year + 1), 'distinct'],
            'month'       => ['nullable', 'array', 'max:12'],
            'month.*'     => ['integer', 'between:1,12', 'distinct'],
            'date_from'   => ['nullable', 'date_format:Y-m-d'],
            'date_to'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'eventdate'   => ['nullable', 'date_format:Y-m-d'],
            'views'       => ['nullable', Rule::in(['most_viewed', 'least_viewed'])],
            'modal_views' => ['nullable', Rule::in(['most_viewed', 'least_viewed'])],
            'likes'       => ['nullable', Rule::in(['most_liked', 'least_liked'])],
            'status'      => ['nullable', 'array', 'max:1'],
            'status.*'    => ['string', Rule::in(['Active'])],
            'sort'        => ['nullable', Rule::in([
                'latest',
                'oldest',
                'most_viewed',
                'least_viewed',
                'most_liked',
                'least_liked',
                'az',
                'za',
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['category', 'year', 'month', 'status'] as $key) {
            if (!$this->exists($key)) {
                continue;
            }

            $value = $this->input($key);
            $values = is_array($value) ? $value : [$value];
            $normalized[$key] = array_values(array_filter(
                $values,
                static fn ($item) => $item !== null && $item !== '' && $item !== 'all'
            ));
        }

        if (!$this->filled('date_from') && !$this->filled('date_to') && $this->filled('eventdate')) {
            $normalized['date_from'] = $this->input('eventdate');
            $normalized['date_to'] = $this->input('eventdate');
        }

        if ($this->input('gallery_source') === 'advanced' && $this->exists('modal_uploader')) {
            $normalized['uploader'] = $this->input('modal_uploader');
        }

        if ($this->input('gallery_source') === 'advanced' && $this->exists('modal_views')) {
            $normalized['views'] = $this->input('modal_views');
        }

        $this->merge($normalized);
    }
}
