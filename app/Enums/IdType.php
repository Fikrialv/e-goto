<?php

namespace App\Enums;

/**
 * Jenis identitas yang diwajibkan sebuah kategori trip
 * (categories.id_requirement) dan yang tercatat per peserta
 * (booking_participants.id_type).
 */
enum IdType: string
{
    case None = 'none';
    case Nik = 'nik';
    case Passport = 'passport';
}
