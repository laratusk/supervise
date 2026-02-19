<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Enums;

enum WorkerType: string
{
    case Horizon = 'horizon';
    case Queue = 'queue';
    case Reverb = 'reverb';
}
