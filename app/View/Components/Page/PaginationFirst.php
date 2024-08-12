<?php

namespace App\View\Components\Page;

use Illuminate\View\Component;

class PaginationFirst extends Component
{
    public $count;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($count)
    {
        $this->count    = $count;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.page.pagination-first');
    }
}
