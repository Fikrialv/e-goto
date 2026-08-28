@props([
    'icon' => 'camera',
    'label' => null,
])

{{--
    Pengganti gambar saat foto aslinya belum ada. Satu komponen untuk keempat
    titiknya (kartu trip, hero homepage, panel halaman masuk, seksi mitra) supaya
    kekosongan terlihat sebagai satu keputusan, bukan empat tambalan berbeda.

    Dua hal yang sengaja TIDAK dilakukan di sini: memasang foto stok dari CDN
    mana pun (dilarang di GUIDE.md — foto stok generik adalah ciri paling cepat
    terbaca "dibuat AI"), dan memakai abu-abu polos yang membuat halaman
    terlihat rusak. Yang dipakai: gradasi lembut dari token brand + satu ikon
    besar beropasitas rendah, jadi jelas ini bidang yang memang dirancang
    kosong — bukan foto yang gagal dimuat.

    Gradasi di sini satu-satunya pengecualian dari larangan gradient di
    docs/DESIGN_SYSTEM.md: justru gradasilah yang membedakan bidang ini dari
    kartu berlatar solid di sekitarnya, sehingga tidak tertukar dengan konten.
--}}
<div {{ $attributes->merge(['class' => 'relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br from-mist-100 via-mist-200 to-teal-200']) }}>
    @svg('lucide-'.$icon, 'size-12 text-teal-700/25 sm:size-16')

    @if ($label)
        <span class="absolute inset-x-0 bottom-4 px-4 text-center text-xs tracking-wide text-teal-700/70 uppercase">
            {{ $label }}
        </span>
    @endif
</div>
