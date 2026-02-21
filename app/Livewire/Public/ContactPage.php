<?php

namespace App\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ContactPage extends Component
{
    public function render()
    {
        return view('livewire.public.contact-page');
    }
}

