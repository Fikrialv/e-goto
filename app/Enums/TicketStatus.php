<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Issued = 'issued';
    case Used = 'used';
    case Void = 'void';
}
