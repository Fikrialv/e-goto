<?php

namespace App\Enums;

/**
 * Role dipakai di keputusan keamanan (middleware `role:` dan
 * User::canAccessPanel), jadi dikunci sebagai enum — bukan string bebas —
 * supaya tidak ada panel yang lolos karena salah ketik nilai role.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Vendor = 'vendor';
    case Customer = 'customer';
}
