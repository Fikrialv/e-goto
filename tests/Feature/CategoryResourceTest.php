<?php

use App\Enums\IdType;
use App\Enums\UserRole;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

/**
 * CRUD kategori di panel admin — gap yang sempat kelewat dari D1-D7
 * (GUIDE.md menjanjikan "Admin: CRUD trip" tapi resource-nya belum pernah
 * dibangun). Checklist perlengkapan (D7.6) ikut diuji di sini karena field-nya
 * menempel di form yang sama, bukan alur terpisah.
 */
function admin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('membuat kategori baru lengkap dengan checklist perlengkapan', function () {
    Livewire::actingAs(admin())
        ->test(CreateCategory::class)
        ->fillForm([
            'name' => 'Pendakian Gunung',
            'slug' => 'pendakian-gunung',
            'id_requirement' => IdType::Nik->value,
            'is_active' => true,
            'sort_order' => 1,
            // Repeater::simple() menyimpan state runtime bersarang per baris
            // (livewire, bukan bentuk flat hasil dehydrate) — fillForm() di test
            // menulis langsung ke state itu, jadi ikut bentuk bersarangnya.
            'gear_checklist' => [
                'row-1' => ['item' => 'Tenda'],
                'row-2' => ['item' => 'Sleeping bag'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::firstOrFail();

    expect($category->name)->toBe('Pendakian Gunung')
        ->and($category->id_requirement)->toBe(IdType::Nik)
        ->and($category->gear_checklist)->toBe(['Tenda', 'Sleeping bag']);
});

it('menolak slug yang sudah dipakai kategori lain', function () {
    Category::factory()->create(['slug' => 'pantai-demo']);

    Livewire::actingAs(admin())
        ->test(CreateCategory::class)
        ->fillForm([
            'name' => 'Pantai Lain',
            'slug' => 'pantai-demo',
            'id_requirement' => IdType::None->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(Category::count())->toBe(1);
});
