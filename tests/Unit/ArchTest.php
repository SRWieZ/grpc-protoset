<?php

use SRWieZ\GrpcProtoset\Exceptions\Exception;

arch('debug')->preset()->php();

// Exceptions
arch('exceptions')
    ->expect('SRWieZ\GrpcProtoset\Exceptions\Exceptions')
    ->toExtend(Exception::class)
    ->ignoring(Exception::class);
