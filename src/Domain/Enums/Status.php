<?php

namespace YSOCode\Commit\Domain\Enums;

enum Status
{
    case STARTED;
    case RUNNING;
    case FAILED;
    case FINISHED;
}
