<?php

use App\Enums\IdType;
use App\Models\Category;

it('menanam 6 kategori dengan Internasional dinonaktifkan', function () {
    $this->seed();

    expect(Category::count())->toBe(6)
        ->and(Category::where('slug', 'internasional')->value('is_active'))->toBeFalse()
        ->and(Category::where('slug', 'pendakian')->value('id_requirement'))->toBe(IdType::Nik);
});
